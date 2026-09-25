<?php
/**
 * Portal der Webseite — erzeugt vom Portal-Assistenten.
 *
 * Eine einzige Eingangstür: alles läuft über ?seite=… und Formulare mit tat=…
 * Das hält die Anwendung auf jedem Hosting lauffähig, ohne Rewrite-Regeln.
 *
 * Bearbeitet wird immer nur ein Entwurf. Auf die Webseite kommt er erst durch
 * „Veröffentlichen“ — das schiebt die Inhalte an ihren Platz und lässt den
 * Generator die Webseite neu schreiben.
 */

declare(strict_types=1);

require __DIR__ . '/lib/start.php';
require __DIR__ . '/lib/benutzer.php';
require __DIR__ . '/lib/anmeldung.php';
require __DIR__ . '/lib/inhalt.php';
require __DIR__ . '/lib/schema.php';
require __DIR__ . '/lib/sektionen.php';
require __DIR__ . '/lib/formular.php';
require __DIR__ . '/lib/medien.php';
require __DIR__ . '/lib/hinweise.php';
require __DIR__ . '/lib/veroeffentlichen.php';
require __DIR__ . '/lib/diagnose.php';
require __DIR__ . '/lib/versand.php';
require __DIR__ . '/lib/design.php';
require __DIR__ . '/lib/ansicht.php';

sitzung_starten();

$seite = (string) ($_GET['seite'] ?? 'start');
$tat = (string) ($_POST['tat'] ?? '');
$post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

/* ------------------------------------------------------- Einrichtung --- */

if (!eingerichtet()) {
    $fehler = [];
    if ($post && $tat === 'einrichten') {
        $fehler = einrichten($_POST);
        if (!$fehler) {
            melden('gut', 'Das Portal ist eingerichtet. Bitte einmal anmelden.');
            header('Location: ?');
            exit;
        }
    }
    seite_schlicht('Einrichtung', ansicht('einrichten', ['fehler' => $fehler, 'werte' => $_POST, 'geraten' => pfade_raten()]));
}

if (!benutzer_alle()) {
    seite_schlicht('Keine Benutzer', '<h1>Keine Benutzerkonten gefunden</h1>'
        . '<p class="leise">Das Portal ist eingerichtet, im privaten Ordner liegen aber keine Benutzerkonten. '
        . 'Entweder zeigt der Pfad in <span class="mono">konfig.php</span> auf den falschen Ordner, oder die Datei '
        . '<span class="mono">ablage/benutzer.json</span> fehlt.</p>'
        . '<p class="leise">Einen Zugang legt auf dem Server dieser Befehl an:</p>'
        . '<pre class="codeblock">php werkzeuge/benutzer.php vorname.nachname</pre>');
}

/* --------------------------------------------------------- Anmeldung --- */

if ($seite === 'abmelden') {
    protokoll('abgemeldet');
    abmelden();
    sitzung_starten();
    melden('gut', 'Sie sind abgemeldet.');
    header('Location: ?');
    exit;
}

if (!angemeldet()) {
    $fehler = null;
    if ($post && $tat === 'anmelden') {
        token_pruefen();
        $rest = anmeldung_gesperrt();
        if ($rest > 0) {
            $fehler = 'Zu viele Fehlversuche. Bitte in ' . ceil($rest / 60) . ' Minuten noch einmal versuchen.';
        } else {
            $ergebnis = anmelden(trim((string) ($_POST['benutzer'] ?? '')), (string) ($_POST['passwort'] ?? ''));
            if ($ergebnis === 'ok') {
                $ziel = (string) ($_POST['weiter'] ?? '');
                header('Location: ' . (preg_match('/^\?[A-Za-z0-9=&_.%-]*$/', $ziel) ? $ziel : '?'));
                exit;
            }
            $fehler = $ergebnis === 'gesperrt'
                ? 'Dieses Konto ist gesperrt. Bitte an die Verwaltung des Portals wenden.'
                : 'Benutzername oder Passwort stimmt nicht.';
        }
    }
    seite_schlicht('Anmeldung', ansicht('anmelden', [
        'fehler' => $fehler,
        'weiter' => $post ? (string) ($_POST['weiter'] ?? '') : (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''),
    ]));
}

/* ------------------------------------------------ Medien ausliefern --- */

if ($seite === 'medium') {
    session_write_close();
    medium_ausliefern((string) ($_GET['pfad'] ?? ''));
}

