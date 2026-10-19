<?php
/**
 * In-house production PDF for gift-customized ribbons.
 *
 * Reads real per-product content from the board_animation post's
 * _board_messages field (the same field the live 3D preview already
 * renders from) and splits it into two physical strands: even-index
 * messages are the L ribbon's instructional content, odd-index are the
 * R ribbon's quote/attribution -- confirmed against real data. Gift text
 * follows the same split: the customer's "left" field only ever lands on
 * the L ribbon, "right" only on R. Not customer-facing: Lullberry prints
 * these in-house and ships the physical satin ribbons.
 */

if (!defined('ABSPATH')) {
    exit; // no direct access
}

// ---------------------------------------------------------------
// Font setup -- tFPDF embeds the .ttf directly at runtime, no MakeFont step.
// Fonts live inside the theme itself (lib/font/unifont/, alongside
// ttfonts.php) so the theme folder is portable across environments --
// that's also where tFPDF caches the .mtx.php/.cw.dat metrics files it
// generates on first use.
// ---------------------------------------------------------------
if (!defined('_SYSTEM_TTFONTS')) {
    define('_SYSTEM_TTFONTS', get_stylesheet_directory() . '/lib/font/unifont/');
}
define('LL_RIBBON_FONT_REGULAR', 'Aleo-Medium.ttf');
define('LL_RIBBON_FONT_BOLD', 'Aleo-Bold.ttf');

define('LL_RIBBON_PDF_DIR', WP_CONTENT_DIR . '/uploads/ll-ribbon-pdfs');

// tFPDF must be loaded before LL_Ribbon_PDF can declare `extends tFPDF` --
// PHP resolves the parent class at declaration time, not lazily on first
// use. Loaded eagerly here (guarded by file_exists so a site that hasn't
// run the one-time installer yet doesn't fatal on every page load).
$__ll_tfpdf_path    = get_stylesheet_directory() . '/lib/tfpdf.php';
$__ll_ttfonts_path  = get_stylesheet_directory() . '/lib/font/unifont/ttfonts.php';
if (file_exists($__ll_tfpdf_path) && file_exists($__ll_ttfonts_path)) {
    require_once $__ll_ttfonts_path; // TTFontFile -- needed by AddFont() to
                                      // parse a raw .ttf at runtime
    require_once $__ll_tfpdf_path;
}
unset($__ll_tfpdf_path, $__ll_ttfonts_path);

if (class_exists('tFPDF')) :
class LL_Ribbon_PDF extends tFPDF
{
    // tFPDF/FPDF has no built-in text rotation; this is the standard
    // FPDF "Rotate" pattern (public-domain utility script from fpdf.org's
    // own script archive) -- emits a rotation matrix directly into the
    // content stream, then restores it. Used once, for the upside-down
    // "hang this side up" label at the very bottom of the ribbon.
    protected $ll_angle = 0;

    function RotatedText($x, $y, $txt, $angle)
    {
        $this->LL_Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->LL_Rotate(0);
    }

    protected function LL_Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if ($this->ll_angle != 0) {
            $this->_out('Q');
        }
        $this->ll_angle = $angle;
        if ($angle != 0) {
            $rad = $angle * M_PI / 180;
            $c = cos($rad);
            $s = sin($rad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
            ));
        }
    }

    // Centers text on cx (cm), baseline at y (cm) -- tFPDF's Text() only
    // does left-aligned placement at a literal x.
    function CenteredText($cx, $y, $txt)
    {
        $w = $this->GetStringWidth($txt);
        $this->Text($cx - $w / 2, $y, $txt);
    }
}
endif;

// ---------------------------------------------------------------
// LAYOUT CONSTANTS (cm)
// ---------------------------------------------------------------

define('LL_RIBBON_WIDTH_CM', 7.5);
define('LL_VISIBLE_AREA_HEIGHT_CM', 2.8);
define('LL_BOTTOM_LABEL_MARGIN_CM', 10.3);
define('LL_PRINT_AREA_MARGIN_CM', 3.0);

define('LL_SIZE_BODY', 9.5);
define('LL_SIZE_TITLE', 10.5);
define('LL_SIZE_GIFT', 9.5);
define('LL_SIZE_ATTRIBUTION', 8.0);

// Fixed distances (cm) from a box's own top edge -- read straight off the
// positions matrix -- down to each line. tFPDF's y grows DOWNWARD from
// the page top, so these are ADDED to move further into the box.
define('LL_TITLE_TOP_INSET_CM', 0.55);
define('LL_TITLE_LEADING_CM', 0.42);
define('LL_BODY_TOP_GAP_CM', 0.28);
define('LL_BODY_LEADING_CM', 0.40);
define('LL_MAX_DAYS', 21);

