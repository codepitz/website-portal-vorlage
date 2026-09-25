<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Designs der Webseite.
 * Erwartet: $designs, $aktiv, $live, $zipMoeglich
 */
$tok = h(token());
$farben = ['bg', 'surface', 'accent', 'text'];
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">Themes, Farben, Schriften</p>
  <p class="leise">
    Das Design bestimmt, wie die Webseite aussieht — die Inhalte bleiben dabei unverändert. Ein anderes Design wählen,
    eine eigene Fassung anpassen oder ein <strong>Claude Design System</strong> hochladen: Die Farben und Schriften daraus
    werden übernommen. Wie jede Änderung geht auch ein neues Design erst mit „Veröffentlichen“ online.
  </p>
</div>

<?php if ($aktiv !== $live): ?>
<div class="meldung meldung--hinweis">
  <span class="meldung-zeichen" aria-hidden="true">i</span>
  <div>Im Entwurf ist „<?= h((string) ($designs[$aktiv]['name'] ?? $aktiv)) ?>“ gewählt, auf der Webseite läuft noch „<?= h((string) ($designs[$live]['name'] ?? $live)) ?>“.
    <a href="<?= h(url_zu('vorschau')) ?>">Vorschau</a> · <a href="<?= h(url_zu('veroeffentlichen')) ?>">Veröffentlichen</a></div>
</div>
<?php endif; ?>

<div class="designgitter">
<?php foreach ($designs as $id => $d):
    $t = $d['tokens'] ?? [];
    $istAktiv = $id === $aktiv;
    $istLive = $id === $live; ?>
  <article class="designkachel<?= $istAktiv ? ' designkachel--aktiv' : '' ?>">
    <a class="designkachel-muster" href="<?= h(url_zu('vorschau', ['design' => $id])) ?>" aria-label="<?= h((string) ($d['name'] ?? $id)) ?> in der Vorschau ansehen"
       style="background:<?= h((string) ($t['bg'] ?? '#000')) ?>;color:<?= h((string) ($t['text'] ?? '#fff')) ?>">
      <span class="designkachel-flaeche" style="background:<?= h((string) ($t['surface'] ?? '#222')) ?>;border-radius:<?= h((string) ($t['radius'] ?? '12px')) ?>">
        <span class="designkachel-zeile" style="background:<?= h((string) ($t['text'] ?? '#fff')) ?>"></span>
        <span class="designkachel-zeile designkachel-zeile--kurz" style="background:<?= h((string) ($t['text-2'] ?? '#999')) ?>"></span>
        <span class="designkachel-knopf" style="background:<?= h((string) ($t['accent'] ?? '#5eead4')) ?>;border-radius:<?= h((string) ($t['radius-sm'] ?? '8px')) ?>"></span>
      </span>
      <span class="designkachel-schrift" style="font-family:<?= h((string) ($t['font-head'] ?? 'inherit')) ?>">Aa</span>
    </a>
    <div class="designkachel-text">
      <div class="knopfzeile">
        <h2 class="designkachel-titel"><?= h((string) ($d['name'] ?? $id)) ?></h2>
<?php if ($istLive): ?><span class="pille pille--gut"><span class="punkt" aria-hidden="true"></span> Auf der Webseite</span><?php endif; ?>
<?php if ($istAktiv && !$istLive): ?><span class="pille pille--akzent">Im Entwurf gewählt</span><?php endif; ?>
      </div>
      <p class="leise klein"><?= h((string) ($d['beschreibung'] ?? '')) ?></p>
      <p class="farbpunkte" aria-hidden="true">
<?php foreach (['bg', 'surface', 'surface-2', 'line', 'text', 'text-2', 'accent', 'accent-ink'] as $k): ?>
        <span style="background:<?= h((string) ($t[$k] ?? 'transparent')) ?>" title="<?= h($k . ': ' . ($t[$k] ?? '')) ?>"></span>
<?php endforeach; ?>
      </p>
<?php $th = ws_theme($d); ?>
<?php if ($th): $vorl = count(glob($th['ordner'] . '/vorlagen/{,*/}*.html', GLOB_BRACE) ?: []); ?>
      <p class="klein"><span class="pille pille--akzent">Theme</span>
        <span class="leise"><?= $vorl ?> Vorlage<?= $vorl === 1 ? '' : 'n' ?><?= $th['css'] ? ' · eigenes CSS' : '' ?><?= $th['js'] ? ' · Skript' : '' ?><?= $th['sektionen'] ? ' · ' . count($th['sektionen']) . ' eigene Sektion' . (count($th['sektionen']) === 1 ? '' : 'en') : '' ?></span></p>
<?php endif; ?>
      <p class="still klein"><?= h((string) ($d['quelle'] ?? '')) ?><?= !empty($d['fonts']) ? ' · ' . count($d['fonts']) . ' Schriftdateien' : '' ?></p>
      <div class="knopfzeile">
