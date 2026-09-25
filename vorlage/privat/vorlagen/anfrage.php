<?php
/**
 * Anfragen über das Kontaktformular der Webseite.
 *
 * Diese Datei wird vom Generator erzeugt — Änderungen hier gehen beim nächsten
 * Veröffentlichen verloren. Einstellungen im Portal unter „Kontaktformular“.
 *
 * Nimmt die Anfrage entgegen, prüft sie und schickt sie als E-Mail — über den
 * Postausgangsserver (SMTP) des Postfachs, sobald im Portal unter
 * Einstellungen → E-Mail-Versand Zugangsdaten hinterlegt sind, sonst über die
 * mail()-Funktion des Webservers. Gespeichert wird nichts: Was nicht auf dem
 * Webserver liegt, kann dort auch nicht abhandenkommen.
 *
 * Schutz gegen Missbrauch ohne Captcha (und damit ohne fremde Dienste):
 *   - verstecktes Feld, das nur Programme ausfüllen
 *   - Mindestzeit zwischen Seitenaufruf und Absenden
 *   - höchstens 5 Anfragen je Anschluss in 10 Minuten
 *   - keine Bestätigungsmail an die angegebene Adresse — sonst ließe sich das
 *     Formular benutzen, um Fremden E-Mails zu schicken
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Berlin');

const WS_ANFRAGE = /*WS:KONFIG*/[];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function antwort(int $code, array $daten): never
{
    http_response_code($code);
    echo json_encode($daten, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    antwort(405, ['ok' => false, 'fehler' => 'Nur Anfragen über das Formular.']);
}

// Nur aus der eigenen Seite heraus
$herkunft = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($herkunft !== '' && parse_url($herkunft, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '')) {
    antwort(403, ['ok' => false, 'fehler' => 'Diese Anfrage kam nicht von unserer Webseite.']);
}

$k = WS_ANFRAGE;
if (!filter_var($k['empfaenger'] ?? '', FILTER_VALIDATE_EMAIL)) {
    antwort(500, ['ok' => false, 'fehler' => 'Das Formular ist gerade nicht eingerichtet. Bitte rufen Sie uns an.']);
}

$roh = json_decode((string) file_get_contents('php://input'), true);
$eingabe = is_array($roh) ? $roh : $_POST;
$feld = static function (string $name, int $max) use ($eingabe): string {
    $wert = $eingabe[$name] ?? '';
    $wert = is_scalar($wert) ? (string) $wert : '';
    $wert = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $wert) ?? '';
    return mb_substr(trim($wert), 0, $max);
};

// Verstecktes Feld: Menschen sehen es nicht, Programme füllen es aus
if ($feld('webseite', 200) !== '') {
    antwort(200, ['ok' => true]);
}

// Wer in unter drei Sekunden alles ausgefüllt hat, ist kein Mensch
$geladen = (int) ($eingabe['geladen'] ?? 0);
$jetzt = (int) round(microtime(true) * 1000);
if ($geladen <= 0 || $jetzt - $geladen < 3000 || $jetzt - $geladen > 86400000) {
    antwort(400, ['ok' => false, 'fehler' => 'Bitte laden Sie die Seite neu und senden Sie die Anfrage noch einmal.']);
}

// Bremse: 5 Anfragen je Anschluss in 10 Minuten
$kennung = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __FILE__), 0, 24);
$bremsDatei = sys_get_temp_dir() . '/ws-anfrage-' . $kennung . '.json';
$verlauf = array_filter(
    json_decode((string) @file_get_contents($bremsDatei), true) ?: [],
    static fn($t) => is_int($t) && $t > time() - 600
);
if (count($verlauf) >= 5) {
    antwort(429, ['ok' => false, 'fehler' => 'Es wurden gerade viele Anfragen gesendet. Bitte versuchen Sie es später noch einmal oder rufen Sie uns an.']);
}

$name = $feld('name', 120);
$telefon = $feld('telefon', 60);
$email = $feld('email', 160);
$angebot = $feld('angebot', 160);
$zeit = $feld('zeit', 60);
$nachricht = $feld('nachricht', 4000);
$einwilligung = !empty($eingabe['einwilligung']);

