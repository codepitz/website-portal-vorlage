<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Übersicht.
 * Erwartet: $aenderungen, $letzte, $punkte, $probleme, $protokoll
 */
$anzahl = array_sum(array_map('count', $aenderungen));
$ich = aktueller_benutzer();
$stunde = (int) date('G');
$gruss = $stunde < 11 ? 'Guten Morgen' : ($stunde < 18 ? 'Guten Tag' : 'Guten Abend');
$hinweiseAn = modul('hinweise') ? array_filter(hinweise_laden(), static fn($h) => in_array(hinweis_status($h)[0], ['an', 'geplant'], true)) : [];
$sektionen = sektionen_entwurf();
$sichtbar = count(array_filter($sektionen, static fn($s) => !empty($s['aktiv'])));
?>
<p class="eyebrow eyebrow--leise"><?= h($gruss) ?>, <?= h((string) ($ich['anzeige'] ?? '')) ?></p>

<?php if ($probleme): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div>Veröffentlichen ist gerade nicht möglich:<ul><?php foreach ($probleme as $p): ?><li><?= h($p) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<?php if ($aenderungen): ?>
<section class="tafel tafel--akzent">
  <p class="eyebrow">Offene Änderungen</p>
  <h1><?= $anzahl === 1 ? 'Eine Änderung wartet' : $anzahl . ' Änderungen warten' ?></h1>
  <p>Bearbeitet, aber noch nicht auf der Webseite:
<?php foreach (array_keys($aenderungen) as $i => $datei): ?>
    <a href="<?= h(datei_url($datei)) ?>"><?= h(datei_titel($datei)) ?></a><?= $i < count($aenderungen) - 1 ? ', ' : '.' ?>
<?php endforeach; ?>
  </p>
  <div class="knopfzeile">
    <a href="<?= h(url_zu('veroeffentlichen')) ?>" class="knopf knopf--hell">Ansehen und veröffentlichen</a>
    <a href="<?= h(url_zu('vorschau')) ?>" class="knopf knopf--umriss-hell">Vorschau</a>
  </div>
</section>
<?php elseif (!$letzte): ?>
<section class="tafel tafel--akzent">
  <p class="eyebrow">Erster Start</p>
  <h1>Die Webseite ist noch nicht erzeugt</h1>
  <p>Auf der Adresse der Webseite steht bisher nur eine Platzhalterseite. Inhalte ansehen und anpassen, dann einmal
    „Webseite neu erzeugen“ — ab da schreibt jedes „Veröffentlichen“ die Seite neu.</p>
  <div class="knopfzeile">
    <a href="<?= h(url_zu('veroeffentlichen')) ?>" class="knopf knopf--hell">Zum Veröffentlichen</a>
    <a href="<?= h(url_zu('vorschau')) ?>" class="knopf knopf--umriss-hell">Vorschau</a>
  </div>
</section>
<?php else: ?>
<section class="tafel">
  <p class="eyebrow">Stand</p>
  <h1>Alles veröffentlicht</h1>
  <p class="leise">
    Die Webseite zeigt genau das, was hier eingetragen ist.
<?php if ($letzte): ?>
    Zuletzt veröffentlicht am <?= h(zeit_deutsch((string) $letzte['zeit'])) ?> von <?= h((string) (benutzer_holen((string) $letzte['benutzer'])['anzeige'] ?? $letzte['benutzer'] ?: '—')) ?>.
<?php endif; ?>
  </p>
</section>
<?php endif; ?>

<div class="spalten">
  <section class="tafel">
    <div class="tafel-kopf">
      <div>
        <p class="eyebrow eyebrow--leise">Prüfliste</p>
        <h2><?= $punkte ? count($punkte) . ' offene ' . (count($punkte) === 1 ? 'Punkt' : 'Punkte') : 'Keine offenen Punkte' ?></h2>
      </div>
    </div>
<?php if ($punkte): ?>
    <p class="leise klein">Diese Hinweise verhindern das Veröffentlichen nicht — vor dem Start der Webseite sollten sie aber erledigt sein.</p>
    <ul class="pruefliste">
<?php foreach ($punkte as $p): ?>
      <li><span class="pille pille--warn">offen</span> <span><?= h($p) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="leise">Pflichtangaben, Links, Bilder und Rechtstexte sind vollständig.</p>
<?php endif; ?>
  </section>

  <section class="tafel">
    <p class="eyebrow eyebrow--leise">Auf einen Blick</p>
    <dl class="kennzahlen">
      <div><dt>Sektionen sichtbar</dt><dd><?= $sichtbar ?> <span class="still">von <?= count($sektionen) ?></span></dd></div>
