<?php
/**
 * Die Sektionen der Startseite.
 *
 * Die Startseite ist eine Liste von Sektionen (startseite.json). Jede hat
 * einen Typ; der Typ bestimmt, welche Felder es gibt und wie die App sie
 * darstellt. Die fest eingebauten Typen (Kopfbereich, Angebote, Team …)
 * gibt es je einmal. Die Bausteine darunter lassen sich beliebig oft
 * hinzufügen — so entstehen neue Bereiche, ohne dass jemand Code anfasst.
 *
 * Neuen Typ aufnehmen: Eintrag in SEKTION_TYPEN_ALLE, Felder in sektion_felder(),
 * Vorlage in sektion_vorlage() — und eine Darstellung in privat/bauen.php (ws_sektion_…).
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const FARBEN_ALLE = ['standard', 'flaeche', 'akzent'];

/**
 * typ => [Name, Beschreibung, einmalig, Zeichen, Gruppe, Modul]
 *
 * „fest“ sind Bereiche, die ihre Inhalte aus einem eigenen Bereich holen
 * (Leistungen, Team, Preise …) oder die es nur einmal gibt. „baustein“ lässt
 * sich beliebig oft hinzufügen — so entstehen neue Bereiche, ohne dass jemand
 * Code anfasst. Ist das Modul im Projekt ausgeschaltet, fehlt der Typ.
 */
const SEKTION_TYPEN_ALLE = [
    'hero'       => ['Kopfbereich', 'Große Überschrift mit Aurora-Verlauf, Video oder Bild im Hintergrund.', true, '▲', 'fest', ''],
    'laufband'   => ['Laufband', 'Durchlaufende Begriffe unter dem Kopfbereich.', true, '⇄', 'fest', ''],
    'ueber-uns'  => ['Über uns', 'Leitsatz, Bild und Werte-Kacheln.', true, '♥', 'fest', ''],
    'angebote'   => ['{angebote}', 'Die {angebote} als Karten — Inhalte unter „{angebote}“.', true, '✚', 'fest', 'angebote'],
    'team'       => ['{team}', 'Die Menschen dahinter — Personen unter „{team}“.', true, '☺', 'fest', 'team'],
    'zahlen'     => ['Zahlen', 'Kennzahlen, die beim Scrollen hochzählen.', false, '#', 'fest', ''],
    'preise'     => ['Preise', 'Preiskarten und Preisliste — Inhalte unter „Preise“.', true, '€', 'fest', 'preise'],
    'faq'        => ['Häufige Fragen', 'Fragen und Antworten zum Aufklappen.', false, '?', 'fest', ''],
    'kontakt'    => ['Kontakt', 'Kontaktdaten, {zeiten} und Anfrageformular.', true, '✉', 'fest', ''],
    'anfahrt'    => ['Anfahrt', 'Karte mit dem Standort, Anschrift und Hinweisen zum Weg.', true, '⚑', 'fest', ''],
    'text-bild'  => ['Text mit Bild', 'Überschrift, Fließtext und ein Bild links oder rechts.', false, '▤', 'baustein', ''],
    'karten'     => ['Karten', 'Zwei bis vier Kacheln nebeneinander, optional mit Bild und Link.', false, '▦', 'baustein', ''],
    'schritte'   => ['Ablauf in Schritten', 'Nummerierte Schritte, z. B. „So läuft es ab“.', false, '①', 'baustein', ''],
    'stimmen'    => ['Kundenstimmen', 'Zitate zufriedener Kundinnen und Kunden.', false, '❝', 'baustein', ''],
    'galerie'    => ['Bildergalerie', 'Mehrere Fotos, z. B. Räume oder Arbeiten.', false, '▣', 'baustein', ''],
    'video'      => ['Video', 'Ein Video vom eigenen Server mit Überschrift und Beschreibung.', false, '▶', 'baustein', ''],
    'aufruf'     => ['Aufruf-Band', 'Auffälliges Band mit kurzem Text und Knöpfen.', false, '➜', 'baustein', ''],
    'freitext'   => ['Freier Text', 'Überschrift und Absätze, z. B. für Stellenangebote.', false, '¶', 'baustein', ''],
];

const SEKTION_FARBEN = ['standard' => 'Grundfläche', 'flaeche' => 'Abgesetzte Fläche', 'akzent' => 'Akzent (hervorgehoben)'];

