<?php
/**
 * Veröffentlichen: Entwurf → Live → Webseite neu bauen.
 *
 * Der Ablauf ist bewusst umständlich, damit nichts halb passiert:
 *
 *   1. alle Live-Daten wegsichern (mit Vermerk, wer und was)
 *   2. Entwürfe nach privat/daten/ kopieren
 *   3. Generator laufen lassen
 *   4. geht dabei etwas schief: Schritt 1 zurückspielen und noch einmal bauen
 *
 * Erst wenn der Generator sauber durchgelaufen ist, verschwinden die Entwürfe.
 *
 * Eine Sicherung lässt sich später per Klick wiederherstellen. Sie wird dabei
 * nicht direkt live geschaltet, sondern als Entwurf geladen — so sieht man
 * vorher im Vergleich, was sich zurückändert.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const SICHERUNGEN_BEHALTEN = 30;

function veroeffentlichen_moeglich(): array
{
    $p = [];
    $privat = rtrim((string) konfig('privat'), '/');
    $site = rtrim((string) konfig('site'), '/');

    if (!is_dir($privat)) {
        $p[] = 'Den privaten Ordner gibt es nicht: ' . ($privat ?: '(nichts eingetragen)') . ' — unter „Einstellungen“ den richtigen Pfad eintragen.';
    } elseif (!is_file($privat . '/bauen.php')) {
        $p[] = "In $privat liegt keine bauen.php — unter „Einstellungen“ den richtigen Pfad eintragen.";
    } elseif (!is_dir(pfad_daten()) || !is_writable(pfad_daten())) {
        $p[] = 'Kein Schreibrecht auf ' . rtrim(pfad_daten(), '/') . ' — dort landen die veröffentlichten Inhalte.';
    }
    if (!is_dir($site)) {
        $p[] = 'Den Ordner der Webseite gibt es nicht: ' . ($site ?: '(nichts eingetragen)') . ' — unter „Einstellungen“ den richtigen Pfad eintragen.';
    } elseif (!is_writable($site)) {
        $p[] = "Kein Schreibrecht auf $site — dorthin schreibt der Generator die Webseite.";
    }
    if (!is_file(pfad_privat('vorlagen/seite.css'))) {
        $p[] = 'Im privaten Ordner fehlt vorlagen/seite.css — bitte den Ordner privat/ vollständig neu hochladen.';
    }
    return $p;
}

function sicherung_anlegen(array $bereiche, string $anlass): string
{
    $ordner = pfad_sicherung(date('Y-m-d_His'));
    ordner_sicherstellen($ordner);
    foreach (glob(pfad_daten('*.json')) ?: [] as $q) {
        if (str_starts_with(basename($q), '._')) {
            continue;
        }
        if (!copy($q, $ordner . '/' . basename($q))) {
            throw new RuntimeException('Sicherung von ' . basename($q) . ' fehlgeschlagen — es wurde nichts verändert.');
        }
    }
    json_schreiben($ordner . '/_info.json', [
        'zeit' => date('c'), 'benutzer' => $_SESSION['benutzer'] ?? '', 'anlass' => $anlass, 'bereiche' => array_values($bereiche),
    ]);
    sicherungen_aufraeumen();
    return $ordner;
}

function sicherungen_aufraeumen(): void
{
    $alle = glob(pfad_sicherung('*'), GLOB_ONLYDIR) ?: [];
    sort($alle);
    foreach (array_slice($alle, 0, max(0, count($alle) - SICHERUNGEN_BEHALTEN)) as $weg) {
        foreach (glob($weg . '/{,.}*', GLOB_BRACE) ?: [] as $d) {
            if (is_file($d)) {
                @unlink($d);
            }
        }
        @rmdir($weg);
    }
}

function sicherung_zurueckspielen(string $ordner): void
{
    foreach (glob($ordner . '/*.json') ?: [] as $q) {
        if (!str_starts_with(basename($q), '_')) {
            @copy($q, pfad_daten(basename($q)));
        }
    }
}

function sicherungen_liste(): array
{
    $alle = glob(pfad_sicherung('*'), GLOB_ONLYDIR) ?: [];
    rsort($alle);
    $raus = [];
    foreach ($alle as $o) {
        $name = basename($o);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}$/', $name)) {
            continue;
        }
        $zeit = DateTimeImmutable::createFromFormat('Y-m-d_His', $name);
        $info = json_lesen($o . '/_info.json') ?? [];
        $raus[] = [
            'name' => $name,
            'zeit' => $zeit ? $zeit->format('d.m.Y, H:i') . ' Uhr' : $name,
            'benutzer' => (string) ($info['benutzer'] ?? ''),
            'anlass' => (string) ($info['anlass'] ?? ''),
            'bereiche' => array_map('datei_titel', (array) ($info['bereiche'] ?? [])),
        ];
    }
    return $raus;
}

/**
 * Lädt den Stand einer Sicherung als Entwurf.
 * @return array{0:?string,1:int} [Fehler, Anzahl geänderter Bereiche]
 */
