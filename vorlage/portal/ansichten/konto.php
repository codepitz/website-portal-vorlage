<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Eigenes Konto. Erwartet: $ich */
$tok = h(token());
?>
<div class="spalten">
  <section class="tafel">
    <p class="eyebrow eyebrow--leise">Konto</p>
    <h2><?= h((string) $ich['anzeige']) ?></h2>
    <dl class="kennzahlen kennzahlen--liste">
      <div><dt>Benutzername</dt><dd class="mono"><?= h((string) $ich['name']) ?></dd></div>
      <div><dt>Rolle</dt><dd><?= h(ROLLEN[$ich['rolle']][0] ?? $ich['rolle']) ?></dd></div>
      <div><dt>Darf</dt><dd class="klein"><?= h(ROLLEN[$ich['rolle']][1] ?? '') ?></dd></div>
    </dl>
    <form method="post" class="stapel abstand-oben">
      <input type="hidden" name="tat" value="anzeige"><input type="hidden" name="token" value="<?= $tok ?>">
      <div class="feld">
        <label class="feld-label" for="anzeige">Angezeigter Name</label>
        <input class="eingabe" id="anzeige" name="anzeige" value="<?= h((string) $ich['anzeige']) ?>" autocomplete="name">
      </div>
      <div><button type="submit" class="knopf knopf--zweit knopf--klein">Namen speichern</button></div>
    </form>
  </section>

  <section class="tafel">
    <p class="eyebrow eyebrow--leise">Sicherheit</p>
    <h2>Passwort ändern</h2>
    <form method="post" class="stapel">
      <input type="hidden" name="tat" value="passwort"><input type="hidden" name="token" value="<?= $tok ?>">
      <input type="text" name="benutzer" value="<?= h((string) $ich['name']) ?>" autocomplete="username" hidden>
      <div class="feld">
        <label class="feld-label" for="aktuell">Bisheriges Passwort</label>
        <input class="eingabe" type="password" id="aktuell" name="aktuell" required autocomplete="current-password">
      </div>
      <div class="feld">
        <label class="feld-label" for="neu">Neues Passwort</label>
        <input class="eingabe" type="password" id="neu" name="neu" required minlength="<?= PASSWORT_MIN ?>" autocomplete="new-password">
        <p class="feld-hilfe">Mindestens <?= PASSWORT_MIN ?> Zeichen.</p>
      </div>
      <div class="feld">
        <label class="feld-label" for="neu2">Neues Passwort wiederholen</label>
        <input class="eingabe" type="password" id="neu2" name="neu2" required minlength="<?= PASSWORT_MIN ?>" autocomplete="new-password">
      </div>
      <div><button type="submit" class="knopf">Passwort ändern</button></div>
    </form>
  </section>
</div>
