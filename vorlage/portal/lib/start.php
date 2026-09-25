<?php
/**
 * Grundlagen des Portals: Konfiguration, Projekt, Pfade, Sitzung, kleine Helfer.
 *
 * Bewusst ohne Framework und ohne Composer — läuft auf jedem Webspace mit
 * PHP 8.1+, genau wie der Generator im privaten Ordner.
 *
 * Drei Ordner gehören zusammen:
 *   portal/   → Docroot der Portal-Subdomain (dieser Code)
 *   privat/   → Inhalte, Entwürfe, Konten, Designs, Generator — außerhalb jedes Docroots
 *   webseite/ → Docroot der Webseite, wird vom Generator geschrieben
 */

declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    exit('Dieses Portal braucht PHP 8.1 oder neuer.');
}

const ADMIN_WURZEL = __DIR__ . '/..';
const KONFIG_DATEI = ADMIN_WURZEL . '/konfig.php';

/**
 * Notbremse gegen die weiße Seite.
 *
 * Bricht PHP mit einem schweren Fehler ab, liefert der Server sonst eine leere
 * Seite. Häufigster Grund auf einem Webspace: eine unvollständig hochgeladene
 * Datei. Ausgegeben werden nur Dateiname und Zeile — genug zum Handeln.
 */
