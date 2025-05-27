<?php
/**
 * Plugin Name: Panolabo Simple Booking Plugin
 * Plugin URI:  https://panolabollc.com/labo/panolabo-simple-booking
 * Description: シンプルな予約機能（自動固定ページ／UIkit＋FullCalendar＋空きスロットAPI）
 * Version:     1.0.1
 * Author:      panolabollc.
 * Text Domain: panolabo-simple-booking-plugin
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 定数定義
if ( ! defined( 'PSBP_PREFIX'         ) ) define( 'PSBP_PREFIX',        'psbp' );
if ( ! defined( 'PSBP_POST_TYPE'      ) ) define( 'PSBP_POST_TYPE',     PSBP_PREFIX . '_booking' );
if ( ! defined( 'PSBP_REST_NAMESPACE' ) ) define( 'PSBP_REST_NAMESPACE','psbp/v1' );
if ( ! defined( 'PSBP_REST_BOOKINGS'  ) ) define( 'PSBP_REST_BOOKINGS', '/bookings' );
if ( ! defined( 'PSBP_REST_SLOTS'     ) ) define( 'PSBP_REST_SLOTS',    '/slots' );
if ( ! defined( 'PSBP_PAGE_SLUG'      ) ) define( 'PSBP_PAGE_SLUG',     'booking' );

// includes
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/class-settings.php';

// フック登録
add_action( 'init',                         'psbp_load_textdomain'       );
add_action( 'init',                         'psbp_register_post_type'    );
add_action( 'add_meta_boxes',               'psbp_add_meta_boxes'        );
add_action( 'save_post_' . PSBP_POST_TYPE,  'psbp_save_meta_box', 10, 2 );
add_action( 'init',                         'psbp_handle_form_submission' );
add_action( 'rest_api_init',                'psbp_register_rest_routes'  );
add_shortcode( PSBP_PREFIX . '_booking_form',     'psbp_booking_form_shortcode'   );
add_shortcode( PSBP_PREFIX . '_booking_calendar', 'psbp_calendar_shortcode'       );
add_action( 'wp_enqueue_scripts',          'psbp_enqueue_assets'        );
// テーマテンプレートより優先して動作するようpriorityを1に変更
add_filter( 'template_include',             'psbp_override_template', 1 );
register_activation_hook(   __FILE__,       'psbp_activate_plugin'       );
register_deactivation_hook( __FILE__,       'psbp_deactivate_plugin'     );
add_action( 'admin_menu',                  [ 'PSBP_Settings', 'add_settings_page' ] );
add_action( 'admin_init',                  [ 'PSBP_Settings', 'register_settings' ] );
