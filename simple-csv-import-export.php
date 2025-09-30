<?php
/**
 * Plugin Name: Simple CSV Import Export
 * Description: CSVファイルを使用して投稿、固定ページ、カスタム投稿タイプを一括インポート/エクスポートできるプラグインです。WordPress標準のインポートツールとして統合されます。
 * Version: 1.0.0
 * Author: Shota Takazawa
 * Author URI: https://sokulabo.com/products/simple-csv-import-export/
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグインの初期化
add_action('admin_init', 'scv_admin_init');
add_action('admin_init', 'scv_add_importer');
add_action('admin_menu', 'scv_add_menu_page');

// 管理画面メニューに追加
if (!function_exists('scv_add_menu_page')) {
    function scv_add_menu_page() {
        add_submenu_page(
            'tools.php',                          // 親メニュー（ツール）
            'Simple CSV Import Export',           // ページタイトル
            'Simple CSV Import Export',           // メニュータイトル
            'manage_options',                     // 必要な権限
            'simple-csv-import-export',           // メニューのスラッグ
            'scv_admin_page'                      // 表示用の関数
        );
    }
}

// WordPressのインポートツールに追加
if (!function_exists('scv_add_importer')) {
    function scv_add_importer() {
        register_importer(
            'simple-csv-import-export',
            'Simple CSV Import Export',
            'CSVファイルを使用して投稿、固定ページ、カスタム投稿タイプを一括インポート/エクスポートできます。',
            'scv_admin_page'
        );
    }
}

// プラグイン非アクティブ化時のクリーンアップ
register_deactivation_hook(__FILE__, 'scv_cleanup_temp_files');

if (!function_exists('scv_cleanup_temp_files')) {
    function scv_cleanup_temp_files() {
        // WordPress Filesystem APIを初期化
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        WP_Filesystem();
        global $wp_filesystem;
        
        // 一時ディレクトリをクリーンアップ
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/csv_temp/';
        
        if ($wp_filesystem->exists($temp_dir)) {
            $wp_filesystem->rmdir($temp_dir, true);
        }
    }
}

// 管理画面の初期化
if (!function_exists('scv_admin_init')) {
    function scv_admin_init() {
        // CSVファイルのアップロード処理
        if (isset($_POST['import_csv']) && !empty($_FILES['csv_file']['name'])) {
            scv_process_csv_import();
        }
        
        // CSVエクスポート処理
        if (isset($_POST['export_csv'])) {
            scv_process_csv_export();
        }
        
        // サンプルCSVダウンロード処理
        if (isset($_POST['download_sample_csv'])) {
            scv_download_sample_csv();
        }
        
        // CSVテスト処理
        if (isset($_POST['test_csv']) && !empty($_FILES['test_csv_file']['name'])) {
            scv_process_csv_test();
        }
    }
}

// 管理画面のページ
if (!function_exists('scv_admin_page')) {
    function scv_admin_page() {
    ?>
    <div class="wrap">
        <h1>Simple CSV Import Export</h1>
        <p>CSVファイルを使用して、WordPressの投稿、固定ページ、カスタム投稿タイプを一括でインポート・エクスポートできます。</p>
        
        <!-- CSS スタイル -->
        <style>
            /* WordPressスタイルのタブナビゲーション */
            .nav-tab-wrapper {
                border-bottom: none;
                margin: 0;
                padding-top: 9px;
                padding-bottom: 0;
                line-height: inherit;
            }
            .nav-tab-wrapper:after {
                clear: both;
                content: "";
                display: table;
            }
            .nav-tab {
                background: #f1f1f1;
                border: none;
                color: #646970;
                display: inline-block;
                font-size: 14px;
                font-weight: 600;
                line-height: 1;
                margin: 0 5px -1px 0;
                padding: 8px 12px;
                text-decoration: none;
                white-space: nowrap;
                outline: none;
                box-shadow: none;
            }
            .nav-tab:focus {
                outline: none;
                box-shadow: none;
                border-bottom: none;
            }
            .nav-tab:hover {
                background-color: #fff;
                color: #646970;
                border-bottom: none;
            }
            .nav-tab-active,
            .nav-tab-active:hover {
                background: #fff!important;
                color: #000;
            }
            
            /* タブコンテンツ */
            .scv-tab-content {
                display: none;
                background: #fff;
                padding: 20px;
            }
            .scv-tab-content.active {
                display: block;
            }
            
            /* 既存スタイル */
            .scv-form-group {
                margin-bottom: 15px;
            }
            .scv-form-label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                font-size: 13px;
            }
            .scv-format-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .scv-format-table th,
            .scv-format-table td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 12px;
            }
            .scv-format-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .scv-format-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .scv-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
                padding: 12px;
                margin-bottom: 15px;
                color: #856404;
            }
            .scv-success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            .scv-error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
        </style>

        <!-- タブナビゲーション -->
        <div class="nav-tab-wrapper">
            <a href="#tab-import" class="nav-tab nav-tab-active scv-tab-link" data-tab="import">インポート</a>
            <a href="#tab-export" class="nav-tab scv-tab-link" data-tab="export">エクスポート</a>
            <a href="#tab-format" class="nav-tab scv-tab-link" data-tab="format">CSVフォーマット仕様</a>
            <a href="#tab-test" class="nav-tab scv-tab-link" data-tab="test">CSVテスト</a>
            <a href="#tab-manual" class="nav-tab scv-tab-link" data-tab="manual">マニュアル</a>
        </div>

        <!-- CSVインポートタブ -->
        <div id="tab-import" class="scv-tab-content active">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('csv_import_action', 'csv_import_nonce'); ?>
                    
                    <div class="scv-form-group">
                        <label for="csv_file" class="scv-form-label">CSVファイルを選択:</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                    
                    <div class="scv-form-group">
                        <label>
                            <input type="checkbox" name="overwrite_empty" value="1">
                            CSVの空セルで既存データを上書き（post_idが同一の場合のみ有効）
                        </label>
                        <p style="font-size: 12px; color: #666; margin: 5px 0 0 20px;">チェックすると、CSVの空セルが既存の投稿データを空で上書してデータを削除します。チェックしない場合、空セルは既存データを保持します。</p>
                    </div>
                    
                    <button type="submit" name="import_csv" class="button button-primary">CSVをインポート</button>
                </form>
            </div>

            <!-- CSVエクスポートタブ -->
            <div id="tab-export" class="scv-tab-content">
                <form method="post">
                    <?php wp_nonce_field('csv_export_action', 'csv_export_nonce'); ?>
                    
                    <div class="scv-form-group">
                        <label for="export_post_type" class="scv-form-label">エクスポートする投稿タイプ:</label>
                        <select name="export_post_type" id="export_post_type">
                            <option value="post">投稿</option>
                            <option value="page">固定ページ</option>
                            <option value="all">すべての投稿タイプ</option>
                            <?php
                            $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                            foreach ($custom_post_types as $post_type) {
                                echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->labels->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="scv-form-group">
                        <label for="export_status" class="scv-form-label">エクスポートする投稿ステータス:</label>
                        <select name="export_status" id="export_status">
                            <option value="all">すべて</option>
                            <option value="publish">公開済み</option>
                            <option value="draft">下書き</option>
                            <option value="private">非公開</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="export_csv" class="button button-secondary">CSVをエクスポート</button>
                </form>
            </div>

            <!-- CSVテストタブ -->
            <div id="tab-test" class="scv-tab-content">
                <p>CSVファイルをアップロードして、内容の確認とファイル形式の検証を行います。</p>
                
                <form method="post" enctype="multipart/form-data" id="csv-test-form">
                    <?php wp_nonce_field('csv_test_action', 'csv_test_nonce'); ?>
                    
                    <div class="scv-form-group">
                        <label for="test_csv_file" class="scv-form-label">テスト用CSVファイル:</label>
                        <input type="file" name="test_csv_file" id="test_csv_file" accept=".csv" required>
                    </div>
                    
                    <div class="scv-form-group">
                        <label>
                            <input type="checkbox" name="detailed_check" value="1" checked>
                            詳細チェックを実行（データの妥当性検証を含む）
                        </label>
                    </div>
                    
                    <div class="scv-form-group">
                        <label>
                            <input type="checkbox" name="validate_urls" value="1">
                            画像URLの有効性をチェック（時間がかかる場合があります）
                        </label>
                    </div>
                    
                    <button type="submit" name="test_csv" class="button button-primary">
                        CSVファイルをテスト・プレビュー
                    </button>
                </form>
                
                <div id="csv-test-results" style="margin-top: 20px;">
                    <?php
                    // CSVテストの詳細結果を表示
                    global $scv_test_results;
                    if (!empty($scv_test_results)) {
                        scv_display_csv_test_details(
                            $scv_test_results['file_analysis'],
                            $scv_test_results['headers'],
                            $scv_test_results['csv_data'],
                            $scv_test_results['validation_results'],
                            $scv_test_results['detailed_check']
                        );
                    }
                    ?>
                </div>
            </div>
            
            <!-- CSVフォーマット仕様タブ -->
            <div id="tab-format" class="scv-tab-content">
                <p>以下のフォーマットでCSVファイルを作成してください。以下の必要なデータ名を、1行目にヘッダー行として記述してください。</p>
                
                <table class="scv-format-table">
                    <thead>
                        <tr>
                            <th>列名</th>
                            <th>説明</th>
                            <th>記入例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>post_id</td>
                            <td>記事のID（更新の場合のみ必要、新規作成の場合は空白）</td>
                            <td>123</td>
                        </tr>
                        <tr>
                            <td>post_name</td>
                            <td>記事のスラッグ（半角英数字、空白の場合は自動生成）</td>
                            <td>sample-post</td>
                        </tr>
                        <tr>
                            <td>post_author</td>
                            <td>投稿するユーザーのID</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>post_date</td>
                            <td>記事の日付</td>
                            <td>2024-01-15 10:00:00</td>
                        </tr>
                        <tr>
                            <td>post_content</td>
                            <td>記事の本文（HTMLタグ使用可能）</td>
                            <td>&lt;p&gt;記事の本文です。&lt;/p&gt;</td>
                        </tr>
                        <tr>
                            <td>post_title</td>
                            <td>記事のタイトル</td>
                            <td>サンプル記事</td>
                        </tr>
                        <tr>
                            <td>post_excerpt</td>
                            <td>記事の抜粋</td>
                            <td>記事の要約文です。</td>
                        </tr>
                        <tr>
                            <td>post_status</td>
                            <td>記事の状態</td>
                            <td>publish, draft, private</td>
                        </tr>
                        <tr>
                            <td>post_password</td>
                            <td>記事のパスワード（20文字以内）</td>
                            <td>password123</td>
                        </tr>
                        <tr>
                            <td>menu_order</td>
                            <td>記事の順番（数字）</td>
                            <td>1</td>
                        </tr>
                        <tr>
                            <td>post_type</td>
                            <td>投稿タイプ</td>
                            <td>post, page, custom_type</td>
                        </tr>
                        <tr>
                            <td>post_thumbnail</td>
                            <td>サムネイル画像のURL</td>
                            <td>https://example.com/image.jpg</td>
                        </tr>
                        <tr>
                            <td>post_category</td>
                            <td>カテゴリーのスラッグ（カンマ区切りで複数指定可能）</td>
                            <td>category1,category2</td>
                        </tr>
                        <tr>
                            <td>post_tags</td>
                            <td>タグのスラッグ（カンマ区切りで複数指定可能）</td>
                            <td>tag1,tag2,tag3</td>
                        </tr>
                        <tr>
                            <td>tax_{taxonomy}</td>
                            <td>カスタムタクソノミーの値（{taxonomy}を実際のタクソノミー名に置換）</td>
                            <td>tax_product_category</td>
                        </tr>
                        <tr>
                            <td>{custom_field_key}</td>
                            <td>カスタムフィールドの値（{custom_field_key}を実際のフィールド名に置換）</td>
                            <td>price, description</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4 style="margin-top: 30px;">サンプルCSVファイル</h4>
                <p>以下のサンプルCSVをダウンロードしてそのままご利用いただけます。任意の内容に編集してお使いください。</p>
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('sample_csv_download', 'sample_csv_nonce'); ?>
                        <button type="submit" name="download_sample_csv" class="button button-secondary" style="font-size: 12px; height: 28px; padding: 0 12px; display: flex; align-items: center; gap: 5px;">
                            サンプルCSVをダウンロード
                        </button>
                    </form>
                    <span style="font-size: 12px; color: #666;">※ 実際のデータ形式を確認できます</span>
                </div>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; font-size: 11px;">post_id,post_title,post_content,post_status,post_type,post_category,post_tags
,サンプル記事1,"&lt;p&gt;これは最初の記事です。&lt;/p&gt;",publish,post,sample-category,tag1
,サンプル記事2,"&lt;p&gt;これは2番目の記事です。&lt;/p&gt;",draft,post,"category1,category2","tag1,tag2"</pre>
            </div>

            <!-- マニュアルタブ -->
            <div id="tab-manual" class="scv-tab-content">
                <h3>Simple CSV Import Export プラグイン マニュアル</h3>
                
                <div class="scv-notice">
                    <strong>重要:</strong> このプラグインを使用する前に、以下のマニュアルをお読みください。
                </div>

                <p><a href="https://sokulabo.com/products/simple-csv-import-export/" target="_blank">https://sokulabo.com/products/simple-csv-import-export/</a></p>
            </div>
            
            <!-- JavaScript for Tab Functionality -->
            <script>
            jQuery(document).ready(function($) {
                // タブクリック処理
                $('.scv-tab-link').on('click', function(e) {
                    e.preventDefault();
                    
                    var targetTab = $(this).data('tab');
                    
                    // すべてのタブとコンテンツからactiveクラスを削除
                    $('.scv-tab-link').removeClass('nav-tab-active');
                    $('.scv-tab-content').removeClass('active');
                    
                    // クリックされたタブとそのコンテンツにactiveクラスを追加
                    $(this).addClass('nav-tab-active');
                    $('#tab-' + targetTab).addClass('active');
                    
                    // URLハッシュを更新（任意）
                    if (history.pushState) {
                        history.pushState(null, null, '#tab-' + targetTab);
                    }
                });
                
                // ページ読み込み時にURLハッシュからタブを判定
                function initTabFromHash() {
                    var hash = window.location.hash;
                    if (hash && hash.match(/^#tab-(import|export|format|test|manual)$/)) {
                        var tabName = hash.replace('#tab-', '');
                        $('.scv-tab-link').removeClass('nav-tab-active');
                        $('.scv-tab-content').removeClass('active');
                        $('.scv-tab-link[data-tab="' + tabName + '"]').addClass('nav-tab-active');
                        $('#tab-' + tabName).addClass('active');
                    }
                }
                
                // 初期化
                initTabFromHash();
                
                // ブラウザの戻る/進むボタン対応
                $(window).on('popstate', function() {
                    initTabFromHash();
                });
            });
            </script>
        </div>
        <?php
    }
}

