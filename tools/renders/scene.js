// Studio renders of Al Hayah Gold products: PBR gold, softbox environment,
// transparent background with a soft contact shadow. Driven by ?item=…
import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

const params = new URLSearchParams(location.search);
const ITEM = params.get('item') || 'bar10';
const SIZE = +(params.get('size') || 1000);
const ASPECT = +(params.get('aspect') || 1);
const SS = 2;
const W = Math.round(SIZE * ASPECT);
const H = SIZE;

try {
  await Promise.all([
    document.fonts.load('700 64px "El Messiri"', 'الحياة 10 g'),
    document.fonts.load('600 64px "El Messiri"', 'الحياة 10 g'),
  ]);
} catch (e) { /* fall back to system face */ }
const DISPLAY = '"El Messiri", "Noto Naskh Arabic", serif';

// ---------- renderer / scene ----------
const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: true });
renderer.setPixelRatio(1);
renderer.setSize(W * SS, H * SS);
renderer.setClearColor(0x000000, 0);
renderer.toneMapping = THREE.NeutralToneMapping;
renderer.toneMappingExposure = 1.12;
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;
document.body.appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.environment = studioEnv();
const camera = new THREE.PerspectiveCamera(26, W / H, 0.01, 200);

function studioEnv() {
  const env = new THREE.Scene();
  env.background = new THREE.Color(0x2b261f);
  const room = new THREE.Mesh(
    new THREE.BoxGeometry(24, 14, 24),
    new THREE.MeshBasicMaterial({ color: 0x8a837b, side: THREE.BackSide }),
  );
  env.add(room);
  const floorEnv = new THREE.Mesh(new THREE.PlaneGeometry(24, 24), new THREE.MeshBasicMaterial({ color: 0x24201b }));
  floorEnv.rotation.x = -Math.PI / 2; floorEnv.position.y = -6.9; env.add(floorEnv);
  const panel = (w, h, k, pos, color = 0xffffff) => {
    const m = new THREE.Mesh(
      new THREE.PlaneGeometry(w, h),
      new THREE.MeshBasicMaterial({ color: new THREE.Color(color).multiplyScalar(k), side: THREE.DoubleSide }),
    );
    m.position.set(...pos);
    m.lookAt(0, 0, 0);
    env.add(m);
  };
  panel(9, 3.5, 7, [0, 6.5, 0.5]);            // overhead softbox
  panel(3, 7, 4.5, [-8, 2.5, 3], 0xfff0d8);    // key strip, left
  panel(2.5, 7, 3, [8, 2, -2], 0xffe2bd);      // warm fill, right
  panel(12, 1.2, 3, [0, 1.2, -10]);            // back rim strip
  panel(4, 2, 1.6, [4, -2.5, 8]);              // low front kicker
  panel(1.2, 1.2, 9, [-3, 5, 7]);              // small hot spot for sparkle
  const pm = new THREE.PMREMGenerator(renderer);
  return pm.fromScene(env, 0.015).texture;
}

const GOLD = { 24: '#ffcf62', 21: '#ffd37e', 18: '#f6d49e' };
function gold(karat = 21, roughness = 0.16, extra = {}) {
  return new THREE.MeshPhysicalMaterial({
    color: GOLD[karat], metalness: 1, roughness, envMapIntensity: 1.15, ...extra,
  });
}
const diamondMat = new THREE.MeshPhysicalMaterial({
  color: '#f3f6ff', metalness: 1, roughness: 0.02, flatShading: true,
  emissive: '#8a8f99', emissiveIntensity: 0.55,
  iridescence: 0.6, iridescenceIOR: 1.8, envMapIntensity: 3.2,
});

function canvasTex(w, h, draw, { repeat, color = false } = {}) {
  const c = document.createElement('canvas');
  c.width = w; c.height = h;
  const g = c.getContext('2d');
  draw(g, w, h);
  const t = new THREE.CanvasTexture(c);
  t.anisotropy = 8;
  if (color) t.colorSpace = THREE.SRGBColorSpace;
  if (repeat) { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(...repeat); }
  return t;
}

