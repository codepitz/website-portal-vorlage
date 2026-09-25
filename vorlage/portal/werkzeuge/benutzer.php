<?php
/**
 * Notfall-Zugang: Konto per Kommandozeile anlegen oder zurücksetzen.
 *
 *     php werkzeuge/benutzer.php vorname.nachname [verwaltung|redaktion]
 *
 * Gibt es das Konto schon, bekommt es ein neues Passwort und wird entsperrt.
 * Sonst wird es neu angelegt (ohne Angabe mit der Rolle „Verwaltung“).
 * Läuft nur auf der Kommandozeile, nie über den Browser.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../lib/start.php';
require __DIR__ . '/../lib/benutzer.php';

$name = strtolower(trim((string) ($argv[1] ?? '')));
$rolle = (string) ($argv[2] ?? 'verwaltung');

if ($name === '' || !isset(ROLLEN[$rolle])) {
    fwrite(STDERR, "Aufruf: php werkzeuge/benutzer.php <benutzername> [verwaltung|redaktion]\n");
    exit(1);
}
if (!is_file(KONFIG_DATEI)) {
    fwrite(STDERR, "Es gibt noch keine konfig.php. Bitte zuerst das Portal im Browser einrichten.\n");
    exit(1);
}

$lesen = static function (string $frage): string {
    echo $frage;
    $verdeckt = DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    if ($verdeckt) {
        shell_exec('stty -echo');
    }
    $wert = rtrim((string) fgets(STDIN), "\r\n");
    if ($verdeckt) {
        shell_exec('stty echo');
    }
    echo "\n";
    return $wert;
};

$passwort = $lesen('Neues Passwort (mindestens ' . PASSWORT_MIN . ' Zeichen): ');
if ($passwort !== $lesen('Noch einmal: ')) {
    fwrite(STDERR, "Die Passwörter stimmen nicht überein.\n");
    exit(1);
}

ordner_sicherstellen(pfad_ablage());
$vorhanden = benutzer_holen($name);

if ($vorhanden) {
    $f = benutzer_passwort_setzen($name, $passwort);
    if (!$f) {
        $alle = benutzer_alle();
        $alle[$name]['gesperrt'] = false;
        benutzer_sichern($alle);
    }
} else {
    $f = benutzer_anlegen($name, $name, $rolle, $passwort);
}

if ($f) {
    fwrite(STDERR, '• ' . implode("\n• ", $f) . "\n");
    exit(1);
}
$_SESSION['benutzer'] = 'Kommandozeile';
protokoll($vorhanden ? "Notfall-Zugang: Passwort für $name zurückgesetzt" : "Notfall-Zugang: Konto $name angelegt");
echo $vorhanden ? "Passwort für „{$name}“ gesetzt, Konto entsperrt.\n" : "Konto „{$name}“ mit der Rolle „" . ROLLEN[$rolle][0] . "“ angelegt.\n";
