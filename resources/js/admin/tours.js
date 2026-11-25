// Admin guided tours: runs the active tour's popups on the page the step belongs to, saves
// progress to the server (admin.tours.update) and, when the admin wanders to another page,
// shows a small "continue" bar instead. Data comes from the JSON block rendered by
// resources/views/admin/partials/tours.blade.php.
import { Tour } from '../tour/engine.js';

function readData() {
    const node = document.getElementById('admin-tour-data');
    if (! node) return null;
    try {
        return JSON.parse(node.textContent || 'null');
    } catch {
        return null;
    }
}

function post(data, fields, { beacon = false } = {}) {
    const body = new FormData();
    body.append('_token', data.csrf);
    Object.entries(fields).forEach(([key, value]) => body.append(key, String(value)));

    // A click that leaves the page: the beacon still reaches the server.
    if (beacon && navigator.sendBeacon) {
        navigator.sendBeacon(data.endpoint, body);

        return Promise.resolve(null);
    }

    return fetch(data.endpoint, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        keepalive: true,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => (response.ok ? response.json() : null)).catch(() => null);
}

function toast(message) {
    if (message && typeof window.toast === 'function') window.toast(message, 'success');
}

function resumeBar(data, active, step, onPause) {
    const bar = document.createElement('div');
    bar.className = 'tour-resume';
    bar.setAttribute('role', 'status');
    const text = document.createElement('span');
    text.className = 'tour-resume-text';
    text.textContent = `${active.name} · ${data.labels.stepOf.replace(':step', String(active.step + 1)).replace(':total', String(active.steps.length))}`;
    const go = document.createElement('a');
    go.className = 'tour-resume-go';
    go.href = step.url;
    go.textContent = data.labels.resume;
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'tour-resume-close';
    close.setAttribute('aria-label', data.labels.close);
    close.textContent = '×';
    close.addEventListener('click', () => {
        bar.remove();
        onPause();
    });
    bar.append(text, go, close);
    document.body.append(bar);
}

function start() {
    const data = readData();
    const active = data?.active;
    if (! active || ! Array.isArray(active.steps) || ! active.steps.length) return;

    const steps = active.steps;
    const key = active.key;
    const pause = async () => {
        const result = await post(data, { action: 'pause' });
        toast(result?.toast);
    };

    const tour = new Tour({
        steps,
        name: active.name,
        labels: data.labels,
        skin: 'admin',
        onMove: async (index) => {
            const step = steps[index];
            if (step.page && step.page !== data.route && step.url) {
                await post(data, { action: 'go', tour: key, step: index });
                window.location.href = step.url;

                return;
            }
            post(data, { action: 'go', tour: key, step: index });
            tour.show(index);
        },
        onTargetClick: (index) => {
            // Clicking a highlighted link (Products, Add product, an order row) moves the tour on.
            if (steps[index].click && index + 1 < steps.length) {
                post(data, { action: 'go', tour: key, step: index + 1 }, { beacon: true });
            }
        },
        onClose: () => {
            tour.destroy();
            pause();
        },
        onFinish: async () => {
            tour.destroy();
            const result = await post(data, { action: 'finish', tour: key });
            if (result?.toast) {
                toast(result.next ? `${result.toast} · ${data.labels.nextUp.replace(':name', result.next.name)}` : result.toast);
            }
        },
    });

    const step = steps[active.step] ?? steps[0];
    if (! step.page || step.page === data.route || ! step.url) {
        tour.show(steps.indexOf(step));
    } else {
        resumeBar(data, active, step, pause);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