/** Die Typen dieses Projekts — mit eingesetzten Begriffen, ohne ausgeschaltete Module. */
function sektion_typen(): array
{
    static $t = null;
    if ($t !== null) {
        return $t;
    }
    $ersetzen = ['{angebote}' => begriff('angebote'), '{team}' => begriff('team'), '{zeiten}' => begriff('zeiten')];
    $t = [];
    foreach (SEKTION_TYPEN_ALLE as $typ => [$name, $text, $einmalig, $zeichen, $gruppe, $modul]) {
        if ($modul !== '' && !modul($modul)) {
            continue;
        }
        $t[$typ] = [strtr($name, $ersetzen), strtr($text, $ersetzen), $einmalig, $zeichen, $gruppe];
    }
    // Eigene Sektionstypen des im Entwurf gewählten Designs (Theme-Paket)
    foreach (sektion_theme()['sektionen'] ?? [] as $typ => $info) {
        if (!isset($t[$typ])) {
            $t[$typ] = [$info['name'], $info['beschreibung'], $info['einmalig'], h($info['zeichen']), 'theme'];
        }
    }
    return $t;
}

/** Theme des im Entwurf gewählten Designs — oder null. */
function sektion_theme(): ?array
{
    static $theme = false;
    if ($theme === false) {
        $theme = function_exists('theme_aktiv') ? theme_aktiv() : null;
    }
    return $theme;
}

function sektion_typ_name(string $typ): string
{
    return sektion_typen()[$typ][0] ?? $typ;
}

function knopf_objekt(string $label, string $hilfe = ''): array
{
    return ['typ' => 'objekt', 'label' => $label, 'hilfe' => $hilfe ?: 'Nur wenn Beschriftung und Ziel ausgefüllt sind, erscheint der Knopf.', 'felder' => [
        'text' => ['typ' => 'text', 'label' => 'Beschriftung'],
        'ziel' => ['typ' => 'link', 'label' => 'Ziel'],
    ]];
}

/** Überschriften-Block, den fast alle Sektionen teilen. */
function sektion_kopf(bool $mitLead = true): array
{
    $f = [
        'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile', 'hilfe' => 'Steht klein über der Überschrift, z. B. „Über uns“.'],
        'titel' => ['typ' => 'text', 'label' => 'Überschrift', 'breit' => true],
        'titelLeise' => ['typ' => 'text', 'label' => 'Überschrift, zweiter Teil', 'breit' => true,
                         'hilfe' => 'Erscheint in einer neuen Zeile in der Akzentfarbe.'],
    ];
    if ($mitLead) {
        $f['lead'] = ['typ' => 'flaeche', 'label' => 'Einleitung', 'breit' => true, 'zeilen' => 3];
    }
    return $f;
}

