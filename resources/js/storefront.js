// Alpine.js is bundled in (./alpine.js) and starts on DOMContentLoaded, after this module
// has run, so components registered on "alpine:init" are always available.
import './alpine.js';
import '@fontsource/ibm-plex-sans/400.css';
import '@fontsource/ibm-plex-sans/500.css';
import '@fontsource/ibm-plex-sans/600.css';
import '@fontsource/ibm-plex-sans-arabic/400.css';
import '@fontsource/ibm-plex-sans-arabic/500.css';
import '@fontsource/ibm-plex-sans-arabic/600.css';

import productPage from './storefront/product-page.js';
import { addToCart, storeToasts, wishlistButton } from './storefront/actions.js';
import './storefront/tour.js';
import checkoutForm from './storefront/checkout.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('checkoutForm', checkoutForm);
    window.Alpine.data('productPage', productPage);
    window.Alpine.data('addToCart', addToCart);
    window.Alpine.data('wishlistButton', wishlistButton);
    window.Alpine.data('storeToasts', storeToasts);
});
