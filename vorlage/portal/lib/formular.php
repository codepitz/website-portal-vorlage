<?php
/**
 * Formulare aus dem Schema bauen und die Eingaben wieder einsammeln.
 *
 * Es gibt genau einen Renderer für alle Bereiche. Was im Schema steht, kommt
 * ins Formular; was nicht im Schema steht, wird beim Speichern unverändert aus
 * der alten Datei übernommen. Ein Formular kann also nichts kaputt machen,
 * was es gar nicht anzeigt.
 *
 * Namen der Formularfelder bilden den Weg durch die JSON-Datei ab:
 *     d[kontakt][telefon]
 *     d[personen][0][bild]
 *
 * In den Vorlagen für neue Listeneinträge stehen statt der Zahlen Platzhalter
 * (%p0%, %i0% für die äußere Liste, %p1%, %i1% für eine Liste darin). Das
 * JavaScript ersetzt sie beim Hinzufügen und nummeriert vor dem Absenden alles
 * in der sichtbaren Reihenfolge durch.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

/* ============================================================ Ausgabe === */

function felder_rendern(array $felder, mixed $werte, string $praefix, int $listenTiefe = 0): string
{
    $werte = is_array($werte) ? $werte : ($werte === null ? null : []);
    $raus = '';
    foreach ($felder as $schluessel => $spec) {
        $typ = $spec['typ'] ?? 'text';
        if ($typ === 'abschnitt') {
            $raus .= karte_rendern($spec, felder_rendern($spec['felder'], $werte, $praefix, $listenTiefe), $praefix, (string) $schluessel);
            continue;
        }
        if ($typ === 'hinweis') {
            $raus .= '<p class="feld feld--breit feld-hinweis"><span aria-hidden="true">i</span> ' . h($spec['text'] ?? '') . '</p>';
            continue;
        }
        $raus .= feld_rendern((string) $schluessel, $spec, $werte === null ? null : ($werte[$schluessel] ?? null), $praefix, $listenTiefe);
    }
    return $raus;
}

function feld_rendern(string $schluessel, array $spec, mixed $wert, string $praefix, int $listenTiefe): string
{
    $typ = $spec['typ'] ?? 'text';
    $name = $praefix . '[' . $schluessel . ']';

    return match ($typ) {
        'objekt'   => !empty($spec['flach'])
            ? felder_rendern($spec['felder'], $wert, $name, $listenTiefe)
            : karte_rendern($spec, felder_rendern($spec['felder'], $wert, $name, $listenTiefe), $name, $schluessel),
        'liste'    => liste_rendern($spec, is_array($wert) ? $wert : [], $name, $schluessel, $listenTiefe),
        'absaetze' => absaetze_rendern($spec, is_array($wert) ? $wert : [], $name, $schluessel, $listenTiefe),
        default    => einfach_rendern($typ, $spec, $wert, $name),
    };
}

function karte_rendern(array $spec, string $inhalt, string $praefix, string $schluessel): string
{
    $zu = !empty($spec['zu']);
    $id = 'k' . substr(md5($praefix . $schluessel), 0, 8);
    $h = '<section class="karte' . ($zu ? ' karte--zu' : '') . '">'
       . '<div class="karte-kopf">'
       . '<button type="button" class="karte-auf" data-auf aria-expanded="' . ($zu ? 'false' : 'true') . '" aria-controls="' . $id . '">'
       . '<span class="pfeil" aria-hidden="true"></span>'
       . '<span class="karte-titel">' . h($spec['label'] ?? '') . '</span>'
       . '</button>';
    if (!empty($spec['hilfe'])) {
        $h .= '<p class="karte-hilfe">' . h($spec['hilfe']) . '</p>';
    }
    return $h . '</div>'
        . '<div class="karte-inhalt feldgitter" id="' . $id . '"' . ($zu ? ' hidden' : '') . '>' . $inhalt . '</div>'
        . '</section>';
}

