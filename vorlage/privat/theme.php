<?php
/**
 * Theme-Pakete — die Gestalt der Webseite austauschbar machen.
 *
 * Ein Design besteht mindestens aus Stellschrauben (design.json › tokens):
 * Farben, Schriften, Rundungen. Ein Theme-Paket geht weiter und bringt
 * zusätzlich eigenes Markup, eigenes CSS, Bilder, Schriften und bei Bedarf
 * eigene Sektionstypen mit. Das Portal bleibt dabei gleich — es pflegt nur
 * Inhalte. Wie die Inhalte aussehen, entscheidet das Theme.
 *
 * Aufbau eines Themes (privat/design/<kennung>/):
 *
 *   design.json               Name, Stellschrauben und der Schlüssel „theme“ (siehe unten)
 *   theme.css, *.css          eigenes Stylesheet (liegt nach seite.css, darf sie ersetzen)
 *   theme.js                  optionales Skript (nur hochladen, was man selbst geprüft hat)
 *   vorlagen/seite.html       Aufbau von <body>: {{{kopfHtml}}} {{{hauptHtml}}} {{{fussHtml}}}
 *   vorlagen/kopf.html        Kopfleiste mit Logo und Menü
 *   vorlagen/fuss.html        Fußzeile
 *   vorlagen/recht.html       Impressum, Datenschutz, Barrierefreiheit
 *   vorlagen/kopfzusatz.html  zusätzliche Zeilen im <head> (z. B. Webfonts)
 *   vorlagen/sektionen/<typ>.html            eine Sektion der Startseite
 *   vorlagen/sektionen/<typ>--<variante>.html  eine Variante davon
 *   vorlagen/teile/<name>.html               Bausteine für {{> name}}
 *   assets/…                  Bilder, Symbole (liegen später unter {{theme}}assets/…)
 *   schriften/…               Schriftdateien — in theme.json › fonts eintragen, dann bindet design.css sie ein
 *
 * Fehlt eine Vorlage, gilt die eingebaute Darstellung — ein Theme muss also
 * nur ersetzen, was anders aussehen soll.
 *
 * design.json › theme:
 *   {
 *     "version": 1,
 *     "css": ["theme.css"],          Stylesheets in dieser Reihenfolge
 *     "js": ["theme.js"],            Skripte (defer)
 *     "basisCss": true,              false = seite.css weglassen, das Theme gestaltet alles selbst
 *     "bodyKlasse": "th-klar",       zusätzliche Klasse an <body>
 *     "sektionen": { "<typ>": { "name", "beschreibung", "zeichen", "einmalig", "felder": {…}, "vorlage": {…} } },
 *     "zusatzfelder": { "<typ>": { "<feld>": {…} } },
 *     "varianten": { "<typ>": { "<schluessel>": "Beschriftung" } }
 *   }
 *
 * Vorlagensprache (klein, logikfrei, an Mustache angelehnt):
 *   {{feld}}              Text, sicher maskiert
 *   {{feld|filter}}       mit Filter: zeilen, absaetze, medium, ziel, knopf, knopf2, initialen, tel, gross, klein,
 *                         anzahl, zahl, zweistellig, json
 *   {{{nameHtml}}}        fertiges HTML — nur für Werte, deren Name auf „Html“ endet (und „standard“)
 *   {{#liste}}…{{/liste}}  Schleife über eine Liste, oder Block, wenn der Wert gefüllt ist
 *   {{^feld}}…{{/feld}}   Block, wenn der Wert leer ist
 *   {{#liste?}}…{{/liste?}}  Block einmal, wenn die Liste Einträge hat (ohne Schleife)
 *   {{#farbe=akzent}}…{{/farbe=akzent}}   Vergleich (auch !=)
 *   {{.}} {{@nummer}} {{@index}} {{#@erste}} {{#@letzte}}   in Schleifen
 *   {{> teil}}            Baustein aus vorlagen/teile/teil.html
 *   {{! Kommentar }}
 */

declare(strict_types=1);

const WS_THEME_FELDTYPEN = ['text', 'zeile', 'flaeche', 'zahl', 'datum', 'schalter', 'auswahl', 'farbe', 'medium',
                            'link', 'absaetze', 'liste', 'abschnitt', 'objekt', 'hinweis'];

/** Dateien, die ein Theme mitbringen darf — alles andere wird beim Import verworfen. */
const WS_THEME_ENDUNGEN = [
    'css' => 'text/css; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'html' => 'text/html; charset=utf-8',
    'json' => 'application/json', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'webp' => 'image/webp', 'avif' => 'image/avif', 'gif' => 'image/gif', 'ico' => 'image/x-icon',
    'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
    'mp4' => 'video/mp4', 'webm' => 'video/webm', 'txt' => 'text/plain; charset=utf-8', 'md' => 'text/plain; charset=utf-8',
];

