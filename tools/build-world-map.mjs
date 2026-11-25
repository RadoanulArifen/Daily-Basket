// Builds resources/views/admin/insights/partials/world-map.blade.php (run once: npm i world-atlas@2 topojson-client@3 topojson-simplify@3 d3-geo@3 i18n-iso-countries@7; Q=0.06 node tools/build-world-map.mjs).
// Builds the inline world map once: world-atlas (Natural Earth 1:50m) → SVG paths keyed by ISO alpha-2.
import fs from 'fs';
import { feature } from 'topojson-client';
import { presimplify, simplify, quantile } from 'topojson-simplify';
import { geoNaturalEarth1, geoPath } from 'd3-geo';
import countries from 'i18n-iso-countries';
const raw = JSON.parse(fs.readFileSync('node_modules/world-atlas/countries-50m.json'));
const rawFc = feature(raw, raw.objects.countries);
let topo = JSON.parse(JSON.stringify(raw));
topo = presimplify(topo);
topo = simplify(topo, quantile(topo, Number(process.env.Q || 0.06)));
const fc = feature(topo, topo.objects.countries);
fc.features = fc.features.filter(f => f.properties.name !== 'Antarctica');
const W = 1000, H = 480;
const proj = geoNaturalEarth1().fitExtent([[4, 4], [W - 4, H - 4]], fc);
const path = geoPath(proj).digits(1);
const byName = { 'Kosovo': 'XK', 'N. Cyprus': 'CY', 'Somaliland': 'SO', 'Siachen Glacier': 'IN', 'Indian Ocean Ter.': 'AU', 'Ashmore and Cartier Is.': 'AU' };
const out = []; const missing = [];
for (const f of fc.features) {
  let cc = f.id ? countries.numericToAlpha2(f.id) : null;
  cc = cc || byName[f.properties.name];
  if (!cc) { missing.push(f.properties.name + ':' + f.id); continue; }
  let d = path(f);
  // Very small countries (Bahrain, Singapore, Malta…) would vanish: draw them as a dot.
  if (!d || path.area(f) < 6) {
    const orig = rawFc.features.find(o => o.id === f.id && o.properties.name === f.properties.name) || f;
    const [cx, cy] = path.centroid(orig);
    if (!isFinite(cx)) continue;
    const r = 2.6;
    d = `M${(cx - r).toFixed(1)},${cy.toFixed(1)}a${r},${r} 0 1,0 ${2 * r},0a${r},${r} 0 1,0 ${-2 * r},0Z`;
  }
  out.push([cc, f.properties.name, d]);
}
const k = proj.scale(), [tx, ty] = proj.translate();
const svg = `<svg viewBox="0 0 ${W} ${H}" data-k="${k.toFixed(4)}" data-tx="${tx.toFixed(3)}" data-ty="${ty.toFixed(3)}" xmlns="http://www.w3.org/2000/svg">` +
  out.map(([cc, n, d]) => `<path data-cc="${cc}" d="${d}"><title>${n.replace(/&/g,'&amp;')}</title></path>`).join('') + '</svg>';
fs.writeFileSync('world.svg', svg);
console.log('countries', out.length, 'bytes', svg.length, 'missing', missing, 'k', k, tx, ty);
