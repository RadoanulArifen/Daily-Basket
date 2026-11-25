// TinyMCE 7 for every <textarea data-rich> in the admin. Loaded on demand by rich-text.js.
//
// Everything is bundled: the skin and content CSS are imported as JS modules, so nothing is
// fetched at run time and the Content-Security-Policy can stay strict (no CDN, no eval).
import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom/model';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/table';
import 'tinymce/plugins/image';
import 'tinymce/plugins/media';
import './tinymce-admin.css';

// Exactly what App\Support\RichText keeps; the server cleans again on save.
const VALID_ELEMENTS = [
    'p[style]', 'br', 'div', 'h2[style]', 'h3[style]', 'h4[style]',
    'strong/b', 'em/i', 'u', 's', 'del',
    'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr',
    'a[href|title|target]',
    'img[src|alt|title|width|height]', 'figure', 'figcaption',
    'video[src|controls|poster|width|height|preload|playsinline|muted|loop]',
    'iframe[src|title|width|height|allowfullscreen|allow|loading|frameborder]',
    'table', 'thead', 'tbody', 'tr', 'th[colspan|rowspan|style]', 'td[colspan|rowspan|style]', 'colgroup', 'col',
].join(',');

const EMBEDS = ['https://www.youtube-nocookie.com/embed/', 'https://www.youtube.com/embed/', 'https://player.vimeo.com/video/'];
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// A YouTube / Vimeo / .mp4 address → the HTML the server keeps, or null.
function videoHtml(url) {
    url = String(url || '').trim();
    let m = url.match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/i);
    if (m) return `<iframe src="https://www.youtube-nocookie.com/embed/${m[1]}" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen" loading="lazy"></iframe>`;
    m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
    if (m) return `<iframe src="https://player.vimeo.com/video/${m[1]}" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen" loading="lazy"></iframe>`;
    if (EMBEDS.some((p) => url.startsWith(p))) return `<iframe src="${esc(url)}" width="560" height="315" frameborder="0" allowfullscreen="allowfullscreen" loading="lazy"></iframe>`;
    if (/^(https:\/\/|\/(?!\/))\S+\.(mp4|webm|m4v)(\?\S*)?$/i.test(url)) return `<video src="${esc(url)}" controls="controls" preload="metadata"></video>`;
    return null;
}

export function mount(textarea) {
    let labels = {};
    try { labels = JSON.parse(textarea.dataset.labels || '{}'); } catch { /* defaults below */ }
    const t = (k, d) => labels[k] || d;
    const rtl = (textarea.getAttribute('dir') || '') === 'rtl';
    const rows = parseInt(textarea.getAttribute('rows') || '6', 10);
    const uploadUrl = textarea.dataset.uploadUrl;
    const libraryUrl = textarea.dataset.libraryUrl;

    tinymce.init({
        target: textarea,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        skin_url: 'default',
        content_css: 'default',
        menubar: false,
        statusbar: true,
        elementpath: false,
        resize: true,
        height: Math.max(180, rows * 26 + 110),
        directionality: rtl ? 'rtl' : 'ltr',
        plugins: 'lists link code fullscreen table image media',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | link | bullist numlist blockquote hr | alignleft aligncenter alignright | image media table | removeformat | code fullscreen',
        toolbar_mode: window.innerWidth < 640 ? 'sliding' : 'wrap',
        block_formats: `${t('paragraph', 'Paragraph')}=p; ${t('heading', 'Heading')} 2=h2; ${t('heading', 'Heading')} 3=h3; ${t('heading', 'Heading')} 4=h4`,
        valid_elements: VALID_ELEMENTS,
        valid_styles: { '*': 'text-align' },
        // The cleaner keeps <u> and <s>, not spans with styles.
        formats: {
            underline: { inline: 'u', remove: 'all' },
            strikethrough: { inline: 's', remove: 'all' },
        },
        convert_urls: false,
        link_default_protocol: 'https',
        link_target_list: [{ title: t('same_tab', 'Same tab'), value: '' }, { title: t('new_tab', 'Open in a new tab'), value: '_blank' }],
        image_dimensions: false,
        image_description: true,
        image_caption: false,
        // Pictures go to the media library (resized to WebP), like everywhere else in the admin.
        images_upload_handler: async (blob) => {
            if (!uploadUrl) throw new Error(t('upload_failed', 'Could not upload that picture.'));
            const data = new FormData();
            data.append('file', blob.blob(), blob.filename());
            const response = await fetch(uploadUrl, { method: 'POST', body: data, headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const media = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(media?.errors?.file?.[0] || media?.message || t('upload_failed', 'Could not upload that picture.'));
            return media.url;
        },
        image_list: (done) => {
            if (!libraryUrl) return done([]);
            fetch(libraryUrl, { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((j) => done((j.data || []).map((m) => ({ title: m.name || m.alt || 'Image', value: m.url }))))
                .catch(() => done([]));
        },
        // YouTube, Vimeo or an .mp4 address; anything else is refused with a reason.
        media_alt_source: false,
        media_poster: false,
        media_dimensions: false,
        media_url_resolver: (data) => {
            const html = videoHtml(data.url);
            if (html) return Promise.resolve({ html });
            return Promise.reject({ msg: t('video_bad', 'Use a YouTube or Vimeo link, or an .mp4 address.') });
        },
        table_default_attributes: {},
        table_default_styles: {},
        table_resize_bars: false,
        object_resizing: false,
        content_style: 'body{font-family:Inter,"IBM Plex Sans Arabic",system-ui,sans-serif;font-size:14px;line-height:1.6;color:#303030;margin:12px 14px}img,video,iframe{max-width:100%;height:auto}',
        setup: (editor) => {
            // Keep the textarea up to date on every change, and tell the page (unsaved-changes bars).
            let last = textarea.value;
            let ready = false;
            const sync = () => {
                editor.save();
                if (textarea.value !== last) {
                    last = textarea.value;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };
            editor.on('input change undo redo keyup ExecCommand NodeChange SetContent', () => { if (ready) sync(); });
            editor.on('init', () => {
                // TinyMCE tidies the HTML when it loads; that is not a change by the user,
                // so the tidied copy becomes the starting point without an input event.
                editor.save();
                last = textarea.value;
                // Start listening only after TinyMCE's own first tidy-up has finished.
                setTimeout(() => { editor.save(); last = textarea.value; ready = true; }, 0);
                editor.getContainer().classList.toggle('is-error', textarea.classList.contains('admin-field-error'));
                textarea.dispatchEvent(new CustomEvent('rich:ready', { bubbles: true }));
            });
        },
    });
}
