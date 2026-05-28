<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修正確認 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>修正確認</h1>
        </header>

        <?php
        // NGワードフィルターを読み込み
        require_once __DIR__ . '/../../rooms/ng_word_filter.php';

        $rokkoku_code = $_POST['code'];
        $rokkoku_name_raw = isset($_POST['name']) && $_POST['name'] != '' ? $_POST['name'] : '匿名';
        $rokkoku_content_raw = $_POST['content'];
        $rokkoku_title_raw = $_POST['title'];
        $rokkoku_image_name_old = $_POST['image_name_old'];
        $rokkoku_image = $_FILES['image'];

        $error = false;
        $error_message = '';
        $new_image_name = $rokkoku_image_name_old;

        if ($rokkoku_title_raw == '') {
            $error = true;
            $error_message .= '<p>❌ タイトル名が入力されていません。</p>';
        }

        if ($rokkoku_content_raw == '') {
            $error = true;
            $error_message .= '<p>❌ 内容が入力されていません。</p>';
        }

        // NGワードチェック（エスケープ前の生データでチェック）
        $ng_check = checkNgWordsMultiple(array(
            'タイトル' => $rokkoku_title_raw,
            '名前' => $rokkoku_name_raw,
            '内容' => $rokkoku_content_raw
        ));
        if ($ng_check !== false) {
            $error = true;
            $error_message .= '<p>❌ ' . generateNgWordErrorMessage($ng_check) . '</p>';
        }

        // 安全対策（表示用にエスケープ）
        $rokkoku_name = htmlspecialchars($rokkoku_name_raw, ENT_QUOTES, 'UTF-8');
        $rokkoku_content = htmlspecialchars($rokkoku_content_raw, ENT_QUOTES, 'UTF-8');
        $rokkoku_title = htmlspecialchars($rokkoku_title_raw, ENT_QUOTES, 'UTF-8');

        // 画像処理
        if($rokkoku_image['size'] > 0) {
            if($rokkoku_image['size'] > 1000000) {
                $error = true;
                $error_message .= '<p>❌ 画像が大きすぎます（最大1MB）。</p>';
            } else {
                // 画像を保存
                $upload_dir = './images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                move_uploaded_file($rokkoku_image['tmp_name'], $upload_dir . $rokkoku_image['name']);
                $new_image_name = $rokkoku_image['name'];
            }
        }
        ?>

        <section class="confirmation-section">
            <h2>修正内容を確認してください</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <div class="confirmation-item">
                    <span class="confirmation-label">📌 タイトル</span>
                    <div class="confirmation-value"><?php echo $rokkoku_title; ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">👤 投稿者</span>
                    <div class="confirmation-value"><?php echo $rokkoku_name; ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">📝 内容</span>
                    <div class="confirmation-value"><?php echo nl2br($rokkoku_content); ?></div>
                </div>

                <?php if($new_image_name != ''): ?>
                    <div class="confirmation-item">
                        <span class="confirmation-label">🖼️ 画像</span>
                        <div class="confirmation-value">
                            <?php if($new_image_name != $rokkoku_image_name_old && $rokkoku_image['size'] > 0): ?>
                                <p class="form-note" style="color: #00ff00; margin-bottom: 10px;">✓ 新しい画像がアップロードされました</p>
                            <?php endif; ?>
                            <img src="./images/<?php echo htmlspecialchars($new_image_name); ?>" alt="アップロード画像" style="max-width:300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
                        </div>
                    </div>
                <?php elseif($rokkoku_image_name_old != ''): ?>
                    <div class="confirmation-item">
                        <span class="confirmation-label">🖼️ 画像</span>
                        <div class="confirmation-value">
                            <p class="form-note" style="color: #ff4444; margin-bottom: 10px;">画像が削除されます</p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="button-group">
                    <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                </div>
            <?php else: ?>
                <form method="post" action="index_edit_done.php">
                    <input type="hidden" name="code" value="<?php echo $rokkoku_code; ?>">
                    <input type="hidden" name="name" value="<?php echo $rokkoku_name; ?>">
                    <input type="hidden" name="content" value="<?php echo $rokkoku_content; ?>">
                    <input type="hidden" name="title" value="<?php echo $rokkoku_title; ?>">
                    <input type="hidden" name="image_name_old" value="<?php echo $rokkoku_image_name_old; ?>">
                    <input type="hidden" name="image_name" value="<?php echo $new_image_name; ?>">
                    <div class="button-group">
                        <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                        <input type="submit" value="修正する" class="btn btn-submit">
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <div class="back-link">
            <a href="index.blade.php">← 投稿一覧に戻る</a>
        </div>
    </div>

    <footer class="footer">
		<div class="footer-content">
			<div class="footer-links">
				<a href="legal/privacy.html">プライバシーポリシー</a>
				<a href="legal/terms-of-service.html">利用規約</a>
				<a href="community/confirm/confirm.html">お問い合わせ</a>
			</div>
			<div class="copyright">
				&copy; 2025 六刻巡旅 - All rights reserved
			</div>
		</div>
	</footer>
    <script src="community.js"></script>
</body>
</html>