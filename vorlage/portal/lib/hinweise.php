<?php
/**
 * Hinweise & Urlaub: Leisten und Fenster auf der Webseite.
 *
 * Das Portal führt eine eigene Ablage (privat/ablage/hinweise.json), damit
 * sich Hinweise vorbereiten und wiederverwenden lassen — der Sommerurlaub
 * kommt jedes Jahr wieder. Eingeschaltete Hinweise wandern in den Entwurf von
 * hinweise.json und gehen mit dem nächsten Veröffentlichen live.
 *
 * Zusätzlich prüft der Browser den Zeitraum: Ein
 * Urlaubshinweis ab dem 1. August erscheint am 1. August von selbst und
 * verschwindet nach dem letzten Tag, ohne dass jemand an diesen Tagen
 * veröffentlichen muss.
 */

declare(strict_types=1);

if (!defined('ADMIN_WURZEL')) { http_response_code(404); exit; }

const HINWEIS_ARTEN = [
    'urlaub'  => ['Urlaub & Schließzeit', 'Geschlossen, Vertretung'],
    'info'    => ['Information', 'Allgemeine Mitteilung'],
    'neu'     => ['Neuigkeit', 'Neues Angebot, neues Gesicht im Team'],
    'wichtig' => ['Wichtig', 'Telefonstörung, kurzfristiger Ausfall'],
];

const HINWEIS_DARSTELLUNG = [
    'banner'  => 'Leiste oben auf der Seite',
    'fenster' => 'Fenster beim Aufruf der Seite',
];

function hinweise_ablage(): array
{
    $a = json_lesen(pfad_ablage('hinweise.json')) ?? [];
    return ['eintraege' => is_array($a['eintraege'] ?? null) ? $a['eintraege'] : [],
            'aktiv' => is_array($a['aktiv'] ?? null) ? array_values($a['aktiv']) : []];
}

function hinweise_laden(): array
{
    return hinweise_ablage()['eintraege'];
}

function hinweis_holen(string $id): ?array
{
    foreach (hinweise_laden() as $h) {
        if (($h['id'] ?? '') === $id) {
            return $h;
        }
    }
    return null;
}

function hinweis_ist_an(string $id): bool
{
    return in_array($id, hinweise_ablage()['aktiv'], true);
}

function hinweise_sichern(array $ablage): void
{
    json_schreiben(pfad_ablage('hinweise.json'), $ablage);
    hinweise_entwurf_schreiben($ablage);
}

/** Die eingeschalteten Hinweise in den Entwurf von hinweise.json schreiben. */
function hinweise_entwurf_schreiben(array $ablage): void
{
    $an = [];
    foreach ($ablage['eintraege'] as $h) {
        if (in_array($h['id'] ?? '', $ablage['aktiv'], true)) {
            $an[] = [
                'id' => $h['id'],
                'art' => $h['art'] ?? 'info',
                'darstellung' => $h['darstellung'] ?? 'banner',
                'eyebrow' => $h['eyebrow'] ?? '',
                'titel' => $h['titel'] ?? '',
                'text' => $h['text'] ?? '',
                'bild' => $h['bild'] ?? '',
                'aktion' => ['text' => $h['aktion']['text'] ?? '', 'ziel' => $h['aktion']['ziel'] ?? ''],
                'gueltigVon' => $h['gueltigVon'] ?? '',
                'gueltigBis' => $h['gueltigBis'] ?? '',
                'einmalProBesuch' => !empty($h['einmalProBesuch']),
                'verzoegerungMs' => (int) ($h['verzoegerungMs'] ?? 1200),
            ];
        }
    }
    $alt = entwurf_laden('hinweise');
    entwurf_speichern('hinweise', ['_hinweis' => $alt['_hinweis'] ?? 'Wird vom Portal geschrieben.', 'eintraege' => $an]);
}

function hinweis_felder(): array
{
    $arten = [];
    foreach (HINWEIS_ARTEN as $k => [$name, $was]) {
        $arten[$k] = $name . ' — ' . $was;
    }
    return [
        'art' => ['typ' => 'auswahl', 'label' => 'Art', 'optionen' => $arten, 'hilfe' => 'Bestimmt Farbe und Zeichen.'],
        'darstellung' => ['typ' => 'auswahl', 'label' => 'Darstellung', 'optionen' => HINWEIS_DARSTELLUNG,
                          'hilfe' => 'Die Leiste stört nicht und passt für Urlaub. Das Fenster fällt auf — sparsam einsetzen.'],
        'eyebrow' => ['typ' => 'text', 'label' => 'Kleinzeile', 'hilfe' => 'z. B. „Urlaub“ — nur im Fenster.'],
        'titel' => ['typ' => 'zeile', 'label' => 'Überschrift', 'pflicht' => true, 'breit' => true],
        'text' => ['typ' => 'flaeche', 'label' => 'Text', 'breit' => true, 'zeilen' => 3,
                   'hilfe' => 'z. B. „In dringenden Fällen erreichen Sie … unter …“.'],
        'bild' => ['typ' => 'medium', 'art' => 'bild', 'label' => 'Bild', 'hilfe' => 'Nur im Fenster. Kann leer bleiben.'],
        'aktion' => ['typ' => 'objekt', 'label' => 'Knopf', 'hilfe' => 'Nur wenn Beschriftung und Ziel ausgefüllt sind, erscheint ein Knopf.', 'felder' => [
            'text' => ['typ' => 'text', 'label' => 'Beschriftung'],
            'ziel' => ['typ' => 'link', 'label' => 'Ziel'],
        ]],
        'zeitraum' => ['typ' => 'abschnitt', 'label' => 'Zeitraum und Verhalten', 'felder' => [
            'gueltigVon' => ['typ' => 'datum', 'label' => 'Zeigen ab', 'hilfe' => 'Leer = sofort nach dem Veröffentlichen.'],
            'gueltigBis' => ['typ' => 'datum', 'label' => 'Zeigen bis einschließlich', 'hilfe' => 'Danach verschwindet der Hinweis von selbst.'],
            'einmalProBesuch' => ['typ' => 'schalter', 'label' => 'Nach dem Schließen nicht erneut zeigen',
                                  'hilfe' => 'Der Browser merkt es sich selbst — ohne Cookies.'],
            'verzoegerungMs' => ['typ' => 'zahl', 'label' => 'Fenster erscheint nach (Millisekunden)', 'min' => 0, 'max' => 20000,
                                 'hilfe' => '1200 = nach 1,2 Sekunden. Nur beim Fenster.'],
        ]],
    ];
}

