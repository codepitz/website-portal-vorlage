<?php
/**
 * Designs der Webseite: auswählen, bearbeiten, duplizieren — und ein
 * Claude Design System (oder jedes andere Token-basierte CSS) hochladen.
 *
 * Ein Design ist ein Ordner in privat/design/<kennung>/ mit design.json
 * (Werte der Stellschrauben, siehe WS_DESIGN_TOKENS im Generator) und
 * optional schriften/ mit Webfonts. Welches Design gilt, steht in
 * allgemein.json unter „design“ — und läuft damit wie jeder Inhalt über
 * Entwurf, Vorschau, Veröffentlichen und Sicherung.
 *
 * Hochladen läuft in zwei Schritten:
 *   1. Analysieren: CSS-Variablen und @font-face-Regeln einsammeln, var()-
 *      Verweise auflösen, Schriftdateien beiseitelegen, Zuordnung vorschlagen.
 *   2. Zuordnen: Die Person prüft, welche Variable welche Stellschraube
 *      bedient, und speichert daraus ein neues Design.
 * Rohes CSS aus dem Upload wird nie ausgeliefert — nur Werte, die durch
 * ws_css_wert() gelaufen sind. So kann ein Upload die Webseite nicht zerlegen.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const DESIGN_UPLOAD_MAX = 30 * 1048576;
const DESIGN_SCHRIFT_ENDUNGEN = ['woff2', 'woff', 'ttf', 'otf'];

/**
 * Vorschläge für die Zuordnung: Stellschraube => Muster für Variablennamen,
 * wichtigste zuerst. Passt zu Claude Design Systems (--<präfix>-color-bg …),
 * shadcn/Tailwind-Themes (--background, --primary …) und Aurora (--bg, --card …).
 */
const DESIGN_MUSTER = [
    'bg'         => ['~(^|-)color-bg$~', '~(^|-)(bg|background)$~', '~(^|-)color-background$~', '~(^|-)bg-(base|default|page|canvas)$~', '~(^|-)(canvas|page)$~'],
    'surface'    => ['~(^|-)color-surface$~', '~(^|-)(surface|card|panel)$~', '~(^|-)(card|surface)-bg$~', '~(^|-)bg-(surface|elevated|raised|subtle)$~', '~(^|-)popover$~'],
    'surface-2'  => ['~(^|-)color-surface-(muted|2|alt|subtle|sunken)$~', '~(^|-)(surface|card)-(2|muted|alt|subtle)$~', '~(^|-)(muted|secondary)$~', '~(^|-)(input|field)(-bg)?$~', '~(^|-)bg-(muted|subtle|input)$~'],
    'line'       => ['~(^|-)color-border$~', '~(^|-)(border|sep|separator|divider|line|stroke)$~', '~(^|-)border-(default|subtle|muted)$~', '~(^|-)input-border$~'],
    'text'       => ['~(^|-)color-text$~', '~(^|-)(text|foreground|fg|ink|on-bg|on-background)$~', '~(^|-)text-(default|primary|base)$~', '~(^|-)color-fg$~'],
    'text-2'     => ['~(^|-)color-text-muted$~', '~(^|-)text-(muted|secondary|2|soft)$~', '~(^|-)muted-foreground$~', '~(^|-)fg-(muted|secondary)$~'],
    'muted'      => ['~(^|-)color-text-subtle$~', '~(^|-)text-(subtle|tertiary|faint|disabled)$~', '~(^|-)(muted|faint)$~', '~(^|-)fg-(subtle|tertiary)$~'],
    'accent'     => ['~(^|-)color-primary$~', '~(^|-)(primary|accent|brand)$~', '~(^|-)color-(accent|brand)$~', '~(^|-)(primary|accent|brand)-(500|600|base|default)$~'],
    'accent-ink' => ['~(^|-)color-on-primary$~', '~(^|-)on-(primary|accent|brand)$~', '~(^|-)(primary|accent)-(foreground|ink|contrast|text)$~', '~(^|-)accent-ink$~'],
    'ok'         => ['~(^|-)color-(success|ok|positive)$~', '~(^|-)(success|ok|positive)$~'],
    'danger'     => ['~(^|-)color-(error|danger|destructive|negative)$~', '~(^|-)(error|danger|destructive|negative)$~'],
    'font-body'  => ['~(^|-)font-(body|text|sans|base|default)$~', '~(^|-)font-family(-body|-base)?$~', '~(^|-)(body|text)-font$~', '~(^|-)font$~'],
    'font-head'  => ['~(^|-)font-(display|heading|head|headline|title)$~', '~(^|-)(heading|display)-font$~', '~(^|-)font-family-(heading|display)$~'],
    'radius'     => ['~(^|-)radius-(md|card|lg|base|default)$~', '~(^|-)radius$~', '~(^|-)border-radius$~', '~(^|-)rounded(-md)?$~'],
    'radius-sm'  => ['~(^|-)radius-(sm|small|control|input|button|xs)$~', '~(^|-)radius-sm$~'],
    'maxw'       => ['~(^|-)(container|content)-(max|width|max-width)$~', '~(^|-)maxw$~', '~(^|-)max-width$~'],
];

