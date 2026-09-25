<?php
/**
 * Beschreibung aller bearbeitbaren Inhalte (außer den Sektionen der
 * Startseite, die stehen in sektionen.php).
 *
 * Formular, Speichern, Vergleich und Prüfung richten sich allein nach diesen
 * Angaben — es gibt kein Formular, das irgendwo einzeln von Hand gebaut wäre.
 * Ein neues Feld braucht nur einen Eintrag hier (und seine Anzeige im
 * Generator, privat/bauen.php).
 *
 * Welche Bereiche es gibt und wie sie heißen, bestimmt privat/projekt.json —
 * geschrieben vom Assistenten, jederzeit von Hand änderbar.
 *
 * Feldtypen
 *   text      einzeilig (format: email prüft die Adresse)
 *   zeile     Überschrift; ein Zeilenumbruch steuert, wo sie bricht
 *   flaeche   mehrzeiliger Fließtext
 *   zahl      Ganzzahl (min, max)
 *   datum     JJJJ-MM-TT
 *   schalter  ja/nein (standard: Vorgabe für neue Einträge)
 *   auswahl   feste Liste
 *   farbe     Farbwert (#rrggbb)
 *   medium    Datei aus der Medienverwaltung (art: bild | video | pdf)
 *   link      Linkziel: #anker, impressum/, https://…, tel:…, mailto:…
 *   absaetze  Liste einzelner Textabsätze (einzeilig: Eingabezeilen)
 *   liste     Liste gleichartiger Einträge mit eigenen Feldern
 *   abschnitt reine Gliederung, ohne eigene Ebene in der JSON-Datei
 *   objekt    Gliederung mit eigener Ebene (flach: ohne Karte)
 *   hinweis   nur ein Erklärtext im Formular
 *
 * Bereichs-Schlüssel
 *   datei     Name der JSON-Datei in privat/daten ohne Endung
 *   huelle    die Datei ist selbst eine Liste und wird unter diesem Namen eingehängt
 *   modul     nur vorhanden, wenn das Modul im Projekt eingeschaltet ist
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const HILFE_ALT = 'Für blinde Besucher und Suchmaschinen: Was ist auf dem Bild zu sehen? Leer lassen nur bei reiner Dekoration.';

function bereiche(): array
{
    static $b = null;
    if ($b !== null) {
        return $b;
    }

    $org = projekt('organisation');
    $angebote = begriff('angebote');
    $angebot = begriff('angebot');
    $team = begriff('team');
    $person = begriff('person');
    $zeiten = begriff('zeiten');
    $regl = !empty(projekt('reglementiert'));

    $rechtstext = static fn(string $label, bool $zu = true): array => ['typ' => 'objekt', 'label' => $label, 'zu' => $zu, 'felder' => [
        'titel' => ['typ' => 'text', 'label' => 'Überschrift'],
        'stand' => ['typ' => 'text', 'label' => 'Stand', 'hilfe' => 'z. B. Stand: September 2026 — steht klein unter dem Text.'],
        'abschnitte' => ['typ' => 'liste', 'label' => 'Abschnitte', 'knopf' => 'Abschnitt hinzufügen',
                         'zeilenTitel' => 'titel', 'zu' => true, 'felder' => [
            'titel' => ['typ' => 'text', 'label' => 'Überschrift', 'breit' => true],
            'absaetze' => ['typ' => 'absaetze', 'label' => 'Absätze', 'breit' => true],
        ]],
    ]];

    $alle = [

    /* ========================================================= Inhalte === */

    'angebote' => [
        'titel' => $angebote,
        'gruppe' => 'Inhalte',
        'zeichen' => '✚',
        'datei' => 'angebote',
        'huelle' => 'eintraege',
        'modul' => 'angebote',
        'wo' => 'Sektion „' . $angebote . '“ und Auswahl im Kontaktformular',
        'beschreibung' => $angebote . ' mit Beschreibung, Dauer, Preisangabe und Bild.',
        'felder' => [
            'eintraege' => ['typ' => 'liste', 'label' => $angebote, 'knopf' => $angebot . ' hinzufügen',
                            'zeilenTitel' => 'titel', 'zeilenZusatz' => 'zusatz', 'zu' => true, 'felder' => [
                'aktiv' => ['typ' => 'schalter', 'label' => 'Auf der Webseite zeigen', 'standard' => true, 'breit' => true],
                'titel' => ['typ' => 'text', 'label' => 'Titel', 'pflicht' => true],
                'kurz' => ['typ' => 'text', 'label' => 'Kurzbeschreibung', 'hilfe' => 'Eine Zeile unter dem Titel.'],
                'text' => ['typ' => 'flaeche', 'label' => 'Beschreibung', 'breit' => true, 'zeilen' => 4],
                'punkte' => ['typ' => 'absaetze', 'label' => 'Stichpunkte', 'einzeilig' => true, 'knopf' => 'Punkt hinzufügen', 'breit' => true,
                             'hilfe' => 'z. B. „Geeignet für …“ oder „Enthalten ist …“.'],
                'dauer' => ['typ' => 'text', 'label' => 'Dauer / Umfang', 'hilfe' => 'z. B. 45 Min, ca. 2 Tage'],
                'zusatz' => ['typ' => 'text', 'label' => 'Preis oder Zusatzangabe', 'hilfe' => 'z. B. ab 49 €, auf Anfrage'],
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild', 'hilfe' => 'Querformat 16:10, mindestens 1600 Pixel breit.'],
                'bildAlt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => HILFE_ALT],
                'id' => ['typ' => 'text', 'label' => 'Kennung', 'muster' => '[a-z0-9-]+',
                         'hilfe' => 'Kleinbuchstaben, Ziffern und Striche. Wird bei Anfragen mitgeschickt. Leer lassen = automatisch.'],
            ]],
        ],
    ],

    'team' => [
        'titel' => $team,
        'gruppe' => 'Inhalte',
        'zeichen' => '☺',
        'datei' => 'team',
        'huelle' => 'personen',
        'modul' => 'team',
        'wo' => 'Sektion „' . $team . '“',
        'beschreibung' => 'Menschen mit Porträt, Funktion, Schwerpunkt und persönlichem Satz.',
        'felder' => [
            'personen' => ['typ' => 'liste', 'label' => $team, 'knopf' => $person . ' hinzufügen',
                           'zeilenTitel' => 'name', 'zeilenZusatz' => 'rolle', 'zu' => true, 'felder' => [
                'aktiv' => ['typ' => 'schalter', 'label' => 'Auf der Webseite zeigen', 'standard' => true, 'breit' => true],
                'name' => ['typ' => 'text', 'label' => 'Name', 'pflicht' => true],
                'rolle' => ['typ' => 'text', 'label' => 'Funktion', 'hilfe' => 'z. B. Inhaberin, Meister, Empfang'],
                'schwerpunkt' => ['typ' => 'text', 'label' => 'Schwerpunkt'],
                'seit' => ['typ' => 'text', 'label' => 'Dabei', 'hilfe' => 'z. B. seit 2017'],
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Porträtfoto',
                           'hilfe' => 'Hochformat 4:5, mindestens 800 × 1000 Pixel. Ohne Foto erscheinen die Initialen.'],
                'bildAlt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => 'z. B. „Porträt von Laura Neumann“. Leer = automatisch.'],
                'zitat' => ['typ' => 'flaeche', 'label' => 'Persönlicher Satz', 'breit' => true, 'zeilen' => 3],
            ]],
        ],
    ],

    'preise' => [
        'titel' => 'Preise',
        'gruppe' => 'Inhalte',
        'zeichen' => '€',
        'datei' => 'preise',
        'modul' => 'preise',
        'wo' => 'Sektion „Preise“',
        'beschreibung' => 'Hervorgehobene Preiskarten und die Preisliste.',
        'felder' => [
            'hervorgehoben' => ['typ' => 'liste', 'label' => 'Hervorgehobene Preise', 'knopf' => 'Karte hinzufügen',
                                'hilfe' => 'Am besten drei Karten — sie stehen nebeneinander.',
                                'zeilenTitel' => 'titel', 'zeilenZusatz' => 'preis', 'felder' => [
                'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile', 'hilfe' => 'z. B. Am häufigsten gebucht'],
                'titel' => ['typ' => 'text', 'label' => 'Titel', 'pflicht' => true],
                'preis' => ['typ' => 'text', 'label' => 'Preis', 'hilfe' => 'z. B. 42 € oder ab 45 €'],
                'variante' => ['typ' => 'auswahl', 'label' => 'Aussehen', 'optionen' => [
                    'standard' => 'Fläche', 'akzent' => 'Akzent, mit Knopf', 'umriss' => 'Nur Rahmen']],
                'detail' => ['typ' => 'flaeche', 'label' => 'Erläuterung', 'breit' => true, 'zeilen' => 2],
            ]],
            'gruppen' => ['typ' => 'liste', 'label' => 'Preisliste', 'knopf' => 'Gruppe hinzufügen',
                          'zeilenTitel' => 'titel', 'zu' => true, 'felder' => [
                'titel' => ['typ' => 'text', 'label' => 'Gruppe', 'pflicht' => true, 'breit' => true],
                'positionen' => ['typ' => 'liste', 'label' => 'Positionen', 'knopf' => 'Position hinzufügen',
                                 'zeilenTitel' => 'name', 'zeilenZusatz' => 'preis', 'kompakt' => true, 'felder' => [
                    'name' => ['typ' => 'text', 'label' => 'Bezeichnung', 'pflicht' => true, 'breit' => true],
                    'meta' => ['typ' => 'text', 'label' => 'Zusatz', 'hilfe' => 'z. B. 45 Min, je Stück'],
                    'preis' => ['typ' => 'text', 'label' => 'Preis'],
                ]],
            ]],
            'hinweis' => ['typ' => 'text', 'label' => 'Hinweis unter der Preisliste', 'breit' => true],
        ],
    ],

    'zeiten' => [
        'titel' => $zeiten . ' & Anfahrt',
        'gruppe' => 'Inhalte',
        'zeichen' => '◷',
        'datei' => 'zeiten',
        'modul' => 'zeiten',
        'wo' => 'Sektion „Kontakt“ und Fußzeile',
        'beschreibung' => $zeiten . ', telefonische Erreichbarkeit und Tipps zu Anfahrt und Zugang.',
        'felder' => [
            '_urlaub' => ['typ' => 'hinweis', 'text' => 'Urlaub oder kurzfristige Schließzeiten tragen Sie am besten unter „' . begriff('hinweise') . '“ ein — mit Zeitraum, der sich von selbst ein- und ausblendet.'],
            'zeiten' => ['typ' => 'liste', 'label' => $zeiten, 'knopf' => 'Zeile hinzufügen',
                         'zeilenTitel' => 'tage', 'zeilenZusatz' => 'zeit', 'kompakt' => true, 'felder' => [
                'tage' => ['typ' => 'text', 'label' => 'Tage', 'pflicht' => true, 'hilfe' => 'z. B. Montag – Donnerstag'],
                'zeit' => ['typ' => 'text', 'label' => 'Uhrzeit', 'hilfe' => 'z. B. 08:00 – 18:00 Uhr oder „nach Vereinbarung“'],
            ]],
            'hinweis' => ['typ' => 'text', 'label' => 'Hinweis unter den Zeiten', 'breit' => true],
            'telefonzeiten' => ['typ' => 'liste', 'label' => 'Telefonzeiten', 'knopf' => 'Zeile hinzufügen',
                                'hilfe' => 'Bleibt die Liste leer, erscheint der Abschnitt nicht.',
                                'zeilenTitel' => 'tage', 'zeilenZusatz' => 'zeit', 'kompakt' => true, 'felder' => [
                'tage' => ['typ' => 'text', 'label' => 'Tage', 'pflicht' => true],
                'zeit' => ['typ' => 'text', 'label' => 'Uhrzeit'],
            ]],
            'telefonHinweis' => ['typ' => 'text', 'label' => 'Hinweis unter den Telefonzeiten', 'breit' => true],
            'anfahrt' => ['typ' => 'absaetze', 'label' => 'Anfahrt & Zugang', 'einzeilig' => true, 'knopf' => 'Punkt hinzufügen', 'breit' => true,
                          'hilfe' => 'z. B. Parkplätze, Bushaltestelle, barrierefreier Zugang.'],
        ],
    ],

    /* ======================================================= Stammdaten === */

    'stammdaten' => [
        'titel' => $org . ' & Kontakt',
        'gruppe' => $org,
        'zeichen' => '⌂',
        'datei' => 'stammdaten',
        'wo' => 'Kontakt, Fußzeile, Impressum und Datenschutz',
        'beschreibung' => 'Name, Anschrift, Telefon und die Pflichtangaben für Impressum und Datenschutzerklärung.',
        'felder' => [
            'stamm' => ['typ' => 'abschnitt', 'label' => $org, 'felder' => [
                'name' => ['typ' => 'text', 'label' => 'Name', 'pflicht' => true],
                'claim' => ['typ' => 'text', 'label' => 'Zusatz', 'hilfe' => 'z. B. „Tischlerei seit 1987“'],
                'beschreibung' => ['typ' => 'flaeche', 'label' => 'Kurzbeschreibung', 'breit' => true, 'zeilen' => 2,
                                   'hilfe' => 'Ein Satz — erscheint in der Fußzeile und als Beschreibung, wenn keine eigene gesetzt ist.'],
            ]],
            'kontakt' => ['typ' => 'objekt', 'label' => 'Anschrift & Kontakt', 'felder' => [
                'strasse' => ['typ' => 'text', 'label' => 'Straße und Hausnummer', 'breit' => true],
                'plz' => ['typ' => 'text', 'label' => 'PLZ', 'muster' => '[0-9]{4,5}'],
                'ort' => ['typ' => 'text', 'label' => 'Ort'],
                'telefon' => ['typ' => 'text', 'label' => 'Telefon (Anzeige)', 'hilfe' => 'So, wie es auf der Seite steht, z. B. 030 123 45 67'],
                'telefonLink' => ['typ' => 'text', 'label' => 'Telefon (zum Anrufen)', 'muster' => '\+?[0-9]+',
                                  'hilfe' => 'Ohne Leerzeichen, mit Ländervorwahl: +49301234567'],
                'fax' => ['typ' => 'text', 'label' => 'Telefax', 'hilfe' => 'Leer lassen, wenn es keins gibt.'],
                'email' => ['typ' => 'text', 'label' => 'E-Mail', 'format' => 'email'],
            ]],
            'karte' => ['typ' => 'schalter', 'label' => 'Adresse mit Google Maps verlinken', 'breit' => true,
                        'hilfe' => 'Nur ein Link — es wird nichts an Google übertragen, solange niemand klickt.'],
            'social' => ['typ' => 'liste', 'label' => 'Profile & Links', 'knopf' => 'Link hinzufügen', 'kompakt' => true,
                         'hilfe' => 'Erscheinen in der Fußzeile, z. B. Instagram oder ein Online-Terminkalender.',
                         'zeilenTitel' => 'titel', 'zeilenZusatz' => 'url', 'felder' => [
                'titel' => ['typ' => 'text', 'label' => 'Name', 'pflicht' => true],
                'url' => ['typ' => 'link', 'label' => 'Adresse', 'breit' => true],
            ]],
            'recht' => ['typ' => 'objekt', 'label' => 'Angaben für Impressum & Datenschutz',
                        'hilfe' => 'Der Generator setzt diese Angaben in die Rechtstexte ein. Fehlt eine Pflichtangabe, steht sie in der Prüfliste. Keine Rechtsberatung — im Zweifel prüfen lassen.', 'felder' => [
                'inhaber' => ['typ' => 'text', 'label' => 'Inhaberin / Inhaber', 'hilfe' => 'Vollständiger Vor- und Nachname — bei Gesellschaften der Firmenname.'],
                'rechtsform' => ['typ' => 'text', 'label' => 'Rechtsform', 'hilfe' => 'z. B. Einzelunternehmen, GbR, GmbH, e. V.'],
                'vertreten' => ['typ' => 'text', 'label' => 'Vertreten durch', 'hilfe' => 'Nur bei GmbH, UG, e. V. usw.: Geschäftsführung bzw. Vorstand.'],
                'register' => ['typ' => 'text', 'label' => 'Registereintrag', 'hilfe' => 'z. B. Amtsgericht Berlin-Charlottenburg, HRB 123456 — leer lassen, wenn es keinen gibt.'],
                'ustid' => ['typ' => 'text', 'label' => 'USt-IdNr.', 'hilfe' => 'Nur falls vorhanden — sonst verschwindet die Zeile.'],
                'berufsbezeichnung' => ['typ' => 'text', 'label' => 'Berufsbezeichnung' . ($regl ? '' : ' (nur reglementierte Berufe)'),
                                        'hilfe' => 'z. B. Physiotherapeutin, Steuerberater, Handwerksmeister'],
                'verliehenIn' => ['typ' => 'text', 'label' => 'Verliehen in'],
                'kammer' => ['typ' => 'text', 'label' => 'Kammer', 'hilfe' => 'z. B. Handwerkskammer Berlin — falls Pflichtmitglied.'],
                'aufsichtsbehoerde' => ['typ' => 'flaeche', 'label' => 'Zuständige Aufsichtsbehörde', 'breit' => true, 'zeilen' => 2],
                'berufsrecht' => ['typ' => 'flaeche', 'label' => 'Berufsrechtliche Regelungen', 'breit' => true, 'zeilen' => 2],
                'verantwortlich' => ['typ' => 'text', 'label' => 'Verantwortlich nach § 18 MStV', 'hilfe' => 'Leer lassen = Inhaberin/Inhaber.'],
                'hoster' => ['typ' => 'flaeche', 'label' => 'Hosting-Anbieter', 'breit' => true, 'zeilen' => 2,
                             'hilfe' => 'Name und Anschrift des Webhosters — für die Datenschutzerklärung.'],
            ]],
        ],
    ],

    'formular' => [
        'titel' => 'Kontaktformular',
        'gruppe' => $org,
        'zeichen' => '✉',
        'datei' => 'formular',
        'modul' => 'formular',
        'wo' => 'Sektion „Kontakt“',
        'beschreibung' => 'Versandweg, Empfänger und alle Texte der Anfrage.',
        'felder' => [
            'versand' => ['typ' => 'abschnitt', 'label' => 'Versand', 'felder' => [
                'modus' => ['typ' => 'auswahl', 'label' => 'Wie sollen Anfragen ankommen?', 'breit' => true, 'optionen' => [
                    'server' => 'Über den Webserver senden (empfohlen)',
                    'mailto' => 'E-Mail-Programm der Besucher öffnen',
                    'aus' => 'Kein Formular — nur Telefon und E-Mail zeigen',
                ], 'hilfe' => '„Über den Webserver“ braucht PHP auf der Hauptdomain. Die Anfragen werden per E-Mail zugestellt und nicht gespeichert.'],
                'empfaenger' => ['typ' => 'text', 'label' => 'Empfänger-Adresse', 'format' => 'email',
                                 'hilfe' => 'Leer lassen = E-Mail aus „' . $org . ' & Kontakt“.'],
                'absender' => ['typ' => 'text', 'label' => 'Absender-Adresse', 'format' => 'email',
                               'hilfe' => 'Muss zur eigenen Domain gehören (z. B. webseite@ihre-domain.de), sonst landen die Mails im Spam. Leer = webseite@Domain.'],
                'betreff' => ['typ' => 'text', 'label' => 'Betreff der E-Mail', 'breit' => true],
            ]],
            'texte' => ['typ' => 'abschnitt', 'label' => 'Texte im Formular', 'felder' => [
                'ueberschrift' => ['typ' => 'text', 'label' => 'Überschrift'],
                'knopfText' => ['typ' => 'text', 'label' => 'Beschriftung des Knopfes'],
                'hinweis' => ['typ' => 'flaeche', 'label' => 'Hinweis unter der Überschrift', 'breit' => true, 'zeilen' => 2],
                'nachrichtPlatzhalter' => ['typ' => 'text', 'label' => 'Beispieltext im Nachrichtenfeld', 'breit' => true],
                'auswahlLabel' => ['typ' => 'text', 'label' => 'Beschriftung der Auswahl „' . $angebot . '“', 'breit' => true,
                                   'hilfe' => 'Die Einträge kommen automatisch aus „' . $angebote . '“. Leer = keine Auswahl.'],
                'beratungLabel' => ['typ' => 'text', 'label' => 'Zusätzliche Auswahl', 'breit' => true, 'hilfe' => 'z. B. „Ich bin mir nicht sicher – bitte beraten“'],
                'zeiten' => ['typ' => 'absaetze', 'label' => 'Auswahl „Wann passt es Ihnen?“', 'einzeilig' => true, 'knopf' => 'Möglichkeit hinzufügen',
                             'hilfe' => 'Bleibt die Liste leer, entfällt die Frage.'],
                'einwilligung' => ['typ' => 'flaeche', 'label' => 'Text der Einwilligung', 'breit' => true, 'zeilen' => 2],
                'einwilligungZusatz' => ['typ' => 'text', 'label' => 'Zusatz unter der Einwilligung', 'breit' => true],
                'erfolgTitel' => ['typ' => 'text', 'label' => 'Überschrift nach dem Absenden'],
                'erfolgText' => ['typ' => 'flaeche', 'label' => 'Text nach dem Absenden', 'breit' => true, 'zeilen' => 2],
            ]],
        ],
    ],

    'allgemein' => [
        'titel' => 'Webseite allgemein',
        'gruppe' => $org,
        'zeichen' => '◎',
        'datei' => 'allgemein',
        'wo' => 'Jede Seite: Browser-Tab, Google, Kopfleiste, Fußzeile',
        'beschreibung' => 'Adresse, Seitentitel und Beschreibung für Google, Vorschaubild, Kopfleiste und Fußzeile.',
        'felder' => [
            'domain' => ['typ' => 'text', 'label' => 'Adresse der Webseite', 'pflicht' => true, 'breit' => true, 'muster' => 'https?://[^\s/]+',
                         'hilfe' => 'z. B. https://www.ihre-domain.de — ohne Schrägstrich am Ende. Daraus entstehen sitemap.xml und die Vorschau beim Teilen.'],
            'seo' => ['typ' => 'objekt', 'label' => 'Suchmaschinen & Teilen', 'felder' => [
                'titel' => ['typ' => 'text', 'label' => 'Seitentitel', 'breit' => true, 'zaehler' => 60,
                            'hilfe' => 'Steht im Browser-Tab und als Überschrift bei Google. Etwa 55 Zeichen.'],
                'beschreibung' => ['typ' => 'flaeche', 'label' => 'Beschreibung', 'breit' => true, 'zeilen' => 3, 'zaehler' => 160,
                                   'hilfe' => 'Der Text unter dem Google-Treffer. Etwa 150 Zeichen.'],
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Vorschaubild beim Teilen',
                           'hilfe' => 'Erscheint in WhatsApp & Co. 1200 × 630 Pixel.'],
                'indexieren' => ['typ' => 'schalter', 'label' => 'Suchmaschinen dürfen die Seite aufnehmen', 'breit' => true,
                                 'hilfe' => 'Ausgeschaltet steht „noindex“ in jeder Seite — gut, solange die Webseite noch im Aufbau ist.'],
            ]],
            'kopf' => ['typ' => 'objekt', 'label' => 'Kopfleiste & Logo', 'felder' => [
                'logo' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Firmenlogo (oben links)',
                           'hilfe' => 'PNG oder WebP mit durchsichtigem Hintergrund, etwa 600 Pixel breit. Ohne Logo steht dort der Name.'],
                'logoAlt' => ['typ' => 'text', 'label' => 'Beschreibung des Logos', 'hilfe' => 'Leer = Name. Für Screenreader.'],
                'anzeige' => ['typ' => 'auswahl', 'label' => 'Oben links zeigen', 'optionen' => [
                    'logo' => 'Nur das Logo', 'beides' => 'Logo und Name nebeneinander', 'name' => 'Nur den Namen']],
                'logoGroesse' => ['typ' => 'auswahl', 'label' => 'Größe des Logos', 'optionen' => [
                    'klein' => 'Klein', 'mittel' => 'Mittel', 'gross' => 'Groß']],
                'knopfText' => ['typ' => 'text', 'label' => 'Knopf rechts oben'],
                'knopfZiel' => ['typ' => 'link', 'label' => 'Ziel des Knopfes', 'hilfe' => 'z. B. #kontakt oder die Adresse eines Online-Kalenders.'],
                '_menue' => ['typ' => 'hinweis', 'text' => 'Menüpunkte zu Bereichen der Startseite schalten Sie unter „Sektionen“ mit ☰ ein. Hier kommen zusätzliche Menüpunkte dazu — etwa zu einem Online-Kalender oder einer PDF-Speisekarte.'],
                'links' => ['typ' => 'liste', 'label' => 'Zusätzliche Menüpunkte', 'knopf' => 'Menüpunkt hinzufügen', 'kompakt' => true,
                            'zeilenTitel' => 'text', 'zeilenZusatz' => 'ziel', 'felder' => [
                    'text' => ['typ' => 'text', 'label' => 'Beschriftung', 'pflicht' => true],
                    'ziel' => ['typ' => 'link', 'label' => 'Ziel', 'pflicht' => true],
                ]],
            ]],
            'fuss' => ['typ' => 'objekt', 'label' => 'Fußzeile', 'felder' => [
                'text' => ['typ' => 'flaeche', 'label' => 'Text neben dem Namen', 'breit' => true, 'zeilen' => 2],
                'angeboteZeigen' => ['typ' => 'schalter', 'label' => $angebote . ' in der Fußzeile auflisten', 'breit' => true],
            ]],
            '_design' => ['typ' => 'hinweis', 'text' => 'Farben, Schriften und Rundungen stellen Sie unter „Design“ ein — dort lässt sich auch ein Claude Design System hochladen.'],
        ],
    ],

    /* ===================================================== Rechtliches === */

    'rechtliches' => [
        'titel' => 'Impressum & Datenschutz',
        'gruppe' => 'Rechtliches',
        'zeichen' => '§',
        'datei' => 'rechtliches',
        'wo' => '/impressum/, /datenschutz/' . (modul('barrierefreiheit') ? ' und /barrierefreiheit/' : ''),
        'beschreibung' => 'Impressum, Datenschutzerklärung' . (modul('barrierefreiheit') ? ' und Erklärung zur Barrierefreiheit' : '') . '.',
        'hinweis' => 'Anschrift, Inhaber & Co. werden nur unter „' . $org . ' & Kontakt“ gepflegt und hier über Platzhalter eingesetzt: '
            . '{name} {inhaber} {rechtsform} {vertreten} {register} {anschrift} {telefon} {fax} {email} {domain} {ustid} {berufsbezeichnung} {verliehenIn} {kammer} {aufsichtsbehoerde} {berufsrecht} {verantwortlich} {hoster}. '
            . 'Ist ein Platzhalter leer, verschwindet seine Zeile — fehlt eine Pflichtangabe, erscheint der Absatz als offener Punkt. '
            . 'Ein Absatz, der mit LUECKE: beginnt, erscheint als offener Punkt. LINK: Beschriftung | ziel wird zum Verweis, z. B. LINK: Zur Datenschutzerklärung | datenschutz/.',
        'felder' => array_filter([
            'impressum' => $rechtstext('Impressum', false),
            'datenschutz' => $rechtstext('Datenschutzerklärung'),
            'barrierefreiheit' => modul('barrierefreiheit') ? $rechtstext('Erklärung zur Barrierefreiheit') : null,
        ]),
    ],

    ];

    $b = array_filter($alle, static fn($x) => empty($x['modul']) || modul($x['modul']));
    return $b;
}

