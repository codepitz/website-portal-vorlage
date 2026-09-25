<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Protokoll. Erwartet: $eintraege */
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">privat/ablage/protokoll.log</p>
  <p class="leise">Wer wann angemeldet war, was gespeichert, veröffentlicht oder hochgeladen hat. Die letzten <?= count($eintraege) ?> Einträge, neueste zuerst.</p>
</div>

<?php if (!$eintraege): ?>
<div class="tafel leer"><p class="leise">Noch keine Einträge.</p></div>
<?php else: ?>
<div class="tabelle-huelle">
  <table class="tabelle tabelle--dicht">
    <thead><tr><th>Zeit</th><th>Wer</th><th>Was</th></tr></thead>
    <tbody>
<?php foreach ($eintraege as $e): ?>
      <tr>
        <td class="mono nowrap klein"><?= h(date('d.m.Y H:i', strtotime($e['zeit']) ?: time())) ?></td>
        <td class="nowrap"><?= h((string) (benutzer_holen($e['wer'])['anzeige'] ?? $e['wer'])) ?></td>
        <td class="klein"><?= h($e['was']) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
