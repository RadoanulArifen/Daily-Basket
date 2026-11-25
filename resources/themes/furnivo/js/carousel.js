/** Product rows: the arrow buttons scroll one "page" of cards, as in the design. */
export default () => ({
    atStart: true,
    atEnd: false,

    init() {
        this.$nextTick(() => this.update());
    },

    update() {
        const t = this.$refs.track;
        if (! t) return;
        this.atStart = t.scrollLeft <= 2;
        this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 2;
    },

    move(direction) {
        const t = this.$refs.track;
        t.scrollBy({ left: direction * t.clientWidth * 0.9, behavior: 'smooth' });
    },
});