/* ------------------------------------------------------------ Zugriff --- */

function design_liste(): array
{
    $raus = [];
    foreach (glob(pfad_design('*/design.json')) ?: [] as $datei) {
        $id = basename(dirname($datei));
        if (!preg_match('/^[a-z0-9-]+$/', $id)) {
            continue;
        }
        $d = json_lesen($datei);
        if ($d) {
            $raus[$id] = $d + ['id' => $id];
        }
    }
    uasort($raus, static fn($a, $b) => [empty($a['schutz']), $a['name'] ?? ''] <=> [empty($b['schutz']), $b['name'] ?? '']);
    return $raus;
}

/** Ein Design mit allen Vorgaben aufgefüllt — über den Generator, damit beide dasselbe sehen. */
function design_laden(string $id): ?array
{
    if (!preg_match('/^[a-z0-9-]+$/', $id) || !is_file(pfad_design($id . '/design.json'))) {
        return null;
    }
    return generator_laden() ? ws_design_laden($id) : json_lesen(pfad_design($id . '/design.json'));
}

function design_aktiv(): string
{
    return (string) (entwurf_laden('allgemein')['design'] ?? '') ?: 'aurora';
}

function design_live(): string
{
    return (string) (live_laden('allgemein')['design'] ?? '') ?: 'aurora';
}

/** Design für die Webseite wählen — als Entwurf in allgemein.json. */
function design_verwenden(string $id): ?string
{
    if (!design_laden($id)) {
        return 'Dieses Design gibt es nicht (mehr).';
    }
    $a = entwurf_laden('allgemein');
    $a['design'] = $id;
    entwurf_speichern('allgemein', $a);
    return null;
}

function design_kennung(string $name): string
{
    $basis = substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']))), '-'), 0, 40) ?: 'design';
    $kandidat = $basis;
    $n = 2;
    while (is_dir(pfad_design($kandidat))) {
        $kandidat = $basis . '-' . $n++;
    }
    return $kandidat;
}

function design_speichern(string $id, array $d): void
{
    unset($d['id']);
    $d['geaendert'] = date('c');
    json_schreiben(pfad_design($id . '/design.json'), $d);
}

function design_duplizieren(string $id, string $name = ''): ?string
{
    $d = json_lesen(pfad_design($id . '/design.json'));
    if (!$d) {
        return null;
    }
    $name = trim($name) ?: ($d['name'] ?? $id) . ' (eigene Fassung)';
    $neu = design_kennung($name);
    ordner_sicherstellen(pfad_design($neu));
    // Schriften, Theme-Vorlagen, CSS und Bilder — alles außer design.json mitkopieren
    design_ordner_kopieren(pfad_design($id), pfad_design($neu));
    $d['name'] = $name;
    $d['schutz'] = false;
    $d['quelle'] = 'Kopie von „' . ($d['name'] ?? $id) . '“';
    $d['erstellt'] = date('c');
    design_speichern($neu, $d);
    protokoll('Design dupliziert: ' . $name);
    return $neu;
}

function design_loeschen(string $id): ?string
{
    $d = json_lesen(pfad_design($id . '/design.json'));
    if (!$d) {
        return 'Dieses Design gibt es nicht (mehr).';
    }
    if (!empty($d['schutz'])) {
        return 'Die Vorlagen-Designs lassen sich nicht löschen.';
    }
    if ($id === design_aktiv() || $id === design_live()) {
        return 'Dieses Design wird gerade verwendet (auf der Webseite oder im Entwurf). Bitte zuerst ein anderes wählen.';
    }
    ws_ordner_leeren(pfad_design($id));
    protokoll('Design gelöscht: ' . ($d['name'] ?? $id));
    return null;
}

