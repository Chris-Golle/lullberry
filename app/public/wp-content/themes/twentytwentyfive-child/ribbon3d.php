<?php
 // 3D Ribbons Custom Post Type and Functions.
 
 // 1. Register Custom Post Type.
add_action('init', 'LL_register_3d_ribbons_post_type');
function LL_register_3d_ribbons_post_type() {
    register_post_type('board_animation', array(
        'labels' => array(
            'name' => '3D Ribbons',
            'singular_name' => '3D Ribbon',
            'add_new_item' => 'Add New 3D Ribbon',
            'edit_item' => 'Edit 3D Ribbon',
        ),
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-admin-customizer',
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'rewrite' => array('slug' => '3d-ribbons'),
    ));
}

 // 2. Meta Boxes.
add_action('add_meta_boxes', 'LL_add_3d_ribbons_meta_boxes');
function LL_add_3d_ribbons_meta_boxes() {
    add_meta_box(
        'board_messages',
        'Board Messages',
        'LL_board_messages_callback',
        'board_animation'
    );
}

function LL_board_messages_callback($post) {
    wp_nonce_field('board_messages_nonce', 'board_messages_nonce');

    $messages = get_post_meta($post->ID, '_board_messages', true);
    $text = '';

    // Convert array of messages back to quoted format for display
    if (is_array($messages) && !empty($messages)) {
        $text = implode(',', array_map(function($msg) {
            // Convert newline markers back to newlines for display
            $msg = str_replace('{{NEWLINE}}', "\n", $msg);
            return '"' . $msg . '"';
        }, $messages));
    }

    echo '<p><strong>Enter messages in quoted format, separated by commas.</strong></p>';
    echo '<p><strong>Format:</strong> "Message 1","Message 2","Message 3"</p>';
    echo '<textarea name="board_messages_text" rows="10" style="width:100%;">'
        . esc_textarea($text)
        . '</textarea>';
}

function LL_is_grouped_product_id($product_id)
{
    if (!$product_id || !function_exists('wc_get_product')) {
        return false;
    }
    $product = wc_get_product($product_id);
    return $product && $product->get_type() === 'grouped';
}

// Ribbon pairs 1–2 (a bundle shows both boards on one simple product).
// Both are defined here, in the existing "Board Animation" group —
// pair 1 reuses the original field's key and name, so saved values stay put.
// Local definitions win over the DB copy, so labels and order live in one place.
// Read by LL_get_group_board_animation_ids().
add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field')) {
        return;
    }
    $fields = [
        1 => ['field_6962c3b8717eb',       'board_animation'],
        2 => ['field_ll_board_animation_2', 'board_animation_2'],
    ];
    foreach ($fields as $n => [$key, $name]) {
        acf_add_local_field([
            'key'           => $key,
            'label'         => 'Ribbon pair ' . $n,
            'name'          => $name,
            'type'          => 'post_object',
            'parent'        => 'group_6962c3b69bf70',
            'post_type'     => ['board_animation'],
            'return_format' => 'id',
            'multiple'      => 0,
            'allow_null'    => 1,
            'ui'            => 1,
            'menu_order'    => $n,
        ]);
    }
});

add_filter('acf/prepare_field/name=board_animation', 'LL_hide_board_animation_field_for_grouped_products');
add_filter('acf/prepare_field/name=board_animation_2', 'LL_hide_board_animation_field_for_grouped_products');
function LL_hide_board_animation_field_for_grouped_products($field)
{
    if (!is_admin()) {
        return $field;
    }

    $post_id = 0;
    if (!empty($_GET['post'])) {
        $post_id = intval($_GET['post']);
    } elseif (!empty($_POST['post_ID'])) {
        $post_id = intval($_POST['post_ID']);
    }

    if ($post_id && LL_is_grouped_product_id($post_id)) {
        return false;
    }

    return $field;
}

function LL_board_animation_mark_rendered()
{
    $GLOBALS['ll_board_animation_rendered'] = true;
}

function LL_board_animation_is_rendered()
{
    return !empty($GLOBALS['ll_board_animation_rendered']);
}

 // 3. Save Meta Data.