// 8-point star from the logo: two squares, one turned 45°.
function starPath(g, cx, cy, R) {
  const r = R * Math.cos(Math.PI / 4) / Math.cos(Math.PI / 8);
  g.beginPath();
  for (let i = 0; i < 16; i++) {
    const a = -Math.PI / 2 + i * Math.PI / 8;
    const rad = i % 2 === 0 ? R : r;
    const x = cx + rad * Math.cos(a), y = cy + rad * Math.sin(a);
    i ? g.lineTo(x, y) : g.moveTo(x, y);
  }
  g.closePath();
}
function starShape(R) {
  const r = R * Math.cos(Math.PI / 4) / Math.cos(Math.PI / 8);
  const s = new THREE.Shape();
  for (let i = 0; i < 16; i++) {
    const a = Math.PI / 2 + i * Math.PI / 8;
    const rad = i % 2 === 0 ? R : r;
    i ? s.lineTo(rad * Math.cos(a), rad * Math.sin(a)) : s.moveTo(rad * Math.cos(a), rad * Math.sin(a));
  }
  return s;
}
function grey(v) { return `rgb(${v},${v},${v})`; }

// ---------- bullion bar ----------
// Two maps from one layout: bump (height) and roughness (frosted field, polished relief).
function barMaps(label, karat = 24) {
  const w = 900, h = 1550;
  const layout = (g, mode) => {
    const P = mode === 'bump'
      ? { base: 128, field: 112, relief: 205, frame: 200 }
      : { base: 36, field: 78, relief: 24, frame: 24 };
    g.fillStyle = grey(P.base); g.fillRect(0, 0, w, h);
    const m = 70;
    g.fillStyle = grey(P.frame);
    g.beginPath(); g.roundRect(m, m, w - 2 * m, h - 2 * m, 40); g.fill();
    g.fillStyle = grey(P.field);
    g.beginPath(); g.roundRect(m + 14, m + 14, w - 2 * m - 28, h - 2 * m - 28, 30); g.fill();
    g.fillStyle = grey(P.relief); g.strokeStyle = grey(P.relief);
    g.textAlign = 'center'; g.textBaseline = 'middle';
    // logo mark: star outline + circle
    g.lineWidth = 16;
    const cx = w / 2, cy = 360;
    const sq = 120;
    g.save(); g.translate(cx, cy);
    g.strokeRect(-sq, -sq, sq * 2, sq * 2);
    g.rotate(Math.PI / 4); g.strokeRect(-sq, -sq, sq * 2, sq * 2);
    g.restore();
    g.beginPath(); g.arc(cx, cy, 52, 0, Math.PI * 2); g.stroke();
    g.font = `700 92px ${DISPLAY}`; g.direction = 'rtl';
    g.fillText('الحياة جولد', cx, 620);
    g.direction = 'ltr';
    g.font = `600 44px ${DISPLAY}`;
    g.fillText('A L   H A Y A H   G O L D', cx, 710);
    g.fillRect(cx - 220, 780, 440, 8);
    g.font = `700 230px ${DISPLAY}`;
    g.fillText(label, cx, 960);
    g.font = `600 58px ${DISPLAY}`;
    g.fillText('FINE GOLD', cx, 1150);
    g.font = `700 96px ${DISPLAY}`;
    g.fillText(karat === 24 ? '999.9' : '875', cx, 1250);
    g.font = `600 36px ${DISPLAY}`;
    g.fillText('ASSAYER  ·  AH 047219', cx, 1370);
  };
  return {
    bump: canvasTex(w, h, (g) => layout(g, 'bump')),
    rough: canvasTex(w, h, (g) => layout(g, 'rough')),
  };
}

