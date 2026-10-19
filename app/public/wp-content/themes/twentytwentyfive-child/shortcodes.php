<?php
/* Lullberry Custom Shortcodes
 * File: shortcodes.php */
 // Apparel category grid.

add_shortcode('apparel', function () {
    ob_start();

    $parent = get_term_by('slug', 'look-at-you', 'product_cat');
    $terms  = $parent ? get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent->term_id,
    ]) : [];
    if (empty($terms) || is_wp_error($terms)) {
        return ''; // nothing to show visitors; was debug text
    }

    echo '<div class="lullberry-cat-grid">';
    foreach ($terms as $term) {
        $slug = $term->slug;
        $bg   = content_url("uploads/{$slug}-enso.png");
        $icon = content_url("uploads/2021/06/{$slug}.svg");
        echo '
        <div class="lullberry-cat-item">
            <img class="cat-bg"   src="' . esc_url($bg)                      . '" alt="">
            <img class="cat-icon" src="' . esc_url($icon)                    . '" alt="">
            <a   class="cat-link" href="' . esc_url(get_term_link($term))    . '"></a>
        </div>';
    }
    echo '</div>';

    return ob_get_clean();
});

 // Product fullscreen button.
add_shortcode('product_fullscreen', function ($atts) {
    $atts = shortcode_atts(['product_id' => null, 'button_text' => 'View Fullscreen'], $atts, 'product_fullscreen');
    if (empty($atts['product_id'])) return '';
    $product = wc_get_product($atts['product_id']);
    if (!$product) return '';
    $relative_permalink = wp_make_link_relative(get_permalink($atts['product_id']));
    return '<button class="product-fullscreen-button" data-product-url="' . esc_url($relative_permalink) . '">' . esc_html($atts['button_text']) . '</button>';
});

 // Personality quiz.
function LL_personality_quiz_shortcode($atts) {
    static $quiz_modal_rendered = false;
    wp_enqueue_script('ll-personality-quiz');
    $atts = shortcode_atts([
        'questions' => "1. You go for a picnic. Is it: alone with the sound of birds, or with pets and friends?|||2. You start a summer job. Is it: counting the stars, or drawing new stars?|||3. You find a magic lamp. Does it: increase happiness, or decrease suffering?|||4. You move to a desert island. You befriend: a spirited lizard, or a sweet teddy bear?",
        'answers'   => "Alone with birds|With pets and friends|||Counting the stars|Drawing new stars|||Increase happiness|Decrease suffering|||A spirited lizard|A sweet teddy bear",
        'btn_text'  => __('Take the quiz', 'lullberry'),
    ], $atts);
    $output = sprintf(
        '<div class="ll-btn personality-quiz-trigger" data-questions="%1$s" data-answers="%2$s"><button class="start-quiz wp-block-button__link" type="button">%3$s</button></div>',
        esc_attr($atts['questions']),
        esc_attr($atts['answers']),
        esc_html($atts['btn_text'])
    );
    if (!$quiz_modal_rendered) {
        $quiz_inner  = '<button class="ll-x wp-element-button" type="button" aria-label="' . esc_attr__('Close quiz', 'lullberry') . '">&times;</button>';
        $quiz_inner .= '<div id="quiz-active" class="wp-block-group">';
        $quiz_inner .= '<div class="ll-panel">';
        $quiz_inner .= '<p class="ll-eyebrow"></p>';
        $quiz_inner .= '<h2 class="wp-block-heading quiz-qst ll-panel-text"></h2>';
        $quiz_inner .= '<div class="wp-block-buttons">';
        $quiz_inner .= '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"></a></div>';
        $quiz_inner .= '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"></a></div>';
        $quiz_inner .= '</div></div></div>';
        $quiz_inner .= '<div id="quiz-complete" class="wp-block-group">';
        $quiz_inner .= '<div class="ll-panel">';
        $quiz_inner .= '<h2 class="wp-block-heading">Reading your strengths in the stars</h2>';
        $quiz_inner .= '<div id="quiz-stars"></div>';
        $quiz_inner .= '<p class="has-text-align-center">stars counted</p>';
        $quiz_inner .= '<p class="has-text-align-center quiz-ready">Your results are ready!</p>';
        $quiz_inner .= '<div class="wp-block-buttons"><div class="wp-block-button quiz-ready">';
        $quiz_inner .= '<a class="wp-block-button__link has-text-align-center wp-element-button">View my strengths</a>';
        $quiz_inner .= '</div></div></div></div>';
        $output .= '<div id="quiz-modal" class="ll-quiz-modal" aria-hidden="true">' . $quiz_inner . '</div>';
        $quiz_modal_rendered = true;
    }
    return $output;
}
add_shortcode('personality_quiz', 'LL_personality_quiz_shortcode');

 // Product categories quiz.