add_action('save_post', 'LL_save_3d_ribbons_meta', 10, 2);
function LL_save_3d_ribbons_meta($post_id, $post) {

    if ($post->post_type !== 'board_animation') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Board Messages.
    if (
        !isset($_POST['board_messages_nonce']) ||
        !wp_verify_nonce($_POST['board_messages_nonce'], 'board_messages_nonce')
    ) {
        return;
    }

    if (isset($_POST['board_messages_text'])) {

        $raw_input = $_POST['board_messages_text'];
        $plain_text = wp_unslash($raw_input);
        $plain_text = html_entity_decode($plain_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $plain_text = str_replace('</p><p>', "\n\n", $plain_text);
        $plain_text = str_replace(array('<p>', '</p>'), '', $plain_text);
        $plain_text = str_replace(array('<br>', '<br/>', '<br />'), "\n", $plain_text);
        // Strip all HTML except <img> tags (used for image-only board messages).
        $plain_text = preg_replace('/<(?!\/?(img)\b)[^>]*>/i', '', $plain_text);
        $plain_text = str_replace('&nbsp;', ' ', $plain_text);
        $plain_text = str_replace(array("\r\n", "\r"), "\n", $plain_text);
        $plain_text = str_replace('\\n', "\n", $plain_text);

        $messages = array();
        
        // Clean up leading/trailing quotes and whitespace
        $plain_text = trim($plain_text);
        
        // Remove outer wrapping quotes if present (""""content"""" -> ""content"")
        if (preg_match('/^""+(.+?)""+$/s', $plain_text, $outer_match)) {
            $plain_text = $outer_match[1];
        }
        
        // Split by "," pattern (quote-comma-quote separator)
        $parts = preg_split('/"\s*,\s*"/', $plain_text);
        
        foreach ($parts as $part) {
            // Clean up any remaining quotes at start/end
            $part = trim($part, '" ');
            
            // Convert newlines to markers
            $part = str_replace("\n", "{{NEWLINE}}", $part);
            
            if (!empty($part)) {
                $messages[] = $part;
            }
        }

        // No de-duplication: messages are left/right pairs, so dropping a
        // repeated one would shift every later day onto the wrong side.
        update_post_meta($post_id, '_board_messages', $messages);
    }

}

/**
 * Append the ribbon animation after the product-details block on single
 * product pages (block themes don't fire the classic add-to-cart hooks).
 * Once per page; grouped products get one board per child (max 3).
 */
add_filter('render_block', 'LL_maybe_inject_board_animation_into_blocks', 20, 2);
function LL_maybe_inject_board_animation_into_blocks($block_content, $block)
{
    if (($block['blockName'] ?? '') !== 'woocommerce/product-details'
        || LL_board_animation_is_rendered()
        || is_admin() || !function_exists('is_product') || !is_product()) {
        return $block_content;
    }

    $board_ids = LL_get_group_board_animation_ids(get_the_ID());
    if (empty($board_ids)) {
        return $block_content;
    }

    LL_board_animation_mark_rendered();
    return $block_content . LL_board_animation_block($board_ids);
}

function LL_board_animation_block($board_ids)
{
    wp_enqueue_script('ll-3d-ribbons');
    return '<div class="ll-board-animation-block">' . LL_get_multi_animation_markup($board_ids) . '</div>';
}

 // 6. Multi-Animation (ribbon groups).

/**
 * Resolve a single board_animation field value to a published post ID.
 * Handles: numeric string, integer, WP_Post object, or ACF post-object array.
 */
function LL_resolve_board_animation_field($value) {
    if (empty($value)) return null;
    if (is_numeric($value))                         return intval($value);
    if (is_object($value) && isset($value->ID))     return intval($value->ID);
    if (is_array($value)  && isset($value['ID']))   return intval($value['ID']);
    return null;
}

/**
 * Read a board_animation post ID from one field of a product post
 * (ACF first, then raw post meta). Null unless the board is published.
 */
function LL_get_board_animation_id_for_post($post_id, $field = 'board_animation') {
    if (!$post_id) {
        return null;
    }
    $value = function_exists('get_field') ? get_field($field, $post_id) : null;
    if (empty($value)) {
        $value = get_post_meta($post_id, $field, true);
    }
    $id = LL_resolve_board_animation_field($value);
    return ($id && get_post_status($id) === 'publish') ? $id : null;
}

/* Up to 3 board_animation IDs for a product:
 *  - grouped product → each child's `board_animation`;
 *  - otherwise → board_animation / _2 on the product itself.
 * Cached per request: gift, PDF and injection code all ask repeatedly. */
function LL_get_group_board_animation_ids($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) return [];

    static $cache = [];
    if (isset($cache[$post_id])) return $cache[$post_id];

    if (LL_is_grouped_product_id($post_id)) {
        $children = array_slice(wc_get_product($post_id)->get_children(), 0, 3);
        $ids = array_map('LL_get_board_animation_id_for_post', $children);
    } else {
        $ids = array_map(
            fn($field) => LL_get_board_animation_id_for_post($post_id, $field),
            ['board_animation', 'board_animation_2']
        );
    }

    return $cache[$post_id] = array_slice(array_values(array_unique(array_filter($ids))), 0, 3);
}