// Box top edges (cm from the printed top). Empirically calibrated per
// physical test print on this ribbon material -- not a formula. Re-verify
// with a real print after adding gift boxes.
const LL_BOX_UPPER_CM_TYVEK1057 = [
    3.546, 7.273711, 11.026624, 14.804409, 18.606736, 22.433275, 26.283696,
    30.157669, 34.054864, 37.974951, 41.9176, 45.882481, 49.869264,
    53.877619, 57.907216, 61.957725, 66.028816, 70.120159, 74.231424,
    78.362281, 82.5124, 88.5124,
];
const LL_PT_TO_CM = 1 / 28.346;

define('LL_BOX_BOTTOM_LABEL', "Hang this side up,\nfacing the wall");

/**
 * Reads the product's board_animation post (_board_messages meta -- the
 * same field LL_get_multi_animation_markup() already renders from, so this
 * stays consistent with the live 3D preview) and splits its message list
 * into two day-ordered sequences: even array-index entries are the L
 * ribbon's instructional content, odd are the R ribbon's quote/attribution
 * -- confirmed against real data, not assumed. Day count is whatever that
 * board actually has authored (not always 21), capped at LL_MAX_DAYS.
 *
 * Returns ['L'=>[...], 'R'=>[...], 'day_count'=>n] or null if the product
 * has no board, or that board has no messages yet.
 */
function LL_get_board_messages_for_product($product_id)
{
    if (!function_exists('LL_get_group_board_animation_ids')) {
        return null;
    }
    $board_ids = LL_get_group_board_animation_ids($product_id);
    if (empty($board_ids)) {
        return null;
    }
    $board_id = $board_ids[0];

    $messages = get_post_meta($board_id, '_board_messages', true);
    if (!is_array($messages) || empty($messages)) {
        return null;
    }
    $messages = array_map(fn($m) => str_replace('{{NEWLINE}}', "\n", $m), array_values($messages));

    $day_count = min((int) floor(count($messages) / 2), LL_MAX_DAYS);
    if ($day_count < 1) {
        return null;
    }

    $L = []; $R = [];
    for ($i = 0; $i < $day_count; $i++) {
        $L[] = $messages[$i * 2];
        $R[] = $messages[$i * 2 + 1];
    }
    return ['L' => $L, 'R' => $R, 'day_count' => $day_count, 'board_id' => $board_id];
}

// ---------------------------------------------------------------
// GIFT-INSERTION LOGIC
// ---------------------------------------------------------------

function LL_day_index_for_week($week)
{
    // Week 1 -> before day index 0 (day 1). Week 2 -> before index 7
    // (day 8). Week 3 -> before index 14 (day 15).
    return 7 * ($week - 1);
}

/**
 * $gifts: array of ['week'=>1|2|3, 'text'=>string] -- ONE text field, since
 * left-field messages only ever appear on the L ribbon and right-field
 * messages only on the R ribbon; each side's PDF only ever sees its own text.
 * $day_texts: that side's day-ordered content (from LL_get_board_messages_for_product).
 * Gifts whose target day doesn't exist on this board (day count too short)
 * are skipped, not fatal -- logged by the caller.
 */
function LL_build_box_sequence($gifts, $day_texts)
{
    $day_count = count($day_texts);
    $gifts_by_index = [];
    $skipped = [];
    foreach ($gifts as $g) {
        $week = (int) $g['week'];
        if (!in_array($week, [1, 2, 3], true)) {
            throw new InvalidArgumentException('week must be 1, 2, or 3');
        }
        $idx = LL_day_index_for_week($week);
        if ($idx >= $day_count) {
            $skipped[] = $week;
            continue;
        }
        $gifts_by_index[$idx] = $g;
    }

    $boxes = [];
    foreach ($day_texts as $i => $day_text) {
        if (isset($gifts_by_index[$i])) {
            $boxes[] = ['type' => 'gift', 'week' => (int) $gifts_by_index[$i]['week'], 'text' => $gifts_by_index[$i]['text'] ?? ''];
        }
        $boxes[] = ['type' => 'day', 'text' => $day_text];
    }
    return ['boxes' => $boxes, 'skipped_weeks' => $skipped, 'gift_indices' => array_keys($gifts_by_index)];
}