function sektion_felder(string $typ): array
{
    $org = projekt('organisation');
    $einstellungen = [
        'aktiv' => ['typ' => 'schalter', 'label' => 'Auf der Webseite zeigen', 'breit' => true],
        'anker' => ['typ' => 'text', 'label' => 'Adresse (Anker)', 'pflicht' => true, 'muster' => '[a-z0-9-]+',
                    'hilfe' => 'Kleinbuchstaben, Ziffern, Striche. Knöpfe verlinken darauf mit #adresse.'],
        'menue' => ['typ' => 'objekt', 'flach' => true, 'felder' => [
            'zeigen' => ['typ' => 'schalter', 'label' => 'Im Menü oben zeigen', 'hilfe' => 'Dann springt ein Menüpunkt oben auf der Webseite direkt zu diesem Bereich.'],
            'label' => ['typ' => 'text', 'label' => 'Beschriftung im Menü', 'hilfe' => 'Kurz halten, z. B. „Anfahrt“. Leer = Name des Bereichs.'],
        ]],
    ];
    if (!in_array($typ, ['hero', 'laufband'], true)) {
        $einstellungen['farbe'] = ['typ' => 'auswahl', 'label' => 'Hintergrund', 'optionen' => SEKTION_FARBEN];
    }

    $inhalt = match ($typ) {
        'hero' => [
            'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile', 'breit' => true],
            'zeilen' => ['typ' => 'absaetze', 'label' => 'Große Überschrift', 'einzeilig' => true, 'knopf' => 'Zeile hinzufügen', 'breit' => true,
                         'hilfe' => 'Jede Zeile erscheint für sich; ab der zweiten in der Akzentfarbe. Zwei kurze Zeilen wirken am besten.'],
            'lead' => ['typ' => 'flaeche', 'label' => 'Einleitung', 'breit' => true, 'zeilen' => 3],
            'knopf1' => knopf_objekt('Erster Knopf (Akzent)'),
            'knopf2' => knopf_objekt('Zweiter Knopf (zurückhaltend)'),
            'hintergrund' => ['typ' => 'abschnitt', 'label' => 'Hintergrund', 'felder' => [
                'medium' => ['typ' => 'auswahl', 'label' => 'Was liegt im Hintergrund?', 'breit' => true, 'optionen' => [
                    'verlauf' => 'Aurora-Verlauf aus dem Design (Standard)', 'ruhig' => 'Nur die Grundfläche', 'video' => 'Video', 'bild' => 'Bild']],
                'video' => ['typ' => 'medium', 'art' => 'video', 'label' => 'Hintergrundvideo',
                            'hilfe' => 'MP4 (H.264) oder WebM, ohne Ton, 10–20 Sekunden als Schleife, möglichst unter 15 MB.'],
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Hintergrundbild',
                           'hilfe' => 'Bei „Bild“ der Hintergrund, bei „Video“ das Standbild bis zum Start. Querformat, mindestens 2000 Pixel breit.'],
                'abdunkeln' => ['typ' => 'zahl', 'label' => 'Abdunkeln in Prozent', 'min' => 0, 'max' => 90,
                                'hilfe' => 'Damit die Schrift auf Bild oder Video lesbar bleibt. 40 ist ein guter Anfang.'],
            ]],
            'news' => ['typ' => 'objekt', 'label' => 'Kasten „Neuigkeit“', 'felder' => [
                'zeigen' => ['typ' => 'schalter', 'label' => 'Kasten zeigen', 'breit' => true],
                'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile'],
                'ziel' => ['typ' => 'link', 'label' => 'Ziel beim Klick'],
                'titel' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 2],
            ]],
        ],
        'laufband' => [
            'begriffe' => ['typ' => 'absaetze', 'label' => 'Begriffe', 'einzeilig' => true, 'knopf' => 'Begriff hinzufügen', 'breit' => true],
        ],
        'ueber-uns' => [
            'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile'],
            'statement' => ['typ' => 'flaeche', 'label' => 'Leitsatz', 'breit' => true, 'zeilen' => 3, 'hilfe' => 'Erscheint sehr groß.'],
            'absaetze' => ['typ' => 'absaetze', 'label' => 'Text darunter', 'breit' => true],
            'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild', 'hilfe' => 'Hochformat oder quadratisch.'],
            'bildAlt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => HILFE_ALT],
            'werte' => ['typ' => 'liste', 'label' => 'Werte-Kacheln', 'knopf' => 'Kachel hinzufügen', 'hilfe' => 'Drei oder vier Kacheln passen am besten.',
                        'zeilenTitel' => 'titel', 'kompakt' => true, 'felder' => [
                'titel' => ['typ' => 'text', 'label' => 'Titel', 'pflicht' => true],
                'text' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 2],
            ]],
            'knopf' => knopf_objekt('Knopf'),
        ],
        'angebote' => sektion_kopf() + [
            '_quelle' => ['typ' => 'hinweis', 'text' => 'Die Einträge selbst pflegen Sie unter „' . begriff('angebote') . '“.'],
            'darstellung' => ['typ' => 'auswahl', 'label' => 'Darstellung', 'optionen' => ['karten' => 'Karten nebeneinander', 'liste' => 'Liste zum Aufklappen']],
            'knopf1' => knopf_objekt('Erster Knopf'),
            'knopf2' => knopf_objekt('Zweiter Knopf'),
        ],
        'team' => sektion_kopf() + [
            '_quelle' => ['typ' => 'hinweis', 'text' => 'Die Personen selbst pflegen Sie unter „' . begriff('team') . '“.'],
        ],
        'zahlen' => sektion_kopf() + [
            'eintraege' => ['typ' => 'liste', 'label' => 'Kennzahlen', 'knopf' => 'Zahl hinzufügen', 'hilfe' => 'Vier Zahlen passen am besten.',
                            'zeilenTitel' => 'label', 'zeilenZusatz' => 'wert', 'kompakt' => true, 'felder' => [
                'wert' => ['typ' => 'zahl', 'label' => 'Zahl', 'pflicht' => true],
                'suffix' => ['typ' => 'text', 'label' => 'Zeichen danach', 'hilfe' => 'z. B. + oder %'],
                'einheit' => ['typ' => 'text', 'label' => 'Einheit', 'hilfe' => 'z. B. Jahre'],
                'label' => ['typ' => 'text', 'label' => 'Bezeichnung', 'pflicht' => true],
                'text' => ['typ' => 'text', 'label' => 'Erläuterung', 'breit' => true],
            ]],
        ],
        'preise' => sektion_kopf() + [
            '_quelle' => ['typ' => 'hinweis', 'text' => 'Preiskarten und Preisliste pflegen Sie unter „Preise“.'],
            'knopf' => knopf_objekt('Knopf in der Akzent-Preiskarte'),
        ],
        'faq' => sektion_kopf(false) + [
            'fragen' => ['typ' => 'liste', 'label' => 'Fragen', 'knopf' => 'Frage hinzufügen', 'zeilenTitel' => 'frage', 'felder' => [
                'frage' => ['typ' => 'text', 'label' => 'Frage', 'pflicht' => true, 'breit' => true],
                'antwort' => ['typ' => 'flaeche', 'label' => 'Antwort', 'breit' => true, 'zeilen' => 4],
            ]],
        ],
        'kontakt' => sektion_kopf() + [
            'zeitenMenue' => ['typ' => 'objekt', 'label' => begriff('zeiten') . ' im Menü', 'felder' => [
                'zeigen' => ['typ' => 'schalter', 'label' => 'Eigenen Menüpunkt „' . begriff('zeiten') . '“ zeigen', 'breit' => true,
                             'hilfe' => 'Die Zeiten stehen im Kontaktbereich. Mit diesem Schalter springt ein eigener Menüpunkt direkt dorthin.'],
                'label' => ['typ' => 'text', 'label' => 'Beschriftung im Menü', 'hilfe' => 'Leer = „' . begriff('zeiten') . '“.'],
            ]],
            '_quelle' => ['typ' => 'hinweis', 'text' => 'Telefon und Anschrift kommen aus „' . $org . ' & Kontakt“'
                . (modul('zeiten') ? ', die Zeiten aus „' . begriff('zeiten') . ' & Anfahrt“' : '')
                . (modul('formular') ? ', das Formular aus „Kontaktformular“' : '') . '.'],
        ],
        'anfahrt' => sektion_kopf() + [
            '_quelle' => ['typ' => 'hinweis', 'text' => 'Ist keine eigene Adresse eingetragen, zeigt die Karte die Anschrift aus „' . $org . ' & Kontakt“.'],
            'adresse' => ['typ' => 'text', 'label' => 'Adresse auf der Karte', 'breit' => true,
                          'hilfe' => 'Nur ausfüllen, wenn die Karte etwas anderes zeigen soll als die Anschrift — z. B. den Parkplatz.'],
            'zoom' => ['typ' => 'zahl', 'label' => 'Zoomstufe', 'min' => 3, 'max' => 20, 'hilfe' => '16 zeigt die Straße, 13 den ganzen Ort.'],
            'laden' => ['typ' => 'auswahl', 'label' => 'Wann wird die Karte geladen?', 'breit' => true, 'optionen' => [
                'klick' => 'Erst auf Klick — bis dahin nur ein Vorschaufeld (empfohlen)',
                'sofort' => 'Sofort beim Aufruf der Seite']],
            '_datenschutz' => ['typ' => 'hinweis', 'text' => 'Die Karte kommt von Google. Sobald sie lädt, erfährt Google die IP-Adresse der Besucher. '
                . 'Bei „Sofort“ geschieht das ungefragt — dann gehört ein Abschnitt zu Google Maps in die Datenschutzerklärung.'],
            'punkte' => ['typ' => 'absaetze', 'label' => 'Hinweise zum Weg', 'einzeilig' => true, 'knopf' => 'Punkt hinzufügen', 'breit' => true,
                         'hilfe' => 'Bleibt die Liste leer, kommen die Punkte aus „Anfahrt & Zugang“.'],
        ],
        'text-bild' => sektion_kopf(false) + [
            'absaetze' => ['typ' => 'absaetze', 'label' => 'Text', 'breit' => true],
            'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild'],
            'bildAlt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => HILFE_ALT],
            'bildSeite' => ['typ' => 'auswahl', 'label' => 'Bild steht', 'optionen' => ['rechts' => 'rechts', 'links' => 'links']],
            'knopf' => knopf_objekt('Knopf'),
        ],
        'karten' => sektion_kopf() + [
            'spalten' => ['typ' => 'auswahl', 'label' => 'Karten je Zeile', 'optionen' => ['2' => 'zwei', '3' => 'drei', '4' => 'vier']],
            'karten' => ['typ' => 'liste', 'label' => 'Karten', 'knopf' => 'Karte hinzufügen', 'zeilenTitel' => 'titel', 'zu' => true, 'felder' => [
                'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile'],
                'titel' => ['typ' => 'text', 'label' => 'Titel', 'pflicht' => true],
                'text' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 3],
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild (optional)'],
                'bildAlt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => HILFE_ALT],
                'link' => knopf_objekt('Link', 'Optional — erscheint als Textlink unter der Karte.'),
            ]],
        ],
        'schritte' => sektion_kopf() + [
            'schritte' => ['typ' => 'liste', 'label' => 'Schritte', 'knopf' => 'Schritt hinzufügen', 'zeilenTitel' => 'titel', 'felder' => [
                'titel' => ['typ' => 'text', 'label' => 'Titel', 'pflicht' => true, 'breit' => true],
                'text' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 2],
            ]],
        ],
        'stimmen' => sektion_kopf() + [
            'stimmen' => ['typ' => 'liste', 'label' => 'Stimmen', 'knopf' => 'Stimme hinzufügen', 'zeilenTitel' => 'name', 'felder' => [
                'zitat' => ['typ' => 'flaeche', 'label' => 'Zitat', 'pflicht' => true, 'breit' => true, 'zeilen' => 3],
                'name' => ['typ' => 'text', 'label' => 'Name', 'pflicht' => true, 'hilfe' => 'Nur mit Einverständnis — z. B. „Anna K.“'],
                'zusatz' => ['typ' => 'text', 'label' => 'Zusatz', 'hilfe' => 'z. B. „Kundin seit 2021“'],
            ]],
            '_echt' => ['typ' => 'hinweis', 'text' => 'Nur echte Stimmen mit Einverständnis der Person veröffentlichen — erfundene Bewertungen sind wettbewerbswidrig.'],
        ],
        'galerie' => sektion_kopf() + [
            'bilder' => ['typ' => 'liste', 'label' => 'Bilder', 'knopf' => 'Bild hinzufügen', 'zeilenTitel' => 'beschriftung', 'kompakt' => true, 'felder' => [
                'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild', 'pflicht' => true],
                'alt' => ['typ' => 'text', 'label' => 'Bildbeschreibung', 'hilfe' => HILFE_ALT],
                'beschriftung' => ['typ' => 'text', 'label' => 'Bildunterschrift (optional)', 'breit' => true],
            ]],
        ],
        'video' => sektion_kopf() + [
            'video' => ['typ' => 'medium', 'art' => 'video', 'label' => 'Video', 'pflicht' => true,
                        'hilfe' => 'MP4 (H.264) oder WebM. Das Video liegt auf dem eigenen Server — kein YouTube, keine Übertragung an Dritte.'],
            'poster' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Vorschaubild', 'hilfe' => 'Erscheint, bis das Video startet.'],
            'wiedergabe' => ['typ' => 'auswahl', 'label' => 'Wiedergabe', 'optionen' => [
                'steuerung' => 'Mit Bedienleiste, startet auf Klick', 'schleife' => 'Stumm in Schleife, startet von selbst']],
            'beschreibung' => ['typ' => 'flaeche', 'label' => 'Beschreibung des Videoinhalts', 'breit' => true, 'zeilen' => 3,
                               'hilfe' => 'Für Menschen, die das Video nicht sehen oder hören können.'],
        ],
        'aufruf' => [
            'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile'],
            'titel' => ['typ' => 'zeile', 'label' => 'Überschrift', 'pflicht' => true, 'breit' => true],
            'text' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 2],
            'knopf1' => knopf_objekt('Erster Knopf'),
            'knopf2' => knopf_objekt('Zweiter Knopf'),
        ],
        'freitext' => sektion_kopf(false) + [
            'absaetze' => ['typ' => 'absaetze', 'label' => 'Absätze', 'breit' => true],
        ],
        default => sektion_theme()['sektionen'][$typ]['felder'] ?? [],
    };

    // Ein Theme kann eingebauten Typen Felder hinzufügen und Varianten anbieten
    $theme = sektion_theme();
    if (!empty($theme['varianten'][$typ])) {
        $einstellungen['variante'] = ['typ' => 'auswahl', 'label' => 'Variante (Design „' . h((string) ($theme['name'] ?? $theme['id'])) . '“)',
            'optionen' => ['' => 'Standard'] + $theme['varianten'][$typ],
            'hilfe' => 'Wie diese Sektion im gewählten Design aufgebaut ist. Andere Designs ignorieren die Wahl.'];
    }
    if (!empty($theme['zusatzfelder'][$typ])) {
        $inhalt['_theme'] = ['typ' => 'abschnitt', 'label' => 'Zusätzlich im Design „' . h((string) ($theme['name'] ?? $theme['id'])) . '“',
            'felder' => $theme['zusatzfelder'][$typ]];
    }
    if (!isset(SEKTION_TYPEN_ALLE[$typ]) && !isset($theme['sektionen'][$typ])) {
        $inhalt = ['_fremd' => ['typ' => 'hinweis', 'text' => 'Diese Sektion gehört zu einem anderen Design und wird im gewählten Design nicht angezeigt. '
            . 'Ihre Inhalte bleiben erhalten — sie erscheinen wieder, sobald das passende Design gewählt ist.']];
    }

    return [
        '_einstellungen' => ['typ' => 'abschnitt', 'label' => 'Einstellungen der Sektion', 'felder' => $einstellungen],
        '_inhalt' => ['typ' => 'abschnitt', 'label' => 'Inhalt', 'felder' => $inhalt],
    ];
}