/** Felder für den Editor — aus den Stellschrauben des Generators. */
function design_felder(): array
{
    $gruppen = [];
    foreach (WS_DESIGN_TOKENS as $k => [$label, $art, $gruppe, , $hilfe]) {
        $feld = match ($art) {
            'farbe' => ['typ' => 'farbe', 'label' => $label],
            'schalter' => ['typ' => 'schalter', 'label' => $label, 'breit' => true],
            'zahl' => ['typ' => 'zahl', 'label' => $label, 'min' => 0, 'max' => 1000],
            'auswahl' => ['typ' => 'auswahl', 'label' => $label, 'optionen' => ['dunkel' => 'Dunkel', 'hell' => 'Hell']],
            'schrift' => ['typ' => 'text', 'label' => $label, 'breit' => true],
            default => ['typ' => 'text', 'label' => $label, 'muster' => '[0-9.]+(px|rem|em|%)?|0'],
        };
        if ($hilfe !== '') {
            $feld['hilfe'] = $hilfe;
        }
        $gruppen[$gruppe][$k] = $feld;
    }
    $felder = [
        'name' => ['typ' => 'text', 'label' => 'Name des Designs', 'pflicht' => true],
        'beschreibung' => ['typ' => 'text', 'label' => 'Kurzbeschreibung', 'breit' => true],
    ];
    $tokens = [];
    foreach ($gruppen as $gruppe => $f) {
        $tokens['_' . $gruppe] = ['typ' => 'abschnitt', 'label' => $gruppe, 'felder' => $f];
    }
    $felder['tokens'] = ['typ' => 'objekt', 'flach' => true, 'felder' => $tokens];
    return $felder;
}

/* ------------------------------------------------------ Farbe prüfen --- */

/** Farbwert → [r, g, b] (0–255) oder null, wenn er sich nicht einfach lesen lässt. */
function farbe_rgb(string $wert): ?array
{
    $w = strtolower(trim($wert));
    if (preg_match('/^#([0-9a-f]{3,8})$/', $w, $m)) {
        $h = $m[1];
        if (strlen($h) === 3 || strlen($h) === 4) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
    }
    if (preg_match('/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/', $w, $m)) {
        return [(int) $m[1], (int) $m[2], (int) $m[3]];
    }
    if (preg_match('/^hsla?\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%/', $w, $m)) {
        [$h, $s, $l] = [(float) $m[1] / 360, (float) $m[2] / 100, (float) $m[3] / 100];
        $q = $l < .5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $f = static function (float $t) use ($p, $q): int {
            $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);
            $v = $t < 1 / 6 ? $p + ($q - $p) * 6 * $t : ($t < .5 ? $q : ($t < 2 / 3 ? $p + ($q - $p) * (2 / 3 - $t) * 6 : $p));
            return (int) round($v * 255);
        };
        return [$f($h + 1 / 3), $f($h), $f($h - 1 / 3)];
    }
    $namen = ['white' => [255, 255, 255], 'black' => [0, 0, 0]];
    return $namen[$w] ?? null;
}

function farbe_hex(string $wert): ?string
{
    $rgb = farbe_rgb($wert);
    return $rgb ? sprintf('#%02x%02x%02x', ...$rgb) : null;
}

function farbe_helligkeit(array $rgb): float
{
    $k = array_map(static function (int $c): float {
        $c /= 255;
        return $c <= .03928 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4;
    }, $rgb);
    return .2126 * $k[0] + .7152 * $k[1] + .0722 * $k[2];
}

function farbe_kontrast(string $a, string $b): ?float
{
    $x = farbe_rgb($a);
    $y = farbe_rgb($b);
    if (!$x || !$y) {
        return null;
    }
    [$h, $d] = [farbe_helligkeit($x), farbe_helligkeit($y)];
    return round((max($h, $d) + .05) / (min($h, $d) + .05), 2);
}

/** Lesbarkeit prüfen — liefert Hinweise, blockiert nichts. */
function design_kontraste(array $tokens): array
{
    $paare = [
        ['text', 'bg', 4.5, 'Haupttext auf Seitenhintergrund'],
        ['text-2', 'bg', 4.5, 'Zweittext auf Seitenhintergrund'],
        ['text', 'surface', 4.5, 'Haupttext auf Karten'],
        ['accent-ink', 'accent', 4.5, 'Schrift auf Akzent-Knöpfen'],
        ['accent', 'bg', 3.0, 'Akzent (Links, Rahmen) auf Seitenhintergrund'],
    ];
    $raus = [];
    foreach ($paare as [$vorn, $hinten, $min, $was]) {
        $k = farbe_kontrast((string) ($tokens[$vorn] ?? ''), (string) ($tokens[$hinten] ?? ''));
        if ($k !== null) {
            $raus[] = ['was' => $was, 'wert' => $k, 'min' => $min, 'ok' => $k >= $min];
        }
    }
    return $raus;
}

/* ----------------------------------------------------- CSS auslesen --- */

function css_ohne_kommentare(string $css): string
{
    return (string) preg_replace('~/\*.*?\*/~s', '', $css);
}