<?php if (!$istAktiv): ?>
        <form method="post"><input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="verwenden"><input type="hidden" name="id" value="<?= h($id) ?>">
          <button type="submit" class="knopf knopf--klein">Verwenden</button></form>
<?php endif; ?>
        <a class="knopf knopf--zweit knopf--klein" href="<?= h(url_zu('vorschau', ['design' => $id])) ?>">Vorschau</a>
<?php if (empty($d['schutz'])): ?>
        <a class="knopf knopf--still knopf--klein" href="<?= h(url_zu('design-bearbeiten', ['id' => $id])) ?>">Bearbeiten</a>
<?php endif; ?>
        <form method="post"><input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="duplizieren"><input type="hidden" name="id" value="<?= h($id) ?>">
          <button type="submit" class="knopf knopf--still knopf--klein"><?= empty($d['schutz']) ? 'Kopieren' : 'Eigene Fassung anlegen' ?></button></form>
<?php $alsZip = $th && $zipMoeglich; ?>
        <a class="minitaste" href="<?= h(url_zu('design-export', ['id' => $id])) ?>" title="<?= $alsZip ? 'Theme-Paket (ZIP) herunterladen' : 'design.json herunterladen' ?>" aria-label="<?= $alsZip ? 'Theme-Paket herunterladen' : 'design.json herunterladen' ?>">↓</a>
<?php if (empty($d['schutz']) && !$istAktiv && !$istLive): ?>
        <form method="post" data-titel="Design löschen?" data-ja="Ja, löschen" data-frage="„<?= h((string) ($d['name'] ?? $id)) ?>“ wird mit seinen Schriftdateien gelöscht.">
          <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="loeschen"><input type="hidden" name="id" value="<?= h($id) ?>">
          <button type="submit" class="minitaste minitaste--weg" title="Löschen" aria-label="Löschen">✕</button></form>
<?php endif; ?>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>

<h2 class="abschnittstitel" id="hochladen">Design oder Theme hochladen</h2>
<section class="tafel">
  <p class="leise">
    <strong>Theme-Paket</strong> (ZIP mit <span class="mono">theme.json</span>): ändert nicht nur Farben und Schriften, sondern den ganzen Aufbau der Webseite —
    Kopfleiste, Sektionen, Fußzeile, bei Bedarf eigene Sektionstypen. Die Inhalte bleiben, wie sie sind; ein anderes Theme zeigt dieselben Inhalte in anderer Gestalt.
    Theme-Pakete darf nur die Verwaltung hochladen, weil sie eigenes Markup und Skripte mitbringen.
  </p>
  <p class="leise">
    So geht’s mit einem <strong>Claude Design System</strong>: in Claude das Design System öffnen und als ZIP exportieren
    (enthält <span class="mono">styles.css</span>, <span class="mono">tokens/</span> und <span class="mono">fonts/</span>) — und die ZIP-Datei hier hochladen.
    Im nächsten Schritt sehen Sie, welche Farbe, Schrift und Rundung wofür verwendet wird, und können jede Zuordnung ändern.
  </p>
  <ul class="pruefliste pruefliste--leise">
    <li><span class="pille pille--akzent">ZIP</span> <span>Theme-Paket mit <span class="mono">theme.json</span>, Vorlagen, CSS und Bildern — wird direkt installiert</span></li>
    <li><span class="pille pille--gut">ZIP</span> <span>Export eines Claude Design Systems — mit Schriften<?= $zipMoeglich ? '' : ' <strong>(auf diesem Server nicht möglich — PHP-Erweiterung „zip“ fehlt)</strong>' ?></span></li>
    <li><span class="pille pille--gut">CSS</span> <span>Eine einzelne Datei mit Variablen, z. B. <span class="mono">styles.css</span>, <span class="mono">_ds_bundle.css</span> oder <span class="mono">tokens.css</span></span></li>
    <li><span class="pille pille--gut">MD</span> <span><span class="mono">DESIGN-SYSTEM.md</span> mit einer Tabelle der Tokens (<span class="mono">| `--bg` | `#0e0e16` |</span>)</span></li>
    <li><span class="pille pille--gut">JSON</span> <span>Eine hier heruntergeladene <span class="mono">design.json</span> — etwa aus einem anderen Portal</span></li>
  </ul>
  <form method="post" enctype="multipart/form-data" class="abstand-oben">
    <input type="hidden" name="tat" value="hochladen"><input type="hidden" name="token" value="<?= $tok ?>">
    <label class="ablegeflaeche" data-ablage>
      <span class="ablegeflaeche-titel">Design-Datei hierher ziehen oder klicken</span>
      <span class="leise klein">ZIP, CSS, MD oder JSON bis <?= groesse_lesbar(min(DESIGN_UPLOAD_MAX, upload_grenze())) ?></span>
      <input type="file" name="datei" class="nur-leser" accept=".zip,.css,.md,.json,application/zip,text/css,text/markdown,application/json">
    </label>
    <noscript><button type="submit" class="knopf knopf--klein abstand-oben">Hochladen</button></noscript>
  </form>
</section>