/** Diese Dateien bleiben im privaten Ordner — sie werden nie auf die Webseite kopiert. */
const WS_THEME_NICHT_OEFFENTLICH = '~^(design\.json|theme\.json|vorlagen/|schriften/|vorschau\.|README|LIESMICH)~i';

/* ============================================================ Theme === */

/**
 * Theme-Angaben eines geladenen Designs — oder null, wenn das Design nur
 * aus Stellschrauben besteht.
 */
function ws_theme(array $design): ?array
{
    $t = $design['theme'] ?? null;
    if (!is_array($t)) {
        return null;
    }
    $ordner = ws_design_ordner() . '/' . ($design['id'] ?? '');
    $dateien = static function (mixed $liste) use ($ordner, $design): array {
        $raus = [];
        foreach (is_array($liste) ? $liste : [$liste] as $p) {
            $p = ws_theme_pfad((string) $p);
            if ($p !== '' && is_file($ordner . '/' . $p)) {
                $raus[] = $p;
            }
        }
        return $raus;
    };
    return [
        'id' => (string) $design['id'],
        'name' => (string) ($design['name'] ?? $design['id']),
        'ordner' => $ordner,
        'css' => $dateien($t['css'] ?? (is_file($ordner . '/theme.css') ? ['theme.css'] : [])),
        'js' => $dateien($t['js'] ?? (is_file($ordner . '/theme.js') ? ['theme.js'] : [])),
        'basisCss' => ($t['basisCss'] ?? true) !== false,
        'bodyKlasse' => preg_replace('/[^A-Za-z0-9 _-]/', '', (string) ($t['bodyKlasse'] ?? '')),
        'sektionen' => ws_theme_sektionstypen($t['sektionen'] ?? []),
        'zusatzfelder' => ws_theme_zusatzfelder($t['zusatzfelder'] ?? []),
        'varianten' => ws_theme_varianten($t['varianten'] ?? []),
    ];
}

/** Ein relativer Pfad im Theme-Ordner — ohne Ausbruch nach oben, ohne versteckte Dateien. */
function ws_theme_pfad(string $pfad): string
{
    $pfad = str_replace('\\', '/', trim($pfad));
    $teile = [];
    foreach (explode('/', $pfad) as $t) {
        if ($t === '' || $t === '.') {
            continue;
        }
        if ($t === '..' || str_starts_with($t, '.') || !preg_match('/^[A-Za-z0-9._@ -]+$/', $t)) {
            return '';
        }
        $teile[] = $t;
    }
    $p = implode('/', $teile);
    return isset(WS_THEME_ENDUNGEN[strtolower(pathinfo($p, PATHINFO_EXTENSION))]) ? $p : '';
}

/** Eine Vorlage des Themes lesen (zwischengespeichert). */
function ws_theme_vorlage(?array $theme, string $name): ?string
{
    static $cache = [];
    if ($theme === null || !preg_match('~^[a-z0-9-]+(/[a-z0-9_-]+)*$~', $name)) {
        return null;
    }
    $datei = $theme['ordner'] . '/vorlagen/' . $name . '.html';
    if (!array_key_exists($datei, $cache)) {
        $cache[$datei] = is_file($datei) ? (string) file_get_contents($datei) : null;
    }
    return $cache[$datei];
}

/** Rendert eine Vorlage des Themes mit Bausteinen aus vorlagen/teile/. */
function ws_theme_rendern(array $theme, string $vorlage, array $kontext): string
{
    return ws_tpl($vorlage, $kontext, static fn(string $n): ?string => ws_theme_vorlage($theme, 'teile/' . $n));
}

/* ------------------------------------------------ Eigene Sektionen --- */

/** Sektionstypen, die ein Theme mitbringt — geprüft und bereinigt. */
function ws_theme_sektionstypen(mixed $roh): array
{
    $raus = [];
    foreach (is_array($roh) ? $roh : [] as $typ => $t) {
        $typ = (string) $typ;
        if (!preg_match('/^[a-z][a-z0-9-]{1,30}$/', $typ) || !is_array($t)) {
            continue;
        }
        $raus[$typ] = [
            'name' => mb_substr(trim((string) ($t['name'] ?? $typ)), 0, 40) ?: $typ,
            'beschreibung' => mb_substr(trim((string) ($t['beschreibung'] ?? '')), 0, 200),
            'zeichen' => mb_substr((string) ($t['zeichen'] ?? '◆'), 0, 2) ?: '◆',
            'einmalig' => !empty($t['einmalig']),
            'menue' => mb_substr(trim((string) ($t['menue'] ?? '')), 0, 30),
            'felder' => ws_theme_felder_saeubern($t['felder'] ?? []),
            'vorlage' => is_array($t['vorlage'] ?? null) ? $t['vorlage'] : [],
        ];
    }
    return $raus;
}

