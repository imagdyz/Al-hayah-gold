import Alpine from 'alpinejs';

const fmt = (n) => Math.round(Number(n) || 0).toLocaleString('en-US');

/* Live prices: seeded from the page, refreshed from the API every minute. */
Alpine.store('prices', {
    quotes: window.__PRICES?.quotes ?? {},
    updatedAt: window.__PRICES?.updated_at ?? null,
    halted: window.__PRICES?.halted ?? false,
    flash: {},
    init() {
        if (!window.__PRICES) return;
        setInterval(() => this.refresh(), 60_000);
    },
    async refresh() {
        try {
            const res = await fetch('/api/v1/prices', { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const flash = {};
            for (const [k, q] of Object.entries(data.quotes)) {
                const old = this.quotes[k]?.sell;
                if (old && q.sell !== old) flash[k] = q.sell > old ? 'flash-up' : 'flash-down';
            }
            this.quotes = data.quotes;
            this.updatedAt = data.updated_at;
            this.halted = data.halted;
            this.flash = flash;
            setTimeout(() => (this.flash = {}), 1500);
        } catch (e) {
            /* keep showing the last prices */
        }
    },
    sell(k) { return fmt(this.quotes[k]?.sell); },
    buy(k) { return fmt(this.quotes[k]?.buy); },
});

Alpine.magic('fmt', () => fmt);

/* Home: how many bars a budget buys, largest first. */
Alpine.data('budget', (bars, initial = 50000) => ({
    amount: initial,
    get plan() {
        let rest = Math.max(0, Number(this.amount) || 0);
        const parts = [];
        let grams = 0;
        for (const b of bars) {
            const n = Math.floor(rest / b.price);
            if (n > 0) {
                parts.push({ ...b, n });
                rest -= n * b.price;
                grams += n * b.grams;
            }
        }
        return { parts, grams, rest, spent: (Number(this.amount) || 0) - rest };
    },
    get label() {
        const p = this.plan.parts;
        return p.length ? p.map((x) => (x.n > 1 ? `${x.n} × ` : '') + x.name).join(' + ') : 'المبلغ أقل من سعر سبيكة 1 جرام';
    },
}));

/* Line chart with hover/tap read-out. Series: [{t, v}] oldest first. */
Alpine.data('lineChart', (series, opts = {}) => ({
    series,
    w: opts.width ?? 720,
    h: opts.height ?? 260,
    pad: { t: 16, r: 12, b: 28, l: 56 },
    hover: null,
    get min() { return Math.min(...this.series.map((p) => p.v)); },
    get max() { return Math.max(...this.series.map((p) => p.v)); },
    get range() {
        const span = Math.max(this.max - this.min, 1);
        return { lo: this.min - span * 0.08, hi: this.max + span * 0.08 };
    },
    x(i) { return this.pad.l + (i / Math.max(this.series.length - 1, 1)) * (this.w - this.pad.l - this.pad.r); },
    y(v) { const r = this.range; return this.pad.t + (1 - (v - r.lo) / (r.hi - r.lo)) * (this.h - this.pad.t - this.pad.b); },
    get line() { return this.series.map((p, i) => `${i ? 'L' : 'M'}${this.x(i).toFixed(1)} ${this.y(p.v).toFixed(1)}`).join(' '); },
    get area() {
        if (!this.series.length) return '';
        const base = this.h - this.pad.b;
        return `${this.line} L${this.x(this.series.length - 1).toFixed(1)} ${base} L${this.x(0).toFixed(1)} ${base} Z`;
    },
    get ticks() {
        const r = this.range;
        return [0, 1, 2, 3].map((i) => { const v = r.lo + ((r.hi - r.lo) * i) / 3; return { v, y: this.y(v), label: fmt(v) }; });
    },
    get last() { return this.series[this.series.length - 1]; },
    get point() { return this.hover === null ? null : this.series[this.hover]; },
    move(e) {
        const box = this.$refs.svg.getBoundingClientRect();
        const px = ((e.touches ? e.touches[0].clientX : e.clientX) - box.left) * (this.w / box.width);
        const i = Math.round(((px - this.pad.l) / (this.w - this.pad.l - this.pad.r)) * (this.series.length - 1));
        this.hover = Math.min(Math.max(i, 0), this.series.length - 1);
    },
    when(t) {
        const d = new Date(t);
        return d.toLocaleString('ar-EG-u-nu-latn', { weekday: 'short', day: 'numeric', month: 'short', hour: 'numeric' });
    },
}));

/* Prices page: switch karat/period without reloading. */
Alpine.data('pricesPage', (all, initial) => ({
    all,
    karat: initial.karat,
    period: initial.period,
    get series() { return this.all[this.karat][this.period]; },
}));

Alpine.data('gallery', (images) => ({ images, i: 0 }));

/* Bullion order form: selection, quantity and live total. */
Alpine.data('bullionOrder', (items, initial) => ({
    items,
    id: initial.id,
    qty: initial.qty ?? 1,
    pay: initial.pay ?? 'branch',
    depositPercent: initial.depositPercent ?? 10,
    get item() { return this.items.find((x) => x.id === Number(this.id)) ?? this.items[0]; },
    get total() { return this.item.price * this.qty; },
    get deposit() { return Math.round((this.total * this.depositPercent) / 100); },
    inc() { this.qty = Math.min(this.qty + 1, 20); },
    dec() { this.qty = Math.max(this.qty - 1, 1); },
}));

/* Sell estimator: what we pay for old gold of a karat and weight. */
Alpine.data('sellEstimate', (buy, initial) => ({
    buy,
    karat: initial.karat ?? '21',
    grams: initial.grams ?? 10,
    get value() { return (Number(this.grams) || 0) * (this.buy[this.karat] ?? 0); },
}));

window.Alpine = Alpine;
Alpine.start();
