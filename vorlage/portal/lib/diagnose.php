<?php
/**
 * Systemprüfung: Läuft das hier so, wie es soll?
 *
 * Geprüft werden PHP, die drei Ordner, die Schreibrechte, die Abschottung des
 * privaten Bereichs und der Zustand der Webseite. Jeder Punkt liefert
 * art (gut | warnung | fehler), titel, wert und eine Erklärung in Klartext.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

function pruefpunkt(string $art, string $titel, string $wert, string $text): array
{
    return ['art' => $art, 'titel' => $titel, 'wert' => $wert, 'text' => $text];
}

function diagnose(): array
{
    return [
        'Grundlage' => diagnose_php(),
        'Ordner' => diagnose_ordner(),
        'Schreibrechte' => diagnose_schreibrechte(),
        'Abschottung' => diagnose_abschottung(),
        'Webseite' => diagnose_webseite(),
    ];
}

function diagnose_php(): array
{
    $p = [];
    $p[] = pruefpunkt(PHP_VERSION_ID >= 80100 ? 'gut' : 'fehler', 'PHP-Version', PHP_VERSION,
        PHP_VERSION_ID >= 80100 ? 'Reicht aus. Das Portal braucht PHP 8.1 oder neuer.'
            : 'Zu alt. Im Kundenmenü lässt sich die PHP-Version pro Domain umstellen.');

    $p[] = pruefpunkt('gut', 'Zeitzone', date_default_timezone_get() . ' — ' . date('d.m.Y, H:i') . ' Uhr',
        'Fest eingestellt, damit die Zeiträume der Hinweise zum richtigen Tag wechseln.');

    $grenze = upload_grenze();
    $p[] = pruefpunkt($grenze >= 64 * 1048576 ? 'gut' : ($grenze >= 12 * 1048576 ? 'warnung' : 'fehler'),
        'Grenze für Uploads', groesse_lesbar($grenze === PHP_INT_MAX ? 0 : $grenze),
        $grenze >= 64 * 1048576
            ? 'Bilder, PDFs und auch längere Videos lassen sich hochladen.'
            : 'Größere Dateien — vor allem Videos — werden abgewiesen. upload_max_filesize und post_max_size lassen sich im Kundenmenü erhöhen (empfohlen: 100M).');

    $p[] = extension_loaded('gd')
        ? pruefpunkt('gut', 'Bildbearbeitung (GD)', 'vorhanden', 'Fotos werden beim Hochladen gedreht, verkleinert und von Aufnahmedaten wie dem Ort befreit.')
        : pruefpunkt('warnung', 'Bildbearbeitung (GD)', 'fehlt', 'Bilder werden unverändert übernommen — auch mit Aufnahmeort in den Metadaten. Im Kundenmenü die PHP-Erweiterung „gd“ aktivieren oder Fotos vorher bereinigen.');

    $mail = function_exists('mail') && !in_array('mail', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true);
    $modus = entwurf_laden('formular')['modus'] ?? 'server';
    $smtp = smtp_werte();
    if ($smtp['host'] !== '' && $smtp['benutzer'] !== '' && $smtp['hatPasswort']) {
        $p[] = pruefpunkt('gut', 'E-Mail-Versand (SMTP)', $smtp['benutzer'] . ' über ' . $smtp['host'] . ':' . $smtp['port'],
            'Das Kontaktformular sendet über das Postfach. Ob es klappt, zeigt „Testmail senden“ unter Einstellungen.');
    } else {
        $p[] = pruefpunkt($modus === 'server' ? 'warnung' : 'gut', 'E-Mail-Versand (SMTP)', 'nicht eingerichtet',
            'Unter Einstellungen → E-Mail-Versand Postfach und Passwort eintragen. Bis dahin sendet das Formular über mail() — solche Mails landen leichter im Spam.');
    }
    $p[] = $mail
        ? pruefpunkt('gut', 'E-Mail-Versand (mail)', 'verfügbar', 'Ersatzweg, falls SMTP nicht eingerichtet ist oder ausfällt.')
        : pruefpunkt('warnung', 'E-Mail-Versand (mail)', 'gesperrt', 'PHP darf hier nicht über mail() senden. Dann unbedingt SMTP einrichten.');

    return $p;
}

function diagnose_ordner(): array
{
    $p = [];
    $privat = rtrim(pfad_privat(), '/');
    $site = rtrim(pfad_site(), '/');

    $p[] = is_file($privat . '/bauen.php')
        ? pruefpunkt('gut', 'Privater Ordner', $privat, 'Enthält bauen.php und die Inhalte.')
        : pruefpunkt('fehler', 'Privater Ordner', $privat ?: '(nicht gesetzt)', 'Hier liegt keine bauen.php — ohne sie lässt sich nichts veröffentlichen.');

    $fehlend = [];
    foreach (['stammdaten', 'allgemein', 'startseite', 'formular', 'rechtliches', 'hinweise', 'angebote', 'team', 'preise', 'zeiten'] as $d) {
        if (!is_file($privat . '/daten/' . $d . '.json')) {
            $fehlend[] = $d . '.json';
        }
    }
    $p[] = $fehlend
        ? pruefpunkt('fehler', 'Inhaltsdateien', count($fehlend) . ' fehlen', 'Es fehlen: ' . implode(', ', $fehlend) . '.')
        : pruefpunkt('gut', 'Inhaltsdateien', 'vollständig', 'Alle Inhaltsdateien liegen in daten/.');

    $vorlagen = ['seite.css', 'seite.js', 'anfrage.php', 'site.htaccess'];
    $fehlendV = array_values(array_filter($vorlagen, static fn($v) => !is_file($privat . '/vorlagen/' . $v)));
    $p[] = $fehlendV
        ? pruefpunkt('fehler', 'Vorlagen der Webseite', count($fehlendV) . ' fehlen', 'Es fehlen in privat/vorlagen: ' . implode(', ', $fehlendV) . '.')
        : pruefpunkt('gut', 'Vorlagen der Webseite', 'vollständig', 'Gestaltung, Skript, Formular-Empfang und .htaccess der Webseite.');

    $designs = design_liste();
    $p[] = $designs
        ? pruefpunkt('gut', 'Designs', count($designs) . ' vorhanden', 'Aktiv: ' . (design_laden(design_aktiv())['name'] ?? design_aktiv()) . '.')
        : pruefpunkt('fehler', 'Designs', 'keins gefunden', 'Im Ordner privat/design/ liegt kein Design. Bitte den Ordner neu hochladen.');

    $p[] = is_dir($site)
        ? pruefpunkt('gut', 'Ordner der Webseite', $site, 'Dorthin schreibt der Generator.')
        : pruefpunkt('fehler', 'Ordner der Webseite', $site ?: '(nicht gesetzt)', 'Den Ordner gibt es nicht.');

    $p[] = is_file(ADMIN_WURZEL . '/.htaccess')
        ? pruefpunkt('gut', '.htaccess des Portals', 'vorhanden', 'Sperrt konfig.php, lib/ und ansichten/ und hält Suchmaschinen fern.')
        : pruefpunkt('fehler', '.htaccess des Portals', 'fehlt', 'Die Datei ist versteckt und wurde vermutlich beim Hochladen übersehen. Im SFTP-Programm „versteckte Dateien anzeigen“ einschalten.');

    $p[] = is_file($privat . '/.htaccess')
        ? pruefpunkt('gut', '.htaccess des privaten Ordners', 'vorhanden', 'Zweite Sperre, falls der Ordner doch einmal über eine Adresse erreichbar wäre.')
        : pruefpunkt('warnung', '.htaccess des privaten Ordners', 'fehlt', 'Nur eine Rückfallebene — wichtiger ist, dass der Ordner außerhalb jedes Docroots liegt.');

    $p[] = is_file($site . '/.htaccess')
        ? pruefpunkt('gut', '.htaccess der Webseite', 'vorhanden', 'HTTPS, Sicherheits-Header und Zwischenspeicher.')
        : pruefpunkt('warnung', '.htaccess der Webseite', 'fehlt', 'Wird beim nächsten Veröffentlichen angelegt. Unter nginx wirkungslos — dann stellt der Anbieter HTTPS selbst ein.');

    return $p;
}

function diagnose_schreibrechte(): array
{
    $p = [];
    $ordner = [
        'Inhalte (daten/)' => [pfad_daten(), 'Dorthin schreibt „Veröffentlichen“.'],
        'Entwürfe (entwurf/)' => [pfad_entwurf(), 'Noch nicht veröffentlichte Änderungen.'],
        'Ablage (ablage/)' => [pfad_ablage(), 'Benutzerkonten, Hinweise und Protokoll.'],
        'Sicherungen (sicherung/)' => [pfad_sicherung(), 'Kopie vor jedem Veröffentlichen.'],
        'Webseite' => [pfad_site(), 'Dorthin schreibt der Generator.'],
        'Medien (medien/)' => [pfad_medien(), 'Hochgeladene Bilder, Videos und PDFs.'],
        'Designs (design/)' => [pfad_design(), 'Hochgeladene und bearbeitete Designs.'],
    ];
    foreach ($ordner as $name => [$pfad, $wofuer]) {
        $pfad = rtrim($pfad, '/');
        if (!is_dir($pfad)) {
            $p[] = is_writable(dirname($pfad))
                ? pruefpunkt('gut', $name, 'wird beim ersten Mal angelegt', $wofuer)
                : pruefpunkt('fehler', $name, 'fehlt und lässt sich nicht anlegen', $wofuer . ' Dafür braucht ' . dirname($pfad) . ' Schreibrecht.');
            continue;
        }
        $p[] = is_writable($pfad)
            ? pruefpunkt('gut', $name, 'beschreibbar', $wofuer)
            : pruefpunkt('fehler', $name, 'schreibgeschützt', $wofuer . ' Im FTP-Programm die Rechte auf 755 setzen.');
    }
    return $p;
}

function diagnose_abschottung(): array
{
    $p = [];
    $privat = rtrim(pfad_privat(), '/');
    $site = rtrim(pfad_site(), '/');

    if (liegt_in($privat, $site)) {
        $p[] = pruefpunkt('fehler', 'Privater Ordner nicht im Web', 'liegt im Docroot der Webseite',
            'Damit könnte jeder die Benutzerkonten und Inhalte herunterladen. Der Ordner gehört neben den Docroot, nicht hinein.');
    } elseif (liegt_in($privat, ADMIN_WURZEL)) {
        $p[] = pruefpunkt('fehler', 'Privater Ordner nicht im Web', 'liegt im Docroot des Portals', 'Der Ordner gehört außerhalb jedes Docroots.');
    } else {
        $p[] = pruefpunkt('gut', 'Privater Ordner nicht im Web', 'außerhalb der Docroots', 'Benutzerkonten, Inhalte und Generator sind über keine Adresse erreichbar.');
    }

    $p[] = liegt_in(ADMIN_WURZEL, $site)
        ? pruefpunkt('warnung', 'Portal getrennt von der Webseite', 'liegt im Docroot der Webseite', 'Funktioniert, aber sauberer ist eine eigene Subdomain (z. B. portal.ihre-domain.de).')
        : pruefpunkt('gut', 'Portal getrennt von der Webseite', 'eigener Docroot', 'Das Portal ist nur über seine eigene Adresse erreichbar.');

    $basis = trim((string) ini_get('open_basedir'));
    if ($basis === '') {
        $p[] = pruefpunkt('gut', 'open_basedir', 'nicht gesetzt', 'PHP darf auf alle nötigen Ordner zugreifen.');
    } else {
        $erlaubt = array_filter(array_map('trim', explode(PATH_SEPARATOR, $basis)));
        $draussen = [];
        foreach (['privater Ordner' => $privat, 'Webseiten-Ordner' => $site] as $name => $pfad) {
            $drin = false;
            foreach ($erlaubt as $e) {
                $drin = $drin || liegt_in($pfad, $e);
            }
            if (!$drin) {
                $draussen[] = $name;
            }
        }
        $p[] = $draussen
            ? pruefpunkt('fehler', 'open_basedir', $basis, 'PHP darf nicht auf den ' . implode(' und den ', $draussen) . ' zugreifen.')
            : pruefpunkt('gut', 'open_basedir', $basis, 'Alle Ordner liegen im erlaubten Bereich.');
    }

    $p[] = https_aktiv()
        ? pruefpunkt('gut', 'Verschlüsselte Verbindung', 'HTTPS', 'Passwort und Sitzung laufen verschlüsselt.')
        : pruefpunkt(lokal_aufgerufen() ? 'gut' : 'fehler', 'Verschlüsselte Verbindung', lokal_aufgerufen() ? 'lokal, unverschlüsselt' : 'unverschlüsselt',
            lokal_aufgerufen() ? 'Am eigenen Rechner ist das in Ordnung.' : 'Das Passwort geht im Klartext über die Leitung. Im Kundenmenü ein SSL-Zertifikat für diese Subdomain aktivieren.');

    $verwaltung = verwaltung_anzahl(benutzer_alle());
    $p[] = $verwaltung >= 2
        ? pruefpunkt('gut', 'Konten der Verwaltung', $verwaltung . ' aktiv', 'Fällt ein Zugang aus, kann ein anderer die Konten verwalten.')
        : pruefpunkt('warnung', 'Konten der Verwaltung', $verwaltung . ' aktiv', 'Empfohlen ist ein zweites Verwaltungs-Konto als Rückfallebene. Notfalls hilft php werkzeuge/benutzer.php.');

    return $p;
}

function diagnose_webseite(): array
{
    $p = [];
    $domain = rtrim((string) (live_laden('allgemein')['domain'] ?? ''), '/');
    $p[] = $domain !== ''
        ? pruefpunkt('gut', 'Adresse der Webseite', $domain, 'Daraus entstehen sitemap.xml und die Vorschau beim Teilen.')
        : pruefpunkt('warnung', 'Adresse der Webseite', '(leer)', 'Unter „Webseite allgemein“ eintragen.');

    $index = pfad_site('index.html');
    $gebaut = is_file($index) && str_contains((string) file_get_contents($index), 'data-ws-seite');
    $p[] = $gebaut
        ? pruefpunkt('gut', 'Erzeugte Webseite', 'vorhanden', 'Die Webseite wurde aus dem Portal gebaut.')
        : pruefpunkt('warnung', 'Erzeugte Webseite', 'noch nicht erzeugt', 'Einmal „Webseite neu erzeugen“ (unter „Veröffentlichen“) schreibt sie.');

    $indexieren = !empty(live_laden('allgemein')['seo']['indexieren']);
    $p[] = $indexieren
        ? pruefpunkt('gut', 'Suchmaschinen', 'erlaubt', 'Google & Co. dürfen die Webseite aufnehmen.')
        : pruefpunkt('warnung', 'Suchmaschinen', 'gesperrt (noindex)', 'Richtig, solange die Seite im Aufbau ist. Zum Start unter „Webseite allgemein“ einschalten und veröffentlichen.');

    $p[] = class_exists('ZipArchive')
        ? pruefpunkt('gut', 'Design-Upload (ZIP)', 'möglich', 'Ein Claude Design System lässt sich als ZIP-Datei hochladen.')
        : pruefpunkt('warnung', 'Design-Upload (ZIP)', 'ZipArchive fehlt', 'ZIP-Dateien lassen sich hier nicht entpacken. Stattdessen die CSS-Datei des Design Systems (styles.css bzw. _ds_bundle.css) hochladen.');

    $letzte = letzte_veroeffentlichung();
    $p[] = $letzte
        ? pruefpunkt('gut', 'Zuletzt veröffentlicht', zeit_deutsch((string) $letzte['zeit']), 'Von ' . ($letzte['benutzer'] ?: '—') . '.')
        : pruefpunkt('warnung', 'Zuletzt veröffentlicht', 'noch nie', 'Aus diesem Portal wurde bisher nicht veröffentlicht.');

    return $p;
}

function diagnose_bilanz(array $gruppen): array
{
    $zahl = ['gut' => 0, 'warnung' => 0, 'fehler' => 0];
    foreach ($gruppen as $punkte) {
        foreach ($punkte as $pp) {
            $zahl[$pp['art']]++;
        }
    }
    return $zahl;
}
