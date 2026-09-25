<?php
/**
 * Theme-Werkzeug für die Kommandozeile.
 *
 *   php privat/theme-werkzeug.php liste
 *   php privat/theme-werkzeug.php installieren <theme-ordner> [--kennung=<id>] [--verwenden]
 *   php privat/theme-werkzeug.php pruefen <kennung>
 *   php privat/theme-werkzeug.php vorschau <kennung> <zielordner>
 *
 * installieren  legt ein Theme aus einem Ordner mit theme.json an (wie der
 *               ZIP-Upload im Portal). --verwenden wählt es zusätzlich in
 *               daten/allgemein.json — gedacht für neue Projekte, bevor das
 *               Portal zum ersten Mal läuft.
 * pruefen       parst alle Vorlagen, meldet fehlende Dateien und Typen.
 * vorschau      baut die Webseite mit diesem Design in einen beliebigen
 *               Ordner, ohne Inhalte oder die echte Webseite anzufassen.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bauen.php';

$args = array_slice($argv, 1);
$optionen = [];
$frei = [];
foreach ($args as $a) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $a, $m)) {
        $optionen[$m[1]] = $m[2] ?? true;
    } else {
        $frei[] = $a;
    }
}
$befehl = $frei[0] ?? '';

try {
    switch ($befehl) {
        case 'liste':
            foreach (glob(ws_design_ordner() . '/*/design.json') ?: [] as $datei) {
                $id = basename(dirname($datei));
                $d = ws_design_laden($id);
                $t = ws_theme($d);
                printf("%-24s %-28s %s\n", $id, $d['name'], $t ? 'Theme (' . count(glob($t['ordner'] . '/vorlagen/{,*/}*.html', GLOB_BRACE) ?: []) . ' Vorlagen)' : 'nur Stellschrauben');
            }
            exit(0);

        case 'installieren':
            $quelle = $frei[1] ?? '';
            if ($quelle === '' || !is_dir($quelle)) {
                throw new RuntimeException('Aufruf: php theme-werkzeug.php installieren <theme-ordner> [--kennung=<id>] [--verwenden]');
            }
            [$roh, $dateien] = ws_theme_ordner_lesen($quelle);
            [$id, $befunde] = ws_theme_installieren($roh, $dateien, 'Theme-Ordner ' . basename(rtrim($quelle, '/')), (string) ($optionen['kennung'] ?? ''));
            if (!$id) {
                throw new RuntimeException(implode("\n", $befunde));
            }
            echo "Installiert: $id (" . count($dateien) . " Dateien)\n";
            if (!empty($optionen['verwenden'])) {
                $datei = __DIR__ . '/daten/allgemein.json';
                $a = json_decode((string) file_get_contents($datei), true) ?: [];
                $a['design'] = $id;
                file_put_contents($datei, json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                echo "Gewählt in daten/allgemein.json.\n";
            }
            if ($befunde) {
                echo "\nBefunde:\n• " . implode("\n• ", $befunde) . "\n";
                exit(2);
            }
            exit(0);

        case 'pruefen':
            $id = $frei[1] ?? '';
            if (!is_file(ws_design_ordner() . '/' . $id . '/design.json')) {
                throw new RuntimeException("Das Design „{$id}“ gibt es nicht. Vorhandene: php theme-werkzeug.php liste");
            }
            $befunde = ws_theme_pruefen(ws_design_laden($id));
            echo $befunde ? "• " . implode("\n• ", $befunde) . "\n" : "Keine Befunde.\n";
            exit($befunde ? 2 : 0);

        case 'vorschau':
            [$id, $ziel] = [$frei[1] ?? '', $frei[2] ?? ''];
            if ($id === '' || $ziel === '') {
                throw new RuntimeException('Aufruf: php theme-werkzeug.php vorschau <kennung> <zielordner>');
            }
            if (!is_dir($ziel)) {
                mkdir($ziel, 0755, true);
            }
            $grund = ws_daten_ordner(__DIR__ . '/daten');
            $laden = static function (string $name) use ($grund, $id): array {
                $d = $grund($name);
                if ($name === 'allgemein') {
                    $d['design'] = $id;
                }
                return $d;
            };
            $bericht = ws_bauen(realpath($ziel) ?: $ziel, $laden);
            printf("%d Seiten nach %s — Design „%s“, %d Theme-Dateien\n", $bericht['seiten'], $ziel, $id, $bericht['themeDateien']);
            $eigene = array_filter($bericht['warnungen'], static fn($w) => str_starts_with($w, 'Design'));
            if ($eigene) {
                echo "\nZum Design:\n• " . implode("\n• ", $eigene) . "\n";
            }
            exit(0);

        default:
            fwrite(STDERR, "Befehle: liste | installieren <ordner> [--kennung=<id>] [--verwenden] | pruefen <kennung> | vorschau <kennung> <zielordner>\n");
            exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . "\n");
    exit(1);
}
