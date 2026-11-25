// The admin's HTML editor: TinyMCE 7 (GPL, bundled by Vite, nothing from a CDN).
// Used by <x-admin.translatable-input rich>, which prints <textarea data-rich>.
// TinyMCE itself is a separate chunk loaded only on pages that have such a textarea, so the
// rest of the admin stays light. Whatever is saved is cleaned again on the server
// (App\Support\RichText).

let loading = null;
const load = () => (loading ??= import('./tinymce-editor.js'));

function mountAll(scope = document) {
    const areas = [...(scope.matches?.('textarea[data-rich]') ? [scope] : []), ...scope.querySelectorAll('textarea[data-rich]')]
        .filter((t) => !t.dataset.richReady);
    if (!areas.length) return;
    areas.forEach((t) => { t.dataset.richReady = '1'; });
    load()
        .then(({ mount }) => areas.forEach(mount))
        .catch((e) => {
            // The plain textarea stays usable if the editor can't load.
            console.error('[editor] could not load the HTML editor', e);
            areas.forEach((t) => t.classList.add('admin-field'));
        });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => mountAll());
else mountAll();

// Editors that appear later (Alpine templates) mount when they show up.
new MutationObserver((changes) => {
    for (const change of changes) {
        change.addedNodes.forEach((n) => { if (n.nodeType === 1) mountAll(n); });
    }
}).observe(document.documentElement, { childList: true, subtree: true });
