<?php
/**
 * Generator der Webseite.
 *
 * Die Webseite ist reines HTML und CSS — ohne Framework, ohne Build-Schritt.
 * Alles, was Besucher lesen, kommt aus den JSON-Dateien in privat/daten/,
 * alles, wie es aussieht, aus dem gewählten Design in privat/design/. Der
 * Generator
 *
 *   1. stellt daraus einen öffentlichen Inhaltsstand zusammen (Platzhalter in
 *      den Rechtstexten gefüllt, ausgeblendete Einträge entfernt, Links geprüft),
 *   2. schreibt index.html für die Startseite und je eine Seite für Impressum,
 *      Datenschutz und Barrierefreiheit — mit Seitentitel, Beschreibung und
 *      Vorschau fürs Teilen,
 *   3. legt assets/ (Design, Gestaltung, Skript, Schriften), sitemap.xml,
 *      robots.txt, die .htaccess der Webseite und bei Bedarf anfrage.php an
 *   4. und meldet offene Punkte.
 *
 * Aufruf per CLI:
 *     php privat/bauen.php <docroot>
 *     php privat/bauen.php --pruefen              → nur die Prüfliste
 *
 * und aus dem Portal:
 *     require '/pfad/zu/privat/bauen.php';
 *     $bericht = ws_bauen($docroot, $laden);
 *
 * Neue Sektion: Darstellung als ws_sektion_<typ>() unten ergänzen (Bindestriche
 * im Typ werden zu Unterstrichen) — Felder und Vorlage stehen im Portal in
 * lib/sektionen.php.
 */

declare(strict_types=1);

const WS_DATEIEN = ['stammdaten', 'zeiten', 'allgemein', 'startseite', 'angebote', 'team',
                    'preise', 'formular', 'rechtliches', 'hinweise'];

const WS_RECHTSSEITEN = ['impressum' => 'Impressum', 'datenschutz' => 'Datenschutz', 'barrierefreiheit' => 'Barrierefreiheit'];

/** Daran erkennt der Generator seine eigene .htaccess im Docroot wieder. */
const WS_HTACCESS_MARKE = 'Website-Generator';

require_once __DIR__ . '/theme.php';

const WS_SYSTEMSCHRIFT = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif';

/**
 * Stellschrauben eines Designs: Schlüssel => [Beschriftung, Art, Gruppe, Vorgabe (Aurora), Erklärung].
 * Art: farbe | schrift | mass | zahl | schalter | auswahl
 */
const WS_DESIGN_TOKENS = [
    'bg'         => ['Seitenhintergrund', 'farbe', 'Flächen', '#0e0e16', 'Die Grundfläche jeder Seite.'],
    'surface'    => ['Karten & Flächen', 'farbe', 'Flächen', '#1a1a28', 'Karten, Kästen, abgesetzte Sektionen.'],
    'surface-2'  => ['Eingaben & zweite Ebene', 'farbe', 'Flächen', '#22222f', 'Formularfelder, Hover, Tabellenzeilen.'],
    'line'       => ['Linien & Rahmen', 'farbe', 'Flächen', '#2a2a3c', 'Trennlinien und Rahmen.'],
    'text'       => ['Haupttext', 'farbe', 'Text', '#ececf5', 'Überschriften und Fließtext.'],
    'text-2'     => ['Zweittext', 'farbe', 'Text', '#9a9ab0', 'Einleitungen, Beschreibungen.'],
    'muted'      => ['Leiser Text', 'farbe', 'Text', '#6b6b82', 'Fußnoten, Metadaten.'],
    'accent'     => ['Akzent', 'farbe', 'Akzent', '#5eead4', 'Knöpfe, Links, alles Aktive.'],
    'accent-ink' => ['Schrift auf Akzent', 'farbe', 'Akzent', '#04241f', 'Muss auf der Akzentfarbe gut lesbar sein.'],
    'ok'         => ['Erfolg', 'farbe', 'Akzent', '#34d399', 'Bestätigungen.'],
    'danger'     => ['Fehler & Warnung', 'farbe', 'Akzent', '#fb7185', 'Fehlermeldungen, wichtige Hinweise.'],
    'aurora'     => ['Aurora-Verlauf im Hintergrund', 'schalter', 'Verlauf', true, 'Drei weiche Farbschleier hinter dem Inhalt — nie auf Karten oder Knöpfen.'],
    'aurora-1'   => ['Verlauf Farbe 1', 'farbe', 'Verlauf', '#34d399', 'Links oben.'],
    'aurora-2'   => ['Verlauf Farbe 2', 'farbe', 'Verlauf', '#8b5cf6', 'Rechts oben.'],
    'aurora-3'   => ['Verlauf Farbe 3', 'farbe', 'Verlauf', '#38bdf8', 'Mitte oben.'],
    'aurora-staerke' => ['Stärke des Verlaufs (%)', 'zahl', 'Verlauf', 16, 'Unter 26 halten — der Verlauf ist Kulisse, nicht Inhalt.'],
    'font-body'  => ['Schrift für Text', 'schrift', 'Schrift', WS_SYSTEMSCHRIFT, 'CSS-Schriftliste. Ohne eigene Schrift: die Systemschrift des Geräts.'],
    'font-head'  => ['Schrift für Überschriften', 'schrift', 'Schrift', WS_SYSTEMSCHRIFT, ''],
    'head-weight' => ['Stärke der Überschriften', 'zahl', 'Schrift', 600, '400 normal, 600 halbfett, 700–800 fett.'],
    'text-size'  => ['Schriftgröße Fließtext (px)', 'zahl', 'Schrift', 17, '16 bis 19 sind gut lesbar.'],
    'radius'     => ['Rundung Karten', 'mass', 'Form', '16px', 'z. B. 16px, 4px oder 0.'],
    'radius-sm'  => ['Rundung Knöpfe & Felder', 'mass', 'Form', '12px', ''],
    'maxw'       => ['Inhaltsbreite', 'mass', 'Form', '1120px', 'Breite des Inhalts auf großen Bildschirmen.'],
    'modus'      => ['Grundton', 'auswahl', 'Form', 'dunkel', 'Steuert Scrollbalken, Formularelemente und die Farbe der Browserleiste.'],
];

/* ============================================================ Laden === */

/** Liest die Inhaltsdateien aus einem Ordner. */
function ws_daten_ordner(string $ordner): callable
{
    return static function (string $name) use ($ordner): array {
        $pfad = rtrim($ordner, '/') . '/' . $name . '.json';
        if (!is_file($pfad)) {
            throw new RuntimeException("Die Inhaltsdatei $name.json fehlt.");
        }
        $daten = json_decode((string) file_get_contents($pfad), true);
        if (!is_array($daten)) {
            throw new RuntimeException("Die Inhaltsdatei $name.json ist beschädigt.");
        }
        return $daten;
    };
}

/** Wie ws_daten_ordner(), bevorzugt aber einen vorhandenen Entwurf. */
function ws_daten_entwurf(string $privat): callable
{
    $live = ws_daten_ordner($privat . '/daten');
    return static function (string $name) use ($privat, $live): array {
        $entwurf = $privat . '/entwurf/' . $name . '.json';
        if (is_file($entwurf)) {
            $daten = json_decode((string) file_get_contents($entwurf), true);
            if (is_array($daten)) {
                return $daten;
            }
        }
        return $live($name);
    };
}

/** Projektangaben aus dem Assistenten (Begriffe, Module). */
function ws_projekt(): array
{
    static $p = null;
    if ($p === null) {
        $roh = json_decode((string) @file_get_contents(__DIR__ . '/projekt.json'), true);
        $p = array_replace_recursive([
            'name' => '', 'organisation' => 'Betrieb', 'reglementiert' => false, 'zeitzone' => 'Europe/Berlin',
            'module' => ['angebote' => true, 'team' => true, 'preise' => true, 'zeiten' => true, 'formular' => true, 'hinweise' => true, 'barrierefreiheit' => true],
            'begriffe' => ['angebote' => 'Leistungen', 'angebot' => 'Leistung', 'team' => 'Team', 'zeiten' => 'Öffnungszeiten'],
        ], is_array($roh) ? $roh : []);
    }
    return $p;
}

function ws_modul(string $name): bool
{
    return !empty(ws_projekt()['module'][$name]);
}

date_default_timezone_set((string) (ws_projekt()['zeitzone'] ?: 'Europe/Berlin'));

/* ========================================================== Helfer === */

function ws_e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ws_text(mixed $wert): string
{
    return is_scalar($wert) ? trim((string) $wert) : '';
}

/** Mehrzeiliger Text: escapen, Umbrüche erhalten. */
function ws_zeilen(mixed $wert): string
{
    return nl2br(ws_e(ws_text($wert)), false);
}

/** Interne Vermerke (Schlüssel mit _) gehören nicht auf die Webseite. */
function ws_ohne_intern(mixed $daten): mixed
{
    if (!is_array($daten)) {
        return $daten;
    }
    $raus = [];
    foreach ($daten as $k => $v) {
        if (is_string($k) && str_starts_with($k, '_')) {
            continue;
        }
        $raus[$k] = ws_ohne_intern($v);
    }
    return $raus;
}

/**
 * Nur Ziele, die im Browser nichts anrichten können: Anker, relative Pfade,
 * http(s), mailto und tel. „javascript:“ und Verwandte fallen heraus.
 */
function ws_sicheres_ziel(string $ziel): bool
{
    $ziel = trim($ziel);
    if ($ziel === '') {
        return true;
    }
    if (preg_match('~^#[A-Za-z0-9_-]*$~', $ziel)) {
        return true;
    }
    if (preg_match('~^(https?://|mailto:|tel:)[^\s<>"]+$~i', $ziel)) {
        return true;
    }
    return (bool) preg_match('~^(?![a-z][a-z0-9+.-]*:)(?!//)[A-Za-z0-9._~/%#?=&-]+$~i', $ziel);
}

/** Ein Linkziel von der jeweiligen Seite aus: #anker auf Unterseiten führt zur Startseite. */
function ws_ziel(string $ziel, string $basis): string
{
    $ziel = trim($ziel);
    if ($ziel === '' || !ws_sicheres_ziel($ziel)) {
        return '';
    }
    if (preg_match('~^(https?://|mailto:|tel:)~i', $ziel)) {
        return $ziel;
    }
    if (str_starts_with($ziel, '#')) {
        return $basis === '' ? $ziel : $basis . $ziel;
    }
    return $basis . ltrim($ziel, '/');
}

function ws_extern(string $url): string
{
    return preg_match('~^https?://~i', $url) ? ' target="_blank" rel="noopener"' : '';
}

/** Läuft rekursiv über alle Werte und ruft $f(Schlüssel, Wert, Pfad) auf. */
function ws_durchlaufen(mixed $daten, callable $f, string $pfad = ''): void
{
    if (!is_array($daten)) {
        return;
    }
    foreach ($daten as $k => $v) {
        $unter = $pfad === '' ? (string) $k : $pfad . ' › ' . $k;
        if (is_array($v)) {
            ws_durchlaufen($v, $f, $unter);
        } else {
            $f((string) $k, $v, $unter);
        }
    }
}

