// Alpine.js is bundled in (./alpine.js) and starts on DOMContentLoaded, after this module
// has run, so components registered on "alpine:init" are always available.
import './alpine.js';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/ibm-plex-sans-arabic/400.css';
import '@fontsource/ibm-plex-sans-arabic/500.css';
import '@fontsource/ibm-plex-sans-arabic/600.css';

import './admin/rich-text.js';
import productEditor from './admin/product-editor.js';
import { mediaMixin } from './admin/media.js';
import adminSearch from './admin/search.js';
import indexFilters from './admin/index-filters.js';
import orderBuilder from './admin/order-builder.js';
import { trafficMap, trafficLive } from './admin/traffic.js';
import './admin/tours.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('productEditor', productEditor);
    window.Alpine.data('mediaField', (config) => mediaMixin(config));
    window.Alpine.data('adminSearch', adminSearch);
    window.Alpine.data('indexFilters', indexFilters);
    window.Alpine.data('orderBuilder', orderBuilder);
    window.Alpine.data('trafficMap', trafficMap);
    window.Alpine.data('trafficLive', trafficLive);
});

/**
 * Show a toast from anywhere: window.toast('Saved', 'success')
 */
window.toast = (message, type = 'success') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};