function bar({ label, wd = 1.8, len = 3.1, th = 0.16 }) {
  const geo = new RoundedBoxGeometry(wd, th, len, 6, Math.min(0.06, th * 0.45));
  const maps = barMaps(label);
  const top = gold(24, 1, { bumpMap: maps.bump, bumpScale: 3.2, roughnessMap: maps.rough });
  const side = gold(24, 0.14);
  // BoxGeometry groups: px, nx, py, ny, pz, nz
  const mesh = new THREE.Mesh(geo, [side, side, top, side, side, side]);
  mesh.position.y = th / 2;
  mesh.castShadow = true; mesh.receiveShadow = true;
  const g = new THREE.Group(); g.add(mesh);
  g.userData.height = th;
  return g;
}

// ---------- coin (جنيه ذهب) ----------
function coin({ r = 1.1, t = 0.12 } = {}) {
  const S = 1400;
  const face = (g, mode) => {
    const P = mode === 'bump'
      ? { out: 128, rim: 225, field: 108, relief: 210 }
      : { out: 36, rim: 22, field: 78, relief: 24 };
    g.fillStyle = grey(P.out); g.fillRect(0, 0, S, S);
    const c = S / 2, R = S / 2;
    g.fillStyle = grey(P.rim); g.beginPath(); g.arc(c, c, R * 0.995, 0, Math.PI * 2); g.fill();
    g.fillStyle = grey(P.field); g.beginPath(); g.arc(c, c, R * 0.9, 0, Math.PI * 2); g.fill();
    g.fillStyle = grey(P.relief);
    for (let i = 0; i < 90; i++) {
      const a = i / 90 * Math.PI * 2;
      g.beginPath(); g.arc(c + Math.cos(a) * R * 0.85, c + Math.sin(a) * R * 0.85, 9, 0, Math.PI * 2); g.fill();
    }
    g.strokeStyle = grey(P.relief); g.lineWidth = 10;
    g.beginPath(); g.arc(c, c, R * 0.8, 0, Math.PI * 2); g.stroke();
    starPath(g, c, c - 40, 210); g.fill();
    g.fillStyle = grey(P.field); g.beginPath(); g.arc(c, c - 40, 70, 0, Math.PI * 2); g.fill();
    g.fillStyle = grey(P.relief); g.beginPath(); g.arc(c, c - 40, 48, 0, Math.PI * 2); g.fill();
    g.textAlign = 'center'; g.textBaseline = 'middle'; g.direction = 'rtl';
    g.font = `700 104px ${DISPLAY}`; g.fillText('الحياة جولد', c, c + 300);
    g.font = `600 64px ${DISPLAY}`; g.fillText('عيار 21 · 8 جرام', c, c - 360);
  };
  const reeding = canvasTex(64, 8, (g) => {
    const grd = g.createLinearGradient(0, 0, 64, 0);
    grd.addColorStop(0, grey(60)); grd.addColorStop(0.5, grey(210)); grd.addColorStop(1, grey(60));
    g.fillStyle = grd; g.fillRect(0, 0, 64, 8);
  }, { repeat: [160, 1] });
  const geo = new THREE.CylinderGeometry(r, r, t, 160, 1);
  const side = gold(21, 0.2, { bumpMap: reeding, bumpScale: 2 });
  const top = gold(21, 1, {
    bumpMap: canvasTex(S, S, (g) => face(g, 'bump')), bumpScale: 3.5,
    roughnessMap: canvasTex(S, S, (g) => face(g, 'rough')),
  });
  top.bumpMap.center.set(0.5, 0.5); top.bumpMap.rotation = Math.PI / 2;
  top.roughnessMap.center.set(0.5, 0.5); top.roughnessMap.rotation = Math.PI / 2;
  const mesh = new THREE.Mesh(geo, [side, top, gold(21, 0.2)]);
  mesh.position.y = t / 2;
  mesh.castShadow = true; mesh.receiveShadow = true;
  const g = new THREE.Group(); g.add(mesh);
  g.userData.height = t;
  return g;
}