// CSVインポート処理
if (!function_exists('scv_process_csv_import')) {
    function scv_process_csv_import() {
        // nonce チェック
        if (!wp_verify_nonce($_POST['csv_import_nonce'], 'csv_import_action')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        // ファイルアップロードのセキュリティチェック
        if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>適切なファイルがアップロードされていません。</p></div>';
            });
            return;
        }
        
        $uploaded_file = $_FILES['csv_file'];
        $overwrite_empty = isset($_POST['overwrite_empty']); // 空データ上書きオプション
        
        // バッチサイズを自動計算（後で決定）
        $batch_size = 0;
        
        // ファイルのバリデーション
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>ファイルのアップロードに失敗しました。</p></div>';
            });
            return;
        }
        
        // ファイルタイプチェック
        $file_info = pathinfo($uploaded_file['name']);
        if (strtolower($file_info['extension']) !== 'csv') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルのみアップロード可能です。</p></div>';
            });
            return;
        }
        
        // CSVファイルの読み込み
        $csv_data = scv_read_csv_file($uploaded_file['tmp_name']);
        if ($csv_data === false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルの読み込みに失敗しました。UTF-8エンコーディングで保存されていることを確認してください。</p></div>';
            });
            return;
        }
        
        if (empty($csv_data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルが空です。</p></div>';
            });
            return;
        }
        
        // ヘッダー行を取得
        $headers = array_shift($csv_data);
        $headers = array_map('trim', $headers);
        
        // バッチサイズを自動計算
        $batch_size = scv_calculate_optimal_batch_size($csv_data, $headers);
        
        // 必須フィールドのチェック（post_titleの必須チェックを削除）
        // 空のpost_titleでもインポートを継続する
        
        // インポート処理の実行（高速モード有効、空データ上書きオプション対応）
        $results = scv_import_posts($csv_data, $headers, $batch_size, true, $overwrite_empty); // 第5パラメータで空データ上書きオプションを渡す
        
        // 結果の表示
        add_action('admin_notices', function() use ($results, $batch_size) {
            $class = $results['errors'] > 0 ? 'notice-warning' : 'notice-success';
            echo '<div class="notice ' . $class . '"><p>';
            echo sprintf(
                'インポート完了: 成功 %d件, スキップ %d件, エラー %d件 (バッチサイズ: %d)',
                $results['success'],
                $results['skipped'],
                $results['errors'],
                $batch_size
            );
            if (!empty($results['error_messages'])) {
                echo '<br><strong>エラー詳細:</strong><br>' . implode('<br>', array_slice($results['error_messages'], 0, 10));
                if (count($results['error_messages']) > 10) {
                    echo '<br>... 他 ' . (count($results['error_messages']) - 10) . ' 件のエラー';
                }
            }
            echo '</p></div>';
        });
    }
}