/* Hochladen direkt aus der Medienauswahl eines Formulars — antwortet mit JSON */
if ($seite === 'medien-api' && $post) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $antwort = static function (array $daten): never {
        echo json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };
    if (!hash_equals($_SESSION['token'] ?? '', (string) ($_POST['token'] ?? ''))) {
        http_response_code(400);
        $antwort(['ok' => false, 'fehler' => 'Das Formular ist abgelaufen. Bitte die Seite neu laden.']);
    }
    $dateien = dateien_normalisieren($_FILES['datei'] ?? []);
    if (!$dateien) {
        $antwort(['ok' => false, 'fehler' => 'Es kam keine Datei an — vermutlich ist sie größer als die Upload-Grenze des Servers (' . groesse_lesbar(upload_grenze()) . ').']);
    }
    [$name, $f, $hinweis] = medium_hochladen($dateien[0]);
    $antwort($name
        ? ['ok' => true, 'pfad' => 'medien/' . $name, 'name' => $name, 'art' => medium_art_nach_endung($name), 'hinweis' => $hinweis]
        : ['ok' => false, 'fehler' => $f]);
}

/* ------------------------------------------------------------ Routen --- */

switch ($seite) {

    /* ------------------------------------------------------ Übersicht --- */
    case 'start':
        seite_ausgeben('Übersicht', 'start', ansicht('start', [
            'aenderungen' => alle_aenderungen(),
            'letzte' => letzte_veroeffentlichung(),
            'punkte' => offene_punkte(),
            'probleme' => veroeffentlichen_moeglich(),
            'protokoll' => array_slice(protokoll_lesen(40), 0, 6),
        ]));

    /* -------------------------------------------- Inhaltsbereich bearbeiten --- */
    case 'bereich':
        $id = (string) ($_GET['id'] ?? '');
        $b = bereich($id);
        if (!$b) {
            melden('fehler', 'Diesen Bereich gibt es nicht.');
            weiter('start');
        }
        $werte = bereich_laden($id);
        $konflikt = false;

        if ($post && $tat === 'speichern') {
            token_pruefen();
            $fehler = [];
            $neu = felder_einsammeln($b['felder'], $_POST['d'] ?? [], $werte, $fehler);
            if (!$fehler && ($_POST['stand'] ?? '') !== stand_kennung($werte) && empty($_POST['erzwingen'])) {
                $konflikt = true;
                $fehler[] = 'Während Sie bearbeitet haben, hat jemand anderes diesen Bereich gespeichert. Ihre Eingaben stehen unten noch — '
                    . 'bitte prüfen und mit „Trotzdem speichern“ übernehmen, oder die Seite neu laden, um den anderen Stand zu sehen.';
            }
            if ($fehler) {
                melden('fehler', 'So lässt sich das noch nicht speichern:', $fehler);
                $werte = $neu;
            } else {
                $offen = bereich_speichern($id, $neu);
                protokoll('gespeichert: ' . $b['titel']);
                melden('gut', $offen
                    ? 'Gespeichert. Die Änderung steht als Entwurf bereit — sichtbar wird sie mit „Veröffentlichen“.'
                    : 'Gespeichert. Der Inhalt entspricht jetzt wieder dem, was auf der Webseite steht.');
                weiter('bereich', ['id' => $id]);
            }
        }

        if ($post && $tat === 'verwerfen') {
            token_pruefen();
            entwurf_verwerfen($b['datei']);
            protokoll('Entwurf verworfen: ' . $b['titel']);
            melden('gut', 'Der Bereich zeigt wieder den Stand der Webseite.');
            weiter('bereich', ['id' => $id]);
        }

        seite_ausgeben($b['titel'], 'bereich:' . $id, ansicht('bereich', [
            'id' => $id, 'b' => $b, 'werte' => $werte, 'konflikt' => $konflikt,
            'stand' => $konflikt ? (string) ($_POST['stand'] ?? '') : stand_kennung(bereich_laden($id)),
            'geaendert' => bereich_geaendert($id),
        ]), ['bearbeiten' => true]);

    /* ------------------------------------------------------- Sektionen --- */
    case 'sektionen':
        if ($post) {
            token_pruefen();
            $id = (string) ($_POST['id'] ?? '');
            $s = $id !== '' ? sektion_holen($id) : null;
            switch ($tat) {
                case 'neu':
                    $typ = (string) ($_POST['typ'] ?? '');
                    $neuId = sektion_neu($typ);
                    if ($neuId) {
                        protokoll('Sektion hinzugefügt: ' . sektion_typ_name($typ));
                        melden('gut', 'Die Sektion „' . sektion_typ_name($typ) . '“ ist angelegt — noch ausgeblendet. Inhalte eintragen, dann „Auf der Webseite zeigen“ einschalten.');
                        weiter('sektion', ['id' => $neuId]);
                    }
                    melden('fehler', 'Diese Sektion lässt sich nicht hinzufügen — sie ist nur einmal möglich und schon vorhanden.');
                    break;
                case 'hoch':
                case 'runter':
                    sektion_verschieben($id, $tat === 'hoch' ? -1 : 1);
                    break;
                case 'menue':
                    $an = sektion_menue_umschalten($id);
                    if ($s && $an !== null) {
                        protokoll('Menüpunkt ' . ($an ? 'eingeschaltet' : 'ausgeschaltet') . ': ' . sektion_titel($s));
                        melden('gut', $an ? 'Der Menüpunkt ist eingeschaltet — sichtbar nach dem Veröffentlichen.' : 'Der Menüpunkt ist ausgeschaltet.');
                    }
                    break;
                case 'zeitenmenue':
                    $liste = sektionen_entwurf();
                    foreach ($liste as $i => $x) {
                        if (($x['id'] ?? '') === $id) {
                            $an = empty($x['zeitenMenue']['zeigen']);
                            $liste[$i]['zeitenMenue'] = ['zeigen' => $an, 'label' => (string) ($x['zeitenMenue']['label'] ?? '')];
                            sektionen_speichern($liste);
                            protokoll('Menüpunkt ' . begriff('zeiten') . ($an ? ' eingeschaltet' : ' ausgeschaltet'));
                            melden('gut', $an ? 'Der Menüpunkt „' . begriff('zeiten') . '“ springt jetzt zu den Zeiten im Kontaktbereich — sichtbar nach dem Veröffentlichen.' : 'Der Menüpunkt „' . begriff('zeiten') . '“ ist ausgeschaltet.');
                        }
                    }
                    break;
                case 'umschalten':
                    $an = sektion_umschalten($id);
                    if ($s && $an !== null) {
                        protokoll('Sektion ' . ($an ? 'eingeblendet' : 'ausgeblendet') . ': ' . sektion_titel($s));
                    }
                    break;
                case 'duplizieren':
                    $kopie = sektion_duplizieren($id);
                    if ($kopie) {
                        melden('gut', 'Kopie angelegt — noch ausgeblendet.');
                        weiter('sektion', ['id' => $kopie]);
                    }
                    break;
                case 'loeschen':
                    $weg = sektion_loeschen($id);
                    if ($weg) {
                        protokoll('Sektion entfernt: ' . sektion_titel($weg));
                        melden('gut', 'Die Sektion „' . sektion_titel($weg) . '“ ist entfernt. Bis zum Veröffentlichen lässt sich das mit „Entwurf verwerfen“ zurücknehmen.');
                    }
                    break;
                case 'verwerfen':
                    entwurf_verwerfen('startseite');
                    protokoll('Entwurf verworfen: Sektionen');
                    melden('gut', 'Die Sektionen zeigen wieder den Stand der Webseite.');
                    break;
            }
            weiter('sektionen');
        }
        seite_ausgeben('Sektionen der Startseite', 'sektionen', ansicht('sektionen', [
            'sektionen' => sektionen_entwurf(),
            'geaendert' => entwurf_offen('startseite'),
        ]));

    case 'sektion':
        $id = (string) ($_GET['id'] ?? '');
        $s = sektion_holen($id);
        if (!$s) {
            melden('fehler', 'Diese Sektion gibt es nicht (mehr).');
            weiter('sektionen');
        }
        $felder = sektion_felder((string) $s['typ']);
        $werte = $s;
        $konflikt = false;

        if ($post && $tat === 'speichern') {
            token_pruefen();
            $fehler = [];
            // Werte ohne Feld (z. B. Zusatzfelder eines anderen Designs) bleiben erhalten
            $neu = felder_einsammeln($felder, $_POST['d'] ?? [], $s, $fehler) + $s;
            $anker = (string) ($neu['anker'] ?? '');
            if ($anker !== '' && !anker_frei($anker, $id, (string) $s['typ'])) {
                $fehler[] = 'Die Adresse #' . $anker . ' ist schon vergeben oder reserviert.';
            }
            if (!$fehler && ($_POST['stand'] ?? '') !== stand_kennung($s) && empty($_POST['erzwingen'])) {
                $konflikt = true;
                $fehler[] = 'Während Sie bearbeitet haben, hat jemand anderes diese Sektion gespeichert. Bitte prüfen und mit „Trotzdem speichern“ übernehmen.';
            }
            if ($fehler) {
                melden('fehler', 'So lässt sich das noch nicht speichern:', $fehler);
                $werte = $neu;
            } else {
                $verweise = $anker !== ($s['anker'] ?? '') ? anker_verweise((string) $s['anker']) : [];
                sektion_speichern($id, $neu);
                protokoll('gespeichert: Sektion „' . sektion_titel($neu) . '“');
                melden('gut', 'Gespeichert. Sichtbar wird die Änderung mit „Veröffentlichen“.');
                if ($verweise) {
                    melden('hinweis', 'Die Adresse wurde von #' . $s['anker'] . ' zu #' . $anker . ' geändert. Diese Stellen verlinken noch auf die alte Adresse:', $verweise);
                }
                weiter('sektion', ['id' => $id]);
            }
        }

        seite_ausgeben(sektion_typ_name((string) $s['typ']) . ': ' . sektion_titel($s), 'sektionen', ansicht('sektion', [
            'id' => $id, 's' => $s, 'felder' => $felder, 'werte' => $werte, 'konflikt' => $konflikt,
            'stand' => $konflikt ? (string) ($_POST['stand'] ?? '') : stand_kennung($s),
            'geaendert' => sektion_geaendert($id),
        ]), ['bearbeiten' => true]);

    /* ---------------------------------------------------------- Medien --- */
    case 'medien':
        if ($post) {
            token_pruefen();
            if ($tat === 'hochladen') {
                $gut = [];
                $fehler = [];
                $hinweise = [];
                foreach (dateien_normalisieren($_FILES['dateien'] ?? []) as $d) {
                    [$name, $f, $hinweis] = medium_hochladen($d);
                    if ($name) {
                        $gut[] = $name;
                        if ($hinweis) {
                            $hinweise[] = $name . ': ' . $hinweis;
                        }
                    } elseif ($f) {
                        $fehler[] = ($d['name'] ?: 'Datei') . ': ' . $f;
                    }
                }
                if ($gut) {
                    melden('gut', count($gut) === 1 ? 'Eine Datei hochgeladen.' : count($gut) . ' Dateien hochgeladen.', $hinweise);
                }
                if ($fehler) {
                    melden('fehler', 'Nicht alles hat geklappt:', $fehler);
                }
                if (!$gut && !$fehler && empty($_FILES)) {
                    melden('fehler', 'Es kam keine Datei an — vermutlich war sie größer als die Upload-Grenze des Servers (' . groesse_lesbar(upload_grenze()) . ').');
                }
            } elseif ($tat === 'ersetzen') {
                $name = (string) ($_POST['name'] ?? '');
                $dateien = dateien_normalisieren($_FILES['datei'] ?? []);
                [$f, $hinweis] = $dateien ? medium_ersetzen($name, $dateien[0]) : ['Es wurde keine Datei ausgewählt.', null];
                $f ? melden('fehler', $f) : melden('gut', '„' . $name . '“ ist ersetzt — überall, wo die Datei verwendet wird.' . ($hinweis ? ' ' . $hinweis : ''));
            } elseif ($tat === 'loeschen') {
                $name = (string) ($_POST['name'] ?? '');
                $verwendung = medium_verwendung('medien/' . $name);
                if ($verwendung) {
                    melden('fehler', 'Die Datei wird noch verwendet und wurde nicht gelöscht. Bitte dort zuerst eine andere eintragen (und veröffentlichen):', array_values($verwendung));
                } else {
                    $f = medium_loeschen($name);
                    $f ? melden('fehler', $f) : melden('gut', 'Datei gelöscht.');
                }
            }
            weiter('medien', array_filter(['art' => (string) ($_GET['art'] ?? '')]));
        }
        $art = (string) ($_GET['art'] ?? '');
        seite_ausgeben('Medien', 'medien', ansicht('medien', [
            'art' => isset(MEDIEN_ARTEN[$art]) ? $art : '',
            'medien' => medien_liste(isset(MEDIEN_ARTEN[$art]) ? $art : null),
            'alle' => medien_liste(),
        ]), ['weit' => true]);

    /* -------------------------------------------------------- Hinweise --- */
    case 'hinweise':
        if (!modul('hinweise')) {
            weiter('start');
        }
        if ($post) {
            token_pruefen();
            $id = (string) ($_POST['id'] ?? '');
            $h = hinweis_holen($id);
            if ($tat === 'an' || $tat === 'aus') {
                $f = hinweis_schalten($id, $tat === 'an');
                if ($f) {
                    melden('fehler', $f);
                } else {
                    protokoll('Hinweis ' . ($tat === 'an' ? 'eingeschaltet' : 'ausgeschaltet') . ': ' . str_replace("\n", ' ', (string) ($h['titel'] ?? '')));
                    melden('gut', $tat === 'an'
                        ? 'Eingeschaltet. Mit „Veröffentlichen“ erscheint der Hinweis auf der Webseite.'
                        : 'Ausgeschaltet. Mit „Veröffentlichen“ verschwindet der Hinweis von der Webseite.');
                }
            } elseif ($tat === 'loeschen') {
                hinweis_loeschen($id);
                protokoll('Hinweis gelöscht: ' . str_replace("\n", ' ', (string) ($h['titel'] ?? '')));
                melden('gut', 'Der Hinweis wurde gelöscht.');
            } elseif ($tat === 'duplizieren') {
                $neu = hinweis_duplizieren($id);
                if ($neu) {
                    weiter('hinweis', ['id' => $neu]);
                }
            }
            weiter('hinweise');
        }
        seite_ausgeben(begriff('hinweise'), 'hinweise', ansicht('hinweise', ['eintraege' => hinweise_laden()]));

    case 'hinweis':
        if (!modul('hinweise')) {
            weiter('start');
        }
        $id = isset($_GET['id']) ? (string) $_GET['id'] : null;
        $eintrag = $id !== null ? hinweis_holen($id) : null;
        if ($id !== null && !$eintrag) {
            melden('fehler', 'Diesen Hinweis gibt es nicht mehr.');
            weiter('hinweise');
        }
        if ($post && $tat === 'speichern') {
            token_pruefen();
            $fehler = [];
            $neu = felder_einsammeln(hinweis_felder(), $_POST['d'] ?? [], $eintrag ?? hinweis_leer(), $fehler);
            if (($neu['gueltigVon'] ?? '') !== '' && ($neu['gueltigBis'] ?? '') !== '' && $neu['gueltigBis'] < $neu['gueltigVon']) {
                $fehler[] = '„Zeigen bis“ liegt vor „Zeigen ab“.';
            }
            if ($fehler) {
                melden('fehler', 'So lässt sich das noch nicht speichern:', $fehler);
                $eintrag = $neu + ['id' => $id];
            } else {
                $neuId = hinweis_speichern($neu, $id);
                protokoll('Hinweis gespeichert: ' . str_replace("\n", ' ', (string) $neu['titel']));
                if (!empty($_POST['einschalten'])) {
                    $f = hinweis_schalten($neuId, true);
                    $f ? melden('fehler', $f) : melden('gut', 'Gespeichert und eingeschaltet. Jetzt noch veröffentlichen.');
                } else {
                    melden('gut', hinweis_ist_an($neuId) ? 'Gespeichert. Die Änderung erscheint mit „Veröffentlichen“.' : 'Hinweis gespeichert.');
                }
                weiter('hinweise');
            }
        }
        seite_ausgeben($eintrag && $id !== null ? 'Hinweis bearbeiten' : 'Neuer Hinweis', 'hinweise', ansicht('hinweis', [
            'id' => $id, 'eintrag' => $eintrag ?? hinweis_leer(),
        ]), ['bearbeiten' => true]);

    /* ------------------------------------------------- Veröffentlichen --- */
    case 'veroeffentlichen':
        if ($post) {
            token_pruefen();
            if ($tat === 'los' || $tat === 'neu_bauen') {
                $ergebnis = $tat === 'los' ? veroeffentlichen() : neu_bauen();
                if ($ergebnis['ok']) {
                    $_SESSION['ergebnis'] = $ergebnis;
                    weiter('veroeffentlicht');
                }
                melden('fehler', (string) $ergebnis['fehler']);
            } elseif ($tat === 'alles_verwerfen') {
                foreach (offene_entwuerfe() as $n) {
                    entwurf_verwerfen($n);
                }
                // Die Hinweis-Ablage wieder an den Live-Stand angleichen
                $ablage = hinweise_ablage();
                $ablage['aktiv'] = array_values(array_intersect(array_column($ablage['eintraege'], 'id'), array_column(live_laden('hinweise')['eintraege'] ?? [], 'id')));
                json_schreiben(pfad_ablage('hinweise.json'), $ablage);
                protokoll('alle Entwürfe verworfen');
                melden('gut', 'Alle Entwürfe wurden verworfen. Die Webseite ist unverändert.');
                weiter('start');
            } elseif ($tat === 'wiederherstellen') {
                [$f, $anzahl] = sicherung_wiederherstellen((string) ($_POST['name'] ?? ''));
                if ($f) {
                    melden('fehler', $f);
                } elseif ($anzahl === 0) {
                    melden('hinweis', 'Diese Sicherung entspricht bereits dem aktuellen Stand — es gibt nichts zurückzuholen.');
                } else {
                    melden('gut', 'Der gesicherte Stand ist als Entwurf geladen. Unten steht, was sich dadurch ändert — mit „Jetzt veröffentlichen“ geht er live.');
                }
            }
            weiter('veroeffentlichen');
        }
        seite_ausgeben('Veröffentlichen', 'veroeffentlichen', ansicht('veroeffentlichen', [
            'aenderungen' => alle_aenderungen(),
            'punkte' => offene_punkte(),
            'probleme' => veroeffentlichen_moeglich(),
            'sicherungen' => sicherungen_liste(),
            'letzte' => letzte_veroeffentlichung(),
        ]));

    case 'veroeffentlicht':
        $ergebnis = $_SESSION['ergebnis'] ?? null;
        unset($_SESSION['ergebnis']);
        if (!$ergebnis) {
            weiter('start');
        }
        seite_ausgeben('Veröffentlicht', 'veroeffentlichen', ansicht('veroeffentlicht', ['ergebnis' => $ergebnis]));

    /* --------------------------------------------------------- Vorschau --- */
    case 'vorschau':
        $designWahl = (string) ($_GET['design'] ?? '');
        seite_ausgeben('Vorschau des Entwurfs', 'vorschau', ansicht('vorschau', [
            'seiteWahl' => in_array($_GET['p'] ?? '', ['impressum', 'datenschutz', 'barrierefreiheit'], true) ? $_GET['p'] : '',
            'moeglich' => generator_laden() && is_file(pfad_privat('vorlagen/seite.css')),
            'designWahl' => design_laden($designWahl) ? $designWahl : '',
        ]), ['weit' => true]);

    /* ------------------------------------------------------------ Design --- */
    case 'design':
        if (!generator_laden()) {
            melden('fehler', 'Der Generator (privat/bauen.php) fehlt — ohne ihn lassen sich Designs nicht verwalten.');
            weiter('start');
        }
        if ($post) {
            token_pruefen();
            $id = (string) ($_POST['id'] ?? '');
            $d = design_laden($id);
            if ($tat === 'verwenden') {
                $f = design_verwenden($id);
                if ($f) {
                    melden('fehler', $f);
                } else {
                    protokoll('Design gewählt: ' . ($d['name'] ?? $id));
                    melden('gut', '„' . ($d['name'] ?? $id) . '“ ist für die Webseite gewählt. In der Vorschau ansehen — online geht es mit „Veröffentlichen“.');
                }
            } elseif ($tat === 'duplizieren') {
                $neu = design_duplizieren($id);
                if ($neu) {
                    melden('gut', 'Eigene Fassung angelegt — jetzt anpassen.');
                    weiter('design-bearbeiten', ['id' => $neu]);
                }
                melden('fehler', 'Das Design ließ sich nicht kopieren.');
            } elseif ($tat === 'loeschen') {
                $f = design_loeschen($id);
                $f ? melden('fehler', $f) : melden('gut', 'Design gelöscht.');
            } elseif ($tat === 'hochladen') {
                $dateien = dateien_normalisieren($_FILES['datei'] ?? []);
                if (!$dateien) {
                    melden('fehler', 'Es kam keine Datei an — vermutlich ist sie größer als die Upload-Grenze des Servers (' . groesse_lesbar(upload_grenze()) . ').');
                } else {
                    $zipTheme = null;
                    if (class_exists('ZipArchive') && ($dateien[0]['error'] ?? 1) === UPLOAD_ERR_OK && is_uploaded_file((string) $dateien[0]['tmp_name'])) {
                        $probe = new ZipArchive();
                        if ($probe->open((string) $dateien[0]['tmp_name']) === true) {
                            $zipTheme = theme_zip_praefix($probe) !== null;
                            $probe->close();
                        }
                    }
                    if ($zipTheme) {
                        // Theme-Pakete bringen Markup und Skripte mit — das darf nur die Verwaltung
                        if (!darf('einstellungen')) {
                            melden('fehler', 'Theme-Pakete (mit eigenen Vorlagen und Skripten) darf nur die Verwaltung hochladen.');
                            weiter('design');
                        }
                        [$neu, $befunde] = theme_installieren((string) $dateien[0]['tmp_name'], (string) $dateien[0]['name']);
                        if ($neu) {
                            melden($befunde ? 'hinweis' : 'gut', 'Das Theme „' . (design_laden($neu)['name'] ?? $neu) . '“ ist installiert. In der Vorschau ansehen — mit „Verwenden“ wählen Sie es für die Webseite.'
                                . ($befunde ? ' Dabei ist aufgefallen:' : ''), $befunde);
                        } else {
                            melden('fehler', 'Das Theme ließ sich nicht installieren:', $befunde);
                        }
                        weiter('design');
                    }
                    [$kennung, $f] = design_import_analysieren($dateien[0]);
                    if ($kennung) {
                        weiter('design-import', ['k' => $kennung]);
                    }
                    melden('fehler', (string) $f);
                }
            }
            weiter('design');
        }
        $designs = [];
        foreach (array_keys(design_liste()) as $id) {
            $designs[$id] = design_laden($id);
        }
        seite_ausgeben('Design', 'design', ansicht('design', [
            'designs' => $designs, 'aktiv' => design_aktiv(), 'live' => design_live(), 'zipMoeglich' => class_exists('ZipArchive'),
        ]), ['weit' => true]);

    case 'design-import':
        generator_laden();
        $kennung = (string) ($_GET['k'] ?? '');
        $analyse = design_import_laden($kennung);
        if (!$analyse) {
            melden('fehler', 'Dieser Import ist abgelaufen. Bitte die Datei noch einmal hochladen.');
            weiter('design');
        }
        $werte = [];
        if ($post && $tat === 'uebernehmen') {
            token_pruefen();
            [$neu, $f] = design_import_uebernehmen($kennung, (string) ($_POST['name'] ?? ''), (array) ($_POST['zuordnung'] ?? []),
                (array) ($_POST['eigen'] ?? []), !empty($_POST['aurora']), 'aurora');
            if ($neu) {
                melden('gut', 'Das Design ist gespeichert. Mit „Verwenden“ wählen Sie es für die Webseite — vorher lohnt ein Blick in die Vorschau.');
                weiter('design');
            }
            melden('fehler', 'So lässt sich das noch nicht speichern:', $f);
            $werte = $_POST;
        }
        seite_ausgeben('Design übernehmen', 'design', ansicht('design-import', [
            'kennung' => $kennung, 'analyse' => $analyse, 'werte' => $werte,
        ]), ['weit' => true]);

    case 'design-bearbeiten':
        generator_laden();
        $id = (string) ($_GET['id'] ?? '');
        $roh = json_lesen(pfad_design($id . '/design.json'));
        $d = design_laden($id);
        if (!$d || !$roh || !empty($roh['schutz'])) {
            melden('fehler', 'Dieses Design lässt sich nicht bearbeiten. Vorlagen bitte zuerst kopieren.');
            weiter('design');
        }
        $werte = ['name' => $d['name'], 'beschreibung' => $d['beschreibung'] ?? '', 'tokens' => $d['tokens']];
        if ($post && $tat === 'speichern') {
            token_pruefen();
            $fehler = [];
            $neu = felder_einsammeln(design_felder(), $_POST['d'] ?? [], $werte, $fehler);
            if ($fehler) {
                melden('fehler', 'So lässt sich das noch nicht speichern:', $fehler);
                $werte = $neu;
            } else {
                $roh['name'] = $neu['name'];
                $roh['beschreibung'] = $neu['beschreibung'];
                $roh['tokens'] = array_map(static fn($w) => is_string($w) ? ws_css_wert($w) : $w, $neu['tokens']);
                design_speichern($id, $roh);
                protokoll('Design bearbeitet: ' . $neu['name']);
                melden('gut', $id === design_live()
                    ? 'Gespeichert. Dieses Design läuft auf der Webseite — mit „Webseite neu erzeugen“ (unter Veröffentlichen) wird die Änderung sichtbar.'
                    : 'Gespeichert.');
                weiter('design-bearbeiten', ['id' => $id]);
            }
        }
        seite_ausgeben('Design bearbeiten: ' . $d['name'], 'design', ansicht('design-bearbeiten', ['id' => $id, 'd' => $d, 'werte' => $werte]),
            ['bearbeiten' => true, 'weit' => true]);

    case 'design-export':
        $id = (string) ($_GET['id'] ?? '');
        $roh = preg_match('/^[a-z0-9-]+$/', $id) ? json_lesen(pfad_design($id . '/design.json')) : null;
        if (!$roh) {
            weiter('design');
        }
        if (!empty($roh['theme']) || ($_GET['art'] ?? '') === 'zip') {
            $zipDatei = theme_exportieren($id);
            if ($zipDatei) {
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="theme-' . $id . '.zip"');
                header('Content-Length: ' . filesize($zipDatei));
                header('Cache-Control: no-store');
                readfile($zipDatei);
                @unlink($zipDatei);
                exit;
            }
        }
        unset($roh['schutz']);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="design-' . $id . '.json"');
        header('Cache-Control: no-store');
        echo json_encode($roh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;

    /* ---------------------------------------------------------- Konto --- */
    case 'konto':
        $ich = aktueller_benutzer();
        if ($post && $tat === 'anzeige') {
            token_pruefen();
            $f = benutzer_aendern($ich['name'], (string) ($_POST['anzeige'] ?? ''), (string) $ich['rolle']);
            $f ? melden('fehler', 'Das hat nicht geklappt:', $f) : melden('gut', 'Gespeichert.');
            weiter('konto');
        }
        if ($post && $tat === 'passwort') {
            token_pruefen();
            if (!password_verify((string) ($_POST['aktuell'] ?? ''), (string) $ich['hash'])) {
                melden('fehler', 'Das bisherige Passwort stimmt nicht.');
            } elseif (($_POST['neu'] ?? '') !== ($_POST['neu2'] ?? '')) {
                melden('fehler', 'Die beiden neuen Passwörter stimmen nicht überein.');
            } else {
                $f = benutzer_passwort_setzen($ich['name'], (string) $_POST['neu']);
                if ($f) {
                    melden('fehler', 'Das hat nicht geklappt:', $f);
                } else {
                    $_SESSION['version'] = (int) (benutzer_holen($ich['name'])['version'] ?? 1);
                    session_regenerate_id(true);
                    protokoll('eigenes Passwort geändert');
                    melden('gut', 'Das Passwort ist geändert. Andere Sitzungen mit diesem Konto sind damit abgemeldet.');
                }
            }
            weiter('konto');
        }
        seite_ausgeben('Mein Konto', 'konto', ansicht('konto', ['ich' => $ich]));

    /* ------------------------------------------------------- Benutzer --- */
    case 'benutzer':
        recht_pruefen('benutzer');
        if ($post) {
            token_pruefen();
            $name = trim((string) ($_POST['name'] ?? ''));
            $f = match ($tat) {
                'anlegen' => benutzer_anlegen($name, (string) ($_POST['anzeige'] ?? ''), (string) ($_POST['rolle'] ?? ''), (string) ($_POST['passwort'] ?? '')),
                'aendern' => benutzer_aendern($name, (string) ($_POST['anzeige'] ?? ''), (string) ($_POST['rolle'] ?? '')),
                'passwort' => benutzer_passwort_setzen($name, (string) ($_POST['passwort'] ?? '')),
                'sperren' => benutzer_sperren($name, true),
                'entsperren' => benutzer_sperren($name, false),
                'loeschen' => benutzer_loeschen($name),
                default => ['Unbekannte Aktion.'],
            };
            if ($f) {
                melden('fehler', 'Das hat nicht geklappt:', $f);
            } else {
                $texte = ['anlegen' => 'Konto „%s“ angelegt.', 'aendern' => 'Konto „%s“ geändert.', 'passwort' => 'Neues Passwort für „%s“ gesetzt — die Person ist überall abgemeldet.',
                          'sperren' => 'Konto „%s“ gesperrt.', 'entsperren' => 'Konto „%s“ entsperrt.', 'loeschen' => 'Konto „%s“ gelöscht.'];
                protokoll('Benutzer: ' . sprintf($texte[$tat], $name));
                melden('gut', sprintf($texte[$tat], $name));
            }
            weiter('benutzer');
        }
        seite_ausgeben('Benutzer', 'benutzer', ansicht('benutzer', ['alle' => benutzer_alle()]));

    /* -------------------------------------------------- Einstellungen --- */
    case 'einstellungen':
        recht_pruefen('einstellungen');
        $zumKopieren = null;
        if ($post && $tat === 'speichern') {
            token_pruefen();
            [$fehler, $zumKopieren] = einstellungen_speichern($_POST);
            if (!$fehler) {
                melden('gut', 'Gespeichert. Die Pfade gelten ab sofort.');
                weiter('einstellungen');
            }
            melden('fehler', 'Das ließ sich noch nicht speichern:', $fehler);
        }
        if ($post && $tat === 'smtp') {
            token_pruefen();
            $fehler = smtp_speichern($_POST);
            if (!$fehler) {
                melden('gut', 'Zugangsdaten für den E-Mail-Versand gespeichert. Am besten gleich eine Testmail senden.');
                weiter('einstellungen');
            }
            melden('fehler', 'Das ließ sich noch nicht speichern:', $fehler);
        }
        if ($post && $tat === 'smtp-test') {
            token_pruefen();
            $problem = smtp_testen();
            $problem === null
                ? melden('gut', 'Testmail an ' . smtp_empfaenger() . ' gesendet. Bitte im Postfach nachsehen (auch im Spam-Ordner).')
                : melden('fehler', 'Die Testmail ging nicht raus:', [$problem]);
            weiter('einstellungen');
        }
        seite_ausgeben('Einstellungen', 'einstellungen', ansicht('einstellungen', [
            'smtp' => smtp_werte(),
            'smtpEmpfaenger' => smtp_empfaenger(),
            'werte' => $post ? ($_POST + konfig()) : konfig(),
            'geraten' => pfade_raten(),
            'probleme' => veroeffentlichen_moeglich(),
            'zumKopieren' => $zumKopieren,
        ]));

    /* -------------------------------------------------- Systemprüfung --- */
    case 'diagnose':
        recht_pruefen('diagnose');
        $gruppen = diagnose();
        seite_ausgeben('Systemprüfung', 'diagnose', ansicht('diagnose', ['gruppen' => $gruppen, 'bilanz' => diagnose_bilanz($gruppen)]));

    case 'protokoll':
        recht_pruefen('protokoll');
        seite_ausgeben('Protokoll', 'protokoll', ansicht('protokoll', ['eintraege' => protokoll_lesen(500)]));

    default:
        http_response_code(404);
        melden('fehler', 'Diese Seite gibt es nicht.');
        weiter('start');
}