// ---------- rings ----------
function bandProfile({ rIn = 0.86, rOut = 1.0, half = 0.2, p = 0.55, dome = 0.025 } = {}) {
  const pts = [];
  const rc = (rIn + rOut) / 2, rx = (rOut - rIn) / 2;
  const N = 96;
  for (let i = 0; i <= N; i++) {
    const a = -Math.PI / 2 + i / N * Math.PI * 2;
    const c = Math.cos(a), s = Math.sin(a);
    let x = rc + rx * Math.sign(c) * Math.pow(Math.abs(c), p);
    const y = half * Math.sign(s) * Math.pow(Math.abs(s), p);
    if (c > 0) x += dome * Math.pow(c, 2);
    pts.push(new THREE.Vector2(x, y));
  }
  return pts;
}
function band({ karat = 21, half = 0.2, engraved = false, rOut = 1.0 } = {}) {
  const geo = new THREE.LatheGeometry(bandProfile({ half, rOut, rIn: rOut - 0.14 }), 220);
  let mat = gold(karat, 0.12);
  if (engraved) {
    const tex = canvasTex(256, 1024, (g, w, h) => {
      g.fillStyle = grey(140); g.fillRect(0, 0, w, h);
      // outer face lies in v ∈ [0, .5]; canvas y is flipped (v=0 at bottom)
      const vy = (v) => h - v * h;
      g.fillStyle = grey(40);
      g.fillRect(0, vy(0.135), w, 10); g.fillRect(0, vy(0.375), w, 10);
      g.save();
      g.beginPath(); g.rect(0, vy(0.355), w, vy(0.155) - vy(0.355)); g.clip();
      g.strokeStyle = grey(60); g.lineWidth = 14;
      for (let k = -4; k < 8; k++) {
        g.beginPath(); g.moveTo(k * 128, vy(0.36)); g.lineTo(k * 128 + 260, vy(0.15)); g.stroke();
        g.beginPath(); g.moveTo(k * 128 + 260, vy(0.36)); g.lineTo(k * 128, vy(0.15)); g.stroke();
      }
      g.restore();
    }, { repeat: [28, 1] });
    mat = gold(karat, 0.14, { bumpMap: tex, bumpScale: 2.5 });
  }
  const mesh = new THREE.Mesh(geo, mat);
  mesh.castShadow = true; mesh.receiveShadow = true;
  mesh.rotation.x = Math.PI / 2;           // axis along Z: the ring stands on its edge
  const g = new THREE.Group(); g.add(mesh);
  g.userData.rOut = rOut + 0.03;
  return g;
}
function diamond(scale = 1) {
  const pts = [[0.001, -0.27], [0.26, 0], [0.26, 0.025], [0.165, 0.115], [0.001, 0.115]]
    .map(([x, y]) => new THREE.Vector2(x * scale, y * scale));
  const geo = new THREE.LatheGeometry(pts, 16).toNonIndexed();
  geo.computeVertexNormals();
  const m = new THREE.Mesh(geo, diamondMat);
  m.castShadow = true;
  return m;
}
function solitaire() {
  const g = band({ karat: 18, half: 0.13, rOut: 1.0 });
  const top = 1.02;
  const d = diamond(1.05); d.position.set(0, top + 0.33, 0); g.add(d);
  const pm = gold(18, 0.15);
  for (let i = 0; i < 4; i++) {
    const a = Math.PI / 4 + i * Math.PI / 2;
    const base = new THREE.Vector3(Math.cos(a) * 0.1, top - 0.04, Math.sin(a) * 0.1);
    const tip = new THREE.Vector3(Math.cos(a) * 0.262, top + 0.37, Math.sin(a) * 0.262);
    const curve = new THREE.QuadraticBezierCurve3(base, new THREE.Vector3(Math.cos(a) * 0.24, top + 0.12, Math.sin(a) * 0.24), tip);
    const prong = new THREE.Mesh(new THREE.TubeGeometry(curve, 24, 0.028, 10), pm);
    prong.castShadow = true; g.add(prong);
    const ball = new THREE.Mesh(new THREE.SphereGeometry(0.034, 16, 12), pm); ball.position.copy(tip); g.add(ball);
  }
  const basket = new THREE.Mesh(new THREE.TorusGeometry(0.19, 0.024, 12, 48), pm);
  basket.rotation.x = Math.PI / 2; basket.position.y = top + 0.12; g.add(basket);
  return g;
}
function stonesRing() {
  const g = band({ karat: 18, half: 0.16, rOut: 1.0 });
  const n = 7;
  for (let i = 0; i < n; i++) {
    const a = (i - (n - 1) / 2) * 0.13;
    const d = diamond(0.36);
    const R = 1.02;
    d.position.set(Math.sin(a) * R, Math.cos(a) * R, 0);
    d.rotation.z = -a;
    g.add(d);
    const seat = new THREE.Mesh(new THREE.TorusGeometry(0.095, 0.014, 8, 24), gold(18, 0.15));
    seat.position.copy(d.position).multiplyScalar(1.005); seat.rotation.set(Math.PI / 2, 0, 0); seat.rotateOnWorldAxis(new THREE.Vector3(0, 0, 1), -a);
    g.add(seat);
  }
  return g;
}

