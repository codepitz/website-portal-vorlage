# {{NAME}} — Webseite mit Adminportal

Erzeugt am {{DATUM}} aus der Website-Portal-Vorlage.

| | |
|---|---|
| Webseite | {{WEBSEITE}} |
| Portal | {{PORTAL}} |
| Hoster | {{HOSTER}} (SFTP: `{{SFTP_HOST}}`, Port {{SFTP_PORT}}) |
| Design | `{{DESIGN}}` — mitgeliefert: {{THEMES}} |
| Portal-Konto | {{ADMIN}} |

## Aufbau

```
webseite/   Docroot von {{DOMAIN}} — schreibt der Generator (nicht von Hand ändern)
portal/     Docroot von {{PORTAL_HOST}} — das Adminportal (PHP 8.1+, ohne Datenbank)
privat/     außerhalb jedes Docroots: Inhalte (daten/), Designs & Themes (design/), Generator (bauen.php)
themes/     Quellen der Theme-Pakete (theme.json, CSS, Vorlagen) — im Portal als ZIP hochladbar
```

Das Portal bleibt immer gleich. Wie die Webseite aussieht, bestimmt das gewählte Design:
ein Theme-Paket kann Kopfleiste, Sektionen, Fußzeile und eigene Sektionstypen komplett neu gestalten,
ohne dass sich an den Inhalten etwas ändert. Themes wechseln: Portal › Design › Verwenden › Veröffentlichen.

## Lokal ausprobieren

```bash
php -S localhost:8081 -t portal     # Portal — beim ersten Aufruf Konto anlegen
php -S localhost:8082 -t webseite   # Webseite — nach „Veröffentlichen“ im Portal
```

Ohne Terminal: Doppelklick auf `Offline ausprobieren (Mac).command` bzw. `(Windows).bat`.

## Online bringen

Schritt für Schritt mit Werten für {{HOSTER}}: **ANLEITUNG.html** öffnen.

Automatisch per GitHub Actions (`.github/workflows/deploy.yml`):

1. Unter *Settings › Secrets and variables › Actions* die Secrets `SFTP_HOST`, `SFTP_BENUTZER`, `SFTP_PASSWORT` anlegen
   (optional die Variablen `SFTP_PFAD`, `SFTP_PORT`).
2. Jeder Push auf `main` lädt Portal, Generator, Vorlagen und Themes hoch.
3. Inhalte, Konten, Medien und die erzeugte Webseite auf dem Server werden **nie** überschrieben —
   sie entstehen beim allerersten Upload und gehören danach dem Portal.
4. Beim Hoster: {{DOMAIN}} → Ordner `/webseite`, {{PORTAL_HOST}} → Ordner `/portal`, SSL für beide.
5. {{PORTAL}} öffnen, Konto anlegen, Inhalte prüfen, veröffentlichen.

## Theme ändern oder neu hochladen

- Kleine Anpassungen (Farben, Schriften): im Portal unter *Design › Bearbeiten*.
- Aufbau ändern: Dateien in `themes/<kennung>/` bearbeiten, prüfen mit
  `php privat/theme-werkzeug.php installieren themes/<kennung> --kennung=<kennung>` und
  `php privat/theme-werkzeug.php vorschau <kennung> /tmp/vorschau`, dann entweder
  das Theme als ZIP im Portal hochladen oder den Workflow „Deploy“ mit *Themes aktualisieren* starten.
