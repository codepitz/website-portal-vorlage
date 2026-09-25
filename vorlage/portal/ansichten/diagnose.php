<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Systemprüfung. Erwartet: $gruppen, $bilanz */
$zeichen = ['gut' => '✓', 'warnung' => '!', 'fehler' => '✕'];
$pille = ['gut' => 'pille--gut', 'warnung' => 'pille--warn', 'fehler' => 'pille--fehler'];
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">Server, Ordner, Rechte</p>
  <p class="leise">Prüft, ob das Portal auf diesem Server alles vorfindet, was es zum Veröffentlichen braucht — und ob der private Bereich wirklich privat ist. Diese Seite ändert nichts.</p>
</div>

<?php if ($bilanz['fehler'] > 0): ?>
<section class="tafel tafel--fehler">
  <p class="eyebrow">Handlungsbedarf</p>
  <h2><?= $bilanz['fehler'] === 1 ? 'Ein Punkt muss' : $bilanz['fehler'] . ' Punkte müssen' ?> behoben werden</h2>
  <p class="leise">Bei jedem Punkt unten steht, was zu tun ist.</p>
</section>
<?php elseif ($bilanz['warnung'] > 0): ?>
<section class="tafel">
  <p class="eyebrow eyebrow--leise">Ergebnis</p>
  <h2>Läuft — mit <?= $bilanz['warnung'] === 1 ? 'einem Hinweis' : $bilanz['warnung'] . ' Hinweisen' ?></h2>
</section>
<?php else: ?>
<section class="tafel tafel--akzent">
  <p class="eyebrow">Ergebnis</p>
  <h2>Alles in Ordnung</h2>
  <p>Alle <?= $bilanz['gut'] ?> Prüfungen bestanden.</p>
</section>
<?php endif; ?>

<?php foreach ($gruppen as $gruppe => $punkte): ?>
<h2 class="abschnittstitel"><?= h($gruppe) ?></h2>
<div class="pruefgruppe">
<?php foreach ($punkte as $pp): ?>
  <div class="pruefpunkt">
    <span class="pille <?= $pille[$pp['art']] ?> pille--quadrat" title="<?= $pp['art'] === 'gut' ? 'In Ordnung' : ($pp['art'] === 'warnung' ? 'Hinweis' : 'Fehler') ?>"><?= $zeichen[$pp['art']] ?></span>
    <span>
      <span class="pruefpunkt-titel"><?= h($pp['titel']) ?></span>
      <span class="pruefpunkt-wert mono"><?= h($pp['wert']) ?></span>
    </span>
    <span class="klein leise"><?= h($pp['text']) ?></span>
  </div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
