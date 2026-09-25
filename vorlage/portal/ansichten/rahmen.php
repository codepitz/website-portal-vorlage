<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Grundgerüst jeder angemeldeten Seite: Navigation links, Kopfleiste oben.
 * Erwartet: $titel, $aktiv, $inhalt, $meldungen, $offen, $weit, optional $bearbeiten
 */
$ich = aktueller_benutzer();
$anzahlOffen = count($offen);
$website = rtrim((string) konfig('website'), '/');
$bearbeiten = !empty($bearbeiten);
$version = static fn(string $datei): string => substr((string) @md5_file(ADMIN_WURZEL . '/assets/' . $datei), 0, 8);

$link = static function (string $url, bool $ist, string $zeichen, string $text, string $punkt = '') {
    return '<a href="' . h($url) . '" class="navlink"' . ($ist ? ' aria-current="page"' : '') . '>'
        . '<span class="navlink-zeichen" aria-hidden="true">' . $zeichen . '</span>'
        . '<span class="navlink-text">' . h($text) . '</span>'
        . ($punkt === 'offen' ? '<span class="navpunkt" title="Nicht veröffentlichte Änderung"></span>' : '')
        . ($punkt === 'fehler' ? '<span class="navpunkt navpunkt--fehler" title="Es gibt ein Problem"></span>' : '')
        . '</a>';
};
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titel) ?> — <?= h((string) projekt('portal')) ?></title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/admin.css?v=<?= $version('admin.css') ?>">
</head>
<body>
<a class="sprungmarke" href="#hauptbereich">Zum Inhalt springen</a>
<div class="huelle">

  <nav class="seitenleiste" aria-label="Bereiche" data-navigation>
    <a href="?" class="marke">
      <span class="marke-zeichen" aria-hidden="true"><?= h(mb_substr((string) projekt('kuerzel'), 0, 2)) ?></span>
      <span class="marke-text">
        <span class="marke-name"><?= h((string) projekt('name')) ?></span>
        <span class="marke-zusatz"><?= h((string) projekt('portal')) ?></span>
      </span>
    </a>

    <button type="button" class="navtaste" data-navtaste aria-expanded="false" aria-controls="navliste">
      <span class="navtaste-striche" aria-hidden="true"></span> Menü
    </button>

    <div class="navliste" id="navliste">
      <div class="navgruppe">
        <?= $link(url_zu('start'), $aktiv === 'start', '◉', 'Übersicht') ?>
      </div>

      <div class="navgruppe">
        <div class="navgruppe-titel">Startseite</div>
        <?= $link(url_zu('sektionen'), $aktiv === 'sektionen', '▦', 'Sektionen', entwurf_offen('startseite') ? 'offen' : '') ?>
        <?= $link(url_zu('vorschau'), $aktiv === 'vorschau', '◐', 'Vorschau') ?>
        <?= $link(url_zu('design'), $aktiv === 'design', '◇', 'Design', design_aktiv() !== design_live() ? 'offen' : '') ?>
      </div>

<?php foreach (bereiche_nach_gruppe() as $gruppe => $liste): ?>
      <div class="navgruppe">
        <div class="navgruppe-titel"><?= h($gruppe) ?></div>
<?php foreach ($liste as $id => $b): ?>
        <?= $link(url_zu('bereich', ['id' => $id]), $aktiv === 'bereich:' . $id, $b['zeichen'] ?? '•', $b['titel'], bereich_geaendert($id) ? 'offen' : '') ?>
<?php endforeach; ?>
      </div>
<?php endforeach; ?>

      <div class="navgruppe">
        <div class="navgruppe-titel">Aktuelles</div>
<?php if (modul('hinweise')): ?>
        <?= $link(url_zu('hinweise'), $aktiv === 'hinweise', '◆', begriff('hinweise'), entwurf_offen('hinweise') ? 'offen' : '') ?>
<?php endif; ?>
        <?= $link(url_zu('medien'), $aktiv === 'medien', '▣', 'Medien') ?>
      </div>

      <div class="seitenleiste-fuss">
<?php if (darf('benutzer')): ?>
        <?= $link(url_zu('benutzer'), $aktiv === 'benutzer', '☷', 'Benutzer') ?>
        <?= $link(url_zu('einstellungen'), $aktiv === 'einstellungen', '⚙', 'Einstellungen', veroeffentlichen_moeglich() ? 'fehler' : '') ?>
        <?= $link(url_zu('diagnose'), $aktiv === 'diagnose', '✓', 'Systemprüfung') ?>
        <?= $link(url_zu('protokoll'), $aktiv === 'protokoll', '≡', 'Protokoll') ?>