// ---------- chains ----------
function ropeMaterial(karat, lengthRepeats) {
  const tex = canvasTex(128, 128, (g, w, h) => {
    g.fillStyle = grey(70); g.fillRect(0, 0, w, h);
    for (const off of [-w, 0, w]) {
      const grd = g.createLinearGradient(off, 0, off + w, h);
      grd.addColorStop(0, grey(70)); grd.addColorStop(0.45, grey(230)); grd.addColorStop(0.55, grey(230)); grd.addColorStop(1, grey(70));
      g.save(); g.translate(w / 2 + off, h / 2); g.rotate(Math.PI / 4); g.translate(-w / 2, -h / 2);
      g.fillStyle = grd; g.fillRect(-w, 0, w * 3, h * 0.7); g.restore();
    }
  }, { repeat: [lengthRepeats, 1] });
  return gold(karat, 0.18, { bumpMap: tex, bumpScale: 4 });
}
function necklaceCurve(scale = 1) {
  const p = [[0, -1.55], [0.9, -1.25], [1.35, -0.3], [1.15, 0.7], [0.55, 1.35], [0, 1.58], [-0.55, 1.35], [-1.15, 0.7], [-1.35, -0.3], [-0.9, -1.25]];
  return new THREE.CatmullRomCurve3(p.map(([x, z]) => new THREE.Vector3(x * scale, 0, z * scale)), true, 'centripetal');
}
function ropeChain({ karat = 21, scale = 1, radius = 0.045 } = {}) {
  const curve = necklaceCurve(scale);
  const len = curve.getLength();
  const geo = new THREE.TubeGeometry(curve, 900, radius, 12, true);
  const m = new THREE.Mesh(geo, ropeMaterial(karat, Math.round(len / (radius * 2.6))));
  m.position.y = radius; m.castShadow = true;
  return m;
}
function starPendant(karat = 21) {
  const g = new THREE.Group();
  const geo = new THREE.ExtrudeGeometry(starShape(0.42), { depth: 0.05, bevelEnabled: true, bevelThickness: 0.025, bevelSize: 0.025, bevelSegments: 4, curveSegments: 4 });
  const m = new THREE.Mesh(geo, gold(karat, 0.13));
  m.rotation.x = -Math.PI / 2; m.position.y = 0.025; m.castShadow = true;
  g.add(m);
  const ring = new THREE.Mesh(new THREE.TorusGeometry(0.15, 0.03, 12, 48), gold(karat, 0.12));
  ring.rotation.x = Math.PI / 2; ring.position.y = 0.11; g.add(ring);
  const d = diamond(0.5); d.position.y = 0.16; g.add(d);
  return g;
}
function bail(karat, pos) {
  const b = new THREE.Mesh(new THREE.TorusGeometry(0.07, 0.022, 12, 32), gold(karat, 0.14));
  b.position.copy(pos); b.rotation.y = Math.PI / 2; b.castShadow = true;
  return b;
}
function teardrop(karat = 18, len = 0.6) {
  const pts = [];
  for (let i = 0; i <= 48; i++) {
    const th = i / 48 * Math.PI;      // 0 = pointed top, π = round bottom
    const y = -len / 2 * (1 - Math.cos(th));
    const r = len * 0.42 * Math.sin(th) * Math.pow(Math.sin(th / 2), 1.3);
    pts.push(new THREE.Vector2(Math.max(r, 0.0005), y));
  }
  const geo = new THREE.LatheGeometry(pts.reverse(), 64);
  const m = new THREE.Mesh(geo, gold(karat, 0.11));
  m.scale.z = 0.5;
  m.castShadow = true;
  return m;
}
function earring(karat = 18) {
  const g = new THREE.Group();
  const drop = teardrop(karat, 0.62);
  drop.rotation.x = -Math.PI / 2;   // lie flat, tip toward -z
  drop.position.set(0, 0.11, 0.0);
  g.add(drop);
  const d = diamond(0.32); d.rotation.x = 0; d.position.set(0, 0.2, 0.18); g.add(d);
  const hook = new THREE.CatmullRomCurve3([
    new THREE.Vector3(0, 0.02, -0.02), new THREE.Vector3(0, 0.02, -0.25), new THREE.Vector3(0.08, 0.02, -0.5),
    new THREE.Vector3(0.28, 0.02, -0.55), new THREE.Vector3(0.36, 0.02, -0.35), new THREE.Vector3(0.34, 0.02, -0.12),
  ]);
  const hm = new THREE.Mesh(new THREE.TubeGeometry(hook, 60, 0.018, 8), gold(karat, 0.12)); hm.castShadow = true; g.add(hm);
  const ball = new THREE.Mesh(new THREE.SphereGeometry(0.05, 20, 14), gold(karat, 0.1)); ball.position.set(0, 0.05, -0.02); g.add(ball);
  return g;
}