/**
 * Stretch the calibrated position table to make room for gift boxes,
 * inserted at the exact same day-indices LL_build_box_sequence() used.
 * A gift box takes over the day's OLD top position; that day and every
 * box after it shift down by exactly one local pitch (the gap this bit
 * of ribbon uses between its neighbors right there -- the calibration
 * table's pitch drifts slightly end to end, so "local" matters more than
 * a single ribbon-wide average).
 */
function LL_build_box_positions($gift_day_indices, $day_count, $base_positions = LL_BOX_UPPER_CM_TYVEK1057)
{
    $positions = array_slice($base_positions, 0, $day_count);
    sort($gift_day_indices);
    $offset = 0;
    foreach ($gift_day_indices as $day_idx) {
        if ($day_idx >= $day_count) {
            continue; // already reported by LL_build_box_sequence
        }
        $pos_idx = $day_idx + $offset;
        if ($pos_idx > 0) {
            $pitch = $positions[$pos_idx] - $positions[$pos_idx - 1];
        } else {
            $pitch = $positions[1] - $positions[0];
        }
        $gift_top = $positions[$pos_idx];
        for ($j = $pos_idx; $j < count($positions); $j++) {
            $positions[$j] += $pitch;
        }
        array_splice($positions, $pos_idx, 0, [$gift_top]);
        $offset++;
    }
    return $positions;
}

// ---------------------------------------------------------------
// PDF RENDERING
// ---------------------------------------------------------------

/**
 * Greedy word-wrap: breaks $text into lines that fit within $max_width_cm
 * at the pdf's CURRENT font/size. Needed because real board content is
 * flowing prose with no pre-wrapped breaks (unlike the old bands.jsx
 * content) -- without this, a long sentence runs straight off the edge
 * of a 7.5cm ribbon.
 */
function LL_wrap_text($pdf, $text, $max_width_cm)
{
    $lines = [];
    foreach (explode("\n", $text) as $paragraph) {
        $words = preg_split('/\s+/', trim($paragraph));
        $current = '';
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($current === '' || $pdf->GetStringWidth($candidate) <= $max_width_cm) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        $lines[] = $current;
    }
    return $lines;
}

/**
 * One day box. L content is "Title{blank line}instruction" (bold title);
 * R content is a quote, optionally "{blank line}attribution", and is never
 * bold -- its first paragraph renders at body size ($is_quote), otherwise a
 * break-less quote would be treated as one giant bold title.
 * Boxes sit at fixed, calibrated positions, so content must fit the box:
 * if wrapped lines overflow, line spacing shrinks (never type size) until
 * it fits, or a warning is logged.
 */
function LL_draw_day_box($pdf, $box_top_cm, $band_left_cm, $day_text, $is_quote = false)
{
    $parts = explode("\n\n", trim($day_text), 2);
    $title = trim($parts[0]);
    $body  = isset($parts[1]) ? trim($parts[1]) : '';
    $cx = $band_left_cm + LL_RIBBON_WIDTH_CM / 2;
    $max_w = LL_RIBBON_WIDTH_CM - 1.0;

    $title_style = $is_quote ? '' : 'B';
    $title_size  = $is_quote ? LL_SIZE_BODY : LL_SIZE_TITLE;
    $body_size   = $is_quote ? LL_SIZE_ATTRIBUTION : LL_SIZE_BODY;
    $has_body    = $body !== ''; // LL_wrap_text('') still returns [''],
                                  // not an empty array -- check the source
                                  // text directly instead

    $pdf->SetFont('Aleo', $title_style, $title_size);
    $title_lines = LL_wrap_text($pdf, $title, $max_w);
    $body_lines = [];
    if ($has_body) {
        $pdf->SetFont('Aleo', '', $body_size);
        $body_lines = LL_wrap_text($pdf, $body, $max_w);
    }

    $title_leading = $is_quote ? LL_BODY_LEADING_CM : LL_TITLE_LEADING_CM;
    $body_leading  = $is_quote ? (LL_BODY_LEADING_CM * 0.9) : LL_BODY_LEADING_CM;
    $gap = LL_BODY_TOP_GAP_CM;
    $available = LL_VISIBLE_AREA_HEIGHT_CM - LL_TITLE_TOP_INSET_CM - 0.15;
    $needed = 0;
    for ($shrink = 0; $shrink < 10; $shrink++) {
        $needed = max(0, count($title_lines) - 1) * $title_leading + $gap + count($body_lines) * $body_leading;
        if ($needed <= $available) {
            break;
        }
        $title_leading *= 0.92;
        $body_leading  *= 0.92;
        $gap *= 0.92;
    }
    if ($needed > $available) {
        error_log('Lullberry ribbon PDF: box still overflows after max shrink, consider shortening: ' . substr($title, 0, 40));
    }

    $y = $box_top_cm + LL_TITLE_TOP_INSET_CM;
    $pdf->SetFont('Aleo', $title_style, $title_size);
    foreach ($title_lines as $line) {
        $pdf->CenteredText($cx, $y, $line);
        $y += $title_leading;
    }
    if ($has_body) {
        $y += $gap;
        $pdf->SetFont('Aleo', '', $body_size);
        foreach ($body_lines as $line) {
            $pdf->CenteredText($cx, $y, $line);
            $y += $body_leading;
        }
    }
}

