#!/usr/bin/env node
/**
 * Kundenprojekt erzeugen — dasselbe Paket wie der Assistent im Browser,
 * aber von der Kommandozeile und direkt als Git-fähiger Ordner.
 *
 *     node werkzeuge/projekt-erzeugen.mjs --daten kunde.json --ziel ../kunde-portal [--theme to-frames-kino]
 *
 * Optionen
 *   --daten <datei>    Kundendaten (Aufbau: docs/KUNDENDATEN.md, Beispiel: beispiele/kunde.beispiel.json)
 *   --ziel <ordner>    Zielordner (wird angelegt; muss leer sein, außer mit --ueberschreiben)
 *   --theme <kennung>  Theme aus themes/ als Design der Webseite wählen (sonst das Design aus den Daten)
 *   --themes <a,b>     nur diese Themes mitliefern (Standard: alle aus themes/, „keine“ = keins)
 *   --theme-ordner <p> zusätzlich ein Theme aus einem beliebigen Ordner mitliefern (mit --theme-kennung <id>)
 *   --kein-bau         Webseite nicht vorab erzeugen (sonst: php privat/bauen.php webseite)
 *   --ueberschreiben   in einen nicht leeren Zielordner schreiben
 *
 * Ergebnis: webseite/ · portal/ · privat/ · themes/ · .github/workflows/deploy.yml ·
 * README.md · ANLEITUNG.html · LIESMICH.txt · projekt-assistent.json · Offline-Startprogramme
 */

