<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿確認 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>

<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>投稿確認</h1>
        </header>

        <?php
        // NGワードフィルターを読み込み
        require_once __DIR__ . '/../../rooms/ng_word_filter.php';

        // POSTデータの取得（生データ）
        $rokkoku_name_raw = isset($_POST['name']) && $_POST['name'] != '' ? $_POST['name'] : '匿名';
        $rokkoku_content_raw = isset($_POST['content']) ? $_POST['content'] : '';
        $rokkoku_title_raw = isset($_POST['title']) ? $_POST['title'] : '';
        $rokkoku_image = isset($_FILES['image']) ? $_FILES['image'] : null;

        $error = false;
        $error_messages = array();
        $image_name = '';

        // エラーチェック
        if ($rokkoku_title_raw == '') {
            $error = true;
            $error_messages[] = '❌ タイトルが入力されていません。';
        }

        if ($rokkoku_content_raw == '') {
            $error = true;
            $error_messages[] = '❌ 内容が入力されていません。';
        }

        // NGワードチェック（エスケープ前の生データでチェック）
        $ng_check = checkNgWordsMultiple(array(
            'タイトル' => $rokkoku_title_raw,
            '名前' => $rokkoku_name_raw,
            '内容' => $rokkoku_content_raw
        ));
        if ($ng_check !== false) {
            $error = true;
            $error_messages[] = '❌ ' . generateNgWordErrorMessage($ng_check);
        }

        // 安全対策（表示用にエスケープ）
        $rokkoku_name = htmlspecialchars($rokkoku_name_raw, ENT_QUOTES, 'UTF-8');
        $rokkoku_content = htmlspecialchars($rokkoku_content_raw, ENT_QUOTES, 'UTF-8');
        $rokkoku_title = htmlspecialchars($rokkoku_title_raw, ENT_QUOTES, 'UTF-8');

        // 画像処理
        if($rokkoku_image && $rokkoku_image['size'] > 0) {
            if($rokkoku_image['size'] > 1000000) {
                $error = true;
                $error_messages[] = '❌ 画像が大きすぎます（最大1MB）。';
            } else {
                // imagesディレクトリを作成
                $upload_dir = './images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                move_uploaded_file($rokkoku_image['tmp_name'], $upload_dir . $rokkoku_image['name']);
                $image_name = $rokkoku_image['name'];
            }
        }
        ?>

        <section class="confirmation-section">
            <h2>投稿内容を確認してください</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php foreach($error_messages as $msg): ?>
                        <p><?php echo $msg; ?></p>
                    <?php endforeach; ?>
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

                <?php if($image_name != ''): ?>
                    <div class="confirmation-item">
                        <span class="confirmation-label">画像</span>
                        <div class="confirmation-value">
                            <img src="./images/<?php echo htmlspecialchars($image_name); ?>" alt="アップロード画像" style="max-width:300px; border-radius: 8px;">
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="button-group">
                    <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                </div>
            <?php else: ?>
                <form method="post" action="index_add_done.php">
                    <input type="hidden" name="title" value="<?php echo $rokkoku_title; ?>">
                    <input type="hidden" name="name" value="<?php echo $rokkoku_name; ?>">
                    <input type="hidden" name="content" value="<?php echo $rokkoku_content; ?>">
                    <input type="hidden" name="image[name]" value="<?php echo $image_name; ?>">
                    <div class="button-group">
                        <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                        <input type="submit" value="投稿する" class="btn btn-submit">
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