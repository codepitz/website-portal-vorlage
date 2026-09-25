<?php
/**
 * Anmeldung, Einrichtung, Einstellungen und Bremse gegen Passwort-Raten.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const ANMELDUNG_VERSUCHE = 6;    // so viele Fehlversuche sind frei
const ANMELDUNG_SPERRE   = 900;  // danach 15 Minuten Pause

/** Gibt es eine Konfiguration? Dann ist die Einrichtung für immer zu. */
function eingerichtet(): bool
{
    return is_file(KONFIG_DATEI);
}

function angemeldet(): bool
{
    if (empty($_SESSION['benutzer'])) {
        return false;
    }
    $b = benutzer_holen((string) $_SESSION['benutzer']);
    // Konto gelöscht, gesperrt oder Passwort geändert: dann gilt die Sitzung nicht mehr
    if (!$b || !empty($b['gesperrt']) || (int) ($b['version'] ?? 1) !== (int) ($_SESSION['version'] ?? 0)) {
        abmelden();
        return false;
    }
    $dauer = max(5, (int) konfig('sitzungsdauer')) * 60;
    if (time() - (int) ($_SESSION['aktiv'] ?? 0) > $dauer) {
        abmelden();
        return false;
    }
    $_SESSION['aktiv'] = time();
    return true;
}