/**
 * Sammelt Custom Properties. :root/html gewinnt, sonst gilt der erste Fund.
 * @return array<string,string>
 */
function css_variablen(string $css, array $vorhanden = []): array
{
    $css = css_ohne_kommentare($css);
    $wurzel = [];
    $sonst = [];
    if (preg_match_all('~([^{}]+)\{([^{}]*)\}~', $css, $bloecke, PREG_SET_ORDER)) {
        foreach ($bloecke as [, $selektor, $inhalt]) {
            $istWurzel = (bool) preg_match('~(^|,)\s*(:root|html)\s*($|,)~', trim($selektor));
            // Dunkel-/Hell-Varianten und Komponenten nur nachrangig
            if (preg_match_all('~(--[A-Za-z0-9_-]+)\s*:\s*([^;]+);?~', $inhalt, $m, PREG_SET_ORDER)) {
                foreach ($m as [, $name, $wert]) {
                    $wert = trim($wert);
                    if ($istWurzel) {
                        $wurzel[$name] ??= $wert;
                    } else {
                        $sonst[$name] ??= $wert;
                    }
                }
            }
        }
    }
    return $vorhanden + $wurzel + $sonst;
}

/** Variablen aus Markdown-Tabellen („| `--bg` | `#0e0e16` |“) — für DESIGN-SYSTEM.md & Co. */
function md_variablen(string $md): array
{
    $raus = [];
    if (preg_match_all('~\|\s*`(--[A-Za-z0-9_-]+)`\s*\|\s*`([^`|]+)`~', $md, $m, PREG_SET_ORDER)) {
        foreach ($m as [, $name, $wert]) {
            $raus[$name] ??= trim($wert);
        }
    }
    return $raus;
}

/** var(--x, fallback) auflösen, auch verschachtelt. */
function css_aufloesen(array $vars): array
{
    $loesen = static function (string $wert, int $tiefe) use (&$loesen, $vars): string {
        if ($tiefe > 12 || !str_contains($wert, 'var(')) {
            return $wert;
        }
        $neu = (string) preg_replace_callback('~var\(\s*(--[A-Za-z0-9_-]+)\s*(?:,\s*([^()]*(?:\([^()]*\))?[^()]*))?\)~', static function ($m) use ($vars, $loesen, $tiefe) {
            if (isset($vars[$m[1]])) {
                return $loesen($vars[$m[1]], $tiefe + 1);
            }
            return isset($m[2]) ? trim($m[2]) : $m[0];
        }, $wert);
        return $neu === $wert ? $wert : $loesen($neu, $tiefe + 1);
    };
    $raus = [];
    foreach ($vars as $n => $w) {
        $raus[$n] = trim($loesen($w, 0));
    }
    return $raus;
}

/** @return array<int,array{familie:string,url:string,gewicht:string,stil:string}> */
function css_schriften(string $css): array
{
    $raus = [];
    if (preg_match_all('~@font-face\s*\{([^}]*)\}~i', css_ohne_kommentare($css), $m)) {
        foreach ($m[1] as $block) {
            $familie = preg_match('~font-family\s*:\s*["\']?([^;"\']+)~i', $block, $f) ? trim($f[1]) : '';
            $url = '';
            if (preg_match_all('~url\(\s*["\']?([^)"\']+)["\']?\s*\)~i', $block, $u)) {
                // woff2 bevorzugen
                usort($u[1], static fn($a, $b) => (int) !str_contains(strtolower($a), '.woff2') <=> (int) !str_contains(strtolower($b), '.woff2'));
                $url = $u[1][0];
            }
            $gewicht = preg_match('~font-weight\s*:\s*([0-9 ]+|normal|bold)~i', $block, $g) ? trim(strtr(strtolower($g[1]), ['normal' => '400', 'bold' => '700'])) : '400';
            $stil = preg_match('~font-style\s*:\s*italic~i', $block) ? 'italic' : 'normal';
            if ($familie !== '' && $url !== '' && !str_starts_with($url, 'data:')) {
                $raus[] = ['familie' => $familie, 'url' => $url, 'gewicht' => $gewicht, 'stil' => $stil];
            }
        }
    }
    return $raus;
}

function wert_ist_farbe(string $w): bool
{
    $w = strtolower(trim($w));
    return (bool) preg_match('~^(#[0-9a-f]{3,8}|rgba?\(|hsla?\(|oklch\(|oklab\(|lab\(|lch\(|color\(|hwb\()~', $w)
        || in_array($w, ['white', 'black'], true);
}

