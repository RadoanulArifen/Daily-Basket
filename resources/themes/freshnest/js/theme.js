// FreshNest theme scripts. Alpine is bundled in from js/alpine.js (one copy per page).
// The shared store behaviour (add to cart, wishlist, toasts, product page) comes from the base
// storefront scripts; this file only adds what the FreshNest design needs on top.
import '../../../js/alpine.js';
import productPage from '../../../js/storefront/product-page.js';
import { addToCart, storeToasts, wishlistButton } from '../../../js/storefront/actions.js';
import cartDrawer from './cart-drawer.js';
import fnCountdown from './countdown.js';
import fnProduct from './product.js';
import '../../../js/storefront/tour.js';
import checkoutForm from '../../../js/storefront/checkout.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('checkoutForm', checkoutForm);
    window.Alpine.data('productPage', productPage);
    window.Alpine.data('addToCart', addToCart);
    window.Alpine.data('wishlistButton', wishlistButton);
    window.Alpine.data('storeToasts', storeToasts);
    window.Alpine.data('cartDrawer', cartDrawer);
    window.Alpine.data('fnCountdown', fnCountdown);
    window.Alpine.data('fnProduct', fnProduct);
});