// Gift box now carries a single message (left-only or right-only, never
// both) -- customer input is already newline-clamped to 3 short lines by
// LL_clamp_gift_text() in ribbon-customize.php, so this trusts those
// breaks directly (like the old day-box body did) rather than wrapping.
function LL_draw_gift_box($pdf, $box_top_cm, $band_left_cm, $text)
{
    $cx = $band_left_cm + LL_RIBBON_WIDTH_CM / 2;
    $lines = explode("\n", trim($text));
    $pdf->SetFont('Aleo', '', LL_SIZE_GIFT);
    $block_h = (count($lines) - 1) * LL_BODY_LEADING_CM;
    $y = $box_top_cm + LL_VISIBLE_AREA_HEIGHT_CM / 2 - $block_h / 2;
    foreach ($lines as $line) {
        $pdf->CenteredText($cx, $y, $line);
        $y += LL_BODY_LEADING_CM;
    }
}

/**
 * Renders one physical ribbon strand (L or R). $day_texts: this side's
 * day-ordered content. $gifts: this side's own weeks only (['week','text']).
 */
function LL_generate_ribbon_side_pdf($output_path, $day_texts, $gifts, $side_label, $band_left_cm = 0.0)
{
    if (!class_exists('LL_Ribbon_PDF')) {
        throw new RuntimeException(
            'tFPDF is not installed -- run `php lib/install-tfpdf.php` once in the theme folder.'
        );
    }

    $seq = LL_build_box_sequence($gifts, $day_texts);
    $boxes = $seq['boxes'];
    if (!empty($seq['skipped_weeks'])) {
        error_log('Lullberry ribbon PDF: skipped gift week(s) ' . implode(',', $seq['skipped_weeks'])
            . " on side {$side_label} -- board doesn't have that many days of content.");
    }

    $positions = LL_build_box_positions($seq['gift_indices'], count($day_texts));

    $total_length_cm = end($positions) + LL_VISIBLE_AREA_HEIGHT_CM
        + LL_BOTTOM_LABEL_MARGIN_CM + LL_PRINT_AREA_MARGIN_CM;

    $pdf = new LL_Ribbon_PDF('P', 'cm', [LL_RIBBON_WIDTH_CM, $total_length_cm]);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddFont('Aleo', '', LL_RIBBON_FONT_REGULAR, true);
    $pdf->AddFont('Aleo', 'B', LL_RIBBON_FONT_BOLD, true);
    $pdf->AddPage();

    // Top label carries the side letter -- these print as two separate
    // physical pieces, easy to mix up at the press without a marker.
    $pdf->SetFont('Aleo', '', LL_SIZE_TITLE);
    $pdf->CenteredText($band_left_cm + LL_RIBBON_WIDTH_CM / 2, 1.5, "to begin  ({$side_label})");

    foreach ($boxes as $i => $box) {
        $top_cm = $positions[$i];
        if ($box['type'] === 'gift') {
            LL_draw_gift_box($pdf, $top_cm, $band_left_cm, $box['text']);
        } else {
            LL_draw_day_box($pdf, $top_cm, $band_left_cm, $box['text'], $side_label === 'R');
        }
    }

    // One separator hyphen at the exact midpoint of the gap between every
    // consecutive pair of boxes, read straight off the positions matrix.
    $pdf->SetFont('Aleo', '', LL_SIZE_BODY);
    $cx = $band_left_cm + LL_RIBBON_WIDTH_CM / 2;
    for ($i = 0; $i < count($positions) - 1; $i++) {
        $bottom_a = $positions[$i] + LL_VISIBLE_AREA_HEIGHT_CM;
        $gap_mid  = ($bottom_a + $positions[$i + 1]) / 2;
        $pdf->CenteredText($cx, $gap_mid, '-');
    }

    // Bottom label, rotated 180 so it reads right-side up once the ribbon
    // is folded/flipped for hanging. Each line rotates around its own
    // anchor point, which does NOT move -- only the glyph orientation
    // flips. So the line drawn at the smaller y (physically higher on
    // the un-rotated page) stays higher after rotation too; it just reads
    // upside-down until the ribbon itself is flipped. Feeding the lines
    // in FORWARD order therefore put "Hang this side up," above "facing
    // the wall" in this unrotated position -- backwards, since flipping
    // the printed ribbon 180 to read it swaps top and bottom. Reversed
    // here so the flipped, correctly-read order is "Hang this side up,"
    // then "facing the wall", top to bottom.
    $bottom_label_y = end($positions) + LL_VISIBLE_AREA_HEIGHT_CM + 1.0;
    $pdf->SetFont('Aleo', '', LL_SIZE_TITLE);
    foreach (array_reverse(explode("\n", LL_BOX_BOTTOM_LABEL)) as $i => $line) {
        $w = $pdf->GetStringWidth($line);
        $pdf->RotatedText(
            $band_left_cm + LL_RIBBON_WIDTH_CM / 2 + $w / 2,
            $bottom_label_y + ($i * 12 * LL_PT_TO_CM),
            $line,
            180
        );
    }

    $pdf->Output('F', $output_path);
    return $output_path;
}

