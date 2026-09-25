# Kundendaten (`kunde.json`)

Eingabe für `node werkzeuge/projekt-erzeugen.mjs --daten kunde.json --ziel <ordner>`.
Jedes Feld ist optional. Was fehlt, kommt aus der gewählten Branche bzw. wird mit neutralen
Platzhaltern gefüllt, die man später im Portal ergänzt. Beispiel: `beispiele/kunde.beispiel.json`.
Auch eine `projekt-assistent.json` aus dem Browser-Assistenten wird angenommen.

## Art & Marke

| Feld | Bedeutung | Beispiel |
|---|---|---|
| `branche` | `praxis` · `handwerk` · `gastro` · `verein` · `dienstleistung` · `handel` · `sonstiges` — setzt Begriffe, Module, Startseite und Beispieltexte | `"handwerk"` |
| `name` | Name des Betriebs (Webseite, Impressum) | `"Tischlerei Holm"` |
| `claim` | kurzer Leitsatz | `"Handwerk mit Haltung"` |
| `beschreibung` | 1–2 Sätze (Über uns, Fußzeile, SEO-Vorgabe) | |
| `organisation` | wie sich der Betrieb nennt (Praxis, Betrieb, Studio, Verein …) | `"Betrieb"` |
| `portal`, `kuerzel` | Name und Buchstabe des Portals | `"Holm Portal"`, `"H"` |
| `logoDatei` | Pfad zu PNG/JPG/WebP (relativ zur kunde.json) | `"logo.png"` |
| `logoAnzeige` | `logo` · `beides` · `name` | |

## Anschrift & Kontakt

`strasse`, `plz`, `ort`, `telefon` (Anzeige), `telefonLink` (`+49…`), `fax`, `email`

## Impressum

| Feld | Bedeutung |
|---|---|
| `inhaber` | Inhaberin/Inhaber bzw. Firma |
| `rechtsform` | z. B. `Einzelunternehmen`, `GmbH`, `UG (haftungsbeschränkt)`, `eingetragener Verein (e. V.)` |
| `vertreten` | Vertretungsberechtigte (bei GmbH, UG, AG, KG, Verein …) |
| `register` | Registergericht + Nummer (bei e. K., GmbH, Verein …) |
| `ustid` | USt-IdNr. |
| `verantwortlich` | Verantwortlich nach § 18 MStV |
| `reglementiert`, `berufsbezeichnung`, `verliehenIn`, `kammer`, `aufsichtsbehoerde`, `berufsrecht` | nur für reglementierte Berufe (Praxis, Handwerk mit Meisterpflicht …) |

## Domain & Hosting

| Feld | Bedeutung | Standard |
|---|---|---|
| `hoster` | `strato` · `ionos` · `allinkl` · `hetzner` · `andere` | `strato` |
| `hosterFirma` | Anschrift des Hosters (Datenschutzerklärung) | aus `hoster` |
| `domain` | ohne `www`/`https` | |
| `www` | Webseite unter `www.` | `true` |
| `sub` | Subdomain des Portals | `portal` |
| `php` | gewünschte PHP-Version | `8.3` |

## E-Mail & Kontaktformular

| Feld | Bedeutung | Standard |
|---|---|---|
| `postfaecher` | `[{ "name": "info", "zweck": "…" }]` | info, webseite |
| `empfaenger` / `absender` | Postfachnamen (ohne Domain) für das Formular | `info` / `webseite` |
| `modus` | `server` (anfrage.php + SMTP) · `mailto` · `aus` | `server` |
| `smtpHost`, `smtpPort` | | aus `hoster` |

## Inhalte

| Feld | Bedeutung |
|---|---|
| `module` | `{ "angebote", "team", "preise", "zeiten", "formular", "hinweise", "barrierefreiheit": true/false }` |
| `begriffe` | `{ "angebote": "Leistungen", "angebot": "Leistung", "team": "Team", "person": "Person", "zeiten": "Öffnungszeiten" }` |
| `sektionen` | Startseite als Liste: `["hero", "ueber-uns", "angebote", …]` oder `[{ "typ": "hero", "aktiv": true, "menue": false }]`. Eingebaute Typen: hero, laufband, ueber-uns, angebote, team, zahlen, preise, faq, kontakt, anfahrt, text-bild, karten, schritte, stimmen, galerie, video, aufruf, freitext. Eigene Typen eines Themes fügt man danach im Portal hinzu. |
| `heroZeilen` | große Überschrift, 1–3 Zeilen: `["Handwerk,", "auf das Verlass ist."]` |
| `lead` | Einleitung unter der Überschrift |
| `knopf` | Beschriftung des Hauptknopfs | 
| `angeboteListe` | `[["Titel", "Kurzbeschreibung"], …]` |
| `zeiten` | `[["Montag – Freitag", "08:00 – 17:00 Uhr"], …]` |
| `zeitenMenue` | eigener Menüpunkt für die Öffnungszeiten |
| `seoTitel`, `seoBeschreibung` | Titel und Beschreibung für Suchmaschinen |

## Design

| Feld | Bedeutung |
|---|---|
| `design` | `aurora` · `aurora-hell` · `eigen` (Aurora mit eigener Akzentfarbe) — ein Theme wählt man mit `--theme <kennung>` |
| `designBasis`, `akzent`, `akzentTinte` | nur bei `design: "eigen"` |

## Portal-Zugang

`adminBenutzer` (Anmeldename), `adminAnzeige` (Anzeigename), `sitzungsdauer` (Minuten, Standard 120).
Das Passwort wird **nie** gespeichert — es wird beim ersten Aufruf des Portals festgelegt.
