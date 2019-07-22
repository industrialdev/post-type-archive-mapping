<?php
require_once 'init.php';
/**
 * Renders the post grid block on server.
 */
function ptam_get_profile_image( $attributes, $post_thumb_id = 0, $post_author = 0, $post_id = 0 ) {
	ob_start();
	// Get the featured image
	$list_item_markup = '';

	if ( isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage'] ) {
		$post_thumb_size = $attributes['imageTypeSize'];
		$image_type = $attributes['imageType'];

		if( $image_type === 'gravatar' ) {
			$image = get_avatar( $post_author, $attributes['avatarSize'] );
		} else {
			$image = wp_get_attachment_image( $post_thumb_id, $post_thumb_size );
		}
		$list_item_markup .= sprintf(
			'<div class="ptam-block-post-grid-image" %3$s><a href="%1$s" rel="bookmark">%2$s</a></div>',
			esc_url( get_permalink( $post_id ) ),
			$image,
			'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['imageAlignment']}'" : ''
		);
		echo $list_item_markup;
	}
	return ob_get_clean();
}
function ptam_get_taxonomy_terms( $post, $attributes = array() ) {
	$markup = '';
	$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
	$terms = array();
	foreach( $taxonomies as $key => $taxonomy ) {
		$term_list = get_the_terms( $post->ID, $key );
		$term_array = array();
		if ( $term_list && ! empty( $term_list ) ) {
			foreach ( $term_list as $term ) {
				$term_permalink = get_term_link( $term, $key );
				$term_array[] = sprintf( '<a href="%s" style="color: %s; text-decoration: none; box-shadow: unset;">%s</a>', esc_url( $term_permalink ),  esc_attr( $attributes['linkColor'] ), esc_html( $term->name ) );
			}
			$terms[$key] = implode( ', ', $term_array );
		} else {
			$terms[$key] = false;
		}
	}
	foreach( $taxonomies as $key => $taxonomy ) {
		if ( false === $terms[$key] ) continue;
		$markup .= sprintf( '<div class="ptam-terms"><span class="ptam-term-label">%s: </span><span class="ptam-term-values">%s</span></div>', esc_html( $taxonomy->label ), $terms[$key] );
	}
	return $markup;
}

$id_counter = 0;
function ptam_filtering ( $attributes, &$post_args, $post_type) {
    if( empty( $attributes['displayFiltering'] ) || !$attributes['displayFiltering'] ) {
        return "";
    }

    global $id_counter;

    $taxonomies = get_object_taxonomies( $post_type, 'objects' );
    $showReset = false;

    $filter_block = "";
    $filter_block .= '<form method="get" class="grid grid--flex-md ptam-filter-form">';

    foreach( $attributes['filterableTaxonomies'] as $tax_name ) {
        $tax_formname = "ptam_" . $tax_name;
        $taxonomy = $taxonomies[$tax_name];
        $terms = get_terms(array(
            'taxonomy'=>$tax_name
        ));

        if(!empty(get_query_var($tax_formname))){
            $showReset = true;
            array_push($post_args['tax_query'],
                array('taxonomy' => $tax_name, 'terms' => (int)get_query_var($tax_formname), 'operator' => 'AND' ));
        }

        $filter_block .= '<div class="form__group grid__col">';
            $filter_block .= '<label for="select-3" class="form__label">';

            $filter_block .= __('Filter by') . " " . $taxonomy->labels->singular_name;
            $args = array(
                'taxonomy'  => $tax_name,
                'class'     => 'form__input',
                //'id'        => 'select-ptam-'.$id_counter++,
                'name'      => $tax_formname,
                'show_option_all' => "&nbsp;",
                'selected'  => get_query_var($tax_formname),
                'echo'      => false
            );
            $filter_block .= wp_dropdown_categories( $args );

            $filter_block .= '</label>';
        $filter_block .= '</div>';
    }

    $filter_block .= '<div class="form__group grid__col">';

        $filter_block .= '<label>&nbsp;';
        $filter_block .= '<input type="submit" value="Filter" class="button button--primary button--full-width"></label>';

        if ($showReset){

            $filter_block .= '
            <div style="text-align: center;">
                <a class="button button--small button--secondary button--full-width reset-form" href="'.get_the_permalink().'">Reset filters</a>
            </div>';

        }
    $filter_block .= '</div>';
    $filter_block .= '</form><br/>';
    return $filter_block;
}