/** Startwerte für eine neu hinzugefügte Sektion — lieber sinnvoller Beispieltext als leere Felder. */
function sektion_vorlage(string $typ): array
{
    $knopf = ['text' => 'Kontakt aufnehmen', 'ziel' => '#kontakt'];
    $leer = ['text' => '', 'ziel' => ''];

    return match ($typ) {
        'hero' => ['eyebrow' => (string) projekt('name'), 'zeilen' => ['Eine klare Botschaft', 'in zwei Zeilen.'], 'lead' => '',
                   'knopf1' => $knopf, 'knopf2' => $leer, 'medium' => 'verlauf', 'video' => '', 'bild' => '', 'abdunkeln' => 40,
                   'news' => ['zeigen' => false, 'eyebrow' => '', 'titel' => '', 'ziel' => '']],
        'laufband' => ['begriffe' => ['Erster Begriff', 'Zweiter Begriff', 'Dritter Begriff']],
        'ueber-uns' => ['farbe' => 'standard', 'eyebrow' => 'Über uns', 'statement' => '', 'absaetze' => [], 'bild' => '', 'bildAlt' => '', 'werte' => [], 'knopf' => $leer],
        'angebote' => ['farbe' => 'flaeche', 'eyebrow' => begriff('angebote'), 'titel' => 'Was wir', 'titelLeise' => 'für Sie tun.', 'lead' => '',
                       'darstellung' => 'karten', 'knopf1' => $knopf, 'knopf2' => $leer],
        'team' => ['farbe' => 'standard', 'eyebrow' => begriff('team'), 'titel' => 'Menschen, die', 'titelLeise' => 'zuhören.', 'lead' => ''],
        'zahlen' => ['farbe' => 'akzent', 'eyebrow' => 'In Zahlen', 'titel' => 'Überschrift', 'titelLeise' => '', 'lead' => '',
                     'eintraege' => [['wert' => 10, 'suffix' => '', 'einheit' => 'Jahre', 'label' => 'Erfahrung', 'text' => '']]],
        'preise' => ['farbe' => 'flaeche', 'eyebrow' => 'Preise', 'titel' => 'Klar und', 'titelLeise' => 'transparent.', 'lead' => '', 'knopf' => $knopf],
        'faq' => ['farbe' => 'standard', 'eyebrow' => 'Gut zu wissen', 'titel' => 'Häufige', 'titelLeise' => 'Fragen.',
                  'fragen' => [['frage' => 'Neue Frage?', 'antwort' => 'Antwort.']]],
        'kontakt' => ['farbe' => 'flaeche', 'zeitenMenue' => ['zeigen' => false, 'label' => ''], 'eyebrow' => 'Kontakt', 'titel' => 'Wir freuen uns', 'titelLeise' => 'auf Sie.', 'lead' => ''],
        'anfahrt' => ['farbe' => 'standard', 'eyebrow' => 'Anfahrt', 'titel' => 'So finden Sie', 'titelLeise' => 'zu uns.',
                      'lead' => 'Auf der Karte sehen Sie den genauen Standort.', 'adresse' => '', 'zoom' => 16, 'laden' => 'klick', 'punkte' => []],
        'text-bild' => ['farbe' => 'standard', 'eyebrow' => 'Kleinzeile', 'titel' => 'Neue Überschrift', 'titelLeise' => '',
                        'absaetze' => ['Hier steht Ihr Text.'], 'bild' => '', 'bildAlt' => '', 'bildSeite' => 'rechts', 'knopf' => $leer],
        'karten' => ['farbe' => 'flaeche', 'eyebrow' => 'Kleinzeile', 'titel' => 'Neue Überschrift', 'titelLeise' => '', 'lead' => '', 'spalten' => '3',
                     'karten' => [
                         ['eyebrow' => '', 'titel' => 'Erste Karte', 'text' => 'Kurzer Text.', 'bild' => '', 'bildAlt' => '', 'link' => $leer],
                         ['eyebrow' => '', 'titel' => 'Zweite Karte', 'text' => 'Kurzer Text.', 'bild' => '', 'bildAlt' => '', 'link' => $leer],
                         ['eyebrow' => '', 'titel' => 'Dritte Karte', 'text' => 'Kurzer Text.', 'bild' => '', 'bildAlt' => '', 'link' => $leer],
                     ]],
        'schritte' => ['farbe' => 'standard', 'eyebrow' => 'Ablauf', 'titel' => 'So läuft es', 'titelLeise' => 'ab.', 'lead' => '',
                       'schritte' => [
                           ['titel' => 'Kontakt aufnehmen', 'text' => 'Telefonisch oder über das Formular.'],
                           ['titel' => 'Beratung', 'text' => 'Wir besprechen in Ruhe, was Sie brauchen.'],
                           ['titel' => 'Umsetzung', 'text' => 'Gründlich, pünktlich und mit Zeit für Fragen.'],
                       ]],
        'stimmen' => ['farbe' => 'flaeche', 'eyebrow' => 'Stimmen', 'titel' => 'Was andere', 'titelLeise' => 'über uns sagen.', 'lead' => '', 'stimmen' => []],
        'galerie' => ['farbe' => 'standard', 'eyebrow' => 'Einblicke', 'titel' => 'Ein Blick', 'titelLeise' => 'hinter die Kulissen.', 'lead' => '', 'bilder' => []],
        'video' => ['farbe' => 'akzent', 'eyebrow' => 'Film', 'titel' => 'Neue Überschrift', 'titelLeise' => '', 'lead' => '',
                    'video' => '', 'poster' => '', 'wiedergabe' => 'steuerung', 'beschreibung' => ''],
        'aufruf' => ['farbe' => 'akzent', 'eyebrow' => '', 'titel' => 'Noch Fragen?', 'text' => 'Wir beraten Sie gern persönlich.',
                     'knopf1' => $knopf, 'knopf2' => $leer],
        'freitext' => ['farbe' => 'standard', 'eyebrow' => 'Kleinzeile', 'titel' => 'Neue Überschrift', 'titelLeise' => '', 'absaetze' => ['Hier steht Ihr Text.']],
        default => ['farbe' => 'standard'] + (sektion_theme()['sektionen'][$typ]['vorlage'] ?? []),
    };
}

