<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返信完了 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>返信完了</h1>
        </header>

        <?php
        try {
            $post_code = $_POST['post_code'];
            $reply_name = isset($_POST['name']) ? $_POST['name'] : '匿名';
            $reply_content = isset($_POST['content']) ? $_POST['content'] : '';
            
            if ($reply_content == '') {
                echo '<section class="confirmation-section">';
                echo '<div class="error-message">';
                echo '<h2>❌ エラー</h2>';
                echo '<p>返信内容が入力されていません。</p>';
                echo '</div>';
                echo '<div class="button-group">';
                echo '<a href="index_post_detail.php?code=' . htmlspecialchars($post_code) . '" class="btn btn-back">投稿に戻る</a>';
                echo '</div>';
                echo '</section>';
                exit();
            }
            
            // HTMLエスケープを解除
            $reply_name = htmlspecialchars_decode($reply_name);
            $reply_content = htmlspecialchars_decode($reply_content);
            
            // データベース接続
            $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // INSERT文実行
            $sql = 'INSERT INTO mst_reply (post_code, name, content) VALUES (?, ?, ?)';
            $stmt = $dbh->prepare($sql);
            $data = array($post_code, $reply_name, $reply_content);
            $stmt->execute($data);

            $dbh = null;

            ?>
            <section class="confirmation-section success-section">
                <h2>返信が完了しました</h2>
                <p class="success-message">あなたの返信が正常に保存されました。</p>

                <div class="confirmation-item">
                    <span class="confirmation-label">👤 名前</span>
                    <div class="confirmation-value"><?php echo htmlspecialchars($reply_name); ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">📝 返信内容</span>
                    <div class="confirmation-value"><?php echo nl2br(htmlspecialchars($reply_content)); ?></div>
                </div>

                <div class="button-group">
                    <a href="index_post_detail.php?code=<?php echo htmlspecialchars($post_code); ?>" class="btn btn-submit">📋 投稿に戻る</a>
                    <a href="index.blade.php" class="btn btn-back">投稿一覧を見る</a>
                </div>
            </section>
            <?php
            
        } catch (Exception $e) {
            ?>
            <section class="confirmation-section">
                <div class="error-message">
                    <h2>❌ エラーが発生しました</h2>
                    <p>ただいま障害により大変ご迷惑をおかけしております。</p>
                    <p class="error-detail">エラー詳細: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                </div>
                <div class="button-group">
                    <a href="index.blade.php" class="btn btn-submit">投稿一覧に戻る</a>
                </div>
            </section>
            <?php
            exit();
        }
        ?>
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