<?php
// Ribbon gift customization — when a single ribbon is bought as a gift, the
// customer can pay to add 1-3 personal bonus messages, inserted at the start
// of week 1/2/3 (i.e. before day 1, day 8, day 15). Left/right text is
// captured for production; "See it on the ribbon" previews it in the 3D
// animation for this visitor only (the saved board_animation is shared by
// every buyer and never changes). Grouped and bundle products (2+ boards)
// are untouched — this only renders on a single ribbon's own page.

// Tiered pricing (store base currency): the 1st message is 5, the 2nd 4,
// the 3rd 3 — whichever weeks they land on. The total depends only on how
// many; the form labels each week with the tier it got, in ticking order.
define('LL_GIFT_MESSAGE_PRICES', [5, 4, 3]);
define('LL_GIFT_MAX_MESSAGES', 3);

// Total price for $count bonus messages, using the tiers above — in the
// store's base currency.
function LL_gift_messages_total_price($count)
{
    $count = max(0, min((int) $count, LL_GIFT_MAX_MESSAGES));
    return array_sum(array_slice(LL_GIFT_MESSAGE_PRICES, 0, $count));
}

// The tiers are base-currency amounts. WooCommerce Multilingual converts
// them to the visitor's currency, with its own exchange and rounding rules
// (the filter is a no-op when WCML isn't active or the visitor is on the
// base currency). The product price read in step 5 is already converted
// by WCML, so both halves of the gift price end up in the same currency.
function LL_gift_amount_in_client_currency($amount)
{
    return (float) apply_filters('wcml_raw_price_amount', $amount);
}

// "+€5" / "+$5.43" — plain text in the visitor's currency and price format.
function LL_gift_price_label($amount)
{
    $amount   = LL_gift_amount_in_client_currency($amount);
    $decimals = floor($amount) == $amount ? 0 : wc_get_price_decimals();
    $price    = wp_strip_all_tags(wc_price($amount, ['decimals' => $decimals]));
    return '+' . html_entity_decode($price, ENT_QUOTES, 'UTF-8');
}
define('LL_GIFT_MAX_LINES', 3);
define('LL_GIFT_MAX_LINE_LENGTH', 20);
define('LL_GIFT_FIELD_MAXLEN', (LL_GIFT_MAX_LINES * LL_GIFT_MAX_LINE_LENGTH) + (LL_GIFT_MAX_LINES - 1)); // + line breaks

// A ribbon board can only take 3 printed lines of 20 characters each.
// Enforced live in ribbon-gift.js (word-wrap); this just clamps anything
// that reaches PHP some other way, silently, no error needed.
function LL_clamp_gift_text($text)
{
    $lines = explode("\n", trim($text));
    $lines = array_slice($lines, 0, LL_GIFT_MAX_LINES);
    $lines = array_map(function ($line) {
        return mb_substr($line, 0, LL_GIFT_MAX_LINE_LENGTH);
    }, $lines);
    return trim(implode("\n", $lines));
}

/**
 * The gift form's ticked weeks, read from $_POST — the one parser shared by
 * validation, add-to-cart capture and "Save modification".
 * Returns ['messages' => [['week','left','right'], …], 'empty_week' => ?int];
 * empty_week is the first ticked week with no text on either side.
 */
function LL_gift_messages_from_post()
{
    $enabled = (isset($_POST['ll_week_enabled']) && is_array($_POST['ll_week_enabled'])) ? $_POST['ll_week_enabled'] : [];
    $raw     = (isset($_POST['ll_custom_msg']) && is_array($_POST['ll_custom_msg'])) ? $_POST['ll_custom_msg'] : [];

    $messages   = [];
    $empty_week = null;
    for ($week = 1; $week <= LL_GIFT_MAX_MESSAGES; $week++) {
        if (empty($enabled[$week])) {
            continue;
        }
        $side  = fn($key) => LL_clamp_gift_text(sanitize_textarea_field(wp_unslash($raw[$week][$key] ?? '')));
        $left  = $side('left');
        $right = $side('right');
        if ($left === '' && $right === '') {
            $empty_week = $empty_week ?? $week;
            continue;
        }
        $messages[] = ['week' => $week, 'left' => $left, 'right' => $right];
    }
    return ['messages' => $messages, 'empty_week' => $empty_week];
}

