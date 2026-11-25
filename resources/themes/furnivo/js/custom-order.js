/**
 * "Made to Order enquiry" popup. The Custom Order buttons open it; it posts to the same
 * endpoint as the full custom-order page, so both routes record the same enquiry.
 */
export default function customOrder(url) {
    return {
        open: false,
        sent: false,
        busy: false,
        error: '',
        product: '',
        category: '',
        opener: null,

        show(detail = {}) {
            this.opener = document.activeElement;
            this.product = detail?.product ?? '';
            this.category = detail?.category ?? '';
            this.sent = false;
            this.error = '';
            this.open = true;
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => {
                // Pre-select the piece's category when opened from a product page.
                if (this.$refs.category) this.$refs.category.value = this.category;
                this.$refs.panel.focus();
            });
        },

        close() {
            if (! this.open) return;
            this.open = false;
            document.documentElement.style.overflow = '';
            this.opener?.focus?.();
        },

        async send(event) {
            if (this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(event.target),
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.sent = true;
                    event.target.reset();
                } else if (response.status === 422) {
                    // Only ever show the visitor validation messages, never a server error.
                    const errors = data.errors ? Object.values(data.errors).flat() : [];
                    this.error = errors[0] ?? 'Please check the highlighted fields.';
                } else {
                    this.error = 'Something went wrong. Please try again.';
                }
            } catch (e) {
                this.error = 'Something went wrong. Please try again.';
            } finally {
                this.busy = false;
            }
        },

        trap(event) {
            const items = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled]), input, select, textarea')];
            if (! items.length) return;
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (! event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },
    };
}
