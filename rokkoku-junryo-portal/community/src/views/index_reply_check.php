<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返信確認 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>返信確認</h1>
        </header>

        <?php
        // NGワードフィルターを読み込み
        require_once __DIR__ . '/../../rooms/ng_word_filter.php';

        $post_code = isset($_POST['post_code']) ? $_POST['post_code'] : '';
        $reply_name_raw = isset($_POST['name']) && $_POST['name'] != '' ? $_POST['name'] : '匿名';
        $reply_content_raw = isset($_POST['content']) ? $_POST['content'] : '';

        $error = false;
        $error_message = '';

        if ($reply_content_raw == '') {
            $error = true;
            $error_message = '❌ 返信内容が入力されていません。';
        }

        // NGワードチェック（エスケープ前の生データでチェック）
        if (!$error) {
            $ng_check = checkNgWordsMultiple(array(
                '名前' => $reply_name_raw,
                '内容' => $reply_content_raw
            ));
            if ($ng_check !== false) {
                $error = true;
                $error_message = '❌ ' . generateNgWordErrorMessage($ng_check);
            }
        }

        // 安全対策（表示用にエスケープ）
        $reply_name = htmlspecialchars($reply_name_raw, ENT_QUOTES, 'UTF-8');
        $reply_content = htmlspecialchars($reply_content_raw, ENT_QUOTES, 'UTF-8');
        ?>

        <section class="confirmation-section">
            <h2>返信内容を確認してください</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <p><?php echo $error_message; ?></p>
                </div>
            <?php else: ?>
                <div class="confirmation-item">
                    <span class="confirmation-label">👤 名前</span>
                    <div class="confirmation-value"><?php echo $reply_name; ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">📝 返信内容</span>
                    <div class="confirmation-value"><?php echo nl2br($reply_content); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="button-group">
                    <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                </div>
            <?php else: ?>
                <form method="post" action="index_reply_done.php">
                    <input type="hidden" name="post_code" value="<?php echo $post_code; ?>">
                    <input type="hidden" name="name" value="<?php echo $reply_name; ?>">
                    <input type="hidden" name="content" value="<?php echo $reply_content; ?>">
                    <div class="button-group">
                        <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                        <input type="submit" value="返信する" class="btn btn-submit">
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <div class="back-link">
            <a href="index_post_detail.php?code=<?php echo $post_code; ?>">← 投稿に戻る</a>
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
</body>
</html>