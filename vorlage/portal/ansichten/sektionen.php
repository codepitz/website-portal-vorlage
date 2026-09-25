<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Liste der Sektionen der Startseite.
 * Erwartet: $sektionen, $geaendert
 */
$tok = h(token());
$knopf = static function (string $tat, string $id, string $beschriftung, string $klasse = 'minitaste', array $frage = [], bool $aus = false, string $titel = '') use ($tok): string {
    $attr = $frage ? ' data-titel="' . h($frage[0]) . '" data-ja="' . h($frage[1]) . '" data-frage="' . h($frage[2]) . '"' : '';
    return '<form method="post"' . $attr . '>'
        . '<input type="hidden" name="token" value="' . $tok . '"><input type="hidden" name="tat" value="' . h($tat) . '"><input type="hidden" name="id" value="' . h($id) . '">'
        . '<button type="submit" class="' . $klasse . '"' . ($aus ? ' disabled' : '') . ($titel !== '' ? ' title="' . h($titel) . '" aria-label="' . h($titel) . '"' : '') . '>' . $beschriftung . '</button></form>';
};
$nr = 0;
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">Startseite</p>
  <p class="leise">
    Die Startseite besteht aus Sektionen. Hier legen Sie fest, welche sichtbar sind, in welcher Reihenfolge sie stehen
    und welche oben im Menü der Webseite erscheinen (☰). Neue Bereiche fügen Sie unten hinzu.
  </p>
</div>

<?php if ($geaendert): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>
    Die Sektionen haben Änderungen, die noch nicht auf der Webseite stehen.
    <a href="<?= h(url_zu('vorschau')) ?>">Vorschau</a> · <a href="<?= h(url_zu('veroeffentlichen')) ?>">Änderungen ansehen</a>
  </div>
</div>
<?php endif; ?>

<ol class="sektionsliste">
<?php foreach ($sektionen as $i => $s):
    $typ = (string) ($s['typ'] ?? '');
    [$typName, , $einmalig, $zeichen] = sektion_typen()[$typ] ?? [$typ, '', true, '•'];
    $an = !empty($s['aktiv']);
    $menue = !empty($s['menue']['zeigen']);
    $url = url_zu('sektion', ['id' => $s['id']]);
?>
  <li class="sektion-zeile<?= $an ? '' : ' sektion-zeile--aus' ?>">
    <span class="sektion-nr"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
    <span class="sektion-zeichen" aria-hidden="true"><?= $zeichen ?></span>
    <a class="sektion-text" href="<?= h($url) ?>">
      <span class="sektion-titel"><?= h(sektion_titel($s)) ?></span>
      <span class="sektion-meta">
        <?= h($typName) ?> · <span class="mono">#<?= h((string) ($s['anker'] ?? '')) ?></span>
<?php if ($menue): ?> · Menü: „<?= h(trim((string) ($s['menue']['label'] ?? '')) ?: sektion_titel($s)) ?>“<?php endif; ?>
<?php if ($typ === 'kontakt' && modul('zeiten') && !empty($s['zeitenMenue']['zeigen'])): ?> · Menü: „<?= h(trim((string) ($s['zeitenMenue']['label'] ?? '')) ?: begriff('zeiten')) ?>“<?php endif; ?>
      </span>
    </a>
    <span class="sektion-status">
<?php if (!$an): ?><span class="pille pille--still">ausgeblendet</span><?php endif; ?>
<?php if (sektion_geaendert((string) $s['id'])): ?><span class="pille pille--akzent">geändert</span><?php endif; ?>
    </span>
    <span class="sektion-tasten">
      <?= $knopf('hoch', $s['id'], '↑', 'minitaste', [], $i === 0, 'Nach oben') ?>
      <?= $knopf('runter', $s['id'], '↓', 'minitaste', [], $i === count($sektionen) - 1, 'Nach unten') ?>
<?php if (!in_array($typ, ['hero', 'laufband'], true)): ?>
      <?= $knopf('menue', $s['id'], '☰', 'minitaste' . ($menue ? ' minitaste--an' : ''), [], false, $menue ? 'Aus dem Menü nehmen' : 'Im Menü zeigen') ?>
<?php endif; ?>
<?php if ($typ === 'kontakt' && modul('zeiten')): $zm = !empty($s['zeitenMenue']['zeigen']); ?>
      <?= $knopf('zeitenmenue', $s['id'], '◷', 'minitaste' . ($zm ? ' minitaste--an' : ''), [], false, $zm ? begriff('zeiten') . ' aus dem Menü nehmen' : begriff('zeiten') . ' als eigenen Menüpunkt zeigen') ?>
