// Renders every product shot to ./out/<item>.webp with headless Chromium.
// Usage: npm run render            (all items)
//        npm run render -- coin bar10
import { chromium } from 'playwright';
import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(here, 'out');
const TYPES = { '.html': 'text/html', '.js': 'text/javascript', '.mjs': 'text/javascript', '.woff2': 'font/woff2' };

const ITEMS = {
  hero: { size: 1100, aspect: 1.5 },
  bar1: {}, bar2_5: {}, bar5: {}, bar10: {}, bar20: {}, bar50: {},
  coin: {},
  'ring-solitaire': {}, 'ring-stones': {}, 'band-classic': {}, 'band-engraved': {},
  necklace: {}, set: { aspect: 1.25 }, earrings: {},
  'bracelet-braided': {}, 'bracelet-chain': {},
};

const wanted = process.argv.slice(2);
const list = wanted.length ? wanted : Object.keys(ITEMS);

const server = http.createServer(async (req, res) => {
  const p = path.join(here, decodeURIComponent(new URL(req.url, 'http://x').pathname));
  try {
    const body = await fs.readFile(p);
    res.writeHead(200, { 'content-type': TYPES[path.extname(p)] || 'application/octet-stream' });
    res.end(body);
  } catch {
    res.writeHead(404); res.end();
  }
}).listen(0);
const port = server.address().port;

await fs.mkdir(outDir, { recursive: true });
const browser = await chromium.launch({
  args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'],
});
try {
  for (const item of list) {
    const opt = { size: 1000, aspect: 1, ...ITEMS[item] };
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
    const t0 = Date.now();
    await page.goto(`http://localhost:${port}/scene.html?item=${item}&size=${opt.size}&aspect=${opt.aspect}`);
    await page.waitForFunction(() => window.__done || null, null, { timeout: 240000 }).catch(() => {});
    const data = await page.evaluate(() => window.__out);
    if (!data) { console.error(`✗ ${item}`, errors.join(' | ')); await page.close(); continue; }
    const file = path.join(outDir, `${item}.webp`);
    await fs.writeFile(file, Buffer.from(data.split(',')[1], 'base64'));
    console.log(`✓ ${item} ${((Date.now() - t0) / 1000).toFixed(1)}s`);
    await page.close();
  }
} finally {
  await browser.close();
  server.close();
}