function bereich(string $id): ?array
{
    return bereiche()[$id] ?? null;
}

function bereiche_nach_gruppe(): array
{
    $raus = [];
    foreach (bereiche() as $id => $b) {
        $raus[$b['gruppe']][$id] = $b;
    }
    return $raus;
}

function bereich_laden(string $id): array
{
    $b = bereich($id);
    $daten = entwurf_laden($b['datei']);
    if (!empty($b['huelle'])) {
        return [$b['huelle'] => $daten];
    }
    return $daten;
}

function bereich_speichern(string $id, array $neu): bool
{
    $b = bereich($id);
    if (!empty($b['huelle'])) {
        return entwurf_speichern($b['datei'], array_values($neu[$b['huelle']] ?? []));
    }
    return entwurf_speichern($b['datei'], $neu);
}

function bereich_geaendert(string $id): bool
{
    $b = bereich($id);
    return $b && entwurf_offen($b['datei']);
}

/** Lesbarer Name einer Datendatei für Übersicht, Vergleich und Protokoll. */
function datei_titel(string $datei): string
{
    $feste = ['startseite' => 'Sektionen der Startseite', 'hinweise' => begriff('hinweise')];
    if (isset($feste[$datei])) {
        return $feste[$datei];
    }
    foreach (bereiche() as $b) {
        if ($b['datei'] === $datei) {
            return $b['titel'];
        }
    }
    return $datei === 'allgemein' ? 'Webseite allgemein' : $datei;
}

