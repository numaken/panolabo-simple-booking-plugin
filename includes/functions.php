<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** テキストドメイン読み込み */
function psbp_load_textdomain() {
    load_plugin_textdomain(
        'panolabo-simple-booking-plugin',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/../languages'
    );
}

/** 設定ヘルパー */
function psbp_get_setting( $key, $default = '' ) {
    $opts = get_option( 'psbp_settings', array() );
    return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
}

// メール送信元フィルター
add_filter( 'wp_mail_from',      'psbp_override_mail_from',      20 );
add_filter( 'wp_mail_from_name', 'psbp_override_mail_from_name', 20 );

/** override mail_from */
function psbp_override_mail_from( $original_email ) {
    $addr = psbp_get_setting( 'mail_from_address', '' );
    return is_email( $addr ) ? $addr : $original_email;
}

/** override mail_from_name */
function psbp_override_mail_from_name( $original_name ) {
    $name = psbp_get_setting( 'mail_from_name', '' );
    return $name !== '' ? $name : $original_name;
}

/** お客様通知メール送信 */
function psbp_send_customer_notification( $email, $name, $date, $time ) {
    if ( ! psbp_get_setting( 'notify_customer', 1 ) ) {
        return;
    }
    $subject = __( 'ご予約ありがとうございます', 'panolabo-simple-booking-plugin' );
    $message = sprintf(
        "%s: %s\n%s: %s %s\n\n%s",
        __( 'お名前', 'panolabo-simple-booking-plugin' ), $name,
        __( '日時',   'panolabo-simple-booking-plugin' ), $date, $time,
        __( '当日はお気をつけてお越しください。', 'panolabo-simple-booking-plugin' )
    );
    // wp_mail ではフィルターでFromが置換されるためヘッダー不要
    wp_mail( $email, $subject, $message );
}



/**
 * ── CPT登録 ───────────────────────────────────────
 */
// includes/functions.php より

if ( ! function_exists( 'psbp_register_post_type' ) ) {
    function psbp_register_post_type() {
        register_post_type( PSBP_POST_TYPE, array(
            'labels'             => array(
                'name'          => __( 'Bookings', 'panolabo-simple-booking-plugin' ),
                'singular_name' => __( 'Booking',  'panolabo-simple-booking-plugin' ),
                'menu_name'     => __( '予約管理',  'panolabo-simple-booking-plugin' ),
            ),
            'public'             => false,
            'show_ui'            => true,
            // 以下を追加 ↓
            'show_in_rest'       => true,
            'rest_base'          => PSBP_POST_TYPE,
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            // ↑ ここまで
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => array( 'title' ),
        ) );
    }
}

/**
 * ── メタボックス ───────────────────────────────────
 */
if ( ! function_exists( 'psbp_add_meta_boxes' ) ) {
    function psbp_add_meta_boxes() {
        add_meta_box(
            'psbp_details',
            __( '予約詳細', 'panolabo-simple-booking-plugin' ),
            'psbp_render_meta_box',
            PSBP_POST_TYPE
        );
    }
}

if ( ! function_exists( 'psbp_render_meta_box' ) ) {
    function psbp_render_meta_box( $post ) {
        wp_nonce_field( 'psbp_save_meta', 'psbp_meta_nonce' );
        $d = get_post_meta( $post->ID, PSBP_PREFIX . '_date',  true );
        $t = get_post_meta( $post->ID, PSBP_PREFIX . '_time',  true );
        $n = get_post_meta( $post->ID, PSBP_PREFIX . '_name',  true );
        $e = get_post_meta( $post->ID, PSBP_PREFIX . '_email', true );
        ?>
        <p><label><?php _e( '予約日', 'panolabo-simple-booking-plugin' ); ?><br>
          <input type="date" name="<?php echo PSBP_PREFIX;?>_date" value="<?php echo esc_attr( $d ); ?>" required>
        </label></p>
        <p><label><?php _e( '時間', 'panolabo-simple-booking-plugin' ); ?><br>
          <input type="time" name="<?php echo PSBP_PREFIX;?>_time" value="<?php echo esc_attr( $t ); ?>" required>
        </label></p>
        <p><label><?php _e( 'お名前', 'panolabo-simple-booking-plugin' ); ?><br>
          <input type="text" name="<?php echo PSBP_PREFIX;?>_name" value="<?php echo esc_attr( $n ); ?>" required>
        </label></p>
        <p><label><?php _e( 'メール', 'panolabo-simple-booking-plugin' ); ?><br>
          <input type="email" name="<?php echo PSBP_PREFIX;?>_email" value="<?php echo esc_attr( $e ); ?>" required>
        </label></p>
        <?php
    }
}

