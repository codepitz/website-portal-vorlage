# Website-Portal-Vorlage

Baukasten für Kundenwebseiten mit eigenem **Adminportal** — ohne Framework, ohne Datenbank, PHP 8.1+,
läuft auf jedem Webspace (STRATO, IONOS, ALL-INKL, Hetzner …).

- **Portal bleibt gleich**: Sektionen der Startseite, Leistungen, Team, Preise, Medien, Hinweise,
  Benutzer & Rollen, Rechtstexte, SMTP, Systemprüfung, Entwurf → Vorschau → Veröffentlichen mit Sicherung.
- **Design ist austauschbar**: Theme-Pakete (ZIP mit `theme.json`, Vorlagen, CSS, Bildern, Schriften)
  gestalten Kopfleiste, Sektionen und Fußzeile komplett neu und bringen bei Bedarf eigene Sektionstypen,
  Varianten und Zusatzfelder mit — die Inhalte bleiben dabei unverändert. Siehe **docs/THEMES.md**.
- **Ein Befehl pro Kunde**: `werkzeuge/projekt-erzeugen.mjs` baut aus einer `kunde.json` ein fertiges,
  Git-fähiges Kundenprojekt inklusive GitHub-Actions-Deploy per SFTP.

```bash
node werkzeuge/projekt-erzeugen.mjs --daten beispiele/kunde.beispiel.json --ziel ../muster-film-portal --theme to-frames-kino
python3 werkzeuge/e2e-pruefen.py ../muster-film-portal --bilder /tmp/e2e     # optional: Ende-zu-Ende im Browser
```

| Pfad | Inhalt |
|---|---|
| `vorlage/` | das Paket: `portal/` (Adminportal), `privat/` (Generator `bauen.php`, Theme-System `theme.php`, `theme-werkzeug.php`, Daten, Designs), `webseite/` |
| `themes/` | Theme-Pakete (Quellen). Jedes wird ins Kundenprojekt übernommen und installiert |
| `kunde-repo/` | Dateien, die jedes Kundenprojekt zusätzlich bekommt: `.github/workflows/deploy.yml`, `.gitignore`, `README.md` |
| `werkzeuge/projekt-erzeugen.mjs` | kunde.json → Kundenprojekt |
| `werkzeuge/e2e-pruefen.py` | Portal einrichten, Theme hochladen/wählen, Sektionen, Veröffentlichen, Webseite — alles im Browser prüfen |
| `werkzeuge/buendeln.mjs` | Vorlage für den Browser-Assistenten bündeln (nach Änderungen in `vorlage/`) |
| `assistent/` | der ursprüngliche Browser-Assistent (Doppelklick auf `index.html`) |
| `docs/THEMES.md` · `docs/KUNDENDATEN.md` | Theme-Format und Aufbau der kunde.json |

---

## Browser-Assistent (weiterhin enthalten)

### Zwei Abschnitte

| | Entwicklung (Installation) | Implementierung (Anleitung) |
|---|---|---|
| Wo | komplett offline im Browser | beim Hoster, per SFTP |
| Was | 12 Fragen → fertiges Paket als ZIP (oder direkt in einen Ordner) | 20 Schritte mit eigenen Werten und gezeichneten Beispiel-Screenshots |
| Ende | „Weiter mit der Anleitung“ **oder** „Installation abschließen“ | „Weiter“ nach jedem Schritt, „Anleitung beenden“ jederzeit |

Jeder Schritt lässt sich mit **„Später ergänzen“** überspringen — das Paket entsteht trotzdem mit
neutralen Platzhaltern; die Zusammenfassung listet offene Schritte mit „Jetzt ergänzen“.
Der Stand wird im Browser gespeichert; „Fortsetzen“ springt an die letzte Stelle.

