// PurgeCSS config for Lullberry
// Run AFTER crawling lullberry.local — see README comment below.
//
// STEP 1 — crawl rendered HTML (run once, re-run when new pages are added):
//   wget --recursive --level=5 --no-clobber --html-extension \
//        --convert-links --domains lullberry.local --no-parent \
//        -P /tmp/lb-crawl http://lullberry.local/
//
// STEP 2 — analyse (Run the custom node script to get the rejected list):
//   node analyse-purge.js
//
// Outputs:
//   /tmp/lb-purge/style.css        → what would survive
//   /tmp/lb-purge/rejected.css     → what would be removed  ← the useful one

module.exports = {
  css: [
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/style.css',
  ],

  content: [
    // Rendered HTML from crawler
    '/tmp/lb-crawl/**/*.html',

    // JS files that inject class names at runtime
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/scripts.js',
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/quiz.js',
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/ribbon3d.js',
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/product-overlay.js',
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/iframe-cart-handler.js',

    // Re-enabled PHP: This catches classes defined in your shortcodes and ACF logic
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/**/*.php',
    
    // Catch any dynamically generated blocks in the theme
    '/Users/chris/Documents/Lullberry/app/public/wp-content/themes/twentytwentyfive-child/parts/**/*.html'
  ],

  output: '/tmp/lb-purge/',

  rejected: true,
  keyframes: true,    // Purge unused animations (lb-menu-in, lb-bounce, etc.)
  fontFace: true,     // Purge unused font-face declarations

  safelist: {
    standard: [
      'active',
      'is-menu-open',
      'has-modal-open',
      'lb-menu-closing',
      'modal-open',
      'show',
      'custom-logo',
      'lb-visible',
      'is-fading',
      'body-overlay-active', // Added for product overlay (from chris-js-scripts.js)
      'product-fullscreen-button', // Added for product overlay trigger (from chris-js-scripts.js)
      'sr-only', // Added for accessibility in personality-quiz.js
      'plus',   // Corrected: use single tokens for standard safelist
      'minus',  // Corrected
      'qty',    // Protect quantity input states
      'regions', // ID for the region switcher container
      'onsale', // WooCommerce "On Sale" badge
      'region', // ID for the region trigger link
      'change_region', // ID for the region popup
      'close-region', // ID for the close button
      'buttons_added', // WC quantity wrapper
      'posted_in', // WC meta
      'product_meta', // WC meta wrapper
      'lb-drawer-injected', // Injected by chris-js-scripts.js for mini-cart
    ],

    greedy: [
      // Lullberry Namespaces (essential for JS toggles and dynamic overlays)
      /^lb-/,                   // Protects .lb-drawer-upsell, .lb-mode-seed, etc.
      /^ll-/,                   // Protects .ll-zen-overlay, .ll-board, etc.
      /^cat-/,                  // Protects .cat-question, .cat-thumbnail (from functions.php)
      
      // Interaction Systems
      /^quiz-/,                 
      /^star-/,                 
      /^completion-/,           
      /^showing_/,              

      // WooCommerce & WordPress Block system
      // We use these because WC often injects state classes (e.g., .woocommerce-cart-form)
      /^wc-/,
      /^woocommerce-/,
      /^product-/,              
      /uagb-/,                  // Protect any class containing uagb (like .wp-block-uagb-container)

      // Board Animation
      /^animation-container/,
      /^sticky-wrapper/,
      /^scene/,
    ],
  },
};
