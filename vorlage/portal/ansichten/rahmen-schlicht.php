<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Gerüst für Anmeldung und Einrichtung. Erwartet: $titel, $inhalt, $meldungen */
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titel) ?> — <?= h((string) projekt('portal')) ?></title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/admin.css?v=<?= substr((string) @md5_file(ADMIN_WURZEL . '/assets/admin.css'), 0, 8) ?>">
</head>
<body class="tor">
<main class="tor-tafel<?= $titel === 'Einrichtung' ? ' tor-tafel--weit' : '' ?>">
  <div class="tor-marke">
    <span class="marke-zeichen" aria-hidden="true"><?= h(mb_substr((string) projekt('kuerzel'), 0, 2)) ?></span>
    <span class="marke-text">
      <span class="marke-name"><?= h((string) projekt('name')) ?></span>
      <span class="marke-zusatz"><?= h((string) projekt('portal')) ?></span>
    </span>
  </div>

<?php foreach ($meldungen as $m): ?>
  <div class="meldung meldung--<?= h($m['art']) ?>" role="status">
    <span class="meldung-zeichen" aria-hidden="true"><?= $m['art'] === 'gut' ? '✓' : ($m['art'] === 'fehler' ? '!' : 'i') ?></span>
    <div><?= h($m['text']) ?></div>
  </div>
<?php endforeach; ?>

<?= $inhalt ?>
</main>
<script src="assets/admin.js?v=<?= substr((string) @md5_file(ADMIN_WURZEL . '/assets/admin.js'), 0, 8) ?>" defer></script>
</body>
</html>