// CSVファイルを読み込む関数
if (!function_exists('scv_read_csv_file')) {
    function scv_read_csv_file($file_path) {
        $csv_data = array();
        
        // WordPress Filesystem APIを初期化
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        WP_Filesystem();
        global $wp_filesystem;
        
        // ファイルの存在とアクセス権限をチェック
        if (!$wp_filesystem->exists($file_path) || !$wp_filesystem->is_readable($file_path)) {
            return false;
        }
        
        // ファイルを開く（BOM対応）
        $content = $wp_filesystem->get_contents($file_path);
        if ($content === false) {
            return false;
        }
        
        // BOMを除去
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // 改行コードを統一
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        
        // WordPress一時ディレクトリを使用
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/csv_temp/';
        
        // 一時ディレクトリを作成（存在しない場合）
        if (!$wp_filesystem->exists($temp_dir)) {
            $wp_filesystem->mkdir($temp_dir, FS_CHMOD_DIR);
        }
        
        $temp_file = $temp_dir . 'csv_import_' . wp_generate_uuid4() . '.tmp';
        if (!$wp_filesystem->put_contents($temp_file, $content, FS_CHMOD_FILE)) {
            return false;
        }
        
        // CSVを解析
        if (($handle = fopen($temp_file, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
                $csv_data[] = $data;
            }
            fclose($handle);
            
            // 一時ファイルを削除
            $wp_filesystem->delete($temp_file);
        } else {
            // ファイルオープンに失敗した場合も一時ファイルを削除
            $wp_filesystem->delete($temp_file);
            return false;
        }
        
        return $csv_data;
    }
}

// 最適なバッチサイズを自動計算する関数（軽量化版）
if (!function_exists('scv_calculate_optimal_batch_size')) {
    function scv_calculate_optimal_batch_size($csv_data, $headers) {
        $total_rows = count($csv_data);
        
        // 基本バッチサイズを軽量化に最適化（小さく設定）
        $base_batch_size = 20; // 50から20に削減
    
        // データ量に基づく調整（より軽量化重視）
        if ($total_rows <= 50) {
            $size_factor = 1.5; // 小規模: 少し上げる
        } elseif ($total_rows <= 200) {
            $size_factor = 1.0; // 中小規模: そのまま
        } elseif ($total_rows <= 500) {
            $size_factor = 0.7; // 中規模: 下げる
        } elseif ($total_rows <= 1000) {
            $size_factor = 0.5; // 大規模: 大幅に下げる
        } else {
            $size_factor = 0.3; // 超大規模: 大幅に下げる
        }
        
        // サーバー環境に基づく調整（軽量化重視）
        $memory_limit = ini_get('memory_limit');
        $memory_in_mb = intval($memory_limit);
        
        if ($memory_in_mb >= 1024) {
            $memory_factor = 1.3; // 高メモリ: 上げる
        } elseif ($memory_in_mb >= 512) {
            $memory_factor = 1.0; // 標準メモリ: そのまま
        } elseif ($memory_in_mb >= 256) {
            $memory_factor = 0.8; // 低メモリ: 下げる
        } else {
            $memory_factor = 0.5; // 非常に低メモリ: 大幅に下げる
        }
        
        // 実行時間制限に基づく調整
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time == 0) {
            $time_factor = 1.5; // 無制限: 上げる
        } elseif ($max_execution_time >= 300) {
            $time_factor = 1.2; // 長時間: 少し上げる
        } elseif ($max_execution_time >= 120) {
            $time_factor = 1.0; // 標準: そのまま
        } elseif ($max_execution_time >= 60) {
            $time_factor = 0.8; // 短時間: 下げる
        } else {
            $time_factor = 0.4; // 非常に短時間: 大幅に下げる
        }
        
        // 複雑度に基づく調整（画像やカスタムフィールドの有無）
        $complexity_factor = 1.0;
        
        // 画像フィールドがある場合（処理が重い）
        if (in_array('post_thumbnail', $headers)) {
            $complexity_factor *= 0.3; // 画像処理は非常に重いのでさらに下げる
        }
        
        // カスタムフィールドの数（より厳しく制限）
        $custom_field_count = 0;
        foreach ($headers as $header) {
            if (!in_array($header, array('post_id', 'post_name', 'post_author', 'post_date', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'menu_order', 'post_type', 'post_thumbnail', 'post_category', 'post_tags')) && strpos($header, 'tax_') !== 0) {
                $custom_field_count++;
            }
        }
        
        if ($custom_field_count > 15) {
            $complexity_factor *= 0.5; // 非常に多数のカスタムフィールド
        } elseif ($custom_field_count > 10) {
            $complexity_factor *= 0.6; // 多数のカスタムフィールド
        } elseif ($custom_field_count > 5) {
            $complexity_factor *= 0.8; // 中程度のカスタムフィールド
        }
        
        // カスタムタクソノミーの数
        $taxonomy_count = 0;
        foreach ($headers as $header) {
            if (strpos($header, 'tax_') === 0) {
                $taxonomy_count++;
            }
        }
        
        if ($taxonomy_count > 5) {
            $complexity_factor *= 0.6;
        } elseif ($taxonomy_count > 3) {
            $complexity_factor *= 0.8;
        }
        
        // 最終的なバッチサイズを計算
        $calculated_batch_size = intval($base_batch_size * $size_factor * $memory_factor * $time_factor * $complexity_factor);
        
        // 最小・最大値の制限（軽量化重視でより小さく）
        $batch_size = max(3, min(100, $calculated_batch_size)); // 最大値を200から100に削減
        
        // デバッグ情報をログに出力
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'CSV Import Optimized Batch Size: Total=%d, Base=%d, Size=%.2f, Memory=%.2f, Time=%.2f, Complexity=%.2f, Final=%d',
                $total_rows, $base_batch_size, $size_factor, $memory_factor, $time_factor, $complexity_factor, $batch_size
            ));
        }
        
        return $batch_size;
    }
}

// 最適なエクスポート制限を自動計算する関数
if (!function_exists('scv_calculate_optimal_export_limit')) {
    function scv_calculate_optimal_export_limit($post_type, $post_status) {
        // 基本制限数を設定
        $base_limit = 1000;
    
        // サーバー環境に基づく調整
        $memory_limit = ini_get('memory_limit');
        $memory_in_mb = intval($memory_limit);
        
        if ($memory_in_mb >= 512) {
            $memory_factor = 2.0; // 高メモリ: 大幅に上げる
        } elseif ($memory_in_mb >= 256) {
            $memory_factor = 1.5; // 標準メモリ: 上げる
        } elseif ($memory_in_mb >= 128) {
            $memory_factor = 1.0; // 低メモリ: そのまま
        } else {
            $memory_factor = 0.5; // 非常に低メモリ: 下げる
        }
        
        // 実行時間制限に基づく調整
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time == 0) {
            $time_factor = 2.0; // 無制限: 大幅に上げる
        } elseif ($max_execution_time >= 300) {
            $time_factor = 1.5; // 長時間: 上げる
        } elseif ($max_execution_time >= 60) {
            $time_factor = 1.0; // 標準: そのまま
        } else {
            $time_factor = 0.3; // 短時間: 大幅に下げる
        }
        
        // 投稿タイプに基づく調整
        $type_factor = 1.0;
        if ($post_type === 'all' || $post_type === 'any') {
            $type_factor = 0.7; // 全投稿タイプは重いので下げる
        }
        
        // データベースサイズの推定による調整
        $total_posts = wp_count_posts();
        $total_count = 0;
        if (is_object($total_posts)) {
            foreach ($total_posts as $status => $count) {
                $total_count += $count;
            }
        }
        
        $size_factor = 1.0;
        if ($total_count > 10000) {
            $size_factor = 0.5; // 大規模サイト: 下げる
        } elseif ($total_count > 5000) {
            $size_factor = 0.7; // 中規模サイト: 少し下げる
        } elseif ($total_count > 1000) {
            $size_factor = 0.9; // やや大きいサイト: わずかに下げる
        }
        
        // 最終的なエクスポート制限を計算
        $calculated_limit = intval($base_limit * $memory_factor * $time_factor * $type_factor * $size_factor);
        
        // 最小・最大値の制限
        $export_limit = max(100, min(10000, $calculated_limit));
        
        // デバッグ情報をログに出力
        error_log(sprintf(
            'CSV Export Auto Limit Calculation: Base=%d, Memory=%.2f, Time=%.2f, Type=%.2f, Size=%.2f, Final=%d',
            $base_limit, $memory_factor, $time_factor, $type_factor, $size_factor, $export_limit
        ));
        
        return $export_limit;
    }
}