<?php endif; ?>
      <?= $knopf('umschalten', $s['id'], $an ? 'Ausblenden' : 'Einblenden', 'knopf knopf--still knopf--klein') ?>
      <a class="knopf knopf--zweit knopf--klein" href="<?= h($url) ?>">Bearbeiten</a>
<?php if (!$einmalig): ?>
      <?= $knopf('duplizieren', $s['id'], '⧉', 'minitaste', [], false, 'Kopieren') ?>
<?php endif; ?>
      <?= $knopf('loeschen', $s['id'], '✕', 'minitaste minitaste--weg', ['Sektion entfernen?', 'Ja, entfernen',
          '„' . sektion_titel($s) . '“ wird von der Startseite entfernt. Bis zum Veröffentlichen lässt sich das mit „Entwurf verwerfen“ zurücknehmen.'
          . (($v = anker_verweise((string) ($s['anker'] ?? ''))) ? ' Achtung, darauf verlinken noch: ' . implode(', ', $v) . '.' : '')], false, 'Entfernen') ?>
    </span>
  </li>
<?php endforeach; ?>
</ol>

<?php if (!$sektionen): ?>
<div class="tafel"><p class="leise">Die Startseite hat noch keine Sektionen. Fügen Sie unten die erste hinzu.</p></div>
<?php endif; ?>

<h2 class="abschnittstitel" id="hinzufuegen">Sektion hinzufügen</h2>
<p class="leise klein einleitung">Neue Sektionen werden vor „Kontakt“ eingefügt und sind zunächst ausgeblendet — so können Sie in Ruhe Inhalte eintragen.</p>

<div class="typwahl">
<?php foreach (sektion_typen() as $typ => [$name, $beschreibung, $einmalig, $zeichen, $gruppe]):
    if ($gruppe !== 'baustein') { continue; } ?>
  <form method="post" class="typkachel">
    <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="neu"><input type="hidden" name="typ" value="<?= h($typ) ?>">
    <button type="submit">
      <span class="typkachel-zeichen" aria-hidden="true"><?= $zeichen ?></span>
      <span class="typkachel-titel"><?= h($name) ?></span>
      <span class="typkachel-text"><?= h($beschreibung) ?></span>
    </button>
  </form>
<?php endforeach; ?>
</div>

<?php $themeTypen = array_filter(sektion_typen(), static fn($t) => $t[4] === 'theme'); ?>
<?php if ($themeTypen): ?>
<h3 class="abschnittstitel abschnittstitel--klein">Aus dem Design „<?= h((string) (sektion_theme()['name'] ?? '')) ?>“</h3>
<p class="leise klein einleitung">Diese Sektionen bringt das gewählte Design mit. In einem anderen Design bleiben sie gespeichert, werden aber nicht angezeigt.</p>
<div class="typwahl">
<?php foreach ($themeTypen as $typ => [$name, $beschreibung, $einmalig, $zeichen]):
    if (!typ_verfuegbar($typ)) { continue; } ?>
  <form method="post" class="typkachel">
    <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="neu"><input type="hidden" name="typ" value="<?= h($typ) ?>">
    <button type="submit">
      <span class="typkachel-zeichen" aria-hidden="true"><?= $zeichen ?></span>
      <span class="typkachel-titel"><?= h($name) ?></span>
      <span class="typkachel-text"><?= h($beschreibung) ?></span>
    </button>
  </form>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php $fehlende = array_filter(sektion_typen(), static fn($t, $k) => $t[4] === 'fest' && typ_verfuegbar($k), ARRAY_FILTER_USE_BOTH); ?>
<?php if ($fehlende): ?>
<h3 class="abschnittstitel abschnittstitel--klein">Feste Bereiche hinzufügen</h3>
<div class="typwahl typwahl--klein">
<?php foreach ($fehlende as $typ => [$name, $beschreibung, , $zeichen]): ?>
  <form method="post" class="typkachel">
    <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="neu"><input type="hidden" name="typ" value="<?= h($typ) ?>">
    <button type="submit">
      <span class="typkachel-zeichen" aria-hidden="true"><?= $zeichen ?></span>
      <span class="typkachel-titel"><?= h($name) ?></span>
      <span class="typkachel-text"><?= h($beschreibung) ?></span>
    </button>
  </form>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($geaendert): ?>
<hr class="trenner">
<form method="post" data-titel="Entwurf der Sektionen verwerfen?" data-ja="Ja, verwerfen"
      data-frage="Alle Sektionen zeigen danach wieder genau den Stand der Webseite — neue, entfernte und geänderte Sektionen eingeschlossen.">
  <input type="hidden" name="tat" value="verwerfen"><input type="hidden" name="token" value="<?= $tok ?>">
  <button type="submit" class="knopf knopf--gefahr-still knopf--klein">Entwurf der Sektionen verwerfen</button>
</form>
<?php endif; ?>