register_shutdown_function(static function (): void {
    $fehler = error_get_last();
    if (!$fehler || !in_array($fehler['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $datei = htmlspecialchars(basename((string) ($fehler['file'] ?? '')), ENT_QUOTES, 'UTF-8');
    $zeile = (int) ($fehler['line'] ?? 0);
    $speicher = str_contains((string) ($fehler['message'] ?? ''), 'memory size');

    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Fehler — Portal</title><style>'
       . 'body{margin:0;background:#0e0e16;color:#ececf5;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;'
       . 'line-height:1.55;display:grid;place-items:center;min-height:100dvh;padding:16px}'
       . 'div{max-width:60ch;background:#1a1a28;border-radius:16px;padding:28px 24px}'
       . 'h1{margin:0 0 14px;font-size:22px;font-weight:600}p{margin:0 0 12px;color:#9a9ab0}'
       . 'code{background:#22222f;padding:2px 6px;border-radius:6px;font-family:ui-monospace,Menlo,monospace;font-size:14px;color:#5eead4}'
       . 'ul{margin:0;padding-left:20px;color:#9a9ab0}li{margin-bottom:6px}</style></head><body><div>'
       . '<h1>Das Portal ist abgebrochen</h1>'
       . '<p>Betroffen ist <code>' . $datei . '</code>' . ($zeile ? ', Zeile ' . $zeile : '') . '.</p>'
       . ($speicher
           ? '<p>PHP hatte nicht genug Arbeitsspeicher — meist bei einem sehr großen Foto. Bitte das Bild vorher verkleinern (z. B. auf 3000 Pixel Breite) oder im Kundenmenü <code>memory_limit</code> erhöhen.</p>'
           : '<p>Das passiert fast immer aus einem dieser Gründe:</p><ul>'
             . '<li>Die Datei ist beim Hochladen nicht vollständig angekommen. Noch einmal per SFTP übertragen und die Dateigröße vergleichen.</li>'
             . '<li>Es fehlt eine Datei, die dazugehört. Den Ordner vollständig neu hochladen.</li>'
             . '<li>Der Server läuft mit einer zu alten PHP-Version. Nötig ist PHP 8.1 oder neuer.</li></ul>')
       . '</div></body></html>';
});

/* ------------------------------------------------------- Konfiguration --- */

function konfig(?string $schluessel = null): mixed
{
    static $k = null;
    if ($k === null) {
        $k = is_file(KONFIG_DATEI) ? require KONFIG_DATEI : [];
        $k += ['privat' => ADMIN_WURZEL . '/../privat', 'site' => ADMIN_WURZEL . '/../webseite',
               'website' => '', 'sitzungsdauer' => 120];
    }
    return $schluessel === null ? $k : ($k[$schluessel] ?? null);
}

/**
 * Projektangaben aus dem Assistenten (privat/projekt.json): Name des Portals,
 * eingeschaltete Module und die Begriffe, mit denen Bereiche heißen.
 */
function projekt(?string $schluessel = null): mixed
{
    static $p = null;
    if ($p === null) {
        $roh = json_lesen(pfad_privat('projekt.json')) ?? [];
        $p = array_replace_recursive([
            'name' => 'Meine Webseite',
            'portal' => 'Portal',
            'kuerzel' => 'W',
            'organisation' => 'Betrieb',
            'zeitzone' => 'Europe/Berlin',
            'reglementiert' => false,
            'module' => ['angebote' => true, 'team' => true, 'preise' => true, 'zeiten' => true,
                         'formular' => true, 'hinweise' => true, 'barrierefreiheit' => true],
            'begriffe' => ['angebote' => 'Leistungen', 'angebot' => 'Leistung', 'team' => 'Team', 'person' => 'Person',
                           'zeiten' => 'Öffnungszeiten', 'hinweise' => 'Hinweise & Urlaub', 'anfrage' => 'Anfrage'],
            'hoster' => ['name' => '', 'smtp' => 'smtp.strato.de', 'port' => 465],
        ], $roh);
    }
    return $schluessel === null ? $p : ($p[$schluessel] ?? null);
}

function modul(string $name): bool
{
    return !empty(projekt('module')[$name]);
}

function begriff(string $name): string
{
    return (string) (projekt('begriffe')[$name] ?? $name);
}

/* -------------------------------------------------------------- Pfade --- */

function pfad_privat(string $datei = ''): string   { return rtrim((string) konfig('privat'), '/') . '/' . $datei; }
function pfad_daten(string $datei = ''): string    { return pfad_privat('daten/' . $datei); }
function pfad_entwurf(string $datei = ''): string  { return pfad_privat('entwurf/' . $datei); }
function pfad_ablage(string $datei = ''): string   { return pfad_privat('ablage/' . $datei); }
function pfad_sicherung(string $datei = ''): string { return pfad_privat('sicherung/' . $datei); }
function pfad_design(string $datei = ''): string   { return pfad_privat('design/' . $datei); }
function pfad_site(string $datei = ''): string     { return rtrim((string) konfig('site'), '/') . '/' . $datei; }
function pfad_medien(string $datei = ''): string   { return pfad_site('medien/' . $datei); }

/** Den Generator einbinden. Fehlt er, liefert die Funktion false statt abzustürzen. */
function generator_laden(): bool
{
    if (function_exists('ws_bauen')) {
        return true;
    }
    $datei = pfad_privat('bauen.php');
    if (!is_file($datei)) {
        return false;
    }
    require_once $datei;
    return function_exists('ws_bauen');
}

/* -------------------------------------------------------------- Helfer --- */

/** HTML-sichere Ausgabe — jeder Wert aus den Daten läuft hier durch. */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ordner_sicherstellen(string $pfad): void
{
    if (!is_dir($pfad) && !mkdir($pfad, 0755, true) && !is_dir($pfad)) {
        throw new RuntimeException("Ordner lässt sich nicht anlegen: $pfad");
    }
}

/** Erst vollständig daneben schreiben, dann an den Platz schieben — nie eine halbe Datei. */
function datei_schreiben(string $ziel, string $inhalt): void
{
    ordner_sicherstellen(dirname($ziel));
    $temp = $ziel . '.tmp' . bin2hex(random_bytes(4));
    if (file_put_contents($temp, $inhalt, LOCK_EX) === false || !rename($temp, $ziel)) {
        @unlink($temp);
        throw new RuntimeException("Datei lässt sich nicht schreiben: $ziel");
    }
}

function json_schreiben(string $ziel, mixed $daten): void
{
    $text = json_encode($daten, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($text === false) {
        throw new RuntimeException('Daten lassen sich nicht als JSON speichern.');
    }
    datei_schreiben($ziel, $text . "\n");
}

function json_lesen(string $pfad): ?array
{
    if (!is_readable($pfad)) {
        return null;
    }
    $roh = json_decode((string) file_get_contents($pfad), true);
    return is_array($roh) ? $roh : null;
}

function datum_deutsch(string $iso): string
{
    if ($iso === '') {
        return '';
    }
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $iso);
    return $d ? $d->format('d.m.Y') : $iso;
}

function zeit_deutsch(string $iso): string
{
    $t = strtotime($iso);
    return $t ? date('d.m.Y, H:i', $t) . ' Uhr' : $iso;
}

function liegt_in(string $kind, string $eltern): bool
{
    $k = realpath($kind);
    $e = realpath($eltern);
    if (!$k || !$e) {
        return false;
    }
    return $k === $e || str_starts_with($k . DIRECTORY_SEPARATOR, rtrim($e, '/') . DIRECTORY_SEPARATOR);
}

function https_aktiv(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function lokal_aufgerufen(): bool
{
    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
}

/* ------------------------------------------------------------- Sitzung --- */

function sitzung_starten(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('wsportal');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => https_aktiv(),
    ]);
    session_start();
}

/** Einmal pro Sitzung erzeugtes Geheimnis gegen fremde Formulare (CSRF). */
function token(): string
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['token'];
}

function token_pruefen(): void
{
    if (!hash_equals($_SESSION['token'] ?? '', (string) ($_POST['token'] ?? ''))) {
        http_response_code(400);
        exit('Das Formular ist abgelaufen. Bitte die Seite neu laden und noch einmal versuchen.');
    }
}

/* ------------------------------------------------------------ Meldungen --- */

function melden(string $art, string $text, array $liste = []): void
{
    $_SESSION['meldungen'][] = ['art' => $art, 'text' => $text, 'liste' => $liste];
}

function meldungen_abholen(): array
{
    $m = $_SESSION['meldungen'] ?? [];
    unset($_SESSION['meldungen']);
    return $m;
}

/* ---------------------------------------------------------- Navigation --- */

function url_zu(string $seite, array $zusatz = []): string
{
    return '?' . http_build_query(array_merge(['seite' => $seite], $zusatz));
}

function weiter(string $seite, array $zusatz = []): never
{
    header('Location: ' . url_zu($seite, $zusatz));
    exit;
}

/** Protokoll: wer hat wann was geändert oder veröffentlicht. */
function protokoll(string $text): void
{
    try {
        ordner_sicherstellen(pfad_ablage());
        $zeile = sprintf("%s\t%s\t%s\n", date('Y-m-d H:i:s'), $_SESSION['benutzer'] ?? '—',
            str_replace(["\t", "\n"], ' ', $text));
        file_put_contents(pfad_ablage('protokoll.log'), $zeile, FILE_APPEND | LOCK_EX);
    } catch (Throwable) {
        // Ein fehlendes Protokoll darf die eigentliche Arbeit nicht aufhalten.
    }
}

/** Die letzten Einträge des Protokolls, neueste zuerst. */
function protokoll_lesen(int $anzahl = 300): array
{
    $datei = pfad_ablage('protokoll.log');
    if (!is_readable($datei)) {
        return [];
    }
    $zeilen = array_slice(file($datei, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], -$anzahl);
    $raus = [];
    foreach (array_reverse($zeilen) as $z) {
        [$zeit, $wer, $was] = array_pad(explode("\t", $z, 3), 3, '');
        $raus[] = ['zeit' => $zeit, 'wer' => $wer, 'was' => $was];
    }
    return $raus;
}

/**
 * Sucht die Ordner selbst — neben und über dem Portal.
 *
 * @return array{privat:string,site:string}
 */
function pfade_raten(): array
{
    $gefunden = ['privat' => '', 'site' => ''];
    $selbst = realpath(ADMIN_WURZEL);

    foreach ([ADMIN_WURZEL . '/..', ADMIN_WURZEL . '/../..'] as $stamm) {
        foreach (glob(rtrim($stamm, '/') . '/*', GLOB_ONLYDIR) ?: [] as $k) {
            $echt = realpath($k) ?: '';
            if ($echt === '' || $echt === $selbst) {
                continue;
            }
            if ($gefunden['privat'] === '' && is_file($k . '/bauen.php') && is_dir($k . '/daten')) {
                $gefunden['privat'] = $echt;
            }
            if ($gefunden['site'] === '' && (is_file($k . '/.webseite') || in_array(basename($k), ['webseite', 'site', 'htdocs', 'public_html'], true))) {
                $gefunden['site'] = $echt;
            }
        }
    }
    return $gefunden;
}

// Zeitzone aus dem Projekt — fest, damit die Zeiträume der Hinweise zum richtigen Tag wechseln
date_default_timezone_set((string) (projekt('zeitzone') ?: 'Europe/Berlin'));
