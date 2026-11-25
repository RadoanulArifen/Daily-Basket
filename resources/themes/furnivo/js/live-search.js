/**
 * Results while you type, in the header search box.
 *
 * Deliberately restrained: it waits for a pause in typing, never runs two requests at once,
 * and always leaves the form itself working — pressing Enter still goes to the full results
 * page, so the box does the right thing with JavaScript broken or the network slow.
 */
export default function liveSearch(endpoint) {
    return {
        term: '',
        results: [],
        suggestion: null,
        allUrl: null,
        open: false,
        busy: false,
        active: -1,          // which result the arrow keys are on
        timer: null,
        controller: null,    // so a slow reply cannot overwrite a newer one

        look() {
            clearTimeout(this.timer);
            this.active = -1;

            if (this.term.trim().length < 2) {
                this.results = [];
                this.suggestion = null;
                this.open = false;

                return;
            }

            // Wait for a pause, rather than a request per keystroke.
            this.timer = setTimeout(() => this.fetchResults(), 220);
        },

        async fetchResults() {
            this.controller?.abort();
            this.controller = new AbortController();
            this.busy = true;

            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(this.term.trim())}`, {
                    headers: { Accept: 'application/json' },
                    signal: this.controller.signal,
                });

                if (! response.ok) throw new Error('search failed');

                const data = await response.json();
                this.results = data.results ?? [];
                this.suggestion = data.suggestion ?? null;
                this.allUrl = data.all_url ?? null;
                this.open = true;
            } catch (e) {
                if (e.name !== 'AbortError') {
                    // Leave the form to do its job rather than showing an error in a dropdown.
                    this.open = false;
                }
            } finally {
                this.busy = false;
            }
        },

        move(step) {
            if (! this.open || this.results.length === 0) return;
            this.active = (this.active + step + this.results.length) % this.results.length;
        },

        choose() {
            if (this.active >= 0 && this.results[this.active]) {
                window.location = this.results[this.active].url;

                return false;   // do not also submit the form
            }

            return true;
        },

        useSuggestion() {
            this.term = this.suggestion;
            this.suggestion = null;
            this.fetchResults();
        },

        close() {
            this.open = false;
            this.active = -1;
        },
    };
}