function ws_theme_zusatzfelder(mixed $roh): array
{
    $raus = [];
    foreach (is_array($roh) ? $roh : [] as $typ => $felder) {
        if (preg_match('/^[a-z][a-z0-9-]*$/', (string) $typ) && is_array($felder)) {
            $raus[(string) $typ] = ws_theme_felder_saeubern($felder);
        }
    }
    return $raus;
}

function ws_theme_varianten(mixed $roh): array
{
    $raus = [];
    foreach (is_array($roh) ? $roh : [] as $typ => $liste) {
        if (!preg_match('/^[a-z][a-z0-9-]*$/', (string) $typ) || !is_array($liste)) {
            continue;
        }
        foreach ($liste as $k => $label) {
            if (preg_match('/^[a-z0-9-]{1,30}$/', (string) $k)) {
                $raus[(string) $typ][(string) $k] = mb_substr((string) $label, 0, 60);
            }
        }
    }
    return $raus;
}

/**
 * Feldbeschreibungen aus einem Theme: nur bekannte Feldtypen, nur harmlose
 * Angaben. Dieselbe Form wie in portal/lib/schema.php.
 */
function ws_theme_felder_saeubern(mixed $felder, int $tiefe = 0): array
{
    $raus = [];
    if (!is_array($felder) || $tiefe > 3) {
        return $raus;
    }
    foreach ($felder as $k => $f) {
        $k = (string) $k;
        if (!preg_match('/^_?[A-Za-z][A-Za-z0-9]{0,40}$/', $k) || !is_array($f) || !in_array($f['typ'] ?? '', WS_THEME_FELDTYPEN, true)) {
            continue;
        }
        $neu = ['typ' => $f['typ']];
        foreach (['label', 'hilfe', 'text', 'knopf', 'muster', 'art', 'zeilenTitel'] as $s) {
            if (isset($f[$s]) && is_scalar($f[$s])) {
                $neu[$s] = mb_substr((string) $f[$s], 0, 300);
            }
        }
        foreach (['breit', 'pflicht', 'einzeilig', 'kompakt', 'flach', 'standard'] as $s) {
            if (isset($f[$s])) {
                $neu[$s] = (bool) $f[$s];
            }
        }
        foreach (['min', 'max', 'zeilen'] as $s) {
            if (isset($f[$s]) && is_numeric($f[$s])) {
                $neu[$s] = (int) $f[$s];
            }
        }
        if ($neu['typ'] === 'auswahl') {
            $neu['optionen'] = [];
            foreach ((array) ($f['optionen'] ?? []) as $ok => $ol) {
                $neu['optionen'][(string) $ok] = mb_substr((string) $ol, 0, 80);
            }
            if (!$neu['optionen']) {
                continue;
            }
        }
        if ($neu['typ'] === 'medium' && !in_array($neu['art'] ?? '', ['bild', 'video', 'pdf'], true)) {
            $neu['art'] = 'bild';
        }
        if (in_array($neu['typ'], ['liste', 'objekt', 'abschnitt'], true)) {
            $neu['felder'] = ws_theme_felder_saeubern($f['felder'] ?? [], $tiefe + 1);
        }
        $neu['label'] ??= $k;
        $raus[$k] = $neu;
    }
    return $raus;
}

/* ======================================================= Vorlagen === */

/**
 * Vorlage rendern.
 * @param callable|null $teil  Name => Vorlagentext eines Bausteins (oder null)
 */
function ws_tpl(string $vorlage, array $kontext, ?callable $teil = null, int $tiefe = 0): string
{
    static $baeume = [];
    $schluessel = md5($vorlage);
    $baeume[$schluessel] ??= ws_tpl_parsen($vorlage);
    return ws_tpl_knoten($baeume[$schluessel], [$kontext], $teil, $tiefe);
}

