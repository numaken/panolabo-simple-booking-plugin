=== Panolabo Simple Booking Plugin ===
Contributors: yourname
Tags: booking, reservation, calendar
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


== Announcement Message ==

🎉 新しい WordPress 予約プラグイン『Panolabo Simple Booking Plugin』を公開しました！
お気軽にテストしてフィードバックをお寄せください😊
🔗 ダウンロード: https://www.panolabollc.com/lab/panolabo-simple-booking-plugin/panolabo-simple-booking-plugin.zip
#WordPress #プラグイン #予約

== Description ==
Panolabo Simple Booking Plugin は、WordPress サイトに予約機能を簡単に追加できる軽量プラグインです。  
カレンダー形式で空きスロットを一覧表示し、ステップ形式のフォームから予約を受け付けます。


== Installation ==
1. 管理画面 → プラグイン → 新規追加 → プラグインのアップロード から ZIP をアップロードして有効化  
   または FTP/SFTP で `panolabo-simple-booking-plugin` フォルダを `wp-content/plugins/` 配下に置き、有効化。  
2. プラグイン有効化時に自動で「ご予約ページ」という固定ページを作成します。  
   固定ページ → 「ご予約ページ」を公開状態にしてください。  
3. 管理画面 → 設定 → 予約設定 から、営業時間、スロット長、定員、メニュー、メール送信元を設定。


== Usage ==
1. 「ご予約ページ」にアクセス → カレンダーで日付クリック  
2. メニュー選択 → 空き時間スロット選択 → お客様情報入力 → 予約完了  

任意の固定ページ／投稿に以下ショートコードを貼り付けて利用できます（複数ページ可）：

[psbp_booking_calendar] カレンダー形式の予約ページを表示

[psbp_booking_form] シンプルな予約フォームのみを表示


== Settings ==
- **時間スロット長（分）**：予約受付の時間刻み  
- **予約確保可能数（定員）**：同一スロット内で受け付ける予約数  
- **営業時間開始／終了**：カレンダーに表示する時間帯  
- **最小／最大人数**：フォーム入力時の人数制限  
- **メニュー（JSON 配列）**：各サービスの `id`, `name`, `duration` を設定  
- **お客様通知メール**：ON で予約完了メールを自動送信  
- **送信元アドレス／表示名**：通知メールの From ヘッダーを任意に設定可能


== Testing Instructions ==
1. ZIP を配布し、プラグインを有効化  
2. 設定画面で営業時間・定員・メニュー・送信元をセット  
3. 「ご予約ページ」にアクセスし、同一スロットを複数ユーザーで予約  
4. 定員オーバー時は予約不可、定員以内で予約可となることを確認  
5. 管理画面 → 予約管理 で予約データを確認  
6. 予約者・管理者に届くメールの送信元と表示名を検証


== Frequently Asked Questions ==
= ショートコードは複数ページに貼れますか？ =  
はい。どの固定ページ／投稿にも貼り付けて利用可能です。  
有効化時に自動生成される「ご予約ページ」は不要なら削除して、別のページに貼っても構いません。

= メール送信元を変更したい =  
予約プラグイン設定 → 基本設定 に「送信元アドレス」「送信者名」欄を追加しました。  
ここで指定した値が、ユーザー通知メール・管理者通知メールの From ヘッダーに反映されます。

== Changelog ==
= 1.0.0 =

Initial release

== Upgrade Notice ==
= 1.0.0 =
初回リリースです。

