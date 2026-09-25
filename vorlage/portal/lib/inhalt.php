<?php
/**
 * Inhalte lesen und schreiben.
 *
 * Zwei Stände liegen nebeneinander:
 *
 *   privat/daten/    → das, was auf der Webseite steht (live)
 *   privat/entwurf/  → das, was im Portal bearbeitet wurde
 *
 * Bearbeiten schreibt immer nur in entwurf/. Erst „Veröffentlichen“ schiebt
 * den Entwurf nach daten/ und baut die Webseite neu.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

function live_laden(string $name): array
{
    return json_lesen(pfad_daten($name . '.json')) ?? [];
}

/** Entwurf, falls vorhanden — sonst live. */
function entwurf_laden(string $name): array
{
    return json_lesen(pfad_entwurf($name . '.json')) ?? live_laden($name);
}

/**
 * Entwurf sichern. Ist er inhaltlich gleich dem Live-Stand, verschwindet die
 * Entwurfsdatei wieder — dann steht in der Übersicht auch nichts Offenes.
 */
function entwurf_speichern(string $name, array $daten): bool
{
    $datei = pfad_entwurf($name . '.json');
    if ($daten == live_laden($name)) {
        if (is_file($datei)) {
            unlink($datei);
        }
        return false;
    }
    json_schreiben($datei, $daten);
    return true;
}

function entwurf_verwerfen(string $name): void
{
    $datei = pfad_entwurf($name . '.json');
    if (is_file($datei)) {
        unlink($datei);
    }
}

function entwurf_offen(string $name): bool
{
    return is_file(pfad_entwurf($name . '.json'));
}

function offene_entwuerfe(): array
{
    $namen = [];
    foreach (glob(pfad_entwurf('*.json')) ?: [] as $p) {
        if (!str_starts_with(basename($p), '._')) {
            $namen[] = basename($p, '.json');
        }
    }
    sort($namen);
    return $namen;
}

/**
 * Kennung eines Datenstands. Steht versteckt im Formular; hat sich der Stand
 * beim Speichern inzwischen geändert, hat jemand anderes dazwischen gespeichert.
 */
function stand_kennung(mixed $daten): string
{
    return substr(md5((string) json_encode($daten, JSON_UNESCAPED_UNICODE)), 0, 16);
}

/* ----------------------------------------------------------- Vergleich --- */

/**
 * Unterschiede zweier Datenstände als flache Liste:
 * [['pfad' => 'kontakt › telefon', 'alt' => '…', 'neu' => '…', 'art' => 'geaendert'], …]
 */
function vergleich(mixed $alt, mixed $neu, string $pfad = '', array &$ergebnis = []): array
{
    if (is_array($alt) && is_array($neu)) {
        foreach (array_keys($alt + $neu) as $k) {
            if (is_string($k) && str_starts_with($k, '_')) {
                continue;
            }
            $teil = is_int($k) ? 'Nr. ' . ($k + 1) : (string) $k;
            $unter = $pfad === '' ? $teil : $pfad . ' › ' . $teil;
            if (!array_key_exists($k, $alt)) {
                $ergebnis[] = ['pfad' => $unter, 'art' => 'neu', 'alt' => '', 'neu' => kurz($neu[$k])];
            } elseif (!array_key_exists($k, $neu)) {
                $ergebnis[] = ['pfad' => $unter, 'art' => 'entfernt', 'alt' => kurz($alt[$k]), 'neu' => ''];
            } else {
                vergleich($alt[$k], $neu[$k], $unter, $ergebnis);
            }
        }
        return $ergebnis;
    }
    if ($alt !== $neu) {
        $ergebnis[] = ['pfad' => $pfad, 'art' => 'geaendert', 'alt' => kurz($alt), 'neu' => kurz($neu)];
    }
    return $ergebnis;
}

function kurz(mixed $wert, int $max = 120): string
{
    if (is_bool($wert)) {
        return $wert ? 'ja' : 'nein';
    }
    if (is_array($wert)) {
        foreach (['titel', 'name', 'frage', 'tage'] as $k) {
            if (isset($wert[$k]) && is_string($wert[$k]) && trim($wert[$k]) !== '') {
                return kurz($wert[$k], $max);
            }
        }
        $n = count($wert);
        return $n === 1 ? '1 Eintrag' : $n . ' Einträge';
    }
    $s = trim(str_replace("\n", ' ⏎ ', (string) $wert));
    if ($s === '') {
        return '(leer)';
    }
    return mb_strlen($s) > $max ? mb_substr($s, 0, $max) . ' …' : $s;
}

/** Änderungen aller offenen Entwürfe, je Datei. */
function alle_aenderungen(): array
{
    $raus = [];
    foreach (offene_entwuerfe() as $name) {
        $alt = live_laden($name);
        $neu = entwurf_laden($name);
        if ($name === 'startseite') {
            $unterschiede = sektionen_vergleich($alt['sektionen'] ?? [], $neu['sektionen'] ?? []);
        } else {
            $unterschiede = vergleich($alt, $neu);
        }
        if ($unterschiede) {
            $raus[$name] = $unterschiede;
        }
    }
    return $raus;
}

/**
 * Sektionen nicht nach Position vergleichen, sondern nach Kennung — sonst
 * erschiene nach dem Verschieben einer Sektion jede darunter als „geändert“.
 */
function sektionen_vergleich(array $alt, array $neu): array
{
    $nachId = static function (array $liste): array {
        $raus = [];
        foreach ($liste as $s) {
            $raus[(string) ($s['id'] ?? '')] = $s;
        }
        return $raus;
    };
    $a = $nachId($alt);
    $n = $nachId($neu);
    $ergebnis = [];

    foreach ($n as $id => $s) {
        $name = sektion_titel($s);
        if (!isset($a[$id])) {
            $ergebnis[] = ['pfad' => $name, 'art' => 'neu', 'alt' => '', 'neu' => 'Sektion „' . sektion_typ_name($s['typ'] ?? '') . '“ hinzugefügt'];
            continue;
        }
        $teil = [];
        vergleich($a[$id], $s, $name, $teil);
        $ergebnis = array_merge($ergebnis, $teil);
    }
    foreach ($a as $id => $s) {
        if (!isset($n[$id])) {
            $ergebnis[] = ['pfad' => sektion_titel($s), 'art' => 'entfernt', 'alt' => 'Sektion „' . sektion_typ_name($s['typ'] ?? '') . '“', 'neu' => ''];
        }
    }
    $reihenfolgeAlt = array_values(array_filter(array_keys($a), fn($id) => isset($n[$id])));
    $reihenfolgeNeu = array_values(array_filter(array_keys($n), fn($id) => isset($a[$id])));
    if ($reihenfolgeAlt !== $reihenfolgeNeu) {
        $ergebnis[] = ['pfad' => 'Reihenfolge', 'art' => 'geaendert',
            'alt' => implode(' → ', array_map(fn($id) => sektion_titel($a[$id]), $reihenfolgeAlt)),
            'neu' => implode(' → ', array_map(fn($id) => sektion_titel($n[$id]), $reihenfolgeNeu))];
    }
    return $ergebnis;
}