import { readFileSync, writeFileSync, mkdirSync, readdirSync, statSync, existsSync, chmodSync, cpSync } from 'node:fs';
import { join, dirname, relative, sep, resolve, extname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import vm from 'node:vm';

const wurzel = join(dirname(fileURLToPath(import.meta.url)), '..');

/* ------------------------------------------------------------ Argumente --- */

const argv = process.argv.slice(2);
const opt = {};
for (let i = 0; i < argv.length; i++) {
  const a = argv[i];
  if (!a.startsWith('--')) continue;
  const [k, v] = a.slice(2).split('=');
  if (v !== undefined) opt[k] = v;
  else if (argv[i + 1] && !argv[i + 1].startsWith('--')) opt[k] = argv[++i];
  else opt[k] = true;
}
if (!opt.daten || !opt.ziel) {
  console.error('Aufruf: node werkzeuge/projekt-erzeugen.mjs --daten kunde.json --ziel <ordner> [--theme <kennung>] [--kein-bau]');
  process.exit(1);
}
const ziel = resolve(opt.ziel);
if (existsSync(ziel) && readdirSync(ziel).filter((n) => n !== '.git').length && !opt.ueberschreiben) {
  console.error(`Der Zielordner ${ziel} ist nicht leer. Mit --ueberschreiben trotzdem hineinschreiben.`);
  process.exit(1);
}

/* ------------------------------------------- Assistent-Logik laden (vm) --- */

const TEXT = /\.(php|json|css|js|html|htaccess|svg|txt|md|xml)$|(^|\/)\.[a-z]+$/i;
const AUSLASSEN = [/^\._/, /^\.DS_Store$/, /^konfig\.php$/, /^testlogin\.php$/];
const ORDNER_AUSLASSEN = ['privat/ablage', 'privat/entwurf', 'privat/sicherung', 'webseite/medien', 'webseite/assets'];

function vorlageLesen() {
  const quelle = join(wurzel, 'vorlage');
  const dateien = {};
  const binaer = {};
  (function laufen(ordner) {
    for (const name of readdirSync(ordner).sort()) {
      if (AUSLASSEN.some((re) => re.test(name))) continue;
      const pfad = join(ordner, name);
      const rel = relative(quelle, pfad).split(sep).join('/');
      if (statSync(pfad).isDirectory()) {
        if (!ORDNER_AUSLASSEN.includes(rel)) laufen(pfad);
      } else if (TEXT.test(name)) {
        dateien[rel] = readFileSync(pfad, 'utf8');
      } else {
        binaer[rel] = readFileSync(pfad).toString('base64');
      }
    }
  })(quelle);
  return { erstellt: new Date().toISOString(), dateien, binaer, assistentCss: readFileSync(join(wurzel, 'assistent/assets/aurora.css'), 'utf8') };
}

const fenster = {
  VORLAGE: vorlageLesen(),
  atob: (s) => Buffer.from(s, 'base64').toString('binary'),
  btoa: (s) => Buffer.from(s, 'binary').toString('base64'),
};
fenster.window = fenster;
const kontext = vm.createContext({ window: fenster, atob: fenster.atob, btoa: fenster.btoa, console, Date, JSON, Object, Array, String, Number, RegExp, Math, Uint8Array });
for (const datei of ['daten.js', 'bilder.js', 'erzeugen.js', 'anleitung.js']) {
  vm.runInContext(readFileSync(join(wurzel, 'assistent/assets', datei), 'utf8'), kontext, { filename: datei });
}
const WS = fenster.WS;

/* --------------------------------------------------------- Kundendaten --- */

const kopie = (o) => JSON.parse(JSON.stringify(o));

/** Wie grunddaten() im Assistenten — Vorgaben aus Branche und Hoster. */
function grunddaten(branche, hoster) {
  const b = WS.BRANCHEN[branche] || WS.BRANCHEN.sonstiges;
  const h = WS.HOSTER[hoster] || WS.HOSTER.strato;
  return {
    branche: branche || 'sonstiges', organisation: b.organisation, reglementiert: !!b.reglementiert,
    name: '', claim: '', beschreibung: '', portal: '', kuerzel: '',
    strasse: '', plz: '', ort: '', telefon: '', telefonLink: '', fax: '', email: '',
    inhaber: '', rechtsform: b.rechtsform, vertreten: '', register: '', ustid: '',
    berufsbezeichnung: '', verliehenIn: 'Bundesrepublik Deutschland', kammer: '', aufsichtsbehoerde: '', berufsrecht: '', verantwortlich: '',
    hoster: WS.HOSTER[hoster] ? hoster : 'strato', hosterFirma: h.firma, domain: '', www: true, sub: 'portal',
    ordner: { webseite: 'webseite', portal: 'portal', privat: 'privat' }, php: '8.3',
    postfaecher: [{ name: 'info', zweck: 'Empfang: Anfragen und allgemeine Post' }, { name: 'webseite', zweck: 'Absender des Kontaktformulars' }],
    empfaenger: 'info', absender: 'webseite', modus: 'server', smtpHost: h.smtp.host, smtpPort: h.smtp.port,
    module: kopie(b.module), begriffe: kopie(b.begriffe),
    sektionen: b.sektionen.map((t) => ({ typ: t, aktiv: true, menue: t !== 'hero' && t !== 'laufband' })),
    design: 'aurora', designBasis: 'aurora', akzent: '#5eead4', akzentTinte: '#04241f',
    heroZeilen: b.hero.slice(), lead: '', knopf: b.knopf, seoTitel: '', seoBeschreibung: '',
    angeboteListe: kopie(b.angebote), zeiten: kopie(b.zeitenBeispiel),
    adminAnzeige: '', adminBenutzer: '', sitzungsdauer: 120,
    logo: null, logoAnzeige: 'logo', zeitenMenue: true, _manuell: {},
  };
}

const datenPfad = resolve(opt.daten);
const roh = JSON.parse(readFileSync(datenPfad, 'utf8'));
const eingabe = roh.daten && roh.assistent ? roh.daten : roh; // auch projekt-assistent.json wird angenommen
const d = Object.assign(grunddaten(eingabe.branche, eingabe.hoster), eingabe);
if (eingabe.module) d.module = Object.assign(grunddaten(eingabe.branche).module, eingabe.module);
if (eingabe.begriffe) d.begriffe = Object.assign(grunddaten(eingabe.branche).begriffe, eingabe.begriffe);
if (Array.isArray(d.sektionen)) d.sektionen = d.sektionen.map((s) => (typeof s === 'string' ? { typ: s, aktiv: true, menue: s !== 'hero' && s !== 'laufband' } : s));

// Logo als Datei: "logoDatei": "pfad/zum/logo.png" (relativ zur Datendatei)
if (d.logoDatei && !d.logo) {
  const p = resolve(dirname(datenPfad), d.logoDatei);
  const typ = { '.png': 'png', '.jpg': 'jpeg', '.jpeg': 'jpeg', '.webp': 'webp' }[extname(p).toLowerCase()];
  if (!typ) throw new Error('Logo: nur PNG, JPG oder WebP (' + p + ')');
  d.logo = { daten: `data:image/${typ};base64,` + readFileSync(p).toString('base64') };
}
delete d.logoDatei;

const warnungen = [];
for (const s of d.sektionen || []) {
  if (!WS.SEKTIONEN[s.typ]) warnungen.push(`Sektion „${s.typ}“ ist kein eingebauter Typ und wird übersprungen (eigene Typen eines Themes im Portal hinzufügen).`);
}

/* ------------------------------------------------------------ Erzeugen --- */

const paket = fenster.WSErzeugen.erzeugen(d);
const A = fenster.WSErzeugen.abgeleitet(fenster.WSErzeugen.mitStandards(d));
mkdirSync(ziel, { recursive: true });
let anzahl = 0;
for (const f of paket.dateien) {
  const rel = f.pfad.slice(paket.wurzel.length + 1);
  const pfad = join(ziel, rel);
  mkdirSync(dirname(pfad), { recursive: true });
  writeFileSync(pfad, typeof f.inhalt === 'string' ? f.inhalt : Buffer.from(f.inhalt));
  if (f.rechte) chmodSync(pfad, f.rechte);
  anzahl++;
}

/* ---------------------------------------------------------------- Themes --- */

const themesQuelle = join(wurzel, 'themes');
const verfuegbar = existsSync(themesQuelle) ? readdirSync(themesQuelle).filter((n) => existsSync(join(themesQuelle, n, 'theme.json'))) : [];
const gewuenscht = opt.themes === 'keine' ? [] : opt.themes ? String(opt.themes).split(',').map((s) => s.trim()).filter(Boolean) : verfuegbar;
const zusatzTheme = opt['theme-ordner'] ? resolve(opt['theme-ordner']) : null;

let php = null;
try { execFileSync('php', ['-v'], { stdio: 'ignore' }); php = 'php'; } catch { /* kein PHP */ }

const themeListe = [];
for (const t of gewuenscht) {
  if (!verfuegbar.includes(t)) { warnungen.push(`Theme „${t}“ gibt es in themes/ nicht.`); continue; }
  cpSync(join(themesQuelle, t), join(ziel, 'themes', t), { recursive: true });
  themeListe.push(t);
}
if (zusatzTheme) {
  const id = String(opt['theme-kennung'] || zusatzTheme.split(sep).pop()).toLowerCase().replace(/[^a-z0-9-]+/g, '-');
  cpSync(zusatzTheme, join(ziel, 'themes', id), { recursive: true });
  themeListe.push(id);
}
if (opt.theme && !themeListe.includes(opt.theme)) {
  console.error(`Das Theme „${opt.theme}“ liegt weder in themes/ noch wurde es mit --theme-ordner übergeben.`);
  process.exit(1);
}
const themeBefunde = {};
if (themeListe.length && !php) {
  warnungen.push('PHP fehlt auf diesem Rechner — Themes liegen unter themes/, sind aber noch nicht installiert. Nachholen: php privat/theme-werkzeug.php installieren themes/<kennung> --kennung=<kennung>');
} else {
  for (const t of themeListe) {
    const args = ['privat/theme-werkzeug.php', 'installieren', join('themes', t), '--kennung=' + t];
    if (opt.theme === t) args.push('--verwenden');
    try {
      execFileSync(php, args, { cwd: ziel, stdio: 'pipe' });
    } catch (e) {
      const text = String(e.stdout || '') + String(e.stderr || '');
      if (e.status === 2) themeBefunde[t] = text.split('Befunde:')[1]?.trim() || text.trim();
      else throw new Error(`Theme „${t}“ ließ sich nicht installieren:\n${text}`);
    }
  }
}
const aktivesDesign = opt.theme || (d.design === 'eigen' ? 'eigen' : d.design || 'aurora');

/* ------------------------------------------------- Repo-Dateien (GitHub) --- */

const ersetzen = (text) => text
  .replaceAll('{{NAME}}', d.name || 'Meine Webseite')
  .replaceAll('{{WEBSEITE}}', A.webseite)
  .replaceAll('{{PORTAL}}', A.portalUrl)
  .replaceAll('{{DOMAIN}}', A.domain)
  .replaceAll('{{PORTAL_HOST}}', A.portalHost)
  .replaceAll('{{HOSTER}}', A.hoster.name)
  .replaceAll('{{SFTP_HOST}}', A.hoster.sftp.host)
  .replaceAll('{{SFTP_PORT}}', String(A.hoster.sftp.port))
  .replaceAll('{{SFTP_BENUTZER}}', String(A.hoster.sftp.benutzer || '').replaceAll('{domain}', A.domain))
  .replaceAll('{{DESIGN}}', aktivesDesign)
  .replaceAll('{{THEMES}}', themeListe.length ? themeListe.map((t) => '`' + t + '`').join(', ') : '—')
  .replaceAll('{{DATUM}}', new Date().toLocaleDateString('de-DE'))
  .replaceAll('{{ADMIN}}', d.adminBenutzer || '(beim ersten Aufruf festlegen)');

const repoVorlage = join(wurzel, 'kunde-repo');
(function kopieren(ordner) {
  for (const name of readdirSync(ordner)) {
    const pfad = join(ordner, name);
    const rel = relative(repoVorlage, pfad);
    if (statSync(pfad).isDirectory()) { kopieren(pfad); continue; }
    const zielPfad = join(ziel, rel.replace(/\.vorlage$/, ''));
    mkdirSync(dirname(zielPfad), { recursive: true });
    writeFileSync(zielPfad, ersetzen(readFileSync(pfad, 'utf8')));
  }
})(repoVorlage);

/* ------------------------------------------------ Webseite vorab bauen --- */

let bau = '';
if (!opt['kein-bau'] && php) {
  try {
    bau = execFileSync(php, ['privat/bauen.php', 'webseite'], { cwd: ziel, encoding: 'utf8' });
  } catch (e) {
    warnungen.push('Die Webseite ließ sich nicht vorab erzeugen: ' + (e.stderr || e.message));
  }
}

/* ---------------------------------------------------------------- Bericht --- */

console.log(`\n✓ Projekt „${d.name || 'Meine Webseite'}“ erzeugt: ${ziel}`);
console.log(`  ${anzahl} Dateien aus der Vorlage · Design: ${aktivesDesign}${themeListe.length ? ' · Themes: ' + themeListe.join(', ') : ''}`);
console.log(`  Webseite ${A.webseite} · Portal ${A.portalUrl} · Hoster ${A.hoster.name}`);
if (bau) console.log('\n' + bau.trim().split('\n').map((z) => '  ' + z).join('\n'));
for (const [t, b] of Object.entries(themeBefunde)) console.log(`\n  Theme „${t}“ — Befunde:\n  ${b.replaceAll('\n', '\n  ')}`);
if (warnungen.length) console.log('\n  Hinweise:\n  • ' + warnungen.join('\n  • '));
console.log(`
  Nächste Schritte
  1. Lokal testen:   php -S localhost:8081 -t portal   und   php -S localhost:8082 -t webseite
  2. Git:            git init -b main && git add -A && git commit -m "Erstes Paket"
  3. GitHub:         neues privates Repo anlegen, pushen, Secrets SFTP_HOST / SFTP_BENUTZER / SFTP_PASSWORT setzen
  4. Online:         Workflow „Deploy“ lädt portal/, privat/ und webseite/ hoch — Inhalte auf dem Server bleiben unangetastet
`);