// 投稿をインポートする関数（高速モード対応、空データ上書きオプション対応）
if (!function_exists('scv_import_posts')) {
    function scv_import_posts($csv_data, $headers, $batch_size, $fast_mode = false, $overwrite_empty = false) {
        $results = array(
            'success' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_messages' => array()
        );
    
        // 処理時間制限を延長
        set_time_limit(600); // 10分に延長
        ini_set('memory_limit', '1024M'); // メモリ制限を1GBに増加
        
        // WordPress処理を高速化
        $original_doing_it_wrong_triggered = did_action('doing_it_wrong_triggered');
        remove_action('doing_it_wrong_run', 'doing_it_wrong_run');
        
        // WordPressの自動保存とリビジョンを無効化
        remove_action('pre_post_update', 'wp_save_post_revision');
        add_filter('wp_revisions_to_keep', '__return_zero');
        
        // 高速モード時はさらなる最適化
        if ($fast_mode) {
            // 投稿保存時のフックを無効化
            remove_action('save_post', 'wp_save_post_revision', 10);
            remove_action('wp_insert_post', 'wp_transition_post_status', 10);
            remove_action('transition_post_status', 'wp_kses_post_data', 10);
            // WordPressの自動タクソノミー処理を無効化
            remove_action('wp_insert_post', '_wp_translate_postdata');
        }
        
        // キャッシュを事前にクリア
        wp_cache_flush();
        
        $current_user_id = get_current_user_id();
        $processed = 0;
        
        // 事前データ収集：全post_idを一括取得
        $all_post_ids = array();
        foreach ($csv_data as $row) {
            if (!empty($row[array_search('post_id', $headers)])) {
                $all_post_ids[] = intval($row[array_search('post_id', $headers)]);
            }
        }
        
        if (!empty($all_post_ids)) {
            global $wpdb;
            $post_ids_str = implode(',', array_unique($all_post_ids));
            $existing_posts = $wpdb->get_results(
                "SELECT ID FROM {$wpdb->posts} WHERE ID IN ({$post_ids_str})",
                ARRAY_A
            );
            foreach ($existing_posts as $post) {
                $existing_post_ids[intval($post['ID'])] = true;
            }
        }
        
        foreach ($csv_data as $row_index => $row) {
            $row_number = $row_index + 2; // ヘッダー行を考慮
            
            try {
                // 空行をスキップ
                if (empty(array_filter($row))) {
                    $results['skipped']++;
                    continue;
                }
                
                // データを連想配列に変換
                $data = array();
                foreach ($headers as $index => $header) {
                    $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                
                // 必須フィールドのチェックを簡略化（post_titleが空でも処理継続）
                // 空のpost_titleの場合はタイトルなしで保存
                
                // 投稿データを準備（高速モード適用）
                $post_data = scv_prepare_post_data($data, $current_user_id, $fast_mode);
                
                // 既存投稿の上書きチェック（高速化版、空データ上書き対応）
                $is_update = false;
                if (!empty($data['post_id'])) {
                    $post_id_int = intval($data['post_id']);
                    if (isset($existing_post_ids[$post_id_int])) {
                        // 既存投稿が存在する場合は上書き
                        $post_data['ID'] = $post_id_int;
                        $is_update = true;
                    }
                    // 存在しない場合は新規作成（IDは自動採番）
                }
                
                // 投稿を作成/更新
                $post_id = wp_insert_post($post_data, true);
                
                if (is_wp_error($post_id)) {
                    throw new Exception($post_id->get_error_message());
                }
                
                // メタデータとタクソノミーの設定（空データ上書きオプション対応）
                scv_set_post_metadata($post_id, $data, $overwrite_empty, $is_update);
                scv_set_post_taxonomies($post_id, $data);
                
                // サムネイル画像の設定
                if (!empty($data['post_thumbnail'])) {
                    try {
                        $thumbnail_result = scv_set_post_thumbnail($post_id, $data['post_thumbnail']);
                        if ($thumbnail_result === false) {
                            $results['error_messages'][] = "行 {$row_number}: サムネイル画像の設定に失敗しました: " . $data['post_thumbnail'];
                        }
                    } catch (Exception $e) {
                        $results['error_messages'][] = "行 {$row_number}: サムネイル画像処理エラー: " . $e->getMessage();
                    }
                }
                
                $results['success']++;
                
            } catch (Exception $e) {
                $error_message = "行 {$row_number}: " . $e->getMessage();
                $results['error_messages'][] = $error_message;
                $results['errors']++;
                
                // エラーが発生した場合は処理を停止
                break;
            }
            
            $processed++;
            
            // バッチ処理でメモリ使用量を制御（高速モードでは頻度を下げる）
            $clear_frequency = $fast_mode ? max(20, intval($batch_size / 2)) : max(10, intval($batch_size / 5));
            if ($processed % $clear_frequency === 0) {
                wp_cache_flush();
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                // プログレス表示用（オプション）
                if (defined('WP_DEBUG') && WP_DEBUG && !$fast_mode) {
                    error_log("SCV Import Progress: {$processed}/" . count($csv_data) . " processed");
                }
            }
        }
        
        // WordPress処理を元に戻す
        add_action('pre_post_update', 'wp_save_post_revision');
        remove_filter('wp_revisions_to_keep', '__return_zero');
        if (!$original_doing_it_wrong_triggered) {
            add_action('doing_it_wrong_run', 'doing_it_wrong_run');
        }
        
        // 高速モード時に無効化したフックを復元
        if ($fast_mode) {
            add_action('save_post', 'wp_save_post_revision', 10);
            add_action('wp_insert_post', 'wp_transition_post_status', 10, 3);
            add_action('transition_post_status', 'wp_kses_post_data', 10, 3);
            add_action('wp_insert_post', '_wp_translate_postdata');
        }
        
        // 最終キャッシュクリア
        wp_cache_flush();
        
        return $results;
    }
}

// 投稿データを準備する関数（高速モード対応）
if (!function_exists('scv_prepare_post_data')) {
    function scv_prepare_post_data($data, $default_author_id, $fast_mode = false) {
        if ($fast_mode) {
            // 高速モード: 最低限の検証のみ（空のデータも保存）
            $post_data = array(
                'post_title'    => $data['post_title'] ?? '', // 空でも保存
                'post_content'  => $data['post_content'] ?? '', // wp_kses_post省略
                'post_excerpt'  => $data['post_excerpt'] ?? '',
                'post_status'   => $data['post_status'] ?? 'draft',
                'post_type'     => $data['post_type'] ?? 'post',
                'post_author'   => intval($data['post_author'] ?? $default_author_id),
                'menu_order'    => intval($data['menu_order'] ?? 0),
                'post_password' => substr($data['post_password'] ?? '', 0, 20),
            );
            
            // スラッグの設定（簡略版）
            if (!empty($data['post_name'])) {
                $post_data['post_name'] = $data['post_name']; // sanitize_title省略
            }
            
            // 日付の設定（簡略版）
            if (!empty($data['post_date'])) {
                $post_data['post_date'] = $data['post_date'];
            }
            
            return $post_data;
        }
        
        // 標準モード: 必要な検証を実行（空のデータも保存）
        $post_data = array(
            'post_title'    => sanitize_text_field($data['post_title'] ?? ''), // 空でも保存
            'post_content'  => $data['post_content'] ?? '', // wp_kses_postを省略して高速化
            'post_excerpt'  => sanitize_textarea_field($data['post_excerpt'] ?? ''),
            'post_status'   => sanitize_text_field($data['post_status'] ?? 'draft'),
            'post_type'     => sanitize_text_field($data['post_type'] ?? 'post'),
            'post_author'   => intval($data['post_author'] ?? $default_author_id),
            'menu_order'    => intval($data['menu_order'] ?? 0),
            'post_password' => sanitize_text_field(substr($data['post_password'] ?? '', 0, 20)),
        );
        
        // スラッグの設定
        if (!empty($data['post_name'])) {
            $post_data['post_name'] = sanitize_title($data['post_name']);
        }
        
        // 日付の設定
        if (!empty($data['post_date'])) {
            $post_date = date('Y-m-d H:i:s', strtotime($data['post_date']));
            if ($post_date && $post_date !== '1970-01-01 00:00:00') {
                $post_data['post_date'] = $post_date;
                $post_data['post_date_gmt'] = get_gmt_from_date($post_date);
            }
        }
        
        // 投稿ステータスの簡略検証（高速化）
        static $valid_statuses = array('publish', 'draft', 'private', 'pending', 'future');
        if (!in_array($post_data['post_status'], $valid_statuses)) {
            $post_data['post_status'] = 'draft';
        }
        
        // 投稿タイプの検証を簡略化（post_type_existsをスキップ）
        static $common_post_types = array('post', 'page');
        if (!in_array($post_data['post_type'], $common_post_types)) {
            // 一般的でない投稿タイプの場合のみチェック
            if (!post_type_exists($post_data['post_type'])) {
                $post_data['post_type'] = 'post';
            }
        }
        
        // 作成者の検証を簡略化（高速キャッシュ）
        static $user_cache = array();
        $author_id = $post_data['post_author'];
        if ($author_id !== $default_author_id) {
            if (!isset($user_cache[$author_id])) {
                $user_cache[$author_id] = ($author_id === 1 || get_userdata($author_id) !== false);
            }
            if (!$user_cache[$author_id]) {
                $post_data['post_author'] = $default_author_id;
            }
        }
        
        return $post_data;
    }
}

// 投稿のメタデータを設定する関数（空データ上書きオプション対応）
if (!function_exists('scv_set_post_metadata')) {
    function scv_set_post_metadata($post_id, $data, $overwrite_empty = false, $is_update = false) {
        // 標準フィールド以外をカスタムフィールドとして処理
        $standard_fields = array(
            'post_id', 'post_name', 'post_author', 'post_date', 'post_content',
            'post_title', 'post_excerpt', 'post_status', 'post_password',
            'menu_order', 'post_type', 'post_thumbnail', 'post_category',
            'post_tags'
        );
        
        foreach ($data as $key => $value) {
            // 標準フィールドとタクソノミーフィールドはスキップ
            if (in_array($key, $standard_fields) || strpos($key, 'tax_') === 0) {
                continue;
            }
            
            // 空データ上書き処理の判定
            if ($is_update && !$overwrite_empty && $value === '') {
                // 更新時で空データ上書きがオフの場合、空の値は既存データを保持（何もしない）
                continue;
            }
            
            // カスタムフィールドとして保存（空の値も含めて保存、サニタイズ簡略化）
            update_post_meta($post_id, $key, $value); // sanitize_keyとsanitize_text_fieldを省略
        }
    }
}

// 投稿のタクソノミーを設定する関数（高速化版）
if (!function_exists('scv_set_post_taxonomies')) {
    function scv_set_post_taxonomies($post_id, $data) {
        // 静的キャッシュを使用してパフォーマンスを向上
        static $category_cache = array();
        static $term_cache = array();
        
        // カテゴリーの設定
        if (!empty($data['post_category'])) {
            $categories = array_map('trim', explode(',', $data['post_category']));
            $category_ids = array();
            
            foreach ($categories as $category_slug) {
                if (!isset($category_cache[$category_slug])) {
                    $category = get_category_by_slug($category_slug);
                    if (!$category) {
                        // カテゴリーが存在しない場合は作成
                        $category_id = wp_create_category($category_slug);
                        $category_cache[$category_slug] = !is_wp_error($category_id) ? $category_id : false;
                    } else {
                        $category_cache[$category_slug] = $category->term_id;
                    }
                }
                
                if ($category_cache[$category_slug] !== false) {
                    $category_ids[] = $category_cache[$category_slug];
                }
            }
            
            if (!empty($category_ids)) {
                wp_set_post_categories($post_id, $category_ids, false); // appendオプションをfalseに
            }
        }
        
        // タグの設定（WordPressの最適化されたAPIを使用）
        if (!empty($data['post_tags'])) {
            $tags = array_map('trim', explode(',', $data['post_tags']));
            wp_set_post_tags($post_id, $tags, false); // appendオプションをfalseに
        }
        
        // カスタムタクソノミーの設定
        foreach ($data as $key => $value) {
            if (strpos($key, 'tax_') === 0 && !empty($value)) {
                $taxonomy = substr($key, 4);
                
                // タクソノミーキャッシュをチェック
                $taxonomy_key = "taxonomy_exists_{$taxonomy}";
                if (!isset($term_cache[$taxonomy_key])) {
                    $term_cache[$taxonomy_key] = taxonomy_exists($taxonomy);
                }
                
                if ($term_cache[$taxonomy_key]) {
                    $terms = array_map('trim', explode(',', $value));
                    $term_ids = array();
                    
                    foreach ($terms as $term_slug) {
                        $cache_key = "{$taxonomy}_{$term_slug}";
                        if (!isset($term_cache[$cache_key])) {
                            $term = get_term_by('slug', $term_slug, $taxonomy);
                            if (!$term) {
                                $term_data = wp_insert_term($term_slug, $taxonomy);
                                $term_cache[$cache_key] = !is_wp_error($term_data) ? $term_data['term_id'] : false;
                            } else {
                                $term_cache[$cache_key] = $term->term_id;
                            }
                        }
                        
                        if ($term_cache[$cache_key] !== false) {
                            $term_ids[] = $term_cache[$cache_key];
                        }
                    }
                    
                    if (!empty($term_ids)) {
                        wp_set_object_terms($post_id, $term_ids, $taxonomy, false); // appendオプションをfalseに
                    }
                }
            }
        }
    }
}

// 投稿のサムネイル画像を設定する関数（高速化版）
if (!function_exists('scv_set_post_thumbnail')) {
    function scv_set_post_thumbnail($post_id, $thumbnail_url) {
        // 空のURLや無効なURLをチェック
        if (empty($thumbnail_url) || !filter_var($thumbnail_url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        try {
            // 高速な既存画像チェック（attachment_url_to_postidより高速）
            static $attachment_cache = array();
            
            if (!isset($attachment_cache[$thumbnail_url])) {
                global $wpdb;
                $attachment_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
                    '%' . basename($thumbnail_url)
                ));
                
                // さらに詳細チェック
                if (!$attachment_id) {
                    $attachment_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s LIMIT 1",
                        '%' . basename($thumbnail_url) . '%'
                    ));
                }
                
                $attachment_cache[$thumbnail_url] = $attachment_id ? intval($attachment_id) : false;
            }
            
            if ($attachment_cache[$thumbnail_url]) {
                // 既存の画像を使用
                return set_post_thumbnail($post_id, $attachment_cache[$thumbnail_url]) ? $attachment_cache[$thumbnail_url] : false;
            } else {
                // 新しい画像をダウンロード（バックグラウンドでも可能）
                $upload_result = scv_download_and_attach_image($thumbnail_url, $post_id);
                if ($upload_result && !is_wp_error($upload_result)) {
                    $attachment_cache[$thumbnail_url] = $upload_result; // キャッシュに保存
                    return set_post_thumbnail($post_id, $upload_result) ? $upload_result : false;
                }
                return false;
            }
        } catch (Exception $e) {
            error_log('SCV: Exception in scv_set_post_thumbnail: ' . $e->getMessage());
            return false;
        }
    }
}