/* ------------------------------------------------------------ Zugriff --- */

function sektionen_entwurf(): array
{
    $liste = entwurf_laden('startseite')['sektionen'] ?? [];
    return is_array($liste) ? array_values($liste) : [];
}

function sektionen_speichern(array $liste): bool
{
    $ganz = entwurf_laden('startseite');
    $ganz['sektionen'] = array_values($liste);
    return entwurf_speichern('startseite', $ganz);
}

function sektion_holen(string $id): ?array
{
    foreach (sektionen_entwurf() as $s) {
        if (($s['id'] ?? '') === $id) {
            return $s;
        }
    }
    return null;
}

function sektion_titel(array $s): string
{
    $titel = trim(trim((string) ($s['titel'] ?? '')) . ' ' . trim((string) ($s['titelLeise'] ?? '')));
    if (($s['typ'] ?? '') === 'hero') {
        $titel = implode(' ', $s['zeilen'] ?? []);
    }
    $titel = str_replace("\n", ' ', $titel);
    if ($titel === '') {
        $titel = trim((string) ($s['eyebrow'] ?? '')) ?: sektion_typ_name((string) ($s['typ'] ?? ''));
    }
    return mb_strlen($titel) > 60 ? mb_substr($titel, 0, 60) . ' …' : $titel;
}

