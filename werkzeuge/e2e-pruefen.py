#!/usr/bin/env python3
"""
Ende-zu-Ende-Prüfung eines erzeugten Kundenprojekts — im echten Browser.

    python3 werkzeuge/e2e-pruefen.py <projektordner> [--theme-zip theme.zip] [--bilder <ordner>]

Startet Portal und Webseite mit dem PHP-Server, richtet ein Testkonto ein,
öffnet Design und Sektionen, lädt optional ein Theme-Paket hoch, wählt es,
fügt je eine Sektion jedes Theme-Typs hinzu, veröffentlicht und prüft die
fertige Webseite. Screenshots landen in --bilder (Standard: ./e2e-bilder).

Arbeitet auf einer KOPIE des Projekts — das Original bleibt unberührt.
Braucht: php (8.1+), python3, playwright (pip install playwright).
"""

import argparse
import json
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
from pathlib import Path

from playwright.sync_api import sync_playwright

p = argparse.ArgumentParser()
p.add_argument('projekt')
p.add_argument('--theme-zip')
p.add_argument('--bilder', default='e2e-bilder')
p.add_argument('--breite', type=int, default=1280)
args = p.parse_args()

quelle = Path(args.projekt).resolve()
bilder = Path(args.bilder).resolve()
bilder.mkdir(parents=True, exist_ok=True)
arbeit = Path(tempfile.mkdtemp(prefix='e2e-')) / 'projekt'
shutil.copytree(quelle, arbeit, ignore=shutil.ignore_patterns('.git', 'node_modules'))
for rest in ['portal/konfig.php']:
    (arbeit / rest).unlink(missing_ok=True)
for ordner in ['privat/ablage', 'privat/entwurf', 'privat/sicherung']:
    shutil.rmtree(arbeit / ordner, ignore_errors=True)


def frei() -> int:
    s = socket.socket()
    s.bind(('127.0.0.1', 0))
    port = s.getsockname()[1]
    s.close()
    return port


pp, wp = frei(), frei()
log_portal = open(arbeit.parent / 'portal.log', 'w')
log_web = open(arbeit.parent / 'web.log', 'w')
server = [
    subprocess.Popen(['php', '-S', f'127.0.0.1:{pp}', '-t', str(arbeit / 'portal')], stdout=log_portal, stderr=subprocess.STDOUT),
    subprocess.Popen(['php', '-S', f'127.0.0.1:{wp}', '-t', str(arbeit / 'webseite')], stdout=log_web, stderr=subprocess.STDOUT),
]
time.sleep(0.8)
P = f'http://127.0.0.1:{pp}/'
W = f'http://127.0.0.1:{wp}/'
fehler: list[str] = []
schritte: list[str] = []


def ok(text: str) -> None:
    schritte.append('✓ ' + text)
    print('✓', text, flush=True)


def fail(text: str) -> None:
    fehler.append(text)
    print('✗', text, flush=True)


def meldungen(page) -> str:
    return ' | '.join(t.strip() for t in page.locator('.meldung').all_inner_texts())


