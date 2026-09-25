<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Zuordnen: Welche Variable des hochgeladenen Design Systems bedient welche
 * Stellschraube der Webseite?
 * Erwartet: $kennung, $analyse, $designs, $werte (nach Fehler: bisherige Eingaben)
 */
$tok = h(token());
$vars = $analyse['variablen'];
$vorschlag = $werte['zuordnung'] ?? $analyse['vorschlag'];
$eigen = $werte['eigen'] ?? [];
$grund = design_laden('aurora');

// Welche Variablen passen zu welcher Art?
$nachArt = ['farbe' => [], 'mass' => [], 'schrift' => []];
foreach ($vars as $n => $w) {
    if (wert_ist_farbe($w)) { $nachArt['farbe'][$n] = $w; }
    if (wert_ist_mass($w)) { $nachArt['mass'][$n] = $w; }
    if (wert_ist_schrift($w)) { $nachArt['schrift'][$n] = $w; }
}
$gruppen = [];
foreach (WS_DESIGN_TOKENS as $k => [$label, $art, $gruppe, $vorgabe, $hilfe]) {
    if (isset($nachArt[$art])) {
        $gruppen[$gruppe][$k] = [$label, $art, $vorgabe, $hilfe];
    }
}
$vorschau = [];
foreach (WS_DESIGN_TOKENS as $k => [, , , $vorgabe]) {
    $v = trim((string) ($eigen[$k] ?? ''));
    if ($v === '' && ($vorschlag[$k] ?? '') !== '' && isset($vars[$vorschlag[$k]])) {
        $v = $vars[$vorschlag[$k]];
    }
    $vorschau[$k] = $v !== '' ? $v : $vorgabe;
}
$kontraste = design_kontraste($vorschau);
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise"><a href="<?= h(url_zu('design')) ?>">← Design</a> · <?= h($analyse['datei']) ?></p>
  <p class="leise">
    Gefunden: <strong><?= count($vars) ?> Variablen</strong>, <?= (int) $analyse['schriftRegeln'] ?> Schrift-Regeln,
    <strong><?= count($analyse['schriften']) ?> Schriftdateien</strong>. Für jede Stellschraube steht unten ein Vorschlag —
    prüfen, bei Bedarf eine andere Variable wählen oder einen eigenen Wert eintragen. Was leer bleibt, kommt aus der Aurora-Vorlage.
  </p>
</div>

<form method="post" class="stapel" data-designimport>
  <input type="hidden" name="tat" value="uebernehmen"><input type="hidden" name="token" value="<?= $tok ?>">

  <div class="spalten spalten--designimport">
    <div class="stapel">
      <section class="tafel">
        <div class="feldgitter">
          <div class="feld">
            <label class="feld-label" for="d-name">Name des neuen Designs</label>
            <input class="eingabe" id="d-name" name="name" value="<?= h((string) ($werte['name'] ?? $analyse['name'])) ?>" required maxlength="60">
          </div>
          <div class="feld feld--schalter">
            <label class="schalter"><input type="checkbox" name="aurora" value="1"<?= !empty($werte['aurora']) ? ' checked' : '' ?>>
              <span class="schalter-bahn" aria-hidden="true"><span class="schalter-punkt"></span></span>
              <span class="schalter-text">Aurora-Verlauf im Hintergrund</span></label>
            <p class="feld-hilfe">Aus: Die Webseite nutzt nur die Flächen des Design Systems.</p>
          </div>
        </div>
      </section>

<?php foreach ($gruppen as $gruppe => $liste): ?>
      <section class="tafel">
        <h2><?= h($gruppe) ?></h2>
        <div class="zuordnung">
<?php foreach ($liste as $k => [$label, $art, $vorgabe, $hilfe]):
    $gewaehlt = (string) ($vorschlag[$k] ?? '');
    $optionen = $nachArt[$art]; ?>
          <div class="zuordnung-zeile" data-zuordnung="<?= h($k) ?>" data-art="<?= h($art) ?>">
            <div class="zuordnung-was">
              <span class="zuordnung-label"><?= h($label) ?></span>
              <?php if ($hilfe !== ''): ?><span class="still klein"><?= h($hilfe) ?></span><?php endif; ?>
            </div>
            <span class="zuordnung-muster" data-muster aria-hidden="true"<?= $art === 'farbe' ? ' style="background:' . h($vorschau[$k]) . '"' : '' ?>><?= $art === 'schrift' ? '<span style="font-family:' . h($vorschau[$k]) . '">Aa</span>' : ($art === 'mass' ? h($vorschau[$k]) : '') ?></span>
            <select class="eingabe" name="zuordnung[<?= h($k) ?>]" data-wahl aria-label="Variable für <?= h($label) ?>">
              <option value="">Vorlage behalten (<?= h(is_bool($vorgabe) ? '' : (string) $vorgabe) ?>)</option>