function sicherung_wiederherstellen(string $name): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}$/', $name) || !is_dir(pfad_sicherung($name))) {
        return ['Diese Sicherung gibt es nicht (mehr).', 0];
    }
    $geaendert = 0;
    foreach (glob(pfad_sicherung($name . '/*.json')) ?: [] as $datei) {
        $bereich = basename($datei, '.json');
        if (str_starts_with($bereich, '_') || str_starts_with($bereich, '.')) {
            continue;
        }
        $daten = json_lesen($datei);
        if ($daten === null) {
            return ["$bereich.json in der Sicherung ist beschädigt — es wurde nichts übernommen.", 0];
        }
        if (entwurf_speichern($bereich, $daten)) {
            $geaendert++;
        }
    }
    // Die Hinweis-Ablage an den wiederhergestellten Stand anpassen
    $h = entwurf_laden('hinweise');
    $ablage = hinweise_ablage();
    $ablage['aktiv'] = array_values(array_intersect(array_column($ablage['eintraege'], 'id'), array_column($h['eintraege'] ?? [], 'id')));
    json_schreiben(pfad_ablage('hinweise.json'), $ablage);

    protokoll("Sicherung $name als Entwurf geladen ($geaendert Bereiche)");
    return [null, $geaendert];
}

/** @return array{ok:bool, bericht:?array, fehler:?string, bereiche:string[], sicherung:?string} */
function veroeffentlichen(): array
{
    $entwuerfe = offene_entwuerfe();
    $leer = ['ok' => false, 'bericht' => null, 'bereiche' => [], 'sicherung' => null];
    if (!$entwuerfe) {
        return $leer + ['fehler' => 'Es gibt nichts zu veröffentlichen.'];
    }
    if ($p = veroeffentlichen_moeglich()) {
        return $leer + ['fehler' => $p[0]];
    }
    foreach ($entwuerfe as $name) {
        if (json_lesen(pfad_entwurf($name . '.json')) === null) {
            return $leer + ['fehler' => "Der Entwurf $name.json ist beschädigt und wurde nicht veröffentlicht."];
        }
    }
    if (!generator_laden()) {
        return $leer + ['fehler' => 'Der Generator (bauen.php) ließ sich nicht laden.'];
    }

    $sicherung = sicherung_anlegen($entwuerfe, 'Vor dem Veröffentlichen');

    foreach ($entwuerfe as $name) {
        if (!copy(pfad_entwurf($name . '.json'), pfad_daten($name . '.json'))) {
            sicherung_zurueckspielen($sicherung);
            return ['ok' => false, 'bericht' => null, 'bereiche' => [], 'sicherung' => $sicherung,
                    'fehler' => "$name.json ließ sich nicht schreiben. Der vorherige Stand wurde wiederhergestellt."];
        }
    }

    try {
        $bericht = ws_bauen(rtrim(pfad_site(), '/'), ws_daten_ordner(pfad_daten()));
    } catch (Throwable $e) {
        sicherung_zurueckspielen($sicherung);
        try {
            ws_bauen(rtrim(pfad_site(), '/'), ws_daten_ordner(pfad_daten()));
        } catch (Throwable) {
            // Dann bleibt die Webseite auf dem letzten erfolgreich gebauten Stand.
        }
        protokoll('FEHLGESCHLAGEN: ' . $e->getMessage());
        return ['ok' => false, 'bericht' => null, 'bereiche' => $entwuerfe, 'sicherung' => $sicherung,
                'fehler' => 'Der Generator ist ausgestiegen: ' . $e->getMessage() . ' — die Webseite steht unverändert, die Entwürfe sind erhalten.'];
    }

    foreach ($entwuerfe as $name) {
        entwurf_verwerfen($name);
    }
    protokoll(sprintf('veröffentlicht: %s (%s ms)', implode(', ', array_map('datei_titel', $entwuerfe)), $bericht['dauer']));
    letzte_veroeffentlichung_merken($bericht, $entwuerfe);

    return ['ok' => true, 'bericht' => $bericht, 'fehler' => null, 'bereiche' => $entwuerfe, 'sicherung' => $sicherung];
}

