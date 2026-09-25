/* ===========================================================================
   Portal-Assistent — Ablauf, Fragen, Speichern, Erzeugen, Anleitung.

   Zwei Abschnitte:
     ① Entwicklung      — Fragen beantworten, Paket erzeugen (offline)
     ② Implementierung  — Schritt-für-Schritt-Anleitung, um es online zu bringen
   Nach dem Erzeugen lässt sich wählen: mit der Anleitung weitermachen oder
   die Installation abschließen.

   Stand wird im Browser gespeichert (localStorage) — ohne Server, ohne Konto.
   =========================================================================== */

(function () {
  'use strict';

  var WS = window.WS;
  var SPEICHER = 'portal-assistent-v1';
  var view = document.getElementById('view');
  var leiste = document.getElementById('leiste-innen');

  /* ------------------------------------------------------------ Helfer --- */

  function $(s, w) { return (w || document).querySelector(s); }
  function $$(s, w) { return Array.prototype.slice.call((w || document).querySelectorAll(s)); }
  function e(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (z) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[z]; }); }
  function kopie(o) { return JSON.parse(JSON.stringify(o)); }
  function holen(o, pfad) { return pfad.split('.').reduce(function (x, k) { return x == null ? undefined : x[k]; }, o); }
  function setzen(o, pfad, wert) {
    var teile = pfad.split('.');
    var ziel = o;
    for (var i = 0; i < teile.length - 1; i++) { if (typeof ziel[teile[i]] !== 'object' || ziel[teile[i]] === null) ziel[teile[i]] = {}; ziel = ziel[teile[i]]; }
    ziel[teile[teile.length - 1]] = wert;
  }
  function ascii(s) {
    return String(s || '').toLowerCase().replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
      .normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  var toastUhr = null;
  function toast(text, fehler) {
    var t = document.getElementById('toast');
    t.textContent = text;
    t.className = 'toast da' + (fehler ? ' toast--fehler' : '');
    clearTimeout(toastUhr);
    toastUhr = setTimeout(function () { t.className = 'toast' + (fehler ? ' toast--fehler' : ''); }, fehler ? 3200 : 1800);
  }

  /** Eigener Bestätigungsdialog — „Bestätigen, was weh tut“. */
  function fragen(titel, text, ja, weiter) {
    var d = document.createElement('div');
    d.className = 'dialog';
    d.setAttribute('role', 'alertdialog');
    d.setAttribute('aria-modal', 'true');
    d.innerHTML = '<div class="card card--solo"><h3>' + e(titel) + '</h3><p class="card-hint">' + e(text) + '</p>'
      + '<div class="btn-row"><button type="button" class="btn btn--ghost" data-nein>Abbrechen</button><button type="button" class="btn btn--danger" data-ja>' + e(ja) + '</button></div></div>';
    document.body.appendChild(d);
    $('[data-nein]', d).focus();
    d.addEventListener('click', function (ev) {
      if (ev.target === d || ev.target.closest('[data-nein]')) d.remove();
      else if (ev.target.closest('[data-ja]')) { d.remove(); weiter(); }
    });
  }

  /* ----------------------------------------------------------- Zustand --- */

  function grunddaten() {
    var b = WS.BRANCHEN.sonstiges;
    return {
      branche: '', organisation: b.organisation, reglementiert: false,
      name: '', claim: '', beschreibung: '', portal: '', kuerzel: '',
      strasse: '', plz: '', ort: '', telefon: '', telefonLink: '', fax: '', email: '',
      inhaber: '', rechtsform: b.rechtsform, vertreten: '', register: '', ustid: '',
      berufsbezeichnung: '', verliehenIn: 'Bundesrepublik Deutschland', kammer: '', aufsichtsbehoerde: '', berufsrecht: '', verantwortlich: '',
      hoster: 'strato', hosterFirma: WS.HOSTER.strato.firma, domain: '', www: true, sub: 'portal',
      ordner: { webseite: 'webseite', portal: 'portal', privat: 'privat' }, php: '8.3',
      postfaecher: [{ name: 'info', zweck: 'Empfang: Anfragen und allgemeine Post' }, { name: 'webseite', zweck: 'Absender des Kontaktformulars' }],
      empfaenger: 'info', absender: 'webseite', modus: 'server', smtpHost: WS.HOSTER.strato.smtp.host, smtpPort: WS.HOSTER.strato.smtp.port,
      module: kopie(b.module), begriffe: kopie(b.begriffe),
      sektionen: b.sektionen.map(function (t) { return { typ: t, aktiv: true, menue: true }; }),
      design: 'aurora', designBasis: 'aurora', akzent: '#5eead4', akzentTinte: '#04241f',
      heroZeilen: b.hero.slice(), lead: '', knopf: b.knopf, seoTitel: '', seoBeschreibung: '',
      angeboteListe: kopie(b.angebote), zeiten: kopie(b.zeitenBeispiel),
      adminAnzeige: '', adminBenutzer: '', sitzungsdauer: 120,
      logo: null, logoAnzeige: 'logo', zeitenMenue: true,
      _manuell: {}
    };
  }

  var S = { phase: 'start', schritt: 0, anleitung: 0, erledigt: {}, erzeugt: false, offen: {}, d: grunddaten() };

  function laden() {
    try {
      var roh = window.localStorage.getItem(SPEICHER);
      if (!roh) return false;
      var x = JSON.parse(roh);
      if (!x || !x.d) return false;
      S = Object.assign({ phase: 'start', schritt: 0, anleitung: 0, erledigt: {}, erzeugt: false, offen: {} }, x);
      S.d = Object.assign(grunddaten(), x.d);
      return true;
    } catch (err) { return false; }
  }
  var speicherUhr = null;
  function speichern() {
    clearTimeout(speicherUhr);
    speicherUhr = setTimeout(function () {
      try { window.localStorage.setItem(SPEICHER, JSON.stringify(S)); } catch (err) { /* privat oder voll */ }
    }, 150);
  }
  function hatGespeichert() {
    try { return !!window.localStorage.getItem(SPEICHER); } catch (err) { return false; }
  }

  /** Abgeleitete Werte nachziehen, solange sie niemand von Hand geändert hat. */
  function ableiten() {
    var d = S.d, m = d._manuell || (d._manuell = {});
    // Sektionstypen, die es nicht (mehr) gibt, fallen heraus
    d.sektionen = (d.sektionen || []).filter(function (x) { return WS.SEKTIONEN[x.typ]; });
    if (!m.portal) d.portal = d.name ? d.name + ' Portal' : '';
    if (!m.kuerzel) {
      var w = String(d.name || '').replace(/[^A-Za-zÄÖÜäöü0-9 &]/g, ' ').split(/\s+/).filter(function (x) { return x && x !== '&'; });
      d.kuerzel = (w.length > 1 ? w[0][0] + w[1][0] : (w[0] || 'W').slice(0, 1)).toUpperCase();
    }
    if (!m.telefonLink) {
      var ziffern = String(d.telefon || '').replace(/[^\d+]/g, '');
      d.telefonLink = ziffern ? (ziffern[0] === '+' ? ziffern : (ziffern.slice(0, 2) === '00' ? '+' + ziffern.slice(2) : '+49' + ziffern.replace(/^0/, ''))) : '';
    }
    if (!m.adminBenutzer) {
      var teile = ascii(d.adminAnzeige).replace(/[^a-z0-9 ]/g, ' ').trim().split(/\s+/).filter(Boolean);
      d.adminBenutzer = teile.length ? teile.join('.').slice(0, 32) : '';
    }
    if (!m.seoTitel) d.seoTitel = d.name ? (d.name + (d.claim ? ' – ' + d.claim : (d.ort ? ' in ' + d.ort : ''))).slice(0, 60) : '';
    if (!m.seoBeschreibung) d.seoBeschreibung = d.beschreibung || '';
    if (!m.hosterFirma) d.hosterFirma = (WS.HOSTER[d.hoster] || WS.HOSTER.andere).firma;
    if (!m.smtp) { var h = WS.HOSTER[d.hoster] || WS.HOSTER.andere; d.smtpHost = h.smtp.host; d.smtpPort = h.smtp.port; }
    // Design: eigene Akzentfarbe = eigenes Design
    var basisAkzent = d.designBasis === 'aurora-hell' ? '#0f8f7c' : '#5eead4';
    d.design = d.akzent.toLowerCase() !== basisAkzent ? 'eigen' : d.designBasis;
    // Empfänger und Absender müssen existierende Postfächer sein
    var namen = d.postfaecher.map(function (p) { return p.name; });
    if (namen.indexOf(d.empfaenger) < 0) d.empfaenger = namen[0] || '';
    if (namen.indexOf(d.absender) < 0) d.absender = namen[namen.length - 1] || '';
  }

  function brancheAnwenden(id) {
    var b = WS.BRANCHEN[id];
    if (!b) return;
    var d = S.d;
    d.branche = id;
    d.organisation = b.organisation;
    d.reglementiert = b.reglementiert;
    d.rechtsform = b.rechtsform;
    d.begriffe = kopie(b.begriffe);
    d.module = kopie(b.module);
    d.sektionen = b.sektionen.map(function (t) { return { typ: t, aktiv: true, menue: ['ueber-uns', 'angebote', 'team', 'preise', 'kontakt'].indexOf(t) >= 0 }; });
    d.heroZeilen = b.hero.slice();
    d.knopf = b.knopf;
    d.angeboteListe = kopie(b.angebote);
    d.zeiten = kopie(b.zeitenBeispiel);
  }

  /* -------------------------------------------------------- Bausteine --- */

  function feld(pfad, label, o) {
    o = o || {};
    var wert = holen(S.d, pfad);
    var id = 'f-' + pfad.replace(/\./g, '-');
    var attr = ' id="' + id + '" data-feld="' + e(pfad) + '"' + (o.muster ? ' pattern="' + e(o.muster) + '"' : '')
      + (o.platzhalter ? ' placeholder="' + e(o.platzhalter) + '"' : '') + (o.auto ? ' autocomplete="' + o.auto + '"' : ' autocomplete="off"')
      + (o.typ === 'email' ? ' inputmode="email"' : '') + (o.typ === 'tel' ? ' inputmode="tel"' : '') + (o.max ? ' data-max="' + o.max + '"' : '');
    var eingabe;
    if (o.mehrzeilig) eingabe = '<textarea class="input" rows="' + (o.zeilen || 3) + '"' + attr + '>' + e(wert) + '</textarea>';
    else if (o.optionen) eingabe = '<select class="input"' + attr + '>' + o.optionen.map(function (x) {
      var v = Array.isArray(x) ? x[0] : x, t = Array.isArray(x) ? x[1] : x;
      return '<option value="' + e(v) + '"' + (String(v) === String(wert) ? ' selected' : '') + '>' + e(t) + '</option>';
    }).join('') + '</select>';
    else eingabe = (o.vor || o.nach ? '<div class="input-mit">' + (o.vor ? '<span>' + e(o.vor) + '</span>' : '') : '')
      + '<input class="input" type="' + (o.typ || 'text') + '" value="' + e(wert) + '"' + attr + (o.spell === false ? ' spellcheck="false"' : '') + '>'
      + (o.vor || o.nach ? (o.nach ? '<span>' + e(o.nach) + '</span>' : '') + '</div>' : '');
    return '<div class="field' + (o.klasse ? ' ' + o.klasse : '') + '" data-feldhuelle="' + e(pfad) + '"><label for="' + id + '">' + e(label) + (o.pflicht ? ' <span class="pflicht">*</span>' : '') + '</label>'
      + eingabe + (o.max ? '<div class="zaehler" data-zaehler-fuer="' + e(pfad) + '"></div>' : '') + (o.hilfe ? '<p class="field-hint">' + o.hilfe + '</p>' : '') + '</div>';
  }

  function schalter(pfad, label, klein, attr) {
    return '<label class="switch"><span class="sw-label">' + e(label) + (klein ? '<small>' + klein + '</small>' : '') + '</span>'
      + '<input type="checkbox" data-feld="' + e(pfad) + '"' + (holen(S.d, pfad) ? ' checked' : '') + (attr || '') + '></label>';
  }

  function seg(pfad, optionen) {
    var wert = String(holen(S.d, pfad));
    return '<div class="seg" role="group">' + optionen.map(function (o) {
      return '<button type="button" data-seg="' + e(pfad) + '" data-wert="' + e(o[0]) + '"' + (String(o[0]) === wert ? ' class="is-active" aria-pressed="true"' : ' aria-pressed="false"') + '>' + e(o[1]) + '</button>';
    }).join('') + '</div>';
  }

  function kopf(eyebrow, titel, lead) {
    return '<p class="eyebrow">' + e(eyebrow) + '</p><h1 class="title">' + e(titel) + '</h1>' + (lead ? '<p class="lead">' + lead + '</p>' : '');
  }

  function hinweis(text, warn) {
    return '<p class="hinweis' + (warn ? ' hinweis--warn' : '') + '"><i>' + (warn ? '!' : 'i') + '</i><span>' + text + '</span></p>';
  }

  /* ------------------------------------------ Schritte der Entwicklung --- */

  var SCHRITTE = [
    {
      id: 'branche', titel: 'Art der Webseite',
      zeichnen: function () {
        var d = S.d;
        return kopf('Entwicklung · Start', 'Wofür ist die Webseite?', 'Danach richtet der Assistent passende Bereiche und Beispieltexte ein. Alles lässt sich später ändern.')
          + '<div class="wahl">' + Object.keys(WS.BRANCHEN).map(function (id) {
            var b = WS.BRANCHEN[id];
            return '<button type="button" class="wahl-karte' + (d.branche === id ? ' is-active' : '') + '" data-branche="' + id + '" aria-pressed="' + (d.branche === id) + '">'
              + '<span class="ic" aria-hidden="true">' + b.zeichen + '</span><b>' + e(b.name) + '</b><small>' + e(b.text) + '</small></button>';
          }).join('') + '</div>'
          + (d.branche ? '<div class="card" style="margin-top:14px"><h3>Feinschliff</h3><p class="card-hint">So heißt es im Verwaltungsbereich und im Impressum.</p>'
            + feld('organisation', 'Wie nennen Sie sich?', { hilfe: 'z. B. Praxis, Betrieb, Büro, Verein, Restaurant — erscheint z. B. als „' + e(d.organisation) + ' & Kontakt“.' })
            + schalter('reglementiert', 'Reglementierter Beruf', 'Heilberufe, Handwerksmeister, Steuerberatung, Architektur … Dann gehören Berufsbezeichnung, Kammer und Aufsichtsbehörde ins Impressum.')
            + '</div>' : '');
      },
      pruefen: function () { return S.d.branche ? [] : [{ text: 'Bitte eine Art auswählen.' }]; }
    },
    {
      id: 'name', titel: 'Name & Marke',
      zeichnen: function () {
        return kopf('Entwicklung', 'Name und Marke', 'Wie heißt das, was auf der Webseite steht?')
          + '<div class="card">' + feld('name', 'Name', { pflicht: true, platzhalter: 'z. B. Tischlerei Holzmann', auto: 'organization' })
          + feld('claim', 'Zusatz (optional)', { platzhalter: 'z. B. Möbel nach Maß seit 1987' })
          + feld('beschreibung', 'Ein Satz über Sie', { mehrzeilig: true, zeilen: 2, max: 160, hilfe: 'Erscheint in der Fußzeile und als Beschreibung bei Google.' }) + '</div>'
          + logoKarte()
          + '<div class="card"><h3>Ihr Verwaltungsbereich („Portal“)</h3><p class="card-hint">Hier ändern Sie später Texte, Bilder, Preise und Zeiten Ihrer Webseite — ohne Programmierkenntnisse, einfach im Browser.</p>'
          + '<div class="field-grid" style="grid-template-columns:1fr 110px">' + feld('portal', 'Name des Portals', { platzhalter: 'z. B. Holzmann Portal' })
          + feld('kuerzel', 'Kürzel', { hilfe: 'Im Logo-Kästchen.' }) + '</div></div>';
      },
      pruefen: function () { return S.d.name.trim() ? [] : [{ feld: 'name', text: 'Bitte einen Namen eintragen.' }]; }
    },
    {
      id: 'kontakt', titel: 'Anschrift & Kontakt',
      zeichnen: function () {
        return kopf('Entwicklung', 'Anschrift und Kontakt', 'Steht im Kontaktbereich, in der Fußzeile und im Impressum.')
          + '<div class="card">' + feld('strasse', 'Straße und Hausnummer', { auto: 'street-address' })
          + '<div class="field-grid field-grid--pl">' + feld('plz', 'PLZ', { muster: '[0-9]{4,5}', auto: 'postal-code' }) + feld('ort', 'Ort', { auto: 'address-level2' }) + '</div>'
          + '<div class="field-grid">' + feld('telefon', 'Telefon', { typ: 'tel', platzhalter: '030 123 45 67', auto: 'tel' })
          + feld('telefonLink', 'Telefon zum Anrufen', { typ: 'tel', muster: '\\+?[0-9]+', spell: false, hilfe: 'Wird automatisch gebildet.' }) + '</div>'
          + '<div class="field-grid">' + feld('email', 'E-Mail-Adresse', { pflicht: true, typ: 'email', auto: 'email', platzhalter: 'info@ihre-domain.de', hilfe: 'Öffentliche Adresse für Impressum und Kontakt.' })
          + feld('fax', 'Telefax (optional)', { typ: 'tel' }) + '</div></div>';
      },
      pruefen: function () {
        var d = S.d, f = [];
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email.trim())) f.push({ feld: 'email', text: 'Bitte eine gültige E-Mail-Adresse eintragen.' });
        if (d.plz && !/^[0-9]{4,5}$/.test(d.plz)) f.push({ feld: 'plz', text: 'Die PLZ hat 4 oder 5 Ziffern.' });
        if (d.telefonLink && !/^\+?[0-9]+$/.test(d.telefonLink)) f.push({ feld: 'telefonLink', text: 'Nur Ziffern und ein + am Anfang.' });
        return f;
      }
    },
    {
      id: 'recht', titel: 'Rechtliche Angaben',
      zeichnen: function () {
        var d = S.d;
        return kopf('Entwicklung', 'Angaben fürs Impressum', 'Jede Webseite braucht ein Impressum. Diese Angaben werden automatisch in Impressum und Datenschutzerklärung eingesetzt. Was fehlt, zeigt Ihnen der Verwaltungsbereich später als offenen Punkt.')
          + hinweis('Die Rechtstexte sind eine allgemeine Vorlage und keine Rechtsberatung. Im Zweifel vor dem Start prüfen lassen.', true)
          + '<div class="card">' + feld('inhaber', d.rechtsform && WS.MIT_VERTRETUNG.test(d.rechtsform) ? 'Firma (vollständig)' : 'Inhaberin / Inhaber', { platzhalter: 'Vor- und Nachname', hilfe: 'Bei Gesellschaften der vollständige Firmenname.' })
          + feld('rechtsform', 'Rechtsform', { optionen: WS.RECHTSFORMEN })
          + (WS.MIT_VERTRETUNG.test(d.rechtsform) ? feld('vertreten', 'Vertreten durch', { platzhalter: 'Geschäftsführung bzw. Vorstand' }) : '')
          + (WS.MIT_REGISTER.test(d.rechtsform) ? feld('register', 'Registereintrag', { platzhalter: 'Amtsgericht …, HRB/VR …' }) : '')
          + feld('ustid', 'USt-IdNr. (falls vorhanden)', { platzhalter: 'DE123456789' })
          + feld('verantwortlich', 'Verantwortlich nach § 18 MStV', { hilfe: 'Leer lassen = die Inhaberin bzw. der Inhaber.' }) + '</div>'
          + '<div class="card"><h3>Reglementierter Beruf</h3>' + schalter('reglementiert', 'Angaben zu Beruf und Kammer', 'Nur für reglementierte Berufe nötig.')
          + (d.reglementiert ? '<div style="margin-top:12px">' + feld('berufsbezeichnung', 'Berufsbezeichnung', { platzhalter: 'z. B. Physiotherapeutin' })
            + feld('verliehenIn', 'Verliehen in') + feld('kammer', 'Kammer (falls Pflichtmitglied)')
            + feld('aufsichtsbehoerde', 'Zuständige Aufsichtsbehörde', { mehrzeilig: true, zeilen: 2, hilfe: 'Mit Anschrift, z. B. Gesundheitsamt des Kreises.' })
            + feld('berufsrecht', 'Berufsrechtliche Regelungen', { mehrzeilig: true, zeilen: 2, hilfe: 'Name des Gesetzes und wo es abrufbar ist.' }) + '</div>' : '')
          + '</div>';
      },
      pruefen: function () { return []; }
    },
    {
      id: 'domain', titel: 'Domain & Hosting',
      zeichnen: function () {
        var d = S.d, A = window.WSErzeugen.abgeleitet(d);
        return kopf('Entwicklung', 'Adresse und Anbieter', 'Unter welcher Adresse soll die Webseite erreichbar sein — und bei welcher Firma ist sie gemietet? Damit füllt der Assistent die Anleitung mit den passenden Angaben. Noch nichts gemietet? Einfach „STRATO“ lassen oder den Schritt überspringen.')
          + '<p class="field-label">Wo ist bzw. wird die Webseite gemietet? (Hosting-Anbieter)</p><div class="chips chips--wrap" style="margin-bottom:14px">' + Object.keys(WS.HOSTER).map(function (id) {
            return '<button type="button" class="chip' + (d.hoster === id ? ' is-active' : '') + '" data-hoster="' + id + '">' + e(WS.HOSTER[id].name) + '</button>';
          }).join('') + '</div>'
          + '<div class="card">' + feld('domain', 'Adresse der Webseite (Domain)', { pflicht: true, platzhalter: 'ihre-domain.de', spell: false, hilfe: 'So, wie Kunden sie eintippen — ohne „www.“. Gibt es noch keine? Den Wunschnamen eintragen; bestellt wird er in der Anleitung.' })
          + '<p class="field-label">Wie soll sie im Browser erscheinen?</p>' + seg('www', [[true, 'www.' + A.domain], [false, A.domain]])
          + '<div style="margin-top:14px">' + feld('sub', 'Adresse Ihres Verwaltungsbereichs', { muster: '[a-z0-9-]+', nach: '.' + A.domain, spell: false, hilfe: 'Hier melden Sie sich später an, um die Webseite zu pflegen: ' + e(A.portalUrl) + '. „portal“ passt fast immer.' }) + '</div></div>'
          + '<div class="card"><h3>Hoster fürs Impressum</h3><p class="card-hint">Steht in der Datenschutzerklärung. Bitte mit dem Impressum bzw. dem Vertrag zur Auftragsverarbeitung des Anbieters abgleichen.</p>'
          + feld('hosterFirma', 'Name und Anschrift des Hosters', { mehrzeilig: true, zeilen: 3 }) + '</div>'
          + '<details class="card"><summary class="card-titel" style="cursor:pointer">Für Fortgeschrittene: Ordner und PHP <span class="still klein">— kann so bleiben</span></summary><div style="margin-top:12px">'
          + '<div class="field-grid field-grid--3">' + feld('ordner.webseite', 'Ordner Webseite', { vor: '/', spell: false }) + feld('ordner.portal', 'Ordner Portal', { vor: '/', spell: false }) + feld('ordner.privat', 'Privater Ordner', { vor: '/', spell: false }) + '</div>'
          + feld('php', 'PHP-Version beim Hoster', { optionen: [['8.4', 'PHP 8.4'], ['8.3', 'PHP 8.3 (empfohlen)'], ['8.2', 'PHP 8.2'], ['8.1', 'PHP 8.1 (Minimum)']] })
          + '</div></details>';
      },
      pruefen: function () {
        var d = S.d, f = [];
        d.domain = String(d.domain).trim().toLowerCase().replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/.*$/, '');
        if (!/^[a-z0-9äöü]([a-z0-9äöü-]*[a-z0-9äöü])?(\.[a-z0-9-]+)*\.[a-z]{2,}$/.test(d.domain)) f.push({ feld: 'domain', text: 'Bitte eine Domain wie „ihre-domain.de“ eintragen.' });
        if (!/^[a-z0-9-]+$/.test(d.sub)) f.push({ feld: 'sub', text: 'Nur Kleinbuchstaben, Ziffern und Bindestriche.' });
        ['webseite', 'portal', 'privat'].forEach(function (k) {
          if (!/^[a-z0-9_-]+$/.test(d.ordner[k])) f.push({ feld: 'ordner.' + k, text: 'Nur Kleinbuchstaben, Ziffern, - und _.' });
        });
        if (new Set([d.ordner.webseite, d.ordner.portal, d.ordner.privat]).size < 3) f.push({ feld: 'ordner.privat', text: 'Die drei Ordner brauchen verschiedene Namen.' });
        return f;
      }
    },
    {
      id: 'mail', titel: 'E-Mail & Formular',
      zeichnen: function () {
        var d = S.d, A = window.WSErzeugen.abgeleitet(d);
        var optionen = d.postfaecher.map(function (p) { return [p.name, p.name + '@' + A.domain]; });
        return kopf('Entwicklung', 'E-Mail-Adressen', 'Welche E-Mail-Adressen soll es geben, z. B. info@…? Eingerichtet werden sie später beim Anbieter — die Anleitung zeigt jeden Klick.')
          + '<div class="list">' + d.postfaecher.map(function (p, i) {
            return '<div class="list-row"><span class="ic" aria-hidden="true">✉</span><div class="meta">'
              + '<div class="input-mit"><input class="input" data-postfach="' + i + '" data-teil="name" value="' + e(p.name) + '" aria-label="Name des Postfachs" spellcheck="false"><span>@' + e(A.domain) + '</span></div>'
              + '<input class="input" style="margin-top:6px;min-height:40px;font-size:14px" data-postfach="' + i + '" data-teil="zweck" value="' + e(p.zweck) + '" placeholder="Wofür?" aria-label="Zweck"></div>'
              + '<button type="button" class="icon-btn" data-postfach-weg="' + i + '" aria-label="Postfach entfernen"' + (d.postfaecher.length < 2 ? ' disabled' : '') + '>✕</button></div>';
          }).join('') + '</div>'
          + '<button type="button" class="btn btn--ghost btn--klein" data-postfach-neu style="margin-bottom:18px">＋ Postfach hinzufügen</button>'
          + '<div class="card"><h3>Kontaktformular</h3><p class="card-hint">Wie sollen Nachrichten von Besuchern bei Ihnen ankommen?</p>'
          + seg('modus', [['server', 'Als E-Mail an mich (empfohlen)'], ['mailto', 'Besucher schreiben selbst'], ['aus', 'Kein Formular']])
          + (d.modus !== 'aus' ? '<div class="field-grid" style="margin-top:14px">' + feld('empfaenger', 'Anfragen gehen an', { optionen: optionen })
            + (d.modus === 'server' ? feld('absender', 'Absender der Mails', { optionen: optionen, hilfe: 'Eine Adresse der eigenen Domain — schützt vor dem Spam-Ordner.' }) : '') + '</div>' : '')
          + (d.modus === 'server' ? '<details style="margin-top:6px"><summary class="field-label" style="cursor:pointer">Technik: Postausgangsserver (SMTP) — für ' + e(A.hoster.name) + ' schon richtig eingestellt</summary>'
            + '<div class="field-grid" style="margin-top:10px">' + feld('smtpHost', 'SMTP-Server', { spell: false }) + feld('smtpPort', 'Port', { optionen: [[465, '465 — SSL/TLS'], [587, '587 — STARTTLS']] }) + '</div>'
            + '<p class="field-hint">Das Passwort des Postfachs tragen Sie erst im Portal ein — es kommt nie in das Paket.</p></details>' : '')
          + '</div>';
      },
      pruefen: function () {
        var f = [], gesehen = {};
        S.d.postfaecher.forEach(function (p, i) {
          p.name = String(p.name).trim().toLowerCase();
          if (!/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/.test(p.name)) f.push({ text: 'Postfach ' + (i + 1) + ': nur Kleinbuchstaben, Ziffern, Punkt, - und _.' });
          if (gesehen[p.name]) f.push({ text: 'Das Postfach „' + p.name + '“ steht doppelt da.' });
          gesehen[p.name] = true;
        });
        return f;
      }
    },
    {
      id: 'module', titel: 'Inhaltsbereiche',
      zeichnen: function () {
        var d = S.d;
        var modul = function (k, titel, text, begriffe) {
          return '<div class="card" style="padding:4px 16px">' + schalter('module.' + k, titel, text)
            + (d.module[k] && begriffe ? '<div class="switch-extra field-grid">' + begriffe + '</div>' : '') + '</div>';
        };
        return kopf('Entwicklung', 'Was soll es auf der Webseite geben?', 'Jeder Bereich bekommt im Verwaltungsbereich eine eigene Seite zum Pflegen. Die Namen können Sie frei wählen — und später jederzeit ändern.')
          + modul('angebote', 'Angebote / Leistungen', 'Einträge mit Beschreibung, Bild und Preisangabe — auch als Auswahl im Formular.',
            feld('begriffe.angebote', 'Heißt (Mehrzahl)') + feld('begriffe.angebot', 'Ein Eintrag heißt'))
          + modul('team', 'Team / Personen', 'Porträts mit Funktion und persönlichem Satz.', feld('begriffe.team', 'Heißt') + feld('begriffe.person', 'Eine Person heißt'))
          + modul('preise', 'Preise', 'Preiskarten und eine Preisliste.')
          + modul('zeiten', 'Öffnungszeiten & Anfahrt', 'Zeiten, telefonische Erreichbarkeit, Tipps zum Weg.', feld('begriffe.zeiten', 'Heißt'))
          + modul('hinweise', 'Hinweise & Urlaub', 'Leiste oder Fenster mit Zeitraum — erscheint und verschwindet von selbst.')
          + modul('formular', 'Kontaktformular', 'Anfragen per E-Mail, ohne Speicherung auf dem Server.')
          + modul('barrierefreiheit', 'Erklärung zur Barrierefreiheit', 'Eigene Seite neben Impressum und Datenschutz.')
          + hinweis('Immer dabei: Startseite mit Sektionen, Medien, Design (inkl. Upload eines Claude Design Systems), Vorschau, Veröffentlichen mit Sicherungen, Benutzer & Rollen, Systemprüfung und Protokoll.');
      },
      pruefen: function () {
        if (!S.d.module.formular) S.d.modus = 'aus';
        else if (S.d.modus === 'aus') S.d.modus = 'server';
        return [];
      }
    },
    {
      id: 'startseite', titel: 'Startseite',
      zeichnen: function () {
        var d = S.d, A = window.WSErzeugen.abgeleitet(d);
        var name = function (t) { return (WS.SEKTIONEN[t].name || t).replace('{angebote}', A.begriffe.angebote).replace('{team}', A.begriffe.team); };
        var vorhanden = {};
        d.sektionen.forEach(function (s) { vorhanden[s.typ] = true; });
        var zeilen = d.sektionen.map(function (s, i) {
          var info = WS.SEKTIONEN[s.typ];
          var aus = info.modul && !d.module[info.modul];
          var menueMoeglich = s.typ !== 'hero' && s.typ !== 'laufband';
          return '<div class="list-row' + (!s.aktiv || aus ? ' list-row--aus' : '') + '"><span class="ic" aria-hidden="true">' + info.zeichen + '</span>'
            + '<div class="meta"><b>' + e(name(s.typ)) + '</b><small>' + e(aus ? 'Bereich ist ausgeschaltet — erscheint nicht' : info.text.replace('{angebote}', A.begriffe.angebote)) + '</small></div>'
            + '<div class="tasten">'
            + (menueMoeglich ? '<button type="button" class="icon-btn" data-sek-menue="' + i + '" aria-pressed="' + !!s.menue + '" title="' + (s.menue ? 'Aus dem Menü nehmen' : 'Im Menü zeigen') + '" aria-label="Im Menü zeigen" style="' + (s.menue ? 'color:var(--accent);border-color:var(--accent-line)' : '') + '">☰</button>' : '')
            + (s.typ === 'kontakt' && d.module.zeiten ? '<button type="button" class="icon-btn" data-zeiten-menue title="' + e(A.begriffe.zeiten) + ' als eigener Menüpunkt" aria-pressed="' + !!d.zeitenMenue + '" style="' + (d.zeitenMenue ? 'color:var(--accent);border-color:var(--accent-line)' : '') + '">◷</button>' : '')
            + '<button type="button" class="icon-btn" data-sek-hoch="' + i + '" aria-label="Nach oben"' + (i === 0 ? ' disabled' : '') + '>↑</button>'
            + '<button type="button" class="icon-btn" data-sek-runter="' + i + '" aria-label="Nach unten"' + (i === d.sektionen.length - 1 ? ' disabled' : '') + '>↓</button>'
            + '<button type="button" class="icon-btn" data-sek-weg="' + i + '" aria-label="Entfernen">✕</button>'
            + '</div></div>';
        }).join('');
        var neu = Object.keys(WS.SEKTIONEN).filter(function (t) {
          var info = WS.SEKTIONEN[t];
          return (!info.einmalig || !vorhanden[t]) && (!info.modul || d.module[info.modul]);
        }).map(function (t) { return '<button type="button" class="chip" data-sek-neu="' + t + '">＋ ' + e(name(t)) + '</button>'; }).join('');
        return kopf('Entwicklung · Baukasten', 'Aufbau der Startseite', 'Die Bausteine stehen von oben nach unten so auf der Seite. Mit <b>☰</b> bekommt ein Bereich einen Eintrag im Menü oben (z. B. „Anfahrt“). Die ' + e(A.begriffe.zeiten) + ' stehen im Kontaktbereich — mit <b>◷</b> bekommen sie trotzdem einen eigenen Menüpunkt. Im Verwaltungsbereich lässt sich alles jederzeit umstellen.')
          + '<div class="list sektionen">' + (zeilen || '<div class="empty"><span class="glyph">▦</span><p class="leise">Noch keine Sektionen.</p></div>') + '</div>'
          + '<p class="field-label">Hinzufügen</p><div class="chips chips--wrap">' + neu + '</div>';
      },
      pruefen: function () { return S.d.sektionen.length ? [] : [{ text: 'Mindestens eine Sektion wird gebraucht.' }]; }
    },
    {
      id: 'design', titel: 'Design',
      zeichnen: function () {
        var d = S.d;
        var hell = d.designBasis === 'aurora-hell';
        var bg = hell ? '#f6f6fb' : '#0e0e16', fl = hell ? '#ffffff' : '#1a1a28', tx = hell ? '#16161f' : '#ececf5', tx2 = hell ? '#55556b' : '#9a9ab0', li = hell ? '#dedee9' : '#2a2a3c';
        var akzente = hell ? [{ farbe: '#0f8f7c', tinte: '#ffffff', name: 'Dunkeltürkis (Aurora Hell)' }].concat(WS.AKZENTE.slice(1).map(function (a) { return a; })) : WS.AKZENTE;
        return kopf('Entwicklung', 'Wie soll die Webseite aussehen?', 'Die Vorlage ist im Aurora-Stil gebaut: ruhig, klar, ein Akzent. Das Portal selbst bleibt immer im Aurora-Design.')
          + '<p class="field-label">Grundton</p>' + seg('designBasis', [['aurora', 'Aurora (dunkel)'], ['aurora-hell', 'Aurora Hell']])
          + '<p class="field-label" style="margin-top:16px">Akzentfarbe — Knöpfe, Links, Hervorhebungen</p>'
          + '<div class="farbwahl">' + akzente.map(function (a) {
            return '<button type="button" class="farbpunkt' + (d.akzent.toLowerCase() === a.farbe ? ' is-active' : '') + '" data-akzent="' + a.farbe + '" data-tinte="' + a.tinte + '" style="background:' + a.farbe + '" title="' + e(a.name) + '" aria-label="' + e(a.name) + '"></button>';
          }).join('') + '<input type="color" class="farbfeld" data-akzent-frei value="' + e(d.akzent) + '" aria-label="Eigene Farbe" title="Eigene Farbe"></div>'
          + '<div class="mini-seite" style="margin-top:16px;background:' + bg + ';color:' + tx + ';border-color:' + li + '">'
          + '<div class="m-kopf" style="border-color:' + li + '">' + (d.logo && d.logo.daten ? '<span style="display:flex;align-items:center;gap:8px"><img src="' + e(d.logo.daten) + '" alt="" style="height:26px;width:auto">' + (d.logoAnzeige === 'beides' ? '<b>' + e(d.name || '') + '</b>' : '') + '</span>' : '<b>' + e(d.name || 'Ihr Name') + '</b>') + '<span class="m-knopf" style="background:' + e(d.akzent) + ';color:' + e(d.akzentTinte) + '">' + e(d.knopf) + '</span></div>'
          + '<p class="m-eyebrow" style="color:' + e(d.akzent) + '">' + e(d.claim || 'Kleinzeile') + '</p>'
          + '<p class="m-titel">' + e(d.heroZeilen[0] || '') + '<br><span style="color:' + e(d.akzent) + '">' + e(d.heroZeilen[1] || '') + '</span></p>'
          + '<p class="m-text" style="color:' + tx2 + '">' + e(d.beschreibung || 'Ein, zwei Sätze Einleitung.') + '</p>'
          + '<div class="m-karte" style="background:' + fl + ';border:1px solid ' + li + '"><b>' + e((d.angeboteListe[0] || ['Eintrag'])[0]) + '</b><br><span style="color:' + tx2 + '">' + e((d.angeboteListe[0] || ['', ''])[1]) + '</span></div></div>'
          + (kontrast(d.akzent, bg) < 3 ? hinweis('Diese Akzentfarbe ist auf dem ' + (hell ? 'hellen' : 'dunklen') + ' Grund schwer lesbar (Kontrast ' + kontrast(d.akzent, bg).toFixed(1).replace('.', ',') + ':1, empfohlen mindestens 3:1). Für Links und hervorgehobene Überschriften lieber eine ' + (hell ? 'dunklere' : 'hellere') + ' Farbe wählen.', true) : '')
          + hinweis('Später im Portal unter <b>Design</b>: weitere Designs anlegen, Farben und Schriften feinjustieren oder ein <b>Claude Design System</b> als ZIP hochladen — die Webseite übernimmt dann dessen Farben, Schriften und Rundungen.');
      },
      pruefen: function () { return []; }
    },
    {
      id: 'texte', titel: 'Texte & Suchmaschinen',
      zeichnen: function () {
        var d = S.d, A = window.WSErzeugen.abgeleitet(d);
        return kopf('Entwicklung', 'Die ersten Texte', 'Damit die Webseite nicht mit Platzhaltern startet. Alles bleibt im Portal änderbar.')
          + '<div class="card"><h3>Kopfbereich</h3>' + feld('heroZeilen.0', 'Große Überschrift, Zeile 1') + feld('heroZeilen.1', 'Zeile 2 (in der Akzentfarbe)')
          + feld('lead', 'Einleitung', { mehrzeilig: true, zeilen: 2 }) + feld('knopf', 'Beschriftung des Hauptknopfs') + '</div>'
          + (d.module.angebote ? '<div class="card"><h3>Ihre wichtigsten ' + e(A.begriffe.angebote) + '</h3><p class="card-hint">Drei reichen für den Start.</p>'
            + d.angeboteListe.map(function (x, i) {
              return '<div class="field-grid">' + feld('angeboteListe.' + i + '.0', A.begriffe.angebot + ' ' + (i + 1)) + feld('angeboteListe.' + i + '.1', 'Kurz beschrieben') + '</div>';
            }).join('') + '</div>' : '')
          + (d.module.zeiten ? '<div class="card"><h3>' + e(A.begriffe.zeiten) + '</h3>'
            + d.zeiten.map(function (z, i) {
              return '<div class="field-grid" style="grid-template-columns:1fr 1fr 38px;align-items:end">' + feld('zeiten.' + i + '.0', 'Tage') + feld('zeiten.' + i + '.1', 'Uhrzeit')
                + '<button type="button" class="icon-btn" data-zeit-weg="' + i + '" aria-label="Zeile entfernen" style="margin-bottom:19px">✕</button></div>';
            }).join('') + '<button type="button" class="btn btn--ghost btn--klein" data-zeit-neu>＋ Zeile</button></div>' : '')
          + '<div class="card"><h3>Google & Teilen</h3>' + feld('seoTitel', 'Seitentitel', { max: 60, hilfe: 'Steht im Browser-Tab und als Überschrift bei Google.' })
          + feld('seoBeschreibung', 'Beschreibung', { mehrzeilig: true, zeilen: 3, max: 160, hilfe: 'Der Text unter dem Google-Treffer.' })
          + hinweis('Suchmaschinen bleiben ausgesperrt, bis Sie im Portal den Schalter umlegen — so taucht keine halbfertige Seite bei Google auf.') + '</div>';
      },
      pruefen: function () { return []; }
    },
    {
      id: 'zugang', titel: 'Portal-Zugang',
      zeichnen: function () {
        return kopf('Entwicklung', 'Wer pflegt die Webseite?', 'Mit diesem Zugang melden Sie sich im Verwaltungsbereich an. Das erste Konto darf alles — weitere Personen können Sie später selbst einladen.')
          + '<div class="card">' + feld('adminAnzeige', 'Ihr Name', { pflicht: true, auto: 'name', platzhalter: 'Vorname Nachname' })
          + feld('adminBenutzer', 'Benutzername', { pflicht: true, muster: '[a-z0-9._-]{3,32}', spell: false, hilfe: 'Kleinbuchstaben, Ziffern, Punkt, Strich — z. B. vorname.nachname.' })
          + '<p class="field-label">Automatisch abmelden nach</p>' + seg('sitzungsdauer', [[30, '30 Min'], [60, '1 Std'], [120, '2 Std'], [240, '4 Std']]) + '</div>'
          + hinweis('Das <b>Passwort</b> vergeben Sie beim ersten Aufruf des Portals auf dem Server — es steht nirgends im Paket. Weitere Personen legen Sie im Portal unter „Benutzer“ an: Rolle „Verwaltung“ (alles) oder „Redaktion“ (nur Inhalte).')
          + '<div class="tile-row"><div class="tile tile--accent"><div class="lbl">Verwaltung</div><div class="val">alle Rechte</div></div>'
          + '<div class="tile tile--rest"><div class="lbl">Redaktion</div><div class="val">Inhalte</div></div>'
          + '<div class="tile tile--ok"><div class="lbl">Anmeldeschutz</div><div class="val">6 Versuche</div></div></div>';
      },
      pruefen: function () {
        var d = S.d, f = [];
        if (!d.adminAnzeige.trim()) f.push({ feld: 'adminAnzeige', text: 'Bitte Ihren Namen eintragen.' });
        if (!/^[a-z0-9._-]{3,32}$/.test(d.adminBenutzer)) f.push({ feld: 'adminBenutzer', text: '3 bis 32 Zeichen: Kleinbuchstaben, Ziffern, Punkt, Strich, Unterstrich.' });
        return f;
      }
    },
    {
      id: 'pruefen', titel: 'Prüfen & erzeugen',
      zeichnen: function () {
        var d = S.d, A = window.WSErzeugen.abgeleitet(d);
        var offen = offenePunkte();
        var gruppe = function (titel, schritt, zeilen) {
          return '<div class="card zusammenfassung"><div class="card-kopf"><h3 style="margin:0">' + e(titel) + '</h3><button type="button" class="verweis" data-gehe="' + schritt + '">Ändern</button></div><dl>'
            + zeilen.filter(function (z) { return z[1] !== '' && z[1] != null; }).map(function (z) { return '<dt>' + e(z[0]) + '</dt><dd>' + e(z[1]) + '</dd>'; }).join('') + '</dl></div>';
        };
        var aktiveSek = d.sektionen.filter(function (s) { var i = WS.SEKTIONEN[s.typ]; return !i.modul || d.module[i.modul]; });
        var uebersprungen = SCHRITTE.filter(function (x) { return S.offen[x.id]; });
        return kopf('Entwicklung · Letzter Schritt', 'Alles richtig?', 'Ein Klick auf „Portal erzeugen“ baut das komplette Paket — offline, auf diesem Rechner.')
          + (uebersprungen.length ? '<div class="card card--accent"><h3>Später ergänzen</h3><p class="card-hint">Diese Schritte haben Sie übersprungen. Das Paket entsteht trotzdem — mit neutralen Platzhaltern, die Sie hier oder später im Verwaltungsbereich ersetzen.</p><div class="list" style="margin:0">'
            + uebersprungen.map(function (x) {
              return '<div class="list-row"><span class="ic" aria-hidden="true">↺</span><div class="meta"><b>' + e(x.titel) + '</b></div><button type="button" class="btn btn--ghost btn--klein" data-gehe="' + SCHRITTE.indexOf(x) + '">Jetzt ergänzen</button></div>';
            }).join('') + '</div></div>' : '')
          + (offen.length ? '<div class="card card--warn"><h3>' + offen.length + ' offene ' + (offen.length === 1 ? 'Punkt' : 'Punkte') + '</h3><p class="card-hint">Kein Hindernis — sie lassen sich auch später im Portal ergänzen, sollten aber vor dem Start erledigt sein.</p>'
            + '<ul style="padding-left:18px;color:var(--text-2);font-size:14px">' + offen.map(function (o) { return '<li>' + e(o) + '</li>'; }).join('') + '</ul></div>' : '')
          + gruppe('Webseite', 1, [['Name', d.name], ['Zusatz', d.claim], ['Art', (WS.BRANCHEN[d.branche] || {}).name], ['Design', (d.designBasis === 'aurora-hell' ? 'Aurora Hell' : 'Aurora') + (d.design === 'eigen' ? ', Akzent ' + d.akzent : '')]])
          + gruppe('Kontakt & Impressum', 2, [['Anschrift', [d.strasse, (d.plz + ' ' + d.ort).trim()].filter(Boolean).join(', ')], ['Telefon', d.telefon], ['E-Mail', d.email], ['Inhaber', d.inhaber], ['Rechtsform', d.rechtsform]])
          + gruppe('Adressen & Hosting', 4, [['Webseite', A.webseite], ['Portal', A.portalUrl], ['Hoster', A.hoster.name], ['Ordner', '/' + A.ordner.webseite + ', /' + A.ordner.portal + ', /' + A.ordner.privat]])
          + gruppe('E-Mail', 5, [['Postfächer', d.postfaecher.map(function (p) { return A.postfach(p.name); }).join(', ')], ['Formular', { server: 'Versand über den Server', mailto: 'Mailprogramm der Besucher', aus: 'kein Formular' }[d.modus]], ['Anfragen an', d.modus !== 'aus' ? A.empfaenger : ''], ['Absender', d.modus === 'server' ? A.absender : '']])
          + gruppe('Portal', 6, [['Bereiche', Object.keys(d.module).filter(function (k) { return d.module[k]; }).map(function (k) { return { angebote: A.begriffe.angebote, team: A.begriffe.team, preise: 'Preise', zeiten: A.begriffe.zeiten, formular: 'Kontaktformular', hinweise: 'Hinweise', barrierefreiheit: 'Barrierefreiheit' }[k]; }).join(', ')], ['Startseite', aktiveSek.length + ' Sektionen'], ['Portal-Name', d.portal], ['Erstes Konto', d.adminBenutzer]])
          + '<div class="card card--accent"><h3>Was entsteht (alles in einer ZIP-Datei)</h3><div class="baum">' + e(A.wurzel) + '/\n'
          + '├─ <b>' + e(A.ordner.webseite) + '/</b>     <i>→ ' + e(A.host) + '</i>\n'
          + '├─ <b>' + e(A.ordner.portal) + '/</b>       <i>→ ' + e(A.portalHost) + '</i>\n'
          + '├─ <b>' + e(A.ordner.privat) + '/</b>       <i>→ ohne Domain (Inhalte, Konten, Designs)</i>\n'
          + '├─ ANLEITUNG.html  <i>Schritt für Schritt online bringen</i>\n'
          + '├─ LIESMICH.txt\n├─ Offline ausprobieren (Mac / Windows)  <i>Doppelklick zum Testen</i>\n└─ projekt-assistent.json  <i>Ihre Antworten</i></div></div>'
          + '<button type="button" class="btn btn--ghost btn--voll" data-offline="' + system() + '" style="margin-bottom:10px">▶ Vorher offline ausprobieren</button>'
          + (window.showDirectoryPicker ? '<button type="button" class="btn btn--ghost btn--voll" data-erzeugen="ordner" style="margin-bottom:10px">In einen Ordner auf diesem Rechner schreiben …</button>' : '')
          + '<button type="button" class="btn btn--leise btn--voll" data-projekt-sichern>Nur die Antworten sichern (projekt-assistent.json)</button>';
      },
      pruefen: function () { return []; }
    }
  ];

  function logoKarte() {
    var d = S.d;
    var hat = d.logo && d.logo.daten;
    return '<div class="card"><h3>Firmenlogo (optional)</h3><p class="card-hint">Erscheint oben links auf jeder Seite. Am besten PNG mit durchsichtigem Hintergrund, etwa 600 Pixel breit. Später jederzeit im Verwaltungsbereich änderbar.</p>'
      + '<div class="logo-feld">'
      + '<span class="logo-vorschau">' + (hat ? '<img src="' + e(d.logo.daten) + '" alt="Ihr Logo">' : '<span class="still klein">kein Logo</span>') + '</span>'
      + '<div class="btn-row" style="flex:1"><label class="btn btn--ghost btn--klein" style="cursor:pointer">' + (hat ? 'Anderes Logo wählen' : 'Logo auswählen')
      + '<input type="file" accept="image/png,image/jpeg,image/webp" data-logo-datei hidden></label>'
      + (hat ? '<button type="button" class="btn btn--leise btn--klein" data-logo-weg>Entfernen</button>' : '') + '</div></div>'
      + (hat ? '<p class="field-label" style="margin-top:14px">Oben links zeigen</p>' + seg('logoAnzeige', [['logo', 'Nur Logo'], ['beides', 'Logo und Name']]) : '')
      + '</div>';
  }

  function logoLaden(datei) {
    if (!/^image\/(png|jpeg|webp)$/.test(datei.type)) return toast('Bitte eine PNG-, JPG- oder WebP-Datei wählen.', true);
    if (datei.size > 1.5 * 1048576) return toast('Das Logo ist größer als 1,5 MB — bitte vorher verkleinern.', true);
    var leser = new FileReader();
    leser.onload = function () {
      S.d.logo = { name: datei.name, daten: String(leser.result) };
      zeichnen(false);
      toast('Logo übernommen');
    };
    leser.readAsDataURL(datei);
  }

  function offenePunkte() {
    var d = S.d, o = [];
    if (!d.name.trim()) o.push('Name der Webseite fehlt — Platzhalter „Meine Webseite“.');
    if (!d.email.trim()) o.push('Öffentliche E-Mail-Adresse fehlt (Pflicht im Impressum).');
    if (!d.domain.trim()) o.push('Domain fehlt — die Anleitung zeigt „ihre-domain.de“ als Platzhalter.');
    if (!d.adminBenutzer) o.push('Benutzername fürs Portal fehlt — wird beim ersten Aufruf gewählt.');
    if (!d.inhaber) o.push('Impressum: Inhaberin/Inhaber bzw. Firma fehlt.');
    if (!d.strasse || !d.plz || !d.ort) o.push('Impressum: Die Anschrift ist unvollständig.');
    if (!d.telefon) o.push('Impressum: Eine Telefonnummer fehlt (schnelle Kontaktaufnahme).');
    if (!d.hosterFirma) o.push('Datenschutz: Name und Anschrift des Hosters fehlen.');
    if (WS.MIT_VERTRETUNG.test(d.rechtsform) && !d.vertreten) o.push('Impressum: „Vertreten durch“ fehlt.');
    if (WS.MIT_REGISTER.test(d.rechtsform) && !d.register) o.push('Impressum: Der Registereintrag fehlt.');
    if (d.reglementiert && (!d.berufsbezeichnung || !d.aufsichtsbehoerde)) o.push('Impressum: Angaben zum reglementierten Beruf sind unvollständig.');
    o.push('Datenschutz: Speicherdauer der Server-Logdateien beim Hoster erfragen (im Portal nachtragen).');
    return o;
  }

  /* ------------------------------------------------------- Oberfläche --- */

  function kopfZeichnen() {
    var phase = S.phase;
    $$('#phasen [data-phase]').forEach(function (s) {
      var p = s.getAttribute('data-phase');
      s.className = (p === phase || (p === 'entwicklung' && phase === 'fertig')) ? 'ist' : ((p === 'entwicklung' && (phase === 'anleitung' || phase === 'abschluss' || S.erzeugt)) ? 'fertig' : '');
    });
    var f = document.getElementById('fortschritt');
    var anteil = null, text = '';
    if (phase === 'entwicklung') { anteil = (S.schritt + 1) / (SCHRITTE.length + 1); text = 'Schritt ' + (S.schritt + 1) + ' von ' + SCHRITTE.length; }
    else if (phase === 'fertig') { anteil = 1; text = 'Entwicklung abgeschlossen'; }
    else if (phase === 'anleitung') { var n = window.WSAnleitung.schritte(S.d).length; anteil = (S.anleitung + 1) / n; text = 'Anleitung ' + (S.anleitung + 1) + ' von ' + n; }
    f.hidden = anteil === null;
    if (anteil !== null) {
      document.getElementById('fortschritt-balken').style.width = Math.round(anteil * 100) + '%';
      document.getElementById('fortschritt-zahl').textContent = text;
      $('.fortschritt-leiste').setAttribute('aria-valuenow', String(Math.round(anteil * 100)));
    }
    document.getElementById('kopf-zusatz').textContent = S.d.name ? S.d.name : 'Webseite mit Adminportal erstellen';
    document.body.classList.toggle('voll', phase === 'start' || phase === 'abschluss');
  }

  function leisteZeichnen(teile) {
    leiste.innerHTML = teile.join('');
  }

  function zeichnen(fokus) {
    ableiten();
    kopfZeichnen();
    view.className = 'view' + (S.phase === 'anleitung' ? ' view--weit' : '');
    if (S.phase === 'start') startZeichnen();
    else if (S.phase === 'entwicklung') entwicklungZeichnen();
    else if (S.phase === 'fertig') fertigZeichnen();
    else if (S.phase === 'anleitung') anleitungZeichnen();
    else abschlussZeichnen();
    zaehlerAktualisieren();
    if (fokus !== false) { window.scrollTo(0, 0); view.focus({ preventScroll: true }); }
    speichern();
  }

  function startZeichnen() {
    var gespeichert = hatGespeichert() && (S.d.name || S.schritt > 0 || S.erzeugt);
    view.innerHTML = '<div class="card card--solo willkommen">'
      + '<div class="zeichen" aria-hidden="true">◈</div>'
      + '<p class="eyebrow">Portal-Assistent</p><h1 class="title">Webseite mit eigenem Adminportal</h1>'
      + '<p class="lead">Der Assistent stellt die wichtigen Fragen und baut daraus ein vollständiges Paket: eine Webseite nach dem Baukastenprinzip und ein Portal, in dem Sie Inhalte, Sektionen, Medien, Hinweise, Design, Benutzer und E-Mail-Versand selbst verwalten.</p>'
      + '<p class="leise klein">Sie brauchen keine Programmierkenntnisse. Was Sie gerade nicht wissen, lassen Sie mit <b>„Später ergänzen“</b> einfach aus.</p>'
      + '<div class="phase-karten"><div class="phase-karte"><span class="nr">1</span><b>Entwicklung</b><span class="leise klein">Fragen beantworten, Paket erzeugen. Komplett offline — nichts verlässt diesen Rechner.</span></div>'
      + '<div class="phase-karte"><span class="nr">2</span><b>Implementierung</b><span class="leise klein">Anleitung mit Beispiel-Bildschirmen: Domain, SSL, Postfächer, SFTP-Upload, Einrichtung — Schritt für Schritt.</span></div></div>'
      + (gespeichert ? '<div class="list"><div class="list-row"><span class="ic" aria-hidden="true">↺</span><div class="meta"><b>' + e(S.d.name || 'Angefangenes Projekt') + '</b><small>'
        + (S.erzeugt ? 'Paket erzeugt' + (S.anleitung ? ' · Anleitung bei Schritt ' + (S.anleitung + 1) : '') : 'Entwicklung bei Schritt ' + (S.schritt + 1)) + '</small></div>'
        + '<button type="button" class="btn btn--ghost btn--klein" data-fortsetzen>Fortsetzen</button></div></div>' : '')
      + '<div class="btn-row"><button type="button" class="btn btn--ghost" data-projekt-laden>Projektdatei laden</button>'
      + (gespeichert ? '<button type="button" class="btn btn--danger" data-neu-beginnen>Neu beginnen</button>' : '') + '</div>'
      + '</div>'
      + '<p class="still klein" style="text-align:center">Die Webseite und das Portal laufen auf jedem Webspace mit PHP 8.1 — z. B. bei STRATO, IONOS, ALL-INKL oder Hetzner.</p>';
    leisteZeichnen(['<span class="leise klein leiste-info">Dauer: etwa 10 Minuten</span>',
      '<button type="button" class="btn btn--primary" data-los>' + (gespeichert ? 'Neues Projekt starten' : 'Los geht’s') + '</button>']);
  }

  function entwicklungZeichnen() {
    var s = SCHRITTE[S.schritt];
    view.innerHTML = (S.offen[s.id] ? hinweis('Diesen Schritt hatten Sie übersprungen. Ergänzen Sie jetzt, was Sie wissen — oder lassen Sie ihn weiter offen.') : '') + s.zeichnen();
    var letzter = S.schritt === SCHRITTE.length - 1;
    leisteZeichnen([
      '<button type="button" class="btn btn--ghost" data-zurueck>' + (S.schritt === 0 ? 'Start' : 'Zurück') + '</button>',
      '<span class="leiste-info" aria-hidden="true">' + e(s.titel) + '<br>automatisch gespeichert</span>',
      letzter ? '' : '<button type="button" class="btn btn--leise" data-ueberspringen title="Diesen Schritt jetzt auslassen und später ergänzen">Später ergänzen</button>',
      '<button type="button" class="btn btn--primary" data-weiter>' + (letzter ? 'Portal erzeugen' : 'Weiter') + '</button>'
    ]);
  }

  function fertigZeichnen() {
    var d = S.d, A = window.WSErzeugen.abgeleitet(d);
    var bericht = S.bericht || {};
    view.innerHTML = kopf('Entwicklung abgeschlossen', 'Das Portal ist erzeugt ✓', 'Das Paket <span class="mono">' + e(A.wurzel) + '</span> ist fertig' + (bericht.art === 'ordner' ? ' und liegt im gewählten Ordner.' : ' und wurde heruntergeladen.'))
      + '<div class="tile-row"><div class="tile tile--ok"><div class="lbl">Dateien</div><div class="val">' + (bericht.dateien || '–') + '</div></div>'
      + '<div class="tile tile--accent"><div class="lbl">Sektionen</div><div class="val">' + d.sektionen.length + '</div></div>'
      + '<div class="tile tile--rest"><div class="lbl">Postfächer</div><div class="val">' + d.postfaecher.length + '</div></div></div>'
      + offlineKarte()
      + '<div class="card card--accent"><h3>Wie geht es weiter?</h3><p class="card-hint" style="margin:0">Die <b>Anleitung</b> führt Sie Schritt für Schritt durch alles, was es braucht, damit Webseite und Portal bei <b>' + e(A.hoster.name) + '</b> online gehen: Domain, Subdomain, SSL, PHP, Postfächer, Server-Daten, SFTP-Upload, Einrichtung und E-Mail-Versand. Nach jedem Schritt geht es mit „Weiter“ zum nächsten.</p></div>'
      + '<div class="btn-row"><button type="button" class="btn btn--ghost" data-erzeugen="zip">Paket noch einmal herunterladen</button>'
      + '<button type="button" class="btn btn--ghost" data-anleitung-datei>ANLEITUNG.html speichern</button></div>';
    leisteZeichnen([
      '<button type="button" class="btn btn--ghost" data-abschliessen>Installation abschließen</button>',
      '<button type="button" class="btn btn--primary" data-anleitung-start>Weiter mit der Anleitung</button>'
    ]);
  }

  function anleitungZeichnen() {
    var liste = window.WSAnleitung.schritte(S.d);
    if (S.anleitung >= liste.length) S.anleitung = liste.length - 1;
    var s = liste[S.anleitung];
    var erledigt = !!S.erledigt[S.anleitung];
    var anzahlErledigt = Object.keys(S.erledigt).filter(function (k) { return S.erledigt[k]; }).length;
    view.innerHTML = window.WSAnleitung.schrittHtml(s, S.anleitung + 1, liste.length, S.d)
      + '<label class="erledigt"><input type="checkbox" data-erledigt' + (erledigt ? ' checked' : '') + '><span><b>' + e(s.erledigt || 'Erledigt') + '</b><br><span class="still klein">Abhaken und mit „Weiter“ zum nächsten Schritt.</span></span></label>'
      + '<details class="card"><summary class="card-titel" style="cursor:pointer">Alle Schritte (' + anzahlErledigt + ' von ' + liste.length + ' erledigt)</summary><div class="a-uebersicht" style="margin-top:12px">'
      + liste.map(function (x, i) {
        return '<button type="button" data-anleitung-gehe="' + i + '" class="' + (i === S.anleitung ? 'ist' : '') + (S.erledigt[i] ? ' ok' : '') + '"><span class="n">' + (S.erledigt[i] ? '✓' : i + 1) + '</span><span>' + e(x.titel) + '</span></button>';
      }).join('') + '</div></details>'
      + '<div class="btn-row"><button type="button" class="btn btn--leise" data-anleitung-ende>Anleitung beenden</button></div>';
    var letzter = S.anleitung === liste.length - 1;
    leisteZeichnen([
      '<button type="button" class="btn btn--ghost" data-zurueck-anleitung>Zurück</button>',
      '<span class="leiste-info" aria-hidden="true">' + e(s.titel) + '</span>',
      '<button type="button" class="btn btn--primary" data-weiter-anleitung>' + (letzter ? 'Abschließen' : 'Weiter') + '</button>'
    ]);
  }

  function abschlussZeichnen() {
    var d = S.d, A = window.WSErzeugen.abgeleitet(d);
    var liste = window.WSAnleitung.schritte(d);
    var anzahl = Object.keys(S.erledigt).filter(function (k) { return S.erledigt[k]; }).length;
    var mitAnleitung = S.anleitungGesehen;
    view.innerHTML = '<div class="card card--solo willkommen"><div class="zeichen" aria-hidden="true">✓</div>'
      + '<p class="eyebrow">' + (mitAnleitung ? 'Implementierung' : 'Installation') + ' abgeschlossen</p>'
      + '<h1 class="title">' + (mitAnleitung && anzahl === liste.length ? e(d.name) + ' ist online' : 'Fertig') + '</h1>'
      + '<p class="lead">' + (mitAnleitung
        ? anzahl + ' von ' + liste.length + ' Schritten der Anleitung sind abgehakt.' + (anzahl < liste.length ? ' Offene Schritte finden Sie jederzeit wieder — über „Anleitung öffnen“ oder in ANLEITUNG.html im Paket.' : '')
        : 'Das Paket ist erzeugt. Die Anleitung zum Online-Bringen liegt als <span class="mono">ANLEITUNG.html</span> im Paket und lässt sich hier jederzeit wieder öffnen.') + '</p>'
      + '<div class="list"><div class="list-row"><span class="ic">↗</span><div class="meta"><b>Webseite</b><small class="mono">' + e(A.webseite) + '</small></div></div>'
      + '<div class="list-row"><span class="ic">◈</span><div class="meta"><b>Portal</b><small class="mono">' + e(A.portalUrl) + '</small></div></div>'
      + '<div class="list-row"><span class="ic">☺</span><div class="meta"><b>Erstes Konto</b><small class="mono">' + e(d.adminBenutzer) + '</small></div></div></div>'
      + '<div class="btn-row"><button type="button" class="btn btn--ghost" data-anleitung-start>Anleitung öffnen</button><button type="button" class="btn btn--ghost" data-zur-entwicklung>Antworten ändern</button></div>'
      + '</div>';
    leisteZeichnen(['<button type="button" class="btn btn--leise" data-neu-beginnen>Neues Projekt</button>',
      '<button type="button" class="btn btn--primary" data-erzeugen="zip">Paket herunterladen</button>']);
  }

  function system() {
    var p = ((navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || navigator.userAgent || '').toLowerCase();
    return p.indexOf('win') >= 0 ? 'windows' : 'mac';
  }

  function offlineKarte() {
    var sys = system();
    var anders = sys === 'windows' ? 'mac' : 'windows';
    return '<div class="card"><h3>▶ Offline ausprobieren — ohne Internet, ohne Befehle</h3>'
      + '<p class="card-hint">Probieren Sie Webseite und Verwaltungsbereich auf diesem Rechner aus, bevor irgendetwas online geht. Der Knopf lädt ein kleines Startprogramm herunter — ein Doppelklick darauf genügt.</p>'
      + '<button type="button" class="btn btn--ghost btn--voll" data-offline="' + sys + '">▶ Offline ausprobieren (' + (sys === 'windows' ? 'Windows' : 'Mac') + ')</button>'
      + '<p class="still klein" style="margin:8px 0 0;text-align:center">Anderer Rechner? <button type="button" class="verweis" data-offline="' + anders + '">Startprogramm für ' + (anders === 'windows' ? 'Windows' : 'Mac') + '</button></p></div>';
  }

  function offlineDialog(sys, dateiname, startdatei) {
    var mac = sys !== 'windows';
    var d = document.createElement('div');
    d.className = 'dialog';
    d.setAttribute('role', 'dialog');
    d.setAttribute('aria-modal', 'true');
    var einstellungen = 'x-apple.systempreferences:com.apple.preference.security?General';
    d.innerHTML = '<div class="card card--solo" style="max-width:540px;max-height:calc(100dvh - 32px);overflow-y:auto"><p class="eyebrow">Offline ausprobieren</p><h3 style="font-size:20px">So geht’s</h3>'
      + '<ol class="a-schritte" style="margin-top:14px">'
      + (mac
        ? '<li>Im Ordner „Downloads“ <b>' + e(dateiname) + '</b> doppelklicken (Safari macht das oft schon selbst). Daneben erscheint <b>' + e(startdatei) + '</b>.</li>'
          + '<li><b>' + e(startdatei) + '</b> doppelklicken. Beim ersten Mal meldet macOS „… nicht geöffnet“ — dort auf <b>„Fertig“</b> klicken (nicht auf „In den Papierkorb legen“). Das ist normal: Die Datei stammt nicht aus dem App Store.</li>'
          + '<li>Jetzt einmal freigeben: <a href="' + einstellungen + '"><b>Datenschutz &amp; Sicherheit öffnen</b></a> (oder Systemeinstellungen › Datenschutz &amp; Sicherheit). Ganz nach unten zum Abschnitt „Sicherheit“ scrollen, bei „… wurde blockiert“ auf <b>„Dennoch öffnen“</b> klicken, mit Passwort bzw. Touch ID bestätigen und im nächsten Fenster <b>„Trotzdem öffnen“</b> wählen.</li>'
        : '<li>Im Ordner „Downloads“ <b>' + e(dateiname) + '</b> suchen. Fragt der Browser beim Herunterladen nach, „Behalten“ wählen.</li>'
          + '<li><b>Doppelklicken</b>. Zeigt Windows „Der Computer wurde durch Windows geschützt“: auf „Weitere Informationen“ und dann „Trotzdem ausführen“ klicken.</li>')
      + '<li>Ein kleines Fenster öffnet sich, packt alles aus und startet den Browser mit Ihrem Verwaltungsbereich. Beim ersten Mal legen Sie dort ein Konto an. Zum Beenden das kleine Fenster schließen.' + (mac ? ' Ab jetzt genügt immer ein Doppelklick.' : '') + '</li>'
      + '</ol>'
      + (mac ? '<details class="card" style="background:var(--card-2)"><summary class="card-titel" style="cursor:pointer">Plan B: ohne Freigabe in den Einstellungen</summary>'
        + '<ol class="a-schritte" style="margin-top:12px"><li>Das Programm <b>Terminal</b> öffnen (Programme › Dienstprogramme › Terminal).</li>'
        + '<li>Die Datei <b>' + e(startdatei) + '</b> mit der Maus in das Terminal-Fenster ziehen.</li>'
        + '<li>Die <b>Eingabetaste</b> drücken — fertig, nichts tippen.</li></ol>'
        + '<p class="still klein" style="margin:0">macOS 14 oder älter: Rechtsklick auf die Datei › „Öffnen“ › „Öffnen“ genügt.</p></details>' : '')
      + hinweis('Für den Test wird das kostenlose Programm PHP gebraucht. Fehlt es, bietet das Startprogramm die Installation an (' + (mac ? 'über Homebrew oder MAMP' : 'über Windows selbst oder XAMPP') + ') — einmalig.')
      + '<button type="button" class="btn btn--primary btn--voll" data-dialog-zu>Verstanden</button></div>';
    document.body.appendChild(d);
    $('[data-dialog-zu]', d).focus();
    d.addEventListener('click', function (ev) { if (ev.target === d || ev.target.closest('[data-dialog-zu]')) d.remove(); });
  }

  function offlineStarten(sys) {
    ableiten();
    toast('Startprogramm wird erstellt …');
    window.WSErzeugen.offlineStarter(S.d, sys).then(function (f) {
      herunterladen(f.blob, f.name);
      offlineDialog(sys, f.name, f.datei);
    }).catch(function (err) { toast('Das Startprogramm ließ sich nicht erstellen: ' + err.message, true); });
  }

  function zaehlerAktualisieren() {
    $$('[data-zaehler-fuer]').forEach(function (z) {
      var f = $('[data-feld="' + z.getAttribute('data-zaehler-fuer') + '"]');
      var max = +f.getAttribute('data-max');
      z.textContent = f.value.length + ' / ' + max;
      z.classList.toggle('ueber', f.value.length > max);
    });
  }

  function fehlerZeigen(fehler) {
    $$('.fehlertext').forEach(function (x) { x.remove(); });
    $$('.input.falsch').forEach(function (x) { x.classList.remove('falsch'); });
    fehler.forEach(function (f) {
      if (!f.feld) return;
      var huelle = $('[data-feldhuelle="' + f.feld + '"]');
      if (!huelle) return;
      var eingabe = $('[data-feld]', huelle);
      if (eingabe) eingabe.classList.add('falsch');
      huelle.insertAdjacentHTML('beforeend', '<p class="fehlertext" role="alert">' + e(f.text) + '</p>');
    });
    var erster = $('.input.falsch');
    if (erster) erster.focus();
    toast(fehler[0].text, true);
  }

  /* ---------------------------------------------------------- Erzeugen --- */

  function herunterladen(blob, name) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 4000);
  }

  function erzeugen(art) {
    ableiten();
    var paket;
    try { paket = window.WSErzeugen.erzeugen(S.d); }
    catch (err) { toast('Das Paket ließ sich nicht erzeugen: ' + err.message, true); return; }

    if (art === 'ordner' && window.showDirectoryPicker) {
      window.showDirectoryPicker({ mode: 'readwrite' }).then(function (wurzel) {
        return paket.dateien.reduce(function (kette, datei) {
          return kette.then(function () {
            var teile = datei.pfad.split('/');
            var name = teile.pop();
            return teile.reduce(function (p, ordner) {
              return p.then(function (h) { return h.getDirectoryHandle(ordner, { create: true }); });
            }, Promise.resolve(wurzel)).then(function (ordner) {
              return ordner.getFileHandle(name, { create: true });
            }).then(function (h) { return h.createWritable(); }).then(function (w) {
              return w.write(datei.inhalt).then(function () { return w.close(); });
            });
          });
        }, Promise.resolve());
      }).then(function () {
        fertig('ordner', paket.dateien.length);
      }).catch(function (err) {
        if (err && err.name === 'AbortError') return;
        toast('Schreiben in den Ordner hat nicht geklappt — bitte die ZIP-Datei nehmen.', true);
      });
      return;
    }
    herunterladen(window.zipErstellen(paket.dateien), paket.wurzel + '.zip');
    fertig('zip', paket.dateien.length);
  }

  function fertig(art, anzahl) {
    S.erzeugt = true;
    S.bericht = { art: art, dateien: anzahl, zeit: new Date().toISOString() };
    if (S.phase !== 'abschluss') S.phase = 'fertig';
    toast(art === 'zip' ? 'Paket heruntergeladen' : 'Paket geschrieben');
    zeichnen();
  }

  /* -------------------------------------------------------- Ereignisse --- */

  document.addEventListener('input', function (ev) {
    var t = ev.target;
    if (t.matches('[data-feld]')) {
      var pfad = t.getAttribute('data-feld');
      var wert = t.type === 'checkbox' ? t.checked : t.value;
      if (/^(smtpPort|sitzungsdauer)$/.test(pfad)) wert = +wert;
      setzen(S.d, pfad, wert);
      var basis = pfad.split('.')[0];
      if (['portal', 'kuerzel', 'telefonLink', 'adminBenutzer', 'seoTitel', 'seoBeschreibung', 'hosterFirma'].indexOf(basis) >= 0) S.d._manuell[basis] = true;
      if (basis === 'smtpHost' || basis === 'smtpPort') S.d._manuell.smtp = true;
      t.classList.remove('falsch');
      var alt = t.closest('[data-feldhuelle]') && $('.fehlertext', t.closest('[data-feldhuelle]'));
      if (alt) alt.remove();
      ableiten();
      // abgeleitete Felder im Formular mitziehen, ohne neu zu zeichnen
      ['portal', 'kuerzel', 'telefonLink', 'adminBenutzer', 'seoTitel', 'seoBeschreibung'].forEach(function (k) {
        var f = $('[data-feld="' + k + '"]');
        if (f && f !== t && !S.d._manuell[k]) f.value = S.d[k];
      });
      zaehlerAktualisieren();
      kopfZeichnen();
      speichern();
      // Diese Felder verändern den Aufbau der Seite
      if (t.type === 'checkbox' || t.tagName === 'SELECT' && /^(rechtsform|modus)$/.test(pfad)) zeichnen(false);
      if (pfad === 'domain' && S.phase === 'entwicklung') domainTexte();
      return;
    }
    if (t.matches('[data-postfach]')) {
      S.d.postfaecher[+t.getAttribute('data-postfach')][t.getAttribute('data-teil')] = t.value;
      speichern();
      return;
    }
    if (t.matches('[data-akzent-frei]')) {
      akzentSetzen(t.value);
    }
  });

  document.addEventListener('change', function (ev) {
    var t = ev.target;
    if (t.matches('[data-erledigt]')) {
      S.erledigt[S.anleitung] = t.checked;
      speichern();
      if (t.checked) toast('Erledigt ✓');
    }
    if (t.matches('[data-postfach]')) zeichnen(false);
    if (t.matches('[data-akzent-frei]')) zeichnen(false);
    if (t.id === 'projektdatei' && t.files.length) projektLaden(t.files[0]);
    if (t.matches('[data-logo-datei]') && t.files.length) logoLaden(t.files[0]);
  });

  function domainTexte() {
    // Nur die abhängigen Beschriftungen aktualisieren, damit der Fokus im Feld bleibt
    var A = window.WSErzeugen.abgeleitet(S.d);
    var knoepfe = $$('[data-seg="www"]');
    if (knoepfe[0]) knoepfe[0].textContent = 'www.' + A.domain;
    if (knoepfe[1]) knoepfe[1].textContent = A.domain;
    var nach = $('[data-feldhuelle="sub"] .input-mit span');
    if (nach) nach.textContent = '.' + A.domain;
    var hilfe = $('[data-feldhuelle="sub"] .field-hint');
    if (hilfe) hilfe.textContent = 'Das Portal läuft unter einer eigenen Adresse: ' + A.portalUrl;
  }

  function helligkeit(hex) {
    var lin = function (c) { c /= 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
    return 0.2126 * lin(parseInt(hex.slice(1, 3), 16)) + 0.7152 * lin(parseInt(hex.slice(3, 5), 16)) + 0.0722 * lin(parseInt(hex.slice(5, 7), 16));
  }
  function kontrast(a, b) {
    var x = helligkeit(a), y = helligkeit(b);
    return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
  }

  function akzentSetzen(farbe, tinte) {
    var d = S.d;
    d.akzent = farbe.toLowerCase();
    if (!tinte) tinte = kontrast(farbe, '#0b0b12') >= kontrast(farbe, '#ffffff') ? '#0b0b12' : '#ffffff';
    d.akzentTinte = tinte;
    ableiten();
    speichern();
  }

  document.addEventListener('click', function (ev) {
    var t = ev.target.closest('button, [data-kopieren]');
    if (!t) return;
    var d = S.d;

    if (t.hasAttribute('data-los')) { S = { phase: 'entwicklung', schritt: 0, anleitung: 0, erledigt: {}, erzeugt: false, offen: {}, d: grunddaten() }; return zeichnen(); }
    if (t.hasAttribute('data-fortsetzen')) { S.phase = S.weiterPhase && S.weiterPhase !== 'start' ? S.weiterPhase : 'entwicklung'; return zeichnen(); }
    if (t.hasAttribute('data-projekt-laden')) return document.getElementById('projektdatei').click();
    if (t.hasAttribute('data-neu-beginnen')) {
      return fragen('Neu beginnen?', 'Alle Antworten und der Stand der Anleitung werden aus diesem Browser gelöscht. Ein bereits heruntergeladenes Paket bleibt davon unberührt.', 'Ja, neu beginnen', function () {
        try { window.localStorage.removeItem(SPEICHER); } catch (err) { /* egal */ }
        S = { phase: 'start', schritt: 0, anleitung: 0, erledigt: {}, erzeugt: false, offen: {}, d: grunddaten() };
        zeichnen();
      });
    }

    if (t.hasAttribute('data-ueberspringen')) {
      var sid = SCHRITTE[S.schritt].id;
      S.offen[sid] = true;
      if (sid === 'branche' && !d.branche) brancheAnwenden('sonstiges');
      S.schritt++;
      toast('Übersprungen — lässt sich später ergänzen');
      return zeichnen();
    }
    if (t.hasAttribute('data-offline')) return offlineStarten(t.getAttribute('data-offline'));
    if (t.hasAttribute('data-weiter')) {
      var fehler = SCHRITTE[S.schritt].pruefen();
      if (fehler.length) return fehlerZeigen(fehler);
      delete S.offen[SCHRITTE[S.schritt].id];
      if (S.schritt === SCHRITTE.length - 1) return erzeugen('zip');
      S.schritt++;
      return zeichnen();
    }
    if (t.hasAttribute('data-zurueck')) {
      if (S.schritt === 0) S.phase = 'start';
      else S.schritt--;
      return zeichnen();
    }
    if (t.hasAttribute('data-gehe')) { S.schritt = +t.getAttribute('data-gehe'); S.phase = 'entwicklung'; return zeichnen(); }
    if (t.hasAttribute('data-branche')) { brancheAnwenden(t.getAttribute('data-branche')); return zeichnen(false); }
    if (t.hasAttribute('data-hoster')) { d.hoster = t.getAttribute('data-hoster'); d._manuell.hosterFirma = false; d._manuell.smtp = false; return zeichnen(false); }
    if (t.hasAttribute('data-seg')) {
      var pfad = t.getAttribute('data-seg'), wert = t.getAttribute('data-wert');
      setzen(d, pfad, wert === 'true' ? true : wert === 'false' ? false : (/^\d+$/.test(wert) ? +wert : wert));
      if (pfad === 'designBasis') akzentSetzen(wert === 'aurora-hell' ? '#0f8f7c' : '#5eead4', wert === 'aurora-hell' ? '#ffffff' : '#04241f');
      return zeichnen(false);
    }
    if (t.hasAttribute('data-akzent')) { akzentSetzen(t.getAttribute('data-akzent'), t.getAttribute('data-tinte')); return zeichnen(false); }

    if (t.hasAttribute('data-postfach-neu')) { d.postfaecher.push({ name: '', zweck: '' }); zeichnen(false); var neu = $$('[data-teil="name"]').pop(); if (neu) neu.focus(); return; }
    if (t.hasAttribute('data-postfach-weg')) { d.postfaecher.splice(+t.getAttribute('data-postfach-weg'), 1); return zeichnen(false); }

    if (t.hasAttribute('data-sek-hoch') || t.hasAttribute('data-sek-runter')) {
      var i = +(t.getAttribute('data-sek-hoch') || t.getAttribute('data-sek-runter'));
      var j = t.hasAttribute('data-sek-hoch') ? i - 1 : i + 1;
      var x = d.sektionen[i]; d.sektionen[i] = d.sektionen[j]; d.sektionen[j] = x;
      zeichnen(false);
      var k = $('[data-sek-' + (t.hasAttribute('data-sek-hoch') ? 'hoch' : 'runter') + '="' + j + '"]');
      if (k && !k.disabled) k.focus();
      return;
    }
    if (t.hasAttribute('data-logo-weg')) { d.logo = null; return zeichnen(false); }
    if (t.hasAttribute('data-zeiten-menue')) { d.zeitenMenue = !d.zeitenMenue; return zeichnen(false); }
    if (t.hasAttribute('data-sek-weg')) { d.sektionen.splice(+t.getAttribute('data-sek-weg'), 1); return zeichnen(false); }
    if (t.hasAttribute('data-sek-menue')) { var s = d.sektionen[+t.getAttribute('data-sek-menue')]; s.menue = !s.menue; return zeichnen(false); }
    if (t.hasAttribute('data-sek-neu')) {
      var typ = t.getAttribute('data-sek-neu');
      var pos = d.sektionen.findIndex(function (s) { return s.typ === 'kontakt'; });
      d.sektionen.splice(typ === 'hero' ? 0 : (pos < 0 || typ === 'anfahrt' ? d.sektionen.length : pos), 0, { typ: typ, aktiv: true, menue: false });
      zeichnen(false);
      return toast('Sektion hinzugefügt');
    }
    if (t.hasAttribute('data-zeit-neu')) { d.zeiten.push(['', '']); return zeichnen(false); }
    if (t.hasAttribute('data-zeit-weg')) { d.zeiten.splice(+t.getAttribute('data-zeit-weg'), 1); return zeichnen(false); }

    if (t.hasAttribute('data-erzeugen')) return erzeugen(t.getAttribute('data-erzeugen'));
    if (t.hasAttribute('data-projekt-sichern')) {
      herunterladen(new Blob([JSON.stringify({ assistent: 1, daten: d, offen: S.offen }, null, 2)], { type: 'application/json' }), 'projekt-assistent.json');
      return toast('Antworten gesichert');
    }
    if (t.hasAttribute('data-anleitung-datei')) {
      herunterladen(new Blob([window.anleitungHtml(d)], { type: 'text/html' }), 'ANLEITUNG.html');
      return toast('Anleitung gespeichert');
    }

    if (t.hasAttribute('data-anleitung-start')) { S.phase = 'anleitung'; S.anleitungGesehen = true; return zeichnen(); }
    if (t.hasAttribute('data-abschliessen')) { S.phase = 'abschluss'; return zeichnen(); }
    if (t.hasAttribute('data-zur-entwicklung')) { S.phase = 'entwicklung'; S.schritt = SCHRITTE.length - 1; return zeichnen(); }
    if (t.hasAttribute('data-weiter-anleitung')) {
      S.erledigt[S.anleitung] = S.erledigt[S.anleitung] || !!($('[data-erledigt]') || {}).checked;
      var n = window.WSAnleitung.schritte(d).length;
      if (S.anleitung >= n - 1) { S.phase = 'abschluss'; return zeichnen(); }
      S.anleitung++;
      return zeichnen();
    }
    if (t.hasAttribute('data-zurueck-anleitung')) {
      if (S.anleitung === 0) S.phase = 'fertig';
      else S.anleitung--;
      return zeichnen();
    }
    if (t.hasAttribute('data-anleitung-gehe')) { S.anleitung = +t.getAttribute('data-anleitung-gehe'); return zeichnen(); }
    if (t.hasAttribute('data-anleitung-ende')) {
      return fragen('Anleitung beenden?', 'Der Stand bleibt gespeichert — über „Anleitung öffnen“ geht es später an derselben Stelle weiter.', 'Beenden', function () { S.phase = 'abschluss'; zeichnen(); });
    }

    if (t.hasAttribute('data-kopieren') && navigator.clipboard) {
      navigator.clipboard.writeText(t.getAttribute('data-kopieren')).then(function () { toast('Kopiert: ' + t.getAttribute('data-kopieren')); });
    }
  });

  function projektLaden(datei) {
    var leser = new FileReader();
    leser.onload = function () {
      try {
        var x = JSON.parse(String(leser.result));
        if (!x || x.assistent !== 1 || !x.daten) throw new Error('keine Projektdatei');
        S = { phase: 'entwicklung', schritt: SCHRITTE.length - 1, anleitung: 0, erledigt: {}, erzeugt: false, offen: x.offen || {}, d: Object.assign(grunddaten(), x.daten) };
        toast('Projekt „' + (S.d.name || '') + '“ geladen');
        zeichnen();
      } catch (err) {
        toast('Das ist keine Projektdatei des Assistenten (projekt-assistent.json).', true);
      }
      document.getElementById('projektdatei').value = '';
    };
    leser.readAsText(datei);
  }

  // Enter in einem einzeiligen Feld = Weiter
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter' && ev.target.matches('input.input') && S.phase === 'entwicklung' && !ev.isComposing) {
      ev.preventDefault();
      var w = $('[data-weiter]');
      if (w) w.click();
    }
  });

  /* ------------------------------------------------------------- Start --- */

  if (!window.VORLAGE) {
    view.innerHTML = '<div class="card card--gefahr"><h3>Die Vorlage fehlt</h3><p class="card-hint">assets/vorlage.js wurde nicht gefunden. Im Projektordner einmal <span class="mono">node werkzeuge/buendeln.mjs</span> ausführen.</p></div>';
    return;
  }
  laden();
  // Immer mit dem Startbildschirm beginnen — „Fortsetzen“ springt an die letzte Stelle
  S.weiterPhase = S.phase !== 'start' ? S.phase : (S.weiterPhase || 'entwicklung');
  S.phase = 'start';
  zeichnen();
})();