function abmelden(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function anmeldung_kennung(): string
{
    return substr(hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli')), 0, 16);
}

function anmeldung_gesperrt(): int
{
    $e = (json_lesen(pfad_ablage('anmeldung.json')) ?? [])[anmeldung_kennung()] ?? null;
    return ($e && ($e['bis'] ?? 0) > time()) ? (int) $e['bis'] - time() : 0;
}

function anmeldung_zaehlen(bool $erfolg): void
{
    $datei = pfad_ablage('anmeldung.json');
    $log = json_lesen($datei) ?? [];
    $k = anmeldung_kennung();
    foreach ($log as $key => $e) {
        if (($e['zuletzt'] ?? 0) < time() - 86400) {
            unset($log[$key]);
        }
    }
    if ($erfolg) {
        unset($log[$k]);
    } else {
        $anzahl = (int) ($log[$k]['anzahl'] ?? 0) + 1;
        $log[$k] = ['anzahl' => $anzahl, 'zuletzt' => time(),
                    'bis' => $anzahl >= ANMELDUNG_VERSUCHE ? time() + ANMELDUNG_SPERRE : 0];
    }
    try {
        json_schreiben($datei, $log);
    } catch (Throwable) {
        // Kein Schreibrecht: dann eben ohne Bremse — die Systemprüfung meldet das.
    }
}

/** @return string ok | falsch | gesperrt */
function anmelden(string $name, string $passwort): string
{
    $b = benutzer_holen($name);
    // Auch bei unbekanntem Namen rechnen, damit die Antwortzeit nichts verrät
    $ok = password_verify($passwort, $b['hash'] ?? '$2y$10$usedforcomparisononlyxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx') && $b !== null;
    anmeldung_zaehlen($ok);
    if (!$ok) {
        return 'falsch';
    }
    if (!empty($b['gesperrt'])) {
        return 'gesperrt';
    }

    $alle = benutzer_alle();
    if (password_needs_rehash($b['hash'], PASSWORD_DEFAULT)) {
        $alle[$name]['hash'] = password_hash($passwort, PASSWORD_DEFAULT);
    }
    $alle[$name]['zuletzt'] = date('c');
    try {
        benutzer_sichern($alle);
    } catch (Throwable) {
    }

    session_regenerate_id(true);
    $_SESSION['benutzer'] = $name;
    $_SESSION['version'] = (int) ($b['version'] ?? 1);
    $_SESSION['aktiv'] = time();
    protokoll('angemeldet');
    return 'ok';
}

/**
 * Prüft die beiden Ordner.
 * @return array{0:string[],1:string,2:string} [Fehler, privat, site]
 */
function pfade_pruefen(string $privat, string $site): array
{
    $f = [];
    $privatEcht = realpath($privat) ?: '';
    $siteEcht = realpath($site) ?: '';

    if ($privatEcht === '') {
        $f[] = 'Den privaten Ordner gibt es nicht: ' . $privat;
    } elseif (!is_file($privatEcht . '/bauen.php')) {
        $f[] = "In $privatEcht liegt keine bauen.php — das ist nicht der private Ordner.";
    } elseif (!is_writable($privatEcht)) {
        $f[] = "Der Ordner $privatEcht ist schreibgeschützt — dort entstehen Entwürfe, Sicherungen und Benutzerkonten.";
    }

    if ($siteEcht === '') {
        $f[] = 'Den Ordner der Webseite gibt es nicht: ' . $site;
    } elseif (!is_writable($siteEcht)) {
        $f[] = "Der Ordner $siteEcht ist schreibgeschützt — dorthin schreibt der Generator die Webseite.";
    }

    if ($privatEcht !== '' && $siteEcht !== '' && liegt_in($privatEcht, $siteEcht)) {
        $f[] = 'Der private Ordner liegt innerhalb des Webseiten-Ordners — dann wären Benutzerkonten und Inhalte aus dem Netz abrufbar.';
    }
    if ($privatEcht !== '' && liegt_in($privatEcht, ADMIN_WURZEL)) {
        $f[] = 'Der private Ordner liegt innerhalb des Portal-Ordners — er gehört daneben, nicht hinein.';
    }

    return [$f, $privatEcht ?: $privat, $siteEcht ?: $site];
}

function konfig_bauen(array $w): string
{
    return "<?php\n/**\n * Konfiguration des Portals.\n *\n"
        . " * Zuletzt geändert am " . date('d.m.Y H:i') . ".\n"
        . " * Benutzer werden im Portal verwaltet (privat/ablage/benutzer.json).\n */\n\n"
        . "return [\n"
        . "    // Privater Ordner mit Generator, Inhalten, Entwürfen, Designs und Sicherungen (außerhalb jedes Docroots)\n"
        . "    'privat' => " . var_export($w['privat'], true) . ",\n\n"
        . "    // Docroot der Webseite — dorthin wird veröffentlicht\n"
        . "    'site' => " . var_export($w['site'], true) . ",\n\n"
        . "    // Adresse der Webseite, für „Webseite ansehen“\n"
        . "    'website' => " . var_export($w['website'], true) . ",\n\n"
        . "    // Nach so vielen Minuten ohne Aktivität wird abgemeldet\n"
        . "    'sitzungsdauer' => " . (int) $w['sitzungsdauer'] . ",\n"
        . "];\n";
}

function konfig_schreiben(array $werte): void
{
    datei_schreiben(KONFIG_DATEI, konfig_bauen($werte));
    @chmod(KONFIG_DATEI, 0640);
}

/** Erste Einrichtung: konfig.php und das erste Verwaltungs-Konto. */
function einrichten(array $post): array
{
    $name = trim((string) ($post['benutzer'] ?? ''));
    $anzeige = trim((string) ($post['anzeige'] ?? ''));
    $passwort = (string) ($post['passwort'] ?? '');
    $f = [];

    if (!preg_match('/^[a-z0-9._-]{3,32}$/', $name)) {
        $f[] = 'Der Benutzername darf nur Kleinbuchstaben, Ziffern, Punkt, Strich und Unterstrich enthalten (3 bis 32 Zeichen).';
    }
    if ($passwort !== (string) ($post['passwort2'] ?? '')) {
        $f[] = 'Die beiden Passwörter stimmen nicht überein.';
    }
    $f = array_merge($f, passwort_regeln($passwort, $name));

    [$pf, $privat, $site] = pfade_pruefen(trim((string) ($post['privat'] ?? '')), trim((string) ($post['site'] ?? '')));
    $f = array_merge($f, $pf);
    $website = rtrim(trim((string) ($post['website'] ?? '')), '/');
    if ($website !== '' && !preg_match('~^https?://[^\s/]+~', $website)) {
        $f[] = 'Die Adresse der Webseite muss mit http:// oder https:// beginnen.';
    }
    if ($f) {
        return $f;
    }

    $benutzerDatei = $privat . '/ablage/benutzer.json';
    $vorhanden = json_lesen($benutzerDatei)['benutzer'] ?? [];
    if ($vorhanden && !isset($vorhanden[$name])) {
        // Nicht still ein Konto neben fremde setzen — das wäre eine Hintertür.
        return ['Im privaten Ordner gibt es bereits Benutzerkonten. Bitte mit einem vorhandenen Konto anmelden oder den Notfall-Zugang (php werkzeuge/benutzer.php) verwenden.'];
    }

    konfig_schreiben([
        'privat' => $privat, 'site' => $site, 'website' => $website,
        'sitzungsdauer' => max(5, min(1440, (int) (projekt('sitzungsdauer') ?: 120))),
    ]);

    ordner_sicherstellen(dirname($benutzerDatei));
    json_schreiben($benutzerDatei, [
        '_hinweis' => 'Benutzerkonten des Portals. Passwörter stehen nur als Hash hier. Notfall-Zugang: php werkzeuge/benutzer.php',
        'benutzer' => [$name => [
            'anzeige' => $anzeige ?: $name, 'rolle' => 'verwaltung',
            'hash' => password_hash($passwort, PASSWORD_DEFAULT), 'gesperrt' => false, 'version' => 1,
            'erstellt' => date('c'), 'zuletzt' => '',
        ]],
    ]);
    @chmod($benutzerDatei, 0600);
    return [];
}

/** @return array{0:string[],1:?string} [Fehler, Dateiinhalt zum Selbsteintragen] */
function einstellungen_speichern(array $post): array
{
    [$f, $privat, $site] = pfade_pruefen(trim((string) ($post['privat'] ?? '')), trim((string) ($post['site'] ?? '')));
    $website = trim((string) ($post['website'] ?? ''));
    if ($website !== '' && !preg_match('~^https?://[^\s/]+~', $website)) {
        $f[] = 'Die Adresse der Webseite muss mit http:// oder https:// beginnen.';
    }
    $dauer = (int) ($post['sitzungsdauer'] ?? 120);
    if ($dauer < 5 || $dauer > 1440) {
        $f[] = 'Die Sitzungsdauer muss zwischen 5 und 1440 Minuten liegen.';
    }
    if ($f) {
        return [$f, null];
    }
    $werte = ['privat' => $privat, 'site' => $site, 'website' => rtrim($website, '/'), 'sitzungsdauer' => $dauer];
    try {
        konfig_schreiben($werte);
    } catch (Throwable) {
        return [['konfig.php ließ sich nicht schreiben — dem Webserver fehlt dort das Schreibrecht.'], konfig_bauen($werte)];
    }
    protokoll('Einstellungen geändert');
    return [[], null];
}
