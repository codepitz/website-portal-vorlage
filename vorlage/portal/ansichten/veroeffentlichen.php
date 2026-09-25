<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Änderungen ansehen und auf die Webseite schieben.
 * Erwartet: $aenderungen, $punkte, $probleme, $sicherungen, $letzte
 */
$anzahl = array_sum(array_map('count', $aenderungen));
$tok = h(token());
$domain = rtrim((string) (live_laden('allgemein')['domain'] ?? ''), '/');
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">Entwurf → Webseite</p>
  <p class="leise">
    Beim Veröffentlichen werden die Inhalte an ihren Platz geschrieben und die Webseite neu erzeugt. Vorher legt das
    Portal eine Sicherung an — geht beim Bauen etwas schief, wird der alte Stand automatisch zurückgeholt.
  </p>
</div>

<?php if ($probleme): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div>Erst muss das hier in Ordnung sein:<ul><?php foreach ($probleme as $p): ?><li><?= h($p) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<?php if (!$aenderungen): ?>
<section class="tafel">
  <p class="eyebrow eyebrow--leise">Nichts zu tun</p>
  <h2>Keine offenen Änderungen</h2>
  <p class="leise">
    Die Webseite entspricht dem, was hier eingetragen ist.
<?php if ($letzte): ?> Zuletzt veröffentlicht am <?= h(zeit_deutsch((string) $letzte['zeit'])) ?>.<?php endif; ?>
  </p>
</section>
<?php else: ?>
<section class="tafel tafel--akzent">
  <p class="eyebrow">Bereit</p>
  <h2 class="gross"><?= $anzahl === 1 ? 'Eine Änderung' : $anzahl . ' Änderungen' ?> in <?= count($aenderungen) === 1 ? 'einem Bereich' : count($aenderungen) . ' Bereichen' ?></h2>
  <p>Nach dem Klick sind die Inhalte für alle Besucher sichtbar.</p>
  <div class="knopfzeile">
    <form method="post" data-titel="Jetzt veröffentlichen?" data-ja="Ja, veröffentlichen"
          data-frage="Die aufgeführten Änderungen gehen <?= $domain !== '' ? 'auf ' . h(preg_replace('~^https?://~', '', $domain)) . ' ' : '' ?>live und sind sofort für alle Besucher sichtbar. Der bisherige Stand wird vorher gesichert.">
      <input type="hidden" name="tat" value="los"><input type="hidden" name="token" value="<?= $tok ?>">
      <button type="submit" class="knopf knopf--hell knopf--gross"<?= $probleme ? ' disabled' : '' ?>>Jetzt veröffentlichen</button>
    </form>
    <a href="<?= h(url_zu('vorschau')) ?>" class="knopf knopf--umriss-hell knopf--gross">Vorher in der Vorschau ansehen</a>
  </div>
</section>

<?php if ($punkte): ?>
<details class="meldung meldung--hinweis aufklapp">
  <summary><span class="meldung-zeichen" aria-hidden="true">i</span> <?= count($punkte) ?> offene <?= count($punkte) === 1 ? 'Punkt' : 'Punkte' ?> in der Prüfliste — das Veröffentlichen geht trotzdem.</summary>
  <ul><?php foreach ($punkte as $p): ?><li><?= h($p) ?></li><?php endforeach; ?></ul>
</details>
<?php endif; ?>

<h2 class="abschnittstitel">Was sich ändert</h2>
<?php foreach ($aenderungen as $datei => $liste): ?>
<section class="diff">
  <div class="diff-kopf">
    <a class="diff-titel" href="<?= h(datei_url($datei)) ?>"><?= h(datei_titel($datei)) ?></a>
    <span class="pille"><?= count($liste) ?> <?= count($liste) === 1 ? 'Änderung' : 'Änderungen' ?></span>
  </div>
<?php foreach (array_slice($liste, 0, 60) as $u): ?>
  <div class="diff-zeile">
    <span class="diff-pfad"><?= h($u['pfad']) ?></span>
<?php if ($u['art'] === 'neu'): ?>
    <span class="diff-alt diff-alt--leer">neu</span>
    <span class="diff-neu"><?= h($u['neu']) ?></span>
<?php elseif ($u['art'] === 'entfernt'): ?>
    <span class="diff-alt"><?= h($u['alt']) ?></span>
    <span class="diff-neu diff-neu--leer">entfernt</span>
