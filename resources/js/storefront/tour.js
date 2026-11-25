// The shopper tour on the storefront: a "Take the 1-minute tour" button on the home page, then
// popups on the home page, a product page and the cart. Progress is kept in this browser only
// (localStorage, when it is available); data comes from the JSON block printed by
// resources/views/storefront/partials/tour.blade.php.
import { Tour } from '../tour/engine.js';

const KEY = 'store-tour';

const PLAY_ICON = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 9l5 3-5 3z"/></svg>';

function load() {
    try {
        return JSON.parse(window.localStorage.getItem(KEY) || 'null') || {};
    } catch {
        return {};
    }
}

function save(state) {
    try {
        window.localStorage.setItem(KEY, JSON.stringify(state));
    } catch {
        // Private mode or storage blocked: the tour still works on this page.
    }
}

function start() {
    const node = document.getElementById('store-tour-data');
    if (! node) return;
    let data;
    try {
        data = JSON.parse(node.textContent || 'null');
    } catch {
        return;
    }
    if (! data || ! Array.isArray(data.steps) || ! data.steps.length) return;

    const steps = data.steps;
    let launcher = null;

    const tour = new Tour({
        steps,
        name: data.labels.name,
        labels: data.labels,
        skin: 'store',
        onMove: (index) => {
            save({ status: 'active', step: index });
            const step = steps[index];
            if (step.page !== data.route && step.url) {
                window.location.href = step.url;

                return;
            }
            tour.show(index);
        },
        onClose: () => {
            save({ status: 'skipped' });
            tour.destroy();
        },
        onFinish: () => {
            save({ status: 'done' });
            tour.destroy();
        },
    });

    function showLauncher() {
        if (data.route !== 'home' || launcher) return;
        launcher = document.createElement('button');
        launcher.type = 'button';
        launcher.className = 'tour-launcher';
        const icon = document.createElement('span');
        icon.className = 'tour-launcher-icon';
        icon.innerHTML = PLAY_ICON;
        launcher.append(icon, document.createTextNode(data.labels.launcher));
        launcher.addEventListener('click', () => {
            launcher.remove();
            launcher = null;
            save({ status: 'active', step: 0 });
            tour.show(0);
        });
        document.body.append(launcher);
    }

    const state = load();
    if (state.status === 'active') {
        const index = Math.max(0, Math.min(Number(state.step) || 0, steps.length - 1));
        if (steps[index].page === data.route) {
            tour.show(index);

            return;
        }
    }
    // Shown until the shopper finishes or skips the tour once.
    if (state.status !== 'done' && state.status !== 'skipped') showLauncher();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