function wert_ist_mass(string $w): bool
{
    return (bool) preg_match('~^(0|[0-9.]+(px|rem|em|%))$~', trim($w));
}

function wert_ist_schrift(string $w): bool
{
    return (bool) preg_match('~["\']|,\s*(sans-serif|serif|monospace|system-ui)|^(system-ui|sans-serif|serif)$~i', $w);
}

/** Vorschlag, welche Variable welche Stellschraube bedient. */
function design_vorschlag(array $vars): array
{
    $art = static fn(string $k): string => WS_DESIGN_TOKENS[$k][1];
    $passt = static function (string $k, string $w) use ($art): bool {
        return match ($art($k)) {
            'farbe' => wert_ist_farbe($w),
            'mass' => wert_ist_mass($w),
            'schrift' => wert_ist_schrift($w),
            default => false,
        };
    };
    $raus = [];
    foreach (DESIGN_MUSTER as $k => $muster) {
        foreach ($muster as $re) {
            foreach ($vars as $name => $wert) {
                if (preg_match($re, strtolower($name)) && $passt($k, $wert)) {
                    $raus[$k] = $name;
                    continue 3;
                }
            }
        }
    }
    // Überschriften ohne eigene Schrift: wie der Text
    if (!isset($raus['font-head']) && isset($raus['font-body'])) {
        $raus['font-head'] = $raus['font-body'];
    }
    return $raus;
}

/* ---------------------------------------------------------- Hochladen --- */

/**
 * Nimmt eine hochgeladene Datei (ZIP, CSS, Markdown oder design.json) an und
 * analysiert sie. Legt das Ergebnis in ablage/design-import/<kennung>/ ab.
 * @return array{0:?string,1:?string} [Kennung, Fehler]
 */
