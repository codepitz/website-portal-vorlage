<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Erwartet: $fehler, $weiter */ ?>
<h1>Anmelden</h1>
<p class="leise tor-lead">Hier pflegen Sie die Inhalte der Webseite.</p>

<?php if ($fehler): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div><?= h($fehler) ?></div>
</div>
<?php endif; ?>

<form method="post" class="stapel">
  <input type="hidden" name="tat" value="anmelden">
  <input type="hidden" name="token" value="<?= h(token()) ?>">
  <input type="hidden" name="weiter" value="<?= h($weiter) ?>">

  <div class="feld">
    <label class="feld-label" for="benutzer">Benutzername</label>
    <input class="eingabe" type="text" id="benutzer" name="benutzer" autocomplete="username" autocapitalize="none" autofocus required
           value="<?= h((string) ($_POST['benutzer'] ?? '')) ?>">
  </div>

  <div class="feld">
    <label class="feld-label" for="passwort">Passwort</label>
    <input class="eingabe" type="password" id="passwort" name="passwort" autocomplete="current-password" required>
  </div>

  <button type="submit" class="knopf knopf--gross knopf--voll">Anmelden</button>
</form>
<p class="still klein tor-fuss">Passwort vergessen? Wer die Rolle „Verwaltung“ hat, kann unter „Benutzer“ ein neues setzen.</p>
