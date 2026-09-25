<?php
/**
 * Medienverwaltung: Bilder, Videos und PDF-Dateien.
 *
 * Die Dateien liegen im Docroot der Webseite unter medien/ — sie sind nach dem
 * Hochladen sofort erreichbar, zu sehen aber erst, wenn ein Inhalt darauf
 * zeigt und veröffentlicht wurde.
 *
 * Angenommen wird nur, was sich am Dateiinhalt als JPEG, PNG, GIF, WebP, MP4,
 * WebM oder PDF erkennen lässt — die Endung allein zählt nicht. SVG bleibt
 * draußen, weil darin Skripte stecken können.
 *
 * Fotos vom Handy tragen oft den Aufnahmeort in den EXIF-Daten. Bei einem
 * Teamfoto aus dem Wohnzimmer möchte man das nicht im Netz haben. JPEG-Bilder
 * werden deshalb neu gespeichert: richtig gedreht, ohne Metadaten und bei
 * Bedarf auf 2400 Pixel verkleinert.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

/** art => [Bezeichnung, Höchstgröße in Bytes, Endungen] */
const MEDIEN_ARTEN = [
    'bild'  => ['Bild', 12 * 1048576, ['jpg', 'jpeg', 'png', 'gif', 'webp']],
    'video' => ['Video', 80 * 1048576, ['mp4', 'webm']],
    'pdf'   => ['PDF', 20 * 1048576, ['pdf']],
];

const BILD_MAX_KANTE = 2400;

function medium_art_nach_endung(string $name): ?string
{
    $endung = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    foreach (MEDIEN_ARTEN as $art => [, , $endungen]) {
        if (in_array($endung, $endungen, true)) {
            return $art;
        }
    }
    return null;
}

/**
 * Erkennt den Dateityp am Inhalt.
 * @return array{0:string,1:string}|null [art, endung]
 */
function medium_erkennen(string $datei): ?array
{
    $kopf = (string) @file_get_contents($datei, false, null, 0, 16);
    if (str_starts_with($kopf, '%PDF-')) {
        return ['pdf', 'pdf'];
    }
    if (str_starts_with($kopf, "\x1A\x45\xDF\xA3")) {
        return ['video', 'webm'];
    }
    if (substr($kopf, 4, 4) === 'ftyp') {
        $marke = substr($kopf, 8, 4);
        // QuickTime (.mov) spielen nicht alle Browser ab
        return $marke === 'qt  ' ? null : ['video', 'mp4'];
    }
    $masse = @getimagesize($datei);
    $typen = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if ($masse && isset($typen[$masse[2]])) {
        return ['bild', $typen[$masse[2]]];
    }
    return null;
}

/** Alle Dateien in medien/, neueste zuerst. */
function medien_liste(?string $nurArt = null): array
{
    $raus = [];
    foreach (glob(pfad_medien('*')) ?: [] as $p) {
        $name = basename($p);
        if (!is_file($p) || str_starts_with($name, '.')) {
            continue;
        }
        $art = medium_art_nach_endung($name);
        if ($art === null || ($nurArt !== null && $art !== $nurArt)) {
            continue;
        }
        $masse = $art === 'bild' ? @getimagesize($p) : false;
        $raus[] = [
            'name' => $name,
            'pfad' => 'medien/' . $name,
            'art' => $art,
            'groesse' => (int) filesize($p),
            'geaendert' => (int) filemtime($p),
            'breite' => $masse[0] ?? null,
            'hoehe' => $masse[1] ?? null,
        ];
    }
    usort($raus, static fn($a, $b) => $b['geaendert'] <=> $a['geaendert']);
    return $raus;
}

/** Wo wird eine Datei verwendet — in Entwürfen, im Live-Stand und in der Hinweis-Ablage? */
function medium_verwendung(string $pfad): array
{
    $treffer = [];
    $suche = '"' . $pfad . '"';
    foreach (['stammdaten', 'allgemein', 'startseite', 'angebote', 'team', 'preise', 'hinweise'] as $datei) {
        foreach ([entwurf_laden($datei), live_laden($datei)] as $stand) {
            if (str_contains((string) json_encode($stand, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $suche)) {
                $treffer[$datei] = datei_titel($datei);
            }
        }
    }
    if (str_contains((string) json_encode(hinweise_laden(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $suche)) {
        $treffer['hinweise'] = datei_titel('hinweise');
    }
    return $treffer;
}

function medium_name_gueltig(string $name): bool
{
    return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name) && !str_contains($name, '..');
}

function medium_name_saeubern(string $name, string $endung): string
{
    $basis = strtr(pathinfo($name, PATHINFO_FILENAME), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss']);
    $basis = trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $basis)), '-') ?: 'datei';
    $basis = substr($basis, 0, 60);
    $kandidat = $basis . '.' . $endung;
    $n = 2;
    while (is_file(pfad_medien($kandidat))) {
        $kandidat = $basis . '-' . $n++ . '.' . $endung;
    }
    return $kandidat;
}