function LL_gift_empty_week_notice($week)
{
    wc_add_notice(sprintf('Please write a message for Week %d, or uncheck it.', $week), 'error');
}

function LL_is_giftable_ribbon_product($product)
{
    if (!$product instanceof WC_Product || !$product->is_type('simple')) {
        return false;
    }
    if (!function_exists('LL_get_group_board_animation_ids')) {
        return false;
    }
    // Exactly one board: gift messages go on a single ribbon. Bundles
    // (several ribbon pairs in one product) are not giftable.
    return count(LL_get_group_board_animation_ids($product->get_id())) === 1;
}

/**
 * If we arrived here from the cart (product link carries cart_item_key for
 * a gift-customized line), return that cart item so the form can be
 * pre-populated. Null otherwise.
 */
function LL_get_prefill_gift_data($product_id)
{
    if (empty($_GET['cart_item_key']) || !WC()->cart) {
        return null;
    }
    $key = sanitize_text_field(wp_unslash($_GET['cart_item_key']));
    if (!isset(WC()->cart->cart_contents[$key])) {
        return null;
    }
    $item = WC()->cart->cart_contents[$key];
    if ((int) $item['product_id'] !== (int) $product_id || empty($item['ll_is_gift'])) {
        return null;
    }
    return $item;
}

// Cart product-name link carries the cart item key for gift-customized lines,
// so landing back on the product page can pre-fill what was already entered.
add_filter('woocommerce_cart_item_permalink', 'LL_add_cart_item_key_to_gift_permalink', 10, 3);
function LL_add_cart_item_key_to_gift_permalink($permalink, $cart_item, $cart_item_key)
{
    if (empty($permalink) || empty($cart_item['ll_is_gift'])) {
        return $permalink;
    }
    return add_query_arg('cart_item_key', $cart_item_key, $permalink);
}

// The add-to-cart <form>'s action attribute defaults to the plain permalink,
// which drops the ?cart_item_key= query string the moment the form is
// submitted — so relying on it survives caching/redirect quirks unevenly.
// A hidden field inside the form itself is unconditional: it's part of the
// POST body regardless of what the action URL ends up being.
add_action('woocommerce_before_add_to_cart_button', 'LL_render_editing_cart_item_key_field', 5);
function LL_render_editing_cart_item_key_field()
{
    global $product;
    if (!$product || !LL_get_prefill_gift_data($product->get_id())) {
        return;
    }
    printf(
        '<input type="hidden" name="ll_editing_cart_item_key" value="%s">',
        esc_attr(sanitize_text_field(wp_unslash($_GET['cart_item_key'])))
    );
}

// "Add to cart" reads "Save modification" while editing an existing gift line.
add_filter('woocommerce_product_single_add_to_cart_text', 'LL_gift_edit_add_to_cart_text', 10, 2);
function LL_gift_edit_add_to_cart_text($text, $product)
{
    return LL_get_prefill_gift_data($product->get_id()) ? 'Save modification' : $text;
}

// A fresh gift add re-renders the SAME product page when "redirect to cart"
// is off in WooCommerce settings — and since that re-render isn't an edit
// (no cart_item_key yet), the form redraws blank, looking like the message
// got lost even though it's sitting in the cart correctly. Send them to the
// cart instead, same as "Save modification" already does, so it's visibly there.
add_filter('woocommerce_add_to_cart_redirect', 'LL_redirect_after_gift_add_to_cart');
function LL_redirect_after_gift_add_to_cart($url)
{
    return !empty($_POST['ll_is_gift']) ? wc_get_cart_url() : $url;
}