// ---------- bracelets ----------
function braidedBangle(karat = 21) {
  const tex = canvasTex(128, 128, (g, w, h) => {
    g.fillStyle = grey(90); g.fillRect(0, 0, w, h);
    g.lineCap = 'round';
    for (const [lw, v] of [[34, 200], [16, 245]]) {
      g.strokeStyle = grey(v); g.lineWidth = lw;
      g.beginPath(); g.moveTo(-10, -10); g.lineTo(w / 2, h / 2 + 4); g.lineTo(w + 10, -10); g.stroke();
      g.beginPath(); g.moveTo(-10, h - 10); g.lineTo(w / 2, h + h / 2 + 4); g.lineTo(w + 10, h - 10); g.stroke();
    }
  }, { repeat: [70, 3] });
  const geo = new THREE.TorusGeometry(1.15, 0.13, 48, 500);
  const m = new THREE.Mesh(geo, gold(karat, 0.2, { bumpMap: tex, bumpScale: 4 }));
  m.rotation.x = Math.PI / 2; m.position.y = 0.13; m.castShadow = true;
  const g = new THREE.Group(); g.add(m);
  return g;
}
function curbBracelet(karat = 21) {
  const g = new THREE.Group();
  const N = 34, R = 1.15;
  const geo = new THREE.TorusGeometry(0.078, 0.024, 14, 40);
  const mat = gold(karat, 0.13);
  const X = new THREE.Vector3(1, 0, 0);
  for (let i = 0; i < N; i++) {
    const th = i / N * Math.PI * 2;
    const tangent = new THREE.Vector3(-Math.sin(th), 0, Math.cos(th));
    const align = new THREE.Quaternion().setFromUnitVectors(X, tangent);
    const m = new THREE.Mesh(geo, mat);
    m.scale.set(1.75, 1, 1);
    const flat = i % 2 === 0;
    const q = flat ? align.clone().multiply(new THREE.Quaternion().setFromAxisAngle(X, Math.PI / 2)) : align;
    m.quaternion.copy(q);
    m.position.set(R * Math.cos(th), flat ? 0.026 : 0.1, R * Math.sin(th));
    m.castShadow = true;
    g.add(m);
  }
  return g;
}