function liste_rendern(array $spec, array $werte, string $name, string $schluessel, int $listenTiefe): string
{
    $zeilen = '';
    $i = 0;
    foreach ($werte as $originalIndex => $eintrag) {
        $zeilen .= listenzeile_rendern($spec, $eintrag, $name . '[' . $i . ']', $i, (string) $originalIndex, $listenTiefe);
        $i++;
    }

    $marke = 'p' . $listenTiefe;
    $vorlage = listenzeile_rendern($spec, null, '%p' . $listenTiefe . '%[%i' . $listenTiefe . '%]', null, '', $listenTiefe);

    return '<div class="feld feld--breit">'
        . '<div class="listen-kopf">'
        . '<span class="feld-label">' . h($spec['label'] ?? '') . '</span>'
        . '<span class="listen-anzahl" data-anzahl>' . count($werte) . '</span>'
        . (!empty($spec['hilfe']) ? '<span class="feld-hilfe">' . h($spec['hilfe']) . '</span>' : '')
        . '</div>'
        . '<div class="liste' . (!empty($spec['kompakt']) ? ' liste--kompakt' : '') . '" data-liste data-feld="' . h($schluessel) . '" data-marke="' . $marke . '" data-praefix="' . h($name) . '">'
        . $zeilen
        . '<template data-vorlage>' . $vorlage . '</template>'
        . '</div>'
        . '<button type="button" class="knopf knopf--geist knopf--klein" data-neu>'
        . '<span aria-hidden="true">+</span> ' . h($spec['knopf'] ?? 'Eintrag hinzufügen') . '</button>'
        . '</div>';
}

function listenzeile_rendern(array $spec, mixed $eintrag, string $praefix, ?int $nr, string $originalIndex, int $listenTiefe): string
{
    $neu = $eintrag === null;
    $eintrag = is_array($eintrag) ? $eintrag : [];
    $titelFeld = $spec['zeilenTitel'] ?? null;
    $zusatzFeld = $spec['zeilenZusatz'] ?? null;

    $beschriftung = static function (?string $feld) use ($spec, $eintrag): string {
        if ($feld === null) {
            return '';
        }
        $wert = $eintrag[$feld] ?? '';
        $wert = is_scalar($wert) ? trim((string) $wert) : '';
        $unter = $spec['felder'][$feld] ?? [];
        if (($unter['typ'] ?? '') === 'auswahl' && $wert !== '') {
            return (string) ($unter['optionen'][$wert] ?? $wert);
        }
        return $wert;
    };

    $titel = $beschriftung($titelFeld);
    $zusatz = $beschriftung($zusatzFeld);
    $zu = !empty($spec['zu']) && $nr !== null;
    $aus = array_key_exists('aktiv', $spec['felder']) && !$neu && empty($eintrag['aktiv']);

    return '<div class="zeile' . ($zu ? ' zeile--zu' : '') . ($aus ? ' zeile--aus' : '') . '" data-zeile data-praefix="' . h($praefix) . '"'
       . ' data-titelfeld="' . h((string) $titelFeld) . '" data-zusatzfeld="' . h((string) $zusatzFeld) . '">'
       . '<div class="zeile-kopf">'
       . '<button type="button" class="zeile-auf" data-auf aria-expanded="' . ($zu ? 'false' : 'true') . '" aria-label="Eintrag auf- und zuklappen">'
       . '<span class="pfeil" aria-hidden="true"></span></button>'
       . '<span class="zeile-nr" data-nr>' . ($nr === null ? '' : str_pad((string) ($nr + 1), 2, '0', STR_PAD_LEFT)) . '</span>'
       . '<span class="zeile-titel" data-zeilentitel>' . h($titel !== '' ? $titel : 'Neuer Eintrag') . '</span>'
       . '<span class="zeile-zusatz" data-zeilenzusatz>' . h(kurz($zusatz, 40) === '(leer)' ? '' : kurz($zusatz, 40)) . '</span>'
       . ($aus ? '<span class="pille pille--still">ausgeblendet</span>' : '')
       . '<span class="zeile-tasten">'
       . '<button type="button" class="minitaste" data-hoch aria-label="Nach oben" title="Nach oben">↑</button>'
       . '<button type="button" class="minitaste" data-runter aria-label="Nach unten" title="Nach unten">↓</button>'
       . '<button type="button" class="minitaste" data-kopie aria-label="Eintrag kopieren" title="Kopieren">⧉</button>'
       . '<button type="button" class="minitaste minitaste--weg" data-weg aria-label="Eintrag entfernen" title="Entfernen">✕</button>'
       . '</span>'
       . '</div>'
       . '<div class="zeile-inhalt feldgitter"' . ($zu ? ' hidden' : '') . '>'
       . '<input type="hidden" name="' . h($praefix) . '[_o]" value="' . h($originalIndex) . '" data-herkunft>'
       . felder_rendern($spec['felder'], $neu ? null : $eintrag, $praefix, $listenTiefe + 1)
       . '</div></div>';
}