// 画像をダウンロードしてメディアライブラリに追加する関数（高速化版）
if (!function_exists('scv_download_and_attach_image')) {
    function scv_download_and_attach_image($image_url, $post_id) {
        // URLの検証
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', '無効なURLです: ' . $image_url);
        }
        
        // ファイルサイズの事前チェック（大きすぎる画像をスキップ）
        $headers = @get_headers($image_url, true);
        if ($headers && isset($headers['Content-Length'])) {
            $file_size = is_array($headers['Content-Length']) ? end($headers['Content-Length']) : $headers['Content-Length'];
            if ($file_size > 10 * 1024 * 1024) { // 10MB以上はスキップ
                return new WP_Error('file_too_large', 'ファイルサイズが大きすぎます: ' . $image_url);
            }
        }
        
        try {
            // 必要なファイルをインクルード（静的チェック）
            static $includes_loaded = false;
            if (!$includes_loaded) {
                if (!function_exists('media_handle_sideload')) {
                    require_once(ABSPATH . 'wp-admin/includes/media.php');
                }
                if (!function_exists('download_url')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                if (!function_exists('wp_generate_attachment_metadata')) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                }
                $includes_loaded = true;
            }
            
            // 画像をダウンロード
            $temp_file = download_url($image_url);
            if (is_wp_error($temp_file)) {
                error_log('SCV: Failed to download image ' . $image_url . ': ' . $temp_file->get_error_message());
                return $temp_file;
            }
            
            // ファイルが正常にダウンロードされたかチェック
            if (!file_exists($temp_file) || filesize($temp_file) == 0) {
                if (file_exists($temp_file)) {
                    wp_delete_file($temp_file);
                }
                error_log('SCV: Downloaded file is empty or missing: ' . $image_url);
                return new WP_Error('empty_file', 'ダウンロードしたファイルが空です');
            }
            
            // ファイル情報を取得
            $file_info = pathinfo($image_url);
            $filename = sanitize_file_name(basename($image_url));
            
            // ファイル名が空の場合のフォールバック
            if (empty($filename)) {
                $filename = 'image_' . wp_generate_uuid4() . '.jpg';
            }
            
            // アップロード処理
            $file_array = array(
                'name' => $filename,
                'tmp_name' => $temp_file,
            );
            
            // メディアライブラリに追加
            $attachment_id = media_handle_sideload($file_array, $post_id);
            
            // 一時ファイルを安全に削除
            if (file_exists($temp_file)) {
                wp_delete_file($temp_file);
            }
            
            if (is_wp_error($attachment_id)) {
                error_log('SCV: Failed to create attachment for ' . $image_url . ': ' . $attachment_id->get_error_message());
                return $attachment_id;
            }
            
            error_log('SCV: Successfully created attachment ' . $attachment_id . ' for image ' . $image_url);
            return $attachment_id;
            
        } catch (Exception $e) {
            error_log('SCV: Exception in scv_download_and_attach_image: ' . $e->getMessage());
            return new WP_Error('exception', 'エラーが発生しました: ' . $e->getMessage());
        }
    }
}

