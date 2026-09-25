<?php
/**
 * Benutzerkonten des Portals.
 *
 * Die Konten liegen im privaten Ordner (ablage/benutzer.json), nicht in
 * konfig.php — so lassen sie sich im Portal selbst anlegen, sperren und
 * zurücksetzen, ohne dass jemand per FTP an eine PHP-Datei muss.
 *
 * Jedes Konto trägt eine Versionsnummer. Sie steigt bei Passwortwechsel,
 * Sperre und Rollenwechsel; wer mit einer älteren Nummer angemeldet ist, wird
 * beim nächsten Klick abgemeldet.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const ROLLEN = [
    'verwaltung' => ['Verwaltung', 'Alles – auch Benutzer, Einstellungen, Systemprüfung und Protokoll.'],
    'redaktion'  => ['Redaktion', 'Inhalte, Sektionen, Medien, Hinweise und Veröffentlichen.'],
];

/** Was nur die Verwaltung darf. Alles andere darf jede angemeldete Person. */
const NUR_VERWALTUNG = ['benutzer', 'einstellungen', 'diagnose', 'protokoll'];

const PASSWORT_MIN = 10;

function benutzer_alle(): array
{
    $d = json_lesen(pfad_ablage('benutzer.json')) ?? [];
    return is_array($d['benutzer'] ?? null) ? $d['benutzer'] : [];
}

function benutzer_holen(string $name): ?array
{
    $alle = benutzer_alle();
    return isset($alle[$name]) ? $alle[$name] + ['name' => $name] : null;
}

function benutzer_sichern(array $alle): void
{
    ksort($alle, SORT_NATURAL | SORT_FLAG_CASE);
    json_schreiben(pfad_ablage('benutzer.json'), [
        '_hinweis' => 'Benutzerkonten des Portals. Passwörter stehen nur als Hash hier. Notfall-Zugang: php werkzeuge/benutzer.php',
        'benutzer' => $alle,
    ]);
    @chmod(pfad_ablage('benutzer.json'), 0600);
}

function aktueller_benutzer(): ?array
{
    return isset($_SESSION['benutzer']) ? benutzer_holen((string) $_SESSION['benutzer']) : null;
}

function darf(string $recht): bool
{
    $b = aktueller_benutzer();
    if (!$b) {
        return false;
    }
    return !in_array($recht, NUR_VERWALTUNG, true) || ($b['rolle'] ?? '') === 'verwaltung';
}

function recht_pruefen(string $recht): void
{
    if (!darf($recht)) {
        melden('fehler', 'Dieser Bereich ist der Verwaltung vorbehalten.');
        weiter('start');
    }
}

/** Wie viele nicht gesperrte Verwaltungs-Konten gibt es (optional ohne eines)? */
function verwaltung_anzahl(array $alle, ?string $ohne = null): int
{
    $n = 0;
    foreach ($alle as $name => $b) {
        if ($name !== $ohne && ($b['rolle'] ?? '') === 'verwaltung' && empty($b['gesperrt'])) {
            $n++;
        }
    }
    return $n;
}

function passwort_regeln(string $passwort, string $name): array
{
    $f = [];
    if (mb_strlen($passwort) < PASSWORT_MIN) {
        $f[] = 'Das Passwort muss mindestens ' . PASSWORT_MIN . ' Zeichen lang sein.';
    }
    if ($name !== '' && mb_strtolower($passwort) === mb_strtolower($name)) {
        $f[] = 'Das Passwort darf nicht dem Benutzernamen entsprechen.';
    }
    return $f;
}