<?php if (modul('angebote')): ?>
      <div><dt><?= h(begriff('angebote')) ?></dt><dd><?= count(array_filter(entwurf_laden('angebote'), static fn($l) => !empty($l['aktiv']))) ?></dd></div>
<?php endif; ?>
<?php if (modul('team')): ?>
      <div><dt><?= h(begriff('team')) ?></dt><dd><?= count(array_filter(entwurf_laden('team'), static fn($l) => !empty($l['aktiv']))) ?></dd></div>
<?php endif; ?>
<?php if (modul('hinweise')): ?>
      <div><dt>Hinweise aktiv</dt><dd><?= count($hinweiseAn) ?></dd></div>
<?php endif; ?>
      <div><dt>Design</dt><dd class="klein"><?= h((string) (design_laden(design_aktiv())['name'] ?? design_aktiv())) ?></dd></div>
      <div><dt>Mediendateien</dt><dd><?= count(medien_liste()) ?></dd></div>
    </dl>
<?php if ($protokoll): ?>
    <p class="eyebrow eyebrow--leise abstand-oben">Zuletzt</p>
    <ul class="verlauf">
<?php foreach ($protokoll as $e): ?>
      <li><span class="still klein mono"><?= h(date('d.m. H:i', strtotime($e['zeit']) ?: time())) ?></span> <span class="klein"><strong><?= h((string) (benutzer_holen($e['wer'])['anzeige'] ?? $e['wer'])) ?></strong> — <?= h($e['was']) ?></span></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </section>
</div>

<h2 class="abschnittstitel">Inhalte bearbeiten</h2>
<div class="kacheln">
  <a class="kachel" href="<?= h(url_zu('sektionen')) ?>">
    <span class="kachel-kopf"><span class="kachel-zeichen" aria-hidden="true">▦</span><span class="kachel-titel">Sektionen</span></span>
    <span class="kachel-text">Reihenfolge, Sichtbarkeit und Inhalte aller Bereiche der Startseite — und neue hinzufügen.</span>
    <span class="kachel-fuss"><?= entwurf_offen('startseite') ? '<span class="pille pille--akzent">geändert</span>' : '<span class="still">Startseite</span>' ?></span>
  </a>
<?php foreach (bereiche() as $id => $b): ?>
  <a class="kachel" href="<?= h(url_zu('bereich', ['id' => $id])) ?>">
    <span class="kachel-kopf"><span class="kachel-zeichen" aria-hidden="true"><?= $b['zeichen'] ?></span><span class="kachel-titel"><?= h($b['titel']) ?></span></span>
    <span class="kachel-text"><?= h($b['beschreibung']) ?></span>
    <span class="kachel-fuss"><?= bereich_geaendert($id) ? '<span class="pille pille--akzent">geändert</span>' : '<span class="still">' . h($b['wo']) . '</span>' ?></span>
  </a>
<?php endforeach; ?>
<?php if (modul('hinweise')): ?>
  <a class="kachel" href="<?= h(url_zu('hinweise')) ?>">
    <span class="kachel-kopf"><span class="kachel-zeichen" aria-hidden="true">◆</span><span class="kachel-titel"><?= h(begriff('hinweise')) ?></span></span>
    <span class="kachel-text">Urlaub, Schließzeiten und Neuigkeiten als Leiste oder Fenster — mit Zeitraum.</span>
    <span class="kachel-fuss"><?= entwurf_offen('hinweise') ? '<span class="pille pille--akzent">geändert</span>' : '<span class="still">' . count($hinweiseAn) . ' aktiv</span>' ?></span>
  </a>
<?php endif; ?>
  <a class="kachel" href="<?= h(url_zu('design')) ?>">
    <span class="kachel-kopf"><span class="kachel-zeichen" aria-hidden="true">◇</span><span class="kachel-titel">Design</span></span>
    <span class="kachel-text">Farben, Schriften und Rundungen — oder ein Claude Design System hochladen.</span>
    <span class="kachel-fuss"><?= design_aktiv() !== design_live() ? '<span class="pille pille--akzent">geändert</span>' : '<span class="still">' . h((string) (design_laden(design_aktiv())['name'] ?? '')) . '</span>' ?></span>
  </a>
  <a class="kachel" href="<?= h(url_zu('medien')) ?>">
    <span class="kachel-kopf"><span class="kachel-zeichen" aria-hidden="true">▣</span><span class="kachel-titel">Medien</span></span>
    <span class="kachel-text">Fotos, Hintergrundbilder, Videos und PDF-Dateien hochladen und ersetzen.</span>
    <span class="kachel-fuss"><span class="still">medien/</span></span>
  </a>
</div>