/** Mehrfach-Upload: aus PHPs verdrehtem $_FILES-Format eine Liste machen. */
function dateien_normalisieren(array $feld): array
{
    if (!isset($feld['name'])) {
        return [];
    }
    if (!is_array($feld['name'])) {
        return [$feld];
    }
    $raus = [];
    foreach (array_keys($feld['name']) as $i) {
        $raus[] = ['name' => $feld['name'][$i], 'type' => $feld['type'][$i], 'tmp_name' => $feld['tmp_name'][$i],
                   'error' => $feld['error'][$i], 'size' => $feld['size'][$i]];
    }
    return $raus;
}

/**
 * Prüft eine hochgeladene Datei.
 * @return array{0:?array,1:?string} [[art, endung], Fehler]
 */
function medium_pruefen(array $datei): array
{
    $code = $datei['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) {
        return [null, 'Es wurde keine Datei ausgewählt.'];
    }
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'Die Datei ist größer, als der Server annimmt (' . groesse_lesbar(upload_grenze()) . '). Die Grenze lässt sich im Kundenmenü erhöhen.'];
    }
    if ($code !== UPLOAD_ERR_OK) {
        return [null, 'Das Hochladen ist fehlgeschlagen (Code ' . (int) $code . ').'];
    }
    if (!is_uploaded_file($datei['tmp_name'])) {
        return [null, 'Die Datei kam nicht auf dem üblichen Weg an und wurde abgelehnt.'];
    }
    $typ = medium_erkennen($datei['tmp_name']);
    if (!$typ) {
        return [null, 'Dieses Format wird nicht angenommen. Möglich sind JPEG, PNG, GIF, WebP, MP4, WebM und PDF — SVG und QuickTime (.mov) nicht.'];
    }
    [$art] = $typ;
    if ($datei['size'] > MEDIEN_ARTEN[$art][1]) {
        return [null, 'Die Datei ist größer als ' . groesse_lesbar(MEDIEN_ARTEN[$art][1]) . '. '
            . ($art === 'video' ? 'Videos für die Webseite bitte vorher komprimieren (z. B. mit HandBrake, 1080p).' : 'Bitte vorher verkleinern.')];
    }
    return [$typ, null];
}

/**
 * Bild neu speichern: gedreht, ohne Metadaten, höchstens BILD_MAX_KANTE groß.
 * @return string|null Hinweis für die Meldung
 */
function bild_aufbereiten(string $datei, string $endung): ?string
{
    if (!extension_loaded('gd') || !in_array($endung, ['jpg', 'png', 'webp'], true)) {
        return null;
    }
    $masse = @getimagesize($datei);
    if (!$masse) {
        return null;
    }
    [$b, $h] = $masse;

    // Grob abschätzen, ob der Arbeitsspeicher reicht — sonst lieber das Original behalten
    $grenze = groesse_aus_ini((string) ini_get('memory_limit'));
    if ($grenze !== PHP_INT_MAX && $b * $h * 5 * 2 > $grenze - memory_get_usage()) {
        return 'Das Bild ist zu groß, um es auf dem Server zu verkleinern — es wurde unverändert übernommen.';
    }

    $zuGross = max($b, $h) > BILD_MAX_KANTE;
    if ($endung !== 'jpg' && !$zuGross) {
        return null;
    }

    $bild = match ($endung) {
        'jpg' => @imagecreatefromjpeg($datei),
        'png' => @imagecreatefrompng($datei),
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($datei) : false,
    };
    if (!$bild) {
        return null;
    }

    if ($endung === 'jpg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($datei);
        $drehung = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($drehung !== 0) {
            $gedreht = imagerotate($bild, $drehung, 0);
            if ($gedreht) {
                unset($bild);
                $bild = $gedreht;
                [$b, $h] = [imagesx($bild), imagesy($bild)];
            }
        }
    }

    $hinweis = null;
    if (max($b, $h) > BILD_MAX_KANTE) {
        $faktor = BILD_MAX_KANTE / max($b, $h);
        $klein = imagescale($bild, (int) round($b * $faktor), (int) round($h * $faktor), IMG_BICUBIC);
        if ($klein) {
            unset($bild);
            $bild = $klein;
            $hinweis = 'Auf ' . imagesx($bild) . ' × ' . imagesy($bild) . ' Pixel verkleinert.';
        }
    }

    if ($endung === 'png') {
        imagealphablending($bild, false);
        imagesavealpha($bild, true);
    }
    $ok = match ($endung) {
        'jpg' => imagejpeg($bild, $datei, 86),
        'png' => imagepng($bild, $datei, 6),
        'webp' => function_exists('imagewebp') && imagewebp($bild, $datei, 84),
    };
    unset($bild);
    if ($ok && $endung === 'jpg') {
        $hinweis = trim(($hinweis ?? '') . ' Aufnahmedaten (z. B. Ort) entfernt.');
    }
    return $ok ? $hinweis : null;
}

