<?php
/**
 * Zugangsdaten für den E-Mail-Versand des Kontaktformulars.
 *
 * Gespeichert in privat/ablage/smtp.php (außerhalb jedes Docroots). Das
 * Passwort wird nie wieder angezeigt — ein leeres Feld lässt es unverändert.
 * Den eigentlichen Versand erledigt privat/versand.php, dieselbe Datei, die
 * auch anfrage.php auf der Webseite benutzt.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }


function smtp_versand_laden(): bool
{
    if (function_exists('ws_smtp_senden')) {
        return true;
    }
    $datei = pfad_privat('versand.php');
    if (!is_file($datei)) {
        return false;
    }
    require_once $datei;
    return function_exists('ws_smtp_senden');
}

function smtp_gespeichert(): array
{
    $datei = pfad_ablage('smtp.php');
    $k = is_file($datei) ? (require $datei) : [];
    $hoster = (array) projekt('hoster');
    return (is_array($k) ? $k : []) + ['host' => (string) ($hoster['smtp'] ?? ''), 'port' => (int) ($hoster['port'] ?? 465), 'benutzer' => '', 'passwort' => ''];
}

/** Werte fürs Formular — ohne Passwort. */
function smtp_werte(): array
{
    $k = smtp_gespeichert();
    return ['host' => (string) $k['host'], 'port' => (int) $k['port'], 'benutzer' => (string) $k['benutzer'],
            'hatPasswort' => (string) $k['passwort'] !== ''];
}

/** Liefert eine Liste von Fehlern; leer = gespeichert. */
function smtp_speichern(array $post): array
{
    $alt = smtp_gespeichert();
    $host = trim((string) ($post['smtp_host'] ?? ''));
    $port = (int) ($post['smtp_port'] ?? 465);
    $benutzer = trim((string) ($post['smtp_benutzer'] ?? ''));
    $passwort = (string) ($post['smtp_passwort'] ?? '');
    $loeschen = !empty($post['smtp_loeschen']);

    $f = [];
    if ($host !== '' && !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
        $f[] = 'Der Server-Name darf nur Buchstaben, Ziffern, Punkte und Bindestriche enthalten (z. B. smtp.ihr-anbieter.de).';
    }
    if (!in_array($port, [465, 587], true)) {
        $f[] = 'Als Port sind 465 (SSL/TLS) oder 587 (STARTTLS) möglich.';
    }
    if ($benutzer !== '' && !filter_var($benutzer, FILTER_VALIDATE_EMAIL)) {
        $f[] = 'Der Benutzername ist bei den meisten Anbietern die vollständige E-Mail-Adresse des Postfachs.';
    }
    if ($f) {
        return $f;
    }

    $neu = [
        'host' => $host,
        'port' => $port,
        'benutzer' => $benutzer,
        'passwort' => $loeschen ? '' : ($passwort !== '' ? $passwort : (string) $alt['passwort']),
    ];
    $code = "<?php\n// E-Mail-Versand des Kontaktformulars — geschrieben vom Portal am " . date('d.m.Y H:i') . ".\n"
        . "// Enthält ein Passwort: nicht weitergeben, nicht ins Repository.\n"
        . "return " . var_export($neu, true) . ";\n";
    try {
        datei_schreiben(pfad_ablage('smtp.php'), $code);
        @chmod(pfad_ablage('smtp.php'), 0600);
    } catch (Throwable) {
        return ['Die Zugangsdaten ließen sich nicht speichern — dem Webserver fehlt das Schreibrecht auf privat/ablage.'];
    }
    protokoll('E-Mail-Versand (SMTP) geändert' . ($passwort !== '' ? ', Passwort neu gesetzt' : ''));
    return [];
}

/** An wen die Anfragen gehen — wie im Generator. */
function smtp_empfaenger(): string
{
    $formular = entwurf_laden('formular');
    $stamm = entwurf_laden('stammdaten');
    return trim((string) ($formular['empfaenger'] ?? '')) ?: trim((string) ($stamm['kontakt']['email'] ?? ''));
}

/** Schickt eine Testmail. Liefert null bei Erfolg, sonst die Fehlermeldung. */
function smtp_testen(): ?string
{
    if (!smtp_versand_laden()) {
        return 'privat/versand.php fehlt — bitte den Code neu auf den Server bringen.';
    }
    $k = ws_smtp_konfig();
    if ($k === null) {
        return 'Es sind noch keine vollständigen Zugangsdaten gespeichert (Server, Benutzername und Passwort).';
    }
    $an = smtp_empfaenger();
    if (!filter_var($an, FILTER_VALIDATE_EMAIL)) {
        return 'Es ist keine gültige Empfänger-Adresse eingetragen (Kontaktformular oder ' . projekt('organisation') . ' & Kontakt).';
    }
    try {
        ws_smtp_senden($k, $an, 'Testmail vom ' . projekt('portal'),
            "Diese Testmail hat " . ($_SESSION['benutzer'] ?? 'das Portal') . " am " . date('d.m.Y \u\m H:i') . " Uhr ausgelöst.\n\n"
            . "Kommt sie an, ist der Versand für das Kontaktformular richtig eingerichtet.", (string) projekt('name'));
    } catch (Throwable $e) {
        return $e->getMessage();
    }
    protokoll('Testmail an ' . $an . ' gesendet');
    return null;
}