function hinweis_leer(): array
{
    return ['id' => '', 'art' => 'urlaub', 'darstellung' => 'banner', 'eyebrow' => 'Urlaub', 'titel' => '', 'text' => '',
            'bild' => '', 'aktion' => ['text' => '', 'ziel' => ''], 'gueltigVon' => '', 'gueltigBis' => '',
            'einmalProBesuch' => true, 'verzoegerungMs' => 1200];
}

function hinweis_speichern(array $eintrag, ?string $id): string
{
    $ablage = hinweise_ablage();
    $jetzt = date('c');
    if ($id === null || hinweis_holen($id) === null) {
        $eintrag['id'] = date('ymd') . '-' . bin2hex(random_bytes(3));
        $eintrag['erstellt'] = $jetzt;
        $eintrag['geaendert'] = $jetzt;
        array_unshift($ablage['eintraege'], $eintrag);
    } else {
        foreach ($ablage['eintraege'] as $i => $h) {
            if (($h['id'] ?? '') === $id) {
                $eintrag['id'] = $id;
                $eintrag['erstellt'] = $h['erstellt'] ?? $jetzt;
                $eintrag['geaendert'] = $jetzt;
                $ablage['eintraege'][$i] = $eintrag;
            }
        }
    }
    hinweise_sichern($ablage);
    return $eintrag['id'];
}

function hinweis_loeschen(string $id): void
{
    $ablage = hinweise_ablage();
    $ablage['eintraege'] = array_values(array_filter($ablage['eintraege'], static fn($h) => ($h['id'] ?? '') !== $id));
    $ablage['aktiv'] = array_values(array_diff($ablage['aktiv'], [$id]));
    hinweise_sichern($ablage);
}

function hinweis_duplizieren(string $id): ?string
{
    $h = hinweis_holen($id);
    if (!$h) {
        return null;
    }
    $h['titel'] = trim(($h['titel'] ?? '') . ' (Kopie)');
    unset($h['id']);
    return hinweis_speichern($h, null);
}

function hinweis_schalten(string $id, bool $an): ?string
{
    $h = hinweis_holen($id);
    if (!$h) {
        return 'Diesen Hinweis gibt es nicht mehr.';
    }
    if ($an && trim((string) ($h['titel'] ?? '')) === '') {
        return 'Ohne Überschrift zeigt die Webseite nichts an — bitte zuerst eine Überschrift eintragen.';
    }
    if ($an && ($h['gueltigBis'] ?? '') !== '' && $h['gueltigBis'] < date('Y-m-d')) {
        return 'Der Zeitraum dieses Hinweises ist bereits vorbei. Bitte zuerst das Datum anpassen.';
    }
    $ablage = hinweise_ablage();
    $ablage['aktiv'] = array_values(array_diff($ablage['aktiv'], [$id]));
    if ($an) {
        $ablage['aktiv'][] = $id;
    }
    hinweise_sichern($ablage);
    return null;
}

/** @return array{0:string,1:string,2:string} [Zustand, Wort, Erklärung] */
function hinweis_status(array $h): array
{
    $heute = date('Y-m-d');
    $von = (string) ($h['gueltigVon'] ?? '');
    $bis = (string) ($h['gueltigBis'] ?? '');

    if ($bis !== '' && $heute > $bis) {
        return ['abgelaufen', 'Abgelaufen', 'Der Zeitraum ist vorbei — auf der Webseite erscheint der Hinweis nicht mehr.'];
    }
    if (!hinweis_ist_an((string) ($h['id'] ?? ''))) {
        return ['aus', 'Ausgeschaltet', 'Liegt bereit, steht aber nicht auf der Webseite.'];
    }
    $zeitraum = ($von !== '' ? 'ab ' . datum_deutsch($von) : '') . ($bis !== '' ? ' bis ' . datum_deutsch($bis) : '');
    if ($von !== '' && $heute < $von) {
        return ['geplant', 'Geplant ' . trim($zeitraum), 'Erscheint am ' . datum_deutsch($von) . ' von selbst — sofern veröffentlicht wurde.'];
    }
    return ['an', 'Eingeschaltet', $zeitraum !== '' ? 'Sichtbar ' . trim($zeitraum) . '.' : 'Sichtbar, bis er ausgeschaltet wird.'];
}
