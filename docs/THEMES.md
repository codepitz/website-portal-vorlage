# Themes — die Gestalt der Webseite austauschen

Das Portal ist immer dasselbe: Es pflegt Inhalte (Texte, Bilder, Sektionen, Leistungen, Team,
Preise, Rechtstexte) in JSON-Dateien. **Wie** diese Inhalte auf der Webseite aussehen, bestimmt
das gewählte Design. Ein Design ist entweder

- **nur Stellschrauben** (`design.json › tokens`: Farben, Schriften, Rundungen, Breite) — so sind
  „Aurora“ und „Aurora Hell“ gebaut, oder
- ein **Theme-Paket**: Stellschrauben *plus* eigenes Markup (Vorlagen), eigenes CSS, Bilder,
  Schriften, bei Bedarf eigene Sektionstypen, Varianten und Zusatzfelder.

Themes lassen sich im Portal unter **Design** als ZIP hochladen, in der Vorschau ansehen, wählen
und veröffentlichen — wie jede andere Änderung. Die Inhalte bleiben beim Wechsel unverändert.
Nur die Verwaltung darf Theme-Pakete hochladen (sie bringen Markup und Skripte mit).

---

## 1. Aufbau eines Theme-Pakets

```
mein-theme/
├─ theme.json                 Pflicht — Name, Stellschrauben, Schriften, Theme-Angaben
├─ theme.css                  eigenes Stylesheet (wird NACH seite.css geladen)
├─ theme.js                   optional, mit defer geladen
├─ vorlagen/
│  ├─ seite.html              Aufbau von <body> (optional)
│  ├─ kopf.html               Kopfleiste (optional)
│  ├─ fuss.html               Fußzeile (optional)
│  ├─ recht.html              Impressum / Datenschutz / Barrierefreiheit (optional)
│  ├─ kopfzusatz.html         zusätzliche Zeilen in <head> (optional)
│  ├─ sektionen/<typ>.html            Darstellung eines Sektionstyps
│  ├─ sektionen/<typ>--<variante>.html  Variante davon
│  └─ teile/<name>.html       wiederverwendbare Bausteine für {{> name}}
├─ assets/                    Bilder, Symbole, Videos → im Template unter {{theme}}assets/…
└─ schriften/                 woff2/woff/ttf/otf → in theme.json › fonts eintragen
```

**Alles ist optional außer `theme.json`.** Fehlt eine Vorlage, gilt die eingebaute Darstellung
(die dann über `theme.css` und die Stellschrauben gestaltet wird). Ein Theme ersetzt also nur,
was wirklich anders aussehen soll.

Erlaubte Dateiarten: css, js, html, json, svg, png, jpg/jpeg, webp, avif, gif, ico, woff2, woff,
ttf, otf, mp4, webm, txt, md. Alles andere (PHP, .htaccess, versteckte Dateien) wird beim Import
verworfen. Höchstens 80 MB entpackt. Achtung: Die Upload-Grenze des Hosters (oft 2–64 MB) gilt für
die ZIP-Datei — große Bilder besser über die Medienverwaltung einbinden.

## 2. theme.json

```json
{
  "name": "to.frames Kino",
  "beschreibung": "Ein Satz für die Designkachel im Portal.",
  "version": "1.0.0",
  "autor": "to.frames",
  "tokens": { "bg": "#0a0a0b", "surface": "#131316", "surface-2": "#1b1b1f", "line": "#26262b",
              "text": "#f4f2ec", "text-2": "#b9b5ac", "muted": "#86837c",
              "accent": "#f26d6d", "accent-ink": "#22100f", "ok": "#6fcf97", "danger": "#ff8a80",
              "aurora": false, "font-body": "-apple-system, …", "font-head": "\"Newsreader\", Georgia, serif",
              "head-weight": 500, "text-size": 17, "radius": "16px", "radius-sm": "999px",
              "maxw": "1140px", "modus": "dunkel" },
  "fonts": [ { "familie": "Newsreader", "datei": "newsreader-latin-400-normal.woff2", "gewicht": "400", "stil": "normal" } ],
  "variablen": { "--tf-kaltlicht": "#506e8c" },
  "theme": {
    "version": 1,
    "css": ["theme.css"],
    "js": [],
    "basisCss": true,
    "bodyKlasse": "tf",
    "varianten":    { "hero": { "mit-karte": "Mit Paket-Karte rechts" } },
    "zusatzfelder": { "hero": { "preisZeile": { "typ": "text", "label": "Preis neben den Knöpfen" } } },
    "sektionen":    { "preis-band": { "name": "Preis-Band", "beschreibung": "…", "zeichen": "€", "einmalig": false,
                                      "menue": "Preis", "felder": { … }, "vorlage": { … } } }
  }
}
```