function absaetze_rendern(array $spec, array $werte, string $name, string $schluessel, int $listenTiefe): string
{
    $einzeilig = !empty($spec['einzeilig']);
    $zeilen = '';
    $i = 0;
    foreach ($werte as $wert) {
        $zeilen .= absatz_zeile($name . '[' . $i . ']', is_scalar($wert) ? (string) $wert : '', $einzeilig);
        $i++;
    }
    $vorlage = absatz_zeile('%p' . $listenTiefe . '%[%i' . $listenTiefe . '%]', '', $einzeilig);

    return '<div class="feld' . (!empty($spec['breit']) ? ' feld--breit' : '') . '">'
        . '<div class="listen-kopf">'
        . '<span class="feld-label">' . h($spec['label'] ?? '') . '</span>'
        . (!empty($spec['hilfe']) ? '<span class="feld-hilfe">' . h($spec['hilfe']) . '</span>' : '')
        . '</div>'
        . '<div class="absaetze" data-liste data-absaetze data-feld="' . h($schluessel) . '" data-marke="p' . $listenTiefe . '" data-praefix="' . h($name) . '">'
        . $zeilen
        . '<template data-vorlage>' . $vorlage . '</template>'
        . '</div>'
        . '<button type="button" class="knopf knopf--geist knopf--klein" data-neu>'
        . '<span aria-hidden="true">+</span> ' . h($spec['knopf'] ?? 'Absatz hinzufügen') . '</button>'
        . '</div>';
}

function absatz_zeile(string $praefix, string $wert, bool $einzeilig): string
{
    $feld = $einzeilig
        ? '<input type="text" class="eingabe" name="' . h($praefix) . '" value="' . h($wert) . '" aria-label="Eintrag">'
        : '<textarea class="eingabe eingabe--flaeche" rows="3" name="' . h($praefix) . '" aria-label="Absatz">' . h($wert) . '</textarea>';

    return '<div class="absatz" data-zeile data-praefix="' . h($praefix) . '">'
        . '<span class="absatz-griff" aria-hidden="true"></span>'
        . $feld
        . '<span class="zeile-tasten">'
        . '<button type="button" class="minitaste" data-hoch aria-label="Nach oben" title="Nach oben">↑</button>'
        . '<button type="button" class="minitaste" data-runter aria-label="Nach unten" title="Nach unten">↓</button>'
        . '<button type="button" class="minitaste minitaste--weg" data-weg aria-label="Entfernen" title="Entfernen">✕</button>'
        . '</span></div>';
}

