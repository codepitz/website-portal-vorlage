<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Ein Inhaltsbereich zum Bearbeiten.
 * Erwartet: $id, $b, $werte, $geaendert, $konflikt, $stand
 */
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise"><?= h($b['wo']) ?></p>
  <p class="leise"><?= h($b['beschreibung']) ?></p>
</div>

<?php if (!empty($b['hinweis'])): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div><?= h($b['hinweis']) ?></div>
</div>
<?php endif; ?>

<?php if ($geaendert): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>Hier liegt ein Entwurf, der noch nicht auf der Webseite steht. <a href="<?= h(url_zu('veroeffentlichen')) ?>">Änderungen ansehen</a> · <a href="<?= h(url_zu('vorschau')) ?>">Vorschau</a></div>
</div>
<?php endif; ?>

<form method="post" data-inhaltsformular>
  <input type="hidden" name="tat" value="speichern">
  <input type="hidden" name="token" value="<?= h(token()) ?>">
  <input type="hidden" name="stand" value="<?= h($stand) ?>">

  <div class="feldgitter">
    <?= felder_rendern($b['felder'], $werte, 'd') ?>
  </div>

  <div class="speicherleiste">
    <span class="speicherleiste-text">
      <span data-ungespeichert hidden><strong>Noch nicht gespeichert.</strong> </span>
      Gespeichert wird als Entwurf — sichtbar wird es erst durch „Veröffentlichen“.
    </span>
<?php if ($konflikt): ?>
    <input type="hidden" name="erzwingen" value="1">
    <button type="submit" class="knopf knopf--gefahr">Trotzdem speichern</button>
<?php else: ?>
    <button type="submit" class="knopf">Speichern</button>
<?php endif; ?>
  </div>
</form>

<?php if ($geaendert): ?>
<form method="post" class="abstand-oben" data-titel="Entwurf verwerfen?" data-ja="Ja, verwerfen"
      data-frage="Dieser Bereich zeigt danach wieder genau das, was auf der Webseite steht. Die hier gemachten Änderungen sind dann weg.">
  <input type="hidden" name="tat" value="verwerfen">
  <input type="hidden" name="token" value="<?= h(token()) ?>">
  <button type="submit" class="knopf knopf--gefahr-still knopf--klein">Entwurf dieses Bereichs verwerfen</button>
</form>
<?php endif; ?>