// CSVエクスポート処理
if (!function_exists('scv_process_csv_export')) {
    function scv_process_csv_export() {
        // nonce チェック
        if (!wp_verify_nonce($_POST['csv_export_nonce'], 'csv_export_action')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        $post_type = sanitize_text_field($_POST['export_post_type']);
        $post_status = sanitize_text_field($_POST['export_status']);
        
        // 自動制限を計算
        $auto_limit = scv_calculate_optimal_export_limit($post_type, $post_status);
        
        // クエリ引数を設定
        $args = array(
            'posts_per_page' => $auto_limit,
            'post_type' => $post_type === 'all' ? 'any' : $post_type,
            'post_status' => $post_status === 'all' ? 'any' : $post_status,
            'orderby' => 'ID',
            'order' => 'ASC',
            'meta_query' => array(),
            'tax_query' => array(),
        );
        
        // 投稿を取得
        $posts = get_posts($args);
        
        if (empty($posts)) {
            add_action('admin_notices', function() use ($auto_limit) {
                echo '<div class="notice notice-warning"><p>エクスポートする投稿が見つかりませんでした。（自動制限: ' . number_format($auto_limit) . '件）</p></div>';
            });
            return;
        }
        
        // CSV データを生成
        $csv_data = scv_generate_csv_data($posts);
        
        // エクスポート件数をログに記録
        $exported_count = count($posts);
        error_log(sprintf('CSV Export completed: %d posts exported (auto limit: %d)', $exported_count, $auto_limit));
        
        // CSV ファイルをダウンロード
        scv_download_csv($csv_data, $post_type, $post_status);
    }
}

// CSV データを生成する関数
if (!function_exists('scv_generate_csv_data')) {
    function scv_generate_csv_data($posts) {
        $csv_data = array();
        
        // ヘッダー行を設定
        $headers = array(
            'post_id', 'post_name', 'post_author', 'post_date', 'post_content',
            'post_title', 'post_excerpt', 'post_status', 'post_password',
            'menu_order', 'post_type', 'post_thumbnail', 'post_category', 'post_tags'
        );
        
        // カスタムフィールドとタクソノミーを収集
        $custom_fields = array();
        $taxonomies = array();
        
        foreach ($posts as $post) {
            // カスタムフィールドを収集
            $meta_keys = get_post_meta($post->ID);
            foreach ($meta_keys as $key => $values) {
                if (!in_array($key, $headers) && strpos($key, '_') !== 0) {
                    $custom_fields[$key] = true;
                }
            }
            
            // タクソノミーを収集
            $post_taxonomies = get_object_taxonomies($post->post_type);
            foreach ($post_taxonomies as $taxonomy) {
                if (!in_array($taxonomy, array('category', 'post_tag'))) {
                    $tax_key = 'tax_' . $taxonomy;
                    $taxonomies[$tax_key] = true;
                }
            }
        }
        
        // ヘッダーにカスタムフィールドとタクソノミーを追加
        $headers = array_merge($headers, array_keys($taxonomies), array_keys($custom_fields));
        $csv_data[] = $headers;
        
        // 各投稿のデータを生成
        foreach ($posts as $post) {
            $row = array();
            
            foreach ($headers as $header) {
                switch ($header) {
                    case 'post_id':
                        $row[] = $post->ID;
                        break;
                    case 'post_name':
                        $row[] = $post->post_name;
                        break;
                    case 'post_author':
                        $row[] = $post->post_author;
                        break;
                    case 'post_date':
                        $row[] = $post->post_date;
                        break;
                    case 'post_content':
                        $row[] = $post->post_content;
                        break;
                    case 'post_title':
                        $row[] = $post->post_title;
                        break;
                    case 'post_excerpt':
                        $row[] = $post->post_excerpt;
                        break;
                    case 'post_status':
                        $row[] = $post->post_status;
                        break;
                    case 'post_password':
                        $row[] = $post->post_password;
                        break;
                    case 'menu_order':
                        $row[] = $post->menu_order;
                        break;
                    case 'post_type':
                        $row[] = $post->post_type;
                        break;
                    case 'post_thumbnail':
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        $row[] = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
                        break;
                    case 'post_category':
                        $categories = get_the_category($post->ID);
                        $category_slugs = array();
                        foreach ($categories as $category) {
                            $category_slugs[] = $category->slug;
                        }
                        $row[] = implode(',', $category_slugs);
                        break;
                    case 'post_tags':
                        $tags = get_the_tags($post->ID);
                        $tag_slugs = array();
                        if ($tags) {
                            foreach ($tags as $tag) {
                                $tag_slugs[] = $tag->slug;
                            }
                        }
                        $row[] = implode(',', $tag_slugs);
                        break;
                    default:
                        if (strpos($header, 'tax_') === 0) {
                            // カスタムタクソノミー
                            $taxonomy = substr($header, 4);
                            $terms = get_the_terms($post->ID, $taxonomy);
                            $term_slugs = array();
                            if ($terms && !is_wp_error($terms)) {
                                foreach ($terms as $term) {
                                    $term_slugs[] = $term->slug;
                                }
                            }
                            $row[] = implode(',', $term_slugs);
                        } else {
                            // カスタムフィールド
                            $meta_value = get_post_meta($post->ID, $header, true);
                            $row[] = is_array($meta_value) ? serialize($meta_value) : $meta_value;
                        }
                        break;
                }
            }
            
            $csv_data[] = $row;
        }
        
        return $csv_data;
    }
}

// CSV ファイルをダウンロードする関数
if (!function_exists('scv_download_csv')) {
    function scv_download_csv($csv_data, $post_type, $post_status) {
        $filename = sanitize_file_name('wordpress_export_' . $post_type . '_' . $post_status . '_' . date('Y-m-d_H-i-s') . '.csv');
        
        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // HTTP ヘッダーを設定
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // BOM を追加（Excel での文字化け防止）
        echo "\xEF\xBB\xBF";
        
        // CSV データを出力
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        
        exit;
    }
}

// サンプルCSVファイルをダウンロードする関数
if (!function_exists('scv_download_sample_csv')) {
    function scv_download_sample_csv() {
        // nonce チェック
        if (!wp_verify_nonce($_POST['sample_csv_nonce'], 'sample_csv_download')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        // サンプルCSVデータを生成
        $sample_data = array(
            // ヘッダー行
            array(
                'post_id', 'post_name', 'post_author', 'post_date', 'post_content',
                'post_title', 'post_excerpt', 'post_status', 'post_password',
                'menu_order', 'post_type', 'post_thumbnail', 'post_category', 'post_tags',
                'tax_product_category', 'price', 'description'
            ),
            // サンプルデータ1（新規投稿）
            array(
                '', 'sample-post-1', '1', '2024-01-15 10:00:00',
                '<p>これは最初のサンプル記事です。<strong>HTMLタグ</strong>も使用できます。</p><p>複数の段落を含むことができます。</p>',
                'サンプル記事1', 'これは記事の抜粋文です。',
                'publish', '', '0', 'post',
                'https://example.com/images/sample1.jpg',
                'sample-category,news', 'サンプル,記事,投稿',
                'category-a,category-b', '1980', '商品の詳細説明'
            ),
            // サンプルデータ2（下書き投稿）
            array(
                '', 'sample-post-2', '1', '2024-01-16 14:30:00',
                '<p>これは2番目のサンプル記事です。</p><ul><li>リスト項目1</li><li>リスト項目2</li></ul>',
                'サンプル記事2', '下書きの記事例です。',
                'draft', '', '1', 'post',
                'https://example.com/images/sample2.jpg',
                'category1,category2', 'tag1,tag2,tag3',
                'category-c', '2500', '別の商品説明'
            ),
            // サンプルデータ3（固定ページ）
            array(
                '', 'about-us', '1', '2024-01-17 09:15:00',
                '<p>私たちについてのページです。</p><p>企業情報や沿革などを記載します。</p>',
                '私たちについて', '企業情報ページ',
                'publish', '', '0', 'page',
                '',
                '', '',
                '', '', ''
            ),
            // サンプルデータ4（更新例 - 既存のpost_idを指定）
            array(
                '123', 'updated-post', '1', '2024-01-18 16:45:00',
                '<p>既存の投稿を更新する例です。post_idを指定することで既存記事を更新できます。</p>',
                '更新されたサンプル記事', '更新された抜粋文',
                'publish', '', '2', 'post',
                'https://example.com/images/updated.jpg',
                'updated-category', 'updated,sample',
                'category-updated', '3000', '更新された商品情報'
            ),
            // サンプルデータ5（パスワード付き投稿）
            array(
                '', 'private-post', '1', '2024-01-19 11:20:00',
                '<p>パスワードで保護された記事の例です。</p>',
                'プライベート記事', 'パスワード保護されています。',
                'publish', 'secret123', '0', 'post',
                '',
                'private', 'private,password',
                '', '5000', 'プライベート商品'
            )
        );
        
        $filename = sanitize_file_name('wordpress_import_sample_' . date('Y-m-d_H-i-s') . '.csv');
        
        // 出力バッファをクリア
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // HTTP ヘッダーを設定
        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        // BOM を追加（Excel での文字化け防止）
        echo "\xEF\xBB\xBF";
        
        // CSV データを出力
        $output = fopen('php://output', 'w');
        foreach ($sample_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        
        exit;
    }
}

// CSVテスト処理
if (!function_exists('scv_process_csv_test')) {
    function scv_process_csv_test() {
        // nonce チェック
        if (!wp_verify_nonce($_POST['csv_test_nonce'], 'csv_test_action')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません。');
        }
        
        // ファイルアップロードのセキュリティチェック
        if (!isset($_FILES['test_csv_file']) || !is_uploaded_file($_FILES['test_csv_file']['tmp_name'])) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>適切なファイルがアップロードされていません。</p></div>';
            });
            return;
        }
        
        $uploaded_file = $_FILES['test_csv_file'];
        $detailed_check = isset($_POST['detailed_check']);
        $validate_urls = isset($_POST['validate_urls']);
        
        // ファイルサイズチェック（10MB以下）
        if ($uploaded_file['size'] > 10 * 1024 * 1024) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>ファイルサイズが大きすぎます（10MB以下にしてください）。</p></div>';
            });
            return;
        }
        
        // MIMEタイプチェック
        $allowed_types = array('text/csv', 'text/plain', 'application/csv', 'application/excel');
        $file_type = wp_check_filetype($uploaded_file['name']);
        
        if (!in_array($file_type['type'], $allowed_types) && $file_type['ext'] !== 'csv') {
            add_action('admin_notices', function() use ($file_type) {
                $error_message = '許可されていないファイルタイプです';
                if (!empty($file_type['type'])) {
                    $error_message .= '：' . esc_html($file_type['type']);
                }
                echo '<div class="notice notice-error"><p>' . $error_message . '</p></div>';
            });
            return;
        }
        
        // ファイルのバリデーション
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>ファイルのアップロードに失敗しました。</p></div>';
            });
            return;
        }
        
        // ファイルタイプチェック
        $file_info = pathinfo($uploaded_file['name']);
        if (strtolower($file_info['extension']) !== 'csv') {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルのみアップロード可能です。</p></div>';
            });
            return;
        }
        
        // ファイル情報を取得
        $file_analysis = scv_analyze_csv_file($uploaded_file['tmp_name']);
        
        if ($file_analysis === false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルの解析に失敗しました。</p></div>';
            });
            return;
        }
        
        // CSVファイルの読み込み
        $csv_data = scv_read_csv_file($uploaded_file['tmp_name']);
        if ($csv_data === false) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>CSVファイルの読み込みに失敗しました。UTF-8エンコーディングで保存されていることを確認してください。</p></div>';
            });
            return;
        }
        
        if (empty($csv_data)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>CSVファイルが空です。</p></div>';
            });
            return;
        }
        
        // ヘッダー行を取得
        $headers = array_shift($csv_data);
        $headers = array_map('trim', $headers);
        
        // データ検証
        $validation_results = array();
        if ($detailed_check) {
            $validation_results = scv_validate_csv_data($csv_data, $headers, $validate_urls);
        }
        
        // 上部に成功/警告メッセージを表示
        add_action('admin_notices', function() use ($file_analysis, $validation_results, $detailed_check) {
            scv_display_csv_test_summary($file_analysis, $validation_results, $detailed_check);
        });
        
        // 詳細結果をグローバル変数に保存（後で下部に表示するため）
        global $scv_test_results;
        $scv_test_results = array(
            'file_analysis' => $file_analysis,
            'headers' => $headers,
            'csv_data' => $csv_data,
            'validation_results' => $validation_results,
            'detailed_check' => $detailed_check
        );
    }
}

