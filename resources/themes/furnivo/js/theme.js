// Furnivo theme scripts. Alpine is bundled in from js/alpine.js (one copy per page).
// The shared store behaviour (add to bag, wishlist, toasts, product page) comes from the base
// storefront scripts; this file only adds what the Furnivo design needs on top.
import '../../../js/alpine.js';
import productPage from '../../../js/storefront/product-page.js';
import { addToCart, storeToasts, wishlistButton } from '../../../js/storefront/actions.js';
import furnivoHeader from './header.js';
import heroSlider from './hero-slider.js';
import carousel from './carousel.js';
import cartDrawer from './cart-drawer.js';
import customOrder from './custom-order.js';
import liveSearch from './live-search.js';
import variantPicker from './variant-picker.js';
import '../../../js/storefront/tour.js';

// Available as a global too, for markup that calls it directly.
window.liveSearch = liveSearch;
import checkoutForm from '../../../js/storefront/checkout.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('checkoutForm', checkoutForm);
    window.Alpine.data('productPage', productPage);
    window.Alpine.data('addToCart', addToCart);
    window.Alpine.data('wishlistButton', wishlistButton);
    window.Alpine.data('storeToasts', storeToasts);
    window.Alpine.data('furnivoHeader', furnivoHeader);
    window.Alpine.data('heroSlider', heroSlider);
    window.Alpine.data('carousel', carousel);
    window.Alpine.data('cartDrawer', cartDrawer);
    window.Alpine.data('customOrder', customOrder);
    window.Alpine.data('liveSearch', liveSearch);
    window.Alpine.data('variantPicker', variantPicker);
});
