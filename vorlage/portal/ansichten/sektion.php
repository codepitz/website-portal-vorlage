<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Eine Sektion bearbeiten.
 * Erwartet: $id, $s, $felder, $werte, $konflikt, $stand, $geaendert
 */
[$typName, $typText] = sektion_typen()[$s['typ']] ?? [$s['typ'], ''];
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise"><a href="<?= h(url_zu('sektionen')) ?>">← Sektionen</a> · <?= h($typName) ?></p>
  <p class="leise"><?= h($typText) ?></p>
</div>

<?php if (empty($s['aktiv'])): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>Diese Sektion ist ausgeblendet. Zum Anzeigen oben „Auf der Webseite zeigen“ einschalten, speichern und veröffentlichen.</div>
</div>
<?php elseif ($geaendert): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>Diese Sektion hat Änderungen, die noch nicht auf der Webseite stehen.
    <a href="<?= h(url_zu('vorschau', ['anker' => (string) $s['anker']])) ?>">In der Vorschau ansehen</a></div>
</div>
<?php endif; ?>

<form method="post" data-inhaltsformular>
  <input type="hidden" name="tat" value="speichern">
  <input type="hidden" name="token" value="<?= h(token()) ?>">
  <input type="hidden" name="stand" value="<?= h($stand) ?>">

  <div class="feldgitter">
    <?= felder_rendern($felder, $werte, 'd') ?>
  </div>

  <div class="speicherleiste">
    <span class="speicherleiste-text">
      <span data-ungespeichert hidden><strong>Noch nicht gespeichert.</strong> </span>
      Gespeichert wird als Entwurf — sichtbar wird es erst durch „Veröffentlichen“.
    </span>
    <a class="knopf knopf--geist" href="<?= h(url_zu('vorschau', ['anker' => (string) $s['anker']])) ?>">Vorschau</a>
<?php if ($konflikt): ?>
    <input type="hidden" name="erzwingen" value="1">
    <button type="submit" class="knopf knopf--gefahr">Trotzdem speichern</button>
<?php else: ?>
    <button type="submit" class="knopf">Speichern</button>
<?php endif; ?>
  </div>
</form>