/** Zerlegt eine Vorlage in einen Baum. Nicht geschlossene Blöcke werfen eine Ausnahme mit Zeilenangabe. */
function ws_tpl_parsen(string $vorlage): array
{
    $teile = preg_split('/(\{\{\{\s*[^}]+?\s*\}\}\}|\{\{[^}]+?\}\})/', $vorlage, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE) ?: [];
    $wurzel = [];
    $stapel = [[&$wurzel, '', 0]];
    foreach ($teile as $i => [$stueck, $pos]) {
        $ziel = &$stapel[count($stapel) - 1][0];
        if ($i % 2 === 0) {
            if ($stueck !== '') {
                $ziel[] = ['t', $stueck];
            }
            unset($ziel);
            continue;
        }
        if (str_starts_with($stueck, '{{{')) {
            $ziel[] = ['v', trim(substr($stueck, 3, -3)), [], true];
            unset($ziel);
            continue;
        }
        $innen = trim(substr($stueck, 2, -2));
        $zeichen = $innen[0] ?? '';
        $name = trim(substr($innen, 1));
        switch ($zeichen) {
            case '!':
                break;
            case '#':
            case '^':
                $knoten = ['s', $name, [], $zeichen === '^'];
                $ziel[] = $knoten;
                $index = count($ziel) - 1;
                $stapel[] = [&$ziel[$index][2], $name, $pos];
                break;
            case '/':
                $offen = $stapel[count($stapel) - 1];
                if (count($stapel) < 2 || $offen[1] !== $name) {
                    $zeile = substr_count(substr($vorlage, 0, $pos), "\n") + 1;
                    throw new RuntimeException('Vorlage: {{/' . $name . '}} in Zeile ' . $zeile . ' passt zu keinem offenen Block'
                        . ($offen[1] !== '' ? ' (offen ist {{#' . $offen[1] . '}})' : '') . '.');
                }
                array_pop($stapel);
                break;
            case '>':
                $ziel[] = ['p', $name];
                break;
            default:
                $filter = array_map('trim', explode('|', $innen));
                $ziel[] = ['v', array_shift($filter), $filter, false];
        }
        unset($ziel);
    }
    if (count($stapel) > 1) {
        $offen = $stapel[count($stapel) - 1];
        $zeile = substr_count(substr($vorlage, 0, $offen[2]), "\n") + 1;
        throw new RuntimeException('Vorlage: {{#' . $offen[1] . '}} aus Zeile ' . $zeile . ' wird nicht geschlossen.');
    }
    return $wurzel;
}

function ws_tpl_knoten(array $knoten, array $stapel, ?callable $teil, int $tiefe): string
{
    $html = '';
    foreach ($knoten as $k) {
        switch ($k[0]) {
            case 't':
                $html .= $k[1];
                break;
            case 'v':
                $html .= ws_tpl_ausgabe($k[1], $k[2], $k[3], $stapel);
                break;
            case 'p':
                if ($teil !== null && $tiefe < 8 && preg_match('/^[a-z0-9_-]+$/', $k[1])) {
                    $text = $teil($k[1]);
                    if ($text !== null) {
                        $html .= ws_tpl_knoten(ws_tpl_parsen($text), $stapel, $teil, $tiefe + 1);
                    }
                }
                break;
            case 's':
                $wert = ws_tpl_wert($k[1], $stapel);
                $leer = $wert === null || $wert === false || $wert === '' || $wert === [] || $wert === 0 || $wert === '0';
                if ($k[3]) {
                    if ($leer) {
                        $html .= ws_tpl_knoten($k[2], $stapel, $teil, $tiefe);
                    }
                    break;
                }
                if ($leer) {
                    break;
                }
                if (is_array($wert) && array_is_list($wert)) {
                    $n = count($wert);
                    foreach ($wert as $i => $eintrag) {
                        $meta = ['@index' => $i, '@nummer' => $i + 1, '@erste' => $i === 0, '@letzte' => $i === $n - 1, '@anzahl' => $n];
                        $rahmen = is_array($eintrag) && !array_is_list($eintrag) ? $eintrag + $meta + ['.' => $eintrag] : ['.' => $eintrag] + $meta;
                        $html .= ws_tpl_knoten($k[2], array_merge($stapel, [$rahmen]), $teil, $tiefe);
                    }
                } elseif (is_array($wert)) {
                    $html .= ws_tpl_knoten($k[2], array_merge($stapel, [$wert + ['.' => $wert]]), $teil, $tiefe);
                } elseif (is_bool($wert)) {
                    // Ja/Nein und Vergleiche: Block zeigen, „.“ bleibt, was es war
                    $html .= ws_tpl_knoten($k[2], $stapel, $teil, $tiefe);
                } else {
                    $html .= ws_tpl_knoten($k[2], array_merge($stapel, [['.' => $wert] + end($stapel)]), $teil, $tiefe);
                }
                break;
        }
    }
    return $html;
}

/** Einen Namen im Kontext auflösen: innerster Rahmen zuerst, Punkte gehen in die Tiefe. */
function ws_tpl_wert(string $name, array $stapel): mixed
{
    foreach (['!=', '='] as $op) {
        if (str_contains($name, $op)) {
            [$links, $rechts] = array_map('trim', explode($op, $name, 2));
            $gleich = (string) (is_bool($w = ws_tpl_wert($links, $stapel)) ? ($w ? '1' : '0') : (is_scalar($w) ? $w : '')) === $rechts;
            return $op === '=' ? $gleich : !$gleich;
        }
    }
    if (str_ends_with($name, '?')) {
        // „gefüllt?“ — für Listen: Block einmal zeigen statt je Eintrag
        $w = ws_tpl_wert(substr($name, 0, -1), $stapel);
        return !($w === null || $w === false || $w === '' || $w === [] || $w === 0 || $w === '0');
    }
    if ($name === '.') {
        return end($stapel)['.'] ?? null;
    }
    $teile = explode('.', $name);
    $erstes = array_shift($teile);
    $wert = null;
    $gefunden = false;
    for ($i = count($stapel) - 1; $i >= 0; $i--) {
        if (is_array($stapel[$i]) && array_key_exists($erstes, $stapel[$i])) {
            $wert = $stapel[$i][$erstes];
            $gefunden = true;
            break;
        }
    }
    if (!$gefunden) {
        return null;
    }
    foreach ($teile as $t) {
        if (!is_array($wert) || !array_key_exists($t, $wert)) {
            return null;
        }
        $wert = $wert[$t];
    }
    return $wert;
}

