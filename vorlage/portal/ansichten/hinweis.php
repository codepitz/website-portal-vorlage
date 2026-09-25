<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Einen Hinweis anlegen oder bearbeiten.
 * Erwartet: $id, $eintrag
 */
$an = $id !== null && hinweis_ist_an($id);
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise"><a href="<?= h(url_zu('hinweise')) ?>">← <?= h(begriff('hinweise')) ?></a></p>
<?php if ($an): ?>
  <p class="leise">Dieser Hinweis ist eingeschaltet. Änderungen erscheinen nach dem Veröffentlichen.</p>
<?php endif; ?>
</div>

<form method="post" data-inhaltsformular>
  <input type="hidden" name="tat" value="speichern">
  <input type="hidden" name="token" value="<?= h(token()) ?>">

  <div class="feldgitter">
    <?= felder_rendern(hinweis_felder(), $eintrag, 'd') ?>
  </div>

  <div class="speicherleiste">
    <span class="speicherleiste-text">
<?php if (!$an): ?>
      <label class="schalter schalter--klein">
        <input type="checkbox" name="einschalten" value="1">
        <span class="schalter-bahn" aria-hidden="true"><span class="schalter-punkt"></span></span>
        <span class="schalter-text">Direkt einschalten</span>
      </label>
<?php else: ?>
      <span data-ungespeichert hidden><strong>Noch nicht gespeichert.</strong></span>
<?php endif; ?>
    </span>
    <a href="<?= h(url_zu('hinweise')) ?>" class="knopf knopf--geist">Abbrechen</a>
    <button type="submit" class="knopf">Speichern</button>
  </div>
</form>