<?php foreach ($optionen as $n => $w): ?>
              <option value="<?= h($n) ?>" data-wert="<?= h($w) ?>"<?= $n === $gewaehlt ? ' selected' : '' ?>><?= h($n) ?> · <?= h(mb_strimwidth($w, 0, 42, '…')) ?></option>
<?php endforeach; ?>
            </select>
            <input class="eingabe mono" name="eigen[<?= h($k) ?>]" value="<?= h((string) ($eigen[$k] ?? '')) ?>" placeholder="eigener Wert" data-eigen aria-label="Eigener Wert für <?= h($label) ?>" spellcheck="false">
          </div>
<?php endforeach; ?>
        </div>
      </section>
<?php endforeach; ?>

<?php if ($analyse['schriften']): ?>
      <section class="tafel">
        <h2>Schriftdateien</h2>
        <p class="leise klein">Werden mit dem Design gespeichert und von der eigenen Domain geladen — ohne Google Fonts, ohne Übertragung an Dritte.</p>
        <ul class="verlauf">
<?php foreach ($analyse['schriften'] as $s): ?>
          <li><span class="mono klein"><?= h($s['datei']) ?></span> <span class="still klein">— <?= h($s['familie']) ?>, <?= h($s['gewicht']) ?><?= $s['stil'] === 'italic' ? ' kursiv' : '' ?></span></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>
    </div>

    <aside class="stapel designimport-seite">
      <section class="tafel">
        <p class="eyebrow eyebrow--leise">So ungefähr</p>
        <div class="minivorschau" data-minivorschau style="<?php foreach ($vorschau as $k => $v): if (is_bool($v)) { continue; } ?>--m-<?= h($k) ?>:<?= h((string) $v) ?>;<?php endforeach; ?>">
          <div class="minivorschau-kopf"><strong><?= h((string) projekt('name')) ?></strong><span class="minivorschau-knopf">Kontakt</span></div>
          <p class="minivorschau-eyebrow">Kleinzeile</p>
          <p class="minivorschau-titel">Eine klare Botschaft, <span>die hängen bleibt.</span></p>
          <p class="minivorschau-text">Ein, zwei Sätze Einleitung in der Farbe für Zweittext.</p>
          <div class="minivorschau-karte"><strong>Karte</strong><span>Mit Text auf der Kartenfläche.</span></div>
          <span class="minivorschau-knopf minivorschau-knopf--gross">Anfrage senden</span>
        </div>
        <p class="still klein">Die echte Webseite sehen Sie nach dem Speichern über „Vorschau“.</p>
      </section>
      <section class="tafel">
        <p class="eyebrow eyebrow--leise">Lesbarkeit</p>
        <ul class="pruefliste" data-kontraste>
<?php foreach ($kontraste as $kk): ?>
          <li><span class="pille <?= $kk['ok'] ? 'pille--gut' : 'pille--warn' ?>"><?= h(number_format($kk['wert'], 1, ',', '')) ?>:1</span> <span class="klein"><?= h($kk['was']) ?><?= $kk['ok'] ? '' : ' — empfohlen mindestens ' . h(number_format($kk['min'], 1, ',', '')) . ':1' ?></span></li>
<?php endforeach; ?>
        </ul>
      </section>
    </aside>
  </div>

  <div class="speicherleiste">
    <span class="speicherleiste-text">Gespeichert wird ein neues Design — die Webseite ändert sich erst, wenn Sie es verwenden und veröffentlichen.</span>
    <a class="knopf knopf--geist" href="<?= h(url_zu('design')) ?>">Abbrechen</a>
    <button type="submit" class="knopf">Design speichern</button>
  </div>
</form>
