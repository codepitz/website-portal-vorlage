/* ===========================================================================
   Anleitung „Online bringen“ — Schritt für Schritt, mit den eigenen Werten.
   Wird im Assistenten Schritt für Schritt gezeigt (Weiter-Knopf) und
   zusätzlich als ANLEITUNG.html ins Paket gelegt.
   =========================================================================== */

(function () {
  'use strict';

  function e(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (z) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[z]; }); }
  function c(s) { return '<code>' + e(s) + '</code>'; }

  /**
   * Die Schritte. Jeder Schritt:
   *   titel, kurz, wo (Menüpfad), schritte (HTML-Sätze), werte ([Label, Wert]),
   *   bild (Name in WSBilder, optional mit Zusatz), tipp, warnung, erledigt (Beschriftung des Hakens)
   */
  function schritte(d) {
    var A = window.WSErzeugen.abgeleitet(d);
    var h = A.hoster;
    var p = h.pfade;
    var o = A.ordner;
    var mails = (d.postfaecher || []).map(function (m) { return A.postfach(m.name); });
    var sftpBenutzer = h.sftp.benutzer.replace('{domain}', A.domain);

    return [
      {
        titel: 'Überblick: So kommt das Portal ins Netz',
        kurz: 'Was Sie brauchen und wie die Teile zusammenhängen.',
        schritte: [
          'Legen Sie sich bereit: Zugang zum ' + e(h.bereich) + ' (' + e(h.loginText) + '), das entpackte Paket ' + c(A.wurzel + '.zip') + ' und ein SFTP-Programm — z. B. <b>FileZilla</b> (Windows, macOS, Linux), <b>Cyberduck</b> (macOS/Windows) oder <b>WinSCP</b> (Windows).',
          'Öffnen Sie einen <b>Passwortmanager</b> (z. B. der des Betriebssystems, 1Password, Bitwarden). Sie vergeben gleich mehrere Passwörter: SFTP, Postfächer, Portal.',
          'Es gibt zwei Adressen: die Webseite ' + c(A.webseite) + ' und das Portal ' + c(A.portalUrl) + '. Jede zeigt auf einen eigenen Ordner. Der dritte Ordner ' + c('/' + o.privat) + ' bekommt <b>keine</b> Adresse — dort liegen Konten und Inhalte.',
          'Rechnen Sie mit 45–90 Minuten. Wartezeiten entstehen vor allem bei neuen Domains und SSL-Zertifikaten.'
        ],
        bild: 'architektur',
        bildText: 'Aufbau: zwei Adressen, drei Ordner. „Veröffentlichen“ im Portal schreibt die fertige Webseite.',
        tipp: 'Alles lässt sich später ändern. Diese Anleitung liegt auch als ANLEITUNG.html im Paket — zum Ausdrucken oder Weitergeben.',
        erledigt: 'Ich habe alles bereit'
      },
      {
        titel: 'Im Kundenbereich anmelden und das Paket prüfen',
        kurz: 'Hat der Tarif alles, was das Portal braucht?',
        wo: p.paket,
        schritte: [
          'Melden Sie sich an: ' + e(h.loginText) + (h.login ? ' — ' + c(h.login) : '') + '.',
          'Prüfen Sie in der Paketübersicht: <b>PHP 8.1 oder neuer</b>, <b>SSL-Zertifikate</b>, <b>E-Mail-Postfächer</b> und <b>SFTP</b> (oder FTP mit TLS).',
          'Notieren Sie Ihre Kundennummer — die fragt der Support als Erstes.'
        ],
        bild: 'paket',
        bildText: 'Beispielhafte Paketübersicht — wichtig sind PHP ①, SFTP ② und SSL.',
        warnung: 'Reine „Homepage-Baukasten“-Tarife ohne PHP und ohne Webspace-Zugang reichen nicht. Im Zweifel beim Anbieter nach „Webhosting mit PHP 8 und SFTP“ fragen.',
        erledigt: 'Paket hat PHP, SSL, E-Mail und SFTP'
      },
      {
        titel: 'Domain bestellen oder zuordnen',
        kurz: 'Die Adresse ' + A.domain + ' muss im Paket liegen.',
        wo: p.domain,
        schritte: [
          'Steht ' + c(A.domain) + ' schon in der Domainverwaltung? Dann weiter mit dem nächsten Schritt ①.',
          'Neue Domain: über „Domain bestellen“ registrieren ②. Sie ist meist nach wenigen Minuten aktiv.',
          'Domain liegt noch bei einem anderen Anbieter? Entweder umziehen (dort den <b>Auth-Code</b> anfordern und hier „Domain umziehen“ wählen) oder beim alten Anbieter die DNS-Einträge (A/AAAA) auf diesen Webspace zeigen lassen.',
          'Die Variante ' + c('www.' + A.domain) + ' gibt es automatisch mit — sie bekommt gleich dasselbe Ziel.'
        ],
        werte: [['Hauptdomain', A.domain], ['Adresse der Webseite', A.webseite], ['Adresse des Portals', A.portalUrl]],
        bild: 'domain',
        bildText: 'Die Domain in der Domainverwaltung — noch ohne Ziel.',
        tipp: 'Ein Domain-Umzug kann einige Tage dauern. Das Portal lässt sich solange schon auf einer Test-Subdomain des Anbieters einrichten.',
        erledigt: 'Domain ist im Paket'
      },
      {
        titel: 'SFTP-Zugang einrichten',
        kurz: 'Damit Sie Dateien sicher auf den Webspace laden können.',
        wo: p.sftp,
        schritte: [
          'Öffnen Sie im Kundenbereich den Bereich für SFTP/SSH bzw. die Zugänge.',
          'Legen Sie ein <b>eigenes, starkes Passwort</b> für den Zugang fest ② (mindestens 16 Zeichen, im Passwortmanager speichern) und speichern Sie ③.',
          'Notieren Sie Server, Port und Benutzername ① — die Werte stehen auch hier unten.'
        ],
        werte: [['Protokoll', 'SFTP (SSH File Transfer Protocol)'], ['Server', h.sftp.host.replace('{domain}', A.domain)], ['Port', String(h.sftp.port)], ['Benutzername', sftpBenutzer], ['Passwort', '(Ihr neues SFTP-Passwort — nur im Passwortmanager)']],
        bild: 'sftpZugang',
        bildText: 'SFTP-Zugang mit Server, Benutzer und neuem Passwort.',
        tipp: h.hinweise.sftp,
        warnung: 'Nie unverschlüsseltes FTP verwenden — dabei gehen Passwort und Dateien im Klartext durchs Netz.',
        erledigt: 'SFTP-Zugang ist eingerichtet'
      },
      {
        titel: 'Mit dem SFTP-Programm verbinden',
        kurz: 'Beispiel FileZilla — in anderen Programmen heißen die Felder genauso.',
        wo: 'FileZilla › Datei › Servermanager › „Neuer Server“',
        schritte: [
          'Protokoll auf <b>SFTP – SSH File Transfer Protocol</b> stellen ①.',
          'Server ' + c(h.sftp.host.replace('{domain}', A.domain)) + ' und Port ' + c(String(h.sftp.port)) + ' eintragen ②.',
          'Verbindungsart „Normal“, Benutzer ' + c(sftpBenutzer) + ' ③ und das SFTP-Passwort eingeben.',
          '„Verbinden“ ④. Beim ersten Mal fragt das Programm nach dem <b>Server-Schlüssel</b>: bestätigen und „Immer vertrauen“ wählen.',
          'Versteckte Dateien sichtbar machen: <b>Server › Anzeigen versteckter Dateien erzwingen</b> — sonst fehlen die wichtigen .htaccess-Dateien.'
        ],
        bild: 'filezilla',
        bildText: 'Servermanager eines SFTP-Programms mit Ihren Werten.',
        erledigt: 'Verbindung steht'
      },
      {
        titel: 'Die drei Ordner hochladen',
        kurz: 'Webseite, Portal und privater Ordner ins Hauptverzeichnis.',
        wo: 'SFTP-Programm › links: Ihr Rechner · rechts: Server',
        schritte: [
          'Links den entpackten Ordner ' + c(A.wurzel) + ' öffnen ①. Darin liegen ' + c(o.webseite + '/') + ', ' + c(o.portal + '/') + ' und ' + c(o.privat + '/') + '.',
          'Rechts ins <b>Hauptverzeichnis</b> ' + c('/') + ' wechseln ② (bei manchen Anbietern heißt es ' + c('/html') + ' oder ' + c('/htdocs') + ' — dann dort).',
          'Die <b>drei Ordner</b> markieren und nach rechts ziehen. ANLEITUNG.html, LIESMICH.txt und die Startskripte bleiben bei Ihnen.',
          'Nach dem Übertragen prüfen: In ' + c('/' + o.portal) + ' und ' + c('/' + o.privat) + ' liegt jeweils eine ' + c('.htaccess') + '. Die Übertragungsliste sollte keine fehlgeschlagenen Dateien zeigen.',
          'Rechte (Rechtsklick › Dateiberechtigungen): Ordner ' + c('755') + ', Dateien ' + c('644') + ' — meist ist das schon so. Der Webserver muss in ' + c('/' + o.privat) + ' und ' + c('/' + o.webseite) + ' schreiben dürfen.'
        ],
        werte: [['Ziel auf dem Server', '/'], ['Ordner 1', '/' + o.webseite], ['Ordner 2', '/' + o.portal], ['Ordner 3', '/' + o.privat]],
        bild: 'hochladen',
        bildText: 'Links Ihr Rechner, rechts der Webspace: drei Ordner ins Hauptverzeichnis.',
        warnung: 'Den Ordner /' + o.privat + ' nicht in /' + o.webseite + ' oder /' + o.portal + ' hineinlegen — er muss daneben liegen, sonst wären Konten und Inhalte aus dem Netz abrufbar.',
        erledigt: 'Alle drei Ordner sind oben'
      },
      {
        titel: 'Domain auf den Ordner /' + o.webseite + ' zeigen lassen',
        kurz: 'Damit ' + A.host + ' die Webseite ausliefert.',
        wo: p.ziel,
        schritte: [
          'In der Domainverwaltung bei ' + c(A.domain) + ' die Einstellungen öffnen ①.',
          'Als Ziel <b>„Verzeichnis im Webspace“</b> wählen ② und ' + c('/' + o.webseite) + ' eintragen ③. Übernehmen ④.',
          'Dasselbe für ' + c('www.' + A.domain) + ' — beide Adressen zeigen auf denselben Ordner.',
          'Kurz ' + c('http://' + A.host) + ' aufrufen: Es erscheint „Hier entsteht eine neue Webseite“. Das ist die Platzhalterseite aus dem Paket.'
        ],
        werte: [['Domain', A.domain + ' und www.' + A.domain], ['Ziel (Verzeichnis)', '/' + o.webseite]],
        bild: 'ziel',
        tipp: 'Bevorzugt ist die Adresse ' + A.host + '. Die jeweils andere Schreibweise leitet der Browser nach dem SSL-Schritt auf dieselbe Seite.',
        erledigt: 'Domain zeigt auf /' + o.webseite
      },
      {
        titel: 'Subdomain für das Portal anlegen',
        kurz: A.portalHost + ' → /' + o.portal,
        wo: p.subdomain,
        schritte: [
          'Neue Subdomain ' + c(d.sub || 'portal') + ' zur Domain ' + c(A.domain) + ' anlegen ①.',
          'Als Ziel „Verzeichnis im Webspace“ ② mit ' + c('/' + o.portal) + ' ③ — übernehmen ④.',
          'Das Portal noch <b>nicht</b> aufrufen: Erst kommt das SSL-Zertifikat, sonst ginge das erste Passwort unverschlüsselt über die Leitung.'
        ],
        werte: [['Subdomain', A.portalHost], ['Ziel (Verzeichnis)', '/' + o.portal]],
        bild: ['ziel', 'portal'],
        erledigt: 'Subdomain ist angelegt'
      },
      {
        titel: 'SSL-Zertifikate aktivieren',
        kurz: 'https:// für Webseite und Portal.',
        wo: p.ssl,
        schritte: [
          'Für ' + c(A.domain) + ' ① ein Zertifikat zuweisen bzw. aktivieren.',
          'Für ' + c('www.' + A.domain) + ' ② ebenso.',
          'Und für ' + c(A.portalHost) + ' ③ — das ist das wichtigste, denn dort melden Sie sich an.',
          'Warten, bis überall „aktiv“ steht. Dann ' + c(A.webseite) + ' aufrufen: Das Schloss im Browser muss geschlossen sein.'
        ],
        werte: [['Zertifikate für', A.domain + ', www.' + A.domain + ', ' + A.portalHost]],
        bild: 'ssl',
        bildText: 'Drei Zertifikate — eins je Adresse.',
        tipp: h.hinweise.ssl + ' Die .htaccess-Dateien im Paket leiten alle Aufrufe automatisch auf https:// um.',
        warnung: 'Erscheint „Zu viele Weiterleitungen“ oder eine Zertifikatswarnung, ist das Zertifikat noch nicht fertig. Ein paar Minuten warten und neu laden.',
        erledigt: 'Alle drei Adressen laufen mit https://'
      },
      {
        titel: 'PHP-Version einstellen',
        kurz: 'Das Portal braucht PHP 8.1 oder neuer.',
        wo: p.php,
        schritte: [
          'Die neueste angebotene Version wählen ① — empfohlen ' + c('PHP ' + (d.php || '8.3')) + ' oder höher.',
          'Speichern ②. Die Umstellung wirkt meist sofort.'
        ],
        werte: [['PHP-Version', (d.php || '8.3') + ' oder neuer (mindestens 8.1)']],
        bild: 'php',
        tipp: h.hinweise.php,
        erledigt: 'PHP ist aktuell'
      },
      {
        titel: 'E-Mail-Postfächer anlegen',
        kurz: mails.join(', ') || 'Postfächer für Ihre Domain',
        wo: p.mail,
        schritte: (d.postfaecher || []).map(function (m) {
          return 'Postfach ' + c(A.postfach(m.name)) + (m.zweck ? ' — ' + e(m.zweck) : '') + ' anlegen, mit eigenem starken Passwort.';
        }).concat([
          'Die Passwörter nur im Passwortmanager speichern. Das Passwort von ' + c(A.absender || A.empfaenger) + ' brauchen Sie gleich im Portal für den Versand des Kontaktformulars.',
          'Optional: Weiterleitungen einrichten (z. B. alles von ' + c(A.absender || 'webseite@' + A.domain) + ' an ' + c(A.empfaenger) + '), damit Rückläufer auffallen.'
        ]),
        werte: mails.map(function (m, i) { return ['Postfach ' + (i + 1), m]; }),
        bild: 'postfach',
        bildText: 'Postfach anlegen: Adresse ①, Passwort ②, anlegen ③.',
        erledigt: 'Postfächer sind angelegt'
      },
      {
        titel: 'Server-Daten für E-Mail-Programme',
        kurz: 'Für Outlook, Apple Mail, Thunderbird, Handy — und für das Portal.',
        wo: p.mail + ' › Programm einrichten',
        schritte: [
          'In Ihrem E-Mail-Programm ein neues Konto hinzufügen und „manuell einrichten“ wählen.',
          'Posteingang per <b>IMAP</b> (Port ' + h.imap.port + ', SSL/TLS), Postausgang per <b>SMTP</b> (Port ' + h.smtp.port + ', SSL/TLS) ①.',
          'Benutzername ist immer die <b>vollständige E-Mail-Adresse</b>, das Passwort das des Postfachs.',
          'Test: eine Mail von einer privaten Adresse an ' + c(A.empfaenger) + ' schicken und antworten.' + (h.webmail ? ' Alternativ im Webmail: ' + c(h.webmail) + '.' : '')
        ],
        werte: [['Posteingang (IMAP)', h.imap.host + ' · Port ' + h.imap.port + ' · SSL/TLS'], ['Postausgang (SMTP)', h.smtp.host + ' · Port ' + h.smtp.port + ' · SSL/TLS'],
          ['SMTP-Ausweichport', h.smtp.port2 + ' · STARTTLS'], ['POP3 (falls gewünscht)', h.pop.host + ' · Port ' + h.pop.port + ' · SSL/TLS'], ['Benutzername', 'vollständige E-Mail-Adresse']],
        bild: 'mailServer',
        erledigt: 'E-Mail funktioniert'
      },
      {
        titel: 'Zustellbarkeit prüfen: SPF, DKIM, DMARC',
        kurz: 'Damit Ihre Mails nicht im Spam landen.',
        wo: p.dns,
        schritte: [
          'Die Einträge <b>MX</b> und <b>SPF</b> setzt der Anbieter meist selbst, sobald die Domain im Paket liegt — nur ansehen, nichts löschen.',
          'Bietet der Anbieter <b>DKIM</b> an, dort einschalten.',
          'Einen <b>DMARC</b>-Eintrag ergänzen ①: Typ ' + c('TXT') + ', Name ' + c('_dmarc') + ', Wert wie unten. „p=none“ beobachtet nur und blockiert nichts.',
          'Nach einigen Stunden prüfen, z. B. mit einem kostenlosen Dienst wie „mail-tester.com“: eine Testmail aus dem Portal (Schritt „E-Mail-Versand“) an die dort angezeigte Adresse schicken.'
        ],
        werte: [['Typ', 'TXT'], ['Name', '_dmarc'], ['Wert', 'v=DMARC1; p=none; rua=mailto:' + (A.empfaenger || 'postmaster@' + A.domain)]],
        bild: 'dns',
        tipp: 'Absender des Kontaktformulars ist ' + (A.absender || 'webseite@' + A.domain) + ' — eine Adresse Ihrer eigenen Domain. Das ist der wichtigste Schutz gegen den Spam-Ordner.',
        erledigt: 'DNS-Einträge sind geprüft'
      },
      {
        titel: 'Portal einrichten (erster Aufruf)',
        kurz: A.portalUrl + ' öffnen und das erste Konto anlegen.',
        wo: A.portalUrl,
        schritte: [
          c(A.portalUrl) + ' im Browser öffnen. Es erscheint „Einrichtung“.',
          'Name und Benutzername ' + c(d.adminBenutzer || 'vorname.nachname') + ' eintragen ①.',
          'Ein <b>starkes Passwort</b> vergeben ② (mindestens 10 Zeichen, besser 16+, aus dem Passwortmanager).',
          'Die beiden Ordner hat das Portal meist schon selbst gefunden ③ — nur prüfen. „Einrichten“ ④.',
          'Mit dem neuen Konto anmelden. Die Einrichtung ist danach für immer gesperrt.'
        ],
        werte: [['Adresse', A.portalUrl], ['Benutzername', d.adminBenutzer || ''], ['Rolle', 'Verwaltung (alle Rechte)']],
        bild: 'portalEinrichtung',
        bildText: 'Die Einrichtung des Portals beim ersten Aufruf.',
        warnung: 'Diesen Schritt direkt nach dem Hochladen erledigen: Solange niemand eingerichtet hat, könnte theoretisch jemand anderes das erste Konto anlegen.',
        erledigt: 'Ich bin im Portal angemeldet'
      },
      {
        titel: 'E-Mail-Versand im Portal hinterlegen',
        kurz: 'Damit das Kontaktformular zuverlässig ankommt.',
        wo: 'Portal › Einstellungen › E-Mail-Versand',
        schritte: [
          'Postausgangsserver ' + c(d.smtpHost || h.smtp.host) + ' ① und Port ' + c(String(d.smtpPort || h.smtp.port)) + ' ② eintragen (sind schon vorbelegt).',
          'Benutzername ' + c(A.absender || A.empfaenger) + ' ③ und das Passwort dieses Postfachs ④.',
          '„Zugangsdaten speichern“ ⑤, dann „Testmail senden“ ⑥. Die Testmail geht an ' + c(A.empfaenger) + ' — auch im Spam-Ordner nachsehen.'
        ],
        werte: [['SMTP-Server', d.smtpHost || h.smtp.host], ['Port', (d.smtpPort || h.smtp.port) + ' (SSL/TLS)'], ['Benutzername', A.absender || A.empfaenger], ['Empfänger der Anfragen', A.empfaenger]],
        bild: 'portalSmtp',
        tipp: 'Das Passwort liegt danach nur im privaten Ordner auf dem Server und wird im Portal nie wieder angezeigt.',
        erledigt: 'Testmail ist angekommen'
      },
      {
        titel: 'Systemprüfung',
        kurz: 'Das Portal prüft sich selbst.',
        wo: 'Portal › Systemprüfung',
        schritte: [
          'Die Systemprüfung öffnen. Jeder Punkt erklärt, was er prüft und was im Fehlerfall zu tun ist.',
          'Rote Punkte zuerst beheben — typisch sind fehlende Schreibrechte (per SFTP auf 755 setzen) oder eine vergessene ' + c('.htaccess') + '.',
          'Gelbe Hinweise sind Empfehlungen, z. B. ein zweites Verwaltungskonto oder die Upload-Grenze für Videos.'
        ],
        bild: 'portalDiagnose',
        tipp: h.hinweise.upload,
        erledigt: 'Keine roten Punkte mehr'
      },
      {
        titel: 'Inhalte prüfen und zum ersten Mal veröffentlichen',
        kurz: 'Die Webseite entsteht.',
        wo: 'Portal › Übersicht, Sektionen, Vorschau, Veröffentlichen',
        schritte: [
          'In der Übersicht die <b>Prüfliste</b> abarbeiten: vor allem Impressum-Angaben unter „' + e(d.organisation) + ' & Kontakt“ und die Speicherdauer der Server-Logdateien in der Datenschutzerklärung.',
          'Texte und Bilder in den Bereichen anpassen, Sektionen ein-/ausblenden und sortieren. Alles landet zuerst im Entwurf.',
          'Unter „Vorschau“ die Seite auf Computer, Tablet und Handy ansehen.',
          'Unter „Veröffentlichen“ beim ersten Mal „<b>Webseite neu erzeugen</b>“ ① wählen, danach reicht „Jetzt veröffentlichen“ ②.',
          c(A.webseite) + ' aufrufen — die Platzhalterseite ist durch Ihre Webseite ersetzt.'
        ],
        bild: 'portalVeroeffentlichen',
        warnung: 'Die Rechtstexte sind eine allgemeine Vorlage und keine Rechtsberatung. Vor dem Start prüfen (lassen) — besonders bei reglementierten Berufen.',
        erledigt: 'Die Webseite ist online'
      },
      {
        titel: 'Suchmaschinen freigeben',
        kurz: 'Erst jetzt darf Google die Seite aufnehmen.',
        wo: 'Portal › Webseite allgemein › Suchmaschinen & Teilen',
        schritte: [
          'Den Schalter „Suchmaschinen dürfen die Seite aufnehmen“ einschalten, speichern und veröffentlichen. Bis dahin stand „noindex“ in jeder Seite — gut während des Aufbaus.',
          'Optional: In der <b>Google Search Console</b> die Property ' + c(A.webseite) + ' anlegen und bestätigen.',
          'Dort unter „Sitemaps“ ' + c('sitemap.xml') + ' einreichen ① und senden ②.',
          'Optional: Einen Eintrag im <b>Google Unternehmensprofil</b> anlegen und auf die Webseite verlinken.'
        ],
        werte: [['Sitemap', A.webseite + '/sitemap.xml'], ['robots.txt', A.webseite + '/robots.txt']],
        bild: 'searchConsole',
        erledigt: 'Suchmaschinen sind freigegeben'
      },
      {
        titel: 'Sicherheit und Pflege',
        kurz: 'Damit es so bleibt.',
        wo: 'Portal › Benutzer · SFTP-Programm',
        schritte: [
          'Unter „Benutzer“ ein <b>zweites Konto mit der Rolle Verwaltung</b> anlegen — als Rückfallebene. Wer nur Inhalte pflegt, bekommt die Rolle „Redaktion“.',
          'Passwort vergessen und kein zweites Konto? Per SSH im Ordner ' + c('/' + o.portal) + ' ausführen: ' + c('php werkzeuge/benutzer.php ' + (d.adminBenutzer || 'vorname.nachname')) + '.',
          'Sicherung: Vor jedem Veröffentlichen sichert das Portal die Inhalte (die letzten 30 Stände, unter „Veröffentlichen“). Zusätzlich einmal im Monat ' + c('/' + o.privat) + ' und ' + c('/' + o.webseite + '/medien') + ' per SFTP auf den eigenen Rechner kopieren.',
          'Design wechseln: Unter „Design“ lässt sich jederzeit ein <b>Claude Design System</b> als ZIP hochladen — Farben, Schriften und Rundungen werden übernommen, die Inhalte bleiben.',
          'Einmal im Jahr: PHP-Version im Kundenbereich auf die neueste stellen und die Systemprüfung ansehen.'
        ],
        tipp: 'Das Protokoll im Portal zeigt, wer wann was geändert oder veröffentlicht hat.',
        erledigt: 'Zweites Konto und Sicherung sind eingerichtet'
      },
      {
        titel: 'Geschafft — Ihre Übersicht',
        kurz: 'Alle Adressen und Zugangsdaten auf einen Blick (ohne Passwörter).',
        schritte: [
          'Die Webseite läuft unter ' + c(A.webseite) + ', das Portal unter ' + c(A.portalUrl) + '.',
          'Diese Übersicht ausdrucken oder im Passwortmanager als Notiz ablegen.'
        ],
        werte: [
          ['Webseite', A.webseite], ['Portal', A.portalUrl], ['Portal-Benutzer', d.adminBenutzer || ''],
          ['Hoster', h.name + (h.login ? ' — ' + h.login : '')], ['SFTP', h.sftp.host.replace('{domain}', A.domain) + ':' + h.sftp.port + ' · ' + sftpBenutzer],
          ['Postfächer', mails.join(', ')], ['IMAP', h.imap.host + ':' + h.imap.port], ['SMTP', h.smtp.host + ':' + h.smtp.port],
          ['Ordner', '/' + o.webseite + ' · /' + o.portal + ' · /' + o.privat]
        ],
        erledigt: 'Alles erledigt'
      }
    ];
  }

  /** HTML eines Schritts (für Assistent und ANLEITUNG.html). */
  function schrittHtml(s, nr, gesamt, d) {
    var A = window.WSErzeugen.abgeleitet(d);
    var bild = '';
    if (s.bild && window.WSBilder) {
      var name = Array.isArray(s.bild) ? s.bild[0] : s.bild;
      var zusatz = Array.isArray(s.bild) ? s.bild[1] : undefined;
      bild = '<figure class="bild">' + window.WSBilder[name](d, A, zusatz)
        + '<figcaption>' + e(s.bildText || 'Beispielhafte Darstellung') + ' Nachgezeichnet — Menüs heißen beim Anbieter evtl. etwas anders.</figcaption></figure>';
    }
    var werte = s.werte && s.werte.length ? '<div class="werte">' + s.werte.map(function (w) {
      return '<div class="wert"><span class="wert-label">' + e(w[0]) + '</span><span class="wert-wert">' + e(w[1]) + '</span>'
        + (/^\(/.test(w[1]) ? '<span></span>' : '<button type="button" class="icon-btn" data-kopieren="' + e(w[1]) + '" title="Kopieren" aria-label="' + e(w[0]) + ' kopieren">⧉</button>') + '</div>';
    }).join('') + '</div>' : '';
    return '<div class="a-kopf"><span class="a-nr">' + nr + '</span><div><p class="eyebrow">Schritt ' + nr + ' von ' + gesamt + '</p>'
      + '<h1 class="title">' + e(s.titel) + '</h1><p class="lead" style="margin:0">' + e(s.kurz || '') + '</p></div></div>'
      + (s.wo ? '<p class="a-wo"><span>Wo:</span> <b>' + e(s.wo) + '</b></p>' : '')
      + '<ol class="a-schritte">' + s.schritte.map(function (x) { return '<li>' + x + '</li>'; }).join('') + '</ol>'
      + werte + bild
      + (s.tipp ? '<p class="hinweis"><i>i</i><span>' + e(s.tipp) + '</span></p>' : '')
      + (s.warnung ? '<p class="hinweis hinweis--warn"><i>!</i><span>' + e(s.warnung) + '</span></p>' : '');
  }

  /** Die ganze Anleitung als eigenständige HTML-Datei. */
  function anleitungHtml(d) {
    var A = window.WSErzeugen.abgeleitet(d);
    var liste = schritte(d);
    // Das Stylesheet des Assistenten liegt gebündelt in vorlage.js — so klappt es auch bei file://
    var css = (window.VORLAGE && window.VORLAGE.assistentCss) || '';
    return '<!DOCTYPE html>\n<html lang="de">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">\n'
      + '<title>Anleitung — ' + e(d.name) + ' online bringen</title>\n<style>\n' + css
      + '\n.a-schritt{background:var(--card);border-radius:var(--radius);padding:20px;margin:0 0 20px;break-inside:avoid-page}'
      + '\n@media print{body{background:#fff;color:#111}.a-schritt{background:#fff;border:1px solid #ddd}.icon-btn{display:none}.werte,.a-wo,.hinweis{background:#f5f5f5;color:#111}.wert-wert,code{color:#0f766e}}\n</style>\n</head>\n<body>\n'
      + '<main class="view view--weit" style="padding-bottom:40px">'
      + '<p class="eyebrow">Anleitung · ' + e(A.hoster.name) + '</p><h1 class="title">' + e(d.name) + ' online bringen</h1>'
      + '<p class="lead">Webseite: <span class="mono">' + e(A.webseite) + '</span> · Portal: <span class="mono">' + e(A.portalUrl) + '</span><br>Erstellt am ' + e(new Date().toLocaleDateString('de-DE')) + ' mit dem Portal-Assistenten. Menüpfade sind typische Wege — beim Anbieter können Bezeichnungen abweichen.</p>'
      + liste.map(function (s, i) { return '<section class="a-schritt">' + schrittHtml(s, i + 1, liste.length, d) + '</section>'; }).join('\n')
      + '</main>\n<script>document.addEventListener("click",function(e){var b=e.target.closest("[data-kopieren]");if(b&&navigator.clipboard){navigator.clipboard.writeText(b.getAttribute("data-kopieren"));b.textContent="✓";setTimeout(function(){b.textContent="⧉"},1200)}});</script>\n'
      + '</body>\n</html>\n';
  }

  window.WSAnleitung = { schritte: schritte, schrittHtml: schrittHtml };
  window.anleitungHtml = anleitungHtml;
})();