// Intercept that same submit before WooCommerce's own handler — WC_Form_Handler::add_to_cart_action()
// runs on wp_loaded priority 20, not template_redirect, so we have to beat it
// there or it adds a second cart line before we ever get a chance to run.
add_action('wp_loaded', 'LL_maybe_save_gift_modification', 10);
function LL_maybe_save_gift_modification()
{
    // Note: this runs on wp_loaded, before the main query — is_product()/global
    // $product aren't reliable here yet, so scoping relies only on the POST
    // values and the cart lookup below (same as WooCommerce's own handler does),
    // keyed off the hidden field rendered by LL_render_editing_cart_item_key_field()
    // above rather than $_GET, since that's guaranteed to be in the POST body
    // regardless of what the form's action URL turned out to be.
    if (empty($_POST['add-to-cart']) || empty($_POST['ll_editing_cart_item_key'])) {
        return;
    }

    $product_id    = absint($_POST['add-to-cart']);
    $cart_item_key = sanitize_text_field(wp_unslash($_POST['ll_editing_cart_item_key']));

    if (!WC()->cart || !isset(WC()->cart->cart_contents[$cart_item_key])) {
        return;
    }
    $existing = WC()->cart->cart_contents[$cart_item_key];
    if ((int) $existing['product_id'] !== $product_id || empty($existing['ll_is_gift'])) {
        return; // Not actually editing an existing gift line — let the normal add-to-cart run.
    }

    $edit_url = add_query_arg('cart_item_key', $cart_item_key, get_permalink($product_id));

    if (empty($_POST['ll_is_gift'])) {
        unset(WC()->cart->cart_contents[$cart_item_key]['ll_gift_customization']);
        unset(WC()->cart->cart_contents[$cart_item_key]['ll_is_gift']);
        WC()->cart->set_session();
        wc_add_notice('Gift customization removed.', 'success');
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }

    $parsed = LL_gift_messages_from_post();
    if ($parsed['empty_week']) {
        LL_gift_empty_week_notice($parsed['empty_week']);
        wp_safe_redirect($edit_url);
        exit;
    }
    $messages = $parsed['messages'];

    WC()->cart->cart_contents[$cart_item_key]['ll_is_gift'] = true;
    if (!empty($messages)) {
        WC()->cart->cart_contents[$cart_item_key]['ll_gift_customization'] = [
            'count'    => count($messages),
            'messages' => $messages,
        ];
    } else {
        unset(WC()->cart->cart_contents[$cart_item_key]['ll_gift_customization']);
    }
    WC()->cart->set_session();

    wc_add_notice('Your gift message has been updated.', 'success');
    wp_safe_redirect(wc_get_cart_url());
    exit;
}

