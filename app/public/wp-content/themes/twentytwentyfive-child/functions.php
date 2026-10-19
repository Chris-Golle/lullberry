<?php

if (!defined('LL_TRACKER_ID')) define('LL_TRACKER_ID', 157457); // "Habit Tracker 21 Days" product

require_once get_stylesheet_directory() . '/shortcodes.php';
require_once get_stylesheet_directory() . '/geo-pricing.php';
require_once get_stylesheet_directory() . '/ribbon3d.php';
require_once get_stylesheet_directory() . '/ribbon-pdf.php';
require_once get_stylesheet_directory() . '/ribbon-customize.php';
require_once get_stylesheet_directory() . '/shelf.php';

/* ── Assets ─────────────────────────────────────────────────────────
   Registered here, enqueued by handle where needed. Versions follow the
   file's mtime so edits bust caches. */

function LL_asset_version($relative_path)
{
    $absolute_path = get_stylesheet_directory() . '/' . ltrim($relative_path, '/');
    return file_exists($absolute_path) ? filemtime($absolute_path) : null;
}

function LL_asset_url($relative_path)
{
    return get_stylesheet_directory_uri() . '/' . ltrim($relative_path, '/');
}

add_action('wp_enqueue_scripts', 'twentytwentyfive_child_enqueue_styles');
function twentytwentyfive_child_enqueue_styles()
{
    wp_enqueue_style('2025-child', LL_asset_url('style.css'), [], LL_asset_version('style.css'));
    wp_enqueue_style('ll-ribbon3d', LL_asset_url('ribbon3d.css'), ['2025-child'], LL_asset_version('ribbon3d.css'));
}

add_action('wp_enqueue_scripts', 'LL_register_theme_scripts', 5);
function LL_register_theme_scripts()
{
    $scripts = [
        'll-core'             => ['scripts.js',         ['jquery']],
        'll-personality-quiz' => ['quiz.js',            []],
        'll-3d-ribbons'       => ['ribbon3d.js',        []],
        'll-product-overlay'  => ['product-overlay.js', ['jquery']],
        'll-ribbon-gift'      => ['ribbon-gift.js',     []],
    ];
    foreach ($scripts as $handle => [$file, $deps]) {
        wp_register_script($handle, LL_asset_url($file), $deps, LL_asset_version($file), true);
    }
}

add_action('wp_enqueue_scripts', 'LL_enqueue_core_scripts');
function LL_enqueue_core_scripts()
{
    wp_enqueue_script('ll-core');
    wp_localize_script('ll-core', 'LLCart', [
        'trackerId' => LL_TRACKER_ID,
        'homeUrl'   => home_url('/'),
    ]);

    // Product overlay launcher: not needed on the product page itself.
    // (quiz.js enqueues itself from the [personality_quiz] shortcode.)
    if (!is_singular('product')) {
        wp_enqueue_script('ll-product-overlay');
    }
}

// Region overlay on every page, plus geo data for scripts.js.
add_action('wp_footer', function () {
    ob_start();
    block_template_part('region-overlay');
    echo do_shortcode(ob_get_clean());
    $region   = function_exists('get_user_geo_region') ? get_user_geo_region() : 'Europe';
    $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
    echo '<script>window.llRegion=' . wp_json_encode(['name' => $region, 'currency' => $currency]) . ';</script>';
}, 1);

// Remove MailerLite remote font stylesheets.
add_action('wp_print_styles', 'LL_dequeue_mailerlite_font_styles', 100);
function LL_dequeue_mailerlite_font_styles()
{
    $styles = wp_styles();
    foreach ((array) $styles->queue as $handle) {
        $src = isset($styles->registered[$handle]) ? (string) $styles->registered[$handle]->src : '';
        if (strpos($src, 'fonts.mailerlite.com/css') !== false || strpos($src, 'assets.mlcdn.com/fonts.css') !== false) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }
}

// Keep <span> tags in the classic editor.
add_filter('tiny_mce_before_init', 'LL_tinymce_keep_spans');
function LL_tinymce_keep_spans($init)
{
    $init['extended_valid_elements'] = 'span[*]';
    return $init;
}

/* ── Product categories ─────────────────────────────────────────── */

// Child categories use their parent's FSE template.
add_filter('template_include', function ($template) {
    if (!is_product_category()) return $template;
    $term = get_queried_object();
    if (!$term || !$term->parent) return $template;
    $parent = get_term($term->parent, 'product_cat');
    if (is_wp_error($parent)) return $template;
    $slug = 'taxonomy-product_cat-' . $parent->slug;
    return locate_block_template($template, $slug, [$slug]) ?: $template;
});

// Category pages: ACF fields replace the WC description, the FSE title block
// replaces WC's, and there's no result count / ordering.
add_filter('woocommerce_taxonomy_archive_description_raw', fn($description) => is_product_category() ? '' : $description);
add_filter('woocommerce_show_page_title', fn($show) => is_product_category() ? false : $show);
add_action('wp', function () {
    if (!is_product_category()) return;
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
});

/* ── Single product ─────────────────────────────────────────────── */

// ?overlay_mode=1 — the product page rendered inside the overlay iframe.
add_filter('body_class', function ($classes) {
    if (isset($_GET['overlay_mode']) && is_product()) {
        $classes[] = 'll-overlay-active';
    }
    return $classes;
});

