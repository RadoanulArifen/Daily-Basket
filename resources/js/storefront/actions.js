/**
 * Small progressive enhancements. Every form works without JavaScript;
 * with it, adding to the bag and saving to the wishlist happen without a reload.
 */
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export function toast(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('store-toast', { detail: { message, type } }));
}

async function send(form) {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    });
    const body = await response.json().catch(() => ({}));

    if (!response.ok) {
        const errors = body.errors ? Object.values(body.errors).flat() : [];
        throw new Error(errors[0] ?? body.message ?? form.dataset.error);
    }

    return body;
}

export function addToCart() {
    return {
        busy: false,
        error: null,

        async submit(event) {
            if (this.busy) return;
            this.busy = true;
            this.error = null;

            try {
                const body = await send(event.target);
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: body.count } }));
                toast(body.message);
            } catch (e) {
                this.error = e.message;
            } finally {
                this.busy = false;
            }
        },
    };
}

export function wishlistButton(saved) {
    return {
        saved,
        busy: false,

        async toggle(event) {
            if (this.busy) return;
            this.busy = true;

            try {
                const body = await send(event.target);
                this.saved = body.saved;
                window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: { count: body.count } }));
                toast(body.message);
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                this.busy = false;
            }
        },
    };
}

export function storeToasts(initial) {
    return {
        items: [],

        init() {
            if (initial) this.push({ message: initial, type: 'success' });
            window.addEventListener('store-toast', (e) => this.push(e.detail));
        },

        push({ message, type }) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type });
            setTimeout(() => this.dismiss(id), 4000);
        },

        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    };
}