<?php endif; ?>
        <?= $link(url_zu('konto'), $aktiv === 'konto', '☺', 'Mein Konto') ?>
<?php if ($website !== ''): ?>
        <a href="<?= h($website) ?>" class="navlink" target="_blank" rel="noopener">
          <span class="navlink-zeichen" aria-hidden="true">↗</span><span class="navlink-text">Webseite ansehen</span>
        </a>
<?php endif; ?>
        <a href="<?= h(url_zu('abmelden')) ?>" class="navlink">
          <span class="navlink-zeichen" aria-hidden="true">⏻</span>
          <span class="navlink-text">Abmelden <span class="still">(<?= h((string) ($ich['anzeige'] ?? '')) ?>)</span></span>
        </a>
      </div>
    </div>
  </nav>

  <div class="inhalt">
    <header class="kopfleiste">
      <span class="kopfleiste-titel"><?= h($titel) ?></span>
      <div class="kopfleiste-rechts">
<?php if ($anzahlOffen > 0): ?>
        <a href="<?= h(url_zu('veroeffentlichen')) ?>" class="pille pille--akzent">
          <span class="punkt punkt--puls" aria-hidden="true"></span>
          <?= $anzahlOffen === 1 ? '1 Bereich geändert' : $anzahlOffen . ' Bereiche geändert' ?>
        </a>
        <a href="<?= h(url_zu('vorschau')) ?>" class="knopf knopf--geist knopf--klein">Vorschau</a>
        <a href="<?= h(url_zu('veroeffentlichen')) ?>" class="knopf knopf--klein">Veröffentlichen</a>
<?php else: ?>
        <span class="pille pille--gut"><span class="punkt" aria-hidden="true"></span> Alles veröffentlicht</span>
        <?php /* Auch ohne offene Änderungen erreichbar: dort liegt „Webseite neu erzeugen“ —
                 nötig nach einem Update der Vorlagen oder wenn Dateien per SFTP ausgetauscht wurden. */ ?>
        <a href="<?= h(url_zu('veroeffentlichen')) ?>" class="knopf knopf--zweit knopf--klein">Veröffentlichen</a>
<?php endif; ?>
      </div>
    </header>

    <main class="hauptbereich<?= !empty($weit) ? ' hauptbereich--weit' : '' ?>" id="hauptbereich">
<?php if (!https_aktiv() && !lokal_aufgerufen()): ?>
      <div class="meldung meldung--fehler" role="alert">
        <span class="meldung-zeichen" aria-hidden="true">!</span>
        <div>Diese Verbindung ist unverschlüsselt — Passwort und Sitzung sind unterwegs mitlesbar. Bitte im Kundenbereich des Hosters ein SSL-Zertifikat für diese Subdomain aktivieren.</div>
      </div>
<?php endif; ?>
<?php foreach ($meldungen as $m): ?>
      <div class="meldung meldung--<?= h($m['art']) ?>" role="<?= $m['art'] === 'fehler' ? 'alert' : 'status' ?>">
        <span class="meldung-zeichen" aria-hidden="true"><?= $m['art'] === 'gut' ? '✓' : ($m['art'] === 'fehler' ? '!' : 'i') ?></span>
        <div>
          <?= h($m['text']) ?>
<?php if (!empty($m['liste'])): ?>
          <ul>
<?php foreach ($m['liste'] as $z): ?>
            <li><?= h((string) $z) ?></li>
<?php endforeach; ?>
          </ul>
<?php endif; ?>
        </div>
      </div>
<?php endforeach; ?>

<?= $inhalt ?>
    </main>
  </div>
</div>

<?php if ($bearbeiten): ?>
<datalist id="np-ziele">
<?php foreach (ziel_vorschlaege() as $wert => $label): ?>
  <option value="<?= h($wert) ?>"><?= h($label) ?></option>
<?php endforeach; ?>
</datalist>
<script>window.NP_MEDIEN = <?= json_encode(array_map(static fn($m) => ['pfad' => $m['pfad'], 'name' => $m['name'], 'art' => $m['art']], medien_liste()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<?php endif; ?>
<script src="assets/admin.js?v=<?= $version('admin.js') ?>" defer></script>
</body>
</html>
