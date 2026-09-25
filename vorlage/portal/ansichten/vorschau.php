<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Vorschau des Entwurfs in verschiedenen Bildschirmbreiten.
 * Erwartet: $seiteWahl, $moeglich, $designWahl
 */
$basis = 'vorschau.php/';
$anker = preg_match('/^[a-z0-9-]+$/', (string) ($_GET['anker'] ?? '')) ? '#' . $_GET['anker'] : '';
$quelle = $basis . ($seiteWahl !== '' ? $seiteWahl . '/' : '') . ($designWahl !== '' ? '?design=' . rawurlencode($designWahl) : '') . $anker;
$seiten = ['' => 'Startseite', 'impressum' => 'Impressum', 'datenschutz' => 'Datenschutz'] + (modul('barrierefreiheit') ? ['barrierefreiheit' => 'Barrierefreiheit'] : []);
$designName = $designWahl !== '' ? (string) (design_laden($designWahl)['name'] ?? $designWahl) : '';
?>
<?php if (!$moeglich): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div>Für die Vorschau fehlt der Generator oder eine Vorlage im privaten Ordner. Näheres unter „Systemprüfung“.</div>
</div>
<?php else: ?>
<div class="vorschau-leiste">
  <nav class="filterleiste filterleiste--ohne-abstand" aria-label="Seite wählen">
<?php foreach ($seiten as $k => $name): ?>
    <a class="filter" href="<?= h(url_zu('vorschau', array_filter(['p' => $k, 'design' => $designWahl]))) ?>"<?= $seiteWahl === $k ? ' aria-current="page"' : '' ?>><?= h($name) ?></a>
<?php endforeach; ?>
  </nav>
  <div class="knopfzeile" role="group" aria-label="Bildschirmbreite">
    <button type="button" class="filter" data-geraet="100%" aria-pressed="true">Computer</button>
    <button type="button" class="filter" data-geraet="820px" aria-pressed="false">Tablet</button>
    <button type="button" class="filter" data-geraet="390px" aria-pressed="false">Handy</button>
    <button type="button" class="minitaste" data-neu-laden title="Neu laden" aria-label="Vorschau neu laden">↻</button>
    <a class="knopf knopf--still knopf--klein" href="<?= h($quelle) ?>" target="_blank" rel="noopener">In neuem Tab ↗</a>
  </div>
</div>
<?php if ($designName !== ''): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>Probeansicht im Design „<?= h($designName) ?>“ — gewählt ist es damit noch nicht. <a href="<?= h(url_zu('design')) ?>">Zur Designauswahl</a> · <a href="<?= h(url_zu('vorschau', array_filter(['p' => $seiteWahl]))) ?>">Gewähltes Design zeigen</a></div>
</div>
<?php endif; ?>
<p class="still klein">Die Vorschau zeigt den gespeicherten Entwurf — so, wie die Webseite nach dem Veröffentlichen aussieht. Das Kontaktformular sendet hier nichts, offene Punkte der Rechtstexte sind rot umrandet.</p>
<div class="vorschau-buehne">
  <iframe class="vorschau-rahmen" src="<?= h($quelle) ?>" title="Vorschau der Webseite" data-vorschau-rahmen></iframe>
</div>
<?php endif; ?>
