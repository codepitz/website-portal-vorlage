<?php
/**
 * Ansichten rendern.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

function ansicht(string $name, array $v = []): string
{
    $pfad = ADMIN_WURZEL . '/ansichten/' . $name . '.php';
    if (!is_readable($pfad)) {
        throw new RuntimeException("Ansicht fehlt: $name");
    }
    extract($v, EXTR_SKIP);
    ob_start();
    require $pfad;
    return (string) ob_get_clean();
}

function kopfzeilen_senden(bool $rahmenErlaubt = false): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: ' . ($rahmenErlaubt ? 'SAMEORIGIN' : 'DENY'));
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-src 'self'; frame-ancestors " . ($rahmenErlaubt ? "'self'" : "'none'") . "; form-action 'self'; base-uri 'self'");
}

/** Vollständige Seite mit Navigation und Veröffentlichen-Leiste. */
function seite_ausgeben(string $titel, string $aktiv, string $inhalt, array $extra = []): never
{
    kopfzeilen_senden();
    echo ansicht('rahmen', array_merge([
        'titel' => $titel,
        'aktiv' => $aktiv,
        'inhalt' => $inhalt,
        'meldungen' => meldungen_abholen(),
        'offen' => offene_entwuerfe(),
        'weit' => false,
    ], $extra));
    exit;
}

/** Schlanke Seite ohne Navigation — für Anmeldung und Einrichtung. */
function seite_schlicht(string $titel, string $inhalt): never
{
    kopfzeilen_senden();
    echo ansicht('rahmen-schlicht', ['titel' => $titel, 'inhalt' => $inhalt, 'meldungen' => meldungen_abholen()]);
    exit;
}