function design_import_analysieren(array $datei): array
{
    $code = $datei['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return [null, 'Es wurde keine Datei ausgewählt.'];
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE || ($datei['size'] ?? 0) > DESIGN_UPLOAD_MAX) {
        return [null, 'Die Datei ist zu groß (höchstens ' . groesse_lesbar(min(DESIGN_UPLOAD_MAX, upload_grenze())) . ').'];
    }
    if ($code !== UPLOAD_ERR_OK || !is_uploaded_file((string) $datei['tmp_name'])) {
        return [null, 'Das Hochladen ist fehlgeschlagen (Code ' . (int) $code . ').'];
    }
    $name = (string) ($datei['name'] ?? 'design');
    $endung = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $tmp = (string) $datei['tmp_name'];
    $kopf = (string) file_get_contents($tmp, false, null, 0, 4);

    $kennung = date('ymd-His') . '-' . bin2hex(random_bytes(3));
    $ordner = pfad_ablage('design-import/' . $kennung);
    ordner_sicherstellen($ordner . '/schriften');
    design_import_aufraeumen();

    $css = [];      // Pfad => Inhalt
    $md = '';
    $readme = '';
    $dateien = [];  // Pfad im Archiv => Inhalt (nur Schriften)
    $fertig = null; // eine exportierte design.json

    if ($kopf === "PK\x03\x04" || $endung === 'zip') {
        if (!class_exists('ZipArchive')) {
            return [null, 'Auf diesem Server fehlt die PHP-Erweiterung „zip“. Bitte stattdessen die CSS-Datei des Design Systems hochladen (meist styles.css oder _ds_bundle.css).'];
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            return [null, 'Die ZIP-Datei lässt sich nicht öffnen — ist sie vollständig?'];
        }
        $gesamt = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            $pfad = (string) ($st['name'] ?? '');
            $basis = basename($pfad);
            if ($pfad === '' || str_ends_with($pfad, '/') || str_starts_with($basis, '._') || str_contains($pfad, '__MACOSX') || str_contains($pfad, 'node_modules')) {
                continue;
            }
            $e = strtolower(pathinfo($basis, PATHINFO_EXTENSION));
            if (!in_array($e, array_merge(['css', 'md', 'json'], DESIGN_SCHRIFT_ENDUNGEN), true)) {
                continue;
            }
            $gesamt += (int) ($st['size'] ?? 0);
            if ($gesamt > 80 * 1048576) {
                $zip->close();
                return [null, 'Das Archiv ist entpackt größer als 80 MB — das ist für ein Design ungewöhnlich viel.'];
            }
            $inhalt = (string) $zip->getFromIndex($i);
            if ($e === 'css') {
                $css[$pfad] = $inhalt;
            } elseif ($e === 'md') {
                $md .= "\n" . $inhalt;
                if ($readme === '' && preg_match('~(readme|design-system)\.md$~i', $basis)) {
                    $readme = $inhalt;
                }
            } elseif ($e === 'json' && $basis === 'design.json') {
                $fertig = json_decode($inhalt, true);
            } elseif (in_array($e, DESIGN_SCHRIFT_ENDUNGEN, true)) {
                $dateien[$pfad] = $inhalt;
            }
        }
        $zip->close();
    } elseif ($endung === 'css') {
        $css[$name] = (string) file_get_contents($tmp);
    } elseif ($endung === 'md') {
        $md = (string) file_get_contents($tmp);
        $readme = $md;
    } elseif ($endung === 'json') {
        $fertig = json_decode((string) file_get_contents($tmp), true);
        if (!is_array($fertig)) {
            return [null, 'Die JSON-Datei ist beschädigt.'];
        }
    } else {
        return [null, 'Dieses Format wird nicht angenommen. Möglich sind: ZIP-Export eines Claude Design Systems, eine CSS-Datei, DESIGN-SYSTEM.md oder eine exportierte design.json.'];
    }

    // Stellschrauben aus einer exportierten design.json direkt übernehmen
    $vars = [];
    if (is_array($fertig) && is_array($fertig['tokens'] ?? null)) {
        foreach ($fertig['tokens'] as $k => $w) {
            if (isset(WS_DESIGN_TOKENS[$k]) && is_scalar($w)) {
                $vars['--ws-' . $k] = (string) (is_bool($w) ? ($w ? '1' : '0') : $w);
            }
        }
    }

    // Einstiegsdateien zuerst (styles.css, tokens), damit :root dort gewinnt
    uksort($css, static function ($a, $b) {
        $rang = static fn(string $p): int => preg_match('~(^|/)(tokens?|_ds_bundle|styles)[^/]*\.css$~i', $p) ? 0 : (str_contains($p, 'token') ? 1 : 2);
        return [$rang($a), substr_count($a, '/'), $a] <=> [$rang($b), substr_count($b, '/'), $b];
    });
    $schriften = [];
    foreach ($css as $pfad => $inhalt) {
        $vars = css_variablen($inhalt, $vars);
        foreach (css_schriften($inhalt) as $s) {
            $s['css'] = $pfad;
            $schriften[] = $s;
        }
    }
    $vars += md_variablen($md);
    if (!$vars) {
        return [null, 'In der Datei stehen keine CSS-Variablen (--name: wert). Ein Claude Design System legt seine Farben, Schriften und Rundungen als Variablen in :root ab — bitte den vollständigen Export hochladen.'];
    }
    $vars = css_aufloesen($vars);
    if (count($vars) > 1500) {
        $vars = array_slice($vars, 0, 1500, true);
    }

    // Schriftdateien den @font-face-Regeln zuordnen und beiseitelegen
    $gefunden = [];
    foreach ($schriften as $s) {
        $url = (string) preg_replace('~[?#].*$~', '', $s['url']);
        $ziel = null;
        if ($dateien) {
            $kandidat = ltrim((string) preg_replace('~/+~', '/', dirname($s['css']) . '/' . $url), './');
            $kandidat = design_pfad_normalisieren($kandidat);
            if (isset($dateien[$kandidat])) {
                $ziel = $kandidat;
            } else {
                foreach (array_keys($dateien) as $p) {
                    if (basename($p) === basename($url)) {
                        $ziel = $p;
                        break;
                    }
                }
            }
        }
        if ($ziel === null) {
            continue;
        }
        $dateiname = preg_replace('/[^A-Za-z0-9._-]/', '-', basename($ziel));
        if (!in_array(strtolower(pathinfo($dateiname, PATHINFO_EXTENSION)), DESIGN_SCHRIFT_ENDUNGEN, true)) {
            continue;
        }
        file_put_contents($ordner . '/schriften/' . $dateiname, $dateien[$ziel]);
        $gefunden[] = ['familie' => $s['familie'], 'datei' => $dateiname, 'gewicht' => $s['gewicht'], 'stil' => $s['stil']];
    }

    $titel = '';
    if (preg_match('~^#\s+(.+)$~m', $readme, $t)) {
        $titel = trim((string) preg_replace('~[—–-].*$|\(.*\)|how to build.*$~i', '', $t[1]));
    }
    $titel = $titel ?: (is_array($fertig) ? (string) ($fertig['name'] ?? '') : '') ?: pathinfo($name, PATHINFO_FILENAME);

    json_schreiben($ordner . '/analyse.json', [
        'datei' => $name,
        'name' => mb_substr(trim($titel), 0, 60),
        'variablen' => $vars,
        'schriften' => $gefunden,
        'schriftRegeln' => count($schriften),
        'vorschlag' => design_vorschlag($vars),
        'cssDateien' => array_keys($css),
        'erstellt' => date('c'),
    ]);
    protokoll('Design hochgeladen und analysiert: ' . $name . ' (' . count($vars) . ' Variablen, ' . count($gefunden) . ' Schriftdateien)');
    return [$kennung, null];
}