if ( ! function_exists( 'psbp_save_meta_box' ) ) {
    function psbp_save_meta_box( $post_id ) {
        if (
            ! isset( $_POST['psbp_meta_nonce'] ) ||
            ! wp_verify_nonce( $_POST['psbp_meta_nonce'], 'psbp_save_meta' )
        ) {
            return;
        }
        update_post_meta( $post_id, PSBP_PREFIX . '_date',  sanitize_text_field( $_POST[ PSBP_PREFIX . '_date' ] ) );
        update_post_meta( $post_id, PSBP_PREFIX . '_time',  sanitize_text_field( $_POST[ PSBP_PREFIX . '_time' ] ) );
        update_post_meta( $post_id, PSBP_PREFIX . '_name',  sanitize_text_field( $_POST[ PSBP_PREFIX . '_name' ] ) );
        update_post_meta( $post_id, PSBP_PREFIX . '_email', sanitize_email(      $_POST[ PSBP_PREFIX . '_email' ] ) );
    }
}

/**
 * ── フロントフォームショートコード ───────────────────
 */
if ( ! function_exists( 'psbp_booking_form_shortcode' ) ) {
    function psbp_booking_form_shortcode() {
        // 予約完了メッセージ
        if ( isset( $_GET[ PSBP_PREFIX . '_success' ] ) ) {
            return '<div class="uk-alert-success" uk-alert>'
                 . esc_html__( 'ご予約ありがとうございます！', 'panolabo-simple-booking-plugin' )
                 . '</div>';
        }

        // 日付プリフィル
        $prefill = isset( $_GET[ PSBP_PREFIX . '_date' ] )
                 ? esc_attr( $_GET[ PSBP_PREFIX . '_date' ] )
                 : '';

        ob_start();
        ?>
        <form method="post" class="uk-form-stacked">
          <?php wp_nonce_field( 'psbp_front_submit', 'psbp_front_nonce' ); ?>

          <div class="uk-margin">
            <label class="uk-form-label"><?php _e( '予約日', 'panolabo-simple-booking-plugin' ); ?></label>
            <div class="uk-form-controls">
              <input class="uk-input"
                     type="date"
                     name="<?php echo PSBP_PREFIX; ?>_date"
                     required
                     value="<?php echo $prefill; ?>">
            </div>
          </div>

          <div class="uk-margin">
            <label class="uk-form-label"><?php _e( '時間', 'panolabo-simple-booking-plugin' ); ?></label>
            <div class="uk-form-controls">
              <input class="uk-input"
                     type="time"
                     name="<?php echo PSBP_PREFIX; ?>_time"
                     required>
            </div>
          </div>

          <div class="uk-margin">
            <label class="uk-form-label"><?php _e( 'お名前', 'panolabo-simple-booking-plugin' ); ?></label>
            <div class="uk-form-controls">
              <input class="uk-input"
                     type="text"
                     name="<?php echo PSBP_PREFIX; ?>_name"
                     required>
            </div>
          </div>

          <div class="uk-margin">
            <label class="uk-form-label"><?php _e( 'メール', 'panolabo-simple-booking-plugin' ); ?></label>
            <div class="uk-form-controls">
              <input class="uk-input"
                     type="email"
                     name="<?php echo PSBP_PREFIX; ?>_email"
                     required>
            </div>
          </div>

          <div class="uk-margin">
            <button class="uk-button uk-button-primary"
                    type="submit"
                    name="<?php echo PSBP_PREFIX; ?>_submit">
              <?php esc_html_e( '予約する', 'panolabo-simple-booking-plugin' ); ?>
            </button>
          </div>
        </form>
        <?php
        return ob_get_clean();
    }
}

/**
 * ── フォーム送信処理 ───────────────────────────────────
 */
