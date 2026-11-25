// Guided-tour popups, shared by the admin (resources/js/admin/tours.js) and the storefront
// (resources/js/storefront/tour.js). It dims the page, rings the element a step points at and
// places a popup next to it (a bottom sheet on phones). Everything is built with DOM methods and
// textContent — no HTML from data, no inline scripts — so it works under the strict CSP.
//
// A step: { title, text, target: CSS selector | null, page, url }. When the target is missing or
// hidden, the popup shows in the middle of the screen instead.

const PAD = 6;      // space between the element and the ring
const GAP = 14;     // space between the ring and the popup
const EDGE = 12;    // keep the popup this far from the window edges
const SHEET_BELOW = 640;

const CLOSE_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';

function isVisible(node) {
    if (! node) return false;
    const rect = node.getBoundingClientRect();
    if (rect.width < 2 || rect.height < 2) return false;
    const style = window.getComputedStyle(node);

    return style.visibility !== 'hidden' && style.display !== 'none';
}

export function findTarget(selector) {
    if (! selector) return null;
    let nodes;
    try {
        nodes = document.querySelectorAll(selector);
    } catch {
        return null;
    }

    return Array.from(nodes).find(isVisible) ?? null;
}

function make(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;

    return node;
}

const clamp = (value, min, max) => Math.max(min, Math.min(value, max));

let uid = 0;

export class Tour {
    /**
     * @param {object} options
     * @param {Array} options.steps
     * @param {string} options.name          tour name shown in the popup label
     * @param {object} options.labels        next, back, finish, skip, close, stepOf (":step", ":total"), notHere
     * @param {string} options.skin          'admin' | 'store'
     * @param {(index:number)=>void} options.onMove
     * @param {()=>void} options.onClose
     * @param {()=>void} options.onFinish
     * @param {(index:number)=>void} [options.onTargetClick]
     */
    constructor(options) {
        this.o = options;
        this.index = 0;
        this.target = null;
        this.frame = 0;
        this.layer = make('div', 'tour-layer');
        this.layer.dataset.skin = options.skin || 'admin';
        this.dim = make('div', 'tour-dim');
        this.ring = make('div', 'tour-ring');
        this.ring.setAttribute('aria-hidden', 'true');
        this.layer.append(this.dim, this.ring);

        this.reposition = () => {
            cancelAnimationFrame(this.frame);
            this.frame = requestAnimationFrame(() => this.place());
        };
        this.onKey = (event) => {
            if (event.key === 'Escape') this.o.onClose();
        };
        this.onTargetClick = () => this.o.onTargetClick?.(this.index);
        this.observer = typeof ResizeObserver === 'function' ? new ResizeObserver(this.reposition) : null;
    }

    show(index) {
        const step = this.o.steps[index];
        if (! step) return;

        this.detachTarget();
        this.index = index;
        this.target = findTarget(step.target);

        if (! this.layer.isConnected) {
            document.body.append(this.layer);
            window.addEventListener('resize', this.reposition);
            window.addEventListener('scroll', this.reposition, true);
            document.addEventListener('keydown', this.onKey);
            this.observer?.observe(document.body);
        }

        this.pop?.remove();
        this.pop = this.buildPopup(step, index);
        this.layer.append(this.pop);

        this.target?.addEventListener('click', this.onTargetClick);

        this.place();
        this.bringIntoView();
        // Pages often grow a moment after loading (Alpine sections, images): check again.
        clearTimeout(this.settle);
        this.settle = setTimeout(() => {
            this.bringIntoView();
            this.place();
        }, 500);
        this.pop.focus({ preventScroll: true });
    }

    buildPopup(step, index) {
        const total = this.o.steps.length;
        const labels = this.o.labels;
        const id = `tour-title-${++uid}`;

        const pop = make('section', 'tour-pop');
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-modal', 'false');
        pop.setAttribute('aria-labelledby', id);
        pop.tabIndex = -1;

        const arrow = make('span', 'tour-arrow');
        arrow.setAttribute('aria-hidden', 'true');

        const head = make('div', 'tour-head');
        const stepOf = labels.stepOf.replace(':step', String(index + 1)).replace(':total', String(total));
        head.append(make('span', 'tour-label', this.o.name ? `${this.o.name} · ${stepOf}` : stepOf));
        const close = make('button', 'tour-close');
        close.type = 'button';
        close.setAttribute('aria-label', labels.close);
        close.innerHTML = CLOSE_ICON;
        close.addEventListener('click', () => this.o.onClose());
        head.append(close);

        const progress = make('div', 'tour-progress');
        progress.setAttribute('aria-hidden', 'true');
        for (let i = 0; i < total; i++) {
            progress.append(make('span', i <= index ? 'is-done' : ''));
        }

        const title = make('h2', 'tour-title', step.title);
        title.id = id;
        const body = make('div', 'tour-body');
        body.append(title, make('p', 'tour-text', step.text));
        if (step.target && ! this.target && labels.notHere) {
            body.append(make('p', 'tour-note', labels.notHere));
        }

        const foot = make('div', 'tour-foot');
        const skip = make('button', 'tour-skip', labels.skip);
        skip.type = 'button';
        skip.addEventListener('click', () => this.o.onClose());
        const buttons = make('div', 'tour-buttons');
        if (index > 0) {
            const back = make('button', 'tour-btn tour-btn--secondary', labels.back);
            back.type = 'button';
            back.addEventListener('click', () => this.o.onMove(index - 1));
            buttons.append(back);
        }
        const last = index === total - 1;
        const next = make('button', 'tour-btn tour-btn--primary', last ? labels.finish : labels.next);
        next.type = 'button';
        next.addEventListener('click', () => (last ? this.o.onFinish() : this.o.onMove(index + 1)));
        buttons.append(next);
        foot.append(skip, buttons);

        pop.append(arrow, head, progress, body, foot);

        return pop;
    }

