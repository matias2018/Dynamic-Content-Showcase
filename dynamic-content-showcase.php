<?php
/**
 * Plugin Name:       Dynamic Content Showcase
 * Description:       A Gutenberg block to showcase posts, pages, or custom post types dynamically.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           0.1.1
 * Author:            Pedro Matias
 * Author URI:        https://pedromatias.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dcsb
 * Domain Path:       /languages
 *
 * @package           DCSB
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants
define( 'DCSB_VERSION', '0.1.1' );
define( 'DCSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DCSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DCSB_TEXT_DOMAIN', 'dcsb' );
define( 'DCSB_BLOCK_SLUG', 'dynamic-content' );
define( 'DCSB_BLOCK_NAMESPACE', 'dcsb' );
define( 'DCSB_BLOCK_FULL_NAME', DCSB_BLOCK_NAMESPACE . '/' . DCSB_BLOCK_SLUG );

/**
 * Load plugin text domain for translations
 */
function dcsb_load_textdomain() { // Corrected prefix
    load_plugin_textdomain(
        DCSB_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'init', 'dcsb_load_textdomain' ); // Corrected prefix

/**
 * Register the block
 */
function dcsb_register_block() {
    register_block_type_from_metadata(
        DCSB_PLUGIN_DIR . 'build',
        [
            'render_callback' => 'dcsb_render_dynamic_content_block',
        ]
    );
}
add_action( 'init', 'dcsb_register_block' );

/**
 * Render the block on the server side
 *
 * @param array $attributes Block attributes.
 * @return string HTML content for the block content.
 */
function dcsb_render_dynamic_content_block( $attributes ) {
    // Ensure default values are set if attr are not provided
    $post_type        = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : 'post';
    $numberOfPosts    = isset( $attributes['numberOfPosts'] ) ? intval( $attributes['numberOfPosts'] ) : 3; // Corrected variable $attributes
    $orderBy          = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
    $order            = isset( $attributes['order'] ) ? strtoupper( sanitize_key( $attributes['order'] ) ) : 'DESC'; // strtoupper here
    $showExcerpt      = isset( $attributes['showExcerpt'] ) ? (bool) $attributes['showExcerpt'] : true;
    $showThumbnail    = isset( $attributes['showThumbnail'] ) ? (bool) $attributes['showThumbnail'] : false; // Default to false, user can enable
    $selectedTaxonomy = isset( $attributes['selectedTaxonomy'] ) ? sanitize_key( $attributes['selectedTaxonomy'] ) : '';
    $selectedTerms    = isset( $attributes['selectedTerms'] ) && is_array( $attributes['selectedTerms'] ) ? array_map( 'intval', $attributes['selectedTerms'] ) : [];


    // Validate the order
    if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
        $order = 'DESC'; // If invalid default to DESC
    }

    $query_args = [
        'post_type'      => $post_type,
        'posts_per_page' => $numberOfPosts,
        'orderby'        => $orderBy,
        'order'          => $order,
        'post_status'    => 'publish', // Added post_status
    ];

    // Add tax_query if a taxonomy and terms are selected
    if ( ! empty( $selectedTaxonomy ) && ! empty( $selectedTerms ) ) {
        $taxonomy_object = get_taxonomy( $selectedTaxonomy );
        if ( $taxonomy_object && is_object_in_taxonomy( $post_type, $selectedTaxonomy ) ) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => $selectedTaxonomy,
                    'field'    => 'term_id',
                    'terms'    => $selectedTerms,
                    'operator' => 'IN',
                ],
            ];
        }
    }

    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p>' . esc_html__( 'No posts found matching your criteria.', DCSB_TEXT_DOMAIN ) . '</p>';
    }

    $wrapper_attributes = get_block_wrapper_attributes( ['class' => 'dcsb-dynamic-content-showcase-wrapper' ] );
    $output = '<div ' . $wrapper_attributes . '>';
    $output .= '<ul class="dcsb-dynamic-content-list">';

    while ( $query->have_posts() ) {
        $query->the_post();
        $title     = get_the_title();
        $link      = get_permalink();
        $excerpt_content = get_the_excerpt();
        $thumbnail = $showThumbnail ? get_the_post_thumbnail( get_the_ID(), 'thumbnail' ) : ''; // Get ID explicitly

        $output .= '<li class="dcsb-list-item">';

        if ( ! empty( $thumbnail ) ) { // Show thumbnail first if it exists
            $output .= '<div class="dcsb-thumbnail"><a href="' . esc_url( $link ) . '">' . wp_kses_post( $thumbnail ) . '</a></div>';
        }

        $output .= '<div class="dcsb-content">'; // Wrapper for text content
        $output .= '<h3><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';

        if ( $showExcerpt ) {
            if ( ! empty( $excerpt_content ) ) {
                $output .= '<p class="dcsb-excerpt">' . esc_html( $excerpt_content ) . '</p>';
            } else { // Auto-generate excerpt if showExcerpt is true but no manual excerpt
                $content = get_the_content();
                $trimmed_excerpt = wp_trim_words( $content, 25, ' …' ); // Use … for ellipsis
                if(!empty($trimmed_excerpt)){
                    $output .= '<p class="dcsb-excerpt">' . esc_html( $trimmed_excerpt ) . '</p>';
                }
            }
        }
        $output .= '</div>'; // End dcsb-content
        $output .= '</li>';
    }
    $output .= '</ul>';
    $output .= '</div>';

    wp_reset_postdata();
    return $output;
}