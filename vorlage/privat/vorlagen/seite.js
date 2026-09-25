/* ===========================================================================
   Webseite — kleines Skript ohne Bibliotheken.
   Die Seite funktioniert auch ohne: Menü, Hinweise, Formularversand, Karte und
   hochzählende Zahlen sind nur Zugaben.
   =========================================================================== */

(function () {
  'use strict';

  var $$ = function (sel, wurzel) { return Array.prototype.slice.call((wurzel || document).querySelectorAll(sel)); };
  var ruhig = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var speicher = {
    lesen: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
    schreiben: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* privat oder gesperrt */ } }
  };

  /* ------------------------------------------------------------- Menü --- */

  var menueknopf = document.querySelector('[data-menueknopf]');
  var menue = document.getElementById('ws-menue');
  if (menueknopf && menue) {
    menueknopf.addEventListener('click', function () {
      var offen = menue.hasAttribute('data-offen');
      offen ? menue.removeAttribute('data-offen') : menue.setAttribute('data-offen', '');
      menueknopf.setAttribute('aria-expanded', offen ? 'false' : 'true');
    });
    menue.addEventListener('click', function (e) {
      if (e.target.closest('a')) { menue.removeAttribute('data-offen'); menueknopf.setAttribute('aria-expanded', 'false'); }
    });
  }

  /* Passt das Menü nicht in eine Zeile, wird es zum Klappmenü — nie zweizeilig */
  var kopf = document.querySelector('[data-kopf]');
  if (kopf && menue) {
    var innen = kopf.firstElementChild;
    var pruefen = function () {
      kopf.classList.remove('ws-kopfleiste--eng');
      if (innen.scrollWidth > innen.clientWidth + 1) kopf.classList.add('ws-kopfleiste--eng');
    };
    pruefen();
    window.addEventListener('resize', pruefen);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(pruefen);
    window.addEventListener('load', pruefen);
  }

  /* --------------------------------------------------------- Hinweise --- */

  function heute() {
    var d = new Date();
    return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
  }

  $$('[data-hinweis]').forEach(function (h) {
    var tag = heute();
    var von = h.getAttribute('data-von') || '';
    var bis = h.getAttribute('data-bis') || '';
    var schluessel = 'ws-hinweis-' + h.getAttribute('data-hinweis');
    var imZeitraum = (!von || von <= tag) && (!bis || bis >= tag);
    var geschlossen = h.hasAttribute('data-einmal') && speicher.lesen(schluessel) === '1';

    if (!imZeitraum || geschlossen) { h.hidden = true; return; }

    if (h.classList.contains('ws-fenster')) {
      window.setTimeout(function () {
        h.hidden = false;
        var zu = h.querySelector('[data-hinweis-zu]');
        if (zu) zu.focus();
      }, parseInt(h.getAttribute('data-verzoegerung') || '1200', 10));
      h.addEventListener('click', function (e) { if (e.target === h) schliessen(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !h.hidden) schliessen(); });
    } else {
      h.hidden = false;
    }

    function schliessen() {
      h.hidden = true;
      if (h.hasAttribute('data-einmal')) speicher.schreiben(schluessel, '1');
    }
    $$('[data-hinweis-zu]', h).forEach(function (k) { k.addEventListener('click', schliessen); });
  });

  /* ---------------------------------------------------- Hintergrundvideo --- */

  $$('[data-video-pause]').forEach(function (knopf) {
    var video = knopf.parentElement.querySelector('video');
    if (!video) return;
    if (ruhig) { video.pause(); knopf.textContent = '▶'; knopf.setAttribute('aria-label', 'Hintergrundvideo abspielen'); }
    knopf.addEventListener('click', function () {
      if (video.paused) { video.play(); knopf.textContent = '❚❚'; knopf.setAttribute('aria-label', 'Hintergrundvideo anhalten'); }
      else { video.pause(); knopf.textContent = '▶'; knopf.setAttribute('aria-label', 'Hintergrundvideo abspielen'); }
    });
  });

  /* ------------------------------------------------------------ Karte --- */

  document.addEventListener('click', function (e) {
    var knopf = e.target.closest('[data-karte-laden]');
    if (!knopf) return;
    var platz = knopf.closest('[data-karte]');
    var rahmen = document.createElement('iframe');
    rahmen.className = 'ws-karte-rahmen';
    rahmen.src = platz.getAttribute('data-karte');
    rahmen.title = platz.getAttribute('data-titel') || 'Karte';
    rahmen.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
    platz.replaceWith(rahmen);
  });

  /* ---------------------------------------------------- Zahlen zählen --- */

  var zahlen = $$('[data-zaehlen]');
  if (zahlen.length && 'IntersectionObserver' in window && !ruhig) {
    var format = new Intl.NumberFormat('de-DE');
    var beobachter = new IntersectionObserver(function (eintraege) {
      eintraege.forEach(function (e) {
        if (!e.isIntersecting) return;
        beobachter.unobserve(e.target);
        var ziel = parseInt(e.target.getAttribute('data-zaehlen'), 10) || 0;
        var start = null;
        function schritt(t) {
          if (!start) start = t;
          var p = Math.min(1, (t - start) / 1200);
          e.target.textContent = format.format(Math.round(ziel * (1 - Math.pow(1 - p, 3))));
          if (p < 1) window.requestAnimationFrame(schritt);
        }
        window.requestAnimationFrame(schritt);
      });
    }, { threshold: 0.4 });
    zahlen.forEach(function (z) { z.textContent = '0'; beobachter.observe(z); });
  }

  /* --------------------------------------------------------- Formular --- */

  $$('[data-formular]').forEach(function (formular) {
    var geladen = Date.now();
    var status = formular.querySelector('[data-status]');
    var melden = function (text, art) { status.textContent = text; status.setAttribute('data-art', art || ''); };

    formular.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = new FormData(formular);
      var feld = function (n) { return String(d.get(n) || '').trim(); };
      $$('[aria-invalid]', formular).forEach(function (f) { f.removeAttribute('aria-invalid'); });

      var fehler = [];
      if (!feld('name')) { fehler.push('Bitte geben Sie Ihren Namen an.'); formular.elements.name.setAttribute('aria-invalid', 'true'); }
      if (!feld('telefon') && !feld('email')) { fehler.push('Bitte geben Sie eine Telefonnummer oder E-Mail-Adresse an.'); formular.elements.telefon.setAttribute('aria-invalid', 'true'); }
      if (feld('email') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(feld('email'))) { fehler.push('Die E-Mail-Adresse sieht nicht vollständig aus.'); formular.elements.email.setAttribute('aria-invalid', 'true'); }
      if (!formular.elements.einwilligung.checked) { fehler.push('Bitte stimmen Sie der Verarbeitung Ihrer Angaben zu.'); }
      if (fehler.length) { melden(fehler.join(' '), 'fehler'); return; }

      if (formular.hasAttribute('data-vorschau')) {
        melden('Vorschau: Hier würde die Anfrage jetzt gesendet.', '');
        return;
      }

      var modus = formular.getAttribute('data-modus');
      if (modus === 'mailto') {
        var text = 'Name: ' + feld('name') + '\nTelefon: ' + (feld('telefon') || '–') + '\nE-Mail: ' + (feld('email') || '–')
          + (feld('angebot') ? '\nAuswahl: ' + feld('angebot') : '') + (feld('zeit') ? '\nWunschzeit: ' + feld('zeit') : '')
          + '\n\n' + feld('nachricht');
        window.location.href = 'mailto:' + formular.getAttribute('data-empfaenger')
          + '?subject=' + encodeURIComponent(formular.getAttribute('data-betreff') + ' – ' + feld('name'))
          + '&body=' + encodeURIComponent(text);
        return;
      }

      var knopf = formular.querySelector('button[type=submit]');
      knopf.disabled = true;
      melden('Wird gesendet …', '');
      var daten = {};
      d.forEach(function (wert, schluessel) { daten[schluessel] = wert; });
      daten.geladen = geladen;
      fetch(formular.getAttribute('data-ziel'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(daten)
      }).then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (antwort) {
          knopf.disabled = false;
          if (antwort.ok) {
            $$('.ws-feld, .ws-feldpaar, .ws-einwilligung, .ws-feld-hinweis, button[type=submit]', formular).forEach(function (el) { el.hidden = true; });
            melden('', '');
            formular.querySelector('[data-erfolg]').hidden = false;
          } else {
            melden(antwort.fehler || 'Das hat leider nicht geklappt. Bitte rufen Sie uns an.', 'fehler');
          }
        })
        .catch(function () {
          knopf.disabled = false;
          melden('Keine Verbindung — bitte versuchen Sie es noch einmal oder rufen Sie uns an.', 'fehler');
        });
    });
  });
})();