/** Bearbeitungsseite zu einer Datendatei. */
function datei_url(string $datei): string
{
    if ($datei === 'startseite') {
        return url_zu('sektionen');
    }
    if ($datei === 'hinweise') {
        return url_zu('hinweise');
    }
    foreach (bereiche() as $id => $b) {
        if ($b['datei'] === $datei) {
            return url_zu('bereich', ['id' => $id]);
        }
    }
    return url_zu('start');
}

/** Vorschläge für Linkfelder: Sektionen, Rechtstexte, Telefon, E-Mail. */
function ziel_vorschlaege(): array
{
    $raus = [];
    foreach (sektionen_entwurf() as $s) {
        $a = (string) ($s['anker'] ?? '');
        if ($a !== '') {
            $raus['#' . $a] = sektion_titel($s) . (empty($s['aktiv']) ? ' (ausgeblendet)' : '');
        }
    }
    $raus['impressum/'] = 'Impressum';
    $raus['datenschutz/'] = 'Datenschutzerklärung';
    if (modul('barrierefreiheit')) {
        $raus['barrierefreiheit/'] = 'Barrierefreiheit';
    }
    $k = entwurf_laden('stammdaten')['kontakt'] ?? [];
    if (!empty($k['telefonLink'])) {
        $raus['tel:' . $k['telefonLink']] = 'Anrufen';
    }
    if (!empty($k['email'])) {
        $raus['mailto:' . $k['email']] = 'E-Mail schreiben';
    }
    return $raus;
}