/**
 * HTML for 1–3 stacked board pairs, driven by ribbon3d.js (class selectors,
 * not IDs). The satin gradient is defined once per pair and coloured from
 * the --wp--custom--satin--gold-* tokens in ribbon3d.css.
 */
function LL_get_multi_animation_markup($board_ids) {
    if (empty($board_ids)) return '';

    static $counter = 0;
    $uid   = 'mba' . (++$counter);
    $count = count($board_ids);

    ob_start();
    ?>
    <div class="ll-multi-animation-wrapper" id="ll-multi-<?php echo $uid; ?>" data-count="<?php echo $count; ?>">
        <div class="sticky-wrapper">
            <?php foreach ($board_ids as $i => $board_id):
                $messages = get_post_meta($board_id, '_board_messages', true);
                if (!is_array($messages)) $messages = [];
                $messages = array_map(fn($m) => str_replace('{{NEWLINE}}', "\n", $m), $messages);
                $gradient = 'satin-' . $uid . '-' . $i;
            ?>
            <div class="ll-multi-pair ll-anim-row" data-instance="<?php echo $i; ?>"
                 data-messages="<?php echo esc_attr(wp_json_encode($messages, JSON_UNESCAPED_UNICODE)); ?>">
                <?php foreach (['left' => 'll-face', 'right' => 'll-face-right'] as $side => $face): ?>
                <div class="scene scene-<?php echo $side; ?>">
                    <div class="board ll-board ll-board-<?php echo $side; ?>">
                        <div class="face front <?php echo $face; ?>-front"></div>
                        <div class="face back <?php echo $face; ?>-back"></div>
                    </div>
                    <div class="trapezoid-container">
                        <svg class="ll-trap-svg" width="222" height="333" viewBox="0 0 222 333" overflow="visible" aria-hidden="true">
                            <?php if ($side === 'left'): ?>
                            <defs>
                                <linearGradient id="<?php echo $gradient; ?>" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%"/><stop offset="50%"/><stop offset="100%"/>
                                </linearGradient>
                            </defs>
                            <?php endif; ?>
                            <polygon class="ll-trap-poly" points="55.5,0 166.5,0 222,333 0,333" fill="url(#<?php echo $gradient; ?>)"/>
                        </svg>
                    </div>
                </div>
                <?php endforeach; ?>
            </div><!-- .ll-multi-pair -->
            <?php endforeach; ?>
        </div><!-- .sticky-wrapper -->
    </div><!-- .ll-multi-animation-wrapper -->
    <?php
    return ob_get_clean();
}

/* Render singular board_animation posts without a separate template file.*/
add_action('template_redirect', 'LL_render_3d_ribbons_single', 0);
function LL_render_3d_ribbons_single()
{
    if (!is_singular('board_animation')) {
        return;
    }

    get_header();
    echo LL_board_animation_block([get_the_ID()]);
    get_footer();
    exit;
}
