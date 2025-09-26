<?php
/**
 * Plugin Name: Simple CSV Import/Export
 * Description: CSVファイルを使用して投稿、固定ページ、カスタム投稿タイプを一括インポート/エクスポートできるプラグインです。WordPress標準のインポートツールとして統合されます。
 * Version: 1.0.0
 * Author: Shota Takazawa
 * Author URI: https://sokulabo.com
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグインの初期化
add_action('admin_init', 'scv_admin_init');
add_action('admin_init', 'scv_add_importer');

// WordPressのインポートツールに追加
function scv_add_importer() {
    register_importer(
        'csv-import-export',
        'CSV Import/Export',
        'CSVファイルを使用して投稿、固定ページ、カスタム投稿タイプを一括インポート/エクスポートできます。',
        'scv_admin_page'
    );
}

// 管理画面の初期化
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
}

// 管理画面のページ
function scv_admin_page() {
    // ヘルプタブを追加
    $screen = get_current_screen();
    
    $screen->add_help_tab(array(
        'id'      => 'csv-import-overview',
        'title'   => '概要',
        'content' => '
            <p><strong>CSV Import/Export プラグインについて</strong></p>
            <p>このツールを使用すると、CSVファイル形式でWordPressの投稿、固定ページ、カスタム投稿タイプを一括でインポート・エクスポートできます。</p>
            <ul>
                <li>大量のコンテンツを効率的に管理</li>
                <li>他のシステムからの移行</li>
                <li>バックアップとしてのデータエクスポート</li>
                <li>カスタムフィールドとタクソノミーの一括設定</li>
            </ul>
        '
    ));
    
    $screen->add_help_tab(array(
        'id'      => 'csv-format-help',
        'title'   => 'CSVフォーマット',
        'content' => '
            <p><strong>CSVファイルの形式について</strong></p>
            <p>CSVファイルは以下の要件を満たす必要があります：</p>
            <ul>
                <li>UTF-8エンコーディングで保存</li>
                <li>1行目はヘッダー行（列名）</li>
                <li>必須項目：post_title（記事タイトル）</li>
                <li>カンマ区切り形式</li>
            </ul>
            <p>サンプルCSVファイルをダウンロードして、正確な形式を確認することをお勧めします。</p>
        '
    ));
    
    $screen->add_help_tab(array(
        'id'      => 'csv-troubleshooting',
        'title'   => 'トラブルシューティング',
        'content' => '
            <p><strong>よくある問題と解決方法</strong></p>
            <ul>
                <li><strong>文字化け：</strong> CSVファイルがUTF-8で保存されているか確認してください</li>
                <li><strong>メモリエラー：</strong> 大きなファイルは分割してインポートしてください</li>
                <li><strong>画像が表示されない：</strong> 画像URLが有効で、アクセス可能か確認してください</li>
                <li><strong>カテゴリが作成されない：</strong> カテゴリスラッグが適切な形式か確認してください</li>
            </ul>
        '
    ));
    
    $screen->set_help_sidebar('
        <p><strong>関連リンク:</strong></p>
        <p><a href="https://wordpress.org/support/article/importing-content/" target="_blank">WordPress公式：コンテンツのインポート</a></p>
        <p><a href="' . admin_url('import.php') . '">他のインポートツール</a></p>
    ');
    
    ?>
    <div class="wrap">
        <h1>CSV Import/Export</h1>
        <p>CSVファイルを使用して、WordPressの投稿、固定ページ、カスタム投稿タイプを一括でインポート・エクスポートできます。</p>
        
        <!-- CSS スタイル -->
        <style>
            .scv-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .scv-section-header {
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                padding: 12px 16px;
                border-radius: 4px 4px 0 0;
            }
            .scv-section-header h2 {
                margin: 0;
                font-size: 15px;
                color: #1d2327;
            }
            .scv-section-content {
                padding: 16px;
            }
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

        <!-- CSVインポートセクション -->
        <div class="scv-section">
            <div class="scv-section-header">
                <h2>CSVファイルのインポート</h2>
            </div>
            <div class="scv-section-content">
                <div class="scv-notice">
                    <strong>注意:</strong> CSVファイルは UTF-8 エンコーディングで保存してください。処理速度はデータ量、サーバー環境、データの複雑さに応じて自動調整されます。
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('csv_import_action', 'csv_import_nonce'); ?>
                    
                    <div class="scv-form-group">
                        <label for="csv_file" class="scv-form-label">CSVファイルを選択:</label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </div>
                    
                    <div class="scv-form-group">
                        <label>
                            <input type="checkbox" name="update_existing" value="1">
                            既存の投稿を更新する（post_idが指定されている場合）
                        </label>
                    </div>
                    
                    <div class="scv-form-group">
                        <label>
                            <input type="checkbox" name="skip_errors" value="1" checked>
                            エラーが発生した行をスキップして続行する
                        </label>
                    </div>
                    
                    <div class="scv-form-group">
                        <p style="font-size: 12px; color: #666; margin: 0; padding: 8px; background: #f0f0f1; border-left: 3px solid #00a0d2; border-radius: 3px;">
                            <strong>自動バッチ処理:</strong> データ量とサーバー環境に応じて、処理速度を自動調整します。
                        </p>
                    </div>
                    
                    <button type="submit" name="import_csv" class="button button-primary">CSVをインポート</button>
                </form>
            </div>
        </div>

        <!-- CSVエクスポートセクション -->
        <div class="scv-section">
            <div class="scv-section-header">
                <h2>CSVファイルのエクスポート</h2>
            </div>
            <div class="scv-section-content">
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
                    
                    <div class="scv-form-group">
                        <p style="font-size: 12px; color: #666; margin: 0; padding: 8px; background: #f0f0f1; border-left: 3px solid #00a0d2; border-radius: 3px;">
                            <strong>自動制限:</strong> サーバー環境とデータ量に応じて、適切なエクスポート件数を自動設定します。
                        </p>
                    </div>
                    
                    <button type="submit" name="export_csv" class="button button-secondary">CSVをエクスポート</button>
                </form>
            </div>
        </div>

        <!-- CSVフォーマット説明 -->
        <div class="scv-section">
            <div class="scv-section-header">
                <h2>CSVフォーマット仕様</h2>
            </div>
            <div class="scv-section-content">
                <p>以下のフォーマットでCSVファイルを作成してください。1行目はヘッダー行として、各列名を記述してください。</p>
                
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
                            <td>2023/01/05 0:00:00</td>
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
                            <td>http://example.com/image.jpg</td>
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
                
                <h4>サンプルCSVファイル</h4>
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('sample_csv_download', 'sample_csv_nonce'); ?>
                        <button type="submit" name="download_sample_csv" class="button button-secondary" style="font-size: 12px; height: 28px; padding: 0 12px; display: flex; align-items: center; gap: 5px;">
                            <span class="dashicons dashicons-download"></span>
                            サンプルCSVをダウンロード
                        </button>
                    </form>
                    <span style="font-size: 12px; color: #666;">※ 実際のデータ形式を確認できます</span>
                </div>
                <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; font-size: 11px;">post_id,post_title,post_content,post_status,post_type,post_category,post_tags
,サンプル記事1,"&lt;p&gt;これは最初の記事です。&lt;/p&gt;",publish,post,sample-category,tag1
,サンプル記事2,"&lt;p&gt;これは2番目の記事です。&lt;/p&gt;",draft,post,"category1,category2","tag1,tag2"</pre>
            </div>
        </div>
    </div>
    <?php
}

// CSVインポート処理
function scv_process_csv_import() {
    // nonce チェック
    if (!wp_verify_nonce($_POST['csv_import_nonce'], 'csv_import_action')) {
        wp_die('セキュリティチェックに失敗しました。');
    }
    
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません。');
    }
    
    $uploaded_file = $_FILES['csv_file'];
    $update_existing = isset($_POST['update_existing']);
    $skip_errors = isset($_POST['skip_errors']);
    
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
    
    // 必須フィールドのチェック
    $required_fields = array('post_title');
    $missing_fields = array_diff($required_fields, $headers);
    if (!empty($missing_fields)) {
        add_action('admin_notices', function() use ($missing_fields) {
            echo '<div class="notice notice-error"><p>必須フィールドが不足しています: ' . implode(', ', $missing_fields) . '</p></div>';
        });
        return;
    }
    
    // インポート処理の実行
    $results = scv_import_posts($csv_data, $headers, $update_existing, $skip_errors, $batch_size);
    
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

// CSVファイルを読み込む関数
function scv_read_csv_file($file_path) {
    $csv_data = array();
    
    // ファイルを開く（BOM対応）
    $content = file_get_contents($file_path);
    if ($content === false) {
        return false;
    }
    
    // BOMを除去
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    
    // 改行コードを統一
    $content = str_replace(array("\r\n", "\r"), "\n", $content);
    
    // 一時ファイルに書き込み
    $temp_file = tempnam(sys_get_temp_dir(), 'csv_import_');
    file_put_contents($temp_file, $content);
    
    // CSVを解析
    if (($handle = fopen($temp_file, 'r')) !== false) {
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            $csv_data[] = $data;
        }
        fclose($handle);
    }
    
    // 一時ファイルを削除
    unlink($temp_file);
    
    return $csv_data;
}

// 最適なバッチサイズを自動計算する関数
function scv_calculate_optimal_batch_size($csv_data, $headers) {
    $total_rows = count($csv_data);
    
    // 基本バッチサイズを設定
    $base_batch_size = 50;
    
    // データ量に基づく調整
    if ($total_rows <= 100) {
        $size_factor = 1.0; // 小規模: そのまま
    } elseif ($total_rows <= 500) {
        $size_factor = 0.8; // 中規模: 少し下げる
    } elseif ($total_rows <= 1000) {
        $size_factor = 0.6; // 大規模: 下げる
    } else {
        $size_factor = 0.4; // 超大規模: 大幅に下げる
    }
    
    // サーバー環境に基づく調整
    $memory_limit = ini_get('memory_limit');
    $memory_in_mb = intval($memory_limit);
    
    if ($memory_in_mb >= 512) {
        $memory_factor = 1.2; // 高メモリ: 上げる
    } elseif ($memory_in_mb >= 256) {
        $memory_factor = 1.0; // 標準メモリ: そのまま
    } else {
        $memory_factor = 0.7; // 低メモリ: 下げる
    }
    
    // 実行時間制限に基づく調整
    $max_execution_time = ini_get('max_execution_time');
    if ($max_execution_time == 0) {
        $time_factor = 1.2; // 無制限: 上げる
    } elseif ($max_execution_time >= 300) {
        $time_factor = 1.1; // 長時間: 少し上げる
    } elseif ($max_execution_time >= 60) {
        $time_factor = 1.0; // 標準: そのまま
    } else {
        $time_factor = 0.6; // 短時間: 下げる
    }
    
    // 複雑度に基づく調整（画像やカスタムフィールドの有無）
    $complexity_factor = 1.0;
    
    // 画像フィールドがある場合
    if (in_array('post_thumbnail', $headers)) {
        $complexity_factor *= 0.5; // 画像処理は重いので大幅に下げる
    }
    
    // カスタムフィールドの数
    $custom_field_count = 0;
    foreach ($headers as $header) {
        if (!in_array($header, array('post_id', 'post_name', 'post_author', 'post_date', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'menu_order', 'post_type', 'post_thumbnail', 'post_category', 'post_tags')) && strpos($header, 'tax_') !== 0) {
            $custom_field_count++;
        }
    }
    
    if ($custom_field_count > 10) {
        $complexity_factor *= 0.7; // 多数のカスタムフィールド
    } elseif ($custom_field_count > 5) {
        $complexity_factor *= 0.85; // 中程度のカスタムフィールド
    }
    
    // カスタムタクソノミーの数
    $taxonomy_count = 0;
    foreach ($headers as $header) {
        if (strpos($header, 'tax_') === 0) {
            $taxonomy_count++;
        }
    }
    
    if ($taxonomy_count > 3) {
        $complexity_factor *= 0.8;
    }
    
    // 最終的なバッチサイズを計算
    $calculated_batch_size = intval($base_batch_size * $size_factor * $memory_factor * $time_factor * $complexity_factor);
    
    // 最小・最大値の制限
    $batch_size = max(5, min(200, $calculated_batch_size));
    
    // デバッグ情報をログに出力
    error_log(sprintf(
        'CSV Import Auto Batch Size Calculation: Total=%d, Base=%d, Size=%.2f, Memory=%.2f, Time=%.2f, Complexity=%.2f, Final=%d',
        $total_rows, $base_batch_size, $size_factor, $memory_factor, $time_factor, $complexity_factor, $batch_size
    ));
    
    return $batch_size;
}

// 最適なエクスポート制限を自動計算する関数
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

// 投稿をインポートする関数
function scv_import_posts($csv_data, $headers, $update_existing, $skip_errors, $batch_size) {
    $results = array(
        'success' => 0,
        'skipped' => 0,
        'errors' => 0,
        'error_messages' => array()
    );
    
    // 処理時間制限を延長
    set_time_limit(300);
    ini_set('memory_limit', '512M');
    
    $current_user_id = get_current_user_id();
    $processed = 0;
    
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
            
            // 必須フィールドのチェック
            if (empty($data['post_title'])) {
                throw new Exception('post_title が空です');
            }
            
            // 投稿データを準備
            $post_data = scv_prepare_post_data($data, $current_user_id);
            
            // 既存投稿の更新チェック
            if (!empty($data['post_id'])) {
                if (!$update_existing) {
                    $results['skipped']++;
                    continue;
                }
                
                $existing_post = get_post(intval($data['post_id']));
                if (!$existing_post) {
                    throw new Exception('指定されたpost_id の投稿が見つかりません: ' . $data['post_id']);
                }
                
                $post_data['ID'] = $existing_post->ID;
            }
            
            // 投稿を作成/更新
            $post_id = wp_insert_post($post_data, true);
            
            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }
            
            // メタデータとタクソノミーの設定
            scv_set_post_metadata($post_id, $data);
            scv_set_post_taxonomies($post_id, $data);
            
            // サムネイル画像の設定
            if (!empty($data['post_thumbnail'])) {
                scv_set_post_thumbnail($post_id, $data['post_thumbnail']);
            }
            
            $results['success']++;
            
        } catch (Exception $e) {
            $error_message = "行 {$row_number}: " . $e->getMessage();
            $results['error_messages'][] = $error_message;
            $results['errors']++;
            
            if (!$skip_errors) {
                break;
            }
        }
        
        $processed++;
        
        // バッチ処理でメモリ使用量を制御
        if ($processed % $batch_size === 0) {
            wp_cache_flush();
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }
    
    return $results;
}

// 投稿データを準備する関数
function scv_prepare_post_data($data, $default_author_id) {
    $post_data = array(
        'post_title'    => sanitize_text_field($data['post_title']),
        'post_content'  => wp_kses_post($data['post_content'] ?? ''),
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
    
    // 投稿ステータスの検証
    $valid_statuses = array('publish', 'draft', 'private', 'pending', 'future');
    if (!in_array($post_data['post_status'], $valid_statuses)) {
        $post_data['post_status'] = 'draft';
    }
    
    // 投稿タイプの検証
    if (!post_type_exists($post_data['post_type'])) {
        $post_data['post_type'] = 'post';
    }
    
    // 作成者の検証
    if (!get_userdata($post_data['post_author'])) {
        $post_data['post_author'] = $default_author_id;
    }
    
    return $post_data;
}

// 投稿のメタデータを設定する関数
function scv_set_post_metadata($post_id, $data) {
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
        
        // 空の値はスキップ
        if ($value === '') {
            continue;
        }
        
        // カスタムフィールドとして保存
        update_post_meta($post_id, sanitize_key($key), sanitize_text_field($value));
    }
}

// 投稿のタクソノミーを設定する関数
function scv_set_post_taxonomies($post_id, $data) {
    // カテゴリーの設定
    if (!empty($data['post_category'])) {
        $categories = array_map('trim', explode(',', $data['post_category']));
        $category_ids = array();
        
        foreach ($categories as $category_slug) {
            $category = get_category_by_slug($category_slug);
            if (!$category) {
                // カテゴリーが存在しない場合は作成
                $category_id = wp_create_category($category_slug);
                if (!is_wp_error($category_id)) {
                    $category_ids[] = $category_id;
                }
            } else {
                $category_ids[] = $category->term_id;
            }
        }
        
        if (!empty($category_ids)) {
            wp_set_post_categories($post_id, $category_ids);
        }
    }
    
    // タグの設定
    if (!empty($data['post_tags'])) {
        $tags = array_map('trim', explode(',', $data['post_tags']));
        wp_set_post_tags($post_id, $tags);
    }
    
    // カスタムタクソノミーの設定
    foreach ($data as $key => $value) {
        if (strpos($key, 'tax_') === 0 && !empty($value)) {
            $taxonomy = substr($key, 4); // 'tax_' を除去
            
            // タクソノミーが存在するかチェック
            if (taxonomy_exists($taxonomy)) {
                $terms = array_map('trim', explode(',', $value));
                $term_ids = array();
                
                foreach ($terms as $term_slug) {
                    $term = get_term_by('slug', $term_slug, $taxonomy);
                    if (!$term) {
                        // ターミナルが存在しない場合は作成
                        $term_data = wp_insert_term($term_slug, $taxonomy);
                        if (!is_wp_error($term_data)) {
                            $term_ids[] = $term_data['term_id'];
                        }
                    } else {
                        $term_ids[] = $term->term_id;
                    }
                }
                
                if (!empty($term_ids)) {
                    wp_set_object_terms($post_id, $term_ids, $taxonomy);
                }
            }
        }
    }
}

// 投稿のサムネイル画像を設定する関数
function scv_set_post_thumbnail($post_id, $thumbnail_url) {
    if (empty($thumbnail_url)) {
        return;
    }
    
    // 既にメディアライブラリに存在するかチェック
    $attachment_id = attachment_url_to_postid($thumbnail_url);
    
    if ($attachment_id) {
        // 既存の画像を使用
        set_post_thumbnail($post_id, $attachment_id);
    } else {
        // 新しい画像をダウンロードしてメディアライブラリに追加
        $upload_result = scv_download_and_attach_image($thumbnail_url, $post_id);
        if ($upload_result && !is_wp_error($upload_result)) {
            set_post_thumbnail($post_id, $upload_result);
        }
    }
}

// 画像をダウンロードしてメディアライブラリに追加する関数
function scv_download_and_attach_image($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // 画像をダウンロード
    $temp_file = download_url($image_url);
    if (is_wp_error($temp_file)) {
        return $temp_file;
    }
    
    // ファイル情報を取得
    $file_info = pathinfo($image_url);
    $filename = basename($image_url);
    
    // アップロード処理
    $file_array = array(
        'name' => $filename,
        'tmp_name' => $temp_file,
    );
    
    // メディアライブラリに追加
    $attachment_id = media_handle_sideload($file_array, $post_id);
    
    // 一時ファイルを削除
    @unlink($temp_file);
    
    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }
    
    return $attachment_id;
}

// CSVエクスポート処理
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

// CSV データを生成する関数
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

// CSV ファイルをダウンロードする関数
function scv_download_csv($csv_data, $post_type, $post_status) {
    $filename = 'wordpress_export_' . $post_type . '_' . $post_status . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    // HTTP ヘッダーを設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
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

// サンプルCSVファイルをダウンロードする関数
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
    
    $filename = 'wordpress_import_sample_' . date('Y-m-d_H-i-s') . '.csv';
    
    // HTTP ヘッダーを設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    
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