| Schlüssel | Bedeutung |
|---|---|
| `tokens` | Stellschrauben (alle optional, fehlende = Aurora-Vorgabe). Landen als `--ws-<name>` in `assets/design.css` und sind im Portal unter *Design › Bearbeiten* änderbar. **Im Theme-CSS immer `var(--ws-…)` verwenden**, dann wirken Änderungen im Portal auch im Theme. |
| `fonts` | Schriftdateien aus `schriften/`. Der Generator schreibt die `@font-face`-Regeln. Keine Google-Fonts-Links (DSGVO) — Schriften immer lokal mitliefern (z. B. aus `npm pack @fontsource/<name>`). |
| `variablen` | weitere CSS-Variablen für `:root` (z. B. aus einem Claude Design System). |
| `theme.css` / `theme.js` | Dateien in Ladereihenfolge. Fehlt der Schlüssel, werden `theme.css`/`theme.js` genommen, falls vorhanden. |
| `theme.basisCss` | `false` = `seite.css` weglassen. Nur, wenn das Theme **alle** Sektionen, Formular, Hinweise und Menü selbst gestaltet. Empfehlung: `true` lassen und überschreiben. |
| `theme.bodyKlasse` | zusätzliche Klasse an `<body>` (außerdem immer `ws--theme ws-theme--<kennung>`). |
| `theme.varianten` | je Sektionstyp eine Auswahl „Variante“ im Portal → Vorlage `sektionen/<typ>--<variante>.html`. |
| `theme.zusatzfelder` | je (eingebautem) Sektionstyp weitere Felder, erscheinen im Portal unter „Zusätzlich im Design …“. |
| `theme.sektionen` | neue Sektionstypen mit eigenen Feldern; erscheinen im Portal unter „Aus dem Design …“. `vorlage` = Startwerte, `menue` = Vorschlag für den Menüpunkt. Name: `a-z0-9-`, nicht wie ein eingebauter Typ. |

### Feldtypen (für `felder` und `zusatzfelder`)

`text` · `zeile` (Überschrift) · `flaeche` (mehrzeilig, `zeilen`) · `zahl` (`min`,`max`) · `datum` ·
`schalter` · `auswahl` (`optionen: {wert: "Beschriftung"}`) · `farbe` · `medium` (`art`: bild|video|pdf) ·
`link` · `absaetze` (Liste von Texten, `einzeilig`) · `liste` (`felder`, `zeilenTitel`, `knopf`, `kompakt`) ·
`objekt` (`felder`) · `abschnitt` (nur Gliederung) · `hinweis` (`text`).
Allgemein: `label`, `hilfe`, `breit`, `pflicht`, `muster`.

Medienfelder speichern `medien/<datei>` — in der Vorlage steht automatisch `<feld>Url` bereit.

## 3. Vorlagensprache

Logikfrei, an Mustache angelehnt:

| Schreibweise | Wirkung |
|---|---|
| `{{feld}}` | Text, HTML-sicher maskiert. Punkte gehen in die Tiefe: `{{firma.kontakt.email}}` |
| `{{feld\|filter}}` | Filter: `zeilen` (Umbrüche → `<br>`), `absaetze` (Liste → `<p>`), `medium` (Pfad → URL), `ziel` (Link auflösen), `knopf` / `knopf2` (Objekt `{text, ziel}` → fertiger Knopf), `initialen`, `tel`, `gross`, `klein`, `anzahl`, `zahl`, `zweistellig` (1 → 01), `json` |
| `{{{nameHtml}}}` | fertiges HTML — **nur** für Werte, deren Name auf `Html` endet, und `standard`. Alles andere wird auch hier maskiert (Schutz vor eingeschleustem Code aus dem Portal). |
| `{{#liste}}…{{/liste}}` | Schleife. Darin: `{{.}}` (Eintrag), `{{@nummer}}` (1…), `{{@index}}` (0…), `{{#@erste}}`, `{{#@letzte}}`, `{{@anzahl}}` |
| `{{#feld}}…{{/feld}}` | Block, wenn der Wert gefüllt ist (bei Objekten: Felder direkt ansprechbar) |
| `{{#liste?}}…{{/liste?}}` | Block **einmal**, wenn die Liste Einträge hat (für `<ul>` um eine Schleife) |
| `{{^feld}}…{{/feld}}` | Block, wenn der Wert leer ist |
| `{{#farbe=akzent}}…{{/farbe=akzent}}` | Vergleich, auch `!=` |
| `{{> name}}` | Baustein aus `vorlagen/teile/name.html` (bis 8 Ebenen) |
| `{{! Kommentar }}` | wird nicht ausgegeben |

Namen werden von innen nach außen gesucht: In einer Schleife über `angebote` findet `{{titel}}` den
Titel des Angebots, fehlt er, den Titel der Sektion. Nicht geschlossene Blöcke melden Datei und Zeile.
Fehlerhafte Vorlagen brechen nichts: Der Generator nimmt dann die eingebaute Darstellung und meldet
das unter „Offene Punkte“ beim Veröffentlichen.

## 4. Was jede Vorlage kennt

| Name | Inhalt |
|---|---|
| `basis` | Pfad zur Startseite (`''`, `../` oder Vorschau-Adresse) — für eigene Links: `{{basis}}impressum/` |
| `theme` | Adresse des Theme-Ordners: `<img src="{{theme}}assets/deko.svg">` |
| `startUrl`, `jahr`, `seite` (start/impressum/…), `istStart`, `vorschau` | |
| `firma` | Stammdaten: `name`, `claim`, `beschreibung`, `kontakt.{strasse,plz,ort,telefon,email,fax}`, `telefonUrl`, `emailUrl`, `anschriftHtml`, `mapsUrl`, `logoUrl`, `social[]`, `recht.*` |
| `begriffe` | Namen aus dem Projekt: `angebote`, `angebot`, `team`, `person`, `zeiten`, `hinweise`, `anfrage` |
| `angebote[]` | `titel`, `kurz`, `text`, `punkte[]`, `dauer`, `zusatz`, `bild`/`bildUrl`, `bildAlt`, `id`, `anker` |
| `team[]` | `name`, `rolle`, `schwerpunkt`, `seit`, `zitat`, `bild`/`bildUrl`, `bildAlt`, `initialen` |
| `preise` | `hervorgehoben[]` (`eyebrow`, `titel`, `preis`, `variante`, `detail`), `gruppen[]` (`titel`, `positionen[]` mit `name`, `meta`, `preis`), `hinweis` |
| `zeiten[]`, `zeitenHtml` | Öffnungszeiten (`tage`, `zeit`) und fertige Tabelle |
| `menue[]` | Menüpunkte: `label`, `url`, `anker`, `extern` |
| `recht[]`, `social[]` | Links für die Fußzeile: `label`/`titel`, `url` |
| `kopfknopf` | Knopf der Kopfleiste: `text`, `url`, `zeigen` |
| `markeHtml`, `formularHtml`, `hinweiseHtml` | fertiges Logo/Name, Kontaktformular, Hinweis-Leisten |