$fehler = [];
if ($name === '') {
    $fehler['name'] = 'Bitte geben Sie Ihren Namen an.';
}
if ($telefon === '' && $email === '') {
    $fehler['erreichbar'] = 'Bitte geben Sie eine Telefonnummer oder E-Mail-Adresse an.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fehler['email'] = 'Die E-Mail-Adresse sieht nicht vollständig aus.';
}
if (!$einwilligung) {
    $fehler['einwilligung'] = 'Bitte stimmen Sie der Verarbeitung Ihrer Angaben zu.';
}
if ($fehler) {
    antwort(422, ['ok' => false, 'fehler' => 'Bitte prüfen Sie Ihre Angaben.', 'felder' => $fehler]);
}

// Kopfzeilen: keine Zeilenumbrüche, sonst ließen sich weitere Empfänger einschmuggeln
$ohneUmbruch = static fn(string $s): string => str_replace(["\r", "\n"], ' ', $s);
$kodiert = static fn(string $s): string => '=?UTF-8?B?' . base64_encode($s) . '?=';

$betreff = ($k['betreff'] ?? 'Anfrage über die Webseite') . ' – ' . $ohneUmbruch($name);
$text = implode("\n", [
    'Neue Anfrage über das Formular der Webseite',
    str_repeat('-', 44),
    'Name:        ' . $name,
    'Telefon:     ' . ($telefon ?: '–'),
    'E-Mail:      ' . ($email ?: '–'),
    'Auswahl:     ' . ($angebot ?: '–'),
    'Wunschzeit:  ' . ($zeit ?: '–'),
    '',
    'Nachricht:',
    $nachricht ?: '–',
    '',
    str_repeat('-', 44),
    'Einwilligung in die Verarbeitung: erteilt am ' . date('d.m.Y \u\m H:i') . ' Uhr',
    'Diese Anfrage wurde nicht auf dem Webserver gespeichert.',
]);

$gesendet = false;
$smtp = null;
$versand = rtrim((string) ($k['privat'] ?? ''), '/') . '/versand.php';
// Anderswo erzeugt (z. B. vorab im Repository)? Dann liegt privat/ neben dem Docroot.
if (!is_file($versand) && is_file(dirname(__DIR__) . '/privat/versand.php')) {
    $versand = dirname(__DIR__) . '/privat/versand.php';
}
if (is_file($versand)) {
    require_once $versand;
    $smtp = ws_smtp_konfig();
}

if ($smtp !== null) {
    try {
        ws_smtp_senden($smtp, (string) $k['empfaenger'], $betreff, $text, (string) ($k['name'] ?? 'Webseite'),
            $email !== '' ? ['Reply-To' => $kodiert($ohneUmbruch($name)) . ' <' . $ohneUmbruch($email) . '>', 'X-Mailer' => 'Website-Generator'] : ['X-Mailer' => 'Website-Generator']);
        $gesendet = true;
    } catch (Throwable $e) {
        // Nur die Meldung des Servers ins Fehlerprotokoll — nie die Angaben aus dem Formular
        error_log('Anfrage per SMTP fehlgeschlagen: ' . $e->getMessage());
    }
}

if (!$gesendet) {
    $kopf = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . $kodiert((string) ($k['name'] ?? 'Webseite')) . ' <' . $ohneUmbruch((string) $k['absender']) . '>',
        'X-Mailer: Website-Generator',
    ];
    if ($email !== '') {
        $kopf[] = 'Reply-To: ' . $kodiert($ohneUmbruch($name)) . ' <' . $ohneUmbruch($email) . '>';
    }
    $gesendet = @mail(
        (string) $k['empfaenger'],
        $kodiert($betreff),
        $text,
        implode("\r\n", $kopf),
        filter_var($k['absender'] ?? '', FILTER_VALIDATE_EMAIL) ? '-f' . $k['absender'] : ''
    );
}

if (!$gesendet) {
    antwort(502, ['ok' => false, 'fehler' => 'Die Anfrage konnte gerade nicht versendet werden. Bitte rufen Sie uns an.']);
}

$verlauf[] = time();
@file_put_contents($bremsDatei, json_encode(array_values($verlauf)), LOCK_EX);

antwort(200, ['ok' => true]);
