<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Pfade und Sitzungsdauer.
 * Erwartet: $werte, $geraten, $probleme, $zumKopieren, $smtp, $smtpEmpfaenger
 */
$w = static fn(string $k): string => h((string) ($werte[$k] ?? ''));
$vorschlag = static function (string $feld, string $gefunden) use ($werte): string {
    if ($gefunden === '' || $gefunden === (string) ($werte[$feld] ?? '')) {
        return '';
    }
    return '<p class="feld-hilfe">Gefunden: <button type="button" class="verweis mono" data-einsetzen="' . h($feld) . '">' . h($gefunden) . '</button> — zum Übernehmen klicken.</p>';
};
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">konfig.php</p>
  <p class="leise">Wo das Portal Inhalte und Webseite findet. Nach einem Umzug auf einen anderen Webspace ist das meist die erste Stelle, die angepasst werden muss.</p>
</div>

<?php if ($probleme): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div>So kann nicht veröffentlicht werden:<ul><?php foreach ($probleme as $p): ?><li><?= h($p) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<?php if ($zumKopieren): ?>
<section class="tafel">
  <h2>Bitte per FTP eintragen</h2>
  <p class="leise">Dem Webserver fehlt das Schreibrecht auf konfig.php. Diesen Inhalt in <span class="mono">konfig.php</span> im Portal-Ordner speichern:</p>
  <pre class="codeblock"><?= h($zumKopieren) ?></pre>
</section>
<?php endif; ?>

<form method="post" class="feldgitter">
  <input type="hidden" name="tat" value="speichern"><input type="hidden" name="token" value="<?= h(token()) ?>">
  <div class="feld feld--breit">
    <label class="feld-label" for="privat">Privater Ordner</label>
    <input class="eingabe mono" id="privat" name="privat" value="<?= $w('privat') ?>" required>
    <?= $vorschlag('privat', $geraten['privat']) ?>
  </div>
  <div class="feld feld--breit">
    <label class="feld-label" for="site">Ordner der Webseite (Docroot)</label>
    <input class="eingabe mono" id="site" name="site" value="<?= $w('site') ?>" required>
    <?= $vorschlag('site', $geraten['site']) ?>
  </div>
  <div class="feld">
    <label class="feld-label" for="website">Adresse der Webseite</label>
    <input class="eingabe" type="url" id="website" name="website" value="<?= $w('website') ?>">
  </div>
  <div class="feld">
    <label class="feld-label" for="sitzungsdauer">Abmelden nach (Minuten ohne Aktivität)</label>
    <input class="eingabe" type="number" min="5" max="1440" id="sitzungsdauer" name="sitzungsdauer" value="<?= $w('sitzungsdauer') ?>">
  </div>
  <div class="feld feld--breit"><button type="submit" class="knopf">Speichern</button></div>
</form>

<section class="tafel" id="versand">
  <h2>E-Mail-Versand des Kontaktformulars</h2>
  <p class="leise">Mit diesen Angaben verschickt die Webseite Anfragen direkt an <?= $smtpEmpfaenger !== '' ? '<span class="mono">' . h($smtpEmpfaenger) . '</span>' : 'Sie' ?> — über den Postausgangsserver des Postfachs, ohne dass sich bei Besuchern ein E-Mail-Programm öffnet. Die Angaben stehen im Kundenbereich Ihres Hosters bei den E-Mail-Einstellungen<?= ($ho = (string) (projekt('hoster')['name'] ?? '')) !== '' ? ' (' . h($ho) . ')' : '' ?>. Voraussetzung: Im Bereich „Kontaktformular“ ist „Über den Webserver senden“ gewählt.</p>
  <form method="post" class="feldgitter">
    <input type="hidden" name="tat" value="smtp"><input type="hidden" name="token" value="<?= h(token()) ?>">
    <div class="feld">
      <label class="feld-label" for="smtp_host">Postausgangsserver (SMTP)</label>
      <input class="eingabe mono" id="smtp_host" name="smtp_host" value="<?= h($smtp['host']) ?>" placeholder="<?= h((string) (projekt('hoster')['smtp'] ?? 'smtp.ihr-anbieter.de')) ?>">
    </div>
    <div class="feld">
      <label class="feld-label" for="smtp_port">Port</label>
      <select class="eingabe" id="smtp_port" name="smtp_port">
        <option value="465"<?= $smtp['port'] === 465 ? ' selected' : '' ?>>465 — SSL/TLS (empfohlen)</option>
        <option value="587"<?= $smtp['port'] === 587 ? ' selected' : '' ?>>587 — STARTTLS</option>
      </select>
    </div>
    <div class="feld">
      <label class="feld-label" for="smtp_benutzer">Benutzername (vollständige E-Mail-Adresse)</label>
      <input class="eingabe" type="email" id="smtp_benutzer" name="smtp_benutzer" value="<?= h($smtp['benutzer']) ?>" autocomplete="off" placeholder="webseite@ihre-domain.de">
      <p class="feld-hilfe">Die Mails gehen mit dieser Adresse als Absender raus. Antworten landen über „Antworten an“ direkt bei der anfragenden Person.</p>
    </div>
    <div class="feld">
      <label class="feld-label" for="smtp_passwort">Passwort des Postfachs</label>
      <input class="eingabe" type="password" id="smtp_passwort" name="smtp_passwort" autocomplete="new-password" placeholder="<?= $smtp['hatPasswort'] ? '•••••••• (gespeichert)' : '' ?>">
      <p class="feld-hilfe"><?= $smtp['hatPasswort'] ? 'Ein Passwort ist gespeichert. Leer lassen = unverändert.' : 'Noch kein Passwort gespeichert.' ?> Es liegt nur im privaten Ordner auf dem Server und wird nie angezeigt.</p>
      <?php if ($smtp['hatPasswort']): ?>
      <label class="feld-hilfe"><input type="checkbox" name="smtp_loeschen" value="1"> Gespeichertes Passwort entfernen</label>
      <?php endif; ?>
    </div>
    <div class="feld feld--breit"><button type="submit" class="knopf">Zugangsdaten speichern</button></div>
  </form>
  <form method="post">
    <input type="hidden" name="tat" value="smtp-test"><input type="hidden" name="token" value="<?= h(token()) ?>">
    <button type="submit" class="knopf knopf--zweit"<?= $smtp['hatPasswort'] ? '' : ' disabled' ?>>Testmail senden</button>
  </form>
</section>
