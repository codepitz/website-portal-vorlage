#!/usr/bin/env node
/**
 * Bündelt die Portal-Vorlage (vorlage/) in eine Skriptdatei für den
 * Assistenten (assistent/assets/vorlage.js). So läuft der Assistent offline
 * per Doppelklick — ohne Server und ohne fetch().
 *
 *     node werkzeuge/buendeln.mjs
 *
 * Nach jeder Änderung an der Vorlage erneut ausführen.
 */

import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const wurzel = join(fileURLToPath(new URL('.', import.meta.url)), '..');
const quelle = join(wurzel, 'vorlage');
const ziel = join(wurzel, 'assistent', 'assets', 'vorlage.js');

// Laufzeit-Ordner und lokale Reste gehören nicht in die Vorlage
const AUSLASSEN = [/^\._/, /^\.DS_Store$/, /^konfig\.php$/, /^testlogin\.php$/];
const ORDNER_AUSLASSEN = ['privat/ablage', 'privat/entwurf', 'privat/sicherung', 'webseite/medien', 'webseite/assets'];
const TEXT = /\.(php|json|css|js|html|htaccess|svg|txt|md|xml)$|(^|\/)\.[a-z]+$/i;

const dateien = {};
const binaer = {};

function laufen(ordner) {
  for (const name of readdirSync(ordner).sort()) {
    if (AUSLASSEN.some((re) => re.test(name))) continue;
    const pfad = join(ordner, name);
    const rel = relative(quelle, pfad).split(sep).join('/');
    if (statSync(pfad).isDirectory()) {
      if (ORDNER_AUSLASSEN.includes(rel)) continue;
      laufen(pfad);
    } else if (TEXT.test(name)) {
      dateien[rel] = readFileSync(pfad, 'utf8');
    } else {
      binaer[rel] = readFileSync(pfad).toString('base64');
    }
  }
}

laufen(quelle);

// Das Stylesheet des Assistenten — für die eigenständige ANLEITUNG.html im Paket
const assistentCss = readFileSync(join(wurzel, 'assistent', 'assets', 'aurora.css'), 'utf8');

const paket = { erstellt: new Date().toISOString(), dateien, binaer, assistentCss };
writeFileSync(ziel, '/* Automatisch erzeugt von werkzeuge/buendeln.mjs — nicht von Hand ändern. */\nwindow.VORLAGE = '
  + JSON.stringify(paket) + ';\n');

const anzahl = Object.keys(dateien).length + Object.keys(binaer).length;
const groesse = statSync(ziel).size;
console.log(`${anzahl} Dateien gebündelt → ${relative(wurzel, ziel)} (${Math.round(groesse / 1024)} KB)`);