**In `sektionen/*.html`** zusätzlich alle Felder der Sektion (siehe Tabelle unten) und:
`standard` (die eingebaute Darstellung — zum Umhüllen: `<div class="rahmen">{{{standard}}}</div>`),
`klasseHtml` (`ws-sektion ws-sektion--<typ> ws-farbe--<farbe>`), `ankerAttrHtml` (` id="…"`),
`kopfblockHtml` / `kopfblockMitteHtml` (Kleinzeile + Überschrift + Einleitung wie eingebaut),
`farbe` (standard/flaeche/akzent), `variante`, `absaetzeHtml` (wenn es `absaetze` gibt),
`<feld>Url` für Medien, `<feld>Html` für mehrzeilige Texte, und an jedem Knopf-Objekt `url`,
`zeigen`, `extern`.

**In `kopf.html`**: `standard`, `menueHtml`, `knopfHtml`. **In `fuss.html`**: `standard`,
`rechtLinksHtml`, `socialLinksHtml`, `fussText`. **In `recht.html`**: `titel`, `stand`,
`abschnitte[]` (`titel`, `absaetzeHtml`), `abschnitteHtml`, `standard`.
**In `seite.html`**: `sprungHtml`, `hinweiseHtml`, `kopfHtml`, `hauptHtml`, `fussHtml`, `standard`.

### Felder der eingebauten Sektionstypen

| Typ | Felder |
|---|---|
| `hero` | eyebrow · zeilen[] · lead · knopf1 · knopf2 · medium (verlauf/ruhig/video/bild) · video · bild · abdunkeln · news{zeigen, eyebrow, titel, ziel} |
| `laufband` | begriffe[] |
| `ueber-uns` | eyebrow · statement · absaetze[] · bild · bildAlt · werte[]{titel, text} · knopf |
| `angebote` | eyebrow · titel · titelLeise · lead · darstellung (karten/liste) · knopf1 · knopf2 — Einträge aus `angebote[]` |
| `team` | eyebrow · titel · titelLeise · lead — Personen aus `team[]` |
| `zahlen` | eyebrow · titel · titelLeise · lead · eintraege[]{wert, suffix, einheit, label, text} |
| `preise` | eyebrow · titel · titelLeise · lead · knopf — Daten aus `preise` |
| `faq` | eyebrow · titel · titelLeise · fragen[]{frage, antwort} |
| `kontakt` | eyebrow · titel · titelLeise · lead · zeitenMenue{zeigen, label} — dazu `firma`, `zeitenHtml`, `formularHtml` |
| `anfahrt` | eyebrow · titel · titelLeise · lead · adresse · zoom · laden (klick/sofort) · punkte[] |
| `text-bild` | eyebrow · titel · titelLeise · absaetze[] · bild · bildAlt · bildSeite (rechts/links) · knopf |
| `karten` | eyebrow · titel · titelLeise · lead · spalten (2/3/4) · karten[]{eyebrow, titel, text, bild, bildAlt, link} |
| `schritte` | eyebrow · titel · titelLeise · lead · schritte[]{titel, text} |
| `stimmen` | eyebrow · titel · titelLeise · lead · stimmen[]{zitat, name, zusatz} |
| `galerie` | eyebrow · titel · titelLeise · lead · bilder[]{bild, alt, beschriftung} |
| `video` | eyebrow · titel · titelLeise · lead · video · poster · wiedergabe (steuerung/schleife) · beschreibung |
| `aufruf` | eyebrow · titel · text · knopf1 · knopf2 |
| `freitext` | eyebrow · titel · titelLeise · absaetze[] |

Jede Sektion hat außerdem `id`, `typ`, `aktiv`, `anker`, `menue{zeigen,label}`, `farbe` und ggf. `variante`.

## 5. Pflicht-Haken für das Skript der Webseite