if ( ! function_exists( 'psbp_handle_form_submission' ) ) {
    function psbp_handle_form_submission() {
        if ( empty( $_POST[ PSBP_PREFIX . '_submit' ] ) ) {
            return;
        }
        if (
            ! isset( $_POST['psbp_front_nonce'] ) ||
            ! wp_verify_nonce( $_POST['psbp_front_nonce'], 'psbp_front_submit' )
        ) {
            return;
        }
        $d = sanitize_text_field( $_POST['psbp_date'] );
        $t = sanitize_text_field( $_POST['psbp_time'] );
        $n = sanitize_text_field( $_POST['psbp_name'] );
        $e = sanitize_email(      $_POST['psbp_email'] );
        
        $id = wp_insert_post( array(
            'post_type'   => PSBP_POST_TYPE,
            'post_title'  => "{$n} - {$d} {$t}",
            'post_status' => 'publish',
        ) );
        add_post_meta( $id, PSBP_PREFIX . '_date',  $d );
        add_post_meta( $id, PSBP_PREFIX . '_time',  $t );
        add_post_meta( $id, PSBP_PREFIX . '_name',  $n );
        add_post_meta( $id, PSBP_PREFIX . '_email', $e );

        // 送信元設定を取得
        $from_address = psbp_get_setting( 'mail_from_address', 'noreply@'. $_SERVER['SERVER_NAME'] );
        $from_name    = psbp_get_setting( 'mail_from_name',    get_bloginfo( 'name' ) );
        $headers = [
        sprintf( 'From: %s <%s>', $from_name, $from_address ),
        sprintf( 'Reply-To: %s <%s>', $from_name, $from_address ),
        ];

        // 管理者通知
        wp_mail(
            get_option( 'admin_email' ),
            __( '新しい予約が入りました', 'panolabo-simple-booking-plugin' ),
            "お名前: {$n}\n日時: {$d} {$t}\nメール: {$e}",
            $headers
        );
  
        // お客様通知
        psbp_send_customer_notification( $e, $n, $d, $t );

        wp_redirect( add_query_arg( PSBP_PREFIX . '_success', '1', wp_get_referer() ) );
        exit;
    }
}

/**
 * ── REST API ルート登録 ───────────────────────────────
 */