function benutzer_anlegen(string $name, string $anzeige, string $rolle, string $passwort): array
{
    $alle = benutzer_alle();
    $f = [];
    if (!preg_match('/^[a-z0-9._-]{3,32}$/', $name)) {
        $f[] = 'Der Benutzername darf nur Kleinbuchstaben, Ziffern, Punkt, Strich und Unterstrich enthalten (3 bis 32 Zeichen).';
    } elseif (isset($alle[$name])) {
        $f[] = 'Diesen Benutzernamen gibt es schon.';
    }
    if (!isset(ROLLEN[$rolle])) {
        $f[] = 'Bitte eine Rolle wählen.';
    }
    $f = array_merge($f, passwort_regeln($passwort, $name));
    if ($f) {
        return $f;
    }
    $alle[$name] = [
        'anzeige' => mb_substr(trim($anzeige), 0, 80) ?: $name,
        'rolle' => $rolle,
        'hash' => password_hash($passwort, PASSWORD_DEFAULT),
        'gesperrt' => false,
        'version' => 1,
        'erstellt' => date('c'),
        'zuletzt' => '',
    ];
    benutzer_sichern($alle);
    return [];
}

function benutzer_aendern(string $name, string $anzeige, string $rolle): array
{
    $alle = benutzer_alle();
    if (!isset($alle[$name])) {
        return ['Dieses Konto gibt es nicht mehr.'];
    }
    if (!isset(ROLLEN[$rolle])) {
        return ['Bitte eine Rolle wählen.'];
    }
    if ($rolle !== 'verwaltung' && ($alle[$name]['rolle'] ?? '') === 'verwaltung' && verwaltung_anzahl($alle, $name) === 0) {
        return ['Mindestens ein aktives Konto muss die Rolle „Verwaltung“ behalten — sonst kann niemand mehr Benutzer verwalten.'];
    }
    if (($alle[$name]['rolle'] ?? '') !== $rolle) {
        $alle[$name]['version'] = (int) ($alle[$name]['version'] ?? 1) + 1;
    }
    $alle[$name]['anzeige'] = mb_substr(trim($anzeige), 0, 80) ?: $name;
    $alle[$name]['rolle'] = $rolle;
    benutzer_sichern($alle);
    return [];
}

function benutzer_passwort_setzen(string $name, string $passwort): array
{
    $alle = benutzer_alle();
    if (!isset($alle[$name])) {
        return ['Dieses Konto gibt es nicht mehr.'];
    }
    $f = passwort_regeln($passwort, $name);
    if ($f) {
        return $f;
    }
    $alle[$name]['hash'] = password_hash($passwort, PASSWORD_DEFAULT);
    $alle[$name]['version'] = (int) ($alle[$name]['version'] ?? 1) + 1;
    benutzer_sichern($alle);
    return [];
}

function benutzer_sperren(string $name, bool $gesperrt): array
{
    $alle = benutzer_alle();
    if (!isset($alle[$name])) {
        return ['Dieses Konto gibt es nicht mehr.'];
    }
    if ($name === ($_SESSION['benutzer'] ?? '')) {
        return ['Das eigene Konto lässt sich nicht sperren.'];
    }
    if ($gesperrt && ($alle[$name]['rolle'] ?? '') === 'verwaltung' && verwaltung_anzahl($alle, $name) === 0) {
        return ['Das letzte aktive Verwaltungs-Konto lässt sich nicht sperren.'];
    }
    $alle[$name]['gesperrt'] = $gesperrt;
    $alle[$name]['version'] = (int) ($alle[$name]['version'] ?? 1) + 1;
    benutzer_sichern($alle);
    return [];
}

function benutzer_loeschen(string $name): array
{
    $alle = benutzer_alle();
    if (!isset($alle[$name])) {
        return ['Dieses Konto gibt es nicht mehr.'];
    }
    if ($name === ($_SESSION['benutzer'] ?? '')) {
        return ['Das eigene Konto lässt sich nicht löschen.'];
    }
    if (($alle[$name]['rolle'] ?? '') === 'verwaltung' && verwaltung_anzahl($alle, $name) === 0) {
        return ['Das letzte aktive Verwaltungs-Konto lässt sich nicht löschen.'];
    }
    unset($alle[$name]);
    benutzer_sichern($alle);
    return [];
}