**Offline ausprobieren** (Knopf in der Zusammenfassung und nach dem Erzeugen): lädt ein einzelnes
Startprogramm für Mac (`.command` in einer ZIP-Datei, damit das Ausführungsrecht erhalten bleibt) oder
Windows (`.bat`), in dem das ganze Paket steckt. Doppelklick → packt aus, sucht PHP (Homebrew, MAMP,
XAMPP, winget), bietet sonst die Installation an, wählt freie Ports und öffnet Portal und Webseite im
Browser. Keine Befehle nötig. macOS 15+ blockiert den ersten Start unsignierter Skripte:
„Fertig“ klicken, dann Systemeinstellungen › Datenschutz & Sicherheit › „Dennoch öffnen“ (der Dialog im
Assistenten verlinkt die Einstellung direkt; Plan B: Datei ins Terminal ziehen, Eingabetaste). Vermeiden
ließe sich das nur mit einer kostenpflichtigen Apple-Developer-Signierung und Notarisierung.
`projekt-assistent.json` (liegt im Paket) lässt sich über „Projektdatei laden“ wieder öffnen.

### Die Fragen
Art der Webseite (7 Branchen-Vorlagen) · Name & Marke · Anschrift & Kontakt · Impressum-Angaben
(inkl. Rechtsform, Register, reglementierte Berufe) · Domain & Hosting (STRATO, IONOS, ALL-INKL,
Hetzner, andere) · E-Mail-Postfächer & Kontaktformular · Inhaltsbereiche und ihre Namen ·
Startseite als Baukasten · Design (Aurora / Aurora Hell, Akzentfarbe mit Kontrastprüfung) ·
erste Texte & SEO · Portal-Zugang · Zusammenfassung mit offenen Punkten.

### Die Anleitung
Überblick → Kundenbereich & Paket → Domain → SFTP-Zugang → SFTP-Programm → Upload der drei Ordner
→ Domain auf `/webseite` → Portal-Subdomain → SSL → PHP-Version → Postfächer → Server-Daten
(IMAP/SMTP/Ports) → SPF/DKIM/DMARC → Portal einrichten → SMTP im Portal + Testmail →
Systemprüfung → erstes Veröffentlichen → Suchmaschinen & Sitemap → Sicherheit & Pflege →
Übersicht aller Zugangsdaten (ohne Passwörter). Liegt auch als `ANLEITUNG.html` im Paket.

## Das erzeugte Paket

```
<name>-portal/
├─ webseite/   → Docroot der Hauptdomain (www.domain.de) — vom Generator geschrieben
├─ portal/     → Docroot der Subdomain (portal.domain.de) — das Adminportal (PHP 8.1+)
├─ privat/     → ohne Domain: Inhalte, Entwürfe, Konten, Designs, Sicherungen, Generator
├─ ANLEITUNG.html · LIESMICH.txt · projekt-assistent.json
└─ Offline ausprobieren (Mac).command / (Windows).bat → Doppelklick: Portal und Webseite lokal
```

## Das Portal (Vorlage in `vorlage/`)

Übernommen und verallgemeinert aus dem NeoPodo-Portal — ohne Framework, ohne Datenbank, JSON-Dateien:

- **Entwurf → Vorschau → Veröffentlichen** mit Änderungsvergleich, automatischer Sicherung vor jedem
  Veröffentlichen (letzte 30), Wiederherstellen als Entwurf, „Webseite neu erzeugen“
- **Sektionen der Startseite (Baukasten)**: 18 Typen — Kopfbereich, Laufband, Über uns, Angebote,
  Team, Zahlen, Preise, FAQ, Kontakt (mit Öffnungszeiten), Anfahrt (Karte erst auf Klick), Text mit Bild, Karten,
  Schritte, Kundenstimmen, Galerie, Video, Aufruf-Band, Freitext — sortieren, ein-/ausblenden,
  duplizieren, Anker; jeder Bereich (außer Kopfbereich/Laufband) kommt mit ☰ ins Menü oben,
  die Öffnungszeiten im Kontakt bekommen mit ◷ einen eigenen Menüpunkt; dazu frei wählbare Menüpunkte unter
  „Webseite allgemein“. Das Menü steht immer in einer Zeile und klappt bei Platzmangel ins ☰; es verlinkt
  nur Bereiche, die tatsächlich etwas zeigen
