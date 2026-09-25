/* ===========================================================================
   Beispiel-Bildschirme für die Anleitung — als SVG gezeichnet, mit den
   eigenen Werten (Domain, Postfächer, Benutzer) beschriftet. Nachempfunden,
   nicht abfotografiert: Menüs heißen beim Anbieter evtl. etwas anders.
   Nummern im Bild passen zu den nummerierten Schritten im Text.
   =========================================================================== */

(function () {
  'use strict';

  var W = 800;
  var F = '-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif';
  var M = 'ui-monospace, SF Mono, Menlo, Consolas, monospace';
  var HELL = { bg: '#f4f5f8', flaeche: '#ffffff', linie: '#dfe2ea', text: '#1d2230', text2: '#667085', leise: '#98a2b3', akzent: '#0f9f8a', akzentHell: '#e3f6f2', nav: '#1f2a44', navText: '#c9d3e6' };
  var DUNKEL = { bg: '#0e0e16', flaeche: '#1a1a28', flaeche2: '#22222f', linie: '#2a2a3c', text: '#ececf5', text2: '#9a9ab0', leise: '#6b6b82', akzent: '#5eead4', tinte: '#04241f', ok: '#34d399' };

  function e(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (z) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[z]; }); }
  function t(x, y, text, o) {
    o = o || {};
    return '<text x="' + x + '" y="' + y + '" font-family="' + (o.mono ? M : F) + '" font-size="' + (o.g || 13) + '" fill="' + (o.f || HELL.text) + '"'
      + (o.w ? ' font-weight="' + o.w + '"' : '') + (o.a ? ' text-anchor="' + o.a + '"' : '') + '>' + e(text) + '</text>';
  }
  function r(x, y, w, h, f, o) {
    o = o || {};
    return '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + h + '" rx="' + (o.rx == null ? 6 : o.rx) + '" fill="' + f + '"'
      + (o.s ? ' stroke="' + o.s + '" stroke-width="' + (o.sw || 1) + '"' : '') + (o.d ? ' stroke-dasharray="' + o.d + '"' : '') + '/>';
  }
  function marke(x, y, nr, farbe) {
    return '<g><circle cx="' + x + '" cy="' + y + '" r="12" fill="' + (farbe || HELL.akzent) + '"/>' + t(x, y + 4.5, nr, { f: '#fff', g: 12, w: 700, a: 'middle' }) + '</g>';
  }
  function rahmenMarke(x, y, w, h, farbe) {
    return '<rect x="' + (x - 4) + '" y="' + (y - 4) + '" width="' + (w + 8) + '" height="' + (h + 8) + '" rx="9" fill="none" stroke="' + (farbe || HELL.akzent) + '" stroke-width="2.5"/>';
  }
  function feld(x, y, w, label, wert, o) {
    o = o || {};
    var k = o.dunkel ? DUNKEL : HELL;
    return t(x, y, label, { g: 12, f: k.text2 })
      + r(x, y + 8, w, 34, o.dunkel ? k.flaeche2 : '#fff', { s: o.hervor ? (o.dunkel ? k.akzent : k.akzent) : k.linie, sw: o.hervor ? 2 : 1, rx: o.dunkel ? 10 : 6 })
      + t(x + 12, y + 30, wert, { g: 13.5, f: o.leer ? k.leise : k.text, mono: o.mono });
  }
  function knopf(x, y, w, text, o) {
    o = o || {};
    var k = o.dunkel ? DUNKEL : HELL;
    var voll = o.primaer !== false;
    return r(x, y, w, 36, voll ? k.akzent : (o.dunkel ? k.flaeche2 : '#fff'), { s: voll ? null : k.linie, rx: o.dunkel ? 10 : 6 })
      + t(x + w / 2, y + 23, text, { g: 13, w: 600, a: 'middle', f: voll ? (o.dunkel ? k.tinte : '#fff') : k.text });
  }
  function haken(x, y, farbe) {
    return '<path d="M' + (x - 5) + ' ' + y + ' l3.5 3.5 l7 -7" fill="none" stroke="' + (farbe || '#12a150') + '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>';
  }

  /** Browserfenster mit Adresszeile. */
  function fenster(h, url, inhalt, o) {
    o = o || {};
    var k = o.dunkel ? DUNKEL : HELL;
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + W + ' ' + h + '" role="img" aria-label="' + e(o.label || 'Beispiel-Bildschirm') + '">'
      + r(0, 0, W, h, o.dunkel ? k.bg : k.bg, { rx: 0 })
      + r(0, 0, W, 40, o.dunkel ? '#15151f' : '#e9ebf0', { rx: 0 })
      + '<circle cx="20" cy="20" r="6" fill="#ff5f57"/><circle cx="40" cy="20" r="6" fill="#febc2e"/><circle cx="60" cy="20" r="6" fill="#28c840"/>'
      + r(90, 9, W - 180, 22, o.dunkel ? '#22222f' : '#fff', { rx: 11 })
      + t(W / 2, 24.5, '🔒 ' + url, { g: 12, f: o.dunkel ? k.text2 : HELL.text2, a: 'middle' })
      + inhalt + '</svg>';
  }

  /** Kundenbereich eines Hosters: Seitenleiste + Inhalt. */
  function panel(hoster, nav, aktiv, titel, inhalt, h) {
    var s = r(0, 40, 190, h - 40, HELL.nav, { rx: 0 }) + t(20, 72, hoster, { f: '#fff', g: 15, w: 700 }) + t(20, 90, 'Kundenbereich', { f: HELL.navText, g: 11 });
    nav.forEach(function (n, i) {
      var y = 118 + i * 34;
      if (i === aktiv) s += r(10, y - 20, 170, 30, 'rgba(255,255,255,.12)');
      s += t(24, y, n, { f: i === aktiv ? '#fff' : HELL.navText, g: 13, w: i === aktiv ? 600 : 400 });
    });
    return s + t(214, 78, titel, { g: 19, w: 700 }) + inhalt;
  }

  function tabelle(x, y, w, spalten, zeilen, hervor) {
    var s = r(x, y, w, 34 + zeilen.length * 40, '#fff', { s: HELL.linie, rx: 8 });
    var sx = x;
    spalten.forEach(function (sp) { s += t(sx + 14, y + 22, sp[0], { g: 11.5, f: HELL.text2, w: 600 }); sx += sp[1]; });
    zeilen.forEach(function (z, i) {
      var zy = y + 34 + i * 40;
      if (i === hervor || (Array.isArray(hervor) && hervor.indexOf(i) >= 0)) s += r(x + 1, zy, w - 2, 40, HELL.akzentHell, { rx: 0 });
      s += '<line x1="' + x + '" x2="' + (x + w) + '" y1="' + zy + '" y2="' + zy + '" stroke="' + HELL.linie + '"/>';
      var zx = x;
      z.forEach(function (zelle, j) {
        if (zelle && typeof zelle === 'object') {
          s += r(zx + 12, zy + 10, zelle.b, 20, zelle.f || '#e7f7ee', { rx: 10 }) + t(zx + 12 + zelle.b / 2, zy + 24, zelle.t, { g: 11.5, a: 'middle', f: zelle.c || '#12a150', w: 600 });
        } else {
          s += t(zx + 14, zy + 25, zelle, { g: 13, mono: j === 0 && /\./.test(String(zelle)) });
        }
        zx += spalten[j][1];
      });
    });
    return s;
  }

  /* ------------------------------------------------------------ Bilder --- */

  var B = {};

  B.architektur = function (d, a) {
    var o = a.ordner;
    var box = function (x, y, w, h, titel, unter, farbe, gestrichelt) {
      return r(x, y, w, h, '#fff', { s: farbe || HELL.linie, sw: 2, rx: 12, d: gestrichelt ? '6 5' : null })
        + t(x + 16, y + 28, titel, { g: 14, w: 700 }) + t(x + 16, y + 48, unter, { g: 12, f: HELL.text2 });
    };
    var pfeil = function (x1, y1, x2, y2) {
      return '<line x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '" stroke="' + HELL.akzent + '" stroke-width="2" marker-end="url(#pf)"/>';
    };
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400" role="img" aria-label="Aufbau: Domains, Ordner und Portal">'
      + '<defs><marker id="pf" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto"><path d="M0 0L10 5L0 10z" fill="' + HELL.akzent + '"/></marker></defs>'
      + r(0, 0, 800, 400, HELL.bg, { rx: 0 })
      + t(24, 34, 'So hängt alles zusammen', { g: 16, w: 700 })
      + box(24, 60, 230, 64, a.host, 'Besucherinnen und Besucher', HELL.linie)
      + box(24, 150, 230, 64, a.portalHost, 'Sie im Portal (Anmeldung)', HELL.linie)
      + pfeil(254, 92, 330, 92) + pfeil(254, 182, 330, 182)
      + box(330, 60, 200, 64, '/' + o.webseite, 'fertige Webseite (HTML)', HELL.akzent)
      + box(330, 150, 200, 64, '/' + o.portal, 'Portal (PHP)', HELL.akzent)
      + box(330, 250, 200, 76, '/' + o.privat, 'Inhalte, Konten, Designs,', '#e0a800', true)
      + t(346, 316, 'Generator — ohne Domain!', { g: 12, f: '#a26b00', w: 600 })
      + pfeil(430, 214, 430, 248)
      + '<path d="M530 288 C 600 288, 600 92, 534 92" fill="none" stroke="' + HELL.akzent + '" stroke-width="2" stroke-dasharray="5 4" marker-end="url(#pf)"/>'
      + t(612, 176, '„Veröffentlichen“', { g: 12, f: HELL.akzent, w: 600 }) + t(612, 194, 'schreibt die Webseite', { g: 12, f: HELL.text2 })
      + r(24, 352, 752, 34, '#fff8e5', { rx: 8 }) + t(40, 374, 'Wichtig: Der Ordner /' + o.privat + ' bekommt keine Domain — so ist er aus dem Internet nicht erreichbar.', { g: 12.5, f: '#7a5200' })
      + '</svg>';
  };

  B.paket = function (d, a) {
    var h = 380, hs = a.hoster;
    var kachel = function (x, y, titel, wert, ok) {
      return r(x, y, 170, 78, '#fff', { s: HELL.linie, rx: 10 }) + t(x + 14, y + 26, titel, { g: 12, f: HELL.text2 })
        + t(x + 14, y + 54, wert, { g: 16, w: 700 }) + (ok ? haken(x + 150, y + 22) : '');
    };
    return fenster(h, hs.login ? hs.login.replace(/^https?:\/\//, '') : 'kundenbereich', panel(hs.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit', 'Datenbanken'], 0, 'Paketübersicht',
      kachel(214, 100, 'PHP', '8.1 oder neuer', true) + kachel(398, 100, 'SSL-Zertifikate', 'inklusive', true) + kachel(582, 100, 'E-Mail-Postfächer', 'vorhanden', true)
      + kachel(214, 196, 'SFTP / SSH', 'verfügbar', true) + kachel(398, 196, 'Webspace', 'ab 10 GB', true) + kachel(582, 196, 'Domains', '1 inklusive', true)
      + rahmenMarke(214, 100, 170, 78) + marke(214, 100, 1) + rahmenMarke(214, 196, 170, 78) + marke(214, 196, 2)
      + t(214, 318, 'Beispielhafte Darstellung — der echte Kundenbereich sieht anders aus.', { g: 11.5, f: HELL.leise }), h), { label: 'Paketübersicht im Kundenbereich' });
  };

  B.domain = function (d, a) {
    var h = 360;
    return fenster(h, 'kundenbereich › domains', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 1, 'Domainverwaltung',
      tabelle(214, 100, 562, [['Domain', 230], ['Status', 120], ['Ziel', 212]], [
        [a.domain, { t: 'aktiv', b: 60 }, 'noch nicht festgelegt'],
        ['www.' + a.domain, { t: 'aktiv', b: 60 }, 'noch nicht festgelegt']
      ], [0, 1])
      + marke(214, 134, 1) + knopf(214, 232, 200, 'Domain bestellen', { primaer: false }) + knopf(426, 232, 200, 'Domain umziehen', { primaer: false })
      + marke(214, 232, 2), h), { label: 'Domainverwaltung' });
  };

  B.sftpZugang = function (d, a) {
    var h = 420, s = a.hoster.sftp;
    return fenster(h, 'kundenbereich › hosting › sftp', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 2, 'SFTP / SSH-Zugang',
      feld(214, 110, 270, 'Server', s.host, { mono: true }) + feld(500, 110, 120, 'Port', String(s.port), { mono: true })
      + feld(214, 180, 406, 'Benutzername', s.benutzer.replace('{domain}', a.domain), { mono: true, hervor: true })
      + feld(214, 250, 406, 'Neues Passwort', '••••••••••••••••••', { hervor: true })
      + knopf(214, 316, 170, 'Speichern') + marke(214, 190, 1) + marke(214, 260, 2) + marke(214, 316, 3)
      + t(640, 138, 'Werte notieren —', { g: 12, f: HELL.text2 }) + t(640, 156, 'sie kommen gleich', { g: 12, f: HELL.text2 }) + t(640, 174, 'ins SFTP-Programm.', { g: 12, f: HELL.text2 }), h), { label: 'SFTP-Zugang im Kundenbereich' });
  };

  B.filezilla = function (d, a) {
    var h = 440, s = a.hoster.sftp;
    var inhalt = r(0, 40, W, h - 40, '#eceef3', { rx: 0 })
      + r(60, 64, 680, 352, '#fff', { s: HELL.linie, rx: 10 })
      + t(80, 92, 'Servermanager — Neuer Server „' + a.domain + '“', { g: 15, w: 700 })
      + r(80, 108, 190, 280, '#f6f7f9', { rx: 8 }) + t(96, 134, 'Eigene Server', { g: 12, f: HELL.text2 }) + r(90, 144, 170, 26, HELL.akzentHell, { rx: 6 }) + t(104, 162, a.domain, { g: 12.5 })
      + feld(290, 122, 200, 'Protokoll', 'SFTP – SSH File Transfer Protocol', { hervor: true })
      + feld(290, 188, 300, 'Server', s.host, { mono: true, hervor: true }) + feld(604, 188, 116, 'Port', String(s.port), { mono: true, hervor: true })
      + feld(290, 254, 200, 'Verbindungsart', 'Normal') + feld(504, 254, 216, 'Benutzer', s.benutzer.replace('{domain}', a.domain), { mono: true, hervor: true })
      + feld(290, 320, 200, 'Passwort', '••••••••••••') + knopf(604, 336, 116, 'Verbinden')
      + marke(290, 132, 1) + marke(290, 198, 2) + marke(504, 264, 3) + marke(604, 336, 4);
    return fenster(h, 'FileZilla — Datei › Servermanager', inhalt, { label: 'Servermanager eines SFTP-Programms' });
  };

  B.hochladen = function (d, a) {
    var h = 420, o = a.ordner;
    var spalte = function (x, titel, pfad, eintraege, hervor) {
      var s = r(x, 60, 350, 330, '#fff', { s: HELL.linie, rx: 10 }) + t(x + 16, 86, titel, { g: 13, w: 700 }) + t(x + 16, 106, pfad, { g: 12, f: HELL.text2, mono: true });
      eintraege.forEach(function (n, i) {
        var y = 124 + i * 36;
        if (hervor && hervor.indexOf(i) >= 0) s += r(x + 8, y, 334, 30, HELL.akzentHell, { rx: 6 });
        s += t(x + 20, y + 20, (n.slice(-1) === '/' ? '📁 ' : '📄 ') + n, { g: 13, mono: true });
      });
      return s;
    };
    var inhalt = r(0, 40, W, h - 40, '#eceef3', { rx: 0 })
      + spalte(24, 'Lokal (Ihr Rechner)', '…/' + a.wurzel + '/', [o.webseite + '/', o.portal + '/', o.privat + '/', 'ANLEITUNG.html', 'LIESMICH.txt'], [0, 1, 2])
      + spalte(426, 'Server (' + a.hoster.sftp.host.replace('{domain}', a.domain) + ')', '/', [o.webseite + '/', o.portal + '/', o.privat + '/'], [0, 1, 2])
      + '<path d="M378 180 L 420 180" stroke="' + HELL.akzent + '" stroke-width="3"/><path d="M412 172 L 422 180 L 412 188" fill="none" stroke="' + HELL.akzent + '" stroke-width="3"/>'
      + marke(40, 124, 1) + marke(442, 124, 2)
      + t(40, 340, 'Nur die drei Ordner — ANLEITUNG und LIESMICH bleiben bei Ihnen.', { g: 12, f: HELL.text2 });
    return fenster(h, 'SFTP-Programm — Dateien übertragen', inhalt, { label: 'Dateien per SFTP hochladen' });
  };

  B.ziel = function (d, a, welche) {
    var h = 420, portal = welche === 'portal';
    var name = portal ? a.portalHost : a.host;
    var ordner = '/' + (portal ? a.ordner.portal : a.ordner.webseite);
    return fenster(h, 'kundenbereich › domains › einstellungen', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 1, portal ? 'Subdomain anlegen' : 'Ziel der Domain',
      (portal
        ? feld(214, 110, 250, 'Name der Subdomain', (d.sub || 'portal'), { mono: true, hervor: true }) + t(476, 144, '.' + a.domain, { g: 14, mono: true }) + marke(214, 120, 1)
        : feld(214, 110, 400, 'Domain', name, { mono: true }) + marke(214, 120, 1))
      + t(214, 196, 'Ziel', { g: 12, f: HELL.text2 })
      + '<circle cx="224" cy="222" r="7" fill="none" stroke="' + HELL.linie + '" stroke-width="2"/>' + t(240, 227, 'Weiterleitung auf eine andere Adresse', { g: 13, f: HELL.text2 })
      + '<circle cx="224" cy="252" r="7" fill="none" stroke="' + HELL.akzent + '" stroke-width="2"/><circle cx="224" cy="252" r="3.5" fill="' + HELL.akzent + '"/>'
      + t(240, 257, 'Verzeichnis im Webspace', { g: 13, w: 600 }) + marke(200, 252, 2)
      + feld(240, 272, 300, 'Verzeichnis', ordner, { mono: true, hervor: true }) + marke(556, 297, 3)
      + knopf(214, 340, 180, 'Übernehmen') + marke(214, 340, 4), h), { label: portal ? 'Subdomain für das Portal anlegen' : 'Ziel der Domain festlegen' });
  };

  B.ssl = function (d, a) {
    var h = 380;
    var gruen = { t: 'SSL aktiv', b: 76 };
    return fenster(h, 'kundenbereich › sicherheit › ssl', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 4, 'SSL-Verwaltung',
      tabelle(214, 100, 562, [['Domain', 250], ['Zertifikat', 160], ['Status', 152]], [
        [a.domain, 'inklusive (DV)', gruen],
        ['www.' + a.domain, 'inklusive (DV)', gruen],
        [a.portalHost, 'inklusive (DV)', gruen]
      ], [0, 1, 2])
      + marke(214, 134, 1) + marke(214, 174, 2) + marke(214, 214, 3)
      + t(214, 296, 'Nach dem Zuweisen dauert es meist Minuten, selten einige Stunden, bis https:// funktioniert.', { g: 12, f: HELL.text2 }), h), { label: 'SSL-Zertifikate zuweisen' });
  };

  B.php = function (d, a) {
    var h = 360;
    return fenster(h, 'kundenbereich › hosting › php', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 2, 'PHP-Version',
      feld(214, 110, 300, 'Version für das Paket', 'PHP ' + (d.php || '8.3') + ' (empfohlen)', { hervor: true }) + marke(214, 120, 1)
      + r(214, 164, 300, 108, '#fff', { s: HELL.linie, rx: 6 })
      + ['PHP 8.4', 'PHP ' + (d.php || '8.3'), 'PHP 8.2', 'PHP 7.4 (veraltet)'].map(function (v, i) {
        return (i === 1 ? r(215, 166 + i * 26, 298, 26, HELL.akzentHell, { rx: 0 }) : '') + t(228, 184 + i * 26, v, { g: 13, f: i === 3 ? HELL.leise : HELL.text });
      }).join('')
      + knopf(214, 290, 160, 'Speichern') + marke(214, 290, 2), h), { label: 'PHP-Version einstellen' });
  };

  B.postfach = function (d, a) {
    var h = 420, adresse = a.postfach(d.empfaenger || 'info');
    return fenster(h, 'kundenbereich › e-mail', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 3, 'Postfach anlegen',
      feld(214, 110, 200, 'Adresse', (d.empfaenger || 'info'), { mono: true, hervor: true }) + t(424, 144, '@' + a.domain, { g: 14, mono: true }) + marke(214, 120, 1)
      + feld(214, 180, 330, 'Passwort', '••••••••••••••••••••', { hervor: true }) + marke(214, 190, 2)
      + feld(214, 250, 330, 'Größe des Postfachs', 'maximal') + t(560, 212, 'mind. 12 Zeichen,', { g: 12, f: HELL.text2 }) + t(560, 230, 'aus dem Passwortmanager', { g: 12, f: HELL.text2 })
      + knopf(214, 320, 180, 'Postfach anlegen') + marke(214, 320, 3)
      + t(410, 343, 'Für jedes Postfach wiederholen.', { g: 12, f: HELL.text2 }), h), { label: 'E-Mail-Postfach anlegen: ' + adresse });
  };

  B.mailServer = function (d, a) {
    var h = 330, hs = a.hoster;
    return fenster(h, 'kundenbereich › e-mail › programm einrichten', panel(hs.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 3, 'Server-Daten für E-Mail-Programme',
      tabelle(214, 100, 562, [['Dienst', 110], ['Server', 230], ['Port', 70], ['Verschlüsselung', 152]], [
        ['IMAP (Eingang)', hs.imap.host, String(hs.imap.port), 'SSL/TLS'],
        ['SMTP (Ausgang)', hs.smtp.host, String(hs.smtp.port), 'SSL/TLS'],
        ['SMTP (Ausweich)', hs.smtp.host, String(hs.smtp.port2), 'STARTTLS']
      ], 1)
      + marke(214, 174, 1) + t(214, 280, 'Benutzername ist immer die vollständige E-Mail-Adresse, z. B. ' + (a.absender || a.empfaenger), { g: 12, f: HELL.text2 }), h), { label: 'Server-Daten für E-Mail' });
  };

  B.dns = function (d, a) {
    var h = 380;
    return fenster(h, 'kundenbereich › domains › dns', panel(a.hoster.name, ['Übersicht', 'Domains', 'Hosting', 'E-Mail', 'Sicherheit'], 1, 'DNS-Einträge · ' + a.domain,
      tabelle(214, 100, 562, [['Typ', 70], ['Name', 120], ['Wert', 372]], [
        ['MX', '@', 'vom Anbieter gesetzt'],
        ['TXT', '@', 'v=spf1 … (SPF, meist automatisch)'],
        ['TXT', 'dkim…', 'DKIM-Schlüssel (falls angeboten: aktivieren)'],
        ['TXT', '_dmarc', 'v=DMARC1; p=none; rua=mailto:' + (a.empfaenger || 'postmaster@' + a.domain)]
      ], 3)
      + marke(214, 294, 1), h), { label: 'DNS-Einträge für die E-Mail-Zustellung' });
  };

  /* ---------------------------------------------- Portal (Aurora, dunkel) --- */

  function aurora(h, url, titel, inhalt) {
    var k = DUNKEL;
    var hg = '<defs><radialGradient id="a1" cx="18%" cy="8%" r="45%"><stop offset="0" stop-color="#34d399" stop-opacity=".2"/><stop offset="1" stop-color="#34d399" stop-opacity="0"/></radialGradient>'
      + '<radialGradient id="a2" cx="84%" cy="12%" r="45%"><stop offset="0" stop-color="#8b5cf6" stop-opacity=".2"/><stop offset="1" stop-color="#8b5cf6" stop-opacity="0"/></radialGradient></defs>'
      + '<rect x="0" y="40" width="800" height="' + (h - 40) + '" fill="url(#a1)"/><rect x="0" y="40" width="800" height="' + (h - 40) + '" fill="url(#a2)"/>';
    return fenster(h, url, hg + inhalt.replace('{TITEL}', e(titel)), { dunkel: true, label: titel });
  }

  function portalRahmen(a, aktiv, titel, inhalt) {
    var k = DUNKEL;
    var nav = ['Übersicht', 'Sektionen', 'Vorschau', 'Design', 'Inhalte …', 'Benutzer', 'Einstellungen', 'Systemprüfung'];
    var s = r(0, 40, 180, 380, 'rgba(20,20,32,.8)', { rx: 0 }) + r(14, 56, 30, 30, 'rgba(94,234,212,.12)', { rx: 9, s: 'rgba(94,234,212,.3)' })
      + t(29, 76, (a.kuerzel || 'W'), { f: k.akzent, g: 13, w: 700, a: 'middle' }) + t(52, 70, a.name, { f: k.text, g: 12, w: 600 }) + t(52, 84, 'Portal', { f: k.text2, g: 10.5 });
    nav.forEach(function (n, i) {
      var y = 118 + i * 30;
      if (n === aktiv) s += r(8, y - 18, 164, 26, 'rgba(94,234,212,.12)', { rx: 8 });
      s += t(22, y, n, { f: n === aktiv ? k.akzent : k.text2, g: 12.5 });
    });
    return s + r(180, 40, 620, 44, 'rgba(14,14,22,.8)', { rx: 0 }) + t(200, 67, titel, { f: k.text, g: 14, w: 600 }) + inhalt;
  }

  B.portalEinrichtung = function (d, a) {
    var k = DUNKEL, h = 470;
    return aurora(h, a.portalHost, 'Einrichtung',
      r(150, 60, 500, 392, k.flaeche, { rx: 18, s: k.linie })
      + t(176, 100, 'Einrichtung', { f: k.text, g: 20, w: 600 })
      + t(176, 122, 'Einmalig: das erste Konto anlegen und die Ordner bestätigen.', { f: k.text2, g: 12 })
      + feld(176, 146, 214, 'Ihr Name', d.adminAnzeige || 'Vorname Nachname', { dunkel: true })
      + feld(410, 146, 214, 'Benutzername', d.adminBenutzer || 'vorname.nachname', { dunkel: true, mono: true, hervor: true })
      + feld(176, 212, 214, 'Passwort', '••••••••••••••', { dunkel: true, hervor: true }) + feld(410, 212, 214, 'Passwort wiederholen', '••••••••••••••', { dunkel: true })
      + feld(176, 278, 448, 'Privater Ordner (automatisch gefunden)', '…/' + a.ordner.privat, { dunkel: true, mono: true })
      + feld(176, 336, 448, 'Ordner der Webseite (automatisch gefunden)', '…/' + a.ordner.webseite, { dunkel: true, mono: true })
      + knopf(176, 400, 448, 'Einrichten', { dunkel: true })
      + marke(410, 156, 1, '#0f9f8a') + marke(176, 222, 2, '#0f9f8a') + marke(176, 288, 3, '#0f9f8a') + marke(176, 400, 4, '#0f9f8a'));
  };

  B.portalSmtp = function (d, a) {
    var k = DUNKEL, h = 440, hs = a.hoster;
    return aurora(h, a.portalHost + '/?seite=einstellungen', 'Einstellungen',
      portalRahmen({ name: d.name, kuerzel: d.kuerzel }, 'Einstellungen', 'Einstellungen',
        r(200, 100, 580, 320, k.flaeche, { rx: 16 })
        + t(220, 130, 'E-Mail-Versand des Kontaktformulars', { f: k.text, g: 15, w: 600 })
        + feld(220, 150, 260, 'Postausgangsserver (SMTP)', d.smtpHost || hs.smtp.host, { dunkel: true, mono: true, hervor: true })
        + feld(500, 150, 260, 'Port', (d.smtpPort || hs.smtp.port) + ' — SSL/TLS (empfohlen)', { dunkel: true, hervor: true })
        + feld(220, 216, 260, 'Benutzername (E-Mail-Adresse)', a.absender || a.empfaenger, { dunkel: true, mono: true, hervor: true })
        + feld(500, 216, 260, 'Passwort des Postfachs', '••••••••••••', { dunkel: true, hervor: true })
        + knopf(220, 290, 220, 'Zugangsdaten speichern', { dunkel: true }) + knopf(220, 344, 180, 'Testmail senden', { dunkel: true, primaer: false })
        + marke(220, 160, 1, '#0f9f8a') + marke(500, 160, 2, '#0f9f8a') + marke(220, 226, 3, '#0f9f8a') + marke(500, 226, 4, '#0f9f8a') + marke(220, 290, 5, '#0f9f8a') + marke(220, 344, 6, '#0f9f8a')));
  };

  B.portalDiagnose = function (d, a) {
    var k = DUNKEL, h = 440;
    var punkte = ['PHP-Version', 'Privater Ordner', 'Inhaltsdateien', 'Schreibrechte', 'Privater Ordner nicht im Web', 'Verschlüsselte Verbindung', 'E-Mail-Versand (SMTP)'];
    var zeilen = punkte.map(function (p, i) {
      var y = 150 + i * 36;
      return r(220, y - 16, 22, 22, 'rgba(52,211,153,.14)', { rx: 6, s: 'rgba(52,211,153,.4)' }) + haken(231, y - 5, k.ok)
        + t(256, y, p, { f: k.text, g: 13 }) + t(520, y, i === 5 ? 'HTTPS' : 'in Ordnung', { f: k.text2, g: 12 });
    }).join('');
    return aurora(h, a.portalHost + '/?seite=diagnose', 'Systemprüfung',
      portalRahmen({ name: d.name, kuerzel: d.kuerzel }, 'Systemprüfung', 'Systemprüfung',
        r(200, 96, 580, 36, 'rgba(94,234,212,.12)', { rx: 10, s: 'rgba(94,234,212,.3)' }) + t(216, 119, 'Alles in Ordnung — alle Prüfungen bestanden', { f: k.akzent, g: 13, w: 600 })
        + r(200, 140, 580, 270, k.flaeche, { rx: 16 }) + zeilen));
  };

  B.portalVeroeffentlichen = function (d, a) {
    var k = DUNKEL, h = 420;
    return aurora(h, a.portalHost + '/?seite=veroeffentlichen', 'Veröffentlichen',
      portalRahmen({ name: d.name, kuerzel: d.kuerzel }, 'Übersicht', 'Veröffentlichen',
        r(200, 100, 580, 130, 'rgba(94,234,212,.12)', { rx: 16, s: 'rgba(94,234,212,.3)' })
        + t(220, 128, 'BEREIT', { f: k.akzent, g: 11, w: 700 }) + t(220, 156, '3 Änderungen in 2 Bereichen', { f: k.text, g: 18, w: 600 })
        + knopf(220, 176, 190, 'Jetzt veröffentlichen', { dunkel: true }) + knopf(422, 176, 150, 'Vorschau', { dunkel: true, primaer: false })
        + r(200, 246, 580, 150, k.flaeche, { rx: 16 }) + t(220, 274, 'Webseite neu erzeugen', { f: k.text, g: 15, w: 600 })
        + t(220, 296, 'Beim ersten Mal: schreibt die Webseite aus den Inhalten.', { f: k.text2, g: 12 })
        + knopf(220, 320, 200, 'Webseite neu erzeugen', { dunkel: true, primaer: false })
        + marke(220, 176, 2, '#0f9f8a') + marke(220, 320, 1, '#0f9f8a')));
  };

  B.searchConsole = function (d, a) {
    var h = 340;
    var inhalt = r(0, 40, W, h - 40, '#f8f9fa', { rx: 0 }) + t(40, 84, 'Search Console — Sitemaps', { g: 18, w: 700 })
      + t(40, 110, 'Property: ' + a.webseite, { g: 12.5, f: HELL.text2 })
      + r(40, 130, 720, 110, '#fff', { s: HELL.linie, rx: 10 }) + t(60, 160, 'Neue Sitemap hinzufügen', { g: 14, w: 600 })
      + t(60, 199, a.webseite + '/', { g: 13.5, mono: true, f: HELL.text2 }) + r(60 + (a.webseite.length + 1) * 8.2, 178, 170, 32, '#fff', { s: HELL.akzent, sw: 2 })
      + t(70 + (a.webseite.length + 1) * 8.2, 199, 'sitemap.xml', { g: 13.5, mono: true })
      + knopf(640, 178, 100, 'Senden') + marke(60 + (a.webseite.length + 1) * 8.2, 178, 1) + marke(640, 178, 2);
    return fenster(h, 'search.google.com/search-console', inhalt, { label: 'Sitemap in der Google Search Console einreichen' });
  };

  window.WSBilder = B;
})();