// ---------- build ----------
const BARS = {
  bar1: { label: '1 g', th: 0.1 }, bar2_5: { label: '2.5 g', th: 0.12 }, bar5: { label: '5 g', th: 0.14 },
  bar10: { label: '10 g', th: 0.16 }, bar20: { label: '20 g', th: 0.2 }, bar50: { label: '50 g', th: 0.26 },
};
const root = new THREE.Group();
let view = [0.0, 1.15, 1.0];
let yaw = 0;

if (BARS[ITEM]) {
  const b = bar(BARS[ITEM]);
  b.rotation.y = -0.38;
  root.add(b);
  view = [0.0, 1.6, 1.0];
} else if (ITEM === 'coin') {
  const c1 = coin(); root.add(c1);
  view = [0, 1.7, 1.0];
} else if (ITEM === 'ring-solitaire') {
  root.add(solitaire()); yaw = -0.55; view = [0, 0.42, 1.0];
} else if (ITEM === 'ring-stones') {
  root.add(stonesRing()); yaw = -0.5; view = [0, 0.4, 1.0];
} else if (ITEM === 'band-classic') {
  root.add(band({ karat: 21, half: 0.2 })); yaw = -0.6; view = [0, 0.45, 1.0];
} else if (ITEM === 'band-engraved') {
  root.add(band({ karat: 21, half: 0.22, engraved: true })); yaw = -0.6; view = [0, 0.4, 1.0];
} else if (ITEM === 'necklace') {
  root.add(ropeChain({ karat: 21 }));
  root.add(bail(21, new THREE.Vector3(0, 0.07, 1.62)));
  const p = starPendant(21); p.position.set(0, 0, 2.08); root.add(p);
  view = [0, 1.9, 0.8];
} else if (ITEM === 'bracelet-braided') {
  root.add(braidedBangle(21)); view = [0, 1.25, 1.0];
} else if (ITEM === 'bracelet-chain') {
  root.add(curbBracelet(21)); view = [0, 1.35, 1.0];
} else if (ITEM === 'earrings') {
  const a = earring(18); a.position.x = -0.42; a.scale.x = -1; a.rotation.y = 0.12; root.add(a);
  const b = earring(18); b.position.x = 0.42; b.rotation.y = -0.12; root.add(b);
  view = [0, 1.7, 0.9];
} else if (ITEM === 'set') {
  root.add(ropeChain({ karat: 21, scale: 0.85, radius: 0.026 }));
  const drop = teardrop(21, 0.5); drop.rotation.x = -Math.PI / 2; drop.position.set(0, 0.1, 1.42); root.add(drop);
  root.add(bail(21, new THREE.Vector3(0, 0.06, 1.38)));
  const a = earring(21); a.scale.setScalar(0.85); a.position.set(1.85, 0, 0.55); a.rotation.y = 0.25; root.add(a);
  const b = earring(21); b.scale.set(-0.85, 0.85, 0.85); b.position.set(2.45, 0, 0.62); b.rotation.y = -0.1; root.add(b);
  view = [0, 1.9, 0.85];
} else if (ITEM === 'hero') {
  const big = bar(BARS.bar50); big.scale.setScalar(1.3); big.rotation.y = -0.42; root.add(big);
  const mid = bar(BARS.bar10); mid.rotation.y = 0.05; mid.position.set(0.35, 0.26 * 1.3, 0.35); root.add(mid);
  for (let i = 0; i < 3; i++) {
    const c = coin(); c.position.set(2.7 + i * 0.05, i * 0.12, 0.7 - i * 0.04); c.rotation.y = i * 0.7; root.add(c);
  }
  const stand = coin(); stand.rotation.set(Math.PI / 2 - 0.12, 0, 0); stand.rotation.order = 'YXZ'; stand.rotation.y = 0.45;
  stand.position.set(-2.55, 1.1, -0.2); root.add(stand);
  view = [0.25, 0.62, 1.0];
} else {
  throw new Error('unknown item ' + ITEM);
}
root.rotation.y = yaw;
scene.add(root);

