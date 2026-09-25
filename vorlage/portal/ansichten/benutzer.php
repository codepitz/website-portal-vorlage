<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/** Benutzerverwaltung. Erwartet: $alle */
$tok = h(token());
$ichName = (string) ($_SESSION['benutzer'] ?? '');
$rollenAuswahl = static function (string $gewaehlt): string {
    $o = '';
    foreach (ROLLEN as $k => [$name, $text]) {
        $o .= '<option value="' . h($k) . '"' . ($k === $gewaehlt ? ' selected' : '') . '>' . h($name) . ' — ' . h($text) . '</option>';
    }
    return $o;
};
?>
<div class="einleitung">
  <p class="eyebrow eyebrow--leise">Zugänge zum Portal</p>
  <p class="leise">
    Jede Person bekommt ein eigenes Konto — so steht im Protokoll, wer was geändert hat. Wer das Team verlässt,
    wird gesperrt oder gelöscht. Ein neu gesetztes Passwort meldet die Person überall ab.
  </p>
</div>

<div class="tabelle-huelle">
  <table class="tabelle">
    <thead><tr><th>Name</th><th>Rolle</th><th>Zuletzt angemeldet</th><th><span class="nur-leser">Aktionen</span></th></tr></thead>
    <tbody>
<?php foreach ($alle as $name => $b): $ich = $name === $ichName; ?>
      <tr class="<?= !empty($b['gesperrt']) ? 'tabelle-zeile--aus' : '' ?>">
        <td>
          <strong><?= h((string) ($b['anzeige'] ?? $name)) ?></strong><?= $ich ? ' <span class="pille pille--still">Sie</span>' : '' ?>
          <?= !empty($b['gesperrt']) ? ' <span class="pille pille--fehler">gesperrt</span>' : '' ?>
          <br><span class="mono still"><?= h((string) $name) ?></span>
        </td>
        <td><?= h(ROLLEN[$b['rolle'] ?? '']['0'] ?? (string) ($b['rolle'] ?? '')) ?></td>
        <td class="klein leise nowrap"><?= !empty($b['zuletzt']) ? h(zeit_deutsch((string) $b['zuletzt'])) : 'noch nie' ?></td>
        <td class="rechts">
          <details class="aufklapp-menue">
            <summary class="knopf knopf--still knopf--klein">Bearbeiten</summary>
            <div class="aufklapp-menue-inhalt stapel">
              <form method="post" class="stapel">
                <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="aendern"><input type="hidden" name="name" value="<?= h((string) $name) ?>">
                <div class="feld"><label class="feld-label">Angezeigter Name<input class="eingabe" name="anzeige" value="<?= h((string) ($b['anzeige'] ?? '')) ?>"></label></div>
                <div class="feld"><label class="feld-label">Rolle<select class="eingabe" name="rolle"><?= $rollenAuswahl((string) ($b['rolle'] ?? '')) ?></select></label></div>
                <div><button type="submit" class="knopf knopf--zweit knopf--klein">Speichern</button></div>
              </form>
              <form method="post" class="stapel">
                <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="passwort"><input type="hidden" name="name" value="<?= h((string) $name) ?>">
                <div class="feld"><label class="feld-label">Neues Passwort
                  <span class="eingabe-mit-knopf"><input class="eingabe mono" name="passwort" minlength="<?= PASSWORT_MIN ?>" autocomplete="off" required>
                  <button type="button" class="knopf knopf--still knopf--klein" data-passwortvorschlag>Vorschlag</button></span></label>
                  <p class="feld-hilfe">Das Passwort der Person sicher mitteilen (nicht per E-Mail). Sie kann es unter „Mein Konto“ ändern.</p></div>
                <div><button type="submit" class="knopf knopf--zweit knopf--klein">Passwort setzen</button></div>
              </form>
<?php if (!$ich): ?>
              <div class="knopfzeile">
                <form method="post">
                  <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="name" value="<?= h((string) $name) ?>">
                  <input type="hidden" name="tat" value="<?= !empty($b['gesperrt']) ? 'entsperren' : 'sperren' ?>">
                  <button type="submit" class="knopf knopf--still knopf--klein"><?= !empty($b['gesperrt']) ? 'Entsperren' : 'Sperren' ?></button>
                </form>
                <form method="post" data-titel="Konto löschen?" data-ja="Ja, löschen" data-frage="Das Konto „<?= h((string) $name) ?>“ wird endgültig gelöscht. Einträge im Protokoll bleiben erhalten.">
                  <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="name" value="<?= h((string) $name) ?>"><input type="hidden" name="tat" value="loeschen">
                  <button type="submit" class="knopf knopf--gefahr-still knopf--klein">Löschen</button>
                </form>
              </div>
<?php endif; ?>
            </div>
          </details>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>

<section class="tafel abstand-oben">
  <p class="eyebrow eyebrow--leise">Neues Konto</p>
  <h2>Person hinzufügen</h2>
  <form method="post" class="feldgitter">
    <input type="hidden" name="token" value="<?= $tok ?>"><input type="hidden" name="tat" value="anlegen">
    <div class="feld">
      <label class="feld-label" for="n-anzeige">Name</label>
      <input class="eingabe" id="n-anzeige" name="anzeige" placeholder="z. B. Sabine Koch" autocomplete="off">
    </div>
    <div class="feld">
      <label class="feld-label" for="n-name">Benutzername <span class="pflichtstern">*</span></label>
      <input class="eingabe" id="n-name" name="name" required pattern="[a-z0-9._-]{3,32}" placeholder="sabine.koch" autocapitalize="none" autocomplete="off">
      <p class="feld-hilfe">Kleinbuchstaben, Ziffern, Punkt, Strich.</p>
    </div>
    <div class="feld">
      <label class="feld-label" for="n-rolle">Rolle</label>
      <select class="eingabe" id="n-rolle" name="rolle"><?= $rollenAuswahl('redaktion') ?></select>
    </div>
    <div class="feld">
      <label class="feld-label" for="n-passwort">Erstes Passwort <span class="pflichtstern">*</span></label>
      <span class="eingabe-mit-knopf">
        <input class="eingabe mono" id="n-passwort" name="passwort" required minlength="<?= PASSWORT_MIN ?>" autocomplete="off">
        <button type="button" class="knopf knopf--still knopf--klein" data-passwortvorschlag>Vorschlag</button>
      </span>
    </div>
    <div class="feld feld--breit"><button type="submit" class="knopf">Konto anlegen</button></div>
  </form>
</section>
