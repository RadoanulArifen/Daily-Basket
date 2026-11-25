// Slide-out bag (the design's cart drawer). Its contents come from the server
// (GET /cart/drawer) each time it opens or changes, so prices, stock and choices are real.
// Opened by the bag icon and after "Add to cart" on the product page: window event
// 'open-cart-drawer'.
export default function cartDrawer(contentsUrl, itemsUrl) {
    return {
        open: false,
        busy: false,
        html: '',
        opener: null,

        async show() {
            // Focus returns here on close; fall back to the bag icon (e.g. the Add to cart
            // button is disabled while adding, so it has already lost focus).
            const active = document.activeElement;
            this.opener = active && active !== document.body ? active : document.querySelector('[data-cart-link]');
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            await this.load();
            this.$nextTick(() => this.$refs.panel.focus());
        },

        close() {
            if (!this.open) return;
            this.open = false;
            document.documentElement.style.overflow = '';
            const target = this.opener && document.contains(this.opener) && !this.opener.disabled ? this.opener : document.querySelector('[data-cart-link]');
            target?.focus?.();
        },

        async load() {
            try {
                const response = await fetch(contentsUrl, { headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
                if (response.ok) this.html = await response.text();
            } catch (e) {
                // Offline: keep what is shown; the bag page still works.
            }
        },

        async change(method, id, quantity) {
            if (this.busy) return;
            this.busy = true;
            try {
                const body = new FormData();
                body.append('_method', method);
                if (quantity !== undefined) body.append('quantity', quantity);
                const response = await fetch(`${itemsUrl}/${id}`, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.count !== undefined) {
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.count } }));
                } else if (!response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat() : [];
                    window.dispatchEvent(new CustomEvent('store-toast', { detail: { message: errors[0] ?? data.message ?? 'Something went wrong.', type: 'error' } }));
                }
                await this.load();
            } finally {
                this.busy = false;
            }
        },

        setQuantity(id, quantity) {
            return quantity < 1 ? this.remove(id) : this.change('PATCH', id, quantity);
        },

        remove(id) {
            return this.change('DELETE', id);
        },

        // Keep keyboard focus inside the open drawer.
        trap(event) {
            const items = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled])')];
            if (!items.length) return;
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },
    };
}
