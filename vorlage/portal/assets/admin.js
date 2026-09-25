/* ===========================================================================
   Portal
   Ohne Bibliotheken. Formulare funktionieren auch ohne JavaScript; nur
   Hinzufügen, Sortieren, Kopieren und die Medienauswahl brauchen es.
   =========================================================================== */

(function () {
  'use strict';

  var $ = function (sel, wurzel) { return (wurzel || document).querySelector(sel); };
  var $$ = function (sel, wurzel) { return Array.prototype.slice.call((wurzel || document).querySelectorAll(sel)); };
  var token = function () { var t = $('input[name="token"]'); return t ? t.value : ''; };
  var esc = function (s) {
    return String(s).replace(/[&<>"']/g, function (z) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[z]; });
  };
  var mediumUrl = function (pfad) { return '?seite=medium&pfad=' + encodeURIComponent(pfad); };

  /* ------------------------------------------------------- Auf und zu --- */

  document.addEventListener('click', function (e) {
    var auf = e.target.closest('[data-auf]');
    if (!auf) return;
    var ziel = auf.getAttribute('aria-controls')
      ? document.getElementById(auf.getAttribute('aria-controls'))
      : auf.closest('[data-zeile], .karte').querySelector('.zeile-inhalt, .karte-inhalt');
    if (!ziel) return;
    var offen = auf.getAttribute('aria-expanded') === 'true';
    auf.setAttribute('aria-expanded', offen ? 'false' : 'true');
    ziel.hidden = offen;
  });

  /* Beim Absenden mit Pflichtfeldern in zugeklappten Karten: aufklappen, sonst meldet der Browser nichts Sichtbares */
  document.addEventListener('invalid', function (e) {
    var el = e.target;
    while (el && el !== document) {
      if (el.hidden && (el.classList.contains('zeile-inhalt') || el.classList.contains('karte-inhalt'))) {
        el.hidden = false;
        var knopf = el.parentElement.querySelector('[data-auf]');
        if (knopf) knopf.setAttribute('aria-expanded', 'true');
      }
      el = el.parentElement;
    }
  }, true);

  /* -------------------------------------------------- Menü auf dem Handy --- */

  document.addEventListener('click', function (e) {
    var taste = e.target.closest('[data-navtaste]');
    if (!taste) return;
    var leiste = taste.closest('[data-navigation]');
    var offen = leiste.hasAttribute('data-offen');
    offen ? leiste.removeAttribute('data-offen') : leiste.setAttribute('data-offen', '');
    taste.setAttribute('aria-expanded', offen ? 'false' : 'true');
  });

  /* ------------------------------------------------------------ Listen --- */

  function zeilenVon(liste) {
    return Array.prototype.filter.call(liste.children, function (k) { return k.hasAttribute('data-zeile'); });
  }

  function naechsterIndex(liste) {
    var n = parseInt(liste.dataset.naechste || '', 10);
    if (isNaN(n)) n = zeilenVon(liste).length + 1000;
    liste.dataset.naechste = String(n + 1);
    return n;
  }

  /** Nummern und Feldnamen in der sichtbaren Reihenfolge neu vergeben. */
  function nummerieren(liste, praefix) {
    liste.dataset.praefix = praefix;
    var zeilen = zeilenVon(liste);
    zeilen.forEach(function (zeile, i) {
      var alt = zeile.dataset.praefix;
      var neu = praefix + '[' + i + ']';
      if (alt && alt !== neu) {
        $$('[name]', zeile).forEach(function (f) {
          if (f.name === alt || f.name.indexOf(alt + '[') === 0) f.name = neu + f.name.slice(alt.length);
        });
      }
      zeile.dataset.praefix = neu;
      var nr = zeile.querySelector('[data-nr]');
      if (nr && nr.classList.contains('zeile-nr')) nr.textContent = ('0' + (i + 1)).slice(-2);
      $$('[data-liste]', zeile).forEach(function (unter) {
        if (unter.parentElement.closest('[data-zeile]') === zeile && unter.dataset.feld) {
          nummerieren(unter, neu + '[' + unter.dataset.feld + ']');
        }
      });
    });
    var huelle = liste.closest('.feld');
    var anzahl = huelle && huelle.querySelector(':scope > .listen-kopf [data-anzahl]');
    if (anzahl) anzahl.textContent = zeilen.length;
  }

  function alleNummerieren(wurzel) {
    $$('[data-liste]', wurzel).forEach(function (liste) {
      if (!liste.parentElement.closest('[data-zeile]')) nummerieren(liste, liste.dataset.praefix);
    });
  }

  /** Aktuelle Eingaben in die Attribute schreiben, damit eine Kopie sie mitnimmt. */
  function werteFestschreiben(wurzel) {
    $$('input, textarea, select', wurzel).forEach(function (f) {
      if (f.tagName === 'TEXTAREA') f.textContent = f.value;
      else if (f.tagName === 'SELECT') $$('option', f).forEach(function (o) { o.selected ? o.setAttribute('selected', '') : o.removeAttribute('selected'); });
      else if (f.type === 'checkbox' || f.type === 'radio') f.checked ? f.setAttribute('checked', '') : f.removeAttribute('checked');
      else if (f.type !== 'file') f.setAttribute('value', f.value);
    });
  }

  document.addEventListener('click', function (e) {
    var knopf = e.target.closest('[data-neu], [data-hoch], [data-runter], [data-weg], [data-kopie]');
    if (!knopf) return;

    if (knopf.hasAttribute('data-neu')) {
      var liste = knopf.previousElementSibling;
      if (!liste || !liste.hasAttribute('data-liste')) return;
      var vorlage = liste.querySelector(':scope > [data-vorlage]');
      if (!vorlage) return;
      var marke = liste.dataset.marke || 'p0';
      var html = vorlage.innerHTML
        .split('%' + marke + '%').join(liste.dataset.praefix)
        .split('%i' + marke.slice(1) + '%').join(String(naechsterIndex(liste)));
      var huelle = document.createElement('div');
      huelle.innerHTML = html;
      var zeile = huelle.firstElementChild;
      liste.insertBefore(zeile, vorlage);
      alleNummerieren(document);
      var erstes = zeile.querySelector('input:not([type=hidden]):not([type=checkbox]):not([readonly]), textarea, select');
      if (erstes) erstes.focus();
      schmutzig();
      return;
    }

    var zeile2 = knopf.closest('[data-zeile]');
    if (!zeile2) return;
    var liste2 = zeile2.parentElement;

    if (knopf.hasAttribute('data-weg')) {
      var titel = zeile2.querySelector('[data-zeilentitel]');
      var was = titel && titel.textContent.trim() ? '„' + titel.textContent.trim() + '“' : 'Der Eintrag';
      zurueckhalten(liste2, zeile2, zeile2.nextElementSibling, was);
      zeile2.remove();
    } else if (knopf.hasAttribute('data-kopie')) {
      werteFestschreiben(zeile2);
      var kopie = zeile2.cloneNode(true);
      kopie.dataset.praefix = liste2.dataset.praefix + '[' + naechsterIndex(liste2) + ']';
      // Namen der Kopie an den neuen Platz anpassen
      $$('[name]', kopie).forEach(function (f) {
        if (f.name.indexOf(zeile2.dataset.praefix) === 0) f.name = kopie.dataset.praefix + f.name.slice(zeile2.dataset.praefix.length);
      });
      var kt = kopie.querySelector('[data-zeilentitel]');
      if (kt) kt.textContent = kt.textContent + ' (Kopie)';
      liste2.insertBefore(kopie, zeile2.nextElementSibling);
    } else if (knopf.hasAttribute('data-hoch')) {
      var vorher = zeile2.previousElementSibling;
      if (!vorher || !vorher.hasAttribute('data-zeile')) return;
      liste2.insertBefore(zeile2, vorher);
    } else {
      var danach = zeile2.nextElementSibling;
      if (!danach || !danach.hasAttribute('data-zeile')) return;
      liste2.insertBefore(danach, zeile2);
    }
    alleNummerieren(document);
    knopf.focus && document.contains(knopf) && knopf.focus();
    schmutzig();
  });

  /* ------------------------------------------------------- Rückgängig --- */

  var letztesWeg = null, wegLeiste = null, wegUhr = null;

  function zurueckhalten(liste, zeile, davor, was) {
    letztesWeg = { liste: liste, zeile: zeile, davor: davor };
    if (!wegLeiste) {
      wegLeiste = document.createElement('div');
      wegLeiste.className = 'rueckgaengig';
      wegLeiste.setAttribute('role', 'status');
      wegLeiste.innerHTML = '<span data-text></span>'
        + '<button type="button" class="knopf knopf--hell knopf--klein" data-zurueck>Rückgängig</button>'
        + '<button type="button" class="minitaste" data-wegzu aria-label="Hinweis schließen">✕</button>';
      document.body.appendChild(wegLeiste);
      wegLeiste.addEventListener('click', function (e) {
        if (e.target.closest('[data-zurueck]') && letztesWeg) {
          letztesWeg.liste.insertBefore(letztesWeg.zeile, letztesWeg.davor && letztesWeg.davor.parentElement === letztesWeg.liste ? letztesWeg.davor : letztesWeg.liste.querySelector(':scope > [data-vorlage]'));
          alleNummerieren(document);
          letztesWeg = null;
        }
        if (e.target.closest('[data-zurueck], [data-wegzu]')) wegLeisteZu();
      });
    }
    wegLeiste.querySelector('[data-text]').textContent = was + ' wurde entfernt.';
    wegLeiste.classList.add('rueckgaengig--da');
    window.clearTimeout(wegUhr);
    wegUhr = window.setTimeout(wegLeisteZu, 12000);
  }

  function wegLeisteZu() {
    if (wegLeiste) wegLeiste.classList.remove('rueckgaengig--da');
    window.clearTimeout(wegUhr);
  }

  /* ---------------------------------------------------------- Rückfrage --- */

  /* Eigener Dialog statt window.confirm() — der eingebaute wird in manchen
     eingebetteten Ansichten ungefragt mit „nein“ beantwortet. */
  var frageTafel = null, frageWeiter = null, frageVorher = null;

  function fragen(titel, text, jaText, weiter) {
    if (!frageTafel) {
      frageTafel = document.createElement('div');
      frageTafel.className = 'dialog';
      frageTafel.hidden = true;
      frageTafel.setAttribute('role', 'alertdialog');
      frageTafel.setAttribute('aria-modal', 'true');
      frageTafel.setAttribute('aria-labelledby', 'np-frage-titel');
      frageTafel.innerHTML = '<div class="dialog-tafel dialog-tafel--klein">'
        + '<div class="dialog-kopf"><h2 id="np-frage-titel" data-fragetitel></h2></div>'
        + '<div class="dialog-koerper"><p class="leise" data-fragetext></p></div>'
        + '<div class="dialog-fuss"><button type="button" class="knopf knopf--still" data-nein>Abbrechen</button>'
        + '<button type="button" class="knopf" data-ja></button></div></div>';
      document.body.appendChild(frageTafel);
      frageTafel.addEventListener('click', function (e) {
        if (e.target === frageTafel || e.target.closest('[data-nein]')) frageZu();
        else if (e.target.closest('[data-ja]')) { var w = frageWeiter; frageZu(); if (w) w(); }
      });
    }
    frageVorher = document.activeElement;
    frageTafel.querySelector('[data-fragetitel]').textContent = titel;
    frageTafel.querySelector('[data-fragetext]').textContent = text;
    frageTafel.querySelector('[data-ja]').textContent = jaText;
    frageWeiter = weiter;
    frageTafel.hidden = false;
    document.body.style.overflow = 'hidden';
    frageTafel.querySelector('[data-nein]').focus();
  }

  function frageZu() {
    if (frageTafel) frageTafel.hidden = true;
    frageWeiter = null;
    document.body.style.overflow = '';
    if (frageVorher && frageVorher.focus) frageVorher.focus();
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (frageTafel && !frageTafel.hidden) frageZu();
    else if (medienDialog && !medienDialog.hidden) medienDialogZu();
  });

  /* Zeilenüberschrift mitschreiben, während getippt wird */
  document.addEventListener('input', function (e) {
    var feld = e.target;
    if (!feld.name) return;
    var zeile = feld.closest('[data-zeile]');
    if (!zeile || !zeile.dataset.titelfeld) return;
    var kurz = feld.name.slice((zeile.dataset.praefix || '').length);
    var text = feld.tagName === 'SELECT' && feld.selectedIndex >= 0 ? feld.options[feld.selectedIndex].textContent.trim() : feld.value.trim();
    if (kurz === '[' + zeile.dataset.titelfeld + ']') {
      zeile.querySelector('[data-zeilentitel]').textContent = text || 'Neuer Eintrag';
    } else if (kurz === '[' + zeile.dataset.zusatzfeld + ']') {
      zeile.querySelector('[data-zeilenzusatz]').textContent = text.length > 40 ? text.slice(0, 40) + ' …' : text;
    }
  });

  /* Absenden: erst die Rückfrage, dann die Nummerierung in sichtbarer Reihenfolge */
  document.addEventListener('submit', function (e) {
    var formular = e.target;
    var frage = formular.getAttribute('data-frage');
    if (frage && !formular.hasAttribute('data-bestaetigt')) {
      e.preventDefault();
      fragen(formular.getAttribute('data-titel') || 'Sicher?', frage, formular.getAttribute('data-ja') || 'Ja, weiter', function () {
        formular.setAttribute('data-bestaetigt', '');
        formular.requestSubmit ? formular.requestSubmit() : formular.submit();
      });
      return;
    }
    if (formular.matches('[data-inhaltsformular]')) alleNummerieren(formular);
    var knopf = formular.querySelector('button[type=submit]');
    sauber();
    if (knopf) window.setTimeout(function () { knopf.disabled = true; }, 0);
  });

  /* ------------------------------------------------- Nicht gespeichert --- */

  var offen = false;
  function schmutzig() {
    offen = true;
    $$('[data-ungespeichert]').forEach(function (el) { el.hidden = false; });
  }
  function sauber() { offen = false; }
  ['input', 'change'].forEach(function (art) {
    document.addEventListener(art, function (e) { if (e.target.closest('[data-inhaltsformular]')) schmutzig(); });
  });
  window.addEventListener('beforeunload', function (e) {
    if (!offen) return;
    e.preventDefault();
    e.returnValue = '';
  });

  /* ------------------------------------------------------- Zeichenzähler --- */

  function zaehlerAktualisieren(feld) {
    var max = parseInt(feld.dataset.zaehler, 10);
    var anzeige = feld.nextElementSibling && feld.nextElementSibling.classList.contains('zaehler') ? feld.nextElementSibling : null;
    if (!anzeige) {
      anzeige = document.createElement('span');
      anzeige.className = 'zaehler';
      anzeige.setAttribute('aria-live', 'polite');
      feld.insertAdjacentElement('afterend', anzeige);
    }
    anzeige.textContent = feld.value.length + ' / ' + max + ' Zeichen';
    anzeige.classList.toggle('zaehler--ueber', feld.value.length > max);
  }
  $$('[data-zaehler]').forEach(zaehlerAktualisieren);
  document.addEventListener('input', function (e) { if (e.target.matches('[data-zaehler]')) zaehlerAktualisieren(e.target); });

  /* ------------------------------------------------------- Medienauswahl --- */

  var medienDialog = null, zielFeld = null;
  var ARTEN = { bild: 'Bild', video: 'Video', pdf: 'PDF-Datei' };
  var ANNAHME = { bild: 'image/jpeg,image/png,image/webp,image/gif', video: 'video/mp4,video/webm', pdf: 'application/pdf' };

  function kachelHtml(m) {
    var bild = m.art === 'bild' ? '<img src="' + esc(mediumUrl(m.pfad)) + '" alt="" loading="lazy">' : (m.art === 'video' ? '▶' : 'PDF');
    return '<button type="button" class="wahlkachel" data-waehle="' + esc(m.pfad) + '" data-name="' + esc(m.name.toLowerCase()) + '"'
      + (zielFeld && zielFeld.querySelector('[data-medienpfad]').value === m.pfad ? ' aria-current="true"' : '') + '>'
      + '<span class="wahlkachel-bild">' + bild + '</span><span class="wahlkachel-name">' + esc(m.name) + '</span></button>';
  }

  function medienDialogOeffnen(feld) {
    zielFeld = feld;
    var art = feld.dataset.art || 'bild';
    if (!medienDialog) {
      medienDialog = document.createElement('div');
      medienDialog.className = 'dialog';
      medienDialog.hidden = true;
      medienDialog.setAttribute('role', 'dialog');
      medienDialog.setAttribute('aria-modal', 'true');
      medienDialog.setAttribute('aria-labelledby', 'np-medien-titel');
      document.body.appendChild(medienDialog);

      medienDialog.addEventListener('click', function (e) {
        if (e.target === medienDialog || e.target.closest('[data-zu]')) return medienDialogZu();
        var wahl = e.target.closest('[data-waehle]');
        if (wahl) uebernehmen(wahl.getAttribute('data-waehle'));
      });
      medienDialog.addEventListener('input', function (e) {
        if (!e.target.matches('[data-suche]')) return;
        var q = e.target.value.trim().toLowerCase();
        $$('[data-waehle]', medienDialog).forEach(function (k) { k.hidden = q !== '' && k.dataset.name.indexOf(q) === -1; });
      });
      medienDialog.addEventListener('change', function (e) {
        if (e.target.matches('[data-hochladen]') && e.target.files.length) hochladen(e.target.files[0], e.target);
      });
    }

    var liste = (window.NP_MEDIEN || []).filter(function (m) { return m.art === art; });
    medienDialog.innerHTML = '<div class="dialog-tafel">'
      + '<div class="dialog-kopf"><h2 id="np-medien-titel">' + ARTEN[art] + ' auswählen</h2>'
      + '<button type="button" class="minitaste" data-zu style="margin-left:auto" aria-label="Schließen">✕</button></div>'
      + '<div class="dialog-koerper">'
      + '<div class="dialog-werkzeug">'
      + '<input type="search" class="eingabe" placeholder="Nach Dateinamen suchen" data-suche aria-label="Suchen">'
      + '<label class="knopf knopf--klein">Neue Datei hochladen<input type="file" class="nur-leser" data-hochladen accept="' + ANNAHME[art] + '"></label>'
      + '</div>'
      + '<p class="dialog-status leise" data-status role="status">' + (liste.length ? '' : 'Noch keine Dateien dieser Art — laden Sie oben die erste hoch.') + '</p>'
      + '<div class="wahlgitter" data-gitter>' + liste.map(kachelHtml).join('') + '</div>'
      + '</div>'
      + '<div class="dialog-fuss"><a class="knopf knopf--geist knopf--klein" href="?seite=medien" target="_blank" rel="noopener" style="margin-right:auto">Medienverwaltung ↗</a>'
      + '<button type="button" class="knopf knopf--still knopf--klein" data-zu>Abbrechen</button></div>'
      + '</div>';
    medienDialog.hidden = false;
    document.body.style.overflow = 'hidden';
    var erstes = medienDialog.querySelector('[aria-current="true"], [data-suche]');
    if (erstes) erstes.focus();
  }

  function hochladen(datei, eingabe) {
    var status = medienDialog.querySelector('[data-status]');
    var daten = new FormData();
    daten.append('token', token());
    daten.append('datei', datei);
    status.textContent = '„' + datei.name + '“ wird hochgeladen …';
    eingabe.disabled = true;
    fetch('?seite=medien-api', { method: 'POST', body: daten, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (antwort) {
        eingabe.disabled = false;
        if (!antwort.ok) { status.textContent = antwort.fehler || 'Das Hochladen hat nicht geklappt.'; return; }
        var m = { pfad: antwort.pfad, name: antwort.name, art: antwort.art };
        (window.NP_MEDIEN = window.NP_MEDIEN || []).unshift(m);
        if (m.art !== (zielFeld.dataset.art || 'bild')) {
          status.textContent = 'Hochgeladen, aber das ist kein ' + ARTEN[zielFeld.dataset.art || 'bild'] + ' — hier nicht auswählbar.';
          return;
        }
        uebernehmen(m.pfad);
      })
      .catch(function () {
        eingabe.disabled = false;
        status.textContent = 'Das Hochladen hat nicht geklappt — vermutlich ist die Datei zu groß für den Server.';
      });
  }

  function vorschauSetzen(feld, pfad) {
    var art = feld.dataset.art || 'bild';
    feld.querySelector('[data-vorschau]').innerHTML = !pfad
      ? '<span class="medienfeld-leer" aria-hidden="true"></span>'
      : (art === 'bild' ? '<img src="' + esc(mediumUrl(pfad)) + '" alt="">' : '<span class="medienfeld-typ">' + (art === 'video' ? '▶' : 'PDF') + '</span>');
    var leeren = feld.querySelector('[data-medienleeren]');
    if (leeren) leeren.hidden = !pfad;
    var wahl = feld.querySelector('[data-medienwahl]');
    if (wahl) wahl.textContent = pfad ? 'Ändern' : 'Auswählen';
  }

  function uebernehmen(pfad) {
    if (!zielFeld) return medienDialogZu();
    var eingabe = zielFeld.querySelector('[data-medienpfad]');
    eingabe.value = pfad;
    eingabe.dispatchEvent(new Event('input', { bubbles: true }));
    vorschauSetzen(zielFeld, pfad);
    schmutzig();
    medienDialogZu();
  }

  function medienDialogZu() {
    if (medienDialog) medienDialog.hidden = true;
    document.body.style.overflow = '';
    if (zielFeld) { var k = zielFeld.querySelector('[data-medienwahl]'); if (k) k.focus(); }
    zielFeld = null;
  }

  document.addEventListener('click', function (e) {
    var wahl = e.target.closest('[data-medienwahl]');
    if (wahl) return medienDialogOeffnen(wahl.closest('[data-medienfeld]'));
    var leeren = e.target.closest('[data-medienleeren]');
    if (leeren) {
      var feld = leeren.closest('[data-medienfeld]');
      feld.querySelector('[data-medienpfad]').value = '';
      vorschauSetzen(feld, '');
      schmutzig();
    }
  });

  /* ------------------------------------------------------ Dateien ablegen --- */

  $$('[data-ablage]').forEach(function (ablage) {
    var eingabe = ablage.querySelector('input[type=file]');
    ['dragenter', 'dragover'].forEach(function (n) {
      ablage.addEventListener(n, function (e) { e.preventDefault(); ablage.classList.add('ablegeflaeche--drueber'); });
    });
    ['dragleave', 'drop'].forEach(function (n) {
      ablage.addEventListener(n, function () { ablage.classList.remove('ablegeflaeche--drueber'); });
    });
    ablage.addEventListener('drop', function (e) {
      e.preventDefault();
      if (!e.dataTransfer.files.length) return;
      eingabe.files = e.dataTransfer.files;
      senden(ablage);
    });
    eingabe.addEventListener('change', function () { if (eingabe.files.length) senden(ablage); });
  });

  function senden(ablage) {
    var titel = ablage.querySelector('.ablegeflaeche-titel');
    if (titel) titel.textContent = 'Wird hochgeladen … bitte warten';
    ablage.closest('form').submit();
  }

  /* Ersetzen in der Medienverwaltung: Datei gewählt → gleich absenden */
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-dateiwahl]') && e.target.files.length) e.target.closest('form').submit();
  });


  /* ------------------------------------------------------------ Farben --- */

  /* Farbwähler und Textfeld halten sich gegenseitig auf Stand */
  document.addEventListener('input', function (e) {
    var t = e.target;
    if (t.matches('[data-farbe-fuer]')) {
      var feld = document.getElementById(t.getAttribute('data-farbe-fuer'));
      if (feld) { feld.value = t.value; feld.dispatchEvent(new Event('input', { bubbles: true })); }
    } else if (t.matches('[data-farbe-text]')) {
      var wahl = t.parentElement.querySelector('[data-farbe-fuer]');
      if (wahl && /^#[0-9a-f]{6}$/i.test(t.value.trim())) wahl.value = t.value.trim();
    }
  });

  /* ------------------------------------------------- Design übernehmen --- */

  var importFormular = $('[data-designimport]');
  if (importFormular) {
    var mini = $('[data-minivorschau]', importFormular);
    var aktualisieren = function (zeile) {
      var art = zeile.getAttribute('data-art');
      var eigen = zeile.querySelector('[data-eigen]').value.trim();
      var wahl = zeile.querySelector('[data-wahl]');
      var opt = wahl.options[wahl.selectedIndex];
      var wert = eigen || (opt && opt.getAttribute('data-wert')) || '';
      var muster = zeile.querySelector('[data-muster]');
      var k = zeile.getAttribute('data-zuordnung');
      if (!wert) {
        var standard = (opt && opt.textContent.match(/\((.*)\)\s*$/)) || null;
        wert = standard ? standard[1] : '';
      }
      if (art === 'farbe') muster.style.background = wert;
      else if (art === 'schrift') muster.innerHTML = '<span style="font-family:' + esc(wert) + '">Aa</span>';
      else muster.textContent = wert;
      if (mini && wert) mini.style.setProperty('--m-' + k, wert);
    };
    $$('[data-zuordnung]', importFormular).forEach(function (zeile) {
      zeile.addEventListener('change', function () { aktualisieren(zeile); });
      zeile.addEventListener('input', function () { aktualisieren(zeile); });
    });
  }

  /* ------------------------------------------------------ Kleine Helfer --- */

  document.addEventListener('click', function (e) {
    var einsetzen = e.target.closest('[data-einsetzen]');
    if (einsetzen) {
      var feld = document.getElementById(einsetzen.getAttribute('data-einsetzen'));
      if (feld) { feld.value = einsetzen.textContent.trim(); feld.focus(); }
      return;
    }

    var kopieren = e.target.closest('[data-kopieren]');
    if (kopieren && navigator.clipboard) {
      navigator.clipboard.writeText(kopieren.getAttribute('data-kopieren')).then(function () {
        var alt = kopieren.textContent;
        kopieren.textContent = 'Kopiert';
        setTimeout(function () { kopieren.textContent = alt; }, 1400);
      });
      return;
    }

    var vorschlag = e.target.closest('[data-passwortvorschlag]');
    if (vorschlag) {
      var zeichen = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
      var zufall = new Uint32Array(16);
      crypto.getRandomValues(zufall);
      var pw = Array.prototype.map.call(zufall, function (z) { return zeichen[z % zeichen.length]; }).join('');
      var ziel = vorschlag.parentElement.querySelector('input');
      if (ziel) { ziel.value = pw.replace(/(.{4})(?!$)/g, '$1-'); ziel.focus(); ziel.select(); }
      return;
    }

    var geraet = e.target.closest('[data-geraet]');
    if (geraet) {
      var rahmen = $('[data-vorschau-rahmen]');
      if (rahmen) rahmen.style.width = geraet.getAttribute('data-geraet');
      $$('[data-geraet]').forEach(function (k) { k.setAttribute('aria-pressed', k === geraet ? 'true' : 'false'); });
      return;
    }

    if (e.target.closest('[data-neu-laden]')) {
      var r = $('[data-vorschau-rahmen]');
      if (r) r.src = r.src;
    }
  });
})();