function design_pfad_normalisieren(string $pfad): string
{
    $teile = [];
    foreach (explode('/', $pfad) as $t) {
        if ($t === '' || $t === '.') {
            continue;
        }
        if ($t === '..') {
            array_pop($teile);
        } else {
            $teile[] = $t;
        }
    }
    return implode('/', $teile);
}

function design_import_laden(string $kennung): ?array
{
    if (!preg_match('/^\d{6}-\d{6}-[0-9a-f]{6}$/', $kennung)) {
        return null;
    }
    return json_lesen(pfad_ablage('design-import/' . $kennung . '/analyse.json'));
}

/** Angefangene Importe, die älter als zwei Tage sind, wegräumen. */
function design_import_aufraeumen(): void
{
    foreach (glob(pfad_ablage('design-import/*'), GLOB_ONLYDIR) ?: [] as $o) {
        if (filemtime($o) < time() - 172800) {
            foreach (array_merge(glob($o . '/schriften/*') ?: [], glob($o . '/*') ?: []) as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($o . '/schriften');
            @rmdir($o);
        }
    }
}

/**
 * Aus Analyse und Zuordnung ein Design machen.
 * $zuordnung: Stellschraube => Variablenname ('' = Vorgabe behalten)
 * $eigen: Stellschraube => fester Wert (hat Vorrang)
 * @return array{0:?string,1:string[]} [neue Kennung, Fehler]
 */
function design_import_uebernehmen(string $kennung, string $name, array $zuordnung, array $eigen, bool $aurora, string $basis): array
{
    $a = design_import_laden($kennung);
    if (!$a) {
        return [null, ['Dieser Import ist abgelaufen. Bitte die Datei noch einmal hochladen.']];
    }
    $grund = design_laden($basis) ?? design_laden('aurora');
    $tokens = $grund['tokens'];
    $vars = $a['variablen'];
    $f = [];

    foreach (WS_DESIGN_TOKENS as $k => [$label, $art]) {
        if (in_array($art, ['schalter', 'auswahl', 'zahl'], true)) {
            continue;
        }
        $wert = trim((string) ($eigen[$k] ?? ''));
        if ($wert === '' && ($zuordnung[$k] ?? '') !== '' && isset($vars[$zuordnung[$k]])) {
            $wert = (string) $vars[$zuordnung[$k]];
        }
        if ($wert === '') {
            continue;
        }
        if ($art === 'farbe' && !wert_ist_farbe($wert)) {
            $f[] = $label . ': „' . $wert . '“ ist keine Farbe.';
            continue;
        }
        if ($art === 'mass' && !wert_ist_mass($wert)) {
            $f[] = $label . ': „' . $wert . '“ ist kein Maß (z. B. 12px).';
            continue;
        }
        $tokens[$k] = ws_css_wert($wert);
    }
    if ($f) {
        return [null, $f];
    }
    // Grundton aus der Helligkeit des Hintergrunds
    $bg = farbe_rgb((string) $tokens['bg']);
    $tokens['modus'] = $bg && farbe_helligkeit($bg) > .4 ? 'hell' : 'dunkel';
    $tokens['aurora'] = $aurora;
    if ($tokens['modus'] === 'hell') {
        $tokens['aurora-staerke'] = min((int) $tokens['aurora-staerke'], 12);
    }

    $name = trim($name) ?: ($a['name'] ?: 'Hochgeladenes Design');
    $id = design_kennung($name);
    ordner_sicherstellen(pfad_design($id));
    $fonts = [];
    foreach ((array) $a['schriften'] as $s) {
        $quelle = pfad_ablage('design-import/' . $kennung . '/schriften/' . $s['datei']);
        if (is_file($quelle)) {
            ordner_sicherstellen(pfad_design($id . '/schriften'));
            copy($quelle, pfad_design($id . '/schriften/' . $s['datei']));
            $fonts[] = $s;
        }
    }
    // Nur die Variablen behalten, die im Design System in :root standen — ohne die --ws-* der Webseite
    $behalten = array_filter($vars, static fn($w, $n) => !str_starts_with((string) $n, '--ws-') && strlen((string) $w) < 300, ARRAY_FILTER_USE_BOTH);

    design_speichern($id, [
        'name' => mb_substr($name, 0, 60),
        'beschreibung' => 'Aus „' . $a['datei'] . '“ übernommen: ' . count($vars) . ' Variablen, ' . count($fonts) . ' Schriftdateien.',
        'quelle' => 'Claude Design System (' . $a['datei'] . ')',
        'schutz' => false,
        'tokens' => $tokens,
        'fonts' => $fonts,
        'variablen' => array_slice($behalten, 0, 600, true),
        'erstellt' => date('c'),
    ]);
    protokoll('Design angelegt: ' . $name);
    return [$id, []];
}

/* ============================================================ Themes === */

/** Theme des im Entwurf gewählten Designs (für eigene Sektionstypen, Varianten, Zusatzfelder). */
function theme_aktiv(): ?array
{
    if (!generator_laden()) {
        return null;
    }
    $d = design_laden(design_aktiv());
    return $d ? ws_theme($d) : null;
}

/** Alle Dateien eines Design-Ordners kopieren (design.json schreibt der Aufrufer selbst). */
function design_ordner_kopieren(string $von, string $nach): void
{
    if (!is_dir($von)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($von, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($von) + 1));
        if (!$f->isFile() || $rel === 'design.json' || str_starts_with(basename($rel), '.')) {
            continue;
        }
        ordner_sicherstellen(dirname($nach . '/' . $rel));
        copy($f->getPathname(), $nach . '/' . $rel);
    }
}

