/* ===========================================================================
   Aus den Antworten des Assistenten das fertige Paket bauen:
   Vorlage (window.VORLAGE) + Projektdaten + Inhalte + Design + Anleitung.
   =========================================================================== */

(function () {
  'use strict';

  var WS = window.WS;

  function kopie(o) { return JSON.parse(JSON.stringify(o)); }
  function json(o) { return JSON.stringify(o, null, 4) + '\n'; }
  function slug(s) {
    return String(s || '').toLowerCase()
      .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'webseite';
  }

  /** Hilfswerte, die an mehreren Stellen gebraucht werden. */
  function abgeleitet(d) {
    var domain = String(d.domain || '').trim().toLowerCase().replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/.*$/, '');
    var host = (d.www ? 'www.' : '') + (domain || 'ihre-domain.de');
    var hoster = WS.HOSTER[d.hoster] || WS.HOSTER.andere;
    var begriffe = Object.assign({ hinweise: 'Hinweise & Urlaub', anfrage: 'Anfrage' }, d.begriffe || {});
    var postfach = function (name) { return name ? name + '@' + (domain || 'ihre-domain.de') : ''; };
    return {
      domain: domain || 'ihre-domain.de',
      host: host,
      webseite: 'https://' + host,
      portalHost: (d.sub || 'portal') + '.' + (domain || 'ihre-domain.de'),
      portalUrl: 'https://' + (d.sub || 'portal') + '.' + (domain || 'ihre-domain.de'),
      hoster: hoster,
      begriffe: begriffe,
      empfaenger: postfach(d.empfaenger) || d.email || '',
      absender: postfach(d.absender),
      postfach: postfach,
      ordner: Object.assign({ webseite: 'webseite', portal: 'portal', privat: 'privat' }, d.ordner || {}),
      wurzel: slug(d.name || 'meine-webseite') + '-portal'
    };
  }

  /* ------------------------------------------------ Startinhalte der Sektionen --- */

  function sektionInhalt(typ, d, a) {
    var b = WS.BRANCHEN[d.branche] || WS.BRANCHEN.sonstiges;
    var knopf = { text: d.knopf || b.knopf, ziel: '#kontakt' };
    var leer = { text: '', ziel: '' };
    var ort = d.ort ? ' · ' + d.ort : '';
    switch (typ) {
      case 'hero': return {
        eyebrow: (d.claim || d.name || '') + ort, zeilen: (d.heroZeilen || b.hero).filter(Boolean), lead: d.lead || '',
        knopf1: knopf, knopf2: d.module.angebote ? { text: a.begriffe.angebote + ' ansehen', ziel: '#angebote' } : leer,
        medium: 'verlauf', video: '', bild: '', abdunkeln: 40, news: { zeigen: false, eyebrow: '', titel: '', ziel: '' } };
      case 'laufband': return { begriffe: (d.angeboteListe || b.angebote).map(function (x) { return x[0]; }).concat([d.ort || 'Vor Ort']).filter(Boolean) };
      case 'ueber-uns': return { farbe: 'standard', eyebrow: 'Über uns', statement: d.beschreibung || 'Ein Leitsatz, der sagt, wofür Sie stehen.',
        absaetze: ['Hier erzählen Sie in wenigen Sätzen, wer Sie sind und was Sie ausmacht.'], bild: '', bildAlt: '',
        werte: [{ titel: 'Erfahrung', text: 'Seit vielen Jahren für Sie da.' }, { titel: 'Sorgfalt', text: 'Gründlich statt schnell.' }, { titel: 'Nähe', text: 'Kurze Wege, klare Absprachen.' }], knopf: leer };
      case 'angebote': return { farbe: 'flaeche', eyebrow: a.begriffe.angebote, titel: 'Was wir', titelLeise: 'für Sie tun.', lead: '', darstellung: 'karten', knopf1: knopf, knopf2: leer };
      case 'team': return { farbe: 'standard', eyebrow: a.begriffe.team, titel: 'Menschen, die', titelLeise: 'zuhören.', lead: '' };
      case 'zahlen': return { farbe: 'akzent', eyebrow: 'In Zahlen', titel: 'Das sind', titelLeise: 'wir.', lead: '',
        eintraege: [{ wert: 10, suffix: '+', einheit: 'Jahre', label: 'Erfahrung', text: '' }, { wert: 500, suffix: '+', einheit: '', label: 'Zufriedene Menschen', text: '' }, { wert: 3, suffix: '', einheit: '', label: a.begriffe.angebote, text: '' }] };
      case 'preise': return { farbe: 'flaeche', eyebrow: 'Preise', titel: 'Klar und', titelLeise: 'transparent.', lead: '', knopf: knopf };
      case 'faq': return { farbe: 'standard', eyebrow: 'Gut zu wissen', titel: 'Häufige', titelLeise: 'Fragen.',
        fragen: [{ frage: 'Wie erreiche ich Sie am besten?', antwort: 'Telefonisch oder über das Kontaktformular — wir melden uns schnell zurück.' }, { frage: 'Welche Zahlungsarten gibt es?', antwort: 'Bar, EC-Karte und Überweisung.' }] };
      case 'kontakt': return { farbe: 'flaeche', zeitenMenue: { zeigen: !!d.module.zeiten && d.zeitenMenue !== false, label: '' }, eyebrow: 'Kontakt', titel: 'Wir freuen uns', titelLeise: 'auf Sie.', lead: '' };
      case 'anfahrt': return { farbe: 'standard', eyebrow: 'Anfahrt', titel: 'So finden Sie', titelLeise: 'zu uns.', lead: 'Auf der Karte sehen Sie den genauen Standort.', adresse: '', zoom: 16, laden: 'klick', punkte: [] };
      case 'text-bild': return { farbe: 'standard', eyebrow: 'Kleinzeile', titel: 'Neue Überschrift', titelLeise: '', absaetze: ['Hier steht Ihr Text.'], bild: '', bildAlt: '', bildSeite: 'rechts', knopf: leer };
      case 'karten': return { farbe: 'flaeche', eyebrow: 'Kleinzeile', titel: 'Neue Überschrift', titelLeise: '', lead: '', spalten: '3',
        karten: ['Erste', 'Zweite', 'Dritte'].map(function (n) { return { eyebrow: '', titel: n + ' Karte', text: 'Kurzer Text.', bild: '', bildAlt: '', link: leer }; }) };
      case 'schritte': return { farbe: 'standard', eyebrow: 'Ablauf', titel: 'So läuft es', titelLeise: 'ab.', lead: '',
        schritte: [{ titel: 'Kontakt aufnehmen', text: 'Telefonisch oder über das Formular.' }, { titel: 'Beratung', text: 'Wir besprechen in Ruhe, was Sie brauchen.' }, { titel: 'Umsetzung', text: 'Gründlich, pünktlich und mit Zeit für Fragen.' }] };
      case 'stimmen': return { farbe: 'flaeche', eyebrow: 'Stimmen', titel: 'Was andere', titelLeise: 'über uns sagen.', lead: '', stimmen: [] };
      case 'galerie': return { farbe: 'standard', eyebrow: 'Einblicke', titel: 'Ein Blick', titelLeise: 'hinter die Kulissen.', lead: '', bilder: [] };
      case 'video': return { farbe: 'akzent', eyebrow: 'Film', titel: 'Neue Überschrift', titelLeise: '', lead: '', video: '', poster: '', wiedergabe: 'steuerung', beschreibung: '' };
      case 'aufruf': return { farbe: 'akzent', eyebrow: '', titel: 'Noch Fragen?', text: 'Wir beraten Sie gern persönlich.', knopf1: knopf, knopf2: leer };
      case 'freitext': return { farbe: 'standard', eyebrow: 'Kleinzeile', titel: 'Neue Überschrift', titelLeise: '', absaetze: ['Hier steht Ihr Text.'] };
    }
    return {};
  }

  function anker(typ, a, belegt) {
    var basis = { hero: 'top', 'ueber-uns': 'ueber-uns', angebote: 'angebote', team: 'team', preise: 'preise', faq: 'fragen', kontakt: 'kontakt', anfahrt: 'anfahrt' }[typ] || slug((WS.SEKTIONEN[typ] || {}).name || typ);
    var k = basis, n = 2;
    while (belegt[k]) k = basis + '-' + n++;
    belegt[k] = true;
    return k;
  }

  /** Kurzer Menüname je Sektionstyp — jeder Bereich außer Kopfbereich und Laufband kann ins Menü. */
  function menueLabel(typ, a) {
    return { 'ueber-uns': 'Über uns', angebote: a.begriffe.angebote, team: a.begriffe.team, preise: 'Preise', kontakt: 'Kontakt', faq: 'Fragen',
      anfahrt: 'Anfahrt', schritte: 'Ablauf', stimmen: 'Stimmen', galerie: 'Galerie', zahlen: 'In Zahlen',
      video: 'Film', hero: '', laufband: '' }[typ] || '';
  }

  /** Dateiname des hochgeladenen Logos in webseite/medien/ — oder ''. */
  function logoDatei(d) {
    var m = d.logo && /^data:image\/(png|jpeg|webp);base64,/.exec(d.logo.daten || '');
    return m ? 'logo.' + (m[1] === 'jpeg' ? 'jpg' : m[1]) : '';
  }

  /* ------------------------------------------------------ Inhaltsdateien --- */

  function inhalte(d, a, vorlage) {
    var lesen = function (n) { return JSON.parse(vorlage.dateien['privat/daten/' + n + '.json']); };
    var b = WS.BRANCHEN[d.branche] || WS.BRANCHEN.sonstiges;
    var raus = {};

    var stamm = lesen('stammdaten');
    stamm.name = d.name; stamm.claim = d.claim || ''; stamm.beschreibung = d.beschreibung || '';
    stamm.kontakt = { strasse: d.strasse || '', plz: d.plz || '', ort: d.ort || '', telefon: d.telefon || '',
      telefonLink: d.telefonLink || '', fax: d.fax || '', email: d.email || '' };
    stamm.recht = { inhaber: d.inhaber || '', rechtsform: d.rechtsform || '', vertreten: WS.MIT_VERTRETUNG.test(d.rechtsform || '') ? (d.vertreten || '') : '',
      register: WS.MIT_REGISTER.test(d.rechtsform || '') ? (d.register || '') : '', ustid: d.ustid || '',
      berufsbezeichnung: d.reglementiert ? (d.berufsbezeichnung || '') : '', verliehenIn: d.reglementiert ? (d.verliehenIn || '') : '',
      kammer: d.kammer || '', aufsichtsbehoerde: d.reglementiert ? (d.aufsichtsbehoerde || '') : '', berufsrecht: d.reglementiert ? (d.berufsrecht || '') : '',
      verantwortlich: d.verantwortlich || '', hoster: d.hosterFirma || '' };
    stamm._hinweis = 'Stammdaten — im Portal unter „' + d.organisation + ' & Kontakt“. Die Angaben unter „recht“ setzt der Generator in Impressum und Datenschutz ein.';
    raus.stammdaten = stamm;

    var allg = lesen('allgemein');
    allg.domain = a.webseite;
    allg.design = d.design === 'eigen' ? 'eigen' : (d.design || 'aurora');
    allg.seo = { titel: d.seoTitel || (d.name + (d.claim ? ' – ' + d.claim : '')), beschreibung: d.seoBeschreibung || d.beschreibung || '', bild: '', indexieren: false };
    allg.kopf = { logo: logoDatei(d) ? 'medien/' + logoDatei(d) : '', logoAlt: '', anzeige: d.logoAnzeige || 'logo', logoGroesse: 'mittel',
      knopfText: d.knopf || b.knopf, knopfZiel: '#kontakt', links: [] };
    allg.fuss = { text: d.beschreibung || '', angeboteZeigen: !!d.module.angebote };
    raus.allgemein = allg;

    raus.angebote = (d.angeboteListe || b.angebote).map(function (x, i) {
      return { aktiv: true, titel: x[0], kurz: x[1] || '', text: 'Hier beschreiben Sie in zwei, drei Sätzen, was ' + (d.organisation === 'Verein' ? 'Mitglieder' : 'Kundinnen und Kunden') + ' erwartet.',
        punkte: [], dauer: '', zusatz: '', bild: '', bildAlt: '', id: slug(x[0]) || 'eintrag-' + (i + 1) };
    });

    var team = lesen('team');
    team[0].name = d.inhaber && !/GmbH|UG|AG|e\. ?V\./.test(d.inhaber) ? d.inhaber : (d.adminAnzeige || 'Vorname Nachname');
    team[0].rolle = d.organisation === 'Verein' ? 'Vorsitz' : 'Inhaberin / Inhaber';
    raus.team = team;

    raus.preise = lesen('preise');

    var zeiten = lesen('zeiten');
    var z = (d.zeiten && d.zeiten.length ? d.zeiten : b.zeitenBeispiel).filter(function (x) { return x[0]; });
    zeiten.zeiten = z.map(function (x) { return { tage: x[0], zeit: x[1] || '' }; });
    zeiten.anfahrt = [];
    raus.zeiten = zeiten;

    var form = lesen('formular');
    form.modus = d.module.formular ? d.modus : 'aus';
    form.empfaenger = a.empfaenger;
    form.absender = a.absender;
    form.betreff = a.begriffe.anfrage + ' über die Webseite';
    form.auswahlLabel = d.module.angebote ? b.auswahlLabel : '';
    raus.formular = form;

    raus.rechtliches = lesen('rechtliches');
    raus.hinweise = lesen('hinweise');

    var belegt = {};
    raus.startseite = { sektionen: (d.sektionen || []).filter(function (s) {
      var info = WS.SEKTIONEN[s.typ];
      return info && (!info.modul || d.module[info.modul]);
    }).map(function (s, i) {
      var ank = anker(s.typ, a, belegt);
      var label = menueLabel(s.typ, a);
      return Object.assign({
        id: 's-' + ('0000000' + (i + 1).toString(16)).slice(-8), typ: s.typ, aktiv: s.aktiv !== false, anker: ank,
        menue: { zeigen: !!s.menue && s.typ !== 'hero' && s.typ !== 'laufband', label: label }
      }, sektionInhalt(s.typ, d, a));
    }) };
    return raus;
  }

  /* ----------------------------------------------------------- Design --- */

  function eigenesDesign(d, vorlage) {
    var basis = JSON.parse(vorlage.dateien['privat/design/' + (d.designBasis === 'aurora-hell' ? 'aurora-hell' : 'aurora') + '/design.json']);
    basis.name = (d.name || 'Eigenes') + ' — ' + (d.designBasis === 'aurora-hell' ? 'Aurora Hell' : 'Aurora');
    basis.beschreibung = 'Vom Assistenten angelegt: ' + (d.designBasis === 'aurora-hell' ? 'Aurora Hell' : 'Aurora') + ' mit eigener Akzentfarbe ' + d.akzent + '.';
    basis.quelle = 'Portal-Assistent';
    basis.schutz = false;
    basis.tokens.accent = d.akzent;
    basis.tokens['accent-ink'] = d.akzentTinte;
    basis.erstellt = new Date().toISOString();
    return basis;
  }

  /* ------------------------------------------------------- Zusatzdateien --- */

  function liesmich(d, a) {
    return [
      'PORTAL-PAKET — ' + d.name,
      '='.repeat(40),
      '',
      'Erzeugt am ' + new Date().toLocaleString('de-DE') + ' mit dem Portal-Assistenten.',
      '',
      'INHALT',
      '  ' + a.ordner.webseite + '/   → Docroot der Webseite (' + a.host + ')',
      '  ' + a.ordner.portal + '/     → Docroot des Portals (' + a.portalHost + ')',
      '  ' + a.ordner.privat + '/     → Inhalte, Konten, Designs, Generator — NICHT in einen Docroot legen',
      '  ANLEITUNG.html → Schritt-für-Schritt: online bringen bei ' + a.hoster.name,
      '  projekt-assistent.json → Ihre Antworten; im Assistenten über „Projektdatei laden“ wieder öffnen',
      '',
      'SCHNELLSTART',
      '  1. Alle drei Ordner per SFTP in das Hauptverzeichnis (/) des Webspace laden.',
      '  2. ' + a.host + ' auf /' + a.ordner.webseite + ' zeigen lassen, ' + a.portalHost + ' auf /' + a.ordner.portal + '.',
      '  3. SSL für ' + a.host + ', ' + a.domain + ' und ' + a.portalHost + ' aktivieren.',
      '  4. ' + a.portalUrl + ' öffnen, Konto „' + (d.adminBenutzer || '') + '“ mit sicherem Passwort anlegen.',
      '  5. Inhalte prüfen, einmal „Webseite neu erzeugen“ — fertig.',
      '',
      'OFFLINE AUSPROBIEREN (ohne Server, ohne Befehle)',
      '  macOS:   Doppelklick auf „Offline ausprobieren (Mac).command“',
      '           Beim ersten Mal meldet macOS „… nicht geöffnet“: auf „Fertig“ klicken,',
      '           dann Systemeinstellungen › Datenschutz & Sicherheit › ganz unten',
      '           „Dennoch öffnen“ › bestätigen › „Trotzdem öffnen“. Danach genügt der Doppelklick.',
      '           Alternative: Terminal öffnen, die Datei hineinziehen, Eingabetaste.',
      '           (macOS 14 und älter: Rechtsklick › Öffnen › Öffnen)',
      '  Windows: Doppelklick auf „Offline ausprobieren (Windows).bat“',
      '           (bei einer Warnung: „Weitere Informationen“ → „Trotzdem ausführen“)',
      '  Fehlt PHP, bietet das Startprogramm die Installation an.',
      '  Portal:  http://localhost:8081   Webseite: http://localhost:8082',
      '',
      'Die Rechtstexte sind eine allgemeine Vorlage und keine Rechtsberatung.',
      ''
    ].join('\n');
  }

  /**
   * Startprogramm für macOS/Linux: sucht PHP (Homebrew, MAMP, XAMPP), bietet
   * sonst die Installation an, startet Portal und Webseite und öffnet den Browser.
   * Mit eingebettetem Paket packt es sich beim ersten Start selbst aus.
   */
  function startSkriptMac(a, paketBase64) {
    var z = [
      '#!/bin/bash',
      '# Offline ausprobieren — Doppelklick genügt. Beenden: dieses Fenster schließen.',
      'cd "$(dirname "$0")"',
      'NAME="' + a.wurzel + '"',
      'clear; echo ""; echo "  ◈  Offline ausprobieren: ' + a.wurzel.replace(/"/g, '') + '"; echo ""'
    ];
    if (paketBase64) {
      z.push(
        'if [ ! -d "$NAME" ]; then',
        '  echo "  Paket wird ausgepackt …"',
        '  awk \'/^__PAKET__$/{f=1;next} f\' "$0" | base64 --decode > "/tmp/$NAME.zip" || { echo "  Auspacken fehlgeschlagen."; read -n 1; exit 1; }',
        '  unzip -q -o "/tmp/$NAME.zip" -d . && rm -f "/tmp/$NAME.zip"',
        'fi',
        'cd "$NAME"'
      );
    }
    z.push(
      '# Einmal freigegeben: auch die ausgepackten Dateien nicht mehr als „aus dem Internet“ behandeln',
      'xattr -dr com.apple.quarantine . 2>/dev/null',
      'PHP=""',
      'for p in "$(command -v php)" /opt/homebrew/bin/php /usr/local/bin/php $(ls -d /Applications/MAMP/bin/php/php8*/bin/php 2>/dev/null | sort -r) /Applications/XAMPP/xamppfiles/bin/php; do',
      '  if [ -n "$p" ] && [ -x "$p" ] && "$p" -r \'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);\' 2>/dev/null; then PHP="$p"; break; fi',
      'done',
      'if [ -z "$PHP" ]; then',
      '  echo "  Für den Test wird PHP gebraucht (kostenlos, einmalig)."',
      '  if command -v brew >/dev/null 2>&1; then',
      '    read -p "  Jetzt automatisch installieren? (j/n) " antwort',
      '    if [ "$antwort" = "j" ] || [ "$antwort" = "J" ]; then brew install php && PHP="$(command -v php)"; fi',
      '  fi',
      '  if [ -z "$PHP" ]; then',
      '    echo "  Am einfachsten: das kostenlose Programm MAMP installieren (öffnet sich gleich)."',
      '    echo "  Danach dieses Startprogramm einfach noch einmal doppelklicken."',
      '    (command -v open >/dev/null && open "https://www.mamp.info/de/downloads/") || xdg-open "https://www.mamp.info/de/downloads/" 2>/dev/null',
      '    read -n 1 -p "  Taste drücken zum Schließen …"; exit 1',
      '  fi',
      'fi',
      'frei() { p=$1; while (lsof -i :$p >/dev/null 2>&1); do p=$((p+1)); done; echo $p; }',
      'PORTAL=$(frei 8081); WEB=$(frei $((PORTAL+1)))',
      '"$PHP" -S localhost:$WEB -t "' + a.ordner.webseite + '" >/dev/null 2>&1 &',
      'WEBPID=$!',
      'trap "kill $WEBPID 2>/dev/null" EXIT',
      'echo "  Portal:   http://localhost:$PORTAL   ← beim ersten Mal: Konto anlegen"',
      'echo "  Webseite: http://localhost:$WEB   ← nach „Webseite neu erzeugen“ im Portal"',
      'echo ""; echo "  Zum Beenden dieses Fenster schließen."; echo ""',
      '(sleep 1; (command -v open >/dev/null && open "http://localhost:$PORTAL") || xdg-open "http://localhost:$PORTAL") >/dev/null 2>&1 &',
      '"$PHP" -S localhost:$PORTAL -t "' + a.ordner.portal + '" >/dev/null 2>&1',
      'exit 0'
    );
    var text = z.join('\n') + '\n';
    return paketBase64 ? text + '__PAKET__\n' + paketBase64.replace(/(.{76})/g, '$1\n') + '\n' : text;
  }

  /**
   * Startprogramm für Windows: wie oben — PHP suchen (PATH, XAMPP, winget),
   * sonst per winget installieren (nach Rückfrage), dann Portal und Webseite starten.
   */
  function startSkriptWin(a, paketBase64) {
    var z = [
      '@echo off',
      'chcp 65001 >nul',
      'title Offline ausprobieren',
      'rem Offline ausprobieren — Doppelklick genuegt. Beenden: dieses Fenster schliessen.',
      'cd /d "%~dp0"',
      'set "NAME=' + a.wurzel + '"',
      'echo.',
      'echo   Offline ausprobieren: ' + a.wurzel,
      'echo.'
    ];
    if (paketBase64) {
      z.push(
        'if not exist "%NAME%\\" (',
        '  echo   Paket wird ausgepackt ...',
        '  powershell -NoProfile -ExecutionPolicy Bypass -Command "$t=[IO.File]::ReadAllText(\'%~f0\'); $i=$t.LastIndexOf(\'::PAKET\'+\'::\'); $b=$t.Substring($i+9) -replace \'[^A-Za-z0-9+/=]\',\'\'; [IO.File]::WriteAllBytes(\\"$env:TEMP\\ws-paket.zip\\",[Convert]::FromBase64String($b)); Expand-Archive -LiteralPath \\"$env:TEMP\\ws-paket.zip\\" -DestinationPath \'%~dp0.\' -Force"',
        ')',
        'cd /d "%~dp0%NAME%"'
      );
    }
    z.push(
      'set "PHP="',
      'for /f "delims=" %%p in (\'where php 2^>nul\') do if not defined PHP set "PHP=%%p"',
      'if not defined PHP if exist "C:\\xampp\\php\\php.exe" set "PHP=C:\\xampp\\php\\php.exe"',
      'if not defined PHP for /f "delims=" %%p in (\'dir /b /s "%LOCALAPPDATA%\\Microsoft\\WinGet\\Packages\\php.exe" 2^>nul\') do if not defined PHP set "PHP=%%p"',
      'if not defined PHP (',
      '  echo   Fuer den Test wird PHP gebraucht ^(kostenlos, einmalig^).',
      '  choice /c JN /m "  Jetzt automatisch installieren"',
      '  if errorlevel 2 goto :ohnephp',
      '  winget install -e --id PHP.PHP.8.3 --accept-source-agreements --accept-package-agreements',
      '  for /f "delims=" %%p in (\'dir /b /s "%LOCALAPPDATA%\\Microsoft\\WinGet\\Packages\\php.exe" 2^>nul\') do if not defined PHP set "PHP=%%p"',
      ')',
      'if not defined PHP goto :ohnephp',
      'for %%d in ("%PHP%") do set "PHPDIR=%%~dpd"',
      'rem Ohne php.ini: die noetigen Erweiterungen direkt einschalten',
      'set "OPT=-n -d extension_dir="%PHPDIR%ext" -d extension=mbstring -d extension=openssl -d extension=gd -d extension=zip -d extension=fileinfo"',
      'echo   Portal:   http://localhost:8081   ^<- beim ersten Mal: Konto anlegen',
      'echo   Webseite: http://localhost:8082   ^<- nach "Webseite neu erzeugen" im Portal',
      'echo.',
      'echo   Zum Beenden dieses Fenster schliessen.',
      'start "Webseite" /min "%PHP%" %OPT% -S localhost:8082 -t "' + a.ordner.webseite + '"',
      'start "" http://localhost:8081',
      '"%PHP%" %OPT% -S localhost:8081 -t "' + a.ordner.portal + '"',
      'exit /b 0',
      ':ohnephp',
      'echo   Am einfachsten: das kostenlose Programm XAMPP installieren ^(oeffnet sich gleich^).',
      'echo   Danach dieses Startprogramm einfach noch einmal doppelklicken.',
      'start "" https://www.apachefriends.org/de/download.html',
      'pause',
      'exit /b 1'
    );
    var text = z.join('\r\n') + '\r\n';
    return paketBase64 ? text + '::PAKET::\r\n' + paketBase64 + '\r\n' : text;
  }

  function base64(bytes) {
    var teile = [];
    for (var i = 0; i < bytes.length; i += 0x8000) teile.push(String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000)));
    return btoa(teile.join(''));
  }

  /**
   * Ein einzelnes Startprogramm zum Offline-Ausprobieren, in dem das ganze
   * Paket steckt. system: 'mac' | 'windows'. Liefert {name, inhalt, typ}.
   */
  function offlineStarter(eingabe, system) {
    var paket = erzeugen(eingabe);
    var a = abgeleitet(mitStandards(eingabe));
    return window.zipErstellen(paket.dateien).arrayBuffer().then(function (puffer) {
      var b64 = base64(new Uint8Array(puffer));
      if (system === 'windows') {
        var bat = 'Offline ausprobieren – ' + a.wurzel + '.bat';
        return { name: bat, datei: bat, blob: new Blob([startSkriptWin(a, b64)], { type: 'application/octet-stream' }) };
      }
      // macOS startet .command-Dateien nur mit Ausführungsrecht — das übersteht
      // einen Download nur in einer ZIP-Datei. Safari packt sie selbst aus.
      var cmd = 'Offline ausprobieren – ' + a.wurzel + '.command';
      return { name: 'Offline ausprobieren – ' + a.wurzel + '.zip', datei: cmd,
        blob: window.zipErstellen([{ pfad: cmd, inhalt: startSkriptMac(a, b64), rechte: 0x1ed }]) };
    });
  }

  /* ---------------------------------------------------------- Paket --- */

  /**
   * Was übersprungen wurde, bekommt einen neutralen Platzhalter — so entsteht
   * immer ein lauffähiges Paket. Alles lässt sich später im Portal ergänzen.
   */
  function mitStandards(eingabe) {
    var d = kopie(eingabe);
    var b = WS.BRANCHEN[d.branche] || WS.BRANCHEN.sonstiges;
    if (!d.branche) d.branche = 'sonstiges';
    if (!String(d.name || '').trim()) d.name = 'Meine Webseite';
    if (!String(d.portal || '').trim()) d.portal = d.name + ' Portal';
    if (!String(d.kuerzel || '').trim()) d.kuerzel = d.name.slice(0, 1).toUpperCase();
    if (!d.organisation) d.organisation = b.organisation;
    if (!d.knopf) d.knopf = b.knopf;
    if (!d.heroZeilen || !d.heroZeilen.filter(Boolean).length) d.heroZeilen = b.hero.slice();
    if (!d.sektionen || !d.sektionen.length) d.sektionen = b.sektionen.map(function (t) { return { typ: t, aktiv: true, menue: t !== 'hero' && t !== 'laufband' }; });
    if (!d.postfaecher || !d.postfaecher.length) d.postfaecher = [{ name: 'info', zweck: '' }];
    return d;
  }

  /** @return {{wurzel:string, dateien:Array<{pfad:string,inhalt:(string|Uint8Array),rechte?:number}>}} */
  function erzeugen(eingabe) {
    var d = mitStandards(eingabe);
    var vorlage = window.VORLAGE;
    if (!vorlage) throw new Error('Die Vorlage (assets/vorlage.js) fehlt.');
    var a = abgeleitet(d);
    var dateien = [];
    var pfad = function (p) {
      var teile = p.split('/');
      var o = { webseite: a.ordner.webseite, portal: a.ordner.portal, privat: a.ordner.privat }[teile[0]];
      if (o) teile[0] = o;
      return a.wurzel + '/' + teile.join('/');
    };

    Object.keys(vorlage.dateien).forEach(function (p) {
      if (/^privat\/daten\//.test(p) || p === 'privat/projekt.json') return;
      dateien.push({ pfad: pfad(p), inhalt: vorlage.dateien[p] });
    });
    Object.keys(vorlage.binaer || {}).forEach(function (p) {
      var bin = atob(vorlage.binaer[p]);
      var bytes = new Uint8Array(bin.length);
      for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
      dateien.push({ pfad: pfad(p), inhalt: bytes });
    });

    var projekt = {
      _hinweis: 'Vom Portal-Assistenten geschrieben. Legt fest, wie Portal und Webseite heißen, welche Module es gibt und wie Bereiche benannt sind.',
      name: d.name, portal: d.portal || (d.name + ' Portal'), kuerzel: d.kuerzel || d.name.slice(0, 1).toUpperCase(),
      organisation: d.organisation, branche: d.branche, zeitzone: 'Europe/Berlin', reglementiert: !!d.reglementiert,
      sitzungsdauer: +d.sitzungsdauer || 120,
      module: d.module, begriffe: a.begriffe,
      hoster: { name: a.hoster.name, smtp: d.smtpHost || a.hoster.smtp.host, port: +d.smtpPort || a.hoster.smtp.port },
      admin: { benutzer: d.adminBenutzer, anzeige: d.adminAnzeige },
      adressen: { webseite: a.webseite, portal: a.portalUrl },
      erstellt: new Date().toISOString()
    };
    dateien.push({ pfad: pfad('privat/projekt.json'), inhalt: json(projekt) });

    var daten = inhalte(d, a, vorlage);
    Object.keys(daten).forEach(function (n) { dateien.push({ pfad: pfad('privat/daten/' + n + '.json'), inhalt: json(daten[n]) }); });

    if (logoDatei(d)) {
      var roh = atob(d.logo.daten.split(',')[1]);
      var bytes = new Uint8Array(roh.length);
      for (var j = 0; j < roh.length; j++) bytes[j] = roh.charCodeAt(j);
      dateien.push({ pfad: pfad('webseite/medien/' + logoDatei(d)), inhalt: bytes });
    }

    if (d.design === 'eigen') {
      dateien.push({ pfad: pfad('privat/design/eigen/design.json'), inhalt: json(eigenesDesign(d, vorlage)) });
    }

    dateien.push({ pfad: a.wurzel + '/LIESMICH.txt', inhalt: liesmich(d, a) });
    dateien.push({ pfad: a.wurzel + '/Offline ausprobieren (Mac).command', inhalt: startSkriptMac(a), rechte: 0x1ed /* 0755 */ });
    dateien.push({ pfad: a.wurzel + '/Offline ausprobieren (Windows).bat', inhalt: startSkriptWin(a) });
    dateien.push({ pfad: a.wurzel + '/projekt-assistent.json', inhalt: json({ assistent: 1, daten: d }) });
    if (window.anleitungHtml) {
      dateien.push({ pfad: a.wurzel + '/ANLEITUNG.html', inhalt: window.anleitungHtml(d) });
    }
    return { wurzel: a.wurzel, dateien: dateien };
  }

  window.WSErzeugen = { erzeugen: erzeugen, abgeleitet: abgeleitet, slug: slug, kopie: kopie, offlineStarter: offlineStarter, mitStandards: mitStandards };
})();
