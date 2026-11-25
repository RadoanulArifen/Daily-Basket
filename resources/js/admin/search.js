/**
 * Top-bar search (⌘K / Ctrl+K): orders, products and customers, plus quick links.
 */
export default ({ url, shortcuts }) => ({
    open: false,
    q: '',
    groups: [],
    active: 0,
    loading: false,
    icons: {},
    controller: null,

    init() {
        this.$refs.icons.content.querySelectorAll('[data-icon]').forEach((el) => {
            this.icons[el.dataset.icon] = el.innerHTML;
        });
    },

    /** Results (or quick links before typing), each item numbered for keyboard use. */
    get view() {
        const groups = this.q.trim().length >= 2 ? this.groups : [{ label: '', items: shortcuts }];
        let index = 0;
        return groups.map((g) => ({ label: g.label, items: g.items.map((item) => ({ ...item, index: index++ })) }));
    },

    get items() {
        return this.view.flatMap((g) => g.items);
    },

    show() {
        this.open = true;
        this.active = 0;
        this.$nextTick(() => this.$refs.input.focus());
    },

    close() {
        this.open = false;
    },

    async search() {
        const term = this.q.trim();
        this.active = 0;
        if (term.length < 2) {
            this.groups = [];
            return;
        }
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        try {
            const res = await fetch(`${url}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' }, signal: this.controller.signal });
            this.groups = res.ok ? (await res.json()).groups : [];
        } catch (e) {
            if (e.name !== 'AbortError') this.groups = [];
        } finally {
            this.loading = false;
        }
    },

    move(step) {
        const count = this.items.length;
        if (count) this.active = (this.active + step + count) % count;
    },

    go() {
        const item = this.items[this.active];
        if (item) window.location.href = item.url;
    },
});