/**
 * Liegt in der ZIP-Datei ein Theme-Paket? Dann den Präfix-Ordner liefern,
 * unter dem theme.json liegt ('' = oberste Ebene), sonst null.
 */
function theme_zip_praefix(ZipArchive $zip): ?string
{
    $beste = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = (string) $zip->getNameIndex($i);
        if (str_contains($name, '__MACOSX') || !preg_match('~^(([^/]+/)?)theme\.json$~', $name, $m)) {
            continue;
        }
        if ($beste === null || strlen($m[1]) < strlen($beste)) {
            $beste = $m[1];
        }
    }
    return $beste;
}

/**
 * Ein Theme-Paket (ZIP mit theme.json) installieren.
 * Übernommen werden nur erlaubte Dateiarten; PHP, .htaccess und alles
 * Unbekannte bleibt draußen. @return array{0:?string,1:string[]} [Kennung, Befunde/Fehler]
 */
function theme_installieren(string $zipDatei, string $dateiname): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipDatei) !== true) {
        return [null, ['Die ZIP-Datei lässt sich nicht öffnen — ist sie vollständig?']];
    }
    $praefix = theme_zip_praefix($zip);
    if ($praefix === null) {
        $zip->close();
        return [null, ['In der ZIP-Datei liegt keine theme.json.']];
    }
    $roh = json_decode((string) $zip->getFromName($praefix . 'theme.json'), true);
    $dateien = [];
    $gesamt = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $st = $zip->statIndex($i);
        $pfad = (string) ($st['name'] ?? '');
        if ($pfad === '' || str_ends_with($pfad, '/') || !str_starts_with($pfad, $praefix) || str_contains($pfad, '__MACOSX')) {
            continue;
        }
        $gesamt += (int) ($st['size'] ?? 0);
        if ($gesamt > 80 * 1048576) {
            $zip->close();
            return [null, ['Das Theme ist entpackt größer als 80 MB — bitte Bilder und Videos verkleinern.']];
        }
        $dateien[substr($pfad, strlen($praefix))] = (string) $zip->getFromIndex($i);
    }
    $zip->close();
    [$id, $befunde] = ws_theme_installieren(is_array($roh) ? $roh : [], $dateien, 'Theme-Paket ' . basename($dateiname));
    if ($id) {
        protokoll('Theme installiert: ' . (design_laden($id)['name'] ?? $id) . ' (' . count($dateien) . ' Dateien)');
    }
    return [$id, $befunde];
}

/** Ein Design als ZIP-Paket (theme.json + Dateien) — lässt sich in jedes Portal wieder hochladen. */
function theme_exportieren(string $id): ?string
{
    $roh = json_lesen(pfad_design($id . '/design.json'));
    if (!$roh || !class_exists('ZipArchive')) {
        return null;
    }
    unset($roh['schutz'], $roh['id']);
    $roh['theme'] ??= ['version' => 1];
    $tmp = tempnam(sys_get_temp_dir(), 'theme');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        return null;
    }
    $zip->addFromString($id . '/theme.json', json_encode($roh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    $ordner = pfad_design($id);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ordner, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($ordner) + 1));
        if ($f->isFile() && $rel !== 'design.json' && ws_theme_pfad($rel) === $rel) {
            $zip->addFile($f->getPathname(), $id . '/' . $rel);
        }
    }
    $zip->close();
    return $tmp;
}