// CSVファイルを解析する関数
if (!function_exists('scv_analyze_csv_file')) {
    function scv_analyze_csv_file($file_path) {
        // WordPress Filesystem APIを初期化
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        WP_Filesystem();
        global $wp_filesystem;
        
        if (!$wp_filesystem->exists($file_path)) {
            return false;
        }
        
        $file_size = $wp_filesystem->size($file_path);
        $content = $wp_filesystem->get_contents($file_path);
        
        if ($content === false) {
            return false;
        }
        
        // エンコーディングを検出
        $encoding = 'Unknown';
        $encodings = array('UTF-8', 'UTF-16', 'UTF-32', 'SJIS-win', 'EUC-JP', 'JIS', 'ASCII', 'ISO-8859-1');
        
        foreach ($encodings as $enc) {
            if (mb_check_encoding($content, $enc)) {
                $encoding = $enc;
                break;
            }
        }
        
        // BOMの検出
        $has_bom = false;
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $has_bom = true;
        }
        
        // 改行コードの検出
        $line_ending = 'Unknown';
        if (strpos($content, "\r\n") !== false) {
            $line_ending = 'CRLF (Windows)';
        } elseif (strpos($content, "\n") !== false) {
            $line_ending = 'LF (Unix/Linux/Mac)';
        } elseif (strpos($content, "\r") !== false) {
            $line_ending = 'CR (Classic Mac)';
        }
        
        // 行数をカウント
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $total_lines = count($lines);
        if (end($lines) === '') {
            $total_lines--;
        }
        
        // ファイル形式の推定
        $delimiter = ',';
        if (strpos($content, ';') > strpos($content, ',') && strpos($content, ';') !== false) {
            $delimiter = ';';
        } elseif (strpos($content, "\t") > strpos($content, ',') && strpos($content, "\t") !== false) {
            $delimiter = "\t";
        }
        
        return array(
            'file_size' => $file_size,
            'encoding' => $encoding,
            'has_bom' => $has_bom,
            'line_ending' => $line_ending,
            'total_lines' => $total_lines,
            'delimiter' => $delimiter,
            'content_preview' => substr($content, 0, 1000)
        );
    }
}

