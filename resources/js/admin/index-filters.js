/**
 * Row selection for index tables: x-data="indexFilters({ ids: [...] })"
 * Ticking rows shows the bulk-action bar.
 */
export default ({ ids = [] } = {}) => ({
    ids,
    selected: [],

    get all() {
        return this.ids.length > 0 && this.selected.length === this.ids.length;
    },

    get some() {
        return this.selected.length > 0 && ! this.all;
    },

    toggleAll() {
        this.selected = this.all ? [] : [...this.ids];
    },

    clear() {
        this.selected = [];
    },
});