// 1. Gift checkbox + message-count + left/right fields, above Add to cart / Confirm.
// The whole block is one accordion opened by "This is a gift"; each week is
// its own nested accordion. Pre-fills from an existing cart item when the
// customer arrived here via LL_add_cart_item_key_to_gift_permalink() above.
add_action('woocommerce_before_add_to_cart_button', 'LL_render_gift_customization_fields');
function LL_render_gift_customization_fields()
{
    global $product;
    if (!LL_is_giftable_ribbon_product($product)) {
        return;
    }

    $prefill   = LL_get_prefill_gift_data($product->get_id());
    $week_data = [];
    if ($prefill && !empty($prefill['ll_gift_customization']['messages'])) {
        foreach ($prefill['ll_gift_customization']['messages'] as $msg) {
            $week_data[(int) $msg['week']] = $msg;
        }
    }
    ?>
    <div class="ll-accordion ll-gift-accordion<?php echo $prefill ? ' is-open' : ''; ?>" id="ll-gift-accordion">
        <label class="ll-accordion-header">
            <input type="checkbox" class="ll-accordion-toggle" name="ll_is_gift" value="1" id="ll-is-gift" <?php checked((bool) $prefill); ?>>
            <span class="ll-accordion-title">This is a gift</span>
            <span class="ll-accordion-chevron" aria-hidden="true"></span>
        </label>

        <div class="ll-accordion-panel" id="ll-gift-options">
            <?php for ($week = 1; $week <= LL_GIFT_MAX_MESSAGES; $week++) :
                $msg = $week_data[$week] ?? null;
            ?>
            <div class="ll-accordion<?php echo $msg ? ' is-open' : ''; ?>" data-week="<?php echo esc_attr($week); ?>">
                <label class="ll-accordion-header">
                    <input type="checkbox" class="ll-accordion-toggle" name="ll_week_enabled[<?php echo esc_attr($week); ?>]" value="1" <?php checked((bool) $msg); ?>>
                    <span class="ll-accordion-title">Add a message — at the start of Week <?php echo esc_html($week); ?></span>
                    <span class="ll-accordion-price" data-week="<?php echo esc_attr($week); ?>"><?php echo esc_html(LL_gift_price_label(LL_GIFT_MESSAGE_PRICES[$week - 1])); ?></span>
                    <span class="ll-accordion-chevron" aria-hidden="true"></span>
                </label>
                <div class="ll-accordion-panel ll-accordion-fields">
                    <div class="ll-field-group">
                        <textarea name="ll_custom_msg[<?php echo esc_attr($week); ?>][left]" maxlength="<?php echo esc_attr(LL_GIFT_FIELD_MAXLEN); ?>" placeholder="Left side text" rows="<?php echo esc_attr(LL_GIFT_MAX_LINES); ?>" class="ll-field-input"><?php echo esc_textarea($msg['left'] ?? ''); ?></textarea>
                        <span class="ll-field-count" data-max="<?php echo esc_attr(LL_GIFT_FIELD_MAXLEN); ?>"><?php echo esc_html(mb_strlen($msg['left'] ?? '')); ?>/<?php echo esc_html(LL_GIFT_FIELD_MAXLEN); ?></span>
                    </div>
                    <div class="ll-field-group">
                        <textarea name="ll_custom_msg[<?php echo esc_attr($week); ?>][right]" maxlength="<?php echo esc_attr(LL_GIFT_FIELD_MAXLEN); ?>" placeholder="Right side text" rows="<?php echo esc_attr(LL_GIFT_MAX_LINES); ?>" class="ll-field-input"><?php echo esc_textarea($msg['right'] ?? ''); ?></textarea>
                        <span class="ll-field-count" data-max="<?php echo esc_attr(LL_GIFT_FIELD_MAXLEN); ?>"><?php echo esc_html(mb_strlen($msg['right'] ?? '')); ?>/<?php echo esc_html(LL_GIFT_FIELD_MAXLEN); ?></span>
                    </div>
                </div>
            </div>
            <?php endfor; ?>

            <p class="ll-gift-price-note" id="ll-gift-price-note"></p>

            <button type="button" class="ll-preview-ribbon-link" id="ll-preview-ribbon-btn">See it on the ribbon &rarr;</button>
        </div>
    </div>
    <?php
}

// 2. Enqueue the small show/hide script only where the block above renders.
add_action('wp_enqueue_scripts', 'LL_enqueue_gift_customization_script');
function LL_enqueue_gift_customization_script()
{
    if (!is_product()) {
        return;
    }
    global $product;
    if (!LL_is_giftable_ribbon_product($product)) {
        return;
    }
    wp_enqueue_script('ll-ribbon-gift');
    // Single source for the limits and prices the form enforces live.
    wp_add_inline_script('ll-ribbon-gift', 'window.LLGift = ' . wp_json_encode([
        'maxLines'      => LL_GIFT_MAX_LINES,
        'maxLineLength' => LL_GIFT_MAX_LINE_LENGTH,
        // Labels in the visitor's currency: per tier, and totals for 1..3 messages.
        'priceLabels'   => array_map('LL_gift_price_label', LL_GIFT_MESSAGE_PRICES),
        'totalLabels'   => array_map(
            fn($n) => LL_gift_price_label(LL_gift_messages_total_price($n)),
            range(1, LL_GIFT_MAX_MESSAGES)
        ),
    ]) . ';', 'before');
}

// 3. Validate on submit — every ticked week needs text on at least one side.
add_filter('woocommerce_add_to_cart_validation', 'LL_validate_gift_customization', 10, 2);
function LL_validate_gift_customization($passed, $product_id)
{
    if (empty($_POST['ll_is_gift'])) {
        return $passed;
    }

    $product = wc_get_product($product_id);
    if (!LL_is_giftable_ribbon_product($product)) {
        return $passed;
    }

    $parsed = LL_gift_messages_from_post();
    if ($parsed['empty_week']) {
        LL_gift_empty_week_notice($parsed['empty_week']);
        return false;
    }

    return $passed;
}