function einfach_rendern(string $typ, array $spec, mixed $wert, string $name): string
{
    // Neue Listeneinträge bekommen die Vorgabe aus dem Schema
    if ($wert === null && array_key_exists('standard', $spec)) {
        $wert = $spec['standard'];
    }
    $id = 'f' . substr(md5($name), 0, 10);
    $klassen = 'feld' . (!empty($spec['breit']) || $typ === 'medium' ? ' feld--breit' : '');
    $pflicht = !empty($spec['pflicht']);
    $text = is_scalar($wert) ? (string) $wert : '';
    if (is_bool($wert)) {
        $text = $wert ? '1' : '';
    }

    $label = '<label class="feld-label" for="' . $id . '">' . h($spec['label'] ?? '')
           . ($pflicht ? ' <span class="pflichtstern" title="Pflichtangabe">*</span>' : '') . '</label>';

    $gemein = 'id="' . $id . '" name="' . h($name) . '"' . ($pflicht && $typ !== 'medium' ? ' required' : '');
    $zaehler = !empty($spec['zaehler']) ? ' data-zaehler="' . (int) $spec['zaehler'] . '"' : '';

    $eingabe = match ($typ) {
        'flaeche' => '<textarea class="eingabe eingabe--flaeche" rows="' . (int) ($spec['zeilen'] ?? 4) . '" ' . $gemein . $zaehler . '>' . h($text) . '</textarea>',

        'zeile' => '<textarea class="eingabe eingabe--zeile" rows="2" ' . $gemein . '>' . h($text) . '</textarea>',

        'zahl' => '<input type="number" class="eingabe" ' . $gemein . ' value="' . h($text) . '" step="1"'
            . (isset($spec['min']) ? ' min="' . (int) $spec['min'] . '"' : '') . (isset($spec['max']) ? ' max="' . (int) $spec['max'] . '"' : '') . '>',

        'datum' => '<input type="date" class="eingabe" ' . $gemein . ' value="' . h($text) . '">',

        'farbe' => '<span class="farbwahl"><input type="color" class="farbwahl-feld" value="' . h(preg_match('/^#[0-9a-f]{6}$/i', $text) ? $text : '#000000') . '" data-farbe-fuer="' . $id . '" aria-label="Farbe wählen">'
            . '<input type="text" class="eingabe mono" ' . $gemein . ' value="' . h($text) . '" spellcheck="false" data-farbe-text></span>',

        'schalter' => '<label class="schalter"><input type="hidden" name="' . h($name) . '" value="0">'
            . '<input type="checkbox" id="' . $id . '" name="' . h($name) . '" value="1"' . (!empty($wert) ? ' checked' : '') . '>'
            . '<span class="schalter-bahn" aria-hidden="true"><span class="schalter-punkt"></span></span>'
            . '<span class="schalter-text">' . h($spec['label'] ?? '') . '</span></label>',

        'auswahl' => (static function () use ($spec, $text, $gemein): string {
            $o = '';
            foreach ($spec['optionen'] as $k => $t) {
                $o .= '<option value="' . h((string) $k) . '"' . ($text === (string) $k ? ' selected' : '') . '>' . h($t) . '</option>';
            }
            return '<select class="eingabe" ' . $gemein . '>' . $o . '</select>';
        })(),

        'medium' => '<div class="medienfeld" data-medienfeld data-art="' . h($spec['art'] ?? 'bild') . '">'
            . '<span class="medienfeld-vorschau" data-vorschau>' . medium_vorschau($text) . '</span>'
            . '<span class="medienfeld-mitte">'
            . '<input type="text" class="eingabe mono" ' . $gemein . ' value="' . h($text) . '" placeholder="Noch nichts ausgewählt" data-medienpfad readonly>'
            . '<span class="knopfzeile">'
            . '<button type="button" class="knopf knopf--zweit knopf--klein" data-medienwahl>' . ($text === '' ? 'Auswählen' : 'Ändern') . '</button>'
            . '<button type="button" class="knopf knopf--still knopf--klein" data-medienleeren' . ($text === '' ? ' hidden' : '') . '>Entfernen</button>'
            . '</span></span>'
            . '</div>',

        'link' => '<input type="text" class="eingabe" ' . $gemein . ' value="' . h($text) . '" list="np-ziele" spellcheck="false" placeholder="#kontakt">',

        default => '<input type="' . (($spec['format'] ?? '') === 'email' ? 'email' : 'text') . '" class="eingabe" ' . $gemein . ' value="' . h($text) . '"'
            . (!empty($spec['muster']) ? ' pattern="' . h($spec['muster']) . '"' : '') . $zaehler . '>',
    };

    $hilfe = $spec['hilfe'] ?? '';
    if ($typ === 'zeile') {
        $hilfe = trim('Ein Zeilenumbruch bestimmt, wo die Überschrift auf der Seite umbricht. ' . $hilfe);
    }
    if ($typ === 'link' && $hilfe === '') {
        $hilfe = 'Eine Sektion (#kontakt), eine Seite (impressum/), eine Adresse (https://…), tel:… oder mailto:…';
    }
    $hilfeHtml = $hilfe !== '' ? '<p class="feld-hilfe">' . h($hilfe) . '</p>' : '';

    if ($typ === 'schalter') {
        return '<div class="' . $klassen . ' feld--schalter">' . $eingabe . $hilfeHtml . '</div>';
    }
    return '<div class="' . $klassen . '">' . $label . $eingabe . $hilfeHtml . '</div>';
}

