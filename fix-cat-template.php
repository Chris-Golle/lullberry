<?php
/**
 * Fix taxonomy-product_cat FSE template — correct the theme reference
 * so header and footer template parts actually render.
 * Run with: wp eval-file ~/Documents/Lullberry/fix-cat-template.php
 */

global $wpdb;

$rows = $wpdb->get_results(
    "SELECT ID, post_name, post_content FROM {$wpdb->posts}
     WHERE post_type = 'wp_template'
     AND post_status != 'trash'
     AND post_name LIKE '%taxonomy-product_cat%'"
);

if ( empty( $rows ) ) {
    echo "No taxonomy-product_cat template found.\n";
    exit;
}

foreach ( $rows as $row ) {
    echo "Found: ID={$row->ID} slug={$row->post_name}\n";

    $new_content = str_replace(
        [ 'twentytwentythree-child', 'twentytwentyfour-child', 'twentytwentythree' ],
        'twentytwentyfive-child',
        $row->post_content
    );

    // Also ensure the group has alignwide so content isn't too narrow
    $new_content = str_replace(
        '"layout":{"inherit":true,"type":"constrained"}',
        '"align":"wide","layout":{"inherit":true,"type":"constrained"}',
        $new_content
    );

    if ( $new_content === $row->post_content ) {
        echo "No changes needed — template already references correct theme.\n";
    } else {
        $wpdb->update(
            $wpdb->posts,
            [
                'post_content'      => $new_content,
                'post_modified'     => current_time( 'mysql' ),
                'post_modified_gmt' => current_time( 'mysql', 1 ),
            ],
            [ 'ID' => $row->ID ]
        );
        clean_post_cache( $row->ID );
        echo "Updated.\n";
    }
}

wp_cache_flush();
echo "Done.\n";
