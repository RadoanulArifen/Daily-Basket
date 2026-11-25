/**
 * The product form: images, options (Size, Colour…) and the variants generated from them.
 * Field names match App\Http\Requests\Admin\Catalog\ProductRequest.
 */
import { mediaMixin } from './media.js';

const MAX_OPTIONS = 3;
const MAX_VARIANTS = 100;
const bool = (v) => [true, 1, '1', 'true', 'on'].includes(v);

let counter = 0;
const newKey = (prefix) => `new-${prefix}-${Date.now().toString(36)}-${counter++}`;

function blankVariant(template = {}) {
    return {
        id: null,
        option_keys: [],
        price: template.price ?? '',
        compare_at_price: template.compare_at_price ?? '',
        cost_price: template.cost_price ?? '',
        sku: '',
        barcode: '',
        weight_grams: template.weight_grams ?? '',
        track_inventory: template.track_inventory ?? true,
        allow_backorder: template.allow_backorder ?? false,
        is_active: true,
        stock_quantity: 0,
        original_stock: null,
        media_id: null,
        open: false,
    };
}

function normaliseVariant(v) {
    return {
        ...blankVariant(),
        ...v,
        option_keys: (v.option_keys ?? []).map(String),
        compare_at_price: v.compare_at_price ?? '',
        cost_price: v.cost_price ?? '',
        sku: v.sku ?? '',
        barcode: v.barcode ?? '',
        weight_grams: v.weight_grams ?? '',
        track_inventory: bool(v.track_inventory ?? true),
        allow_backorder: bool(v.allow_backorder ?? false),
        is_active: bool(v.is_active ?? true),
        media_id: v.media_id ? Number(v.media_id) : null,
        open: false,
    };
}

export default function productEditor(state) {
    return {
        ...mediaMixin({
            media: state.media,
            multiple: true,
            uploadUrl: state.uploadUrl,
            libraryUrl: state.libraryUrl,
            messages: state.messages,
        }),

        options: (state.options ?? []).map((o) => ({
            ...o,
            key: String(o.key),
            name: { en: o.name?.en ?? '', ar: o.name?.ar ?? '' },
            values: (o.values ?? []).map((v) => ({ ...v, key: String(v.key), value: { en: v.value?.en ?? '', ar: v.value?.ar ?? '' } })),
        })),
        variants: (state.variants?.length ? state.variants : [blankVariant()]).map(normaliseVariant),
        errors: state.errors ?? {},
        currency: state.baseCurrency,
        locale: document.documentElement.lang || 'en',
        tooMany: false,
        dirty: false,

        init() {
            // Opening the details of any variant that failed validation.
            Object.keys(this.errors).forEach((path) => {
                const m = path.match(/^variants\.(\d+)\./);
                if (m && this.variants[m[1]]) this.variants[m[1]].open = true;
            });
        },

        err(path) {
            return this.errors[path]?.[0] ?? null;
        },

        hasOptions() {
            return this.options.length > 0;
        },

        addOption() {
            if (this.options.length >= MAX_OPTIONS) return;
            this.options.push({ id: null, key: newKey('o'), name: { en: '', ar: '' }, values: [{ id: null, key: newKey('v'), value: { en: '', ar: '' } }] });
            this.regenerate();
        },

        removeOption(index) {
            this.options.splice(index, 1);
            this.regenerate();
        },

        addValue(option) {
            option.values.push({ id: null, key: newKey('v'), value: { en: '', ar: '' } });
        },

        removeValue(option, index) {
            option.values.splice(index, 1);
            if (!option.values.length) this.addValue(option);
            this.regenerate();
        },

        /** Every combination of the filled-in option values. */
        combinations() {
            const lists = this.options.map((o) => o.values.filter((v) => v.value.en.trim() !== '').map((v) => v.key));
            if (!lists.length || lists.some((l) => !l.length)) return [];

            return lists.reduce((acc, list) => acc.flatMap((combo) => list.map((key) => [...combo, key])), [[]]);
        },

        /** Rebuild the variant list, keeping prices/stock of combinations that still exist. */
        regenerate() {
            const signature = (keys) => [...keys].sort().join('|');

            if (!this.hasOptions()) {
                const first = this.variants[0] ?? blankVariant();
                first.option_keys = [];
                this.variants = [first];
                this.tooMany = false;
                return;
            }

            const combos = this.combinations();
            // Options still being typed: keep the current variants (and their prices/stock) as they are.
            if (!combos.length) return;
            if (combos.length > MAX_VARIANTS) {
                this.tooMany = true;
                return;
            }
            this.tooMany = false;

            const existing = new Map(this.variants.filter((v) => v.option_keys.length).map((v) => [signature(v.option_keys), v]));
            const template = this.variants[0] ?? {};
            // When a product first gets options, its single variant becomes the first combination.
            const reusable = this.variants.length === 1 && !this.variants[0].option_keys.length ? this.variants[0] : null;

            this.variants = combos.map((keys, i) => {
                const found = existing.get(signature(keys));
                if (found) {
                    found.option_keys = keys;
                    return found;
                }
                if (i === 0 && reusable) {
                    reusable.option_keys = keys;
                    return reusable;
                }
                return { ...blankVariant(template), option_keys: keys };
            });
        },

        valueLabel(key) {
            for (const option of this.options) {
                const value = option.values.find((v) => v.key === key);
                if (value) return (this.locale === 'ar' && value.value.ar) || value.value.en || '—';
            }
            return '—';
        },

        variantLabel(variant) {
            return variant.option_keys.map((k) => this.valueLabel(k)).join(' / ');
        },

        applyToAll(field) {
            const value = this.variants[0]?.[field];
            this.variants.forEach((v) => { v[field] = value; });
        },

        profit(variant) {
            const price = parseFloat(variant.price);
            const cost = parseFloat(variant.cost_price);
            if (!(price > 0) || Number.isNaN(cost)) return null;
            const profit = price - cost;
            return { profit: profit.toFixed(2), margin: Math.round((profit / price) * 100) };
        },

        variantImage(variant) {
            return this.media.find((m) => m.id === Number(variant.media_id))?.thumb ?? null;
        },
    };
}