/** Wert ausgeben — maskiert, außer bei fertigem HTML aus dem Generator. */
function ws_tpl_ausgabe(string $name, array $filter, bool $roh, array $stapel): string
{
    $wert = ws_tpl_wert($name, $stapel);
    $html = false;
    foreach ($filter as $f) {
        [$wert, $html] = ws_tpl_filter($f, $wert, $stapel);
    }
    if ($html) {
        return (string) $wert;
    }
    if (is_array($wert)) {
        $wert = implode(', ', array_filter($wert, 'is_scalar'));
    }
    $text = is_bool($wert) ? ($wert ? '1' : '') : (string) ($wert ?? '');
    $letzter = substr($name, (int) strrpos('.' . $name, '.'));
    // Dreifache Klammern nur für HTML, das der Generator selbst erzeugt hat
    if ($roh && (str_ends_with($letzter, 'Html') || $letzter === 'standard')) {
        return $text;
    }
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** @return array{0:mixed,1:bool} [neuer Wert, ist fertiges HTML] */
function ws_tpl_filter(string $f, mixed $w, array $stapel): array
{
    $basis = ws_kontext()['basis'];
    $s = is_scalar($w) ? (string) $w : '';
    return match ($f) {
        'zeilen' => [ws_zeilen($s), true],
        'absaetze' => [ws_absaetze(is_array($w) ? $w : ($s !== '' ? [$s] : [])), true],
        'medium' => [ws_medium($s), false],
        'ziel' => [ws_ziel($s, $basis), false],
        'knopf' => [ws_knopf($w), true],
        'knopf2' => [ws_knopf($w, 'ws-knopf ws-knopf--zweit'), true],
        'initialen' => [ws_initialen($s), false],
        'tel' => [preg_replace('/[^0-9+]/', '', $s), false],
        'gross' => [mb_strtoupper($s), false],
        'klein' => [mb_strtolower($s), false],
        'anzahl' => [is_array($w) ? count($w) : 0, false],
        'zahl' => [number_format((float) $s, 0, ',', '.'), false],
        'zweistellig' => [str_pad($s, 2, '0', STR_PAD_LEFT), false],
        'json' => [json_encode($w, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), false],
        default => [$w, false],
    };
}

/* ====================================================== Kontexte === */

/**
 * Daten für Theme-Vorlagen anreichern: Knöpfe bekommen „url“, Medienpfade
 * „<feld>Url“, Absatzlisten „<feld>Html“, mehrzeilige Texte „<feld>Html“.
 */
function ws_theme_anreichern(mixed $daten): mixed
{
    if (!is_array($daten)) {
        return $daten;
    }
    $basis = ws_kontext()['basis'];
    $raus = [];
    foreach ($daten as $k => $v) {
        $raus[$k] = ws_theme_anreichern($v);
        if (!is_string($k)) {
            continue;
        }
        if (is_string($v) && str_starts_with($v, 'medien/')) {
            $raus[$k . 'Url'] = ws_medium($v);
        } elseif (is_string($v) && str_contains($v, "\n")) {
            $raus[$k . 'Html'] = ws_zeilen($v);
        } elseif ($k === 'absaetze' && is_array($v)) {
            $raus['absaetzeHtml'] = ws_absaetze($v);
        }
    }
    if (array_key_exists('text', $daten) && array_key_exists('ziel', $daten) && is_string($daten['ziel'] ?? null)) {
        $url = ws_text($daten['text'] ?? '') !== '' ? ws_ziel(ws_text($daten['ziel']), $basis) : '';
        $raus['url'] = $url;
        $raus['extern'] = $url !== '' && ws_extern($url) !== '';
        $raus['zeigen'] = $url !== '';
    }
    return $raus;
}

/** Alles, was jede Theme-Vorlage kennt. Einmal je Seite berechnet. */
function ws_theme_global(array $inhalt): array
{
    $k = ws_kontext();
    static $cache = [];
    $schluessel = md5($k['basis'] . ($k['seite'] ?? '') . ($k['theme']['id'] ?? '') . json_encode($inhalt['stamm'] ?? []));
    if (isset($cache[$schluessel])) {
        return $cache[$schluessel];
    }
    $basis = $k['basis'];
    $stamm = $inhalt['stamm'];
    $kontakt = $stamm['kontakt'] ?? [];
    $projekt = ws_projekt();
    $tel = ws_text($kontakt['telefonLink'] ?? '') ?: preg_replace('/[^0-9+]/', '', (string) ($kontakt['telefon'] ?? ''));

    $angebote = [];
    foreach ($inhalt['angebote'] as $a) {
        $angebote[] = ws_theme_anreichern($a) + ['anker' => 'angebot-' . ($a['id'] ?? '')];
    }
    $team = [];
    foreach ($inhalt['team'] as $p) {
        $team[] = ws_theme_anreichern($p) + ['initialen' => ws_initialen((string) ($p['name'] ?? ''))];
    }
    $zeiten = (array) ($inhalt['zeiten']['zeiten'] ?? []);

    $recht = [];
    foreach (WS_RECHTSSEITEN as $seite => $label) {
        if (isset($inhalt['recht'][$seite])) {
            $recht[] = ['label' => $label, 'url' => $basis . $seite . '/', 'seite' => $seite];
        }
    }
    $social = [];
    foreach ((array) ($stamm['social'] ?? []) as $l) {
        $url = ws_ziel(ws_text($l['url'] ?? ''), $basis);
        if ($url !== '' && ws_text($l['titel'] ?? '') !== '') {
            $social[] = ['titel' => ws_text($l['titel']), 'url' => $url];
        }
    }
    $kopf = $inhalt['allgemein']['kopf'] ?? [];

    return $cache[$schluessel] = [
        'basis' => $basis,
        'startUrl' => $basis === '' ? '#' . ws_erster_anker($inhalt) : $basis,
        'theme' => $basis . 'assets/theme/' . ($k['theme']['id'] ?? '') . '/',
        'jahr' => date('Y'),
        'seite' => (string) ($k['seite'] ?? 'start'),
        'istStart' => ($k['seite'] ?? 'start') === 'start',
        'vorschau' => (bool) $k['vorschau'],
        'firma' => ws_theme_anreichern($stamm) + [
            'telefonUrl' => $tel !== '' ? 'tel:' . $tel : '',
            'emailUrl' => ws_text($kontakt['email'] ?? '') !== '' ? 'mailto:' . ws_text($kontakt['email']) : '',
            'anschriftHtml' => ws_anschrift($stamm),
            'mapsUrl' => ws_maps_link($stamm),
            'logoUrl' => ws_medium(ws_text($kopf['logo'] ?? '') ?: ws_text($stamm['logo'] ?? '')),
        ],
        'begriffe' => (array) ($projekt['begriffe'] ?? []),
        'angebote' => $angebote,
        'team' => $team,
        'preise' => ws_theme_anreichern($inhalt['preise'] ?? []),
        'zeiten' => $zeiten,
        'zeitenHtml' => ws_zeitentabelle($zeiten),
        'menue' => ws_menue_eintraege($inhalt),
        'recht' => $recht,
        'social' => $social,
        'kopfknopf' => ws_theme_anreichern(['text' => $kopf['knopfText'] ?? '', 'ziel' => $kopf['knopfZiel'] ?? '']),
        'markeHtml' => ws_marke($inhalt),
        'formularHtml' => ws_formular($inhalt),
        'hinweiseHtml' => ws_hinweise($inhalt),
    ];
}

/* ============================================================ Dateien === */

/** Alle Dateien des Themes, die auf die Webseite gehören (relativ zum Theme-Ordner). */
function ws_theme_oeffentlich(array $theme): array
{
    $raus = [];
    $ordner = $theme['ordner'];
    if (!is_dir($ordner)) {
        return $raus;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ordner, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $datei) {
        if (!$datei->isFile()) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($datei->getPathname(), strlen($ordner) + 1));
        if (ws_theme_pfad($rel) !== $rel || preg_match(WS_THEME_NICHT_OEFFENTLICH, $rel)) {
            continue;
        }
        $raus[] = $rel;
    }
    sort($raus);
    return $raus;
}

