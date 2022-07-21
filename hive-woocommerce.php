<?php
/*
Plugin Name: Hive WooCommerce
Plugin URI: https://github.com/anisur2805/hive-woocommerce
Description: Hive WooCommerce is a plugin that allows you to filter and paginate products on your WooCommerce store using Ajax.
Author: Anisur Rahman
Author URI: http://github.com/anisur2805/
Version: 1.0.0
Text Domain: hive-woocommerce
License: GPLv2 or later
 */

//Check if woocommerce is installed and activated.
if ( !function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    return;
}

//Enqueue the script and style.
add_action( 'wp_enqueue_scripts', 'hive_enqueue_scripts', 99 );
function hive_enqueue_scripts() {
    wp_enqueue_script( 'hive-woocommerce-script', plugins_url( '/assets/js/hive_woocommerce.js', __FILE__ ), array( 'jquery' ), time(), true );
    //localize script
    wp_localize_script( 'hive-woocommerce-script', 'hive_woocommerce_ajax_object', array(
        'ajax_url'               => admin_url( 'admin-ajax.php' ),
        'hive_woocommerce_nonce' => wp_create_nonce( 'hive_woocommerce_nonce' ),
        'paged'                  => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
    ) );
}

//Woocommerce ajax pagination for both logged in/ out user
add_action( 'wp_ajax_nopriv_hive_woocommerce_pagination', 'hive_woocommerce_pagination' );
add_action( 'wp_ajax_hive_woocommerce_pagination', 'hive_woocommerce_pagination' );
function hive_woocommerce_pagination() {
    //verify nonce
    check_ajax_referer( 'hive_woocommerce_nonce', 'security' );
    $paged = isset( $_POST['paged'] ) ? $_POST['paged'] : 1;
    $args  = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $paged,
    );

    $orderby_args = hive_woocommerce_get_orderby_args( $_POST['orderby'] );

    $args += $orderby_args;

    $loop = new WP_Query( $args );

    $payload['html'] = '';

    $payload['pagination'] = paginate_links( array(
        'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'total'        => $loop->max_num_pages,
        'current'      => max( 1, get_query_var( 'paged' ) ),
        'format'       => '?paged=%#%',
        'show_all'     => false,
        'type'         => 'plain',
        'prev_next'    => true,
        'prev_text'    => is_rtl() ? '&rarr;' : '&larr;',
        'next_text'    => is_rtl() ? '&larr;' : '&rarr;',
        'type'         => 'list',
        'end_size'     => 3,
        'mid_size'     => 3,
        'add_args'     => false,
        'add_fragment' => '',
    ) );
    ob_start();

    while ( $loop->have_posts() ): $loop->the_post();
        wc_get_template_part( 'content', 'product' );
    endwhile;
    $payload['html'] .= ob_get_clean();
    echo json_encode( $payload );
    wp_die();
}

function hive_woocommerce_get_orderby_args( $text ) {
    switch ( $text ) {
    case 'price':
        $args = array(
            'orderby'  => 'meta_value_num',
            'order'    => 'asc',
            'meta_key' => '_price',
        );
        break;
    case 'price-desc':
        $args = array(
            'orderby'  => 'meta_value_num',
            'order'    => 'desc',
            'meta_key' => '_price',
        );
        break;
    case 'rating':
        $args = array(
            'orderby'  => 'meta_value_num',
            'order'    => 'desc',
            'meta_key' => '_wc_average_rating',
        );
        break;
    case 'popularity':
        $args = array(
            'orderby'  => 'meta_value_num',
            'order'    => 'desc',
            'meta_key' => 'total_sales',
        );
        break;
    case 'date':
        $args = array(
            'order' => 'desc',
        );
        break;

    default:
        $args = array(
            'orderby' => 'date',
            'order'   => 'desc',
        );
        break;
    }

    return $args;
}

/**
 * Set discount for the user who purchases 3 products
 */
add_action( 'woocommerce_cart_calculate_fees', 'hive_woocommerce_add_discount_price' );
function hive_woocommerce_add_discount_price() {
    $items = WC()->cart->get_cart_contents_count();
    if ( $items < 3 ) {
        return;
    }

    $discount_price = floatval( WC()->cart->get_cart_contents_total() * 20 ) / 100;
    WC()->cart->add_fee( 'Discount', -$discount_price, true, 'standard' );
}
