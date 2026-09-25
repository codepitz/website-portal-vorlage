<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Ein Design bearbeiten.
 * Erwartet: $id, $d (Design mit allen Vorgaben), $werte
 */
$kontraste = design_kontraste($werte['tokens'] ?? $d['tokens']);
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise"><a href="<?= h(url_zu('design')) ?>">← Design</a> · <?= h((string) ($d['quelle'] ?? '')) ?></p>
  <p class="leise">
    Jede Stellschraube wirkt auf die ganze Webseite. Tipp aus dem Aurora-Stil: Für einen neuen Look reicht oft schon die
    <strong>Akzentfarbe</strong> (und die Schrift darauf). Gespeichert wird sofort in diesem Design — ist es auf der Webseite
    in Gebrauch, erscheint die Änderung nach „Webseite neu erzeugen“ bzw. dem nächsten Veröffentlichen.
  </p>
</div>

<div class="spalten spalten--designimport">
  <form method="post" data-inhaltsformular class="stapel">
    <input type="hidden" name="tat" value="speichern">
    <input type="hidden" name="token" value="<?= h(token()) ?>">
    <div class="feldgitter">
      <?= felder_rendern(design_felder(), $werte, 'd') ?>
    </div>
    <div class="speicherleiste">
      <span class="speicherleiste-text"><span data-ungespeichert hidden><strong>Noch nicht gespeichert.</strong> </span>Die Vorschau zeigt den gespeicherten Stand.</span>
      <a class="knopf knopf--geist" href="<?= h(url_zu('vorschau', ['design' => $id])) ?>">Vorschau</a>
      <button type="submit" class="knopf">Speichern</button>
    </div>
  </form>
  <aside class="stapel designimport-seite">
    <section class="tafel">
      <p class="eyebrow eyebrow--leise">Lesbarkeit</p>
      <ul class="pruefliste">
<?php foreach ($kontraste as $kk): ?>
        <li><span class="pille <?= $kk['ok'] ? 'pille--gut' : 'pille--warn' ?>"><?= h(number_format($kk['wert'], 1, ',', '')) ?>:1</span> <span class="klein"><?= h($kk['was']) ?><?= $kk['ok'] ? '' : ' — empfohlen mindestens ' . h(number_format($kk['min'], 1, ',', '')) . ':1' ?></span></li>
<?php endforeach; ?>
      </ul>
      <p class="still klein">Nach WCAG: 4,5:1 für Text, 3:1 für große Elemente.</p>
    </section>
<?php if (!empty($d['fonts'])): ?>
    <section class="tafel">
      <p class="eyebrow eyebrow--leise">Schriftdateien</p>
      <ul class="verlauf">
<?php foreach ($d['fonts'] as $f): ?>
        <li><span class="mono klein"><?= h((string) $f['datei']) ?></span> <span class="still klein">— <?= h((string) $f['familie']) ?>, <?= h((string) $f['gewicht']) ?></span></li>
<?php endforeach; ?>
      </ul>
      <p class="still klein">Im Feld „Schrift“ mit dem Familiennamen ansprechen, z. B. <span class="mono">"<?= h((string) $d['fonts'][0]['familie']) ?>", sans-serif</span>.</p>
    </section>
<?php endif; ?>
  </aside>
</div>