function ws_slug(string $s): string
{
    $s = strtr(mb_strtolower($s), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-');
}

/* ========================================================== Design === */

function ws_design_ordner(): string
{
    return __DIR__ . '/design';
}

/** Ein Design laden — fehlende Werte kommen aus der Aurora-Vorlage. */
function ws_design_laden(string $id): array
{
    $id = preg_match('/^[a-z0-9-]+$/', $id) ? $id : 'aurora';
    $datei = ws_design_ordner() . '/' . $id . '/design.json';
    $d = is_file($datei) ? json_decode((string) file_get_contents($datei), true) : null;
    if (!is_array($d)) {
        $id = 'aurora';
        $d = json_decode((string) @file_get_contents(ws_design_ordner() . '/aurora/design.json'), true) ?: [];
    }
    $vorgabe = [];
    foreach (WS_DESIGN_TOKENS as $k => [, , , $wert]) {
        $vorgabe[$k] = $wert;
    }
    $d['id'] = $id;
    $d['name'] = (string) ($d['name'] ?? $id);
    $d['tokens'] = array_merge($vorgabe, is_array($d['tokens'] ?? null) ? $d['tokens'] : []);
    $d['fonts'] = is_array($d['fonts'] ?? null) ? $d['fonts'] : [];
    $d['variablen'] = is_array($d['variablen'] ?? null) ? $d['variablen'] : [];
    return $d;
}

/** Nur Zeichen, die in einem CSS-Wert nichts aufbrechen können. */
function ws_css_wert(mixed $wert): string
{
    $w = trim((string) (is_bool($wert) ? ($wert ? '1' : '0') : $wert));
    $w = str_replace(['<', '>', '{', '}', ';', '\\', "\n", "\r", '/*', '*/'], '', $w);
    return mb_substr($w, 0, 300);
}

/**
 * Das CSS eines Designs: Schriften, Variablen, Grundton.
 * $schriftBasis: Pfad, unter dem die Schriftdateien des Designs liegen (endet mit /).
 */
function ws_design_css(array $d, string $schriftBasis): string
{
    $t = $d['tokens'];
    $css = "/* Design „" . str_replace('*/', '', (string) $d['name']) . "“ — vom Generator geschrieben, Änderungen im Portal unter „Design“. */\n";

    foreach ($d['fonts'] as $f) {
        $datei = basename((string) ($f['datei'] ?? ''));
        $familie = str_replace(['"', "'"], '', ws_css_wert($f['familie'] ?? ''));
        if ($datei === '' || $familie === '' || !preg_match('/\.(woff2?|ttf|otf)$/i', $datei)) {
            continue;
        }
        $format = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'][strtolower(pathinfo($datei, PATHINFO_EXTENSION))];
        $css .= '@font-face{font-family:"' . $familie . '";src:url("' . $schriftBasis . rawurlencode($datei) . '") format("' . $format . '");'
            . 'font-weight:' . (preg_match('/^[0-9 ]+$/', (string) ($f['gewicht'] ?? '')) ? $f['gewicht'] : '400') . ';'
            . 'font-style:' . (($f['stil'] ?? '') === 'italic' ? 'italic' : 'normal') . ';font-display:swap}' . "\n";
    }

    $css .= ":root{\n";
    // Zuerst die Variablen aus einem hochgeladenen Design System — so bleiben sie für eigene Ergänzungen greifbar
    foreach ($d['variablen'] as $name => $wert) {
        if (preg_match('/^--[A-Za-z0-9_-]{1,80}$/', (string) $name) && !str_starts_with((string) $name, '--ws-')) {
            $css .= '  ' . $name . ': ' . ws_css_wert($wert) . ";\n";
        }
    }
    foreach (WS_DESIGN_TOKENS as $k => [, $art]) {
        if (in_array($art, ['schalter', 'auswahl'], true)) {
            continue;
        }
        $wert = ws_css_wert($t[$k] ?? '');
        if ($art === 'zahl') {
            $wert = (string) (float) $wert;
        }
        if ($k === 'text-size') {
            $wert .= 'px';
        }
        if ($k === 'aurora-staerke') {
            $wert = max(0, min(40, (float) $wert)) . '%';
        }
        $css .= '  --ws-' . $k . ': ' . $wert . ";\n";
    }
    $css .= '  color-scheme: ' . (($t['modus'] ?? 'dunkel') === 'hell' ? 'light' : 'dark') . ";\n}\n";
    return $css;
}

/* ===================================================== Rechtstexte === */

/** Platzhalter der Rechtstexte: Name => [Beschriftung, Pflicht]. */
function ws_platzhalter(): array
{
    $regl = !empty(ws_projekt()['reglementiert']);
    return [
        'name'              => ['Name', true],
        'inhaber'           => ['Inhaberin oder Inhaber', true],
        'rechtsform'        => ['Rechtsform', false],
        'vertreten'         => ['Vertreten durch', false],
        'register'          => ['Registereintrag', false],
        'anschrift'         => ['Anschrift', true],
        'telefon'           => ['Telefon', true],
        'fax'               => ['Telefax', false],
        'email'             => ['E-Mail', true],
        'domain'            => ['Adresse der Webseite', false],
        'ustid'             => ['USt-IdNr.', false],
        'berufsbezeichnung' => ['Berufsbezeichnung', $regl],
        'verliehenIn'       => ['Staat, in dem die Berufsbezeichnung verliehen wurde', $regl],
        'kammer'            => ['Kammer', false],
        'aufsichtsbehoerde' => ['Zuständige Aufsichtsbehörde', $regl],
        'berufsrecht'       => ['Berufsrechtliche Regelungen', $regl],
        'verantwortlich'    => ['Verantwortlich nach § 18 MStV', true],
        'hoster'            => ['Hosting-Anbieter', true],
    ];
}

/** @return array<string,string> */
function ws_bausteine(array $stamm, array $allgemein): array
{
    $k = $stamm['kontakt'] ?? [];
    $r = $stamm['recht'] ?? [];
    $ort = trim(ws_text($k['plz'] ?? '') . ' ' . ws_text($k['ort'] ?? ''));
    $anschrift = ws_text($k['strasse'] ?? '') !== '' && $ort !== '' ? ws_text($k['strasse']) . "\n" . $ort : '';

    return [
        'name'              => ws_text($stamm['name'] ?? ''),
        'inhaber'           => ws_text($r['inhaber'] ?? ''),
        'rechtsform'        => ws_text($r['rechtsform'] ?? ''),
        'vertreten'         => ws_text($r['vertreten'] ?? ''),
        'register'          => ws_text($r['register'] ?? ''),
        'anschrift'         => $anschrift,
        'telefon'           => ws_text($k['telefon'] ?? ''),
        'fax'               => ws_text($k['fax'] ?? ''),
        'email'             => ws_text($k['email'] ?? ''),
        'domain'            => preg_replace('~^https?://~', '', rtrim(ws_text($allgemein['domain'] ?? ''), '/')) ?? '',
        'ustid'             => ws_text($r['ustid'] ?? ''),
        'berufsbezeichnung' => ws_text($r['berufsbezeichnung'] ?? ''),
        'verliehenIn'       => ws_text($r['verliehenIn'] ?? ''),
        'kammer'            => ws_text($r['kammer'] ?? ''),
        'aufsichtsbehoerde' => ws_text($r['aufsichtsbehoerde'] ?? ''),
        'berufsrecht'       => ws_text($r['berufsrecht'] ?? ''),
        'verantwortlich'    => ws_text($r['verantwortlich'] ?? '') ?: ws_text($r['inhaber'] ?? ''),
        'hoster'            => ws_text($r['hoster'] ?? ''),
    ];
}

/** Setzt die Bausteine in einen Rechtstext ein und sammelt offene Punkte. */
function ws_rechtstext_fuellen(array $text, array $bausteine, string $name, array &$warnungen): array
{
    $platzhalter = ws_platzhalter();
    $org = ws_projekt()['organisation'];
    $abschnitte = [];
    foreach ($text['abschnitte'] ?? [] as $a) {
        $titel = ws_text($a['titel'] ?? '');
        $absaetze = [];

        foreach ($a['absaetze'] ?? [] as $absatz) {
            $absatz = (string) $absatz;
            $fehlend = [];
            $zeilen = [];

            foreach (explode("\n", $absatz) as $zeile) {
                $weglassen = false;
                if (preg_match_all('~\{([A-Za-z]+)\}~', $zeile, $m)) {
                    foreach ($m[1] as $p) {
                        if (!isset($platzhalter[$p])) {
                            $warnungen[] = "$name / $titel: Den Platzhalter {{$p}} gibt es nicht — er bliebe so auf der Seite stehen.";
                            continue;
                        }
                        [$label, $pflicht] = $platzhalter[$p];
                        if (($bausteine[$p] ?? '') === '') {
                            if ($pflicht) {
                                $fehlend[$label] = $label;
                            } else {
                                $weglassen = true;
                            }
                        }
                    }
                }
                if (!$weglassen) {
                    $zeilen[] = $zeile;
                }
            }

            if ($fehlend) {
                $absatz = 'LUECKE: Es fehlt noch: ' . implode(', ', $fehlend) . ' — im Portal unter „' . $org . ' & Kontakt“ eintragen.';
            } else {
                $absatz = trim(strtr(implode("\n", $zeilen), array_combine(
                    array_map(static fn($k) => '{' . $k . '}', array_keys($bausteine)),
                    array_values($bausteine)
                )));
            }
            if ($absatz === '') {
                continue;
            }
            if (str_starts_with($absatz, 'LUECKE:')) {
                $warnungen[] = "$name / $titel: " . trim(substr($absatz, 7));
            }
            $absaetze[] = $absatz;
        }

        if ($absaetze) {
            $abschnitte[] = ['titel' => $titel, 'absaetze' => $absaetze];
        }
    }

    return [
        'titel' => ws_text($text['titel'] ?? $name) ?: $name,
        'stand' => ws_text($text['stand'] ?? ''),
        'abschnitte' => $abschnitte,
    ];
}

/* ================================================== Inhalt bauen === */

/**
 * Stellt den öffentlichen Inhalt zusammen.
 *
 * @param array{medien?:string, heute?:string} $opt  medien = Docroot, um fehlende Dateien zu finden
 * @return array{0:array,1:string[]} [Inhalt, Warnungen]
 */
function ws_inhalt(callable $laden, array $opt = []): array
{
    $w = [];
    $heute = $opt['heute'] ?? date('Y-m-d');
    $projekt = ws_projekt();
    $org = $projekt['organisation'];

    $d = [];
    foreach (WS_DATEIEN as $name) {
        try {
            $d[$name] = ws_ohne_intern($laden($name));
        } catch (RuntimeException $e) {
            $d[$name] = [];
            $w[] = $e->getMessage();
        }
    }
    $stamm = $d['stammdaten'];
    $allgemein = $d['allgemein'];

    /* ---------------------------------------------------- Sektionen --- */
    $sektionen = [];
    $anker = [];
    $abhaengig = ['angebote' => 'angebote', 'team' => 'team', 'preise' => 'preise'];
    foreach ($d['startseite']['sektionen'] ?? [] as $s) {
        if (!is_array($s) || empty($s['aktiv'])) {
            continue;
        }
        $typ = (string) ($s['typ'] ?? '');
        if (isset($abhaengig[$typ]) && !ws_modul($abhaengig[$typ])) {
            continue;
        }
        $a = strtolower(ws_text($s['anker'] ?? ''));
        $name = ws_text($s['titel'] ?? '') ?: (ws_text($s['eyebrow'] ?? '') ?: $typ);
        if ($a !== '' && isset($anker[$a])) {
            $w[] = "Sektionen / $name: Die Adresse #$a kommt mehrfach vor — Links springen dann nur zur ersten.";
        }
        if ($a !== '') {
            $anker[$a] = true;
        }
        if ($typ === 'hero') {
            if (($s['medium'] ?? '') === 'video' && ws_text($s['video'] ?? '') === '') {
                $w[] = 'Kopfbereich: Als Hintergrund ist „Video“ gewählt, aber kein Video eingetragen — es erscheint der Verlauf.';
                $s['medium'] = 'verlauf';
            }
            if (($s['medium'] ?? '') === 'bild' && ws_text($s['bild'] ?? '') === '') {
                $w[] = 'Kopfbereich: Als Hintergrund ist „Bild“ gewählt, aber kein Bild eingetragen — es erscheint der Verlauf.';
                $s['medium'] = 'verlauf';
            }
        }
        unset($s['aktiv']);
        $sektionen[] = $s;
    }
    if (!$sektionen) {
        $w[] = 'Sektionen: Es ist keine einzige Sektion eingeschaltet — die Startseite wäre leer.';
    }

    /* ------------------------------------------- Angebote und Team --- */
    $angebote = [];
    $ids = [];
    if (ws_modul('angebote')) {
        foreach ($d['angebote'] as $i => $l) {
            if (!is_array($l) || empty($l['aktiv']) || ws_text($l['titel'] ?? '') === '') {
                continue;
            }
            $id = strtolower(ws_text($l['id'] ?? '')) ?: (ws_slug((string) $l['titel']) ?: 'eintrag-' . ($i + 1));
            if (isset($ids[$id])) {
                $id .= '-' . ($i + 1);
            }
            $ids[$id] = true;
            $l['id'] = $id;
            unset($l['aktiv']);
            $angebote[] = $l;
        }
    }
    $team = [];
    if (ws_modul('team')) {
        foreach ($d['team'] as $p) {
            if (is_array($p) && !empty($p['aktiv']) && ws_text($p['name'] ?? '') !== '') {
                unset($p['aktiv']);
                $team[] = $p;
            }
        }
    }

    /* ---------------------------------------------------- Hinweise --- */
    $hinweise = [];
    if (ws_modul('hinweise')) {
        foreach ($d['hinweise']['eintraege'] ?? [] as $h) {
            if (!is_array($h) || ws_text($h['titel'] ?? '') === '') {
                continue;
            }
            if (($h['gueltigBis'] ?? '') !== '' && $h['gueltigBis'] < $heute) {
                continue; // abgelaufen — muss niemand mehr herunterladen
            }
            $hinweise[] = $h;
        }
    }

    /* ------------------------------------------------- Rechtstexte --- */
    $bausteine = ws_bausteine($stamm, $allgemein);
    $recht = [];
    foreach (WS_RECHTSSEITEN as $seite => $titel) {
        if ($seite === 'barrierefreiheit' && !ws_modul('barrierefreiheit')) {
            continue;
        }
        if (!isset($d['rechtliches'][$seite])) {
            $w[] = "Rechtliches: Der Text „{$titel}“ fehlt.";
            continue;
        }
        $recht[$seite] = ws_rechtstext_fuellen($d['rechtliches'][$seite], $bausteine, $titel, $w);
    }

    /* ---------------------------------------------- Pflichtangaben --- */
    if (ws_text($stamm['name'] ?? '') === '') {
        $w[] = "$org & Kontakt: Der Name fehlt.";
    }
    if (ws_text($stamm['kontakt']['email'] ?? '') === '') {
        $w[] = "$org & Kontakt: Die E-Mail-Adresse fehlt — sie ist im Impressum Pflicht.";
    }
    $domain = rtrim(ws_text($allgemein['domain'] ?? ''), '/');
    if ($domain === '') {
        $w[] = 'Webseite allgemein: Die Adresse der Webseite fehlt — ohne sie entstehen keine sitemap.xml und keine Vorschau beim Teilen.';
    } elseif (!str_starts_with($domain, 'https://')) {
        $w[] = 'Webseite allgemein: Die Adresse sollte mit https:// beginnen.';
    }
    if (empty($allgemein['seo']['indexieren'])) {
        $w[] = 'Webseite allgemein: Suchmaschinen sind noch ausgesperrt (noindex). Zum Start der Webseite einschalten.';
    }

    $formular = $d['formular'];
    $formularAn = ws_modul('formular') && ($formular['modus'] ?? 'aus') !== 'aus';
    if ($formularAn) {
        $an = ws_text($formular['empfaenger'] ?? '') ?: ws_text($stamm['kontakt']['email'] ?? '');
        if (!filter_var($an, FILTER_VALIDATE_EMAIL)) {
            $w[] = 'Kontaktformular: Es gibt keine gültige Empfänger-Adresse.';
        }
    }

    /* ------------------------------------------------ Links prüfen --- */
    $alles = ['sektionen' => $sektionen, 'allgemein' => $allgemein, 'stamm' => $stamm, 'hinweise' => $hinweise];
    ws_durchlaufen($alles, static function (string $k, mixed $v, string $pfad) use (&$w, $anker): void {
        if (!is_string($v)) {
            return;
        }
        if (in_array($k, ['ziel', 'url', 'knopfZiel'], true) && $v !== '') {
            if (!ws_sicheres_ziel($v)) {
                $w[] = "Link „{$v}“ ($pfad) ist nicht erlaubt und wird weggelassen.";
            } elseif (str_starts_with($v, '#') && strlen($v) > 1 && !isset($anker[substr($v, 1)])) {
                $w[] = "Link „{$v}“ ($pfad) zeigt auf eine Sektion, die es nicht gibt oder die ausgeblendet ist.";
            }
        }
    });

    /* ------------------------------------------------ Medien prüfen --- */
    if (!empty($opt['medien'])) {
        $docroot = rtrim((string) $opt['medien'], '/');
        ws_durchlaufen($alles + ['angebote' => $angebote, 'team' => $team], static function (string $k, mixed $v, string $pfad) use (&$w, $docroot): void {
            if (is_string($v) && str_starts_with($v, 'medien/') && !is_file($docroot . '/' . $v)) {
                $w[] = "Die Datei $v ($pfad) fehlt in der Medienverwaltung.";
            }
        });
    }

    $inhalt = [
        'stamm' => $stamm,
        'allgemein' => $allgemein,
        'sektionen' => $sektionen,
        'angebote' => $angebote,
        'team' => $team,
        'preise' => ws_modul('preise') ? $d['preise'] : [],
        'zeiten' => ws_modul('zeiten') ? $d['zeiten'] : [],
        'formular' => $formularAn ? $formular : ['modus' => 'aus'],
        'hinweise' => $hinweise,
        'recht' => $recht,
        'design' => ws_text($allgemein['design'] ?? '') ?: 'aurora',
    ];
    return [$inhalt, array_values(array_unique($w))];
}

/* ===================================================== HTML-Seiten === */

/** Kontext beim Zeichnen einer Seite. */
function ws_kontext(?array $neu = null): array
{
    static $k = ['basis' => '', 'vorschau' => false, 'docroot' => '', 'inhalt' => [], 'theme' => null, 'seite' => 'start'];
    if ($neu !== null) {
        $k = $neu + $k;
    }
    return $k;
}

/** Adresse einer Mediendatei, mit Versionskennung gegen alte Fassungen im Zwischenspeicher. */
function ws_medium(string $pfad): string
{
    $pfad = ws_text($pfad);
    if ($pfad === '' || !str_starts_with($pfad, 'medien/') || str_contains($pfad, '..')) {
        return '';
    }
    $k = ws_kontext();
    $v = $k['docroot'] !== '' && is_file($k['docroot'] . '/' . $pfad) ? '?v=' . substr(md5((string) filemtime($k['docroot'] . '/' . $pfad)), 0, 8) : '';
    return $k['basis'] . implode('/', array_map('rawurlencode', explode('/', $pfad))) . $v;
}

function ws_bild(string $pfad, string $alt, string $klasse = '', string $laden = 'lazy'): string
{
    $url = ws_medium($pfad);
    if ($url === '') {
        return '';
    }
    return '<img src="' . ws_e($url) . '" alt="' . ws_e($alt) . '"' . ($klasse !== '' ? ' class="' . $klasse . '"' : '')
        . ' loading="' . $laden . '" decoding="async">';
}

function ws_knopf(mixed $k, string $klasse = 'ws-knopf'): string
{
    if (!is_array($k)) {
        return '';
    }
    $text = ws_text($k['text'] ?? '');
    $ziel = ws_ziel(ws_text($k['ziel'] ?? ''), ws_kontext()['basis']);
    if ($text === '' || $ziel === '') {
        return '';
    }
    return '<a class="' . $klasse . '" href="' . ws_e($ziel) . '"' . ws_extern($ziel) . '>' . ws_e($text) . '</a>';
}

function ws_knoepfe(mixed ...$knoepfe): string
{
    $html = '';
    foreach ($knoepfe as $i => $k) {
        $html .= ws_knopf($k, $i === 0 ? 'ws-knopf' : 'ws-knopf ws-knopf--zweit');
    }
    return $html !== '' ? '<div class="ws-knopfzeile">' . $html . '</div>' : '';
}

/** Überschriftenblock einer Sektion. */
function ws_kopf(array $s, bool $mitte = false): string
{
    $eyebrow = ws_text($s['eyebrow'] ?? '');
    $titel = ws_text($s['titel'] ?? '');
    $leise = ws_text($s['titelLeise'] ?? '');
    $lead = ws_text($s['lead'] ?? '');
    if ($eyebrow . $titel . $leise . $lead === '') {
        return '';
    }
    return '<div class="ws-kopfblock' . ($mitte ? ' ws-kopfblock--mitte' : '') . '">'
        . ($eyebrow !== '' ? '<p class="ws-eyebrow">' . ws_e($eyebrow) . '</p>' : '')
        . ($titel . $leise !== '' ? '<h2 class="ws-titel">' . ws_zeilen($titel) . ($leise !== '' ? ' <span class="ws-titel-leise">' . ws_e($leise) . '</span>' : '') . '</h2>' : '')
        . ($lead !== '' ? '<p class="ws-lead">' . ws_zeilen($lead) . '</p>' : '')
        . '</div>';
}

/** Absatz mit den Sonderformen LINK: und LUECKE:. */
function ws_absatz(string $text): string
{
    $k = ws_kontext();
    if (str_starts_with($text, 'LUECKE:')) {
        return $k['vorschau'] ? '<p class="ws-luecke"><strong>Offen:</strong> ' . ws_zeilen(trim(substr($text, 7))) . '</p>' : '';
    }
    if (str_starts_with($text, 'LINK:')) {
        [$label, $ziel] = array_pad(array_map('trim', explode('|', substr($text, 5), 2)), 2, '');
        $url = ws_ziel($ziel, $k['basis']);
        return $url !== '' ? '<p><a class="ws-verweis" href="' . ws_e($url) . '"' . ws_extern($url) . '>' . ws_e($label ?: $ziel) . ' →</a></p>' : '';
    }
    return '<p>' . ws_zeilen($text) . '</p>';
}

function ws_absaetze(mixed $liste): string
{
    $html = '';
    foreach (is_array($liste) ? $liste : [] as $a) {
        $html .= ws_absatz((string) $a);
    }
    return $html;
}

function ws_initialen(string $name): string
{
    $teile = preg_split('/\s+/', trim($name)) ?: [];
    $ini = mb_substr($teile[0] ?? '', 0, 1) . (count($teile) > 1 ? mb_substr((string) end($teile), 0, 1) : '');
    return mb_strtoupper($ini);
}

/** Rahmen jeder Sektion. */
function ws_sektion_rahmen(array $s, string $inhalt, string $zusatzKlasse = ''): string
{
    $typ = (string) ($s['typ'] ?? '');
    $farbe = in_array($s['farbe'] ?? '', ['standard', 'flaeche', 'akzent'], true) ? $s['farbe'] : 'standard';
    $anker = ws_text($s['anker'] ?? '');
    return '<section class="ws-sektion ws-sektion--' . ws_e($typ) . ' ws-farbe--' . $farbe . ($zusatzKlasse !== '' ? ' ' . $zusatzKlasse : '') . '"'
        . ($anker !== '' ? ' id="' . ws_e($anker) . '"' : '') . '><div class="ws-rahmen">' . $inhalt . '</div></section>' . "\n";
}

/* ------------------------------------------------------ Sektionen --- */

function ws_sektion_hero(array $s, array $inhalt): string
{
    $medium = in_array($s['medium'] ?? '', ['verlauf', 'ruhig', 'video', 'bild'], true) ? $s['medium'] : 'verlauf';
    $zeilen = array_values(array_filter(array_map('ws_text', (array) ($s['zeilen'] ?? []))));
    $titel = '';
    foreach ($zeilen as $i => $z) {
        $titel .= '<span class="ws-hero-zeile' . ($i > 0 ? ' ws-hero-zeile--leise' : '') . '">' . ws_e($z) . '</span>';
    }

    $news = '';
    if (!empty($s['news']['zeigen']) && ws_text($s['news']['titel'] ?? '') !== '') {
        $ziel = ws_ziel(ws_text($s['news']['ziel'] ?? ''), ws_kontext()['basis']);
        $innen = (ws_text($s['news']['eyebrow'] ?? '') !== '' ? '<span class="ws-eyebrow">' . ws_e($s['news']['eyebrow']) . '</span>' : '')
            . '<span class="ws-hero-news-text">' . ws_zeilen($s['news']['titel']) . '</span>';
        $news = $ziel !== ''
            ? '<a class="ws-hero-news" href="' . ws_e($ziel) . '"' . ws_extern($ziel) . '>' . $innen . '<span aria-hidden="true">→</span></a>'
            : '<div class="ws-hero-news">' . $innen . '</div>';
    }

    $s['farbe'] = 'standard';
    return ws_sektion_rahmen($s,
        '<div class="ws-hero-inhalt">'
        . (ws_text($s['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($s['eyebrow']) . '</p>' : '')
        . ($titel !== '' ? '<h1 class="ws-hero-titel">' . $titel . '</h1>' : '')
        . (ws_text($s['lead'] ?? '') !== '' ? '<p class="ws-lead ws-hero-lead">' . ws_zeilen($s['lead']) . '</p>' : '')
        . ws_knoepfe($s['knopf1'] ?? null, $s['knopf2'] ?? null)
        . '</div>' . $news,
        'ws-hero--' . $medium);
}

/** Der Hintergrund des Kopfbereichs liegt außerhalb des Rahmens — nachträglich einsetzen. */
function ws_hero_mit_hintergrund(array $s, array $inhalt): string
{
    $html = ws_sektion_hero($s, $inhalt);
    $medium = in_array($s['medium'] ?? '', ['bild', 'video'], true) ? $s['medium'] : '';
    if ($medium === '') {
        return $html;
    }
    $abdunkeln = max(0, min(90, (int) ($s['abdunkeln'] ?? 40))) / 100;
    if ($medium === 'bild') {
        $hinten = '<div class="ws-hero-medium">' . ws_bild((string) $s['bild'], '', 'ws-hero-bild', 'eager') . '</div>';
    } else {
        $poster = ws_medium((string) ($s['bild'] ?? ''));
        $hinten = '<div class="ws-hero-medium"><video class="ws-hero-video" src="' . ws_e(ws_medium((string) $s['video'])) . '"'
            . ($poster !== '' ? ' poster="' . ws_e($poster) . '"' : '') . ' autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video></div>'
            . '<button type="button" class="ws-hero-pause" data-video-pause aria-label="Hintergrundvideo anhalten">❚❚</button>';
    }
    $hinten .= '<div class="ws-hero-schleier" style="opacity:' . $abdunkeln . '"></div>';
    return preg_replace('~<div class="ws-rahmen">~', $hinten . '<div class="ws-rahmen">', $html, 1) ?? $html;
}

function ws_sektion_laufband(array $s, array $inhalt): string
{
    $begriffe = array_values(array_filter(array_map('ws_text', (array) ($s['begriffe'] ?? []))));
    if (!$begriffe) {
        return '';
    }
    $reihe = '';
    foreach ($begriffe as $b) {
        $reihe .= '<span class="ws-laufband-begriff">' . ws_e($b) . '</span><span class="ws-laufband-punkt" aria-hidden="true">◆</span>';
    }
    $s['farbe'] = 'flaeche';
    return ws_sektion_rahmen($s, '<p class="ws-nur-leser">' . ws_e(implode(' · ', $begriffe)) . '</p>'
        . '<div class="ws-laufband" aria-hidden="true"><div class="ws-laufband-spur">' . $reihe . $reihe . '</div></div>');
}

function ws_sektion_ueber_uns(array $s, array $inhalt): string
{
    $werte = '';
    foreach ((array) ($s['werte'] ?? []) as $v) {
        if (ws_text($v['titel'] ?? '') === '') {
            continue;
        }
        $werte .= '<div class="ws-karte ws-karte--klein"><h3>' . ws_e($v['titel']) . '</h3>' . (ws_text($v['text'] ?? '') !== '' ? '<p>' . ws_zeilen($v['text']) . '</p>' : '') . '</div>';
    }
    $bild = ws_bild((string) ($s['bild'] ?? ''), ws_text($s['bildAlt'] ?? ''), 'ws-rundbild');
    return ws_sektion_rahmen($s,
        '<div class="ws-zwei' . ($bild === '' ? ' ws-zwei--ohne-bild' : '') . '"><div>'
        . (ws_text($s['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($s['eyebrow']) . '</p>' : '')
        . (ws_text($s['statement'] ?? '') !== '' ? '<p class="ws-statement">' . ws_zeilen($s['statement']) . '</p>' : '')
        . '<div class="ws-fliesstext">' . ws_absaetze($s['absaetze'] ?? []) . '</div>'
        . ws_knoepfe($s['knopf'] ?? null)
        . '</div>' . ($bild !== '' ? '<div>' . $bild . '</div>' : '') . '</div>'
        . ($werte !== '' ? '<div class="ws-raster ws-raster--4 ws-abstand">' . $werte . '</div>' : ''));
}

function ws_sektion_angebote(array $s, array $inhalt): string
{
    $liste = $inhalt['angebote'];
    if (!$liste) {
        return '';
    }
    $karten = '';
    $alsListe = ($s['darstellung'] ?? 'karten') === 'liste';
    foreach ($liste as $a) {
        $meta = array_filter([ws_text($a['dauer'] ?? ''), ws_text($a['zusatz'] ?? '')]);
        $punkte = '';
        foreach ((array) ($a['punkte'] ?? []) as $p) {
            if (ws_text($p) !== '') {
                $punkte .= '<li>' . ws_e(ws_text($p)) . '</li>';
            }
        }
        $innen = (ws_text($a['text'] ?? '') !== '' ? '<p>' . ws_zeilen($a['text']) . '</p>' : '')
            . ($punkte !== '' ? '<ul class="ws-haken">' . $punkte . '</ul>' : '')
            . ($meta ? '<p class="ws-meta">' . ws_e(implode(' · ', $meta)) . '</p>' : '');
        if ($alsListe) {
            $karten .= '<details class="ws-aufklapp" id="angebot-' . ws_e($a['id']) . '"><summary><span>' . ws_e($a['titel'])
                . (ws_text($a['kurz'] ?? '') !== '' ? '<small>' . ws_e($a['kurz']) . '</small>' : '') . '</span></summary><div class="ws-aufklapp-inhalt">' . $innen . '</div></details>';
        } else {
            $karten .= '<article class="ws-karte" id="angebot-' . ws_e($a['id']) . '">'
                . ws_bild((string) ($a['bild'] ?? ''), ws_text($a['bildAlt'] ?? ''), 'ws-karte-bild')
                . '<h3>' . ws_e($a['titel']) . '</h3>'
                . (ws_text($a['kurz'] ?? '') !== '' ? '<p class="ws-karte-kurz">' . ws_e($a['kurz']) . '</p>' : '')
                . $innen . '</article>';
        }
    }
    return ws_sektion_rahmen($s, ws_kopf($s)
        . ($alsListe ? '<div class="ws-aufklappliste">' . $karten . '</div>' : '<div class="ws-raster ws-raster--3">' . $karten . '</div>')
        . ws_knoepfe($s['knopf1'] ?? null, $s['knopf2'] ?? null));
}

function ws_sektion_team(array $s, array $inhalt): string
{
    if (!$inhalt['team']) {
        return '';
    }
    $karten = '';
    foreach ($inhalt['team'] as $p) {
        $bild = ws_bild((string) ($p['bild'] ?? ''), ws_text($p['bildAlt'] ?? '') ?: 'Porträt von ' . ws_text($p['name']), 'ws-person-bild');
        $meta = array_filter([ws_text($p['schwerpunkt'] ?? ''), ws_text($p['seit'] ?? '')]);
        $karten .= '<article class="ws-karte ws-person">'
            . ($bild !== '' ? $bild : '<div class="ws-person-initialen" aria-hidden="true">' . ws_e(ws_initialen((string) $p['name'])) . '</div>')
            . '<h3>' . ws_e($p['name']) . '</h3>'
            . (ws_text($p['rolle'] ?? '') !== '' ? '<p class="ws-karte-kurz">' . ws_e($p['rolle']) . '</p>' : '')
            . ($meta ? '<p class="ws-meta">' . ws_e(implode(' · ', $meta)) . '</p>' : '')
            . (ws_text($p['zitat'] ?? '') !== '' ? '<blockquote class="ws-person-zitat">' . ws_zeilen($p['zitat']) . '</blockquote>' : '')
            . '</article>';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<div class="ws-raster ws-raster--3">' . $karten . '</div>');
}

function ws_sektion_zahlen(array $s, array $inhalt): string
{
    $zahlen = '';
    foreach ((array) ($s['eintraege'] ?? []) as $z) {
        if (ws_text($z['label'] ?? '') === '') {
            continue;
        }
        $wert = (int) ($z['wert'] ?? 0);
        $zahlen .= '<div class="ws-zahl"><p class="ws-zahl-wert"><span data-zaehlen="' . $wert . '">' . number_format($wert, 0, ',', '.') . '</span>'
            . ws_e(ws_text($z['suffix'] ?? '')) . (ws_text($z['einheit'] ?? '') !== '' ? ' <small>' . ws_e($z['einheit']) . '</small>' : '') . '</p>'
            . '<p class="ws-zahl-label">' . ws_e($z['label']) . '</p>'
            . (ws_text($z['text'] ?? '') !== '' ? '<p class="ws-meta">' . ws_e($z['text']) . '</p>' : '') . '</div>';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<div class="ws-raster ws-raster--4">' . $zahlen . '</div>');
}

function ws_sektion_preise(array $s, array $inhalt): string
{
    $p = $inhalt['preise'];
    $karten = '';
    foreach ((array) ($p['hervorgehoben'] ?? []) as $k) {
        if (ws_text($k['titel'] ?? '') === '') {
            continue;
        }
        $variante = in_array($k['variante'] ?? '', ['standard', 'akzent', 'umriss'], true) ? $k['variante'] : 'standard';
        $karten .= '<article class="ws-karte ws-preiskarte ws-preiskarte--' . $variante . '">'
            . (ws_text($k['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($k['eyebrow']) . '</p>' : '')
            . '<h3>' . ws_e($k['titel']) . '</h3>'
            . (ws_text($k['preis'] ?? '') !== '' ? '<p class="ws-preis">' . ws_e($k['preis']) . '</p>' : '')
            . (ws_text($k['detail'] ?? '') !== '' ? '<p>' . ws_zeilen($k['detail']) . '</p>' : '')
            . ($variante === 'akzent' ? ws_knopf($s['knopf'] ?? null, 'ws-knopf ws-knopf--invers') : '')
            . '</article>';
    }
    $tabellen = '';
    foreach ((array) ($p['gruppen'] ?? []) as $g) {
        $zeilen = '';
        foreach ((array) ($g['positionen'] ?? []) as $pos) {
            if (ws_text($pos['name'] ?? '') === '') {
                continue;
            }
            $zeilen .= '<tr><th scope="row">' . ws_e($pos['name']) . (ws_text($pos['meta'] ?? '') !== '' ? ' <small>' . ws_e($pos['meta']) . '</small>' : '')
                . '</th><td>' . ws_e(ws_text($pos['preis'] ?? '')) . '</td></tr>';
        }
        if ($zeilen !== '') {
            $tabellen .= '<div class="ws-preisgruppe"><h3>' . ws_e(ws_text($g['titel'] ?? '')) . '</h3><table class="ws-preistabelle">' . $zeilen . '</table></div>';
        }
    }
    if ($karten . $tabellen === '') {
        return '';
    }
    return ws_sektion_rahmen($s, ws_kopf($s)
        . ($karten !== '' ? '<div class="ws-raster ws-raster--3">' . $karten . '</div>' : '')
        . ($tabellen !== '' ? '<div class="ws-preisliste">' . $tabellen . '</div>' : '')
        . (ws_text($p['hinweis'] ?? '') !== '' ? '<p class="ws-meta ws-abstand">' . ws_e($p['hinweis']) . '</p>' : ''));
}

function ws_sektion_faq(array $s, array $inhalt): string
{
    $fragen = '';
    foreach ((array) ($s['fragen'] ?? []) as $f) {
        if (ws_text($f['frage'] ?? '') === '') {
            continue;
        }
        $fragen .= '<details class="ws-aufklapp"><summary><span>' . ws_e($f['frage']) . '</span></summary><div class="ws-aufklapp-inhalt"><p>' . ws_zeilen($f['antwort'] ?? '') . '</p></div></details>';
    }
    return ws_sektion_rahmen($s, '<div class="ws-zwei ws-zwei--schmal-breit">' . ws_kopf($s) . '<div class="ws-aufklappliste">' . $fragen . '</div></div>');
}

function ws_zeitentabelle(array $liste): string
{
    $zeilen = '';
    foreach ($liste as $z) {
        if (ws_text($z['tage'] ?? '') !== '') {
            $zeilen .= '<tr><th scope="row">' . ws_e($z['tage']) . '</th><td>' . ws_e(ws_text($z['zeit'] ?? '')) . '</td></tr>';
        }
    }
    return $zeilen !== '' ? '<table class="ws-zeiten">' . $zeilen . '</table>' : '';
}

function ws_maps_link(array $stamm): string
{
    $k = $stamm['kontakt'] ?? [];
    $adresse = trim(ws_text($k['strasse'] ?? '') . ', ' . ws_text($k['plz'] ?? '') . ' ' . ws_text($k['ort'] ?? ''), ', ');
    return $adresse !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(ws_text($stamm['name'] ?? '') . ', ' . $adresse) : '';
}

function ws_anschrift(array $stamm): string
{
    $k = $stamm['kontakt'] ?? [];
    $zeilen = array_filter([ws_text($k['strasse'] ?? ''), trim(ws_text($k['plz'] ?? '') . ' ' . ws_text($k['ort'] ?? ''))]);
    if (!$zeilen) {
        return '';
    }
    $text = implode('<br>', array_map('ws_e', $zeilen));
    $link = !empty($stamm['karte']) ? ws_maps_link($stamm) : '';
    return $link !== '' ? '<a href="' . ws_e($link) . '" target="_blank" rel="noopener">' . $text . '</a>' : $text;
}

function ws_formular(array $inhalt): string
{
    $f = $inhalt['formular'];
    $modus = $f['modus'] ?? 'aus';
    if ($modus === 'aus') {
        return '';
    }
    $k = ws_kontext();
    $stamm = $inhalt['stamm'];
    $empfaenger = ws_text($f['empfaenger'] ?? '') ?: ws_text($stamm['kontakt']['email'] ?? '');
    $id = static fn(string $n): string => 'ws-f-' . $n;

    $auswahl = '';
    if (ws_text($f['auswahlLabel'] ?? '') !== '' && $inhalt['angebote']) {
        $optionen = '<option value="">Bitte wählen</option>';
        foreach ($inhalt['angebote'] as $a) {
            $optionen .= '<option value="' . ws_e($a['titel']) . '">' . ws_e($a['titel']) . '</option>';
        }
        if (ws_text($f['beratungLabel'] ?? '') !== '') {
            $optionen .= '<option value="' . ws_e($f['beratungLabel']) . '">' . ws_e($f['beratungLabel']) . '</option>';
        }
        $auswahl = '<div class="ws-feld"><label for="' . $id('angebot') . '">' . ws_e($f['auswahlLabel']) . '</label>'
            . '<select id="' . $id('angebot') . '" name="angebot">' . $optionen . '</select></div>';
    }
    $zeiten = '';
    $zeitListe = array_values(array_filter(array_map('ws_text', (array) ($f['zeiten'] ?? []))));
    if ($zeitListe) {
        $zeiten = '<fieldset class="ws-feld ws-chips"><legend>Wann passt es Ihnen?</legend>';
        foreach ($zeitListe as $i => $z) {
            $zeiten .= '<label class="ws-chip"><input type="radio" name="zeit" value="' . ws_e($z) . '"' . ($i === count($zeitListe) - 1 ? ' checked' : '') . '><span>' . ws_e($z) . '</span></label>';
        }
        $zeiten .= '</fieldset>';
    }

    return '<form class="ws-formular" data-formular data-modus="' . ws_e($modus) . '" data-ziel="' . ws_e($k['basis'] . 'anfrage.php') . '"'
        . ' data-empfaenger="' . ws_e($empfaenger) . '" data-betreff="' . ws_e(ws_text($f['betreff'] ?? '') ?: 'Anfrage über die Webseite') . '"'
        . ($k['vorschau'] ? ' data-vorschau' : '') . ' novalidate>'
        . (ws_text($f['ueberschrift'] ?? '') !== '' ? '<h3>' . ws_e($f['ueberschrift']) . '</h3>' : '')
        . (ws_text($f['hinweis'] ?? '') !== '' ? '<p class="ws-meta">' . ws_zeilen($f['hinweis']) . '</p>' : '')
        . '<div class="ws-feld"><label for="' . $id('name') . '">Name *</label><input id="' . $id('name') . '" name="name" autocomplete="name" required></div>'
        . '<div class="ws-feldpaar"><div class="ws-feld"><label for="' . $id('telefon') . '">Telefon</label><input id="' . $id('telefon') . '" name="telefon" type="tel" autocomplete="tel"></div>'
        . '<div class="ws-feld"><label for="' . $id('email') . '">E-Mail</label><input id="' . $id('email') . '" name="email" type="email" autocomplete="email"></div></div>'
        . '<p class="ws-meta ws-feld-hinweis">Telefon oder E-Mail — eins von beiden genügt.</p>'
        . $auswahl . $zeiten
        . '<div class="ws-feld"><label for="' . $id('nachricht') . '">Nachricht</label><textarea id="' . $id('nachricht') . '" name="nachricht" rows="4" placeholder="' . ws_e(ws_text($f['nachrichtPlatzhalter'] ?? '')) . '"></textarea></div>'
        . '<div class="ws-honig" aria-hidden="true"><label>Webseite <input name="webseite" tabindex="-1" autocomplete="off"></label></div>'
        . '<label class="ws-einwilligung"><input type="checkbox" name="einwilligung" value="1" required><span>' . ws_zeilen($f['einwilligung'] ?? 'Ich stimme zu, dass meine Angaben zur Bearbeitung meiner Anfrage verwendet werden.')
        . (ws_text($f['einwilligungZusatz'] ?? '') !== '' ? ' <a href="' . ws_e($k['basis'] . 'datenschutz/') . '">' . ws_e($f['einwilligungZusatz']) . '</a>' : '') . '</span></label>'
        . '<p class="ws-formular-status" data-status role="status"></p>'
        . '<button type="submit" class="ws-knopf">' . ws_e(ws_text($f['knopfText'] ?? '') ?: 'Senden') . '</button>'
        . '<div class="ws-formular-erfolg" data-erfolg hidden><h3>' . ws_e(ws_text($f['erfolgTitel'] ?? '') ?: 'Vielen Dank!') . '</h3><p>' . ws_zeilen($f['erfolgText'] ?? '') . '</p></div>'
        . '</form>';
}

function ws_sektion_kontakt(array $s, array $inhalt): string
{
    $stamm = $inhalt['stamm'];
    $k = $stamm['kontakt'] ?? [];
    $z = $inhalt['zeiten'];
    $infos = '';
    $anschrift = ws_anschrift($stamm);
    if ($anschrift !== '') {
        $infos .= '<div class="ws-info"><p class="ws-info-label">Anschrift</p><p>' . $anschrift . '</p></div>';
    }
    if (ws_text($k['telefon'] ?? '') !== '') {
        $tel = ws_text($k['telefonLink'] ?? '') ?: preg_replace('/[^0-9+]/', '', (string) $k['telefon']);
        $infos .= '<div class="ws-info"><p class="ws-info-label">Telefon</p><p><a href="tel:' . ws_e($tel) . '">' . ws_e($k['telefon']) . '</a></p></div>';
    }
    if (ws_text($k['email'] ?? '') !== '') {
        $infos .= '<div class="ws-info"><p class="ws-info-label">E-Mail</p><p><a href="mailto:' . ws_e($k['email']) . '">' . ws_e($k['email']) . '</a></p></div>';
    }
    if ($z) {
        $tab = ws_zeitentabelle((array) ($z['zeiten'] ?? []));
        if ($tab !== '') {
            // Eigener Sprungpunkt, damit ein Menüpunkt direkt zu den Zeiten führt
            $infos .= '<div class="ws-info" id="zeiten"><p class="ws-info-label">' . ws_e(ws_projekt()['begriffe']['zeiten'] ?? 'Öffnungszeiten') . '</p>' . $tab
                . (ws_text($z['hinweis'] ?? '') !== '' ? '<p class="ws-meta">' . ws_e($z['hinweis']) . '</p>' : '') . '</div>';
        }
        $tel = ws_zeitentabelle((array) ($z['telefonzeiten'] ?? []));
        if ($tel !== '') {
            $infos .= '<div class="ws-info"><p class="ws-info-label">Telefonisch erreichbar</p>' . $tel
                . (ws_text($z['telefonHinweis'] ?? '') !== '' ? '<p class="ws-meta">' . ws_e($z['telefonHinweis']) . '</p>' : '') . '</div>';
        }
    }
    $formular = ws_formular($inhalt);
    return ws_sektion_rahmen($s, ws_kopf($s)
        . '<div class="ws-zwei ws-zwei--kontakt' . ($formular === '' ? ' ws-zwei--ohne-bild' : '') . '"><div class="ws-infos">' . $infos . '</div>'
        . ($formular !== '' ? '<div class="ws-karte">' . $formular . '</div>' : '') . '</div>');
}

function ws_sektion_anfahrt(array $s, array $inhalt): string
{
    $stamm = $inhalt['stamm'];
    $k = $stamm['kontakt'] ?? [];
    $adresse = ws_text($s['adresse'] ?? '') ?: trim(ws_text($k['strasse'] ?? '') . ', ' . ws_text($k['plz'] ?? '') . ' ' . ws_text($k['ort'] ?? ''), ', ');
    $zoom = max(3, min(20, (int) ($s['zoom'] ?? 16)));
    $karte = '';
    if ($adresse !== '') {
        $url = 'https://www.google.com/maps?q=' . rawurlencode($adresse) . '&z=' . $zoom . '&output=embed';
        $karte = ($s['laden'] ?? 'klick') === 'sofort'
            ? '<iframe class="ws-karte-rahmen" src="' . ws_e($url) . '" title="Karte: ' . ws_e($adresse) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>'
            : '<div class="ws-karte-rahmen ws-karte-platzhalter" data-karte="' . ws_e($url) . '" data-titel="Karte: ' . ws_e($adresse) . '">'
              . '<p>Die Karte wird von Google geladen. Dabei erfährt Google Ihre IP-Adresse.</p>'
              . '<button type="button" class="ws-knopf ws-knopf--zweit" data-karte-laden>Karte laden</button>'
              . '<a class="ws-verweis" href="' . ws_e(ws_maps_link($stamm) ?: $url) . '" target="_blank" rel="noopener">In Google Maps öffnen ↗</a></div>';
    }
    $punkte = array_values(array_filter(array_map('ws_text', (array) ($s['punkte'] ?? []))));
    if (!$punkte) {
        $punkte = array_values(array_filter(array_map('ws_text', (array) ($inhalt['zeiten']['anfahrt'] ?? []))));
    }
    $liste = $punkte ? '<ul class="ws-haken">' . implode('', array_map(static fn($p) => '<li>' . ws_e($p) . '</li>', $punkte)) . '</ul>' : '';
    $anschrift = ws_anschrift($stamm);
    return ws_sektion_rahmen($s, ws_kopf($s)
        . '<div class="ws-zwei ws-zwei--breit-schmal">' . ($karte !== '' ? '<div>' . $karte . '</div>' : '')
        . '<div class="ws-infos">' . ($anschrift !== '' ? '<div class="ws-info"><p class="ws-info-label">Adresse</p><p>' . $anschrift . '</p></div>' : '')
        . ($liste !== '' ? '<div class="ws-info"><p class="ws-info-label">Gut zu wissen</p>' . $liste . '</div>' : '') . '</div></div>');
}

function ws_sektion_text_bild(array $s, array $inhalt): string
{
    $bild = ws_bild((string) ($s['bild'] ?? ''), ws_text($s['bildAlt'] ?? ''), 'ws-rundbild');
    $links = ($s['bildSeite'] ?? 'rechts') === 'links';
    $text = '<div>' . ws_kopf($s) . '<div class="ws-fliesstext">' . ws_absaetze($s['absaetze'] ?? []) . '</div>' . ws_knoepfe($s['knopf'] ?? null) . '</div>';
    return ws_sektion_rahmen($s, '<div class="ws-zwei' . ($bild === '' ? ' ws-zwei--ohne-bild' : '') . ($links ? ' ws-zwei--bild-links' : '') . '">'
        . $text . ($bild !== '' ? '<div>' . $bild . '</div>' : '') . '</div>');
}

function ws_sektion_karten(array $s, array $inhalt): string
{
    $spalten = in_array((string) ($s['spalten'] ?? '3'), ['2', '3', '4'], true) ? (string) $s['spalten'] : '3';
    $karten = '';
    foreach ((array) ($s['karten'] ?? []) as $k) {
        if (ws_text($k['titel'] ?? '') === '') {
            continue;
        }
        $link = ws_knopf($k['link'] ?? null, 'ws-verweis');
        $karten .= '<article class="ws-karte">' . ws_bild((string) ($k['bild'] ?? ''), ws_text($k['bildAlt'] ?? ''), 'ws-karte-bild')
            . (ws_text($k['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($k['eyebrow']) . '</p>' : '')
            . '<h3>' . ws_e($k['titel']) . '</h3>' . (ws_text($k['text'] ?? '') !== '' ? '<p>' . ws_zeilen($k['text']) . '</p>' : '')
            . ($link !== '' ? '<p>' . $link . '</p>' : '') . '</article>';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<div class="ws-raster ws-raster--' . $spalten . '">' . $karten . '</div>');
}

function ws_sektion_schritte(array $s, array $inhalt): string
{
    $schritte = '';
    $nr = 0;
    foreach ((array) ($s['schritte'] ?? []) as $st) {
        if (ws_text($st['titel'] ?? '') === '') {
            continue;
        }
        $nr++;
        $schritte .= '<li class="ws-schritt"><span class="ws-schritt-nr" aria-hidden="true">' . str_pad((string) $nr, 2, '0', STR_PAD_LEFT) . '</span>'
            . '<h3>' . ws_e($st['titel']) . '</h3>' . (ws_text($st['text'] ?? '') !== '' ? '<p>' . ws_zeilen($st['text']) . '</p>' : '') . '</li>';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<ol class="ws-schritte">' . $schritte . '</ol>');
}

function ws_sektion_stimmen(array $s, array $inhalt): string
{
    $stimmen = '';
    foreach ((array) ($s['stimmen'] ?? []) as $st) {
        if (ws_text($st['zitat'] ?? '') === '') {
            continue;
        }
        $stimmen .= '<figure class="ws-karte ws-stimme"><blockquote>' . ws_zeilen($st['zitat']) . '</blockquote>'
            . '<figcaption><strong>' . ws_e(ws_text($st['name'] ?? '')) . '</strong>' . (ws_text($st['zusatz'] ?? '') !== '' ? ' · ' . ws_e($st['zusatz']) : '') . '</figcaption></figure>';
    }
    if ($stimmen === '') {
        return '';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<div class="ws-raster ws-raster--3">' . $stimmen . '</div>');
}

function ws_sektion_galerie(array $s, array $inhalt): string
{
    $bilder = '';
    foreach ((array) ($s['bilder'] ?? []) as $b) {
        $img = ws_bild((string) ($b['bild'] ?? ''), ws_text($b['alt'] ?? ''));
        if ($img === '') {
            continue;
        }
        $bilder .= '<figure class="ws-galerie-bild">' . $img . (ws_text($b['beschriftung'] ?? '') !== '' ? '<figcaption>' . ws_e($b['beschriftung']) . '</figcaption>' : '') . '</figure>';
    }
    if ($bilder === '') {
        return '';
    }
    return ws_sektion_rahmen($s, ws_kopf($s) . '<div class="ws-galerie">' . $bilder . '</div>');
}

function ws_sektion_video(array $s, array $inhalt): string
{
    $url = ws_medium((string) ($s['video'] ?? ''));
    if ($url === '') {
        return '';
    }
    $poster = ws_medium((string) ($s['poster'] ?? ''));
    $schleife = ($s['wiedergabe'] ?? '') === 'schleife';
    return ws_sektion_rahmen($s, ws_kopf($s)
        . '<div class="ws-video"><video src="' . ws_e($url) . '"' . ($poster !== '' ? ' poster="' . ws_e($poster) . '"' : '')
        . ($schleife ? ' autoplay muted loop playsinline' : ' controls') . ' preload="metadata"></video></div>'
        . (ws_text($s['beschreibung'] ?? '') !== '' ? '<details class="ws-aufklapp ws-abstand"><summary><span>Beschreibung des Videos</span></summary><div class="ws-aufklapp-inhalt"><p>' . ws_zeilen($s['beschreibung']) . '</p></div></details>' : ''));
}

function ws_sektion_aufruf(array $s, array $inhalt): string
{
    return ws_sektion_rahmen($s, '<div class="ws-aufruf">'
        . (ws_text($s['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($s['eyebrow']) . '</p>' : '')
        . '<h2 class="ws-titel">' . ws_zeilen($s['titel'] ?? '') . '</h2>'
        . (ws_text($s['text'] ?? '') !== '' ? '<p class="ws-lead">' . ws_zeilen($s['text']) . '</p>' : '')
        . ws_knoepfe($s['knopf1'] ?? null, $s['knopf2'] ?? null) . '</div>');
}

function ws_sektion_freitext(array $s, array $inhalt): string
{
    return ws_sektion_rahmen($s, '<div class="ws-lesebreite">' . ws_kopf($s) . '<div class="ws-fliesstext">' . ws_absaetze($s['absaetze'] ?? []) . '</div></div>');
}

/* ------------------------------------------------ Kopf und Fuß --- */

function ws_marke(array $inhalt): string
{
    $stamm = $inhalt['stamm'];
    $kopf = $inhalt['allgemein']['kopf'] ?? [];
    $k = ws_kontext();
    $name = ws_text($stamm['name'] ?? '');
    $pfad = ws_text($kopf['logo'] ?? '') ?: ws_text($stamm['logo'] ?? '');
    $groesse = in_array($kopf['logoGroesse'] ?? '', ['klein', 'mittel', 'gross'], true) ? $kopf['logoGroesse'] : 'mittel';
    $logo = ws_bild($pfad, ws_text($kopf['logoAlt'] ?? '') ?: $name, 'ws-marke-logo ws-marke-logo--' . $groesse, 'eager');
    $anzeige = $logo === '' ? 'name' : (in_array($kopf['anzeige'] ?? '', ['logo', 'beides', 'name'], true) ? $kopf['anzeige'] : 'logo');
    $text = '<span class="ws-marke-name">' . ws_e($name) . '</span>';
    return '<a class="ws-marke" href="' . ws_e($k['basis'] === '' ? '#' . ws_erster_anker($inhalt) : $k['basis']) . '"'
        . ($anzeige === 'logo' ? ' aria-label="' . ws_e($name) . ' — zur Startseite"' : '') . '>'
        . match ($anzeige) { 'logo' => $logo, 'beides' => $logo . $text, default => $text }
        . '</a>';
}

function ws_erster_anker(array $inhalt): string
{
    return ws_text($inhalt['sektionen'][0]['anker'] ?? '') ?: 'inhalt';
}

/** Sektionen, die tatsächlich etwas zeigen — leere (z. B. Galerie ohne Bilder) bekommen keinen Menüpunkt. */
/**
 * Zeigt eine Sektion etwas? Eingebaute Typen: wenn ihre Darstellung nicht leer
 * ist. Eigene Typen eines Themes: wenn das Theme eine Vorlage dafür hat.
 */
function ws_sektion_sichtbar(array $s, array $inhalt): bool
{
    $typ = (string) ($s['typ'] ?? '');
    if ($typ === 'hero') {
        return true;
    }
    if (!preg_match('/^[a-z][a-z0-9-]*$/', $typ)) {
        return false;
    }
    $f = 'ws_sektion_' . str_replace('-', '_', $typ);
    if (function_exists($f)) {
        return trim($f($s, $inhalt)) !== '';
    }
    return ws_theme_vorlage(ws_kontext()['theme'], 'sektionen/' . $typ) !== null;
}

function ws_sichtbare_sektionen(array $inhalt): array
{
    $raus = [];
    foreach ($inhalt['sektionen'] as $s) {
        if (ws_sektion_sichtbar($s, $inhalt)) {
            $raus[(string) ($s['id'] ?? '')] = true;
        }
    }
    return $raus;
}

/**
 * Menüpunkte der Kopfleiste: [label, url, anker, extern] — nur Bereiche, die
 * tatsächlich etwas zeigen, dazu frei gewählte Links aus „Webseite allgemein“.
 */
function ws_menue_eintraege(array $inhalt): array
{
    $k = ws_kontext();
    $raus = [];
    $sichtbar = ws_sichtbare_sektionen($inhalt);
    foreach ($inhalt['sektionen'] as $s) {
        if (ws_text($s['anker'] ?? '') === '' || !isset($sichtbar[(string) ($s['id'] ?? '')])) {
            continue;
        }
        // Die Zeiten stehen im Kontaktbereich, haben aber einen eigenen Menüpunkt
        if (($s['typ'] ?? '') === 'kontakt' && !empty($s['zeitenMenue']['zeigen']) && $inhalt['zeiten']
            && ws_zeitentabelle((array) ($inhalt['zeiten']['zeiten'] ?? [])) !== '') {
            $label = ws_text($s['zeitenMenue']['label'] ?? '') ?: (string) (ws_projekt()['begriffe']['zeiten'] ?? 'Öffnungszeiten');
            $raus[] = ['label' => mb_substr($label, 0, 30), 'url' => $k['basis'] . '#zeiten', 'anker' => 'zeiten', 'extern' => false];
        }
        if (empty($s['menue']['zeigen'])) {
            continue;
        }
        // Ohne eigene Beschriftung: Kleinzeile oder Überschrift der Sektion
        $label = ws_text($s['menue']['label'] ?? '') ?: (ws_text($s['eyebrow'] ?? '') ?: ws_text($s['titel'] ?? ''));
        if ($label !== '') {
            $raus[] = ['label' => mb_substr($label, 0, 30), 'url' => $k['basis'] . '#' . $s['anker'], 'anker' => (string) $s['anker'], 'extern' => false];
        }
    }
    foreach ((array) ($inhalt['allgemein']['kopf']['links'] ?? []) as $l) {
        $ziel = ws_ziel(ws_text($l['ziel'] ?? ''), $k['basis']);
        if ($ziel !== '' && ws_text($l['text'] ?? '') !== '') {
            $raus[] = ['label' => ws_text($l['text']), 'url' => $ziel, 'anker' => '', 'extern' => ws_extern($ziel) !== ''];
        }
    }
    return $raus;
}

function ws_kopfleiste(array $inhalt): string
{
    $menue = '';
    $eintraege = ws_menue_eintraege($inhalt);
    foreach ($eintraege as $e) {
        $menue .= '<li><a href="' . ws_e($e['url']) . '"' . ($e['extern'] ? ws_extern($e['url']) : '') . '>' . ws_e($e['label']) . '</a></li>';
    }
    $knopf = ws_knopf(['text' => $inhalt['allgemein']['kopf']['knopfText'] ?? '', 'ziel' => $inhalt['allgemein']['kopf']['knopfZiel'] ?? ''], 'ws-knopf ws-knopf--klein');
    $standard = '<header class="ws-kopfleiste" data-kopf><div class="ws-kopfleiste-innen">' . ws_marke($inhalt)
        . ($menue !== '' ? '<button type="button" class="ws-menueknopf" data-menueknopf aria-expanded="false" aria-controls="ws-menue"><span aria-hidden="true">☰</span><span class="ws-nur-leser">Menü</span></button>'
            . '<nav class="ws-menue" id="ws-menue" aria-label="Hauptmenü"><ul>' . $menue . '</ul></nav>' : '')
        . $knopf . '</div></header>';
    return ws_theme_teil('kopf', $inhalt, ['standard' => $standard, 'menueHtml' => $menue !== '' ? '<ul>' . $menue . '</ul>' : '',
        'knopfHtml' => $knopf], $standard);
}

/**
 * Ein Teil der Seite aus dem Theme (kopf, fuss, recht, seite) — oder die
 * eingebaute Fassung, wenn das Theme keine Vorlage mitbringt oder sie fehlerhaft ist.
 */
function ws_theme_teil(string $name, array $inhalt, array $zusatz, string $standard): string
{
    $theme = ws_kontext()['theme'];
    $vorlage = ws_theme_vorlage($theme, $name);
    if ($vorlage === null) {
        return $standard;
    }
    try {
        return ws_theme_rendern($theme, $vorlage, $zusatz + ws_theme_global($inhalt));
    } catch (Throwable $e) {
        ws_theme_warnung('vorlagen/' . $name . '.html: ' . $e->getMessage() . ' — verwendet wird die eingebaute Darstellung.');
        return $standard;
    }
}

/** Sammelt Probleme beim Rendern mit Theme-Vorlagen (für den Bericht). */
function ws_theme_warnung(?string $text = null): array
{
    static $liste = [];
    if ($text === null) {
        $raus = array_values(array_unique($liste));
        $liste = [];
        return $raus;
    }
    $liste[] = $text;
    return [];
}

/**
 * Eine Sektion der Startseite: Theme-Vorlage (Variante, dann Typ), sonst die
 * eingebaute Darstellung. Eingebaute Typen, die nichts zeigen (z. B. Galerie
 * ohne Bilder), bleiben auch im Theme leer.
 */
function ws_sektion_html(array $s, array $inhalt): string
{
    static $cache = [];
    $k = ws_kontext();
    $theme = $k['theme'];
    $schluessel = md5(json_encode($s) . $k['basis'] . ($theme['id'] ?? '') . (int) $k['vorschau']);
    if (isset($cache[$schluessel])) {
        return $cache[$schluessel];
    }
    $typ = (string) ($s['typ'] ?? '');
    if (!preg_match('/^[a-z][a-z0-9-]*$/', $typ)) {
        return '';
    }
    $f = 'ws_sektion_' . str_replace('-', '_', $typ);
    $eingebaut = function_exists($f);
    $standard = '';
    if ($typ === 'hero') {
        $standard = ws_hero_mit_hintergrund($s, $inhalt);
    } elseif ($eingebaut) {
        $standard = $f($s, $inhalt);
    }
    $variante = preg_match('/^[a-z0-9-]+$/', (string) ($s['variante'] ?? '')) ? (string) $s['variante'] : '';
    $vorlage = null;
    if ($theme !== null) {
        $vorlage = ($variante !== '' ? ws_theme_vorlage($theme, 'sektionen/' . $typ . '--' . $variante) : null)
            ?? ws_theme_vorlage($theme, 'sektionen/' . $typ);
    }
    if ($vorlage === null || ($eingebaut && trim($standard) === '')) {
        if (!$eingebaut && $vorlage === null) {
            ws_theme_warnung('Die Sektion „' . (ws_text($s['titel'] ?? '') ?: $typ) . '“ (Typ „' . $typ . '“) kann das gewählte Design nicht darstellen — sie bleibt ausgeblendet.');
        }
        return $cache[$schluessel] = $standard;
    }
    $farbe = in_array($s['farbe'] ?? '', ['standard', 'flaeche', 'akzent'], true) ? $s['farbe'] : 'standard';
    try {
        $html = ws_theme_rendern($theme, $vorlage, ws_theme_anreichern($s) + [
            'standard' => $standard,
            'variante' => $variante,
            'farbe' => $farbe,
            'ankerAttrHtml' => ws_text($s['anker'] ?? '') !== '' ? ' id="' . ws_e($s['anker']) . '"' : '',
            'klasseHtml' => 'ws-sektion ws-sektion--' . ws_e($typ) . ' ws-farbe--' . $farbe,
            'kopfblockHtml' => ws_kopf($s),
            'kopfblockMitteHtml' => ws_kopf($s, true),
        ] + ws_theme_global($inhalt));
    } catch (Throwable $e) {
        ws_theme_warnung('vorlagen/sektionen/' . $typ . '.html: ' . $e->getMessage() . ' — verwendet wird die eingebaute Darstellung.');
        $html = $standard;
    }
    return $cache[$schluessel] = trim($html) === '' ? '' : $html . "\n";
}

function ws_fusszeile(array $inhalt): string
{
    $k = ws_kontext();
    $stamm = $inhalt['stamm'];
    $a = $inhalt['allgemein'];
    $kontakt = $stamm['kontakt'] ?? [];
    $spalten = '<div class="ws-fuss-spalte"><p class="ws-fuss-name">' . ws_e(ws_text($stamm['name'] ?? '')) . '</p>'
        . (ws_text($a['fuss']['text'] ?? '') !== '' ? '<p>' . ws_zeilen($a['fuss']['text']) . '</p>' : (ws_text($stamm['beschreibung'] ?? '') !== '' ? '<p>' . ws_zeilen($stamm['beschreibung']) . '</p>' : ''))
        . '</div>';
    $kontaktHtml = ws_anschrift($stamm);
    if (ws_text($kontakt['telefon'] ?? '') !== '') {
        $tel = ws_text($kontakt['telefonLink'] ?? '') ?: preg_replace('/[^0-9+]/', '', (string) $kontakt['telefon']);
        $kontaktHtml .= ($kontaktHtml !== '' ? '<br>' : '') . '<a href="tel:' . ws_e($tel) . '">' . ws_e($kontakt['telefon']) . '</a>';
    }
    if (ws_text($kontakt['email'] ?? '') !== '') {
        $kontaktHtml .= ($kontaktHtml !== '' ? '<br>' : '') . '<a href="mailto:' . ws_e($kontakt['email']) . '">' . ws_e($kontakt['email']) . '</a>';
    }
    if ($kontaktHtml !== '') {
        $spalten .= '<div class="ws-fuss-spalte"><p class="ws-info-label">Kontakt</p><p>' . $kontaktHtml . '</p></div>';
    }
    if ($inhalt['zeiten']) {
        $tab = ws_zeitentabelle((array) ($inhalt['zeiten']['zeiten'] ?? []));
        if ($tab !== '') {
            $spalten .= '<div class="ws-fuss-spalte"><p class="ws-info-label">' . ws_e(ws_projekt()['begriffe']['zeiten'] ?? 'Öffnungszeiten') . '</p>' . $tab . '</div>';
        }
    }
    if (!empty($a['fuss']['angeboteZeigen']) && $inhalt['angebote']) {
        $liste = '';
        foreach (array_slice($inhalt['angebote'], 0, 8) as $an) {
            $liste .= '<li><a href="' . ws_e($k['basis'] . '#angebot-' . $an['id']) . '">' . ws_e($an['titel']) . '</a></li>';
        }
        $spalten .= '<div class="ws-fuss-spalte"><p class="ws-info-label">' . ws_e(ws_projekt()['begriffe']['angebote'] ?? 'Leistungen') . '</p><ul class="ws-fuss-liste">' . $liste . '</ul></div>';
    }
    $links = '';
    foreach ((array) ($stamm['social'] ?? []) as $l) {
        $url = ws_ziel(ws_text($l['url'] ?? ''), $k['basis']);
        if ($url !== '' && ws_text($l['titel'] ?? '') !== '') {
            $links .= '<a href="' . ws_e($url) . '"' . ws_extern($url) . '>' . ws_e($l['titel']) . '</a>';
        }
    }
    $recht = '<a href="' . ws_e($k['basis'] . 'impressum/') . '">Impressum</a><a href="' . ws_e($k['basis'] . 'datenschutz/') . '">Datenschutz</a>'
        . (isset($inhalt['recht']['barrierefreiheit']) ? '<a href="' . ws_e($k['basis'] . 'barrierefreiheit/') . '">Barrierefreiheit</a>' : '');
    $standard = '<footer class="ws-fuss"><div class="ws-rahmen"><div class="ws-fuss-raster">' . $spalten . '</div>'
        . '<div class="ws-fuss-unten"><p>© ' . date('Y') . ' ' . ws_e(ws_text($stamm['name'] ?? '')) . '</p>'
        . '<nav class="ws-fuss-links" aria-label="Rechtliches und Profile">' . $links . $recht . '</nav></div></div></footer>';
    return ws_theme_teil('fuss', $inhalt, ['standard' => $standard, 'rechtLinksHtml' => $recht, 'socialLinksHtml' => $links,
        'fussText' => ws_text($a['fuss']['text'] ?? '') ?: ws_text($stamm['beschreibung'] ?? '')], $standard);
}

/** Hinweise: stehen im HTML, der Browser prüft beim Aufruf den Zeitraum noch einmal. */
function ws_hinweise(array $inhalt): string
{
    $heute = date('Y-m-d');
    $html = '';
    foreach ($inhalt['hinweise'] as $h) {
        $von = (string) ($h['gueltigVon'] ?? '');
        $bis = (string) ($h['gueltigBis'] ?? '');
        $jetzt = ($von === '' || $von <= $heute) && ($bis === '' || $bis >= $heute);
        $art = in_array($h['art'] ?? '', ['urlaub', 'info', 'neu', 'wichtig'], true) ? $h['art'] : 'info';
        $knopf = ws_knopf($h['aktion'] ?? null, 'ws-knopf ws-knopf--klein');
        $attr = ' data-hinweis="' . ws_e((string) ($h['id'] ?? '')) . '" data-von="' . ws_e($von) . '" data-bis="' . ws_e($bis) . '"'
            . (!empty($h['einmalProBesuch']) ? ' data-einmal' : '');
        if (($h['darstellung'] ?? 'banner') === 'fenster') {
            $html .= '<div class="ws-fenster ws-hinweis--' . $art . '"' . $attr . ' data-verzoegerung="' . (int) ($h['verzoegerungMs'] ?? 1200) . '" role="dialog" aria-modal="true" aria-label="' . ws_e(str_replace("\n", ' ', ws_text($h['titel']))) . '" hidden>'
                . '<div class="ws-fenster-tafel">' . ws_bild((string) ($h['bild'] ?? ''), '', 'ws-fenster-bild')
                . (ws_text($h['eyebrow'] ?? '') !== '' ? '<p class="ws-eyebrow">' . ws_e($h['eyebrow']) . '</p>' : '')
                . '<h2>' . ws_zeilen($h['titel']) . '</h2>' . (ws_text($h['text'] ?? '') !== '' ? '<p>' . ws_zeilen($h['text']) . '</p>' : '')
                . '<div class="ws-knopfzeile">' . $knopf . '<button type="button" class="ws-knopf ws-knopf--zweit ws-knopf--klein" data-hinweis-zu>Schließen</button></div>'
                . '</div></div>';
        } else {
            $html .= '<div class="ws-leiste ws-hinweis--' . $art . '"' . $attr . ' role="region" aria-label="Hinweis"' . ($jetzt ? '' : ' hidden') . '>'
                . '<div class="ws-leiste-innen"><p><strong>' . ws_e(str_replace("\n", ' ', ws_text($h['titel']))) . '</strong>'
                . (ws_text($h['text'] ?? '') !== '' ? ' ' . ws_e(str_replace("\n", ' ', ws_text($h['text']))) : '') . '</p>' . $knopf
                . '<button type="button" class="ws-leiste-zu" data-hinweis-zu aria-label="Hinweis schließen">✕</button></div></div>';
        }
    }
    return $html;
}

/** Strukturierte Angaben für Suchmaschinen. */
function ws_json_ld(array $inhalt): string
{
    $stamm = $inhalt['stamm'];
    $k = $stamm['kontakt'] ?? [];
    $daten = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => ws_text($stamm['name'] ?? ''),
        'description' => ws_text($stamm['beschreibung'] ?? ''),
        'url' => rtrim(ws_text($inhalt['allgemein']['domain'] ?? ''), '/') ?: null,
        'telephone' => ws_text($k['telefonLink'] ?? '') ?: null,
        'email' => ws_text($k['email'] ?? '') ?: null,
        'address' => ws_text($k['strasse'] ?? '') !== '' ? ['@type' => 'PostalAddress', 'streetAddress' => ws_text($k['strasse']),
            'postalCode' => ws_text($k['plz'] ?? ''), 'addressLocality' => ws_text($k['ort'] ?? ''), 'addressCountry' => 'DE'] : null,
    ]);
    return '<script type="application/ld+json">' . json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/**
 * Eine vollständige Seite.
 *
 * @param string $seite  start | impressum | datenschutz | barrierefreiheit
 * @param string $basis  '' für die Startseite, '../' für Unterseiten, eine absolute Adresse für die Vorschau
 * @param array  $opt    vorschau (bool), docroot (Medien für Versionskennung), design (geladenes Design), version (Kennung für assets)
 */
function ws_seite_html(array $inhalt, string $seite, string $basis, array $opt = []): string
{
    $design = $opt['design'] ?? ws_design_laden($inhalt['design']);
    $theme = ws_theme($design);
    ws_kontext(['basis' => $basis, 'vorschau' => !empty($opt['vorschau']), 'docroot' => (string) ($opt['docroot'] ?? ''), 'inhalt' => $inhalt,
                'theme' => $theme, 'seite' => $seite]);
    $v = (string) ($opt['version'] ?? '');
    $a = $inhalt['allgemein'];
    $stamm = $inhalt['stamm'];
    $domain = rtrim(ws_text($a['domain'] ?? ''), '/');
    $seoTitel = ws_text($a['seo']['titel'] ?? '') ?: ws_text($stamm['name'] ?? '');
    $beschreibung = ws_text($a['seo']['beschreibung'] ?? '') ?: ws_text($stamm['beschreibung'] ?? '');
    $titel = $seite === 'start' ? $seoTitel : (WS_RECHTSSEITEN[$seite] ?? $seite) . ' — ' . ws_text($stamm['name'] ?? '');
    $url = $domain !== '' ? $domain . '/' . ($seite === 'start' ? '' : $seite . '/') : '';
    $vorschaubild = ws_text($a['seo']['bild'] ?? '');
    $noindex = !empty($opt['vorschau']) || empty($a['seo']['indexieren']);

    $kopf = '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
        . '<title>' . ws_e($titel) . '</title>'
        . ($beschreibung !== '' ? '<meta name="description" content="' . ws_e($beschreibung) . '">' : '')
        . ($noindex ? '<meta name="robots" content="noindex, nofollow">' : '')
        . ($url !== '' && !$noindex ? '<link rel="canonical" href="' . ws_e($url) . '">' : '')
        . '<meta name="theme-color" content="' . ws_e(ws_css_wert($design['tokens']['bg'] ?? '#0e0e16')) . '">'
        . '<meta property="og:type" content="website"><meta property="og:title" content="' . ws_e($titel) . '">'
        . ($beschreibung !== '' ? '<meta property="og:description" content="' . ws_e($beschreibung) . '">' : '')
        . ($url !== '' ? '<meta property="og:url" content="' . ws_e($url) . '">' : '')
        . ($vorschaubild !== '' && $domain !== '' ? '<meta property="og:image" content="' . ws_e($domain . '/' . $vorschaubild) . '">' : '')
        . '<link rel="stylesheet" href="' . ws_e($basis) . 'assets/design.css' . $v . '">'
        . ($theme === null || $theme['basisCss'] ? '<link rel="stylesheet" href="' . ws_e($basis) . 'assets/seite.css' . $v . '">' : '')
        . '<script src="' . ws_e($basis) . 'assets/seite.js' . $v . '" defer></script>'
        . ($seite === 'start' ? ws_json_ld($inhalt) : '');
    if ($theme !== null) {
        $themeBasis = $basis . 'assets/theme/' . $theme['id'] . '/';
        foreach ($theme['css'] as $datei) {
            $kopf .= '<link rel="stylesheet" href="' . ws_e($themeBasis . $datei) . $v . '">';
        }
        foreach ($theme['js'] as $datei) {
            $kopf .= '<script src="' . ws_e($themeBasis . $datei) . $v . '" defer></script>';
        }
        $kopf .= ws_theme_teil('kopfzusatz', $inhalt, [], '');
    }

    if ($seite === 'start') {
        $haupt = '';
        foreach ($inhalt['sektionen'] as $s) {
            $haupt .= ws_sektion_html($s, $inhalt);
        }
    } else {
        $r = $inhalt['recht'][$seite] ?? ['titel' => WS_RECHTSSEITEN[$seite] ?? $seite, 'stand' => '', 'abschnitte' => []];
        $abschnitte = '';
        foreach ($r['abschnitte'] as $ab) {
            $abschnitte .= '<section>' . ($ab['titel'] !== '' ? '<h2>' . ws_e($ab['titel']) . '</h2>' : '') . ws_absaetze($ab['absaetze']) . '</section>';
        }
        $standardRecht = '<article class="ws-sektion ws-recht"><div class="ws-rahmen ws-lesebreite">'
            . '<p class="ws-eyebrow"><a href="' . ws_e($basis) . '">← Zur Startseite</a></p>'
            . '<h1 class="ws-titel">' . ws_e($r['titel']) . '</h1>' . $abschnitte
            . ($r['stand'] !== '' ? '<p class="ws-meta ws-abstand">' . ws_e($r['stand']) . '</p>' : '')
            . '</div></article>';
        $liste = [];
        foreach ($r['abschnitte'] as $ab) {
            $liste[] = ['titel' => (string) $ab['titel'], 'absaetzeHtml' => ws_absaetze($ab['absaetze'])];
        }
        $haupt = ws_theme_teil('recht', $inhalt, ['standard' => $standardRecht, 'titel' => (string) $r['titel'], 'stand' => (string) $r['stand'],
            'abschnitte' => $liste, 'abschnitteHtml' => $abschnitte], $standardRecht);
    }

    $aurora = !empty($design['tokens']['aurora']);
    $hinweise = ws_hinweise($inhalt);
    $kopfleiste = ws_kopfleiste($inhalt);
    $fuss = ws_fusszeile($inhalt);
    $standardKoerper = '<a class="ws-sprung" href="#inhalt">Zum Inhalt springen</a>' . "\n"
        . $hinweise . "\n" . $kopfleiste . "\n"
        . '<main id="inhalt">' . "\n" . $haupt . '</main>' . "\n" . $fuss . "\n";
    $koerper = ws_theme_teil('seite', $inhalt, ['standard' => $standardKoerper, 'sprungHtml' => '<a class="ws-sprung" href="#inhalt">Zum Inhalt springen</a>',
        'hinweiseHtml' => $hinweise, 'kopfHtml' => $kopfleiste, 'hauptHtml' => $haupt, 'fussHtml' => $fuss], $standardKoerper);
    $klasse = 'ws' . ($aurora ? ' ws--aurora' : '') . ($theme !== null ? ' ws--theme ws-theme--' . $theme['id'] . ($theme['bodyKlasse'] !== '' ? ' ' . $theme['bodyKlasse'] : '') : '');
    return "<!DOCTYPE html>\n<html lang=\"de\" data-ws-seite=\"" . ws_e($seite) . '"' . (!empty($opt['vorschau']) ? ' data-ws-vorschau' : '') . ">\n<head>\n" . $kopf . "\n</head>\n"
        . '<body class="' . ws_e($klasse) . '">' . "\n" . $koerper . "</body>\n</html>\n";
}

/* ======================================================= Schreiben === */

/** Schreibt nur, wenn sich etwas ändert. @return string neu|aktualisiert|gleich */
function ws_schreibe(string $pfad, string $inhalt): string
{
    $vorher = is_file($pfad) ? (string) file_get_contents($pfad) : null;
    if ($vorher === $inhalt) {
        return 'gleich';
    }
    if (!is_dir(dirname($pfad)) && !mkdir(dirname($pfad), 0755, true) && !is_dir(dirname($pfad))) {
        throw new RuntimeException('Ordner lässt sich nicht anlegen: ' . dirname($pfad));
    }
    $temp = $pfad . '.tmp' . bin2hex(random_bytes(4));
    if (file_put_contents($temp, $inhalt, LOCK_EX) === false || !rename($temp, $pfad)) {
        @unlink($temp);
        throw new RuntimeException("Datei lässt sich nicht schreiben: $pfad");
    }
    @chmod($pfad, 0644);
    return $vorher === null ? 'neu' : 'aktualisiert';
}

/** Schriften des Designs nach assets/schriften/<design>/ kopieren. */
function ws_schriften_uebertragen(array $design, string $site): int
{
    $ziel = $site . '/assets/schriften/' . $design['id'];
    $quelle = ws_design_ordner() . '/' . $design['id'] . '/schriften';
    $n = 0;
    foreach ($design['fonts'] as $f) {
        $datei = basename((string) ($f['datei'] ?? ''));
        if ($datei === '' || !is_file($quelle . '/' . $datei)) {
            continue;
        }
        if (!is_dir($ziel)) {
            mkdir($ziel, 0755, true);
        }
        if (!is_file($ziel . '/' . $datei) || md5_file($ziel . '/' . $datei) !== md5_file($quelle . '/' . $datei)) {
            copy($quelle . '/' . $datei, $ziel . '/' . $datei);
            $n++;
        }
    }
    return $n;
}

/** macOS legt auf externen Laufwerken ._-Dateien an — die gehören nicht ins Netz. */
function ws_aufraeumen(string $site): int
{
    $anzahl = 0;
    foreach (['', 'assets/', 'medien/', 'impressum/', 'datenschutz/', 'barrierefreiheit/'] as $unter) {
        foreach (glob(rtrim($site, '/') . '/' . $unter . '._*') ?: [] as $d) {
            if (is_file($d) && @unlink($d)) {
                $anzahl++;
            }
        }
    }
    return $anzahl;
}

/**
 * Baut die komplette Webseite.
 *
 * @return array{seiten:int, neu:int, geaendert:int, dauer:float, warnungen:string[], schriften:int, aufgeraeumt:int}
 */
function ws_bauen(string $site, ?callable $laden = null): array
{
    $start = microtime(true);
    $site = rtrim($site, '/');
    $laden ??= ws_daten_ordner(__DIR__ . '/daten');

    if (!is_dir($site) || !is_writable($site)) {
        throw new RuntimeException("Der Ordner der Webseite fehlt oder ist schreibgeschützt: $site");
    }
    foreach (['seite.css', 'seite.js'] as $v) {
        if (!is_file(__DIR__ . '/vorlagen/' . $v)) {
            throw new RuntimeException("Die Vorlage privat/vorlagen/$v fehlt.");
        }
    }

    [$inhalt, $warnungen] = ws_inhalt($laden, ['medien' => $site]);
    $design = ws_design_laden($inhalt['design']);
    if ($design['id'] !== $inhalt['design']) {
        $warnungen[] = 'Design: „' . $inhalt['design'] . '“ gibt es nicht (mehr) — verwendet wird „' . $design['name'] . '“.';
    }

    // Gestaltung und Skript
    $css = ws_design_css($design, 'schriften/' . $design['id'] . '/');
    $seiteCss = (string) file_get_contents(__DIR__ . '/vorlagen/seite.css');
    $seiteJs = (string) file_get_contents(__DIR__ . '/vorlagen/seite.js');
    ws_schreibe($site . '/assets/design.css', $css);
    ws_schreibe($site . '/assets/seite.css', $seiteCss);
    ws_schreibe($site . '/assets/seite.js', $seiteJs);
    $schriften = ws_schriften_uebertragen($design, $site);
    $theme = ws_theme($design);
    $themeDateien = ws_theme_uebertragen($theme, $site);
    foreach ($theme !== null ? ws_theme_pruefen($design) : [] as $befund) {
        $warnungen[] = 'Design „' . $design['name'] . '“: ' . $befund;
    }
    // Eine Kennung aus allen Dateien — ändert sich eine, holen Browser sie neu
    $version = '?v=' . substr(md5($css . $seiteCss . $seiteJs . ws_theme_version($theme)), 0, 10);

    $zaehler = ['neu' => 0, 'aktualisiert' => 0, 'gleich' => 0];
    $seiten = 0;
    $opt = ['docroot' => $site, 'design' => $design, 'version' => $version];

    $zaehler[ws_schreibe($site . '/index.html', ws_seite_html($inhalt, 'start', '', $opt))]++;
    $seiten++;
    foreach (array_keys(WS_RECHTSSEITEN) as $seite) {
        if (!isset($inhalt['recht'][$seite])) {
            if (is_file($site . '/' . $seite . '/index.html')) {
                @unlink($site . '/' . $seite . '/index.html');
                @rmdir($site . '/' . $seite);
            }
            continue;
        }
        $zaehler[ws_schreibe($site . '/' . $seite . '/index.html', ws_seite_html($inhalt, $seite, '../', $opt))]++;
        $seiten++;
    }

    // Kontaktformular: anfrage.php nur, wenn der Versand über den Server laufen soll
    $formular = $inhalt['formular'];
    if (($formular['modus'] ?? '') === 'server') {
        $vorlageAnfrage = (string) file_get_contents(__DIR__ . '/vorlagen/anfrage.php');
        $host = parse_url(ws_text($inhalt['allgemein']['domain'] ?? ''), PHP_URL_HOST) ?: '';
        $konfig = [
            'empfaenger' => ws_text($formular['empfaenger'] ?? '') ?: ws_text($inhalt['stamm']['kontakt']['email'] ?? ''),
            'absender' => ws_text($formular['absender'] ?? '') ?: ($host !== '' ? 'webseite@' . preg_replace('~^www\.~', '', $host) : ''),
            'betreff' => ws_text($formular['betreff'] ?? '') ?: 'Anfrage über die Webseite',
            'name' => ws_text($inhalt['stamm']['name'] ?? ''),
            // Dort liegen versand.php und die SMTP-Zugangsdaten (ablage/smtp.php)
            'privat' => realpath(__DIR__) ?: __DIR__,
        ];
        ws_schreibe($site . '/anfrage.php', str_replace('/*WS:KONFIG*/[]', var_export($konfig, true), $vorlageAnfrage));
    } elseif (is_file($site . '/anfrage.php')) {
        @unlink($site . '/anfrage.php');
    }

    // Hochgeladene Dateien dürfen niemals als Programm laufen
    ws_schreibe($site . '/medien/.htaccess', "# Vom Generator angelegt: In diesem Ordner läuft kein Code.\n"
        . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
        . "RemoveHandler .php .phtml .php5 .php7 .php8 .phar\nRemoveType .php .phtml .php5 .php7 .php8 .phar\n"
        . "Options -ExecCGI -Indexes\n"
        . "<IfModule mod_headers.c>\n  Header set X-Content-Type-Options \"nosniff\"\n</IfModule>\n");

    // Kopfzeilen und Zwischenspeicher der Webseite (Vorlage: privat/vorlagen/site.htaccess)
    $vorlageHt = __DIR__ . '/vorlagen/site.htaccess';
    if (is_file($vorlageHt)) {
        $htaccess = $site . '/.htaccess';
        $sicherung = $site . '/.htaccess-vorher';
        // Eine von Hand gepflegte Fassung bleibt beim ersten Mal erhalten
        if (is_file($htaccess) && !is_file($sicherung) && !str_contains((string) file_get_contents($htaccess), WS_HTACCESS_MARKE)) {
            @copy($htaccess, $sicherung);
            $warnungen[] = 'Webseite: Die bisherige .htaccess stammte nicht vom Generator und liegt jetzt als .htaccess-vorher daneben. '
                . 'Bitte einmal vergleichen, ob darin eigene Regeln standen.';
        }
        ws_schreibe($htaccess, (string) file_get_contents($vorlageHt));
    }

    $domain = rtrim(ws_text($inhalt['allgemein']['domain'] ?? ''), '/');
    $indexieren = !empty($inhalt['allgemein']['seo']['indexieren']);
    if ($domain !== '') {
        $heute = date('Y-m-d');
        $urls = "  <url><loc>" . ws_e($domain) . "/</loc><lastmod>$heute</lastmod><priority>1.0</priority></url>\n";
        foreach (array_keys($inhalt['recht']) as $seite) {
            $urls .= "  <url><loc>" . ws_e($domain . '/' . $seite . '/') . "</loc><priority>0.2</priority></url>\n";
        }
        ws_schreibe($site . '/sitemap.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . $urls . "</urlset>\n");
    }
    ws_schreibe($site . '/robots.txt', $indexieren
        ? "User-agent: *\nAllow: /\n" . ($domain !== '' ? "\nSitemap: $domain/sitemap.xml\n" : '')
        : "# Die Webseite ist noch im Aufbau.\nUser-agent: *\nDisallow: /\n");

    foreach (ws_theme_warnung() as $w) {
        $warnungen[] = 'Design „' . $design['name'] . '“: ' . $w;
    }

    // Merkzeichen, an dem das Portal den Docroot wiedererkennt
    ws_schreibe($site . '/.webseite', "Docroot der Webseite — vom Generator angelegt.\n");

    return [
        'seiten' => $seiten,
        'neu' => $zaehler['neu'],
        'geaendert' => $zaehler['aktualisiert'],
        'dauer' => round((microtime(true) - $start) * 1000, 1),
        'warnungen' => $warnungen,
        'schriften' => $schriften,
        'themeDateien' => $themeDateien,
        'aufgeraeumt' => ws_aufraeumen($site),
    ];
}

/* ============================================================= CLI === */

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $args = array_slice($argv, 1);
    try {
        if (in_array('--pruefen', $args, true)) {
            [, $warnungen] = ws_inhalt(ws_daten_ordner(__DIR__ . '/daten'));
            echo $warnungen ? '• ' . implode("\n• ", $warnungen) . "\n" : "Keine offenen Punkte.\n";
            exit(0);
        }
        if (!$args) {
            fwrite(STDERR, "Aufruf: php bauen.php <docroot>\n       php bauen.php --pruefen\n");
            exit(1);
        }
        $bericht = ws_bauen($args[0]);
        printf("%d Seiten (%d neu, %d geändert), %d Schriften kopiert — %s ms\n",
            $bericht['seiten'], $bericht['neu'], $bericht['geaendert'], $bericht['schriften'], $bericht['dauer']);
        if ($bericht['warnungen']) {
            echo "\nOffene Punkte:\n• " . implode("\n• ", $bericht['warnungen']) . "\n";
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'Fehler: ' . $e->getMessage() . "\n");
        exit(1);
    }
}