/** Kleine Vorschau für ein Medienfeld. */
function medium_vorschau(string $pfad): string
{
    if ($pfad === '') {
        return '<span class="medienfeld-leer" aria-hidden="true"></span>';
    }
    if (!str_starts_with($pfad, 'medien/') || !is_file(pfad_site($pfad))) {
        return '<span class="medienfeld-fehlt" title="Datei nicht gefunden">fehlt</span>';
    }
    return match (medium_art_nach_endung($pfad)) {
        'bild' => '<img src="' . h(url_zu('medium', ['pfad' => $pfad])) . '" alt="" loading="lazy">',
        'video' => '<span class="medienfeld-typ">▶</span>',
        default => '<span class="medienfeld-typ">PDF</span>',
    };
}

/* ========================================================= Einsammeln === */

/**
 * Liest die abgeschickten Werte anhand des Schemas und legt sie über den
 * alten Stand. Felder, die das Schema nicht kennt, bleiben unangetastet.
 */
function felder_einsammeln(array $felder, mixed $post, mixed $alt, array &$fehler, string $wo = ''): array
{
    $post = is_array($post) ? $post : [];
    $alt = is_array($alt) ? $alt : [];

    foreach ($felder as $schluessel => $spec) {
        $typ = $spec['typ'] ?? 'text';
        $label = $spec['label'] ?? $schluessel;
        $stelle = $wo === '' ? $label : $wo . ' › ' . $label;

        if ($typ === 'hinweis') {
            continue;
        }
        if ($typ === 'abschnitt') {
            $alt = felder_einsammeln($spec['felder'], $post, $alt, $fehler, $wo);
            continue;
        }
        if ($typ === 'objekt') {
            $alt[$schluessel] = felder_einsammeln($spec['felder'], $post[$schluessel] ?? [], $alt[$schluessel] ?? [], $fehler,
                !empty($spec['flach']) ? $wo : $stelle);
            continue;
        }
        if ($typ === 'liste') {
            $alt[$schluessel] = liste_einsammeln($spec, $post[$schluessel] ?? [], $alt[$schluessel] ?? [], $fehler, $stelle);
            continue;
        }
        if ($typ === 'absaetze') {
            $roh = is_array($post[$schluessel] ?? null) ? $post[$schluessel] : [];
            ksort($roh, SORT_NUMERIC);
            $sauber = [];
            foreach ($roh as $t) {
                $t = text_saeubern(is_array($t) ? '' : (string) $t);
                if ($t !== '') {
                    $sauber[] = $t;
                }
            }
            $alt[$schluessel] = $sauber;
            continue;
        }

        $roh = $post[$schluessel] ?? null;
        if (is_array($roh)) {
            $roh = end($roh) ?: '';   // Schalter senden hidden + checkbox
        }
        $wert = wert_wandeln($typ, (string) ($roh ?? ''));

        if (!empty($spec['pflicht']) && ($wert === '' || $wert === null)) {
            $fehler[] = $stelle . ' darf nicht leer bleiben.';
        }
        if (is_string($wert) && $wert !== '') {
            if (!empty($spec['muster']) && !preg_match('~^(?:' . $spec['muster'] . ')$~u', $wert)) {
                $fehler[] = $stelle . ': „' . $wert . '“ passt nicht zum erwarteten Format.';
            }
            if (($spec['format'] ?? '') === 'email' && !filter_var($wert, FILTER_VALIDATE_EMAIL)) {
                $fehler[] = $stelle . ': „' . $wert . '“ ist keine gültige E-Mail-Adresse.';
            }
            if ($typ === 'link' && !ziel_sicher($wert)) {
                $fehler[] = $stelle . ': „' . $wert . '“ ist als Linkziel nicht erlaubt.';
            }
            if ($typ === 'medium' && (!str_starts_with($wert, 'medien/') || str_contains($wert, '..'))) {
                $fehler[] = $stelle . ': Bitte eine Datei aus der Medienverwaltung auswählen.';
            }
            if ($typ === 'farbe' && !preg_match('/^(#[0-9a-f]{3,8}|(rgba?|hsla?|oklch|oklab|lab|lch|hwb|color)\([^;{}<>]*\))$/i', $wert)) {
                $fehler[] = $stelle . ': „' . $wert . '“ ist keine Farbe (z. B. #5eead4 oder rgb(94 234 212)).';
            }
            if ($typ === 'datum' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $wert)) {
                $fehler[] = $stelle . ': Bitte ein Datum auswählen.';
            }
        }
        if ($typ === 'zahl' && is_int($wert)) {
            if (isset($spec['min']) && $wert < $spec['min'] || isset($spec['max']) && $wert > $spec['max']) {
                $fehler[] = $stelle . ' muss zwischen ' . ($spec['min'] ?? '…') . ' und ' . ($spec['max'] ?? '…') . ' liegen.';
            }
        }
        $alt[$schluessel] = $wert;
    }

    return $alt;
}