// CSVデータを検証する関数
if (!function_exists('scv_validate_csv_data')) {
    function scv_validate_csv_data($csv_data, $headers, $validate_urls = false) {
        $results = array(
            'total_rows' => count($csv_data),
            'valid_rows' => 0,
            'errors' => array(),
            'warnings' => array(),
            'field_stats' => array()
        );
        
        // 必須フィールドのチェック
        $required_fields = array('post_title');
        $missing_required = array_diff($required_fields, $headers);
        
        if (!empty($missing_required)) {
            $results['errors'][] = '必須フィールドが不足: ' . implode(', ', $missing_required);
        }
        
        // 各行のデータを検証
        foreach ($csv_data as $row_index => $row) {
            $row_number = $row_index + 2; // ヘッダー行を考慮
            $row_errors = array();
            $row_warnings = array();
            
            // 空行のチェック
            if (empty(array_filter($row))) {
                $row_warnings[] = "行 {$row_number}: 空行です";
                continue;
            }
            
            // データを連想配列に変換
            $data = array();
            foreach ($headers as $index => $header) {
                $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            // フィールド統計を更新
            foreach ($data as $field => $value) {
                if (!isset($results['field_stats'][$field])) {
                    $results['field_stats'][$field] = array(
                        'empty_count' => 0,
                        'total_count' => 0,
                        'sample_values' => array()
                    );
                }
                
                $results['field_stats'][$field]['total_count']++;
                
                if (empty($value)) {
                    $results['field_stats'][$field]['empty_count']++;
                } else {
                    if (count($results['field_stats'][$field]['sample_values']) < 3) {
                        $results['field_stats'][$field]['sample_values'][] = $value;
                    }
                }
            }
            
            // 詳細検証
            $validation_result = scv_check_csv_row($data, $row_number, $validate_urls);
            $row_errors = array_merge($row_errors, $validation_result['errors']);
            $row_warnings = array_merge($row_warnings, $validation_result['warnings']);
            
            if (empty($row_errors)) {
                $results['valid_rows']++;
            }
            
            $results['errors'] = array_merge($results['errors'], $row_errors);
            $results['warnings'] = array_merge($results['warnings'], $row_warnings);
        }
        
        return $results;
    }
}

// CSV行を検証する関数
if (!function_exists('scv_check_csv_row')) {
    function scv_check_csv_row($data, $row_number, $validate_urls = false) {
        $errors = array();
        $warnings = array();
        
        // post_titleの検証
        if (empty($data['post_title'])) {
            $errors[] = "行 {$row_number}: post_title が空です";
        } elseif (strlen($data['post_title']) > 255) {
            $warnings[] = "行 {$row_number}: post_title が長すぎます (255文字以下推奨)";
        }
        
        // post_idの検証
        if (!empty($data['post_id'])) {
            if (!is_numeric($data['post_id'])) {
                $errors[] = "行 {$row_number}: post_id は数値である必要があります";
            } else {
                $post_id = intval($data['post_id']);
                if (!get_post($post_id)) {
                    $warnings[] = "行 {$row_number}: 指定されたpost_id の投稿が見つかりません: {$post_id}";
                }
            }
        }
        
        // post_authorの検証
        if (!empty($data['post_author'])) {
            if (!is_numeric($data['post_author'])) {
                $errors[] = "行 {$row_number}: post_author は数値である必要があります";
            } else {
                $author_id = intval($data['post_author']);
                if (!get_userdata($author_id)) {
                    $errors[] = "行 {$row_number}: 指定されたpost_author のユーザーが見つかりません: {$author_id}";
                }
            }
        }
        
        // post_dateの検証
        if (!empty($data['post_date'])) {
            $timestamp = strtotime($data['post_date']);
            if ($timestamp === false || $timestamp === -1) {
                $errors[] = "行 {$row_number}: post_date の形式が正しくありません";
            }
        }
        
        // post_statusの検証
        if (!empty($data['post_status'])) {
            $valid_statuses = array('publish', 'draft', 'private', 'pending', 'future', 'trash');
            if (!in_array($data['post_status'], $valid_statuses)) {
                $warnings[] = "行 {$row_number}: post_status '{$data['post_status']}' は標準的ではありません";
            }
        }
        
        // post_typeの検証
        if (!empty($data['post_type'])) {
            if (!post_type_exists($data['post_type'])) {
                $errors[] = "行 {$row_number}: 投稿タイプ '{$data['post_type']}' が存在しません";
            }
        }
        
        // post_passwordの検証
        if (!empty($data['post_password']) && strlen($data['post_password']) > 20) {
            $warnings[] = "行 {$row_number}: post_password が長すぎます (20文字以下推奨)";
        }
        
        // menu_orderの検証
        if (!empty($data['menu_order']) && !is_numeric($data['menu_order'])) {
            $errors[] = "行 {$row_number}: menu_order は数値である必要があります";
        }
        
        // post_thumbnailの検証
        if (!empty($data['post_thumbnail'])) {
            if (!filter_var($data['post_thumbnail'], FILTER_VALIDATE_URL)) {
                $errors[] = "行 {$row_number}: post_thumbnail は有効なURLである必要があります";
            } elseif ($validate_urls) {
                // URL の有効性をチェック（時間がかかる可能性があるため、オプション）
                $headers = @get_headers($data['post_thumbnail'], 1);
                if (!$headers || strpos($headers[0], '200') === false) {
                    $warnings[] = "行 {$row_number}: post_thumbnail のURLにアクセスできません: {$data['post_thumbnail']}";
                }
            }
        }
        
        // HTMLコンテンツの基本チェック
        if (!empty($data['post_content'])) {
            // HTMLタグの基本的な妥当性チェック
            if (strip_tags($data['post_content']) !== $data['post_content']) {
                // HTMLタグが含まれている場合
                $allowed_tags = wp_kses_allowed_html('post');
                $cleaned_content = wp_kses($data['post_content'], $allowed_tags);
                if ($cleaned_content !== $data['post_content']) {
                    $warnings[] = "行 {$row_number}: post_content に許可されていないHTMLタグが含まれています";
                }
            }
        }
        
        return array(
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
}

// CSVテスト結果のサマリーを表示する関数（上部通知用）
if (!function_exists('scv_display_csv_test_summary')) {
    function scv_display_csv_test_summary($file_analysis, $validation_results, $detailed_check) {
        $has_errors = $detailed_check && !empty($validation_results) && !empty($validation_results['errors']);
        $has_warnings = $detailed_check && !empty($validation_results) && !empty($validation_results['warnings']);
        
        if ($has_errors) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>CSVファイルにエラーが見つかりました</strong></p>';
            echo '<p>エラー: ' . count($validation_results['errors']) . '件';
            if ($has_warnings) {
                echo ' | 警告: ' . count($validation_results['warnings']) . '件';
            }
            echo ' | エンコーディング: ' . $file_analysis['encoding'];
            echo '</p>';
            echo '</div>';
        } elseif ($has_warnings) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>CSVファイルに警告があります</strong></p>';
            echo '<p>警告: ' . count($validation_results['warnings']) . '件';
            echo ' | エンコーディング: ' . $file_analysis['encoding'];
            if ($detailed_check && !empty($validation_results)) {
                echo ' | 有効行数: ' . $validation_results['valid_rows'] . '/' . $validation_results['total_rows'] . '行';
            }
            echo '</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-success">';
            echo '<p><strong>CSVファイルのテストが完了しました</strong></p>';
            echo '<p>';
            if ($detailed_check && !empty($validation_results)) {
                echo '検証結果: 問題なし | ';
            }
            echo 'エンコーディング: ' . $file_analysis['encoding'];
            if ($detailed_check && !empty($validation_results)) {
                echo ' | データ行数: ' . $validation_results['total_rows'] . '行';
            }
            echo '</p>';
            echo '</div>';
        }
    }
}

// CSVテスト結果の詳細を表示する関数（下部詳細用）
if (!function_exists('scv_display_csv_test_details')) {
    function scv_display_csv_test_details($file_analysis, $headers, $csv_data, $validation_results, $detailed_check) {
        echo '<div style="margin-top: 30px;">';
        echo '<h3>詳細結果</h3>';
        
        // ファイル情報
        echo '<div class="postbox" style="margin: 15px 0;">';
        echo '<div class="inside" style="padding: 12px;">';
        echo '<h4 style="margin-top: 0;">ファイル情報</h4>';
        echo '<table class="widefat striped" style="margin: 0;">';
        echo '<tr><td style="width: 150px;"><strong>ファイルサイズ</strong></td><td>' . size_format($file_analysis['file_size']) . '</td></tr>';
        echo '<tr><td><strong>エンコーディング</strong></td><td>';
        if ($file_analysis['encoding'] === 'UTF-8') {
            echo '<span style="color: #46b450;">' . $file_analysis['encoding'] . '</span>';
        } else {
            echo '<span style="color: #dc3232;">' . $file_analysis['encoding'] . '</span>';
        }
        if ($file_analysis['has_bom']) {
            echo ' (BOM付き)';
        }
        echo '</td></tr>';
        echo '<tr><td><strong>総行数</strong></td><td>' . number_format($file_analysis['total_lines']) . ' 行</td></tr>';
        echo '<tr><td><strong>データ行数</strong></td><td>' . number_format(count($csv_data)) . ' 行</td></tr>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        // ヘッダー情報
        echo '<div class="postbox" style="margin: 15px 0;">';
        echo '<div class="inside" style="padding: 12px;">';
        echo '<h4 style="margin-top: 0;">列情報 (' . count($headers) . ' 列)</h4>';
        echo '<div style="line-height: 1.6;">';
        
        $standard_fields = array('post_id', 'post_name', 'post_author', 'post_date', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'menu_order', 'post_type', 'post_thumbnail', 'post_category', 'post_tags');
        
        foreach ($headers as $header) {
            $is_standard = in_array($header, $standard_fields);
            $is_taxonomy = strpos($header, 'tax_') === 0;
            
            if ($is_standard) {
                echo '<span class="button button-small" style="margin: 2px; background: #46b450; color: white; border-color: #46b450;">' . esc_html($header) . '</span>';
            } elseif ($is_taxonomy) {
                echo '<span class="button button-small" style="margin: 2px; background: #0073aa; color: white; border-color: #0073aa;">' . esc_html($header) . '</span>';
            } else {
                echo '<span class="button button-small" style="margin: 2px;">' . esc_html($header) . '</span>';
            }
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // データプレビュー
        echo '<div class="postbox" style="margin: 15px 0;">';
        echo '<div class="inside" style="padding: 12px;">';
        echo '<h4 style="margin-top: 0;">データプレビュー (最初の5行)</h4>';
        echo '<div style="overflow-x: auto;">';
        echo '<table class="widefat striped" style="font-size: 12px;">';
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th style="padding: 8px; white-space: nowrap;">' . esc_html($header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        
        for ($i = 0; $i < min(5, count($csv_data)); $i++) {
            echo '<tr>';
            for ($j = 0; $j < count($headers); $j++) {
                $value = isset($csv_data[$i][$j]) ? $csv_data[$i][$j] : '';
                $display_value = strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value;
                echo '<td style="padding: 8px;" title="' . esc_attr($value) . '">' . esc_html($display_value) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        if (count($csv_data) > 5) {
            echo '<p style="margin: 10px 0 0 0; color: #666; font-style: italic;">他 ' . (count($csv_data) - 5) . ' 行のデータがあります</p>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // 検証結果
        if ($detailed_check && !empty($validation_results)) {
            $has_errors = !empty($validation_results['errors']);
            echo '<div class="postbox" style="margin: 15px 0;">';
            echo '<div class="inside" style="padding: 12px;">';
            echo '<h4 style="margin-top: 0;">データ検証結果</h4>';
            
            // 概要
            echo '<table class="widefat striped" style="margin-bottom: 15px;">';
            echo '<tr><td style="width: 150px;"><strong>総行数</strong></td><td>' . number_format($validation_results['total_rows']) . ' 行</td></tr>';
            echo '<tr><td><strong>有効行数</strong></td><td style="color: #46b450; font-weight: bold;">' . number_format($validation_results['valid_rows']) . ' 行</td></tr>';
            echo '<tr><td><strong>エラー数</strong></td><td style="color: ' . ($has_errors ? '#dc3232' : '#46b450') . '; font-weight: bold;">' . count($validation_results['errors']) . ' 件</td></tr>';
            echo '<tr><td><strong>警告数</strong></td><td style="color: ' . (!empty($validation_results['warnings']) ? '#ffb900' : '#46b450') . '; font-weight: bold;">' . count($validation_results['warnings']) . ' 件</td></tr>';
            echo '</table>';
            
            // エラー詳細
            if ($has_errors) {
                echo '<div style="background: #fff2f2; border-left: 4px solid #dc3232; padding: 12px; margin: 10px 0;">';
                echo '<h5 style="margin: 0 0 10px 0; color: #dc3232;">エラー詳細</h5>';
                echo '<ul style="margin: 0;">';
                foreach (array_slice($validation_results['errors'], 0, 8) as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                if (count($validation_results['errors']) > 8) {
                    echo '<li style="color: #666; font-style: italic;">他 ' . (count($validation_results['errors']) - 8) . ' 件のエラー</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            // 警告詳細
            if (!empty($validation_results['warnings'])) {
                echo '<div style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 12px; margin: 10px 0;">';
                echo '<h5 style="margin: 0 0 10px 0; color: #996600;">警告詳細</h5>';
                echo '<ul style="margin: 0;">';
                foreach (array_slice($validation_results['warnings'], 0, 8) as $warning) {
                    echo '<li>' . esc_html($warning) . '</li>';
                }
                if (count($validation_results['warnings']) > 8) {
                    echo '<li style="color: #666; font-style: italic;">他 ' . (count($validation_results['warnings']) - 8) . ' 件の警告</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        // 確認事項
        echo '<div class="postbox" style="margin: 15px 0;">';
        echo '<div class="inside" style="padding: 12px;">';
        echo '<h4 style="margin-top: 0;">確認事項</h4>';
        echo '<ul style="margin: 0;">';
        
        if ($file_analysis['encoding'] !== 'UTF-8') {
            echo '<li style="color: #dc3232;">ファイルをUTF-8エンコーディングで保存し直すことを推奨します</li>';
        } else {
            echo '<li style="color: #46b450;">エンコーディングは適切です (UTF-8)</li>';
        }
        
        if (!in_array('post_title', $headers)) {
            echo '<li style="color: #dc3232;">必須フィールド「post_title」が不足しています</li>';
        } else {
            echo '<li style="color: #46b450;">必須フィールド「post_title」が含まれています</li>';
        }
        
        if ($detailed_check) {
            if (!empty($validation_results['errors'])) {
                echo '<li style="color: #dc3232;">エラーを修正してからインポートを実行してください</li>';
            } else {
                echo '<li style="color: #46b450;">データ検証でエラーは見つかりませんでした</li>';
            }
        }
        
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
}

// // テスト結果を表示する関数（後方互換性のため残しておく）
// function scv_display_csv_test_results($file_analysis, $headers, $csv_data, $validation_results, $detailed_check) {
//     // 新しい方式では使用されない（後方互換性のみ）
//     scv_display_csv_test_details($file_analysis, $headers, $csv_data, $validation_results, $detailed_check);
// }