function LL_product_categories_quiz_shortcode($atts) {
    $atts = shortcode_atts(['age' => 0], $atts);
    $age  = intval($atts['age']);

    $show_all = isset($_GET['all']);
    $has_quiz = isset($_GET['quiz']);
    if (!$has_quiz && !$show_all) return '';

    if ($age === 0) {
        $strengths = [
            '356'=>'3121','261'=>'2122','98'=>'3121','264'=>'3233','26'=>'3231',
            '113'=>'1121','100'=>'2322','101'=>'3313','102'=>'2321','103'=>'3233',
            '104'=>'1122','105'=>'2231','390'=>'3213','107'=>'2332','108'=>'2111',
            '88'=>'3332','110'=>'1131','115'=>'1332','116'=>'2312','106'=>'1131',
        ];
        $adjectives = [
            '356'=>'calm','261'=>'compassionate','98'=>'courageous','264'=>'creative',
            '26'=>'curious','113'=>'disciplined','100'=>'forgiving','101'=>'grateful',
            '102'=>'honest','103'=>'hopeful','104'=>'humble','105'=>'a little clown',
            '390'=>'joyful','107'=>'kind','108'=>'able to lead','88'=>'loving',
            '110'=>'eager to learn','115'=>'on a spiritual path','116'=>'part of a team',
            '106'=>'a seeker of wisdom',
        ];
        $parent_cat = '39'; $nb_cols = 3;
    } else {
        $strengths = [
            '798'=>'1323','796'=>'1331','795'=>'2333','797'=>'3123',
            '799'=>'3312','802'=>'3213','800'=>'3232','801'=>'3311',
        ];
        $adjectives = [
            '795'=>'kind','798'=>'calm','796'=>'grown up','802'=>'grateful',
            '799'=>'happy','797'=>'brave','801'=>'eager to learn','800'=>'loving',
        ];
        $parent_cat = '792'; $nb_cols = 3;
    }

    if ($show_all) {
        $matched_ids = array_keys($strengths);
    } else {
        $quiz_results = preg_replace('/[^0-9]/', '', wp_unslash($_GET['quiz']));
        $matched_ids  = [];
        foreach ($strengths as $id => $pattern) {
            $match = true;
            for ($i = 0; $i < 4; $i++) {
                if ((int)substr($pattern,$i,1) * (int)substr($quiz_results,$i,1) === 2) { $match = false; break; }
            }
            if ($match) $matched_ids[] = $id;
        }
    }

    if (empty($matched_ids)) return '<img class="ll-quiz-empty" src="' . esc_url(content_url('uploads/box.png')) . '" alt="">';

    $ids_list = implode(',', $matched_ids);
    $out = '';

    if ($show_all) {
        $class = $age === 1 ? 'showing_main_cats_kids' : 'showing_main_cats';
        return '<div class="' . $class . '">' . do_shortcode('[product_categories parent="' . $parent_cat . '" hide_empty="0" columns="' . $nb_cols . '"]') . '</div>';
    }

    if ($age === 1) {
        foreach ($matched_ids as $sid) {
            $out .= '<div class="showing_main_cats_kids">' . do_shortcode('[product_categories ids="' . $sid . '" hide_empty="0"]') . '</div>';
            $out .= '<div class="showing_subcats_kids">' . do_shortcode('[product_categories parent="' . $sid . '" hide_empty="0" columns="' . $nb_cols . '"]') . '</div>';
        }
    } else {
        $nb_cols = min(3, count($matched_ids));
        $out .= '<div class="showing_main_cats">' . do_shortcode('[product_categories ids="' . $ids_list . '" hide_empty="0" columns="' . $nb_cols . '"]') . '</div>';
    }

    $words = [];
    foreach ($matched_ids as $id) {
        if (isset($adjectives[$id])) $words[] = $adjectives[$id];
    }
    if (!empty($words)) {
        // Picked up by the typewriter in scripts.js (no inline script).
        $out .= '<div class="ll-typewriter-words" hidden data-words="' . esc_attr(wp_json_encode(array_values($words))) . '"></div>';
    }

    return $out;
}
add_shortcode('product_categories_quiz', 'LL_product_categories_quiz_shortcode');


// Strength description for Look at You category pages.
add_shortcode( 'll_strength_description', function () {
    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) return '';

    $tid   = 'product_cat_' . $term->term_id;
    $etym  = get_field( 'cat_etymology', $tid );
    $quote = get_field( 'cat_quote',     $tid );
    $qs    = get_field( 'cat_questions', $tid );
    $traits = get_field( 'cat_traits',  $tid );

    if ( ! $etym && ! $quote && ! $qs && ! $traits ) return '';

    $icon = '/wp-content/uploads/2021/06/' . $term->slug . '.svg';
    $kses = [ 'span' => [ 'class' => [] ], 'em' => [], 'i' => [], 'cite' => [], 'strong' => [] ];

    $out = '<div class="ll-dict-header">'
         . '<img class="ll-dict-calligraphy" src="' . esc_url( $icon ) . '" alt="' . esc_attr( $term->name ) . '" onerror="this.style.display=&apos;none&apos;">'
         . '</div>';

    if ( $etym )
        $out .= '<div class="ll-dict-entry"><div class="ll-dict-etymology">' . wp_kses( $etym, $kses ) . '</div></div>';

    if ( $quote )
        $out .= '<blockquote class="ll-cat-quote ll-quote ll-card--quiet">' . wp_kses( $quote, $kses ) . '</blockquote>';

    if ( $qs ) {
        $lines = array_filter( array_map( 'trim', preg_split( '/<br\s*\/?>/i', $qs ) ) );
        $out  .= '<div class="ll-questions-grid">';
        foreach ( $lines as $q )
            $out .= '<div class="ll-question-card ll-card ll-card--quiet"><span class="ll-question-text">' . wp_kses( $q, $kses ) . '</span></div>';
        $out .= '</div>';
    }

    if ( $traits ) {
        $lines = array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $traits ) ) ) );
        $out  .= '<div class="ll-traits-block">'
               . '<p class="ll-cat-inspiration ll-bloom-text">Here\'s how this strength can show up in your life:</p>'
               . '<p class="ll-cat-inspiration ll-seed-text">If it\'s not one of your strengths, here\'s some inspiration:</p>'
               . '<div class="ll-cat-traits ll-chip-grid">';
        foreach ( $lines as $line )
            $out .= '<span class="ll-trait ll-chip">' . wp_kses( $line, $kses ) . '</span>';
        $out .= '</div></div>';
    }

    return $out;
} );
