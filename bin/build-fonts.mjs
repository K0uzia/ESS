#!/usr/bin/env node
// Génère les woff2 sous-ensemble (latin + typographie française) depuis Charte/.
// Usage : NODE_PATH=<dossier où subset-font@2.3.0 est installé>/node_modules node bin/build-fonts.mjs
import { readFile, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const subsetFont = require('subset-font');

const charte = 'Charte/CHARTE GRAPHIQUE ESS BRETAGNE/2-Polices-de-caracteres';
const fonts = [
  [`${charte}/Poppins/Poppins-Regular.ttf`, 'poppins-400.woff2'],
  [`${charte}/Poppins/Poppins-SemiBold.ttf`, 'poppins-600.woff2'],
  [`${charte}/Viga/Viga-Regular.ttf`, 'viga-400.woff2'],
  ['Charte/tag29/Outils graphiques/POLICES/Quicksand_Bold.otf', 'quicksand-700.woff2'],
];

const ranges = [[0x20, 0x7e], [0xa0, 0xff], [0x152, 0x153], [0x178, 0x178], [0x2010, 0x2027], [0x202f, 0x202f], [0x2039, 0x203a], [0x20ac, 0x20ac], [0x2122, 0x2122]];
const text = ranges.flatMap(([a, b]) => Array.from({ length: b - a + 1 }, (_, i) => String.fromCodePoint(a + i))).join('');

for (const [src, out] of fonts) {
  const woff2 = await subsetFont(await readFile(src), text, { targetFormat: 'woff2' });
  await writeFile(`public/assets/fonts/${out}`, woff2);
  console.log(out, woff2.length, 'octets');
}