/** Theme-Dateien nach <site>/assets/theme/<id>/ spiegeln. @return int Anzahl kopierter Dateien */
function ws_theme_uebertragen(?array $theme, string $site): int
{
    $wurzel = $site . '/assets/theme';
    $n = 0;
    // Alte Themes entfernen — es liegt immer nur das aktive auf der Webseite
    foreach (glob($wurzel . '/*', GLOB_ONLYDIR) ?: [] as $alt) {
        if ($theme === null || basename($alt) !== $theme['id']) {
            ws_ordner_leeren($alt);
        }
    }
    if ($theme === null) {
        return 0;
    }
    $ziel = $wurzel . '/' . $theme['id'];
    $soll = ws_theme_oeffentlich($theme);
    foreach ($soll as $rel) {
        $q = $theme['ordner'] . '/' . $rel;
        $z = $ziel . '/' . $rel;
        if (!is_file($z) || filesize($z) !== filesize($q) || md5_file($z) !== md5_file($q)) {
            if (!is_dir(dirname($z))) {
                mkdir(dirname($z), 0755, true);
            }
            copy($q, $z);
            @chmod($z, 0644);
            $n++;
        }
    }
    // Was es im Theme nicht mehr gibt, auch auf der Webseite entfernen
    if (is_dir($ziel)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ziel, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $d) {
            $rel = str_replace('\\', '/', substr($d->getPathname(), strlen($ziel) + 1));
            if ($d->isFile() && !in_array($rel, $soll, true)) {
                @unlink($d->getPathname());
            } elseif ($d->isDir()) {
                @rmdir($d->getPathname()); // nur leere Ordner
            }
        }
    }
    return $n;
}