/**
 * The PDFs carry customers' personal messages and live under uploads/, so
 * block direct web access (Apache) and directory listing (anything). Admins
 * download them through LL_handle_admin_ribbon_pdf_download() instead.
 */
function LL_ensure_private_pdf_dir()
{
    if (!file_exists(LL_RIBBON_PDF_DIR)) {
        wp_mkdir_p(LL_RIBBON_PDF_DIR);
    }
    $guards = [
        '.htaccess' => "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n",
        'index.php' => "<?php // Silence is golden.\n",
    ];
    foreach ($guards as $file => $contents) {
        if (!file_exists(LL_RIBBON_PDF_DIR . '/' . $file)) {
            file_put_contents(LL_RIBBON_PDF_DIR . '/' . $file, $contents);
        }
    }
}

// Protect PDFs generated before the guard existed, too.
add_action('admin_init', function () {
    if (is_dir(LL_RIBBON_PDF_DIR)) {
        LL_ensure_private_pdf_dir();
    }
});

/**
 * Reads Week N left/right meta off a paid order line item, fetches that
 * item's product's real board content, and generates BOTH physical ribbon
 * PDFs (L gets 'left' text, R gets 'right' text). Returns ['L'=>path,
 * 'R'=>path] (a side is still generated as a plain, non-gift ribbon if it
 * had no gift text -- the base tracker content always needs printing
 * regardless of gifting), or null if this item isn't a gift, or its
 * product has no usable board content.
 */
function LL_generate_ribbon_pdf_for_order_item($order, $item)
{
    if ($item->get_meta('Gift', true) !== 'yes') {
        return null;
    }

    $board = LL_get_board_messages_for_product($item->get_product_id());
    if (!$board) {
        error_log('Lullberry ribbon PDF: no board content for product ' . $item->get_product_id() . ' (order ' . $order->get_id() . ')');
        return null;
    }

    // Structured copy (orders from Sept 2026 on); older orders only have the
    // labelled "Week N — left/right" display rows.
    $messages = $item->get_meta('_ll_gift_messages', true);
    if (!is_array($messages) || !$messages) {
        $messages = [];
        for ($week = 1; $week <= 3; $week++) {
            $messages[] = [
                'week'  => $week,
                'left'  => (string) $item->get_meta("Week {$week} — left", true),
                'right' => (string) $item->get_meta("Week {$week} — right", true),
            ];
        }
    }
    $gifts_L = []; $gifts_R = [];
    foreach ($messages as $msg) {
        if (($msg['left'] ?? '') !== '') {
            $gifts_L[] = ['week' => (int) $msg['week'], 'text' => $msg['left']];
        }
        if (($msg['right'] ?? '') !== '') {
            $gifts_R[] = ['week' => (int) $msg['week'], 'text' => $msg['right']];
        }
    }

    LL_ensure_private_pdf_dir();

    // Unguessable, but stable per item so a regeneration overwrites in place.
    $token = substr(wp_hash($order->get_id() . '-' . $item->get_id(), 'nonce'), 0, 16);
    $base  = LL_RIBBON_PDF_DIR . '/' . $order->get_id() . '-' . $item->get_id() . '-' . $token;
    $path_L = LL_generate_ribbon_side_pdf($base . '-L.pdf', $board['L'], $gifts_L, 'L');
    $path_R = LL_generate_ribbon_side_pdf($base . '-R.pdf', $board['R'], $gifts_R, 'R');

    return ['L' => $path_L, 'R' => $path_R];
}
