/* ===========================================================================
   Voreinstellungen: Hosting-Anbieter, Branchen, Sektionstypen, Farben.
   Reine Daten — die Logik steht in app.js, erzeugen.js und anleitung.js.
   =========================================================================== */

(function () {
  'use strict';

  /**
   * Hosting-Anbieter. Menüpfade sind typische Wege durch den Kundenbereich;
   * Anbieter benennen Menüs gelegentlich um — die Anleitung sagt das dazu.
   * {domain} wird durch die eigene Domain ersetzt.
   */
  var HOSTER = {
    strato: {
      name: 'STRATO',
      firma: 'STRATO AG\nOtto-Ostrowski-Straße 7\n10249 Berlin',
      login: 'https://www.strato.de/apps/CustomerService',
      loginText: 'strato.de → „Login“ → Kundennummer bzw. E-Mail und Passwort',
      bereich: 'STRATO Kundenbereich',
      sftp: { host: 'ssh.strato.de', port: 22, benutzer: '{domain}', benutzerText: 'Ihre Domain (z. B. {domain}) oder ein zusätzlich angelegter SFTP-Benutzer' },
      smtp: { host: 'smtp.strato.de', port: 465, port2: 587 },
      imap: { host: 'imap.strato.de', port: 993 },
      pop: { host: 'pop3.strato.de', port: 995 },
      webmail: 'https://webmail.strato.de',
      pfade: {
        paket: 'Kundenbereich › Paketübersicht',
        domain: 'Domains › Domainverwaltung',
        subdomain: 'Domains › Domainverwaltung › „Subdomain anlegen“',
        ziel: 'Domains › Domainverwaltung › Zahnrad bei der Domain › „Umleitung / Ziel ändern“ › Verzeichnis',
        ssl: 'Sicherheit › SSL-Verwaltung (bzw. Zahnrad bei der Domain › SSL)',
        php: 'Hosting › Einstellungen › PHP-Version',
        sftp: 'Hosting › SFTP/SSH (bzw. „Zugänge“)',
        mail: 'E-Mail › Verwaltung › „Postfach anlegen“',
        dns: 'Domains › Domainverwaltung › Zahnrad › DNS',
        dateien: 'Hosting › Webspace / Dateimanager'
      },
      hinweise: {
        sftp: 'SFTP ist bei STRATO je nach Paket enthalten (bei den aktuellen Hosting-Paketen üblich). Fehlt der Menüpunkt, funktioniert auch FTP mit TLS: Server ftp.strato.de, Port 21, „Explizites FTP über TLS“.',
        ssl: 'Bei STRATO ist ein SSL-Zertifikat im Paket enthalten. Es muss aber für jede (Sub-)Domain einzeln zugewiesen werden — auch für die www-Variante und die Portal-Subdomain.',
        php: 'STRATO stellt die PHP-Version für das ganze Paket ein. Gewählt werden sollte die neueste angebotene Version (mindestens 8.1).',
        upload: 'Die Upload-Grenze für Dateien lässt sich bei STRATO nicht in jeder Paketvariante ändern — die Systemprüfung im Portal zeigt, was gilt.'
      }
    },
    ionos: {
      name: 'IONOS',
      firma: 'IONOS SE\nElgendorfer Str. 57\n56410 Montabaur',
      login: 'https://login.ionos.de',
      loginText: 'ionos.de → „Anmelden“ → Kundennummer bzw. E-Mail und Passwort',
      bereich: 'IONOS Cloud Panel',
      sftp: { host: 'access-XXXXXXXX.webspace-host.com', port: 22, benutzer: 'laut Panel (z. B. u12345678)', benutzerText: 'Den Benutzer (z. B. u12345678) zeigt das Panel unter „SFTP & SSH“' },
      smtp: { host: 'smtp.ionos.de', port: 465, port2: 587 },
      imap: { host: 'imap.ionos.de', port: 993 },
      pop: { host: 'pop.ionos.de', port: 995 },
      webmail: 'https://mail.ionos.de',
      pfade: {
        paket: 'Menü › Verträge & Abrechnung',
        domain: 'Menü › Domains & SSL',
        subdomain: 'Domains & SSL › Zahnrad bei der Domain › „Subdomain erstellen“',
        ziel: 'Domains & SSL › Zahnrad › „Verwendungsart anpassen“ › Webspace › Verzeichnis',
        ssl: 'Domains & SSL › Schloss-Symbol bzw. „SSL-Zertifikat“',
        php: 'Hosting › PHP-Einstellungen',
        sftp: 'Hosting › SFTP & SSH',
        mail: 'Menü › E-Mail › „E-Mail-Adresse anlegen“',
        dns: 'Domains & SSL › Zahnrad › DNS',
        dateien: 'Hosting › Webspace-Explorer'
      },
      hinweise: {
        sftp: 'Server-Adresse und Benutzer stehen im Panel unter „SFTP & SSH“ — das Passwort wird dort vergeben.',
        ssl: 'IONOS liefert SSL-Zertifikate mit; jede (Sub-)Domain muss eines zugewiesen bekommen.',
        php: 'Die PHP-Version lässt sich bei IONOS je Verzeichnis bzw. Paket einstellen.',
        upload: 'Die Upload-Grenze zeigt die Systemprüfung im Portal.'
      }
    },
    allinkl: {
      name: 'ALL-INKL.COM',
      firma: 'ALL-INKL.COM – Neue Medien Münnich\nHauptstraße 68\n02742 Friedersdorf',
      login: 'https://kas.all-inkl.com',
      loginText: 'KAS (Kundenadministrationssystem) → Login mit KAS-Login und Passwort',
      bereich: 'KAS',
      sftp: { host: 'w0XXXXXX.kasserver.com', port: 22, benutzer: 'laut KAS (z. B. w0123456)', benutzerText: 'KAS-Login bzw. ein angelegter FTP-/SSH-Benutzer' },
      smtp: { host: 'w0XXXXXX.kasserver.com', port: 465, port2: 587 },
      imap: { host: 'w0XXXXXX.kasserver.com', port: 993 },
      pop: { host: 'w0XXXXXX.kasserver.com', port: 995 },
      webmail: 'https://webmail.all-inkl.com',
      pfade: {
        paket: 'KAS › Account › Übersicht',
        domain: 'KAS › Domain',
        subdomain: 'KAS › Subdomain › „Neue Subdomain anlegen“',
        ziel: 'KAS › Domain bzw. Subdomain › bearbeiten › Zielverzeichnis',
        ssl: 'KAS › Domain › bearbeiten › SSL-Schutz (Let’s Encrypt)',
        php: 'KAS › Domain › bearbeiten › PHP-Version',
        sftp: 'KAS › FTP › FTP-Benutzer (SSH je nach Tarif unter „Tools“)',
        mail: 'KAS › E-Mail › E-Mail-Postfach › „Neues Postfach anlegen“',
        dns: 'KAS › Tools › DNS-Einstellungen',
        dateien: 'KAS › FTP › WebFTP'
      },
      hinweise: {
        sftp: 'Den genauen Servernamen (w0…kasserver.com) zeigt das KAS. SFTP/SSH ist tarifabhängig — sonst FTP mit TLS verwenden.',
        ssl: 'Im KAS pro Domain „Let’s Encrypt“ aktivieren.',
        php: 'Die PHP-Version wird im KAS je Domain eingestellt.',
        upload: 'Die Upload-Grenze zeigt die Systemprüfung im Portal.'
      }
    },
    hetzner: {
      name: 'Hetzner',
      firma: 'Hetzner Online GmbH\nIndustriestr. 25\n91710 Gunzenhausen',
      login: 'https://konsoleh.hetzner.com',
      loginText: 'konsoleH → Login',
      bereich: 'konsoleH',
      sftp: { host: 'wwwXXX.your-server.de', port: 22, benutzer: 'laut konsoleH', benutzerText: 'FTP-/SSH-Login aus konsoleH' },
      smtp: { host: 'mail.your-server.de', port: 465, port2: 587 },
      imap: { host: 'mail.your-server.de', port: 993 },
      pop: { host: 'mail.your-server.de', port: 995 },
      webmail: 'https://webmail.your-server.de',
      pfade: {
        paket: 'konsoleH › Übersicht',
        domain: 'konsoleH › Einstellungen › Domains',
        subdomain: 'konsoleH › Einstellungen › Subdomains',
        ziel: 'konsoleH › Subdomains bzw. Domain › Dokumentenverzeichnis',
        ssl: 'konsoleH › Einstellungen › SSL-Manager',
        php: 'konsoleH › Einstellungen › PHP-Konfiguration',
        sftp: 'konsoleH › Einstellungen › Zugangsdaten / SSH',
        mail: 'konsoleH › E-Mail › Postfächer',
        dns: 'konsoleH › Einstellungen › DNS',
        dateien: 'konsoleH › Dateimanager'
      },
      hinweise: {
        sftp: 'Serveradresse und Login zeigt konsoleH unter „Zugangsdaten“.',
        ssl: 'Im SSL-Manager ein Let’s-Encrypt-Zertifikat für jede (Sub-)Domain bestellen.',
        php: 'Die PHP-Version wird in konsoleH eingestellt.',
        upload: 'Die Upload-Grenze zeigt die Systemprüfung im Portal.'
      }
    },
    andere: {
      name: 'Anderer Anbieter',
      firma: '',
      login: '',
      loginText: 'Kundenbereich Ihres Anbieters',
      bereich: 'Kundenbereich',
      sftp: { host: 'laut Anbieter', port: 22, benutzer: 'laut Anbieter', benutzerText: 'Zugangsdaten stehen im Kundenbereich unter SFTP/SSH oder FTP' },
      smtp: { host: 'smtp.ihr-anbieter.de', port: 465, port2: 587 },
      imap: { host: 'imap.ihr-anbieter.de', port: 993 },
      pop: { host: 'pop.ihr-anbieter.de', port: 995 },
      webmail: '',
      pfade: {
        paket: 'Kundenbereich › Vertrag / Paket',
        domain: 'Kundenbereich › Domains',
        subdomain: 'Kundenbereich › Domains › Subdomain anlegen',
        ziel: 'Kundenbereich › Domains › Ziel bzw. Dokumentenverzeichnis',
        ssl: 'Kundenbereich › SSL / Sicherheit',
        php: 'Kundenbereich › Hosting › PHP-Version',
        sftp: 'Kundenbereich › Hosting › SFTP/SSH oder FTP',
        mail: 'Kundenbereich › E-Mail › Postfach anlegen',
        dns: 'Kundenbereich › Domains › DNS',
        dateien: 'Kundenbereich › Dateimanager'
      },
      hinweise: {
        sftp: 'Wenn kein SFTP angeboten wird: FTP mit TLS (FTPS, Port 21) verwenden — nie unverschlüsseltes FTP.',
        ssl: 'Für jede (Sub-)Domain ein Zertifikat aktivieren (z. B. Let’s Encrypt).',
        php: 'Mindestens PHP 8.1, besser die neueste angebotene Version.',
        upload: 'Die Upload-Grenze zeigt die Systemprüfung im Portal.'
      }
    }
  };

  var SEKTIONEN = {
    hero:        { name: 'Kopfbereich', zeichen: '▲', text: 'Große Überschrift, Einleitung, zwei Knöpfe', einmalig: true },
    laufband:    { name: 'Laufband', zeichen: '⇄', text: 'Durchlaufende Stichworte', einmalig: true },
    'ueber-uns': { name: 'Über uns', zeichen: '♥', text: 'Leitsatz, Text und Werte-Kacheln', einmalig: true },
    angebote:    { name: '{angebote}', zeichen: '✚', text: 'Karten aus dem Bereich „{angebote}“', einmalig: true, modul: 'angebote' },
    team:        { name: '{team}', zeichen: '☺', text: 'Personen mit Porträt', einmalig: true, modul: 'team' },
    zahlen:      { name: 'Zahlen', zeichen: '#', text: 'Kennzahlen, die hochzählen', einmalig: false },
    preise:      { name: 'Preise', zeichen: '€', text: 'Preiskarten und Preisliste', einmalig: true, modul: 'preise' },
    faq:         { name: 'Häufige Fragen', zeichen: '?', text: 'Fragen zum Aufklappen', einmalig: false },
    kontakt:     { name: 'Kontakt', zeichen: '✉', text: 'Kontaktdaten, Zeiten, Formular', einmalig: true },
    anfahrt:     { name: 'Anfahrt', zeichen: '⚑', text: 'Karte (lädt erst auf Klick)', einmalig: true },
    'text-bild': { name: 'Text mit Bild', zeichen: '▤', text: 'Baustein', einmalig: false },
    karten:      { name: 'Karten', zeichen: '▦', text: 'Baustein: 2–4 Kacheln', einmalig: false },
    schritte:    { name: 'Ablauf in Schritten', zeichen: '①', text: 'Baustein: nummerierte Schritte', einmalig: false },
    stimmen:     { name: 'Kundenstimmen', zeichen: '❝', text: 'Baustein: Zitate', einmalig: false },
    galerie:     { name: 'Bildergalerie', zeichen: '▣', text: 'Baustein: Fotos', einmalig: false },
    video:       { name: 'Video', zeichen: '▶', text: 'Baustein: Video vom eigenen Server', einmalig: false },
    aufruf:      { name: 'Aufruf-Band', zeichen: '➜', text: 'Baustein: kurzer Aufruf mit Knopf', einmalig: false },
    freitext:    { name: 'Freier Text', zeichen: '¶', text: 'Baustein: Überschrift und Absätze', einmalig: false }
  };

  /** Branchen: Begriffe, Module, Startseite und Beispieltexte. */
  var BRANCHEN = {
    praxis: {
      name: 'Praxis & Gesundheit', zeichen: '✚', text: 'Arzt-, Therapie-, Podologie- oder Heilpraxis',
      organisation: 'Praxis', reglementiert: true, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Leistungen', angebot: 'Leistung', team: 'Team', person: 'Person', zeiten: 'Sprechzeiten' },
      module: { angebote: true, team: true, preise: true, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'ueber-uns', 'angebote', 'schritte', 'team', 'preise', 'faq', 'kontakt', 'anfahrt'],
      hero: ['Gut versorgt,', 'mit Zeit für Sie.'], knopf: 'Termin anfragen',
      angebote: [['Erstberatung', 'Wir nehmen uns Zeit für Ihr Anliegen'], ['Behandlung', 'Gründlich und schonend'], ['Nachsorge', 'Damit es gut bleibt']],
      auswahlLabel: 'Um welche Leistung geht es?', zeitenBeispiel: [['Montag – Freitag', '08:00 – 13:00 Uhr'], ['Montag, Dienstag, Donnerstag', '14:00 – 18:00 Uhr']]
    },
    handwerk: {
      name: 'Handwerk', zeichen: '⚒', text: 'Tischlerei, Elektro, Sanitär, Maler …',
      organisation: 'Betrieb', reglementiert: false, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Leistungen', angebot: 'Leistung', team: 'Team', person: 'Person', zeiten: 'Öffnungszeiten' },
      module: { angebote: true, team: true, preise: false, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'laufband', 'angebote', 'schritte', 'galerie', 'stimmen', 'team', 'kontakt', 'anfahrt'],
      hero: ['Handwerk,', 'auf das Verlass ist.'], knopf: 'Angebot anfragen',
      angebote: [['Beratung vor Ort', 'Kostenlos und unverbindlich'], ['Ausführung', 'Sauber, pünktlich, zum Festpreis'], ['Wartung & Service', 'Auch nach Jahren für Sie da']],
      auswahlLabel: 'Worum geht es?', zeitenBeispiel: [['Montag – Freitag', '07:00 – 17:00 Uhr']]
    },
    gastro: {
      name: 'Gastronomie', zeichen: '☕', text: 'Restaurant, Café, Bar, Catering',
      organisation: 'Restaurant', reglementiert: false, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Unsere Küche', angebot: 'Gericht', team: 'Team', person: 'Person', zeiten: 'Öffnungszeiten' },
      module: { angebote: true, team: false, preise: true, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'ueber-uns', 'angebote', 'preise', 'galerie', 'stimmen', 'kontakt', 'anfahrt'],
      hero: ['Gut essen,', 'gern wiederkommen.'], knopf: 'Tisch reservieren',
      angebote: [['Mittagstisch', 'Wechselnd, frisch, regional'], ['Abendkarte', 'Saisonal und hausgemacht'], ['Feiern & Catering', 'Für Ihre Anlässe']],
      auswahlLabel: 'Worum geht es?', zeitenBeispiel: [['Dienstag – Samstag', '11:30 – 22:00 Uhr'], ['Sonntag', '11:30 – 15:00 Uhr']]
    },
    verein: {
      name: 'Verein', zeichen: '♣', text: 'Sport-, Kultur- oder Förderverein',
      organisation: 'Verein', reglementiert: false, rechtsform: 'eingetragener Verein (e. V.)',
      begriffe: { angebote: 'Angebote', angebot: 'Angebot', team: 'Vorstand', person: 'Mitglied', zeiten: 'Trainingszeiten' },
      module: { angebote: true, team: true, preise: true, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'ueber-uns', 'angebote', 'zahlen', 'team', 'preise', 'faq', 'kontakt'],
      hero: ['Gemeinsam', 'mehr bewegen.'], knopf: 'Mitmachen',
      angebote: [['Training', 'Für alle Altersgruppen'], ['Veranstaltungen', 'Feste, Turniere, Ausflüge'], ['Ehrenamt', 'Mitgestalten und mitanpacken']],
      auswahlLabel: 'Wofür interessieren Sie sich?', zeitenBeispiel: [['Dienstag', '18:00 – 20:00 Uhr'], ['Donnerstag', '18:00 – 20:00 Uhr']]
    },
    dienstleistung: {
      name: 'Dienstleistung & Beratung', zeichen: '◆', text: 'Agentur, Büro, Beratung, Coaching',
      organisation: 'Unternehmen', reglementiert: false, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Leistungen', angebot: 'Leistung', team: 'Team', person: 'Person', zeiten: 'Bürozeiten' },
      module: { angebote: true, team: true, preise: true, zeiten: false, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'laufband', 'angebote', 'schritte', 'stimmen', 'team', 'preise', 'faq', 'kontakt'],
      hero: ['Klare Lösungen', 'für Ihr Vorhaben.'], knopf: 'Projekt anfragen',
      angebote: [['Beratung', 'Zuhören, verstehen, einordnen'], ['Umsetzung', 'Von der Idee bis zum Ergebnis'], ['Begleitung', 'Auch danach ansprechbar']],
      auswahlLabel: 'Worum geht es?', zeitenBeispiel: [['Montag – Freitag', '09:00 – 17:00 Uhr']]
    },
    handel: {
      name: 'Laden & Handel', zeichen: '▣', text: 'Geschäft, Boutique, Hofladen',
      organisation: 'Geschäft', reglementiert: false, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Sortiment', angebot: 'Bereich', team: 'Team', person: 'Person', zeiten: 'Öffnungszeiten' },
      module: { angebote: true, team: true, preise: false, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'ueber-uns', 'angebote', 'galerie', 'stimmen', 'kontakt', 'anfahrt'],
      hero: ['Schönes finden,', 'gut beraten werden.'], knopf: 'Besuchen Sie uns',
      angebote: [['Neu eingetroffen', 'Jede Woche frische Ware'], ['Klassiker', 'Was sich bewährt hat'], ['Geschenke', 'Liebevoll verpackt']],
      auswahlLabel: 'Worum geht es?', zeitenBeispiel: [['Montag – Freitag', '10:00 – 18:30 Uhr'], ['Samstag', '10:00 – 16:00 Uhr']]
    },
    sonstiges: {
      name: 'Etwas anderes', zeichen: '◎', text: 'Allgemeine Vorlage für jede Art von Webseite',
      organisation: 'Betrieb', reglementiert: false, rechtsform: 'Einzelunternehmen',
      begriffe: { angebote: 'Leistungen', angebot: 'Leistung', team: 'Team', person: 'Person', zeiten: 'Öffnungszeiten' },
      module: { angebote: true, team: true, preise: true, zeiten: true, formular: true, hinweise: true, barrierefreiheit: true },
      sektionen: ['hero', 'ueber-uns', 'angebote', 'schritte', 'team', 'faq', 'kontakt'],
      hero: ['Eine klare Botschaft,', 'die hängen bleibt.'], knopf: 'Kontakt aufnehmen',
      angebote: [['Erstes Angebot', 'Eine Zeile, worum es geht'], ['Zweites Angebot', 'Eine Zeile, worum es geht'], ['Drittes Angebot', 'Eine Zeile, worum es geht']],
      auswahlLabel: 'Worum geht es?', zeitenBeispiel: [['Montag – Freitag', '09:00 – 18:00 Uhr']]
    }
  };

  var RECHTSFORMEN = ['Einzelunternehmen', 'eingetragene Kauffrau / eingetragener Kaufmann (e. K.)', 'GbR', 'Partnerschaftsgesellschaft (PartG)',
    'GmbH', 'UG (haftungsbeschränkt)', 'GmbH & Co. KG', 'OHG', 'KG', 'AG', 'eingetragener Verein (e. V.)', 'Stiftung', 'Freiberuflich'];
  var MIT_VERTRETUNG = /GmbH|UG|AG|KG|OHG|Verein|Stiftung|PartG/;
  var MIT_REGISTER = /e\. K\.|GmbH|UG|AG|KG|OHG|Verein|PartG/;

  /** Akzentfarben aus der Aurora-Kategoriepalette, dazu die passende Schrift darauf. */
  var AKZENTE = [
    { farbe: '#5eead4', tinte: '#04241f', name: 'Türkis (Aurora)' },
    { farbe: '#34d399', tinte: '#052e1c', name: 'Grün' },
    { farbe: '#38bdf8', tinte: '#04243a', name: 'Himmelblau' },
    { farbe: '#a78bfa', tinte: '#1e1238', name: 'Violett' },
    { farbe: '#f472b6', tinte: '#3a0a22', name: 'Rosa' },
    { farbe: '#fb7185', tinte: '#3a0a12', name: 'Koralle' },
    { farbe: '#f59e0b', tinte: '#2a1a00', name: 'Bernstein' },
    { farbe: '#facc15', tinte: '#2a2200', name: 'Gelb' }
  ];

  window.WS = { HOSTER: HOSTER, SEKTIONEN: SEKTIONEN, BRANCHEN: BRANCHEN, RECHTSFORMEN: RECHTSFORMEN,
    MIT_VERTRETUNG: MIT_VERTRETUNG, MIT_REGISTER: MIT_REGISTER, AKZENTE: AKZENTE };
})();
