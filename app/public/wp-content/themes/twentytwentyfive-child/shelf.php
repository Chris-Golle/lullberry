<?php
/* The Shelf: the books behind each ribbon series. No affiliate links.
 *
 * Books live on each 3D Ribbon (board_animation) post, meta `_ll_shelf`,
 * one per line:   Title | Author | Why I kept it | Days | Link
 * Only the title is required. Days: "3, 9, 17". Link is optional; without
 * it, "Find a copy" searches Google Books. Lines starting with # are skipped.
 *
 * [ll_shelf]              product page → its series; elsewhere → every series with books
 * [ll_shelf ids="12,34"]  those series only
 */

/* ── Admin: meta box ────────────────────────────────────────────── */

add_action('add_meta_boxes_board_animation', function () {
    add_meta_box('ll_shelf', 'Shelf: the books behind this ribbon', 'LL_shelf_meta_box', 'board_animation', 'normal');
});

function LL_shelf_meta_box($post)
{
    wp_nonce_field('ll_shelf_save', 'll_shelf_nonce');
    echo '<p>One book per line: <code>Title | Author | Why I kept it | Days | Link</code><br>'
       . 'Only the title is required. Days like <code>3, 9, 17</code>. Lines starting with <code>#</code> are ignored.</p>';
    echo '<textarea name="ll_shelf" rows="8" style="width:100%;font-family:monospace;">'
       . esc_textarea((string) get_post_meta($post->ID, '_ll_shelf', true))
       . '</textarea>';
}

add_action('save_post_board_animation', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['ll_shelf_nonce']) || !wp_verify_nonce($_POST['ll_shelf_nonce'], 'll_shelf_save')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $text = sanitize_textarea_field(wp_unslash($_POST['ll_shelf'] ?? ''));
    if ($text === '') {
        delete_post_meta($post_id, '_ll_shelf');
    } else {
        update_post_meta($post_id, '_ll_shelf', wp_slash($text)); // meta API unslashes
    }
});

/* ── Data ───────────────────────────────────────────────────────── */

function LL_shelf_books($board_id)
{
    $books = [];
    foreach (preg_split('/\R/', (string) get_post_meta($board_id, '_ll_shelf', true)) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $f = array_map('trim', explode('|', $line)) + array_fill(0, 5, '');
        if ($f[0] === '') continue;
        $books[] = ['title' => $f[0], 'author' => $f[1], 'why' => $f[2], 'days' => $f[3], 'link' => $f[4]];
    }
    return $books;
}

// "3, 9, 17" → "On days 3, 9 and 17"
function LL_shelf_days_label($days)
{
    $d = array_values(array_filter(
        array_map('intval', preg_split('/[^0-9]+/', (string) $days)),
        fn($n) => $n >= 1 && $n <= 21
    ));
    if (!$d) return '';
    if (count($d) === 1) return 'On day ' . $d[0];
    $last = array_pop($d);
    return 'On days ' . implode(', ', $d) . ' and ' . $last;
}

// Plain search links: region-aware on the reader's side, no tracking tags.
function LL_shelf_links($book)
{
    $q = rawurlencode(trim($book['title'] . ' ' . $book['author']));
    return apply_filters('ll_shelf_links', [
        'Find a copy' => $book['link'] ?: 'https://www.google.com/search?tbm=bks&q=' . $q,
        'Borrow it'   => 'https://search.worldcat.org/search?q=' . $q,
    ], $book);
}

/* ── Markup ─────────────────────────────────────────────────────── */

function LL_shelf_section($board_id)
{
    $post = get_post($board_id);
    if (!$post || $post->post_status !== 'publish') return '';
    $books = LL_shelf_books($board_id);
    if (!$books) return '';

    ob_start(); ?>
    <section class="ll-shelf" id="shelf-<?php echo esc_attr($post->post_name); ?>">
        <header class="ll-shelf-head">
            <p class="ll-eyebrow">The shelf</p>
            <h2 class="ll-shelf-title"><?php echo esc_html(get_the_title($post)); ?></h2>
            <p class="ll-shelf-intro">The books behind this ribbon. Each one earned its place.</p>
        </header>
        <ul class="ll-shelf-books">
            <?php foreach ($books as $b): $days = LL_shelf_days_label($b['days']); ?>
            <li class="ll-book">
                <h3 class="ll-book-title"><cite><?php echo esc_html($b['title']); ?></cite></h3>
                <?php if ($b['author']): ?><p class="ll-book-author">by <?php echo esc_html($b['author']); ?></p><?php endif; ?>
                <?php if ($b['why']): ?><p class="ll-book-why"><?php echo esc_html($b['why']); ?></p><?php endif; ?>
                <?php if ($days): ?><p class="ll-book-days"><?php echo esc_html($days); ?></p><?php endif; ?>
                <p class="ll-book-links">
                    <?php foreach (LL_shelf_links($b) as $label => $url): ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php echo esc_html($label); ?></a>
                    <?php endforeach; ?>
                </p>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}

add_shortcode('ll_shelf', function ($atts) {
    $atts = shortcode_atts(['ids' => ''], $atts, 'll_shelf');

    if ($atts['ids'] !== '') {
        $ids = wp_parse_id_list($atts['ids']);
    } elseif (function_exists('is_product') && is_product() && function_exists('LL_get_group_board_animation_ids')) {
        $ids = LL_get_group_board_animation_ids(get_queried_object_id());
    } else {
        $ids = get_posts([
            'post_type'   => 'board_animation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_key'    => '_ll_shelf',
            'orderby'     => ['menu_order' => 'ASC', 'title' => 'ASC'],
        ]);
    }

    $sections = array_filter(array_map('LL_shelf_section', $ids));
    if (!$sections) return '';

    // Several series (the QR page): a quiet index first.
    $nav = '';
    if (count($sections) > 1) {
        $nav = '<nav class="ll-shelf-nav" aria-label="Series">';
        foreach (array_keys($sections) as $k) {
            $p    = get_post($ids[$k]);
            $nav .= '<a class="ll-chip" href="#shelf-' . esc_attr($p->post_name) . '">' . esc_html(get_the_title($p)) . '</a>';
        }
        $nav .= '</nav>';
    }

    return '<div class="ll-shelves">' . $nav . implode('', $sections) . '</div>';
});