    place() {
        if (! this.pop) return;
        const pop = this.pop;
        const arrow = pop.querySelector('.tour-arrow');
        const sheet = window.innerWidth < SHEET_BELOW;
        pop.toggleAttribute('data-sheet', sheet);
        pop.removeAttribute('data-side');
        pop.style.left = pop.style.top = '';

        const target = this.target && this.target.isConnected && isVisible(this.target) ? this.target : null;
        this.dim.hidden = !! target;
        this.ring.hidden = ! target;
        pop.toggleAttribute('data-centered', ! target && ! sheet);

        if (! target) return;

        const r = this.target.getBoundingClientRect();
        Object.assign(this.ring.style, {
            left: `${r.left - PAD}px`,
            top: `${r.top - PAD}px`,
            width: `${r.width + PAD * 2}px`,
            height: `${r.height + PAD * 2}px`,
        });

        if (sheet) return;

        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const pw = pop.offsetWidth;
        const ph = pop.offsetHeight;
        const rtl = document.documentElement.dir === 'rtl';
        let side = 'none';
        let left;
        let top;

        if (vh - r.bottom >= ph + GAP + PAD + EDGE) {
            side = 'bottom';
            top = r.bottom + PAD + GAP;
        } else if (r.top >= ph + GAP + PAD + EDGE + 56) {
            side = 'top';
            top = r.top - PAD - GAP - ph;
        } else if (vw - r.right >= pw + GAP + PAD + EDGE) {
            side = 'right';
            left = r.right + PAD + GAP;
        } else if (r.left >= pw + GAP + PAD + EDGE) {
            side = 'left';
            left = r.left - PAD - GAP - pw;
        }

        if (side === 'bottom' || side === 'top') {
            left = clamp(rtl ? r.right - pw : r.left, EDGE, vw - pw - EDGE);
            arrow.style.left = `${clamp(r.left + r.width / 2 - left - 7, 18, pw - 32)}px`;
            arrow.style.top = '';
        } else if (side === 'left' || side === 'right') {
            top = clamp(r.top + r.height / 2 - ph / 2, 64, vh - ph - EDGE);
            arrow.style.top = `${clamp(r.top + r.height / 2 - top - 7, 18, ph - 32)}px`;
            arrow.style.left = '';
        } else {
            // The element fills the screen: keep the popup in the corner, over it.
            left = rtl ? EDGE : vw - pw - EDGE * 2;
            top = vh - ph - EDGE * 2;
        }

        pop.dataset.side = side;
        pop.style.left = `${left}px`;
        pop.style.top = `${top}px`;
    }

    /**
     * Scroll the highlighted element into view: centred on large screens (its top for a tall one),
     * and above the bottom sheet on phones.
     */
    bringIntoView() {
        if (! this.target || ! isVisible(this.target)) return;
        const rect = this.target.getBoundingClientRect();
        const top = 72;
        const sheet = this.pop?.hasAttribute('data-sheet');
        const bottom = sheet ? window.innerHeight - this.pop.offsetHeight - 16 : window.innerHeight - 20;
        if (rect.top >= top && rect.bottom <= bottom) return;

        const tall = rect.height > (bottom - top) * 0.8;
        const offset = sheet || tall ? top : (window.innerHeight - rect.height) / 2;
        window.scrollTo({ top: Math.max(0, window.scrollY + rect.top - offset), behavior: 'smooth' });
    }

    detachTarget() {
        this.target?.removeEventListener('click', this.onTargetClick);
        this.target = null;
    }

    destroy() {
        this.detachTarget();
        cancelAnimationFrame(this.frame);
        clearTimeout(this.settle);
        this.observer?.disconnect();
        window.removeEventListener('resize', this.reposition);
        window.removeEventListener('scroll', this.reposition, true);
        document.removeEventListener('keydown', this.onKey);
        this.layer.remove();
        this.pop = null;
    }
}
