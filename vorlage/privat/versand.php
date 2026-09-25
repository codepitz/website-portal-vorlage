<?php
/**
 * E-Mail-Versand über den Postausgangsserver (SMTP) des Postfachs.
 *
 * Genutzt von anfrage.php (Kontaktformular der Webseite) und vom Portal
 * (Einstellungen → Testmail). Bewusst ohne Bibliothek: nur das, was für
 * STRATO & Co. nötig ist — verschlüsselte Verbindung (SSL/TLS auf 465 oder
 * STARTTLS auf 587), Anmeldung mit Postfach und Passwort, eine Nachricht.
 *
 * Die Zugangsdaten liegen in privat/ablage/smtp.php — außerhalb jedes
 * Docroots und nie im Repository. Eingetragen werden sie im Portal.
 */

declare(strict_types=1);

const WS_SMTP_DATEI = __DIR__ . '/ablage/smtp.php';

/** Gespeicherte Zugangsdaten oder null, wenn (noch) keine vollständig sind. */
function ws_smtp_konfig(): ?array
{
    $k = is_file(WS_SMTP_DATEI) ? (require WS_SMTP_DATEI) : null;
    if (!is_array($k) || trim((string) ($k['host'] ?? '')) === '' || trim((string) ($k['benutzer'] ?? '')) === ''
        || (string) ($k['passwort'] ?? '') === '') {
        return null;
    }
    return $k + ['port' => 465];
}

/**
 * Schickt eine Text-Mail. Wirft RuntimeException mit einer Meldung ohne
 * Passwort und ohne Inhalt der Nachricht.
 *
 * $kopf: zusätzliche Kopfzeilen, z. B. ['Reply-To' => '…'] (bereits kodiert).
 */
function ws_smtp_senden(array $k, string $an, string $betreff, string $text, string $absenderName = '', array $kopf = []): void
{
    $host = trim((string) $k['host']);
    $port = (int) ($k['port'] ?? 465);
    $benutzer = trim((string) $k['benutzer']);
    $absender = filter_var($k['absender'] ?? '', FILTER_VALIDATE_EMAIL) ? (string) $k['absender'] : $benutzer;

    $ohneUmbruch = static fn(string $s): string => str_replace(["\r", "\n"], ' ', $s);
    $kodiert = static fn(string $s): string => preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    foreach ([$an, $absender] as $adresse) {
        if (!filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Ungültige Adresse: ' . $ohneUmbruch($adresse));
        }
    }

    $kontext = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
    $ziel = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $v = @stream_socket_client($ziel, $nr, $meldung, 15, STREAM_CLIENT_CONNECT, $kontext);
    if (!$v) {
        throw new RuntimeException("Keine Verbindung zu $host:$port ($meldung).");
    }
    stream_set_timeout($v, 20);

    $lesen = static function () use ($v): array {
        $alles = '';
        while (($zeile = fgets($v, 1024)) !== false) {
            $alles .= $zeile;
            if (strlen($zeile) < 4 || $zeile[3] !== '-') {
                break;
            }
        }
        return [(int) substr($alles, 0, 3), trim($alles)];
    };
    $befehl = static function (?string $zeile, array $erwartet, string $schritt) use ($v, $lesen): string {
        if ($zeile !== null) {
            fwrite($v, $zeile . "\r\n");
        }
        [$code, $antwort] = $lesen();
        if (!in_array($code, $erwartet, true)) {
            throw new RuntimeException("Server lehnt ab ($schritt): " . mb_substr($antwort, 0, 200));
        }
        return $antwort;
    };

    try {
        $befehl(null, [220], 'Begrüßung');
        $ich = preg_replace('/[^A-Za-z0-9.-]/', '', (string) ($_SERVER['SERVER_NAME'] ?? '')) ?: 'localhost';
        $faehig = $befehl("EHLO $ich", [250], 'EHLO');
        if ($port !== 465) {
            if (!str_contains(strtoupper($faehig), 'STARTTLS')) {
                throw new RuntimeException('Der Server bietet keine Verschlüsselung (STARTTLS) an.');
            }
            $befehl('STARTTLS', [220], 'STARTTLS');
            if (!stream_socket_enable_crypto($v, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('Verschlüsselung ließ sich nicht aufbauen.');
            }
            $befehl("EHLO $ich", [250], 'EHLO');
        }
        $befehl('AUTH LOGIN', [334], 'Anmeldung');
        $befehl(base64_encode($benutzer), [334], 'Anmeldung');
        $befehl(base64_encode((string) $k['passwort']), [235], 'Anmeldung — Postfach oder Passwort falsch?');
        $befehl('MAIL FROM:<' . $absender . '>', [250], 'Absender');
        $befehl('RCPT TO:<' . $an . '>', [250, 251], 'Empfänger');
        $befehl('DATA', [354], 'DATA');

        $domain = substr(strrchr($absender, '@') ?: '@localhost', 1);
        $zeilen = [
            'Date: ' . date('r'),
            'From: ' . ($absenderName !== '' ? $kodiert($ohneUmbruch($absenderName)) . ' ' : '') . '<' . $absender . '>',
            'To: <' . $an . '>',
            'Subject: ' . '=?UTF-8?B?' . base64_encode($ohneUmbruch($betreff)) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        foreach ($kopf as $name => $wert) {
            $zeilen[] = $ohneUmbruch((string) $name) . ': ' . $ohneUmbruch((string) $wert);
        }
        $nachricht = implode("\r\n", $zeilen) . "\r\n\r\n"
            . rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\n"], ["\n", "\r\n"], $text)), 76, "\r\n"))
            . "\r\n.";
        $befehl($nachricht, [250], 'Nachricht');
        fwrite($v, "QUIT\r\n");
    } finally {
        fclose($v);
    }
}
