// Product page (the design's buy box): pack size, quantity, price and "Add to Cart · $total".
// config = { variants: [{ id, label, price, minor, old, save, pct, stock, available, sku }], start, unit }
export default function fnProduct(config) {
    return {
        variants: config.variants,
        vi: config.start ?? 0,
        qty: 1,
        image: 0,
        get current() {
            return this.variants[this.vi] ?? { id: null, price: '', old: '', save: '', pct: 0, stock: null, available: false, sku: '' };
        },
        pick(index) {
            this.vi = index;
            if (this.current.stock !== null) this.qty = Math.max(1, Math.min(this.qty, this.current.stock));
        },
        total() {
            return config.unit.replace('__', ((this.current.minor * this.qty) / 100).toFixed(2));
        },
    };
}