function liste_einsammeln(array $spec, mixed $post, mixed $altListe, array &$fehler, string $wo): array
{
    $post = is_array($post) ? $post : [];
    $altListe = is_array($altListe) ? $altListe : [];
    ksort($post, SORT_NUMERIC);

    $raus = [];
    $nr = 0;
    foreach ($post as $zeile) {
        if (!is_array($zeile)) {
            continue;
        }
        $nr++;
        // _o verweist auf den Eintrag, aus dem die Zeile stammt — so bleiben
        // Angaben erhalten, die im Formular gar nicht vorkommen.
        $herkunft = $zeile['_o'] ?? '';
        $basis = ($herkunft !== '' && isset($altListe[$herkunft]) && is_array($altListe[$herkunft])) ? $altListe[$herkunft] : [];
        $raus[] = felder_einsammeln($spec['felder'], $zeile, $basis, $fehler, $wo . ' ' . $nr);
    }
    return $raus;
}

function wert_wandeln(string $typ, string $roh): mixed
{
    $roh = text_saeubern($roh);
    return match ($typ) {
        'zahl' => $roh === '' ? '' : (preg_match('/^-?\d+$/', $roh) ? (int) $roh : $roh),
        'schalter' => $roh === '1',
        'zeile', 'flaeche' => $roh,
        // Einzeilige Felder: Umbrüche haben dort nichts verloren
        default => str_replace("\n", ' ', $roh),
    };
}

function text_saeubern(string $s): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? $s;
    return trim($s);
}

/** Dieselbe Regel wie im Generator: nur Ziele, die im Browser nichts anrichten können. */
function ziel_sicher(string $ziel): bool
{
    $ziel = trim($ziel);
    return $ziel === ''
        || (bool) preg_match('~^#[A-Za-z0-9_-]*$~', $ziel)
        || (bool) preg_match('~^(https?://|mailto:|tel:)[^\s<>"]+$~i', $ziel)
        || (bool) preg_match('~^(?![a-z][a-z0-9+.-]*:)(?!//)[A-Za-z0-9._~/%#?=&-]+$~i', $ziel);
}
