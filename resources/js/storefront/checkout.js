/**
 * Checkout in three steps (contact → delivery → payment) inside one form.
 *
 *   <form x-data="checkoutForm({ step, shipping, payment, totals, messages, errorClass, errorStyle })"
 *         @submit="submit($event)">
 *     <div x-show="step === 1" data-step="1"> … required fields … </div>
 *
 * Moving forward checks the required fields of the steps being left. Anything missing gets a
 * message right under the field (and the first one gets focus), so "Continue" never silently
 * does nothing. The server still validates everything again when the order is placed.
 */
export default function checkoutForm(config = {}) {
    const messages = { required: 'Please fill in this field.', email: 'Enter a valid email address.', ...(config.messages || {}) };

    const errorId = (field) => `${field.name || field.id || 'field'}-client-error`.replace(/[^\w-]/g, '-');

    const clearError = (field) => {
        if (!field || !field.name) return;
        field.removeAttribute('aria-invalid');
        document.getElementById(errorId(field))?.remove();
    };

    const messageFor = (field) => {
        if (field.validity.valueMissing) return messages.required;
        if (field.validity.typeMismatch && field.type === 'email') return messages.email;
        return field.validationMessage || messages.required;
    };

    return {
        step: config.step ?? 1,
        shipping: config.shipping ?? null,
        payment: config.payment ?? null,
        totals: config.totals ?? {},
        coupon: !!config.coupon,
        busy: false,

        get t() {
            return this.totals[this.shipping]?.[this.payment] ?? {};
        },

        /** "$ 12.00" → "$12.00" for symbol currencies (the designs show no space). */
        fmt(value) {
            return (value || '').replace(/^([^\d\s]+) /, '$1');
        },

        init() {
            const onEdit = (event) => {
                if (event.target.getAttribute('aria-invalid') === 'true' && event.target.checkValidity()) clearError(event.target);
            };
            this.$root.addEventListener('input', onEdit);
            this.$root.addEventListener('change', onEdit);
        },

        /** Shows a message under every empty or invalid required field of one step. */
        validateStep(number) {
            const box = this.$root.querySelector(`[data-step="${number}"]`);
            if (!box) return true;

            let first = null;
            box.querySelectorAll('input, select, textarea').forEach((field) => {
                if (!field.willValidate || field.type === 'radio' || field.type === 'checkbox' || field.type === 'hidden') return;
                clearError(field);
                if (field.checkValidity()) return;

                const note = document.createElement('p');
                note.id = errorId(field);
                note.dataset.clientError = '';
                note.textContent = messageFor(field);
                if (config.errorClass) note.className = config.errorClass;
                if (config.errorStyle) note.setAttribute('style', config.errorStyle);
                field.setAttribute('aria-invalid', 'true');
                field.setAttribute('aria-describedby', note.id);
                field.insertAdjacentElement('afterend', note);
                first ??= field;
            });

            if (first) {
                // Wait for x-show to reveal the step before focusing.
                this.$nextTick(() => {
                    first.focus({ preventScroll: true });
                    first.scrollIntoView({ block: 'center', behavior: 'smooth' });
                });
                return false;
            }

            return true;
        },

        go(to) {
            // Going forward: every step being left must be complete.
            for (let number = this.step; number < to; number++) {
                if (!this.validateStep(number)) {
                    this.step = number;
                    return false;
                }
            }
            this.step = to;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return true;
        },

        submit(event) {
            if (this.busy) {
                event.preventDefault();
                return;
            }
            const steps = [...this.$root.querySelectorAll('[data-step]')].map((el) => Number(el.dataset.step)).sort();
            for (const number of steps) {
                if (!this.validateStep(number)) {
                    event.preventDefault();
                    this.step = number;
                    return;
                }
            }
            this.busy = true;
        },

        rememberEmail(value) {
            if (!/.+@.+\..+/.test(value || '') || !this.$root.dataset.emailUrl) return;
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            fetch(this.$root.dataset.emailUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ email: value }),
            }).catch(() => {});
        },
    };
}
