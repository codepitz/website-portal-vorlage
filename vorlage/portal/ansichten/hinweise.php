<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Hinweise & Urlaub.
 * Erwartet: $eintraege
 */
$tok = h(token());
$pille = ['an' => 'pille--gut', 'geplant' => 'pille--warn', 'aus' => 'pille--still', 'abgelaufen' => 'pille--fehler'];
?>
<div class="einleitung einleitung--mit-knopf">
  <div>
    <p class="eyebrow eyebrow--leise">Leiste oder Fenster auf der Webseite</p>
    <p class="leise">
      Für Urlaub, Schließzeiten, Telefonstörungen oder Neuigkeiten. Hinweise mit Zeitraum erscheinen und
      verschwinden von selbst. Vorbereitete Hinweise lassen sich jedes Jahr wiederverwenden.
    </p>
  </div>
  <a href="<?= h(url_zu('hinweis')) ?>" class="knopf">Neuer Hinweis</a>
</div>

<?php if (!$eintraege): ?>
<div class="tafel leer">
  <h2>Noch keine Hinweise</h2>
  <p class="leise">Ein typischer erster Hinweis: „Urlaub vom 4. bis 15. August — in dringenden Fällen …“ als Leiste oben auf der Seite.</p>
  <a href="<?= h(url_zu('hinweis')) ?>" class="knopf knopf--zweit abstand-oben">Ersten Hinweis anlegen</a>
</div>
<?php else: ?>
<ul class="hinweisliste">
<?php foreach ($eintraege as $h):
    [$zustand, $wort, $erklaerung] = hinweis_status($h);
    $an = hinweis_ist_an((string) $h['id']); ?>
  <li class="hinweiszeile hinweiszeile--<?= h((string) ($h['art'] ?? 'info')) ?>">
    <div class="hinweiszeile-text">
      <div class="knopfzeile">
        <span class="pille <?= $pille[$zustand] ?>"><span class="punkt" aria-hidden="true"></span> <?= h($wort) ?></span>
        <span class="still klein"><?= h(HINWEIS_ARTEN[$h['art'] ?? 'info'][0] ?? '') ?> · <?= h(HINWEIS_DARSTELLUNG[$h['darstellung'] ?? 'banner'] ?? '') ?></span>
      </div>
      <a class="hinweiszeile-titel" href="<?= h(url_zu('hinweis', ['id' => $h['id']])) ?>"><?= h(str_replace("\n", ' ', (string) $h['titel'])) ?></a>
      <p class="leise klein"><?= h($erklaerung) ?></p>
    </div>
    <div class="hinweiszeile-tasten">
      <form method="post">
        <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="id" value="<?= h((string) $h['id']) ?>">
        <input type="hidden" name="tat" value="<?= $an ? 'aus' : 'an' ?>">
        <button type="submit" class="knopf knopf--klein <?= $an ? 'knopf--still' : 'knopf--zweit' ?>"<?= !$an && $zustand === 'abgelaufen' ? ' disabled' : '' ?>><?= $an ? 'Ausschalten' : 'Einschalten' ?></button>
      </form>
      <a class="knopf knopf--still knopf--klein" href="<?= h(url_zu('hinweis', ['id' => $h['id']])) ?>">Bearbeiten</a>
      <form method="post">
        <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="id" value="<?= h((string) $h['id']) ?>"><input type="hidden" name="tat" value="duplizieren">
        <button type="submit" class="minitaste" title="Kopieren" aria-label="Kopieren">⧉</button>
      </form>
      <form method="post" data-titel="Hinweis löschen?" data-ja="Ja, löschen" data-frage="Der Hinweis wird aus der Ablage entfernt<?= $an ? ' und beim nächsten Veröffentlichen von der Webseite genommen' : '' ?>.">
        <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="id" value="<?= h((string) $h['id']) ?>"><input type="hidden" name="tat" value="loeschen">
        <button type="submit" class="minitaste minitaste--weg" title="Löschen" aria-label="Löschen">✕</button>
      </form>
    </div>
  </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