/** Darf dieser Typ noch hinzugefügt werden? Einmalige Typen nur, wenn es sie noch nicht gibt. */
function typ_verfuegbar(string $typ): bool
{
    if (!isset(sektion_typen()[$typ])) {
        return false;
    }
    if (!sektion_typen()[$typ][2]) {
        return true;
    }
    foreach (sektionen_entwurf() as $s) {
        if (($s['typ'] ?? '') === $typ) {
            return false;
        }
    }
    return true;
}

/** Ist ein Anker noch frei? „top“ bleibt dem Kopfbereich vorbehalten. */
function anker_frei(string $anker, ?string $ohneId = null, string $typ = ''): bool
{
    if (in_array($anker, ['root', 'np-inhalt', 'inhalt', 'impressum', 'datenschutz', 'barrierefreiheit', 'kopf', 'fuss', 'hinweise', 'zeiten'], true)) {
        return false;
    }
    if ($anker === 'top' && $typ !== 'hero') {
        return false;
    }
    foreach (sektionen_entwurf() as $s) {
        if (($s['anker'] ?? '') === $anker && ($s['id'] ?? '') !== $ohneId) {
            return false;
        }
    }
    return true;
}

function anker_vorschlag(string $basis): string
{
    $basis = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(strtr($basis, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']))), '-') ?: 'sektion';
    $kandidat = $basis;
    $n = 2;
    while (!anker_frei($kandidat)) {
        $kandidat = $basis . '-' . $n++;
    }
    return $kandidat;
}

/** Kurzer Menüname für einen Sektionstyp. */
function sektion_menue_vorschlag(string $typ): string
{
    return match ($typ) {
        'hero', 'laufband' => '',
        'ueber-uns' => 'Über uns',
        'faq' => 'Fragen',
        'schritte' => 'Ablauf',
        'stimmen' => 'Stimmen',
        'galerie' => 'Galerie',
        'text-bild', 'karten', 'freitext', 'video', 'aufruf', 'zahlen' => '',
        default => (sektion_theme()['sektionen'][$typ]['menue'] ?? '') ?: sektion_typ_name($typ),
    };
}

/** Menüpunkt ein- oder ausschalten. Ohne Beschriftung wird eine passende gesetzt. @return bool|null neuer Zustand */
function sektion_menue_umschalten(string $id): ?bool
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            $an = empty($s['menue']['zeigen']);
            $label = trim((string) ($s['menue']['label'] ?? ''));
            if ($an && $label === '') {
                $label = sektion_menue_vorschlag((string) $s['typ']) ?: mb_substr(trim((string) ($s['titel'] ?? '')) ?: sektion_typ_name((string) $s['typ']), 0, 24);
            }
            $liste[$i]['menue'] = ['zeigen' => $an, 'label' => $label];
            sektionen_speichern($liste);
            return $an;
        }
    }
    return null;
}

