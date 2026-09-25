<?php
/**
 * Vorschau des Entwurfs — die echte Webseite, nur mit den Inhalten, die noch
 * nicht veröffentlicht sind.
 *
 * Aufgerufen wird sie als vorschau.php/ (Startseite), vorschau.php/impressum/
 * usw. Alles hinter vorschau.php kommt als PATH_INFO an:
 *
 *   vorschau.php/                      → Startseite mit Entwurfsinhalt
 *   vorschau.php/impressum/            → Rechtsseite mit Entwurfsinhalt
 *   vorschau.php/assets/design.css     → Design des Entwurfs (oder ?design=…)
 *   vorschau.php/assets/seite.css|js   → Vorlagen aus privat/vorlagen
 *   vorschau.php/assets/schriften/…    → Schriften des Designs
 *   vorschau.php/assets/theme/<id>/…   → Dateien eines Theme-Pakets (CSS, JS, Bilder)
 *   vorschau.php/medien/foto.jpg       → Datei aus der Medienverwaltung
 *
 * Mit ?design=<kennung> zeigt sie die Webseite in einem anderen Design, ohne
 * etwas zu ändern. Die gewählte Kennung merkt sich ein Cookie für die
 * Unteranfragen (CSS). Nur für angemeldete Personen.
 */

declare(strict_types=1);

require __DIR__ . '/lib/start.php';
require __DIR__ . '/lib/benutzer.php';
require __DIR__ . '/lib/anmeldung.php';
require __DIR__ . '/lib/medien.php';

sitzung_starten();
if (!eingerichtet() || !angemeldet()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Die Vorschau ist nur nach der Anmeldung im Portal erreichbar.');
}
// Die Seite lädt mehrere Dateien gleichzeitig — die Sitzung darf sie nicht nacheinander aufreihen.
session_write_close();

$pfad = (string) ($_SERVER['PATH_INFO'] ?? '');
if ($pfad === '') {
    header('Location: ' . $_SERVER['SCRIPT_NAME'] . '/' . (isset($_GET['design']) ? '?design=' . rawurlencode((string) $_GET['design']) : ''));
    exit;
}
$pfad = ltrim($pfad, '/');

if (!generator_laden()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Für die Vorschau fehlt der Generator (privat/bauen.php).');
}

$seiten = ['' => 'start', 'impressum/' => 'impressum', 'datenschutz/' => 'datenschutz', 'barrierefreiheit/' => 'barrierefreiheit'];
$designWahl = (string) ($_GET['design'] ?? ($_COOKIE['wsvorschau'] ?? ''));
if (isset($seiten[$pfad])) {
    // Nur beim Seitenaufruf festlegen — CSS und Schriften folgen dem Cookie
    $designWahl = preg_match('/^[a-z0-9-]+$/', (string) ($_GET['design'] ?? '')) ? (string) $_GET['design'] : '';
    setcookie('wsvorschau', $designWahl, ['path' => dirname($_SERVER['SCRIPT_NAME']) ?: '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => https_aktiv()]);
}

$kopf = static function (string $typ): void {
    header('Content-Type: ' . $typ);
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
};

try {
    [$inhalt] = ws_inhalt(ws_daten_entwurf(rtrim(pfad_privat(), '/')), ['medien' => rtrim(pfad_site(), '/')]);
    if (preg_match('/^[a-z0-9-]+$/', $designWahl) && is_file(pfad_design($designWahl . '/design.json'))) {
        $inhalt['design'] = $designWahl;
    }
    $design = ws_design_laden($inhalt['design']);

    if (isset($seiten[$pfad])) {
        if ($seiten[$pfad] !== 'start' && !isset($inhalt['recht'][$seiten[$pfad]])) {
            http_response_code(404);
            exit;
        }
        $kopf('text/html; charset=utf-8');
        echo ws_seite_html($inhalt, $seiten[$pfad], $_SERVER['SCRIPT_NAME'] . '/', [
            'vorschau' => true, 'docroot' => rtrim(pfad_site(), '/'), 'design' => $design, 'version' => '?v=' . time(),
        ]);
        exit;
    }
    if ($pfad === 'assets/design.css') {
        $kopf('text/css; charset=utf-8');
        echo ws_design_css($design, 'schriften/' . $design['id'] . '/');
        exit;
    }
    if ($pfad === 'assets/seite.css' || $pfad === 'assets/seite.js') {
        $kopf($pfad === 'assets/seite.css' ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8');
        readfile(pfad_privat('vorlagen/' . basename($pfad)));
        exit;
    }
    // Dateien eines Theme-Pakets: CSS, Skripte, Bilder, Schriften
    if (preg_match('~^assets/theme/([a-z0-9-]+)/(.+)$~', $pfad, $m)) {
        $rel = ws_theme_pfad(rawurldecode($m[2]));
        $datei = pfad_design($m[1] . '/' . $rel);
        if ($rel === '' || preg_match(WS_THEME_NICHT_OEFFENTLICH, $rel) || !is_file($datei)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . WS_THEME_ENDUNGEN[strtolower(pathinfo($rel, PATHINFO_EXTENSION))]);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($datei);
        exit;
    }
    if (preg_match('~^assets/schriften/([a-z0-9-]+)/([A-Za-z0-9._-]+\.(woff2?|ttf|otf))$~', $pfad, $m)) {
        $datei = pfad_design($m[1] . '/schriften/' . $m[2]);
        if (!is_file($datei)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . ['woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf'][$m[3]]);
        header('Cache-Control: private, max-age=3600');
        readfile($datei);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Die Vorschau ließ sich nicht erzeugen: ' . $e->getMessage());
}

if (str_starts_with($pfad, 'medien/')) {
    medium_ausliefern($pfad);
}

http_response_code(404);
