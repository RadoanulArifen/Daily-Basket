/**
 * Product page: picks the variant matching the chosen options and keeps the
 * price, stock message, gallery image and hidden variant_id in sync.
 */
export default function productPage(config) {
    return {
        options: config.options,
        variants: config.variants,
        chosen: {},
        image: 0,
        quantity: 1,

        init() {
            const start = this.variants.find((v) => v.id === config.selected) ?? this.variants[0];
            this.options.forEach((option, i) => { this.chosen[option.id] = start?.values[i] ?? null; });
            if (start?.image != null) this.image = start.image;
        },

        get variant() {
            if (!this.options.length) return this.variants[0] ?? null;
            return this.variants.find((v) => v.values.every((id, i) => this.chosen[this.options[i].id] === id)) ?? null;
        },

        choose(optionId, valueId) {
            this.chosen[optionId] = valueId;

            // If that combination doesn't exist, jump to the closest one that does.
            if (!this.variant) {
                const index = this.options.findIndex((o) => o.id === optionId);
                const match = this.variants.find((v) => v.values[index] === valueId && v.available)
                    ?? this.variants.find((v) => v.values[index] === valueId);
                if (match) this.options.forEach((o, i) => { this.chosen[o.id] = match.values[i]; });
            }

            const variant = this.variant;
            if (variant?.image != null) this.image = variant.image;
            if (variant) {
                const url = new URL(window.location.href);
                url.searchParams.set('variant', variant.id);
                window.history.replaceState({}, '', url);
            }
        },

        isChosen(optionId, valueId) {
            return this.chosen[optionId] === valueId;
        },

        /** Greyed out when no in-stock variant has this value with the other current choices. */
        isAvailable(optionId, valueId) {
            const index = this.options.findIndex((o) => o.id === optionId);
            return this.variants.some((v) => v.available && v.values[index] === valueId
                && v.values.every((id, i) => i === index || this.chosen[this.options[i].id] === id));
        },

        chosenLabel(option) {
            return option.values.find((v) => v.id === this.chosen[option.id])?.label ?? '';
        },

        step(by) {
            this.quantity = Math.min(99, Math.max(1, (parseInt(this.quantity, 10) || 1) + by));
        },
    };
}