<?php else: ?>
    <span class="diff-alt"><?= h($u['alt']) ?></span>
    <span class="diff-neu"><?= h($u['neu']) ?></span>
<?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (count($liste) > 60): ?>
  <div class="diff-zeile"><span class="diff-pfad">…</span><span class="still diff-rest">und <?= count($liste) - 60 ?> weitere Änderungen</span></div>
<?php endif; ?>
</section>
<?php endforeach; ?>

<form method="post" class="abstand-oben" data-titel="Alle Entwürfe verwerfen?" data-ja="Ja, alles verwerfen"
      data-frage="Sämtliche noch nicht veröffentlichten Änderungen werden gelöscht. Die Webseite bleibt, wie sie ist. Das lässt sich nicht rückgängig machen.">
  <input type="hidden" name="tat" value="alles_verwerfen"><input type="hidden" name="token" value="<?= $tok ?>">
  <button type="submit" class="knopf knopf--gefahr-still knopf--klein">Alle Entwürfe verwerfen</button>
</form>
<?php endif; ?>

<hr class="trenner">

<section class="tafel">
  <p class="eyebrow eyebrow--leise">Nach einem Update der Webseite</p>
  <h2>Webseite neu erzeugen</h2>
  <p class="leise">
    Schreibt die Webseite aus dem veröffentlichten Stand neu, ohne Inhalte zu ändern. Nötig, nachdem das aktive Design
    bearbeitet, eine neue Fassung der Vorlagen (<span class="mono">privat/vorlagen</span>) hochgeladen oder Dateien per SFTP
    ausgetauscht wurden. Offene Entwürfe bleiben liegen.
  </p>
  <form method="post" class="abstand-oben" data-titel="Webseite neu erzeugen?" data-ja="Ja, neu erzeugen"
        data-frage="Die Webseite wird aus dem zuletzt veröffentlichten Stand neu geschrieben. Inhalte und Entwürfe bleiben unverändert.">
    <input type="hidden" name="tat" value="neu_bauen"><input type="hidden" name="token" value="<?= $tok ?>">
    <button type="submit" class="knopf knopf--zweit"<?= $probleme ? ' disabled' : '' ?>>Webseite neu erzeugen</button>
  </form>
</section>

<?php if ($sicherungen): ?>
<section class="abstand-oben">
  <p class="eyebrow eyebrow--leise">Sicherungen</p>
  <h2 class="abschnittstitel abschnittstitel--ohne-abstand">Frühere Stände</h2>
  <p class="leise klein">
    Vor jedem Veröffentlichen wird der bisherige Stand gesichert; die letzten <?= SICHERUNGEN_BEHALTEN ?> bleiben liegen.
    „Als Entwurf laden“ holt einen Stand zurück — Sie sehen danach genau, was sich ändert, und veröffentlichen erst dann.
    Mediendateien sind nicht Teil der Sicherung.
  </p>
  <div class="tabelle-huelle">
    <table class="tabelle">
      <thead><tr><th>Gesichert</th><th>Von</th><th>Danach veröffentlicht</th><th><span class="nur-leser">Aktion</span></th></tr></thead>
      <tbody>
<?php foreach (array_slice($sicherungen, 0, 15) as $s): ?>
        <tr>
          <td class="nowrap"><?= h($s['zeit']) ?></td>
          <td><?= h((string) (benutzer_holen($s['benutzer'])['anzeige'] ?? ($s['benutzer'] ?: '—'))) ?></td>
          <td class="klein leise"><?= h(implode(', ', $s['bereiche']) ?: '—') ?></td>
          <td class="rechts">
            <form method="post" data-titel="Stand vom <?= h($s['zeit']) ?> laden?" data-ja="Als Entwurf laden"
                  data-frage="Die Inhalte dieses Stands werden als Entwurf geladen. Offene Entwürfe in denselben Bereichen werden dabei überschrieben. Auf der Webseite ändert sich erst etwas, wenn Sie danach veröffentlichen.">
              <input type="hidden" name="tat" value="wiederherstellen"><input type="hidden" name="name" value="<?= h($s['name']) ?>"><input type="hidden" name="token" value="<?= $tok ?>">
              <button type="submit" class="knopf knopf--still knopf--klein">Als Entwurf laden</button>
            </form>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