/** Neue Sektion anlegen — vor dem Kontakt, damit der am Ende bleibt (Anfahrt darunter). @return string|null neue Kennung */
function sektion_neu(string $typ): ?string
{
    if (!typ_verfuegbar($typ)) {
        return null;
    }
    $liste = sektionen_entwurf();
    $id = 's-' . bin2hex(random_bytes(4));
    $s = ['id' => $id, 'typ' => $typ, 'aktiv' => false,
          'anker' => anker_vorschlag($typ === 'hero' ? 'top' : sektion_typ_name($typ)),
          'menue' => ['zeigen' => false, 'label' => sektion_menue_vorschlag($typ)]] + sektion_vorlage($typ);
    if ($typ === 'hero' && anker_frei('top', null, 'hero')) {
        $s['anker'] = 'top';
    }

    $position = count($liste);
    if ($typ === 'hero') {
        $position = 0;
    } elseif ($typ !== 'anfahrt') {
        // Die Anfahrt schließt an den Kontakt an und bleibt deshalb am Ende.
        foreach ($liste as $i => $vorhanden) {
            if (($vorhanden['typ'] ?? '') === 'kontakt') {
                $position = $i;
                break;
            }
        }
    }
    array_splice($liste, $position, 0, [$s]);
    sektionen_speichern($liste);
    return $id;
}

