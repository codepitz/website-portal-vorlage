/* ===========================================================================
   ZIP ohne Bibliothek — Dateien werden unkomprimiert abgelegt (STORE).
   Das reicht für ein Paket aus Text-Dateien und läuft offline in jedem
   Browser. Dateinamen in UTF-8 (Bit 11), Unix-Rechte für Ordner und Skripte.
   =========================================================================== */

(function () {
  'use strict';

  var TABELLE = (function () {
    var t = new Uint32Array(256);
    for (var n = 0; n < 256; n++) {
      var c = n;
      for (var k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
      t[n] = c >>> 0;
    }
    return t;
  })();

  function crc32(bytes) {
    var c = 0xffffffff;
    for (var i = 0; i < bytes.length; i++) c = TABELLE[(c ^ bytes[i]) & 0xff] ^ (c >>> 8);
    return (c ^ 0xffffffff) >>> 0;
  }

  function dosZeit(d) {
    return {
      zeit: (d.getHours() << 11) | (d.getMinutes() << 5) | Math.floor(d.getSeconds() / 2),
      datum: ((d.getFullYear() - 1980) << 9) | ((d.getMonth() + 1) << 5) | d.getDate()
    };
  }

  /**
   * dateien: [{ pfad: 'ordner/datei.txt', inhalt: String|Uint8Array, rechte: 0o644 }]
   * Liefert einen Blob (application/zip).
   */
  function zipErstellen(dateien) {
    var enc = new TextEncoder();
    var teile = [];
    var verzeichnis = [];
    var versatz = 0;
    var jetzt = dosZeit(new Date());

    // Ordner als eigene Einträge, damit leere Ordner mitkommen und Rechte stimmen
    var ordner = {};
    dateien.forEach(function (d) {
      var p = d.pfad.split('/');
      for (var i = 1; i < p.length; i++) ordner[p.slice(0, i).join('/') + '/'] = true;
    });
    var eintraege = Object.keys(ordner).sort().map(function (o) { return { pfad: o, inhalt: new Uint8Array(0), rechte: 0x41ed /* 040755 */ }; })
      .concat(dateien.map(function (d) {
        return { pfad: d.pfad, inhalt: typeof d.inhalt === 'string' ? enc.encode(d.inhalt) : d.inhalt, rechte: 0x8000 | (d.rechte || 0x1a4 /* 0644 */) };
      }));

    eintraege.forEach(function (e) {
      var name = enc.encode(e.pfad);
      var daten = e.inhalt;
      var crc = crc32(daten);

      var kopf = new DataView(new ArrayBuffer(30));
      kopf.setUint32(0, 0x04034b50, true);
      kopf.setUint16(4, 20, true);
      kopf.setUint16(6, 0x0800, true);
      kopf.setUint16(8, 0, true);
      kopf.setUint16(10, jetzt.zeit, true);
      kopf.setUint16(12, jetzt.datum, true);
      kopf.setUint32(14, crc, true);
      kopf.setUint32(18, daten.length, true);
      kopf.setUint32(22, daten.length, true);
      kopf.setUint16(26, name.length, true);
      kopf.setUint16(28, 0, true);
      teile.push(new Uint8Array(kopf.buffer), name, daten);

      var zentral = new DataView(new ArrayBuffer(46));
      zentral.setUint32(0, 0x02014b50, true);
      zentral.setUint16(4, 0x0314, true);          // erstellt unter Unix, Version 2.0
      zentral.setUint16(6, 20, true);
      zentral.setUint16(8, 0x0800, true);
      zentral.setUint16(10, 0, true);
      zentral.setUint16(12, jetzt.zeit, true);
      zentral.setUint16(14, jetzt.datum, true);
      zentral.setUint32(16, crc, true);
      zentral.setUint32(20, daten.length, true);
      zentral.setUint32(24, daten.length, true);
      zentral.setUint16(28, name.length, true);
      zentral.setUint16(30, 0, true);
      zentral.setUint16(32, 0, true);
      zentral.setUint16(34, 0, true);
      zentral.setUint16(36, 0, true);
      zentral.setUint32(38, (e.rechte << 16) >>> 0, true);
      zentral.setUint32(42, versatz, true);
      verzeichnis.push(new Uint8Array(zentral.buffer), name);

      versatz += 30 + name.length + daten.length;
    });

    var groesse = verzeichnis.reduce(function (s, t) { return s + t.length; }, 0);
    var ende = new DataView(new ArrayBuffer(22));
    ende.setUint32(0, 0x06054b50, true);
    ende.setUint16(8, eintraege.length, true);
    ende.setUint16(10, eintraege.length, true);
    ende.setUint32(12, groesse, true);
    ende.setUint32(16, versatz, true);

    return new Blob(teile.concat(verzeichnis, [new Uint8Array(ende.buffer)]), { type: 'application/zip' });
  }

  window.zipErstellen = zipErstellen;
})();