function ptam_custom_posts( $attributes ) {

    global $post;
	$paged = 1;

	// only paginate block if we have pagination enabled
	if( !empty( $attributes['pagination'] ) && $attributes['pagination'] ) {
		$paged = max(1, get_query_var( 'paged' ));
	}

	$post_args = array(
		'post_type' => $attributes['postType'],
		'posts_per_page' => $attributes['postsToShow'],
		'post_status' => 'publish',
        'tax_query' => array(
            'relation' => 'AND',
        ),
        'meta_query' => array(
            'relation' => 'AND',
        ),
        'date_query' => array(
            'relation' => 'AND',
            'inclusive' => true,
        ),
		'order' => $attributes['order'],
		'orderby' => $attributes['orderBy'],
		'paged' => $paged,
        'post__not_in' => array ($post->ID)
	);

	if ( isset( $attributes['taxonomy']) && isset( $attributes['term'] ) ) {
		if( 'all' !== $attributes['term'] && 0 != $attributes['term'] && 'none' !== $attributes['taxonomy'] ) {
			$post_args[ 'tax_query' ] = array( array(
				'taxonomy' => $attributes['taxonomy'],
				'terms' => $attributes['term']
			) );
		}
	}

    $list_items_markup = '';

    $list_items_markup .= ptam_filtering($attributes, $post_args, $attributes['postType']);

	$image_placememt_options = $attributes['imageLocation'];
	$taxonomy_placement_options = $attributes['taxonomyLocation'];
	$image_size = $attributes['imageTypeSize'];
	$recent_posts = new WP_Query( $post_args );


	if( $recent_posts->have_posts()):
		while ( $recent_posts->have_posts() ) {
			global $post;
			$recent_posts->the_post();

			// Get the post ID
			$post_id = $post->ID;

			// Get the post thumbnail
			if ( 'gravatar' === $attributes['imageType'] ) {
				$post_thumb_id = 1;
			} else {
				$post_thumb_id = get_post_thumbnail_id( $post_id );
			}


			if ( $post_thumb_id && isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage'] ) {
				$post_thumb_class = 'has-thumb';
			} else {
				$post_thumb_class = 'no-thumb';
			}

			// Start the markup for the post
			$article_style = sprintf( 'border: %dpx solid %s;  background: %s; padding: %dpx; border-radius: %dpx;', absint( $attributes['border'] ), esc_attr( $attributes['borderColor'] ), esc_attr( $attributes['backgroundColor'] ), absint( $attributes['padding'] ), absint( $attributes['borderRounded'] ) );
			$list_items_markup .= sprintf(
				'<article class="%1$s" style="%2$s">',
				esc_attr( $post_thumb_class ),
				$article_style
			);
			if ( 'regular' === $image_placememt_options ) {
				$list_items_markup .= ptam_get_profile_image( $attributes, $post_thumb_id, $post->post_author, $post->ID );
			}

			// Wrap the text content
			$list_items_markup .= sprintf(
				'<div class="ptam-block-post-grid-text">'
			);

			// Get the post date
			if (!empty( $attributes['displayPostDateBefore'] )  &&
			    isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
				$list_items_markup .= sprintf(
					'<time datetime="%1$s" class="ptam-block-post-grid-date" style="color: %3$s;">%2$s</time>',
					esc_attr( get_the_date( 'c', $post_id ) ),
					esc_html( get_the_date( '', $post_id ) ),
                    esc_html($attributes['dateColor'])
				);
			}

				// Get the post title
				$title = get_the_title( $post_id );

				if ( ! $title ) {
					$title = __( 'Untitled' );
				}


				$list_items_markup .= sprintf(
					'<h2 class="ptam-block-post-grid-title" %3$s><a href="%1$s" rel="bookmark" style="color: %4$s; box-shadow: unset;">%2$s</a></h2>',
					esc_url( get_permalink( $post_id ) ),
					esc_html( $title ),
					'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['titleAlignment']}'" : '',
					esc_attr( $attributes['titleColor'] )
				);

				// Wrap the byline content
				$list_items_markup .= sprintf(
					'<div class="ptam-block-post-grid-byline %s" %s>', !empty($attributes['changeCapitilization']) ? 'ptam-text-lower-case' : '',
					'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['metaAlignment']}; color: {$attributes['contentColor']}'" : "style='color: {$attributes['contentColor']};'"

				);

					// Get the featured image
					if ( isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage']  && 'below_title' === $attributes['imageLocation']) {
						if ($post_thumb_id){
							$list_items_markup .= sprintf(
								'<div class="ptam-block-post-grid-image" %3$s><a href="%1$s" rel="bookmark">%2$s</a></div>',
								esc_url( get_permalink( $post_id ) ),
								ptam_get_profile_image( $attributes, $post_thumb_id, $post->post_author, $post->ID ),
								'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['imageAlignment']}" : ''
							);
						}else{
							$list_items_markup .= sprintf(
								'<div class="ptam-block-post-grid-image missing" %1$s></div>',
								'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['imageAlignment']}" : '');

						}
					}

					// Get the post author
					if ( isset( $attributes['displayPostAuthor'] ) && $attributes['displayPostAuthor'] ) {
						$list_items_markup .= sprintf(
							'<div class="ptam-block-post-grid-author"><a class="ptam-text-link" href="%2$s" style="color: %3$s">%1$s</a></div>',
							esc_html( get_the_author_meta( 'display_name', $post->post_author ) ),
							esc_html( get_author_posts_url( $post->post_author ) ),
							esc_attr( $attributes['linkColor'] )
						);
					}

                    // Get the post date
                    if (empty( $attributes['displayPostDateBefore'] )  &&
                        isset( $attributes['displayPostDate'] ) && $attributes['displayPostDate'] ) {
                        $list_items_markup .= sprintf(
                            '<time datetime="%1$s" class="ptam-block-post-grid-date" style="color: %3$s;">%2$s</time>',
                            esc_attr( get_the_date( 'c', $post_id ) ),
                            esc_html( get_the_date( '', $post_id ) ),
                            esc_html($attributes['dateColor'])
                        );
                    }

					// Get the taxonomies
					if ( isset( $attributes['displayTaxonomies'] ) && $attributes['displayTaxonomies'] && 'regular' === $taxonomy_placement_options ) {
						$list_items_markup .= ptam_get_taxonomy_terms( $post, $attributes );
					}
					// Get the featured image
					if ( isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage'] && 'below_title_and_meta' === $attributes['imageLocation']) {
						if ($post_thumb_id){
							$list_items_markup .= sprintf(
								'<div class="ptam-block-post-grid-image" %3$s><a href="%1$s" rel="bookmark">%2$s</a></div>',
								esc_url( get_permalink( $post_id ) ),
								ptam_get_profile_image( $attributes, $post_thumb_id, $post->post_author, $post->ID ),
								'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['imageAlignment']}" : ''
							);
						}else{
							$list_items_markup .= sprintf(
								'<div class="ptam-block-post-grid-image missing" %1$s></div>',
								'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['imageAlignment']}" : '');

						}
					}

				// Close the byline content
				$list_items_markup .= sprintf(
					'</div>'
				);

				// Wrap the excerpt content
				$list_items_markup .= sprintf(
					'<div class="ptam-block-post-grid-excerpt" %s>'
					, 'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['contentAlignment']}; color: {$attributes['contentColor']};'" : "style='color: {$attributes['contentColor']};'"
				);

					// Get the excerpt
					$excerpt = $post->post_excerpt;

					if( empty( $excerpt ) ) {
						$excerpt = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
					}

					if ( ! $excerpt ) {
						$excerpt = null;
					} else {
						$excerpt = wp_trim_words( apply_filters( 'the_excerpt', $excerpt ), isset( $attributes['trimWords'] ) ? $attributes['trimWords'] : 55 );
					}

					if ( isset( $attributes['displayPostExcerpt'] ) && $attributes['displayPostExcerpt'] ) {
						$list_items_markup .=  wp_kses_post( $excerpt );
					}

					// Get the featured image
					if ( isset( $attributes['displayPostImage'] ) && $attributes['displayPostImage'] && $post_thumb_id && 'bottom' === $attributes['imageLocation']) {
						if( $attributes['imageCrop'] === 'landscape' ) {
							$post_thumb_size = 'ptam-block-post-grid-landscape';
						} else {
							$post_thumb_size = 'ptam-block-post-grid-square';
						}

						$list_items_markup .= sprintf(
							'<div class="ptam-block-post-grid-image"><a href="%1$s" rel="bookmark">%2$s</a></div>',
							esc_url( get_permalink( $post_id ) ),
							ptam_get_profile_image( $attributes, $post_thumb_id, $post->post_author, $post->ID )
						);
					}

				// Close the excerpt content
				$list_items_markup .= sprintf(
					'</div>'
				);


                if ( isset( $attributes['displayPostLink'] ) && $attributes['displayPostLink'] ) {
                    $list_items_markup .= sprintf(
                        '<p><a class="ptam-block-post-grid-link ptam-text-link" href="%1$s" rel="bookmark" style="color: %3$s">%2$s</a></p>',
                        esc_url( get_permalink( $post_id ) ),
                        esc_html( $attributes['readMoreText'] ),
                        esc_attr( $attributes['continueReadingColor'] )
                    );
                }

				// Get the taxonomies
				if ( isset( $attributes['displayTaxonomies'] ) && $attributes['displayTaxonomies'] && 'below_content' === $taxonomy_placement_options ) {
					$list_items_markup .= sprintf( '<div %s>', 'grid' === $attributes['postLayout'] ? "style='text-align: {$attributes['metaAlignment']};color: {$attributes['contentColor']};'" : "style='color: {$attributes['contentColor']};'" );
					$list_items_markup .= ptam_get_taxonomy_terms( $post, $attributes );
					$list_items_markup .= '</div>';
				}

			// Wrap the text content
			$list_items_markup .= sprintf(
				'</div>'
			);

			// Close the markup for the post
			$list_items_markup .= "</article>\n";
		}
	endif;

	// Build the classes
	$class = "ptam-block-post-grid align{$attributes['align']}";

	if ( isset( $attributes['className'] ) ) {
		$class .= ' ' . $attributes['className'];
	}

	if ( !empty(  $attributes['displayPostImage'])){
		$class .= ' has-images';
	}

	$grid_class = 'ptam-post-grid-items';

	if ( isset( $attributes['postLayout'] ) ){
		if ('list' === $attributes['postLayout'] ) {
			$grid_class .= ' is-list';
		}elseif ('grid-list' === $attributes['postLayout'] ) {
			$grid_class .= ' is-grid-list';
		} else {
			$grid_class .= ' is-grid';
		}
	} else {
		$grid_class .= ' is-grid';
	}

	if ( isset( $attributes['columns'] ) && 'grid' === $attributes['postLayout'] ) {
		$grid_class .= ' columns-' . $attributes['columns'];
	}

	// Pagination
	$pagination = '';
	if( isset( $attributes['pagination'] ) && $attributes['pagination'] ) {
		 $args = array(
			'total'        => $recent_posts->max_num_pages,
			'current'      => max( 1, get_query_var( 'paged' ) ),
			'format'       => 'page/%#%',
			'show_all'     => false,
			'type'         => 'list',
			'end_size'     => 1,
			'mid_size'     => 2,
			'prev_next'    => false,
			'prev_text'    => sprintf( '<i></i> %1$s', __( 'Newer Items', 'post-type-archive-mapping' ) ),
			'next_text'    => sprintf( '%1$s <i></i>', __( 'Older Items', 'post-type-archive-mapping' ) ),
			'add_fragment' => '',
		 );

		 // TODO: Remove this specific override for sassquatch and allow for filters or something
		 if (function_exists('sassquatch_pagination')){
             $pagination = sassquatch_pagination($args);
         }else{
             $pagination = paginate_links($args);
         }

	}

	// Output the post markup
	$block_content = sprintf(
		'<div class="%1$s"><div class="%2$s">%3$s</div><div class="ptam-pagination">%4$s</div></div>',
		esc_attr( $class ),
		esc_attr( $grid_class ),
		$list_items_markup,
		$pagination
	);

	return $block_content;
}

/**
 * Registers the `core/latest-posts` block on server.
 */
function ptam_register_custom_posts_block()
{

    // Check if the register function exists
    if (!function_exists('register_block_type')) {
        return;
    }

    register_block_type('ptam/custom-posts', array(
        'attributes' => array(
            'postType' => array(
                'type' => 'string',
                'default' => 'post',
            ),
            'imageLocation' => array(
                'type' => 'string',
                'default' => 'regular'
            ),
            'changeCapitilization' => array(
                'type' => 'bool',
                'value' => false
            ),
            'imageSize' => array(
                'type' => 'string',
                'default' => 'ptam-block-post-grid-landscape'
            ),
            'imageTypeSize' => array(
                'type' => 'string',
                'default' => 'thumbnail',
            ),
            'imageType' => array(
                'type' => 'string',
                'default' => 'regular'
            ),
            'avatarSize' => array(
                'type' => 'int',
                'default' => 500
            ),
            'taxonomy' => array(
                'type' => 'string',
                'default' => 'category',
            ),
            'displayTaxonomies' => array(
                'type' => 'bool',
                'default' => true,
            ),
            'taxonomyLocation' => array(
                'type' => 'string',
                'default' => 'regular',
            ),
            'term' => array(
                'type' => 'int',
                'default' => 0,
            ),
            'terms' => array(
                'type' => 'string',
                'default' => 'all',
            ),
            'context' => array(
                'type' => 'string',
                'default' => 'view',
            ),
            'className' => array(
                'type' => 'string',
            ),
            'postsToShow' => array(
                'type' => 'number',
                'default' => 6,
            ),
            'pagination' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'displayPostDate' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'displayPostDateBefore' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'displayPostExcerpt' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'displayPostAuthor' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'displayPostImage' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'displayPostLink' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'postLayout' => array(
                'type' => 'string',
                'default' => 'grid',
            ),
            'columns' => array(
                'type' => 'number',
                'default' => 2,
            ),
            'align' => array(
                'type' => 'string',
                'default' => 'center',
            ),
            'width' => array(
                'type' => 'string',
                'default' => 'wide',
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'desc',
            ),
            'orderBy' => array(
                'type' => 'string',
                'default' => 'date',
            ),
            'imageCrop' => array(
                'type' => 'string',
                'default' => 'landscape',
            ),
            'readMoreText' => array(
                'type' => 'string',
                'default' => 'Continue Reading',
            ),
            'trimWords' => array(
                'type' => 'int',
                'default' => 55,
            ),
            'titleAlignment' => array(
                'type' => 'string',
                'default' => 'left',
            ),
            'imageAlignment' => array(
                'type' => 'string',
                'default' => 'left',
            ),
            'metaAlignment' => array(
                'type' => 'string',
                'default' => 'left',
            ),
            'contentAlignment' => array(
                'type' => 'string',
                'default' => 'left',
            ),
            'padding' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'border' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'borderRounded' => array(
                'type' => 'number',
                'default' => 0
            ),
            'borderColor' => array(
                'type' => 'string',
                'default' => '#000000',
            ),
            'backgroundColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            'titleColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            'linkColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            'contentColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            'dateColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            'continueReadingColor' => array(
                'type' => 'string',
                'default' => 'inherit',
            ),
            // filtering
            'displayFiltering' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'filterableTaxonomies' => array(
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
                'default' => []
            )
        ),
        'render_callback' => 'ptam_custom_posts',
        'editor_script' => 'ptam-custom-posts-gutenberg'
    ));
}

add_action( 'init', 'ptam_register_custom_posts_block' );


function ptam_register_query_vars( $vars ) {

    // add all public taxonomies as query vars
    $args = array(
        'public'   => true,
        '_builtin' => false
    );
    $output = 'names'; // or objects
    $operator = 'and'; // 'and' or 'or'
    $taxonomies = get_taxonomies( $args, $output, $operator );
    if ( $taxonomies ) {
        foreach ( $taxonomies  as $taxonomy ) {
            $vars[] = 'ptam_' . $taxonomy;
        }
    }
    return $vars;
}
add_filter( 'query_vars', 'ptam_register_query_vars' );

/**
 * Add image sizes
 */
function ptam_blocks_image_sizes() {
	// Post Grid Block
	add_image_size( 'ptam-block-post-grid-landscape', 600, 400, true );
	add_image_size( 'ptam-block-post-grid-square', 600, 600, true );
}
add_action( 'after_setup_theme', 'ptam_blocks_image_sizes' );