function sektion_speichern(string $id, array $daten): bool
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            $daten['id'] = $id;
            $daten['typ'] = $s['typ'];
            $liste[$i] = $daten;
        }
    }
    return sektionen_speichern($liste);
}

function sektion_verschieben(string $id, int $richtung): void
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            $j = $i + ($richtung < 0 ? -1 : 1);
            if ($j >= 0 && $j < count($liste)) {
                [$liste[$i], $liste[$j]] = [$liste[$j], $liste[$i]];
                sektionen_speichern($liste);
            }
            return;
        }
    }
}

function sektion_umschalten(string $id): ?bool
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            $liste[$i]['aktiv'] = empty($s['aktiv']);
            sektionen_speichern($liste);
            return $liste[$i]['aktiv'];
        }
    }
    return null;
}

function sektion_duplizieren(string $id): ?string
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            if (sektion_typen()[$s['typ']][2] ?? true) {
                return null;
            }
            $kopie = $s;
            $kopie['id'] = 's-' . bin2hex(random_bytes(4));
            $kopie['aktiv'] = false;
            $kopie['anker'] = anker_vorschlag((string) $s['anker']);
            $kopie['menue']['zeigen'] = false;
            if (isset($kopie['titel'])) {
                $kopie['titel'] = trim($kopie['titel'] . ' (Kopie)');
            }
            array_splice($liste, $i + 1, 0, [$kopie]);
            sektionen_speichern($liste);
            return $kopie['id'];
        }
    }
    return null;
}

function sektion_loeschen(string $id): ?array
{
    $liste = sektionen_entwurf();
    foreach ($liste as $i => $s) {
        if (($s['id'] ?? '') === $id) {
            array_splice($liste, $i, 1);
            sektionen_speichern($liste);
            return $s;
        }
    }
    return null;
}

/** Unterscheidet sich die Sektion vom veröffentlichten Stand? */
function sektion_geaendert(string $id): bool
{
    if (!entwurf_offen('startseite')) {
        return false;
    }
    $live = null;
    foreach (live_laden('startseite')['sektionen'] ?? [] as $s) {
        if (($s['id'] ?? '') === $id) {
            $live = $s;
        }
    }
    return $live != sektion_holen($id);
}

/** Welche Seiten verlinken auf einen Anker? Für die Rückfrage beim Löschen und Umbenennen. */
function anker_verweise(string $anker): array
{
    $treffer = [];
    $suche = '"#' . $anker . '"';
    foreach (sektionen_entwurf() as $s) {
        if (str_contains((string) json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $suche) && ($s['anker'] ?? '') !== $anker) {
            $treffer[] = 'Sektion „' . sektion_titel($s) . '“';
        }
    }
    if (str_contains((string) json_encode(entwurf_laden('allgemein'), JSON_UNESCAPED_SLASHES), $suche)) {
        $treffer[] = 'Kopfleiste';
    }
    return $treffer;
}
