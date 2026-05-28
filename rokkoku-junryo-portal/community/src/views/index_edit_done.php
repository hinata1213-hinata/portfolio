<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修正完了 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>修正完了</h1>
        </header>

        <?php
        try {
            $rokkoku_code = $_POST['code'];
            $rokkoku_name = isset($_POST['name']) ? $_POST['name'] : '匿名';
            $rokkoku_title = $_POST['title'];
            $rokkoku_content = $_POST['content'];
            $rokkoku_image_name_old = $_POST['image_name_old'];
            $rokkoku_image_name = $_POST['image_name'];

            // HTMLエスケープを解除してから再度エスケープ
            $rokkoku_name = htmlspecialchars_decode($rokkoku_name);
            $rokkoku_title = htmlspecialchars_decode($rokkoku_title);
            $rokkoku_content = htmlspecialchars_decode($rokkoku_content);

            $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = 'UPDATE mst_rokkoku SET name=?, title=?, content=?, image=? WHERE code=?';
            $stmt = $dbh->prepare($sql);
            $data = array();
            $data[] = $rokkoku_name;
            $data[] = $rokkoku_title;
            $data[] = $rokkoku_content;
            $data[] = $rokkoku_image_name;
            $data[] = $rokkoku_code;
            $stmt->execute($data);

            $dbh = null;

            // 古い画像を削除（新しい画像がアップロードされた場合）
            if($rokkoku_image_name_old != $rokkoku_image_name && $rokkoku_image_name_old != '') {
                $old_image_path = './images/' . $rokkoku_image_name_old;
                if(file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            ?>

            <section class="confirmation-section success-section">
                <h2>投稿の修正が完了しました</h2>
                <p class="success-message">投稿が正常に更新されました。</p>

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
                        <span class="confirmation-label">画像</span>
                        <div class="confirmation-value">
                            <img src="./images/<?php echo htmlspecialchars($rokkoku_image_name); ?>" alt="投稿画像" style="max-width:300px; border-radius: 8px;">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="button-group">
                    <a href="index.blade.php" class="btn btn-submit">投稿一覧に戻る</a>
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
    <script src="community.js"></script>
</body>
</html>