// 4. Capture onto the cart line.
add_filter('woocommerce_add_cart_item_data', 'LL_capture_gift_customization', 10, 2);
function LL_capture_gift_customization($cart_item_data, $product_id)
{
    if (empty($_POST['ll_is_gift'])) {
        return $cart_item_data;
    }

    $product = wc_get_product($product_id);
    if (!LL_is_giftable_ribbon_product($product)) {
        return $cart_item_data;
    }

    $cart_item_data['ll_is_gift'] = true;

    $messages = LL_gift_messages_from_post()['messages'];
    if (!empty($messages)) {
        $cart_item_data['ll_gift_customization'] = [
            'count'    => count($messages),
            'messages' => $messages,
        ];
    }

    // Distinct cart line per customization so identical products don't merge.
    $cart_item_data['unique_key'] = md5(microtime() . wp_rand());

    return $cart_item_data;
}

// 5. Price: base + tiered bonus-message total (LL_GIFT_MESSAGE_PRICES).
// Recomputed from the canonical price each time so it never compounds
// across repeated totals calculations.
add_action('woocommerce_before_calculate_totals', 'LL_apply_gift_customization_price', 20);
function LL_apply_gift_customization_price($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    foreach ($cart->get_cart() as $cart_item) {
        $count = $cart_item['ll_gift_customization']['count'] ?? 0;
        if ($count < 1 || empty($cart_item['data'])) {
            continue;
        }
        $base_price = (float) get_post_meta($cart_item['product_id'], '_price', true); // WCML: visitor's currency
        $cart_item['data']->set_price($base_price + LL_gift_amount_in_client_currency(LL_gift_messages_total_price($count)));
    }
}

// 6. Cart display.
add_filter('woocommerce_get_item_data', 'LL_display_gift_customization_item_data', 10, 2);
function LL_display_gift_customization_item_data($item_data, $cart_item)
{
    if (empty($cart_item['ll_is_gift'])) {
        return $item_data;
    }

    $count = $cart_item['ll_gift_customization']['count'] ?? 0;
    $item_data[] = [
        'key'   => 'Gift',
        'value' => $count > 0
            ? sprintf('%d personal message%s added (%s)', $count, $count > 1 ? 's' : '', LL_gift_price_label(LL_gift_messages_total_price($count)))
            : 'Marked as a gift',
    ];

    foreach (($cart_item['ll_gift_customization']['messages'] ?? []) as $msg) {
        $item_data[] = [
            'key'   => 'Week ' . (int) $msg['week'],
            'value' => $msg['left'] . ' / ' . $msg['right'],
        ];
    }

    return $item_data;
}

// 7. Carry through to the order so production has it.
add_action('woocommerce_checkout_create_order_line_item', 'LL_add_gift_customization_to_order_item', 10, 3);
function LL_add_gift_customization_to_order_item($item, $cart_item_key, $values)
{
    if (empty($values['ll_is_gift'])) {
        return;
    }
    $item->add_meta_data('Gift', 'yes');

    // Hidden structured copy for the PDF generator; the labelled rows below
    // are what the shop owner and customer see.
    $messages = $values['ll_gift_customization']['messages'] ?? [];
    $item->add_meta_data('_ll_gift_messages', $messages, true);

    foreach ($messages as $msg) {
        $item->add_meta_data('Week ' . (int) $msg['week'] . ' — left', $msg['left']);
        $item->add_meta_data('Week ' . (int) $msg['week'] . ' — right', $msg['right']);
    }
}

// ---------------------------------------------------------------
// 8. In-house production PDF — generated once the order is paid, never
// shown to the customer. Chris prints these and ships the physical
// ribbon. Two files per gift item (one per physical strand: L carries
// the customer's left-field text, R carries right-field), saved as
// underscore-prefixed order item meta ('_ll_ribbon_pdf_path_L' /
// '_ll_ribbon_pdf_path_R'), which WooCommerce automatically hides from
// order emails and the customer's My Account order view — no separate
// access control needed for that part, it's just never public.
// See ribbon-pdf.php for the actual rendering engine.
// ---------------------------------------------------------------

function LL_store_ribbon_pdf_paths($item, $paths)
{
    $item->update_meta_data('_ll_ribbon_pdf_path_L', $paths['L']);
    $item->update_meta_data('_ll_ribbon_pdf_path_R', $paths['R']);
    $item->save();
}