if ( ! function_exists( 'psbp_register_rest_routes' ) ) {
    function psbp_register_rest_routes() {
        register_rest_route( PSBP_REST_NAMESPACE, PSBP_REST_BOOKINGS, array(
            'methods'  => 'GET',
            'callback' => 'psbp_rest_bookings',
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( PSBP_REST_NAMESPACE, PSBP_REST_SLOTS, array(
            'methods'  => 'GET',
            'callback' => 'psbp_rest_slots',
            'permission_callback' => '__return_true',
            'args' => array(
                'date' => array(
                    'required'          => true,
                    'validate_callback' => function( $v ) {
                        return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v );
                    },
                ),
            ),
        ) );
    }
}

if ( ! function_exists( 'psbp_rest_bookings' ) ) {
    function psbp_rest_bookings() {
        $q = new WP_Query([
            'post_type'      => PSBP_POST_TYPE,
            'post_status'    => ['pending','publish'],
            'posts_per_page' => -1,
        ]);
        $events = [];
        foreach ( $q->posts as $p ) {
            $d = get_post_meta( $p->ID, PSBP_PREFIX . '_date', true );
            $t = get_post_meta( $p->ID, PSBP_PREFIX . '_time', true );
            if ( $d && $t ) {
                $events[] = [
                    'title' => __( '予約済み', 'panolabo-simple-booking-plugin' ),
                    'start' => "{$d}T{$t}:00",
                ];
            }
        }
        return rest_ensure_response( $events );
    }
}

if ( ! function_exists( 'psbp_rest_slots' ) ) {
    function psbp_rest_slots( $request ) {
        $date    = $request['date'];
        $service = isset( $request['service'] ) ? sanitize_text_field( $request['service'] ) : '';

        // 設定画面で登録した services 配列とデフォルト duration
        $services = json_decode( psbp_get_setting( 'services', '[]' ), true );
        $default_duration = intval( psbp_get_setting( 'slot_duration', 15 ) );
        $duration = $default_duration;
        foreach ( $services as $s ) {
            if ( $s['id'] === $service ) {
                $duration = intval( $s['duration'] );
                break;
            }
        }

        // 複数席対応の上限
        $capacity = intval( psbp_get_setting( 'capacity_default', 1 ) );

        $open  = psbp_get_setting( 'opening_time',  '09:00' );
        $close = psbp_get_setting( 'closing_time', '18:00' );
        $start = strtotime( "{$date} {$open}" );
        $end   = strtotime( "{$date} {$close}" );

        // 既存予約を「開始／終了タイムスタンプ」の配列に
        $q = new WP_Query([
            'post_type'      => PSBP_POST_TYPE,
            'post_status'    => [ 'pending', 'publish' ],
            'meta_query'     => [
                [ 'key' => PSBP_PREFIX . '_date', 'value' => $date ],
            ],
            'posts_per_page' => -1,
        ]);
        $occupied = [];
        foreach ( $q->posts as $p ) {
            $t = get_post_meta( $p->ID, PSBP_PREFIX . '_time', true );
            // 投稿にサービスIDも保存していれば、ここで固有 duration に切り替え可
            $svc_dur = $default_duration;
            $saved_svc = get_post_meta( $p->ID, PSBP_PREFIX . '_service', true );
            foreach ( $services as $s ) {
                if ( $s['id'] === $saved_svc ) {
                    $svc_dur = intval( $s['duration'] );
                    break;
                }
            }
            $ts0 = strtotime( "{$date} {$t}" );
            $occupied[] = [ 'start' => $ts0, 'end' => $ts0 + $svc_dur * 60 ];
        }

        // スロット候補（設定の slot_duration 刻み）
        $step = intval( $default_duration ) * 60;
        $slots = [];
        for ( $ts = $start; $ts + $duration*60 <= $end; $ts += $step ) {
            $candidate_start = $ts;
            $candidate_end   = $ts + $duration * 60;

            // ① 期間が重複する既存予約があるかどうか
            $overlap_count = 0;
            foreach ( $occupied as $int ) {
                if ( $candidate_start < $int['end'] && $candidate_end > $int['start'] ) {
                    $overlap_count++;
                }
            }

            // ② 重なり数が capacity 未満なら OK
            $slots[ date( 'H:i', $ts ) ] = ( $overlap_count < $capacity );
        }

        return rest_ensure_response( $slots );
    }
}


/**
 * ── カレンダー用ショートコード ───────────────────────
 */
if ( ! function_exists( 'psbp_calendar_shortcode' ) ) {
    function psbp_calendar_shortcode() {
        return '
        <div class="uk-card uk-card-default uk-card-body uk-margin-large">
        <h3 class="uk-card-title uk-text-center"><span uk-icon="calendar"></span> ご予約カレンダー</h3>
        <div id="psbp-calendar" style="height:400px"></div>
        </div>';
    }

}

/**
 * ── アセット読み込み＋FullCalendar 初期化 ─────────────
 */
if ( ! function_exists( 'psbp_enqueue_assets' ) ) {
    function psbp_enqueue_assets() {
        if ( ! is_singular() ) {
            return;
        }
        global $post;
        if ( false === strpos( $post->post_content, '[' . PSBP_PREFIX . '_booking_calendar]' ) ) {
            return;
        }

        // フロント用 nonce を生成
        $front_nonce = wp_create_nonce( 'psbp_front_submit' );

        // UIkit CSS/JS
        wp_enqueue_style(  'psbp-uikit-css',   'https://cdn.jsdelivr.net/npm/uikit@3.17.0/dist/css/uikit.min.css' );
        wp_enqueue_script( 'psbp-uikit-js',    'https://cdn.jsdelivr.net/npm/uikit@3.17.0/dist/js/uikit.min.js', [], null, true );
        wp_enqueue_script( 'psbp-uikit-icons','https://cdn.jsdelivr.net/npm/uikit@3.17.0/dist/js/uikit-icons.min.js', [ 'psbp-uikit-js' ], null, true );

        // FullCalendar
        wp_enqueue_style(  'psbp-fc-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/main.min.css' );
        wp_enqueue_script( 'psbp-fc-js',  'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js', [], null, true );
        // 日本語ロケール
        wp_enqueue_script(
            'psbp-fc-locale-ja',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/locales/ja.js',
            ['psbp-fc-js'], // fc-js の後に読み込む
            null,
            true
        );
        
        // PHPからJSへデータを渡す
        wp_localize_script( 'psbp-fc-js', 'psbpBookingData', [
            'rest_url'     => rest_url(   PSBP_REST_NAMESPACE . PSBP_REST_BOOKINGS ),
            'slots_url'    => rest_url(   PSBP_REST_NAMESPACE . PSBP_REST_SLOTS    ),
            'form_url'     => get_permalink( get_page_by_path( PSBP_PAGE_SLUG ) ),
            'services'     => json_decode( psbp_get_setting( 'services','[]' ), true ),
            'minGuests'    => intval( psbp_get_setting( 'min_guests', 1 ) ),
            'maxGuests'    => intval( psbp_get_setting( 'max_guests', 1 ) ),
            'openingTime' => psbp_get_setting( 'opening_time', '09:00' ),
            'closingTime' => psbp_get_setting( 'closing_time', '18:00' ),
        
            'front_nonce'  => $front_nonce,
        ] );

        // モーダル内フォームを組み立てるJS
        $inline = <<<'JS'
        document.addEventListener('DOMContentLoaded', function(){
          var el = document.getElementById('psbp-calendar');
          if (!el) return;
          var cal = new FullCalendar.Calendar(el, {
            locale: 'ja',
            initialView:'dayGridMonth',
            headerToolbar:{ left:'prev,next today', center:'title', right:'timeGridWeek,timeGridDay,dayGridMonth' },
            events: psbpBookingData.rest_url,
            dateClick: function(info){
              var ymd = info.dateStr;
              // STEP1: サービスリスト
              var servicesHtml = psbpBookingData.services.map(function(s){
                return '<option value="'+s.id+'">'+s.name+'</option>';
              }).join('');
              // モーダル内のHTML組み立て
              var html  = '<ul class="uk-subnav uk-subnav-pill uk-margin" uk-switcher="connect:#booking-steps">';
                  html +=   '<li><a>1. メニュー</a></li>';
                  html +=   '<li><a>2. 時間</a></li>';
                  html +=   '<li><a>3. 情報</a></li>';
                  html += '</ul>';
                  html += '<ul id="booking-steps" class="uk-switcher">';
              // STEP1 フォーム
                  html += '<li><form id="step1-form" class="uk-form-stacked">';
                  html +=   '<div class="uk-margin"><label class="uk-form-label">サービス</label>';
                  html +=     '<div class="uk-form-controls">';
                  html +=       '<select name="psbp_service" class="uk-select">'+servicesHtml+'</select>';
                  html +=     '</div></div>';
                  html +=   '<button class="uk-button uk-button-primary" id="to-step2">次へ</button>';
                  html += '</form></li>';
              // STEP2 リスト表示のみ（隠しフィールドは入れない）
                  html += '<li>';
                  html +=   '<input type="hidden" id="chosen-service">';  // STEP1 から引き継ぐだけ
                  html +=   '<div class="uk-margin"><label class="uk-form-label">時間</label>';
                  html +=     '<ul id="time-list" class="uk-list uk-list-divider"></ul>';
                  html +=   '</div>';
                  html +=   '<button class="uk-button uk-button-default" id="back-to-step1">戻る</button>';
                  html += '</li>';
              // STEP3 実際にPOST するフォーム
                  html += '<li><form id="step3-form" class="uk-form-stacked" method="post" action="'+psbpBookingData.form_url+'">';
                  html +=   '<input type="hidden" name="psbp_submit"     value="1">';
                  html +=   '<input type="hidden" name="psbp_front_nonce" value="'+psbpBookingData.front_nonce+'">';
                  html +=   '<input type="hidden" name="psbp_date"        value="'+ymd+'">';
                  html +=   '<input type="hidden" id="final-service" name="psbp_service">';
                  html +=   '<input type="hidden" id="final-time-step3" name="psbp_time">';
                  html +=   '<div class="uk-margin"><label class="uk-form-label">お名前</label>';
                  html +=     '<div class="uk-form-controls"><input class="uk-input" name="psbp_name" required></div></div>';
                  html +=   '<div class="uk-margin"><label class="uk-form-label">メール</label>';
                  html +=     '<div class="uk-form-controls"><input class="uk-input" type="email" name="psbp_email" required></div></div>';
                  html +=   '<button class="uk-button uk-button-default" id="back-to-step2">戻る</button> ';
                  html +=   '<button class="uk-button uk-button-primary" type="submit">確定</button>';
                  html += '</form></li>';
                  html += '</ul>';
        
              var modal = UIkit.modal.dialog('<div class="uk-modal-body">'+html+'</div>', { animation: 'slide' });
              var switcher = UIkit.switcher(modal.$el.querySelector('[uk-switcher]'));
        
              // STEP1 → STEP2
              modal.$el.querySelector('#to-step2').addEventListener('click', function(e){
                e.preventDefault();
                var svc = modal.$el.querySelector('select[name="psbp_service"]').value;
                modal.$el.querySelector('#chosen-service').value = svc;
                fetch(psbpBookingData.slots_url+'?date='+ymd+'&service='+svc)
                  .then(res=>res.json())
                  .then(function(slots){
                    var list = modal.$el.querySelector('#time-list');
                    list.innerHTML = '';
                    Object.entries(slots).forEach(function([t,ok]){
                      var li = document.createElement('li');
                      if(ok){
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'uk-button uk-button-default uk-width-1-1';
                        btn.textContent = t;
                        btn.addEventListener('click', function(){
                          modal.$el.querySelector('#final-service').value = svc;
                          modal.$el.querySelector('#final-time-step3').value = t;
                          switcher.show(2);
                        });
                        li.appendChild(btn);
                      } else {
                        li.textContent = t+' ×';
                        li.className = 'uk-text-muted';
                      }
                      list.appendChild(li);
                    });
                    switcher.show(1);
                  });
              });
              // STEP2 戻る
              modal.$el.querySelector('#back-to-step1').addEventListener('click', function(e){
                e.preventDefault(); switcher.show(0);
              });
              // STEP3 戻る
              modal.$el.querySelector('#back-to-step2').addEventListener('click', function(e){
                e.preventDefault(); switcher.show(1);
              });
            }
          });
          cal.render();
        });
        JS;
        wp_add_inline_script('psbp-fc-js',$inline);
            }
}

/**
 * ── 自動固定ページ生成 ─────────────────────────────
 */
if ( ! function_exists( 'psbp_activate_plugin' ) ) {
    function psbp_activate_plugin() {
        psbp_register_post_type();
        flush_rewrite_rules();
        $page    = get_page_by_path( PSBP_PAGE_SLUG );
        //$content = "[psbp_booking_form]\n[psbp_booking_calendar]";
        $content = "[psbp_booking_calendar]";
        if ( ! $page ) {
            wp_insert_post( array(
                'post_title'   => __( 'ご予約ページ', 'panolabo-simple-booking-plugin' ),
                'post_name'    => PSBP_PAGE_SLUG,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ) );
        } elseif ( false === strpos( $page->post_content, '[psbp_booking_calendar]' ) ) {
            wp_update_post( array(
                'ID'           => $page->ID,
                'post_content' => $content,
            ) );
        }
    }
}

if ( ! function_exists( 'psbp_deactivate_plugin' ) ) {
    function psbp_deactivate_plugin() {
        flush_rewrite_rules();
    }
}

/**
 * ── テンプレート切替 ─────────────────────────────
 */
add_filter( 'template_include', 'psbp_override_template', 99 );
function psbp_override_template( $template ) {
    // 定数と現在のページスラッグをログ出力
    error_log( '[PSBP] is_page("' . PSBP_PAGE_SLUG . '") = ' . ( is_page( PSBP_PAGE_SLUG ) ? 'TRUE' : 'FALSE' ) );
    error_log( '[PSBP] current template = ' . $template );
 
    if ( is_page( PSBP_PAGE_SLUG ) ) {
        // __DIR__ が includes/ フォルダを指している場合もあるので plugin_dir_path() で直下を狙う
        $tpl = plugin_dir_path( __DIR__ ) . 'templates/booking-page-template.php';
        error_log( '[PSBP] checking plugin template: ' . $tpl );
        if ( file_exists( $tpl ) ) {
            error_log( '[PSBP] override with booking-page-template.php' );
            return $tpl;
        } else {
            error_log( '[PSBP] booking-page-template.php not found' );
        }
    }
    return $template;
}