try:
    with sync_playwright() as pw:
        browser = pw.chromium.launch()
        page = browser.new_page(viewport={'width': args.breite, 'height': 900})
        konsole: list[str] = []
        page.on('console', lambda m: konsole.append(m.text) if m.type == 'error' else None)

        # 1. Einrichten
        page.goto(P)
        if page.locator('input[name=passwort2]').count():
            page.fill('input[name=anzeige]', 'E2E Test')
            page.fill('input[name=benutzer]', 'e2etest')
            page.fill('input[name=passwort]', 'e2e-Passwort-123')
            page.fill('input[name=passwort2]', 'e2e-Passwort-123')
            page.locator('form button[type=submit]').first.click()
            page.wait_for_load_state()
            ok('Portal eingerichtet (Konto e2etest)')
        if page.locator('input[name=passwort]').count() and not page.locator('input[name=passwort2]').count():
            page.fill('input[name=benutzer]', 'e2etest')
            page.fill('input[name=passwort]', 'e2e-Passwort-123')
            page.locator('form button[type=submit]').first.click()
            page.wait_for_load_state()
        if 'abmelden' not in page.content().lower():
            fail('Anmeldung im Portal hat nicht geklappt: ' + meldungen(page))
            raise SystemExit
        page.screenshot(path=str(bilder / '01-start.png'), full_page=True)
        ok('Startseite des Portals')

        # 2. Alle Bereiche des Portals einmal öffnen
        for seite in ['sektionen', 'medien', 'hinweise', 'benutzer', 'einstellungen', 'diagnose', 'protokoll', 'veroeffentlichen', 'design']:
            r = page.goto(P + '?seite=' + seite)
            if r.status >= 400 or 'Das Portal ist abgebrochen' in page.content():
                fail(f'Portalseite {seite}: HTTP {r.status}')
        ok('Alle Portalseiten öffnen ohne Fehler')

        # 3. Theme hochladen
        if args.theme_zip:
            page.goto(P + '?seite=design')
            # Das Portal schickt die Datei sofort nach der Auswahl ab
            with page.expect_navigation():
                page.set_input_files('input[name=datei]', args.theme_zip)
            page.wait_for_load_state()
            text = meldungen(page)
            if 'installiert' in text:
                ok('Theme-Paket hochgeladen: ' + text[:160])
            else:
                fail('Theme-Upload: ' + text)
        page.goto(P + '?seite=design')
        page.screenshot(path=str(bilder / '02-design.png'), full_page=True)

        # 4. Ein Theme wählen (das zuletzt installierte bzw. das erste mit Theme-Plakette)
        kacheln = page.locator('article.designkachel')
        gewaehlt = None
        for i in range(kacheln.count()):
            k = kacheln.nth(i)
            if k.locator('.pille', has_text='Theme').count():
                gewaehlt = k.locator('.designkachel-titel').inner_text()
                if k.locator('input[name=tat][value=verwenden]').count():
                    k.locator('form:has(input[value=verwenden]) button').click()
                    page.wait_for_load_state()
        if gewaehlt:
            ok(f'Theme „{gewaehlt}“ im Entwurf gewählt')
        else:
            ok('Kein Theme installiert — geprüft wird das eingebaute Design')

        # 5. Sektionen: eigene Typen des Themes hinzufügen
        page.goto(P + '?seite=sektionen')
        themeKacheln = page.locator('h3:has-text("Aus dem Design") + p + .typwahl form')
        n = themeKacheln.count()
        for i in range(n):
            page.goto(P + '?seite=sektionen')
            formen = page.locator('h3:has-text("Aus dem Design") + p + .typwahl form')
            if formen.count() <= i:
                break
            f = formen.nth(i)
            f.locator('button').click()
            page.wait_for_load_state()
            # Sichtbar schalten und speichern
            schalter = page.locator('input[type=checkbox][name="d[aktiv]"]')
            if schalter.count() and not schalter.is_checked():
                schalter.check(force=True)
            page.locator('form button[type=submit]:has-text("Speichern")').first.click()
            page.wait_for_load_state()
            if 'Gespeichert' not in meldungen(page):
                fail('Theme-Sektion speichern: ' + meldungen(page))
        if n:
            ok(f'{n} Sektionstyp(en) aus dem Theme hinzugefügt und gespeichert')
        page.goto(P + '?seite=sektionen')
        page.screenshot(path=str(bilder / '03-sektionen.png'), full_page=True)

        # 6. Jede Sektion einmal öffnen und unverändert speichern (Formulare vollständig?)
        links = [a.get_attribute('href') for a in page.locator('a.sektion-text').all()]
        for href in links:
            page.goto(P + href)
            page.locator('form button[type=submit]:has-text("Speichern")').first.click()
            page.wait_for_load_state()
            m = meldungen(page)
            if 'Gespeichert' not in m:
                fail(f'Sektion {href}: {m}')
        ok(f'{len(links)} Sektionen geöffnet und gespeichert')

        # 7. Vorschau
        page.goto(P + 'vorschau.php/')
        page.wait_for_load_state('networkidle')
        page.screenshot(path=str(bilder / '04-vorschau.png'), full_page=True)
        if page.locator('main#inhalt').count() == 0:
            fail('Vorschau ohne <main id="inhalt">')
        else:
            ok('Vorschau des Entwurfs')

        # 8. Veröffentlichen
        page.goto(P + '?seite=veroeffentlichen')
        tat = 'los' if page.locator('input[name=tat][value=los]').count() else 'neu_bauen'
        page.locator(f'form:has(input[name=tat][value={tat}]) button[type=submit]').first.click()
        # Rückfrage des Portals bestätigen
        ja = page.locator('.dialog-fuss [data-ja]')
        if ja.count():
            with page.expect_navigation():
                ja.click()
        page.wait_for_load_state()
        m = meldungen(page)
        if 'seite=veroeffentlicht' in page.url or re.search(r'veröffentlicht|erzeugt|online', m, re.I):
            ok('Veröffentlicht: ' + (page.locator('main p').first.inner_text()[:160] if page.locator('main p').count() else m[:160]))
        else:
            fail('Veröffentlichen: ' + m)
        page.screenshot(path=str(bilder / '05-veroeffentlicht.png'), full_page=True)

        # 9. Fertige Webseite
        for pfad, name in [('', '06-webseite'), ('impressum/', '07-impressum')]:
            r = page.goto(W + pfad)
            page.wait_for_load_state('networkidle')
            if r.status != 200:
                fail(f'Webseite /{pfad}: HTTP {r.status}')
            page.screenshot(path=str(bilder / f'{name}.png'), full_page=True)
        klasse = page.evaluate('document.body.className')
        ok(f'Webseite ausgeliefert (body: {klasse})')
        mobil = browser.new_page(viewport={'width': 390, 'height': 844}, is_mobile=True)
        mobil.goto(W)
        mobil.wait_for_load_state('networkidle')
        breite = mobil.evaluate('document.documentElement.scrollWidth')
        if breite > 392:
            fail(f'Mobil: Seite ist {breite}px breit (horizontales Scrollen)')
        mobil.screenshot(path=str(bilder / '08-mobil.png'), full_page=True)
        ok('Mobilansicht ohne seitliches Scrollen' if breite <= 392 else 'Mobilansicht geprüft')

        fehlende = [u for u in konsole if 'Failed to load resource' in u]
        if fehlende:
            fail('Nicht geladene Dateien: ' + '; '.join(fehlende[:5]))
        browser.close()
finally:
    for s in server:
        s.terminate()
    log_portal.close()
    log_web.close()

php_log = (arbeit.parent / 'portal.log').read_text() + (arbeit.parent / 'web.log').read_text()
php_fehler = [z for z in php_log.splitlines() if re.search(r'PHP (Warning|Fatal|Parse|Deprecated|Notice)', z)]
if php_fehler:
    fail('PHP meldet:\n  ' + '\n  '.join(php_fehler[:10]))

print('\nErgebnis:', 'ALLES GUT' if not fehler else f'{len(fehler)} Problem(e)')
print('Screenshots:', bilder)
json.dump({'schritte': schritte, 'fehler': fehler}, open(bilder / 'ergebnis.json', 'w'), ensure_ascii=False, indent=2)
sys.exit(1 if fehler else 0)
