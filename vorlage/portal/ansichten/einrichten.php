<?php
if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }
/**
 * Erste Einrichtung. Läuft nur, solange es keine konfig.php gibt.
 * Erwartet: $fehler, $werte, $geraten
 */
$w = static fn(string $k, string $vorgabe = ''): string => h((string) ($werte[$k] ?? $vorgabe));
?>
<h1>Einrichtung</h1>
<p class="leise tor-lead">
  Einmalig: das erste Konto anlegen und dem Portal sagen, wo Inhalte und Webseite liegen.
  Danach ist diese Seite gesperrt.
</p>

<?php if ($fehler): ?>
<div class="meldung meldung--fehler" role="alert">
  <span class="meldung-zeichen" aria-hidden="true">!</span>
  <div>Das hat noch nicht geklappt:<ul><?php foreach ($fehler as $f): ?><li><?= h($f) ?></li><?php endforeach; ?></ul></div>
</div>
<?php endif; ?>

<form method="post" class="feldgitter">
  <input type="hidden" name="tat" value="einrichten">

  <h2 class="feld--breit abschnittstitel">Erstes Konto (Verwaltung)</h2>
  <div class="feld">
    <label class="feld-label" for="anzeige">Ihr Name</label>
    <input class="eingabe" type="text" id="anzeige" name="anzeige" autocomplete="name" value="<?= $w('anzeige', (string) (projekt('admin')['anzeige'] ?? '')) ?>" placeholder="z. B. Laura Neumann">
  </div>
  <div class="feld">
    <label class="feld-label" for="benutzer">Benutzername</label>
    <input class="eingabe" type="text" id="benutzer" name="benutzer" required autocomplete="username" autocapitalize="none"
           pattern="[a-z0-9._-]{3,32}" value="<?= $w('benutzer', (string) (projekt('admin')['benutzer'] ?? '')) ?>" placeholder="<?= h((string) (projekt('admin')['benutzer'] ?? 'vorname.nachname')) ?>">
    <p class="feld-hilfe">Kleinbuchstaben, Ziffern, Punkt, Strich.</p>
  </div>
  <div class="feld">
    <label class="feld-label" for="passwort">Passwort</label>
    <input class="eingabe" type="password" id="passwort" name="passwort" required autocomplete="new-password" minlength="<?= PASSWORT_MIN ?>">
    <p class="feld-hilfe">Mindestens <?= PASSWORT_MIN ?> Zeichen — am besten aus dem Passwortspeicher.</p>
  </div>
  <div class="feld">
    <label class="feld-label" for="passwort2">Passwort wiederholen</label>
    <input class="eingabe" type="password" id="passwort2" name="passwort2" required autocomplete="new-password" minlength="<?= PASSWORT_MIN ?>">
  </div>

  <h2 class="feld--breit abschnittstitel">Ordner</h2>
  <div class="feld feld--breit">
    <label class="feld-label" for="privat">Privater Ordner</label>
    <input class="eingabe mono" type="text" id="privat" name="privat" required value="<?= $w('privat', $geraten['privat']) ?>">
    <p class="feld-hilfe">Enthält bauen.php, daten/ und design/. Liegt außerhalb jedes Docroots — neben dem Portal-Ordner. Meist schon richtig gefunden.</p>
  </div>
  <div class="feld feld--breit">
    <label class="feld-label" for="site">Ordner der Webseite (Docroot der Hauptdomain)</label>
    <input class="eingabe mono" type="text" id="site" name="site" required value="<?= $w('site', $geraten['site']) ?>">
    <p class="feld-hilfe">Dorthin schreibt der Generator die fertige Webseite — der Ordner, auf den die Hauptdomain zeigt.</p>
  </div>
  <div class="feld feld--breit">
    <label class="feld-label" for="website">Adresse der Webseite</label>
    <input class="eingabe" type="url" id="website" name="website" value="<?= $w('website', (string) (live_laden('allgemein')['domain'] ?? '')) ?>" placeholder="https://www.ihre-domain.de">
  </div>

  <div class="feld feld--breit">
    <button type="submit" class="knopf knopf--gross">Einrichten</button>
  </div>
</form>
