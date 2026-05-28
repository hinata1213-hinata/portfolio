<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿完了 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>投稿完了</h1>
        </header>

        <?php
        try {
            $rokkoku_name = isset($_POST['name']) ? $_POST['name'] : '匿名';
            $rokkoku_content = isset($_POST['content']) ? $_POST['content'] : '';
            $rokkoku_title = isset($_POST['title']) ? $_POST['title'] : '';
            $rokkoku_image_name = isset($_POST['image']['name']) ? $_POST['image']['name'] : '';
            
            // 入力チェック
            if ($rokkoku_title == '' || $rokkoku_content == '') {
                echo '<section class="confirmation-section">';
                echo '<div class="error-message">';
                echo '<h2>❌ エラー</h2>';
                echo '<p>タイトルまたは内容が入力されていません。</p>';
                echo '</div>';
                echo '<div class="button-group">';
                echo '<a href="index_add.php" class="btn btn-back">新規投稿に戻る</a>';
                echo '</div>';
                echo '</section>';
                exit();
            }
            
            // HTMLエスケープを解除してから再度エスケープ
            $rokkoku_name = htmlspecialchars_decode($rokkoku_name);
            $rokkoku_content = htmlspecialchars_decode($rokkoku_content);
            $rokkoku_title = htmlspecialchars_decode($rokkoku_title);
            
            // データベース接続
            $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // INSERT文実行
            $sql = 'INSERT INTO mst_rokkoku (name, content, title, image) VALUES (?, ?, ?, ?)';
            $stmt = $dbh->prepare($sql);
            $data = array($rokkoku_name, $rokkoku_content, $rokkoku_title, $rokkoku_image_name);
            $stmt->execute($data);

            $dbh = null;

            // 成功メッセージ
            ?>
            <section class="confirmation-section success-section">
                <h2>投稿が完了しました</h2>
                <p class="success-message">あなたの投稿が正常に保存されました。</p>

                <div class="confirmation-item">
                    <span class="confirmation-label">📌 タイトル</span>
                    <div class="confirmation-value"><?php echo htmlspecialchars($rokkoku_title); ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">👤 投稿者</span>
                    <div class="confirmation-value"><?php echo htmlspecialchars($rokkoku_name); ?></div>
                </div>

                <div class="confirmation-item">
                    <span class="confirmation-label">📝 内容</span>
                    <div class="confirmation-value"><?php echo nl2br(htmlspecialchars($rokkoku_content)); ?></div>
                </div>

                <?php if($rokkoku_image_name != ''): ?>
                    <div class="confirmation-item">
                        <span class="confirmation-label">🖼️ 画像</span>
                        <div class="confirmation-value">
                            <img src="./images/<?php echo htmlspecialchars($rokkoku_image_name); ?>" alt="投稿画像" style="max-width:300px; border-radius: 8px;">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <a href="index.blade.php" class="btn btn-submit">📋 投稿一覧を見る</a>
                    <a href="index_add.php" class="btn btn-back">➕ 続けて投稿する</a>
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
                    <a href="index_add.php" class="btn btn-back">新規投稿に戻る</a>
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
    <script src="community.js"></script>
</body>
</html>