/** @return array{0:?string,1:?string,2:?string} [Dateiname, Fehler, Hinweis] */
function medium_hochladen(array $datei): array
{
    [$typ, $fehler] = medium_pruefen($datei);
    if ($fehler) {
        return [null, $fehler, null];
    }
    [$art, $endung] = $typ;
    ordner_sicherstellen(pfad_medien());
    $ziel = medium_name_saeubern((string) $datei['name'], $endung);
    if (!move_uploaded_file($datei['tmp_name'], pfad_medien($ziel))) {
        return [null, 'Die Datei ließ sich nicht speichern. Fehlt dem Webserver das Schreibrecht auf medien/?', null];
    }
    @chmod(pfad_medien($ziel), 0644);
    $hinweis = $art === 'bild' ? bild_aufbereiten(pfad_medien($ziel), $endung) : null;
    protokoll('Medium hochgeladen: ' . $ziel);
    return [$ziel, null, $hinweis];
}

/** Eine vorhandene Datei durch eine neue Fassung ersetzen — überall, wo sie verwendet wird. */
function medium_ersetzen(string $name, array $datei): array
{
    if (!medium_name_gueltig($name) || !is_file(pfad_medien($name))) {
        return ['Diese Datei gibt es nicht (mehr).', null];
    }
    [$typ, $fehler] = medium_pruefen($datei);
    if ($fehler) {
        return [$fehler, null];
    }
    [, $endung] = $typ;
    $alt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($endung !== ($alt === 'jpeg' ? 'jpg' : $alt)) {
        return ['Die neue Datei muss dasselbe Format haben (' . strtoupper($alt) . '), sonst passt der Name nicht mehr.', null];
    }
    if (!move_uploaded_file($datei['tmp_name'], pfad_medien($name))) {
        return ['Die Datei ließ sich nicht speichern.', null];
    }
    @chmod(pfad_medien($name), 0644);
    clearstatcache();
    $hinweis = medium_art_nach_endung($name) === 'bild' ? bild_aufbereiten(pfad_medien($name), $endung) : null;
    protokoll('Medium ersetzt: ' . $name);
    return [null, $hinweis];
}

function medium_loeschen(string $name): ?string
{
    if (!medium_name_gueltig($name)) {
        return 'Ungültiger Dateiname.';
    }
    $p = pfad_medien($name);
    if (!is_file($p)) {
        return 'Diese Datei gibt es nicht (mehr).';
    }
    if (!unlink($p)) {
        return 'Die Datei ließ sich nicht löschen.';
    }
    protokoll('Medium gelöscht: ' . $name);
    return null;
}

/**
 * Liefert eine Datei aus medien/ für die Vorschau im Portal aus — mit
 * Byte-Bereichen, damit Safari Videos abspielt.
 */
function medium_ausliefern(string $pfad): never
{
    $name = basename($pfad);
    if ($pfad !== 'medien/' . $name || !medium_name_gueltig($name) || !($art = medium_art_nach_endung($name))) {
        http_response_code(400);
        exit;
    }
    $datei = pfad_medien($name);
    if (!is_file($datei)) {
        http_response_code(404);
        exit;
    }
    $typen = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
              'mp4' => 'video/mp4', 'webm' => 'video/webm', 'pdf' => 'application/pdf'];
    $groesse = (int) filesize($datei);
    $von = 0;
    $bis = $groesse - 1;

    header('Content-Type: ' . ($typen[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");

    if (preg_match('/bytes=(\d*)-(\d*)/', (string) ($_SERVER['HTTP_RANGE'] ?? ''), $m)) {
        $von = $m[1] === '' ? max(0, $groesse - (int) $m[2]) : (int) $m[1];
        $bis = ($m[1] !== '' && $m[2] !== '') ? min((int) $m[2], $groesse - 1) : $bis;
        if ($von > $bis || $von >= $groesse) {
            http_response_code(416);
            header('Content-Range: bytes */' . $groesse);
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes $von-$bis/$groesse");
    }
    header('Content-Length: ' . ($bis - $von + 1));

    $fh = fopen($datei, 'rb');
    fseek($fh, $von);
    $rest = $bis - $von + 1;
    while ($rest > 0 && !feof($fh)) {
        $stueck = fread($fh, (int) min(262144, $rest));
        echo $stueck;
        $rest -= strlen((string) $stueck);
        flush();
    }
    fclose($fh);
    exit;
}

function groesse_lesbar(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    return number_format(max(1, (int) round($bytes / 1024)), 0, ',', '.') . ' KB';
}

function groesse_aus_ini(string $wert): int
{
    $wert = trim($wert);
    if ($wert === '' || $wert === '-1') {
        return PHP_INT_MAX;
    }
    $zahl = (int) $wert;
    return match (strtolower(substr($wert, -1))) {
        'g' => $zahl * 1073741824,
        'm' => $zahl * 1048576,
        'k' => $zahl * 1024,
        default => $zahl,
    };
}

/** Die kleinere der beiden PHP-Grenzen entscheidet über die größte Datei. */
function upload_grenze(): int
{
    return min(groesse_aus_ini((string) ini_get('upload_max_filesize')), groesse_aus_ini((string) ini_get('post_max_size')));
}
