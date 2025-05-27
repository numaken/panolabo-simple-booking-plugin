<?php
/**
 * Template Name: ご予約ページ
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header(); ?>
<div class="uk-container uk-margin-top">
  <?php echo do_shortcode( '[psbp_booking_calendar]' ); ?>
</div>
<?php get_footer(); ?>
