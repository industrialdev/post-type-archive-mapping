<?php
require_once 'init.php';
/**
 * Renders the filter block on server.
 */
function ptam_filter_posts(){
    return "Filter block render callback";
}
function ptam_register_filter_block(){

    // Check if the register function exists
    if (!function_exists('register_block_type')) {
        return;
    }

    register_block_type( 'ptam/filter-posts', array(
        'attributes' => array(),
        'render_callback' => 'ptam_filter_posts',
        'editor_script'   => 'ptam-custom-posts-gutenberg' // this is the global script
    ));

}

add_action( 'init', 'ptam_register_filter_block' );