`assets/seite.js` bleibt immer geladen. Damit Menü, Hinweise, Karte, Zähler und Formular
funktionieren, müssen eigene Vorlagen diese Attribute behalten (oder die fertigen `…Html`-Werte ausgeben):

| Funktion | Markup |
|---|---|
| Menü klappt auf schmalen Bildschirmen | Kopf mit `data-kopf`, darin Knopf `data-menueknopf` + `aria-controls="ws-menue"` und `<nav id="ws-menue">`. Bei Platzmangel setzt das Skript `.ws-kopfleiste--eng` am `data-kopf`-Element — dafür CSS vorsehen oder die Klassen `ws-kopfleiste`/`ws-menue`/`ws-menueknopf` weiterverwenden. |
| Sprungmarke | `<main id="inhalt">` (steckt in `hauptHtml`/Standardaufbau — in `seite.html` `<main id="inhalt">{{{hauptHtml}}}</main>` schreiben) |
| Öffnungszeiten im Menü | Element mit `id="zeiten"` im Kontaktbereich |
| Kontaktformular | `{{{formularHtml}}}` ausgeben — nie ein eigenes Formular bauen (Versand, Spam-Schutz, Einwilligung hängen daran) |
| Hochzählende Zahlen | `data-zaehlen="<wert>"` (oder eingebaute Darstellung verwenden) |
| Karte erst auf Klick | eingebaute `anfahrt`-Darstellung verwenden oder `{{{standard}}}` einbetten (DSGVO) |
| Hintergrundvideo anhalten | Knopf mit `data-video-pause` |

## 6. Ein Claude Design in ein Theme umwandeln (Arbeitsablauf)

1. **Design lesen**: HTML-Export(e), `styles.css`, `tokens/*.css`, README. Canvas-Dateien (`*.dc.html`)
   ohne `support.js` öffnen: `<x-dc>`/`<helmet>` entfernen, dann im Browser ansehen.
2. **Stellschrauben zuordnen**: Hintergrund, Flächen, Linien, Text, Akzent, Schriften, Rundungen,
   Breite → `theme.json › tokens`. Übrige Design-Variablen → `variablen`.
3. **Schriften lokal** in `schriften/` ablegen (`npm pack @fontsource/<familie>`), in `fonts` eintragen.
4. **Abschnitte des Designs den Sektionstypen zuordnen** (Hero → `hero`, Leistungskarten → `angebote`,
   „So läuft’s“ → `schritte`, Preis-Block → `preise` oder eigener Typ …). Was es eingebaut nicht
   gibt, wird ein eigener Typ in `theme.sektionen`; alternative Anordnungen desselben Inhalts werden
   `varianten`; zusätzliche Angaben an eingebauten Typen `zusatzfelder`.
5. **Vorlagen schreiben**: Inline-Styles aus dem Design in Klassen in `theme.css` überführen,
   feste Texte durch `{{felder}}` ersetzen, Farben durch `var(--ws-…)`.
6. **Prüfen**:
   ```bash
   php privat/theme-werkzeug.php installieren themes/<kennung> --kennung=<kennung>
   php privat/theme-werkzeug.php pruefen <kennung>
   php privat/theme-werkzeug.php vorschau <kennung> /tmp/vorschau && php -S localhost:8090 -t /tmp/vorschau
   ```
   Screenshots bei 1280 px und 390 px vergleichen, bis es dem Design entspricht. Kein
   horizontales Scrollen auf dem Handy, Kontraste lesbar, Tastatur-Fokus sichtbar.
7. **Packen**: `cd themes && zip -r ../<kennung>.zip <kennung>` — im Portal unter *Design* hochladen,
   oder im Kunden-Repo unter `themes/` ablegen und den Deploy-Workflow mit *Themes aktualisieren* starten.

Beispiel: `themes/to-frames-kino/` ist aus dem Claude-Design „Landingpage Imagefilm-Paket“ entstanden
(Varianten für Hero und Karten, zwei eigene Sektionstypen, Zusatzfelder für die Paket-Karte).
