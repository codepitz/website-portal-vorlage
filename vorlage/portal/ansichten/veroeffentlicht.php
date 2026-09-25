<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Rückmeldung nach dem Veröffentlichen.
 * Erwartet: $ergebnis
 */
$b = $ergebnis['bericht'];
$website = rtrim((string) konfig('website'), '/');
?>
<section class="tafel tafel--akzent">
  <p class="eyebrow">Fertig</p>
  <h1><?= $ergebnis['bereiche'] ? 'Steht im Netz' : 'Webseite neu erzeugt' ?></h1>
  <p>
    <?= (int) $b['seiten'] ?> Seiten geschrieben<?= $b['geaendert'] ? ', davon ' . (int) $b['geaendert'] . ' geändert' : '' ?><?= $b['neu'] ? ' und ' . (int) $b['neu'] . ' neu' : '' ?>
    — in <?= h(number_format((float) $b['dauer'], 0, ',', '.')) ?> Millisekunden.
<?php if (!empty($b['schriften'])): ?>
    <?= (int) $b['schriften'] ?> Schriftdateien übertragen.
<?php endif; ?>
  </p>
  <div class="knopfzeile">
<?php if ($website !== ''): ?>
    <a href="<?= h($website) ?>" class="knopf knopf--hell" target="_blank" rel="noopener">Webseite ansehen ↗</a>
<?php endif; ?>
    <a href="<?= h(url_zu('start')) ?>" class="knopf knopf--umriss-hell">Zur Übersicht</a>
  </div>
</section>

<?php if ($ergebnis['bereiche']): ?>
<section class="tafel">
  <p class="eyebrow eyebrow--leise">Veröffentlicht</p>
  <div class="knopfzeile">
<?php foreach ($ergebnis['bereiche'] as $datei): ?>
    <span class="pille pille--gut"><?= h(datei_titel($datei)) ?></span>
<?php endforeach; ?>
  </div>
<?php if (!empty($ergebnis['sicherung'])): ?>
  <p class="still klein abstand-oben">Der vorherige Stand ist gesichert (<?= h(basename($ergebnis['sicherung'])) ?>) und lässt sich unter „Veröffentlichen“ zurückholen.</p>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if (!empty($b['warnungen'])): ?>
<section class="tafel">
  <p class="eyebrow eyebrow--leise">Rückmeldung des Generators</p>
  <h2>Offene Punkte</h2>
  <p class="leise klein">Die Webseite steht — an diesen Stellen fehlt aber noch etwas.</p>
  <ul class="pruefliste">
<?php foreach ($b['warnungen'] as $w): ?>
    <li><span class="pille pille--warn">offen</span> <span><?= h($w) ?></span></li>
<?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