add_action('woocommerce_order_status_processing', 'LL_generate_ribbon_pdfs_for_order', 10, 1);
function LL_generate_ribbon_pdfs_for_order($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    foreach ($order->get_items() as $item) {
        if ($item->get_meta('_ll_ribbon_pdf_path_L', true) || $item->get_meta('_ll_ribbon_pdf_path_R', true)) {
            continue; // already generated — don't redo this on every status change
        }
        try {
            $paths = LL_generate_ribbon_pdf_for_order_item($order, $item);
        } catch (\Throwable $e) {
            // A PDF failure should never block order processing — log and move on;
            // the admin screen below offers a manual retry.
            error_log('Lullberry ribbon PDF generation failed for order ' . $order_id . ': ' . $e->getMessage());
            continue;
        }
        if ($paths) {
            LL_store_ribbon_pdf_paths($item, $paths);
        }
    }
}

// Admin-only "Ribbon PDF" links (L and R) on the order edit screen. Never
// rendered for the customer — gated on manage_woocommerce and only ever
// reachable from wp-admin. Offers to (re)generate on demand if the files
// are missing, e.g. after a server move.
add_action('woocommerce_admin_order_data_after_order_details', 'LL_render_admin_ribbon_pdf_links');
function LL_render_admin_ribbon_pdf_links($order)
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    foreach ($order->get_items() as $item_id => $item) {
        if ($item->get_meta('Gift', true) !== 'yes') {
            continue;
        }
        echo '<p><strong>' . esc_html($item->get_name()) . ':</strong> ';
        foreach (['L', 'R'] as $side) {
            $path   = $item->get_meta("_ll_ribbon_pdf_path_{$side}", true);
            $exists = $path && file_exists($path);
            $url = wp_nonce_url(
                admin_url("admin-post.php?action=ll_download_ribbon_pdf&order_id={$order->get_id()}&item_id={$item_id}&side={$side}"),
                "ll_download_ribbon_pdf_{$item_id}_{$side}"
            );
            printf(
                '<a href="%s" style="margin-right:1em;">%s %s</a>',
                esc_url($url),
                esc_html($side),
                $exists ? 'ribbon PDF' : '(generate)'
            );
        }
        echo '</p>';
    }
}

add_action('admin_post_ll_download_ribbon_pdf', 'LL_handle_admin_ribbon_pdf_download');
function LL_handle_admin_ribbon_pdf_download()
{
    // Mobile Chrome (and others) will happily cache a GET response -- including
    // a wp_die() failure page -- with no explicit cache headers, then keep
    // replaying that stale failure on retry even after the underlying bug is
    // fixed server-side. This is a debug/production link admins click
    // repeatedly while iterating, so force no-store unconditionally, before
    // any wp_die() or file output below.
    nocache_headers();

    if (!current_user_can('manage_woocommerce')) {
        wp_die('Not allowed.', '', ['response' => 403]);
    }
    $order_id = absint($_GET['order_id'] ?? 0);
    $item_id  = absint($_GET['item_id'] ?? 0);
    $side     = in_array($_GET['side'] ?? '', ['L', 'R'], true) ? $_GET['side'] : null;
    if (!$side) {
        wp_die('Missing or invalid side.', '', ['response' => 400]);
    }
    check_admin_referer("ll_download_ribbon_pdf_{$item_id}_{$side}");

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_die('Order not found.', '', ['response' => 404]);
    }
    $item = $order->get_item($item_id);
    if (!$item) {
        wp_die('Item not found.', '', ['response' => 404]);
    }

    $path = $item->get_meta("_ll_ribbon_pdf_path_{$side}", true);
    if (!$path || !file_exists($path)) {
        try {
            $paths = LL_generate_ribbon_pdf_for_order_item($order, $item);
        } catch (\Throwable $e) {
            wp_die('Could not generate PDF: ' . esc_html($e->getMessage()));
        }
        if (!$paths) {
            wp_die('This item has no gift customization to print.');
        }
        LL_store_ribbon_pdf_paths($item, $paths);
        $path = $paths[$side];
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