- **Firmenlogo** oben links (Webseite allgemein › Kopfleiste & Logo): nur Logo, Logo und Name, oder nur
  Name; drei Größen. Im Assistenten direkt beim Namen hochladbar
- **Inhaltsbereiche** aus einem Schema (Angebote, Team, Preise, Zeiten & Anfahrt, Stammdaten,
  Kontaktformular, Webseite allgemein, Impressum/Datenschutz/Barrierefreiheit mit Platzhaltern)
- **Medien**: Upload per Drag & Drop, Erkennung am Dateiinhalt, EXIF-/Ortsdaten entfernen,
  verkleinern, ersetzen, Verwendungsnachweis
- **Hinweise & Urlaub**: Leiste oder Fenster mit Zeitraum, der sich im Browser selbst ein-/ausblendet
- **Benutzer & Rollen** (Verwaltung/Redaktion), Sperren, Passwort setzen, Notfall-Zugang per CLI,
  Anmeldebremse, CSRF-Schutz, Sitzungsablauf, Protokoll
- **Einstellungen**: Pfade, Sitzungsdauer, **SMTP-Versand** mit Testmail
- **Systemprüfung**: PHP, Ordner, Schreibrechte, Abschottung, HTTPS, SMTP, Design-Upload
- **Design** (neu): Designs wählen, kopieren, bearbeiten (alle Tokens, Kontrastprüfung), exportieren —
  und **ein Claude Design System hochladen** (ZIP-Export, CSS, DESIGN-SYSTEM.md oder design.json):
  CSS-Variablen werden eingesammelt und aufgelöst, Schriften übernommen, Zuordnung vorgeschlagen und
  prüfbar. Wie jede Änderung läuft auch ein Designwechsel über Entwurf und Veröffentlichen.

Portal und Webseite sind als Template im Aurora-Stil gebaut. Die Webseite liest alle Farben,
Schriften und Rundungen aus `assets/design.css` (Variablen `--ws-*`), die der Generator aus dem
aktiven Design schreibt — ein hochgeladenes Design System färbt deshalb die ganze Seite um.

## Entwickeln

```bash
node werkzeuge/buendeln.mjs
```

Nach jeder Änderung in `vorlage/` ausführen — bündelt die Vorlage in `assistent/assets/vorlage.js`,
damit der Assistent offline per Doppelklick läuft.

| Pfad | Inhalt |
|---|---|
| `assistent/assets/app.js` | Ablauf, Fragen, Speichern, Abschluss |
| `assistent/assets/daten.js` | Hoster (Server, Ports, Menüpfade), Branchen, Sektionen, Akzentfarben |
| `assistent/assets/erzeugen.js` | Paket aus Antworten + Vorlage bauen |
| `assistent/assets/anleitung.js` · `bilder.js` | Anleitungsschritte · SVG-Beispielbildschirme |
| `assistent/assets/zip.js` | ZIP ohne Bibliothek |
| `vorlage/portal/` | Adminportal (Router `index.php`, `lib/`, `ansichten/`, `assets/`) |
| `vorlage/privat/bauen.php` | Generator der Webseite (reines HTML/CSS) |
| `vorlage/privat/vorlagen/` | `seite.css`, `seite.js`, `anfrage.php`, `site.htaccess` |
| `vorlage/privat/design/` | eingebaute Designs Aurora und Aurora Hell |

Neue Sektion: Typ in `vorlage/portal/lib/sektionen.php` (Felder, Vorlage), Darstellung als
`ws_sektion_<typ>()` in `vorlage/privat/bauen.php`, Eintrag in `assistent/assets/daten.js`.

Hinweis: Die Rechtstexte sind eine allgemeine Vorlage und keine Rechtsberatung. Menüpfade der Hoster
sind typische Wege — Anbieter benennen Menüs gelegentlich um; die Anleitung sagt das dazu.