// The core Post Excerpt block strips HTML and trims to ~55 words. On the
// product's own page, show the full short description with its formatting.
// Product cards elsewhere keep the short plain excerpt.
add_filter('render_block_core/post-excerpt', function ($content, $block, $instance) {
    $post_id = (int) ($instance->context['postId'] ?? get_the_ID());
    if (!is_product() || $post_id !== get_queried_object_id()) {
        return $content;
    }
    $excerpt = get_post_field('post_excerpt', $post_id);
    if (trim($excerpt) === '') {
        return $content;
    }
    $html = apply_filters('woocommerce_short_description', $excerpt);
    return preg_replace_callback(
        '#<p class="wp-block-post-excerpt__excerpt">.*?</p>#s',
        fn() => '<div class="wp-block-post-excerpt__excerpt">' . $html . '</div>',
        $content,
        1
    );
}, 10, 3);

// The tab label already says "Description".
add_filter('woocommerce_product_description_heading', '__return_false');

// No PhotoSwipe lightbox: no theme support, no scripts/styles, no footer markup
// (woocommerce/single-product/photoswipe.php is also an empty override).
add_filter('woocommerce_single_product_photoswipe_enabled', '__return_false');
add_action('after_setup_theme', fn() => remove_theme_support('wc-product-gallery-lightbox'), 20);
add_action('wp_enqueue_scripts', function () {
    foreach (['photoswipe', 'photoswipe-default-skin'] as $style) wp_dequeue_style($style);
    foreach (['photoswipe', 'photoswipe-ui-default', 'wc-photoswipe', 'wc-photoswipe-ui-default'] as $script) wp_dequeue_script($script);
    remove_action('wp_footer', 'woocommerce_photoswipe', 15);
}, 100);

/* ── Cart notice: tracker → ribbons ────────────────────────────── */

// After a tracker (a product showing 2+ ribbon pairs) is added, invite the
// customer to pick ribbons. Styled with the other notice buttons in style.css.
add_filter('wc_add_to_cart_message_html', 'LL_invite_ribbons_after_tracker', 10, 2);
function LL_invite_ribbons_after_tracker($message, $products)
{
    if (!function_exists('LL_get_group_board_animation_ids')) {
        return $message;
    }
    $has_tracker = false;
    foreach (array_keys((array) $products) as $product_id) {
        if (count(LL_get_group_board_animation_ids($product_id)) >= 2) {
            $has_tracker = true;
            break;
        }
    }
    if (!$has_tracker) {
        return $message;
    }
    $ribbons_url = home_url('/#ll-ribbons'); // ribbon section on the home page
    return $message . sprintf(
        '<span class="ll-notice-invite">Your tracker is ready. Choose the ribbons you&rsquo;d like to grow with. <a href="%s" class="button wc-forward ll-notice-ribbons">Choose ribbons</a></span>',
        esc_url($ribbons_url)
    );
}

/* ── Quiz ───────────────────────────────────────────────────────── */

// Inject quiz results into the #quiz-results placeholder.
add_filter('the_content', 'LL_render_quiz_results_placeholder', 20);
function LL_render_quiz_results_placeholder($content)
{
    if (is_admin() || !is_singular() || (!isset($_GET['quiz']) && !isset($_GET['all']))) {
        return $content;
    }
    $results_markup = do_shortcode('[product_categories_quiz]');
    if (trim($results_markup) === '') {
        return $content;
    }
    $pattern = '/<div([^>]*\bid=(["\'"])quiz-results\2[^>]*)>.*?<\/div>/is';
    return preg_replace($pattern, '<div$1>' . $results_markup . '</div>', $content, 1);
}

/* ── Cart: Curated Journey discount ─────────────────────────────────
   A roadmap product (one with upsells) bought together with its primary
   upsell (the apparel) gets N% off both, per matched pair. Upsell/cross-sell
   blocks are not displayed. */

add_action('init', function () {
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
    remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10);
}, 20);

function LL_get_primary_upsell_id($product)
{
    if (!$product instanceof WC_Product) {
        return 0;
    }
    $upsells = array_values(array_filter(array_map('absint', (array) $product->get_upsell_ids())));
    return $upsells[0] ?? 0;
}

add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if ((is_admin() && !defined('DOING_AJAX')) || !$cart || $cart->is_empty()) {
        return;
    }
    $discount_percent = (float) apply_filters('ll_curated_journey_discount_percent', 10.0);
    if ($discount_percent <= 0) {
        return;
    }

    // Quantity still available per product (for pairing) and its first cart line.
    $available  = [];
    $first_line = [];
    foreach ($cart->get_cart() as $line) {
        if (empty($line['data']) || !$line['data'] instanceof WC_Product) continue;
        $pid = (int) $line['product_id'];
        $available[$pid] = ($available[$pid] ?? 0) + (int) $line['quantity'];
        $first_line[$pid] = $first_line[$pid] ?? $line;
    }

    $unit = fn($line) => !empty($line['quantity']) ? (float) ($line['line_subtotal'] ?? 0) / (int) $line['quantity'] : 0.0;

    $eligible_total = 0.0;
    foreach ($cart->get_cart() as $line) {
        $upsell_id = LL_get_primary_upsell_id($line['data'] ?? null);
        if (!$upsell_id || empty($available[$upsell_id])) continue;

        $pairs = min(max(0, (int) $line['quantity']), $available[$upsell_id]);
        if ($pairs <= 0) continue;
        $available[$upsell_id] -= $pairs;

        $eligible_total += ($unit($line) + $unit($first_line[$upsell_id])) * $pairs;
    }

    $discount = round($eligible_total * $discount_percent / 100, wc_get_price_decimals());
    if ($discount > 0) {
        $cart->add_fee('Curated Journey Discount', -$discount, false);
    }
}, 30);

// Disable plugin update emails.
add_filter('auto_plugin_update_send_email', '__return_false');
