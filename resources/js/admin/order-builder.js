/**
 * "Create order" page: products, customer, delivery and payment.
 * The server calculates the final totals (VAT, deals, discount) when the order is created.
 */
export default ({ urls, country, state = '', draftLines = [], currency = '' }) => ({
    lines: [],
    q: '',
    results: [],
    searching: false,
    customerQ: '',
    customers: [],
    customerId: null,
    country,
    state,
    rates: [],
    shipping: '',

    get subtotalMinor() {
        return this.lines.reduce((sum, l) => sum + l.price * (+l.qty || 0), 0);
    },

    money(minor) {
        // The store's own currency, not a hardcoded one.
        return currency + (minor / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },

    /** Put a saved draft's products back in the basket, with their current prices. */
    async init() {
        if (! draftLines.length) return;
        try {
            const res = await fetch(`${urls.variants}?ids=${draftLines.map((l) => l.id).join(',')}`, { headers: { Accept: 'application/json' } });
            const { variants } = await res.json();
            this.lines = draftLines
                .map((line) => {
                    const variant = variants.find((v) => v.id === line.id);
                    return variant ? { ...variant, qty: line.qty } : null;
                })
                .filter(Boolean);
        } catch (e) {
            // A product may have been deleted since; the rest of the draft still loads.
        }

        // Delivery options depend on what is in the basket, so load them for the draft too.
        await this.loadRates();
    },

    async find() {
        this.searching = true;
        try {
            const res = await fetch(`${urls.variants}?q=${encodeURIComponent(this.q.trim())}`, { headers: { Accept: 'application/json' } });
            this.results = (await res.json()).variants;
        } finally {
            this.searching = false;
        }
    },

    add(v) {
        const line = this.lines.find((l) => l.id === v.id);
        if (line) {
            line.qty = Math.min(line.qty + 1, v.stock ?? 999);
        } else if (v.stock === null || v.stock > 0) {
            this.lines.push({ ...v, qty: 1 });
        }
        this.q = '';
        this.results = [];
        this.loadRates();
    },

    remove(id) {
        this.lines = this.lines.filter((l) => l.id !== id);
        this.loadRates();
    },

    async findCustomer() {
        if (this.customerQ.trim().length < 2) {
            this.customers = [];
            return;
        }
        const res = await fetch(`${urls.customers}?q=${encodeURIComponent(this.customerQ.trim())}`, { headers: { Accept: 'application/json' } });
        this.customers = (await res.json()).customers;
    },

    pickCustomer(c) {
        this.customerId = c.id;
        this.customers = [];
        this.customerQ = '';
        const set = (id, value) => { const el = document.getElementById(id); if (el && value != null) el.value = value; };
        set('email', c.email);
        set('name', c.address?.name ?? c.name);
        set('phone', c.address?.phone ?? c.phone);
        if (c.address) {
            set('city', c.address.city);
            set('address_line', c.address.address_line);
            set('building', c.address.building);
            this.country = c.address.country || this.country;
            this.state = c.address.state ?? '';
        }
        this.loadRates();
    },

    async loadRates() {
        if (! this.lines.length) {
            this.rates = [];
            return;
        }
        const params = new URLSearchParams({ country: this.country, state: this.state || '' });
        this.lines.forEach((l) => params.append(`lines[${l.id}]`, l.qty || 1));
        const res = await fetch(`${urls.rates}?${params}`, { headers: { Accept: 'application/json' } });
        this.rates = res.ok ? (await res.json()).rates : [];
        if (! this.rates.some((r) => r.code === this.shipping)) {
            this.shipping = this.rates[0]?.code ?? '';
        }
    },
});
