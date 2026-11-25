/**
 * Picks a product's variant from its options (Size, Colour…).
 *
 * The page is rendered with the chosen variant already in place, so this only has to keep
 * up: which values are chosen, which combinations exist, and what that means for the price,
 * the SKU and whether the piece can be added to the bag.
 */
export default function variantPicker(picker = {}) {
    const variants = picker.variants ?? [];
    const options = picker.options ?? [];

    return {
        options,
        chosen: {},
        variantId: picker.selected ?? null,

        initPicker() {
            const current = variants.find((v) => v.id === this.variantId) ?? variants[0];
            if (! current) return;
            this.variantId = current.id;
            options.forEach((option, index) => {
                const value = option.values.find((v) => current.values.includes(v.id));
                this.chosen[option.id] = value ? value.id : option.values[0]?.id ?? null;
                if (index === 0 && ! value) this.match();
            });
        },

        pick(optionId, valueId) {
            this.chosen[optionId] = valueId;
            this.match();
        },

        /** The variant that has every chosen value; falls back to the closest one. */
        match() {
            const wanted = Object.values(this.chosen);
            const exact = variants.find((v) => wanted.every((id) => v.values.includes(id)));
            if (exact) {
                this.variantId = exact.id;
                return;
            }
            // No such combination: keep the last option chosen and move to a variant that has it.
            const lastChosen = wanted[wanted.length - 1];
            const near = variants.find((v) => v.values.includes(lastChosen));
            if (near) {
                this.variantId = near.id;
                options.forEach((option) => {
                    const value = option.values.find((v) => near.values.includes(v.id));
                    if (value) this.chosen[option.id] = value.id;
                });
            }
        },

        get variant() {
            return variants.find((v) => v.id === this.variantId) ?? null;
        },

        isChosen(optionId, valueId) {
            return this.chosen[optionId] === valueId;
        },

        /** True when choosing this value leads to a combination that can be bought. */
        isAvailable(optionId, valueId) {
            const others = Object.entries(this.chosen).filter(([id]) => Number(id) !== Number(optionId)).map(([, v]) => v);

            return variants.some((v) => v.values.includes(valueId) && others.every((id) => v.values.includes(id)) && v.available);
        },
    };
}
