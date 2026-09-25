<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Medienverwaltung.
 * Erwartet: $art, $medien, $alle
 */
$tok = h(token());
$zaehlen = static fn(string $a): int => count(array_filter($alle, static fn($m) => $m['art'] === $a));
$filter = ['' => ['Alle', count($alle)], 'bild' => ['Bilder', $zaehlen('bild')], 'video' => ['Videos', $zaehlen('video')], 'pdf' => ['PDF', $zaehlen('pdf')]];
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">medien/ im Docroot der Webseite</p>
  <p class="leise">
    Alle Bilder, Videos und PDF-Dateien der Webseite. Ausgewählt werden sie direkt in den Formularen.
    „Ersetzen“ tauscht eine Datei überall aus, wo sie verwendet wird; damit Besucher nicht die alte Fassung aus
    ihrem Zwischenspeicher sehen, danach einmal veröffentlichen oder „Webseite neu erzeugen“.
  </p>
</div>

<form method="post" enctype="multipart/form-data" class="abstand-unten">
  <input type="hidden" name="tat" value="hochladen">
  <input type="hidden" name="token" value="<?= $tok ?>">
  <label class="ablegeflaeche" data-ablage>
    <span class="ablegeflaeche-titel">Dateien hierher ziehen oder klicken</span>
    <span class="leise klein">
      Bilder (JPEG, PNG, WebP, GIF) bis <?= groesse_lesbar(MEDIEN_ARTEN['bild'][1]) ?> ·
      Videos (MP4, WebM) bis <?= groesse_lesbar(min(MEDIEN_ARTEN['video'][1], upload_grenze())) ?> ·
      PDF bis <?= groesse_lesbar(MEDIEN_ARTEN['pdf'][1]) ?>
    </span>
    <span class="still klein">Fotos werden automatisch gedreht, auf <?= BILD_MAX_KANTE ?> Pixel verkleinert und von Aufnahmedaten wie dem Ort befreit.</span>
    <input type="file" name="dateien[]" multiple class="nur-leser"
           accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,application/pdf">
  </label>
  <noscript><button type="submit" class="knopf knopf--klein abstand-oben">Hochladen</button></noscript>
</form>

<nav class="filterleiste" aria-label="Nach Art filtern">
<?php foreach ($filter as $k => [$name, $n]): ?>
  <a href="<?= h(url_zu('medien', array_filter(['art' => $k]))) ?>" class="filter"<?= $art === $k ? ' aria-current="page"' : '' ?>><?= h($name) ?> <span class="still"><?= $n ?></span></a>
<?php endforeach; ?>
  <span class="filterleiste-summe still klein"><?= h(groesse_lesbar((int) array_sum(array_column($alle, 'groesse')))) ?> gesamt</span>
</nav>

<?php if (!$medien): ?>
<div class="tafel leer">
  <p class="leise">Noch keine Dateien<?= $art !== '' ? ' dieser Art' : '' ?>. Ziehen Sie Fotos einfach in die Fläche oben.</p>
</div>
<?php else: ?>
<div class="mediengitter">
<?php foreach ($medien as $m):
    $verwendung = medium_verwendung($m['pfad']);
    $url = url_zu('medium', ['pfad' => $m['pfad']]); ?>
  <figure class="medienkachel">
    <a class="medienkachel-bild medienkachel-bild--<?= h($m['art']) ?>" href="<?= h($url) ?>" target="_blank" rel="noopener" aria-label="<?= h($m['name']) ?> öffnen">
<?php if ($m['art'] === 'bild'): ?>
      <img src="<?= h($url) ?>" alt="" loading="lazy">
<?php elseif ($m['art'] === 'video'): ?>
      <video src="<?= h($url) ?>#t=0.5" preload="metadata" muted playsinline></video><span class="medienkachel-abspielen" aria-hidden="true">▶</span>
<?php else: ?>
      <span class="medienkachel-pdf" aria-hidden="true">PDF</span>
<?php endif; ?>
    </a>
    <figcaption class="medienkachel-fuss">
      <span class="medienkachel-name" title="<?= h($m['name']) ?>"><?= h($m['name']) ?></span>
      <span class="medienkachel-meta"><?= $m['breite'] ? h($m['breite'] . ' × ' . $m['hoehe']) . ' · ' : '' ?><?= h(groesse_lesbar($m['groesse'])) ?> · <?= date('d.m.Y', $m['geaendert']) ?></span>
<?php if ($verwendung): ?>
      <span class="medienkachel-meta medienkachel-meta--an">Verwendet: <?= h(implode(', ', $verwendung)) ?></span>
<?php else: ?>
      <span class="medienkachel-meta">Nirgends verwendet</span>
<?php endif; ?>
      <span class="medienkachel-tasten">
        <button type="button" class="knopf knopf--still knopf--klein" data-kopieren="<?= h($m['pfad']) ?>">Pfad kopieren</button>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="tat" value="ersetzen"><input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="name" value="<?= h($m['name']) ?>">
          <label class="knopf knopf--still knopf--klein">Ersetzen<input type="file" name="datei" class="nur-leser" data-dateiwahl
            accept="<?= $m['art'] === 'bild' ? 'image/*' : ($m['art'] === 'video' ? 'video/mp4,video/webm' : 'application/pdf') ?>"></label>
        </form>
        <form method="post" data-titel="Datei löschen?" data-ja="Ja, löschen" data-frage="„<?= h($m['name']) ?>“ wird endgültig vom Webspace entfernt.">
          <input type="hidden" name="tat" value="loeschen"><input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="name" value="<?= h($m['name']) ?>">
          <button type="submit" class="knopf knopf--gefahr-still knopf--klein"<?= $verwendung ? ' disabled title="Wird noch verwendet"' : '' ?>>Löschen</button>
        </form>
      </span>
    </figcaption>
  </figure>
<?php endforeach; ?>
</div>
<?php endif; ?>