function ws_ordner_leeren(string $ordner): void
{
    if (!is_dir($ordner)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ordner, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $d) {
        $d->isDir() ? @rmdir($d->getPathname()) : @unlink($d->getPathname());
    }
    @rmdir($ordner);
}

/* ====================================================== Installieren === */

/** Freie Kennung (Ordnername) für ein neues Design. */
function ws_design_kennung_frei(string $name): string
{
    $basis = substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']))), '-'), 0, 40) ?: 'design';
    $kandidat = $basis;
    $n = 2;
    while (is_dir(ws_design_ordner() . '/' . $kandidat)) {
        $kandidat = $basis . '-' . $n++;
    }
    return $kandidat;
}

/**
 * Ein Theme aus theme.json und seinen Dateien anlegen — gemeinsam für das
 * Portal (ZIP-Upload) und die Kommandozeile (Ordner).
 *
 * @param array  $roh      Inhalt der theme.json
 * @param array  $dateien  relativer Pfad => Inhalt
 * @param string $quelle   Herkunft für die Anzeige
 * @param string $id       feste Kennung (leer = aus dem Namen; vorhandener Ordner wird ersetzt)
 * @return array{0:?string,1:string[]} [Kennung, Befunde]
 */
function ws_theme_installieren(array $roh, array $dateien, string $quelle, string $id = ''): array
{
    if (trim((string) ($roh['name'] ?? '')) === '') {
        return [null, ['Die theme.json ist beschädigt oder hat keinen „name“.']];
    }
    $name = mb_substr(trim((string) $roh['name']), 0, 60);
    if ($id !== '' && !preg_match('/^[a-z0-9-]{1,40}$/', $id)) {
        return [null, ['Ungültige Kennung „' . $id . '“ — erlaubt sind a–z, 0–9 und Bindestriche.']];
    }
    if (in_array($id, ['aurora', 'aurora-hell'], true)) {
        return [null, ['Die eingebauten Designs lassen sich nicht ersetzen.']];
    }
    $id = $id !== '' ? $id : ws_design_kennung_frei($name);
    $ordner = ws_design_ordner() . '/' . $id;
    ws_ordner_leeren($ordner);
    mkdir($ordner, 0755, true);

    $verworfen = [];
    foreach ($dateien as $rel => $inhalt) {
        $rel = str_replace('\\', '/', (string) $rel);
        if (in_array($rel, ['theme.json', 'design.json'], true) || str_starts_with(basename($rel), '._') || basename($rel) === '.DS_Store') {
            continue;
        }
        $sauber = ws_theme_pfad($rel);
        if ($sauber === '' || $sauber !== $rel) {
            $verworfen[] = $rel;
            continue;
        }
        if (!is_dir(dirname($ordner . '/' . $sauber))) {
            mkdir(dirname($ordner . '/' . $sauber), 0755, true);
        }
        file_put_contents($ordner . '/' . $sauber, $inhalt);
    }

    // Stellschrauben: Vorgaben des Generators, darüber die Werte des Themes
    $tokens = [];
    foreach (WS_DESIGN_TOKENS as $k => [, , , $vorgabe]) {
        $w = $roh['tokens'][$k] ?? $vorgabe;
        $tokens[$k] = is_string($w) ? ws_css_wert($w) : $w;
    }
    $fonts = [];
    foreach ((array) ($roh['fonts'] ?? []) as $f) {
        $datei = basename((string) ($f['datei'] ?? ''));
        if ($datei !== '' && is_file($ordner . '/schriften/' . $datei)) {
            $fonts[] = ['familie' => str_replace(['"', "'"], '', ws_css_wert($f['familie'] ?? '')), 'datei' => $datei,
                        'gewicht' => preg_match('/^[0-9 ]+$/', (string) ($f['gewicht'] ?? '')) ? (string) $f['gewicht'] : '400',
                        'stil' => ($f['stil'] ?? '') === 'italic' ? 'italic' : 'normal'];
        } elseif ($datei !== '') {
            $verworfen[] = 'schriften/' . $datei . ' (in theme.json genannt, fehlt)';
        }
    }
    $variablen = [];
    foreach ((array) ($roh['variablen'] ?? []) as $k => $w) {
        if (preg_match('/^--[A-Za-z0-9_-]{1,80}$/', (string) $k) && is_scalar($w)) {
            $variablen[(string) $k] = ws_css_wert($w);
        }
    }
    $theme = is_array($roh['theme'] ?? null) ? $roh['theme'] : [];
    $theme['version'] = (int) ($theme['version'] ?? 1);

    $design = [
        'name' => $name,
        'beschreibung' => mb_substr(trim((string) ($roh['beschreibung'] ?? '')), 0, 300),
        'quelle' => $quelle . (isset($roh['version']) ? ' · Version ' . mb_substr((string) $roh['version'], 0, 20) : '')
            . (isset($roh['autor']) ? ' · ' . mb_substr((string) $roh['autor'], 0, 60) : ''),
        'schutz' => false,
        'tokens' => $tokens,
        'fonts' => $fonts,
        'variablen' => $variablen,
        'theme' => $theme,
        'erstellt' => date('c'),
    ];
    file_put_contents($ordner . '/design.json', json_encode($design, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

    $befunde = ws_theme_pruefen(ws_design_laden($id));
    if ($verworfen) {
        $befunde[] = count($verworfen) . ' Datei(en) nicht übernommen: ' . implode(', ', array_slice($verworfen, 0, 8)) . (count($verworfen) > 8 ? ' …' : '');
    }
    return [$id, $befunde];
}

/** Liest einen Theme-Ordner (mit theme.json) als [roh, dateien]. */
function ws_theme_ordner_lesen(string $quelle): array
{
    $quelle = rtrim($quelle, '/');
    $roh = json_decode((string) @file_get_contents($quelle . '/theme.json'), true);
    if (!is_array($roh)) {
        throw new RuntimeException('In ' . $quelle . ' liegt keine gültige theme.json.');
    }
    $dateien = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($quelle, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $dateien[str_replace('\\', '/', substr($f->getPathname(), strlen($quelle) + 1))] = (string) file_get_contents($f->getPathname());
        }
    }
    return [$roh, $dateien];
}

/** Kennung für den Zwischenspeicher der Browser: ändert sich, sobald sich eine Theme-Datei ändert. */
function ws_theme_version(?array $theme): string
{
    if ($theme === null) {
        return '';
    }
    $h = '';
    foreach (array_merge($theme['css'], $theme['js']) as $rel) {
        $h .= md5_file($theme['ordner'] . '/' . $rel);
    }
    return $h;
}

/**
 * Prüft ein Theme so gründlich wie möglich, ohne es zu verwenden:
 * Vorlagen parsen, fehlende Dateien, unbekannte Sektionstypen.
 * @return string[] Befunde (leer = alles gut)
 */
function ws_theme_pruefen(array $design): array
{
    $theme = ws_theme($design);
    if ($theme === null) {
        return [];
    }
    $befunde = [];
    $roh = $design['theme'];
    foreach (['css', 'js'] as $art) {
        foreach ((array) ($roh[$art] ?? []) as $p) {
            if (!in_array(ws_theme_pfad((string) $p), $theme[$art], true)) {
                $befunde[] = 'Die Datei „' . $p . '“ aus theme.' . $art . ' fehlt im Theme.';
            }
        }
    }
    $vorlagen = glob($theme['ordner'] . '/vorlagen/{,*/}*.html', GLOB_BRACE) ?: [];
    foreach ($vorlagen as $datei) {
        $rel = substr($datei, strlen($theme['ordner']) + 10, -5);
        try {
            ws_tpl_parsen((string) file_get_contents($datei));
        } catch (RuntimeException $e) {
            $befunde[] = $rel . '.html: ' . $e->getMessage();
        }
        if (str_starts_with($rel, 'sektionen/')) {
            $typ = explode('--', substr($rel, 10))[0];
            if (!function_exists('ws_sektion_' . str_replace('-', '_', $typ)) && !isset($theme['sektionen'][$typ])) {
                $befunde[] = $rel . '.html: Den Sektionstyp „' . $typ . '“ gibt es weder eingebaut noch in theme.sektionen.';
            }
        }
    }
    foreach ($theme['sektionen'] as $typ => $t) {
        if (ws_theme_vorlage($theme, 'sektionen/' . $typ) === null) {
            $befunde[] = 'Für den eigenen Sektionstyp „' . $typ . '“ fehlt vorlagen/sektionen/' . $typ . '.html.';
        }
        if (function_exists('ws_sektion_' . str_replace('-', '_', $typ))) {
            $befunde[] = 'Der eigene Sektionstyp „' . $typ . '“ heißt wie ein eingebauter — bitte umbenennen.';
        }
    }
    $seite = ws_theme_vorlage($theme, 'seite');
    if ($seite !== null && !str_contains($seite, 'hauptHtml')) {
        $befunde[] = 'vorlagen/seite.html gibt {{{hauptHtml}}} nicht aus — dann fehlt der Inhalt.';
    }
    return $befunde;
}
