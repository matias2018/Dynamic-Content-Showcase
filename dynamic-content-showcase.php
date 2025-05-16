<?php
/**
 * Plugin Name:       Dynamic Content Showcase
 * Description:       A Gutenberg block to showcase posts, pages, or custom post types dynamically.
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Version:           0.1.1 // Increment version
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
define( 'DCSB_VERSION', '0.1.1' ); // Increment version
define( 'DCSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DCSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DCSB_TEXT_DOMAIN', 'dcsb' );
define( 'DCSB_BLOCK_SLUG', 'dynamic-content' ); // The part after the namespace
define( 'DCSB_BLOCK_NAMESPACE', 'dcsb' );       // The namespace
define( 'DCSB_BLOCK_FULL_NAME', DCSB_BLOCK_NAMESPACE . '/' . DCSB_BLOCK_SLUG );

/**
 * Load plugin text domain for translations
 */
function dscb_load_textdomain() {
    load_plugin_textdomain(
        DCSB_TEXT_DOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}

add_action( 'init', 'dscb_load_textdomain' );

/**
 * Register the block
 */
function dscb_register_block() {
    register_block_type_from_metadata(
        DCSB_PLUGIN_DIR . 'build', [
            'render_calback' => 'dscb_render_dynamic_content_block',
        ]
    );
}

add_action( 'init', 'dscb_register_block' );

/**
 * Render the block on the server side
 *
 * @param array $attributes Block attributes.
 * @return string HTML content for the block content.
 */
function dcsb_render_dynamic_content_block( $attributes ) {
    // Ensure default values are set if attr are not provided
    $post_type = isset( $attributes['postType'] ) ? sanitize_text_field( $attributes['postType'] ) : 'post';
    $numberOfPosts = isset( $sttributes['numberOfPosts'] ) ? intval( $attributes['numberOfPosts'] ) : 3;
    $orderBy = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date'; // sanitaize_key is better than sanitize_text_field for slugs/keys
    $order = isset( $attributes['order'] ) ? sanitize_key( $attributes['order'] ) : 'DESC';

    // Validate the order
    $order = strtoupper( $order );
    if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
        $order = 'DESC'; // If invalid default to DESC
    }

    $query_args = [
        'post_type'      => $post_type,
        'posts_per_page' => $numberOfPosts,
        'orderby'        => $orderBy,
        'order'          => $order,
    ];
    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        return '<p>' . esc_html__( 'No posts found matching your criteria.', DCSB_TEXT_DOMAIN ) . '</p>';
    }

    // Using a unique class for the wrapper for better targeting
    $wrapper_attributes = get_block_wrapper_attributes( ['class' => 'dcsb-dynamic-content-showcase-wrapper' ] );
    $output = '<div ' . $wrapper_attributes . '>';
    // Now the class 'dcsb-dynamic-content-showcase' can be inside or applied by get_block_wrapper_attributes - we need to configure in block.json
    $output .= '<ul class="dcsb-dynamic-content-list">';

    while ( $query->have_posts() ) {
        $query->the_post();
        $title = get_the_title();
        $link = get_permalink();
        $excerpt = get_the_excerpt();
        $thumbnail = get_the_post_thumbnail( null, 'thumbnail' );

        $output .= '<li class="dcsb-list-item">';
        $output .= '<h3><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';

        if ( ! empty( $excerpt ) && (isset($attributes['showExcerpt']) && $attributes['showExcerpt']) ) {
            $output .= '<p class="dcsb-excerpt">' . esc_html( $excerpt ) . '</p>';
        }
        if ( ! empty( $thumbnail ) && (isset($attributes['showThumbnail']) && $attributes['showThumbnail']) ) {
            $output .= '<div class="dcsb-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>';
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    $output .= '</div>';

    wp_reset_postdata();
    return $output;
}