// floor that only catches shadow
const floor = new THREE.Mesh(new THREE.PlaneGeometry(60, 60), new THREE.ShadowMaterial({ opacity: 0.38 }));
floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true; scene.add(floor);

const box = new THREE.Box3().setFromObject(root);
const sphere = box.getBoundingSphere(new THREE.Sphere());
const key = new THREE.DirectionalLight(0xfff2de, 1.4);
key.position.set(sphere.center.x - sphere.radius * 1.2, sphere.radius * 4, sphere.center.z + sphere.radius * 1.4);
key.target.position.copy(sphere.center);
key.castShadow = true;
key.shadow.mapSize.set(4096, 4096);
key.shadow.radius = 10;
key.shadow.blurSamples = 24;
key.shadow.bias = -0.0004;
const sc = key.shadow.camera;
sc.left = sc.bottom = -sphere.radius * 1.6; sc.right = sc.top = sphere.radius * 1.6; sc.near = 0.1; sc.far = sphere.radius * 10;
scene.add(key, key.target);

// fit camera to the projected bounding box
function fit() {
  const dir = new THREE.Vector3(...view).normalize();
  let target = sphere.center.clone();
  let dist = sphere.radius / Math.sin(THREE.MathUtils.degToRad(camera.fov / 2));
  // sample real vertices: box corners overstate rotated and round shapes
  const corners = [];
  root.updateMatrixWorld(true);
  root.traverse((o) => {
    if (!o.isMesh) return;
    const pos = o.geometry.attributes.position;
    const step = Math.max(1, Math.floor(pos.count / 1500));
    for (let i = 0; i < pos.count; i += step) corners.push(new THREE.Vector3().fromBufferAttribute(pos, i).applyMatrix4(o.matrixWorld));
  });
  const goal = 0.8;
  for (let k = 0; k < 6; k++) {
    camera.position.copy(target).addScaledVector(dir, dist);
    camera.lookAt(target); camera.updateMatrixWorld(); camera.updateProjectionMatrix();
    let minX = 1e9, maxX = -1e9, minY = 1e9, maxY = -1e9;
    for (const c of corners) { const p = c.clone().project(camera); minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x); minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y); }
    const ext = Math.max((maxX - minX) / 2, (maxY - minY) / 2);
    const cx = (minX + maxX) / 2, cy = (minY + maxY) / 2;
    const right = new THREE.Vector3().setFromMatrixColumn(camera.matrixWorld, 0);
    const up = new THREE.Vector3().setFromMatrixColumn(camera.matrixWorld, 1);
    const halfH = Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * dist;
    target.addScaledVector(right, cx * halfH * camera.aspect).addScaledVector(up, cy * halfH);
    dist *= ext / goal;
  }
  camera.position.copy(target).addScaledVector(dir, dist);
  camera.lookAt(target);
}
fit();
renderer.render(scene, camera);

const out = document.createElement('canvas');
out.width = W; out.height = H;
const og = out.getContext('2d');
og.imageSmoothingQuality = 'high';
og.drawImage(renderer.domElement, 0, 0, W, H);
window.__out = out.toDataURL('image/webp', 0.9);
window.__done = true;