/**
 * Webseite aus dem veröffentlichten Stand neu erzeugen, ohne Inhalte zu ändern.
 * Nötig nach einem Update der Vorlage (privat/vorlagen, privat/bauen.php),
 * nach dem Ersetzen von Medien oder wenn Dateien per SFTP ausgetauscht wurden.
 */
function neu_bauen(): array
{
    $leer = ['ok' => false, 'bericht' => null, 'bereiche' => [], 'sicherung' => null];
    if ($p = veroeffentlichen_moeglich()) {
        return $leer + ['fehler' => $p[0]];
    }
    if (!generator_laden()) {
        return $leer + ['fehler' => 'Der Generator (bauen.php) ließ sich nicht laden.'];
    }
    try {
        $bericht = ws_bauen(rtrim(pfad_site(), '/'), ws_daten_ordner(pfad_daten()));
    } catch (Throwable $e) {
        protokoll('Neu erzeugen fehlgeschlagen: ' . $e->getMessage());
        return $leer + ['fehler' => 'Der Generator ist ausgestiegen: ' . $e->getMessage() . ' — die Webseite steht unverändert.'];
    }
    protokoll(sprintf('Webseite neu erzeugt (%d Seiten, %s ms)', $bericht['seiten'], $bericht['dauer']));
    letzte_veroeffentlichung_merken($bericht, []);
    return ['ok' => true, 'bericht' => $bericht, 'fehler' => null, 'bereiche' => [], 'sicherung' => null];
}

function letzte_veroeffentlichung_merken(array $bericht, array $bereiche): void
{
    try {
        json_schreiben(pfad_ablage('letzte-veroeffentlichung.json'), [
            'zeit' => date('c'), 'benutzer' => $_SESSION['benutzer'] ?? '', 'bereiche' => $bereiche, 'bericht' => $bericht,
        ]);
    } catch (Throwable) {
    }
}

function letzte_veroeffentlichung(): ?array
{
    return json_lesen(pfad_ablage('letzte-veroeffentlichung.json'));
}

/**
 * Dieselben Prüfungen, die der Generator beim Bauen meldet — schon auf dem
 * Entwurf, damit man vorher weiß, was einen erwartet.
 */
function offene_punkte(): array
{
    static $punkte = null;
    if ($punkte !== null) {
        return $punkte;
    }
    if (!generator_laden()) {
        return $punkte = ['Der Generator (bauen.php) wurde nicht gefunden — Prüfung nicht möglich.'];
    }
    try {
        [, $punkte] = ws_inhalt(ws_daten_entwurf(rtrim(pfad_privat(), '/')), ['medien' => rtrim(pfad_site(), '/')]);
    } catch (Throwable $e) {
        $punkte = ['Prüfung nicht möglich: ' . $e->getMessage()];
    }
    return $punkte;
}
