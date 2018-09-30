<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since 	1.0.0
 * @package Post Type Archive Mapping
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets for frontend and backend
 *
 * @since 1.0.0
 */
function ptam_blocks_block_assets() {
	
	// Load the compiled styles
	wp_enqueue_style(
		'ptam-style-css',
		PostTypeArchiveMapping::get_plugin_url( 'assets/dist/css/gutenberg.css'),
		array( 'wp-blocks' ),
		'20180927' );
} 
add_action( 'enqueue_block_assets', 'ptam_blocks_block_assets' );


/**
 * Enqueue assets for backend editor
 *
 * @since 1.0.0
 */
function ptam_blocks_editor_assets() {

	// Load the compiled blocks into the editor
	wp_enqueue_script(
		'ptam-custom-posts-gutenberg',
		PostTypeArchiveMapping::get_plugin_url( 'assets/dist/js/gutenberg.js'),
		array( 'wp-blocks', 'wp-element' )
	);

	// Pass in REST URL
	wp_localize_script(
		'ptam-custom-posts-gutenberg',
		'ptam_globals',
		array( 
			'rest_url' => esc_url( rest_url() )
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'ptam_blocks_editor_assets' );

/**
 * Return terms for taxonomy
 *
 * @since 1.0.0
 * @param WP_REST_Request $tax_data
 */
function ptam_get_all_terms($tax_data) {
	$taxonomy = $tax_data['taxonomy'];
	$post_type = $tax_data['post_type'];
	$terms = get_terms( array(
		'taxonomy' => $taxonomy,
		'hide_empty' => true,
		'post_type' => $post_type,
	) );
	if( is_wp_error( $terms ) ) {
		die( json_encode( array() ) );
	} else {
		die( json_encode( $terms ) );
	}
}

/**
 * Return Posts
 *
 * @since 1.0.0
 * @param WP_REST_Request $post_data
 */
function ptam_get_taxonomies($post_data) {
	$post_type = $post_data['post_type'];
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	die( json_encode( $taxonomies ) );
}

/**
 * Return Posts
 *
 * @since 1.0.0
 * @param WP_REST_Request $post_data
 */
function ptam_get_posts($post_data) {
	$taxonomy = $post_data['taxonomy'];
	$order = $post_data['order'];
	$orderby = $post_data['orderby'];
	$term = $post_data['term'];
	$post_type = $post_data['post_type'];
	$posts_per_page = $post_data['posts_per_page'];
	$image_crop = $post_data['image_crop'];
	
	$post_args = array(
		'post_type' => $post_type,
		'post_status' => 'publish',
		'order' => $order,
		'orderby' => $orderby,
		'posts_per_page' => $posts_per_page
	);
	if( 'all' !== $term && '0' !== $term && 'none' !== $taxonomy ) {
		$post_args[ 'tax_query' ] = array( array(
			'taxonomy' => $taxonomy,
			'terms' => $term
		) );
	}
	$posts = get_posts( $post_args );

	foreach( $posts as &$post) {
		
		// Get thumbnail information
		$landscape = get_the_post_thumbnail_url( $post->ID, 'ptam-block-post-grid-landscape' );
		$square = get_the_post_thumbnail_url( $post->ID, 'ptam-block-post-grid-square' );
		
		$post->featured_image_src = $landscape;
		$post->featured_image_src_square = $square;
		
		// Get author information
		$display_name = get_the_author_meta( 'display_name', $post->post_author );
		$author_url = get_author_posts_url( $post->post_author );
		
		$post->author_info = new stdClass();
		$post->author_info->display_name = $display_name;
		$post->author_info->author_link = $author_url;
		
		$post->link = get_permalink( $post->ID );
	
	}
	die( json_encode( $posts ) );
}
/**
 * Register route for getting taxonomy terms
 *
 * @since 1.0.0
 */
function ptam_register_route() {
	register_rest_route('ptam/v1', '/get_terms/(?P<taxonomy>[-_a-zA-Z]+)/(?P<post_type>[-_a-zA-Z]+)', array(
		'methods' => 'GET',
		'callback' => 'ptam_get_all_terms',
	));
	register_rest_route('ptam/v1', '/get_posts/(?P<post_type>[-_a-zA-Z]+)/(?P<order>[a-zA-Z]+)/(?P<orderby>[a-zA-Z]+)/(?P<taxonomy>[-_a-zA-Z]+)/(?P<term>\d+)/(?P<posts_per_page>\d+)/(?P<image_crop>[-a-zA-Z]+)', array(
		'methods' => 'GET',
		'callback' => 'ptam_get_posts',
	));

	register_rest_route('ptam/v1', '/get_taxonomies/(?P<post_type>[-_a-zA-Z]+)', array(
		'methods' => 'GET',
		'callback' => 'ptam_get_taxonomies',
	));
}
add_action('rest_api_init', 'ptam_register_route' );

/**
 * Extend get terms with post type parameter.
 *
 * @global $wpdb
 * @param string $clauses
 * @param string $taxonomy
 * @param array $args
 * @return string
 */
function ptam_terms_clauses( $clauses, $taxonomy, $args ) {
	if ( isset( $args['post_type'] ) && ! empty( $args['post_type'] ) && $args['fields'] !== 'count' ) {
		global $wpdb;

		$post_types = array();

		if ( is_array( $args['post_type'] ) ) {
			foreach ( $args['post_type'] as $cpt ) {
				$post_types[] = "'" . $cpt . "'";
			}
		} else {
			$post_types[] = "'" . $args['post_type'] . "'";
		}

		if ( ! empty( $post_types ) ) {
			$clauses['fields'] = 'DISTINCT ' . str_replace( 'tt.*', 'tt.term_taxonomy_id, tt.taxonomy, tt.description, tt.parent', $clauses['fields'] ) . ', COUNT(p.post_type) AS count';
			$clauses['join'] .= ' LEFT JOIN ' . $wpdb->term_relationships . ' AS r ON r.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN ' . $wpdb->posts . ' AS p ON p.ID = r.object_id';
			$clauses['where'] .= ' AND (p.post_type IN (' . implode( ',', $post_types ) . ') OR p.post_type IS NULL)';
			$clauses['orderby'] = 'GROUP BY t.term_id ' . $clauses['orderby'];
		}
	}
	return $clauses;
}
// For Terms
add_filter( 'terms_clauses', 'ptam_terms_clauses', 10, 3 );
