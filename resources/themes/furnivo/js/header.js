/**
 * The header is transparent over a hero image and turns solid once the page scrolls
 * (or from the start on pages without a hero), exactly like the design.
 */
export default ({ overHero = false } = {}) => ({
    overHero,
    solid: ! overHero,
    megaOpen: null,

    // The phone menu: closed by default, and one open section at a time inside it.
    menuOpen: false,
    openGroup: null,

    toggleMenu() {
        this.menuOpen = ! this.menuOpen;
        // Stop the page behind the panel from scrolling with it.
        document.body.style.overflow = this.menuOpen ? 'hidden' : '';
    },

    closeMenu() {
        this.menuOpen = false;
        this.openGroup = null;
        document.body.style.overflow = '';
    },

    mobileOpen: false,

    init() {
        if (! this.overHero) return;
        const update = () => { this.solid = window.scrollY > 40; };
        update();
        window.addEventListener('scroll', update, { passive: true });
    },

    // Over the hero the header stays transparent, even with the mega menu open (as in the design).
    get dark() {
        return ! this.solid;
    },
});
