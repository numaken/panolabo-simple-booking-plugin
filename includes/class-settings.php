<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PSBP_Settings {
    const OPTION_KEY = 'psbp_settings';

    /**
     * メニューラベルをキーで返す
     */
    public static function get_label( $key ) {
        $labels = array(
            'slot_duration'     => __( '時間スロット長（分）',       'panolabo-simple-booking-plugin' ),
            'capacity_default'  => __( '予約確保可能数（１枠あたり）','panolabo-simple-booking-plugin' ),
            'opening_time'      => __( '営業時間開始 (HH:MM)',      'panolabo-simple-booking-plugin' ),
            'closing_time'      => __( '営業時間終了 (HH:MM)',      'panolabo-simple-booking-plugin' ),
            'min_guests'        => __( '最小人数',                  'panolabo-simple-booking-plugin' ),
            'max_guests'        => __( '最大人数',                  'panolabo-simple-booking-plugin' ),
            'services'          => __( 'メニュー（JSON配列）',       'panolabo-simple-booking-plugin' ),
            'mail_from_address' => __( '送信元メールアドレス',       'panolabo-simple-booking-plugin' ),
            'mail_from_name'    => __( '送信元表示名',              'panolabo-simple-booking-plugin' ),
            'notify_customer'   => __( 'お客様への確認メールを送信','panolabo-simple-booking-plugin' ),
        );
        return isset( $labels[ $key ] ) ? $labels[ $key ] : '';
    }

    public static function add_settings_page() {
        add_options_page(
            __( '予約プラグイン設定', 'panolabo-simple-booking-plugin' ),
            __( '予約設定',       'panolabo-simple-booking-plugin' ),
            'manage_options',
            'psbp-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'psbp_settings_group', self::OPTION_KEY, [ __CLASS__, 'sanitize_settings' ] );

        add_settings_section(
            'psbp_settings_section',
            __( '基本設定', 'panolabo-simple-booking-plugin' ),
            '__return_false',
            'psbp-settings'
        );

        // 各フィールドを get_label() でラベル指定
        add_settings_field( 'slot_duration',     self::get_label( 'slot_duration' ),     [ __CLASS__, 'field_slot_duration' ],     'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'capacity_default',  self::get_label( 'capacity_default' ),  [ __CLASS__, 'field_capacity_default' ],  'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'opening_time',      self::get_label( 'opening_time' ),      [ __CLASS__, 'field_opening_time' ],      'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'closing_time',      self::get_label( 'closing_time' ),      [ __CLASS__, 'field_closing_time' ],      'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'min_guests',        self::get_label( 'min_guests' ),        [ __CLASS__, 'field_min_guests' ],        'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'max_guests',        self::get_label( 'max_guests' ),        [ __CLASS__, 'field_max_guests' ],        'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'services',          self::get_label( 'services' ),          [ __CLASS__, 'field_services' ],          'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'mail_from_address', self::get_label( 'mail_from_address' ), [ __CLASS__, 'field_mail_from_address' ], 'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'mail_from_name',    self::get_label( 'mail_from_name' ),    [ __CLASS__, 'field_mail_from_name' ],    'psbp-settings', 'psbp_settings_section' );
        add_settings_field( 'notify_customer',   self::get_label( 'notify_customer' ),   [ __CLASS__, 'field_notify_customer' ],   'psbp-settings', 'psbp_settings_section' );
    }

    public static function sanitize_settings( $input ) {
        $out = [];
        $out['slot_duration']     = absint( $input['slot_duration'] );
        $out['capacity_default']  = absint( $input['capacity_default'] );
        $out['opening_time']      = sanitize_text_field( $input['opening_time'] );
        $out['closing_time']      = sanitize_text_field( $input['closing_time'] );
        $out['min_guests']        = absint( $input['min_guests'] );
        $out['max_guests']        = absint( $input['max_guests'] );
        $out['services']          = wp_json_encode( json_decode( $input['services'], true ) ?: [] );
        $out['mail_from_address'] = sanitize_email( $input['mail_from_address'] ?? '' );
        $out['mail_from_name']    = sanitize_text_field( $input['mail_from_name']    ?? '' );
        $out['notify_customer']   = ! empty( $input['notify_customer'] ) ? 1 : 0;
        return $out;
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( '予約プラグイン設定', 'panolabo-simple-booking-plugin' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'psbp_settings_group' );
                do_settings_sections( 'psbp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function field_slot_duration() {
        $v = psbp_get_setting( 'slot_duration', 15 );
        printf( '<input type="number" name="%1$s[slot_duration]" value="%2$d" min="1">', self::OPTION_KEY, $v );
    }
    public static function field_capacity_default() {
        $v = psbp_get_setting( 'capacity_default', 1 );
        printf( '<input type="number" name="%1$s[capacity_default]" value="%2$d" min="1">', self::OPTION_KEY, $v );
    }
    public static function field_opening_time() {
        $v = psbp_get_setting( 'opening_time', '09:00' );
        printf( '<input type="time" name="%1$s[opening_time]" value="%2$s">', self::OPTION_KEY, esc_attr( $v ) );
    }
    public static function field_closing_time() {
        $v = psbp_get_setting( 'closing_time', '18:00' );
        printf( '<input type="time" name="%1$s[closing_time]" value="%2$s">', self::OPTION_KEY, esc_attr( $v ) );
    }
    public static function field_min_guests() {
        $v = psbp_get_setting( 'min_guests', 1 );
        printf( '<input type="number" name="%1$s[min_guests]" value="%2$d" min="1">', self::OPTION_KEY, $v );
    }
    public static function field_max_guests() {
        $v = psbp_get_setting( 'max_guests', 1 );
        printf( '<input type="number" name="%1$s[max_guests]" value="%2$d" min="1">', self::OPTION_KEY, $v );
    }
    public static function field_services() {
        $json = json_decode( psbp_get_setting( 'services', '[]' ), true );
        $v    = wp_json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        printf( '<textarea name="%1$s[services]" rows="6" class="large-text code">%2$s</textarea>', self::OPTION_KEY, esc_textarea( $v ) );
        echo '<p class="description">例: [{"id":"cut","name":"カット","duration":30},…]</p>';
    }
    public static function field_mail_from_address() {
        $v = psbp_get_setting( 'mail_from_address', 'noreply@' . $_SERVER['SERVER_NAME'] );
        printf( '<input type="email" name="%1$s[mail_from_address]" value="%2$s" class="regular-text">', self::OPTION_KEY, esc_attr( $v ) );
    }
    public static function field_mail_from_name() {
        $v = psbp_get_setting( 'mail_from_name', get_bloginfo( 'name' ) );
        printf( '<input type="text" name="%1$s[mail_from_name]" value="%2$s" class="regular-text">', self::OPTION_KEY, esc_attr( $v ) );
    }
    public static function field_notify_customer() {
        $v = psbp_get_setting( 'notify_customer', 1 );
        printf(
            '<label><input type="checkbox" name="%1$s[notify_customer]" value="1" %2$s> %3$s</label>',
            self::OPTION_KEY,
            checked( 1, $v, false ),
            esc_html__( 'お客様に確認メールを送信', 'panolabo-simple-booking-plugin' )
        );
    }
}
