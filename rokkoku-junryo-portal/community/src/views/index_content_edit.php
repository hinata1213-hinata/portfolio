<?php
session_start();

try {
    if (!isset($_SESSION['rokkokucode'])) {
        header('Location: index_ng.php');
        exit();
    }
    $rokkoku_code = $_SESSION['rokkokucode'];

    $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = 'SELECT name, title, content, image FROM mst_rokkoku WHERE code=?';
    $stmt = $dbh->prepare($sql);
    $data[] = $rokkoku_code;
    $stmt->execute($data);

    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    $rokkoku_name = $rec['name'];
    $rokkoku_title = $rec['title'];
    $rokkoku_content = $rec['content'];
    $rokkoku_image_name_old = $rec['image'];

    $dbh = null;
} catch (Exception $e) {
    print 'ただいま障害により大変ご迷惑をおかけしております。';
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿修正 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>
<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>投稿修正</h1>
        </header>

        <section class="form-section">
            <h2>投稿を修正</h2>
            <p class="form-note">投稿コード: <?php print $rokkoku_code; ?></p>
            
            <form method="post" action="index_edit_check.php" enctype="multipart/form-data">
                <input type="hidden" name="code" value="<?php print $rokkoku_code; ?>">
                <input type="hidden" name="image_name_old" value="<?php print htmlspecialchars($rokkoku_image_name_old); ?>">
                
                <div class="form-group">
                    <label for="title">タイトル <span style="color: #ff4444;">*</span></label>
                    <input type="text" id="title" name="title" value="<?php print htmlspecialchars($rokkoku_title); ?>" required>
                </div>

                <div class="form-group">
                    <label for="name">名前</label>
                    <input type="text" id="name" name="name" value="<?php print htmlspecialchars($rokkoku_name); ?>">
                    <p class="form-note">※ 名前を入力しない場合は「匿名」として表示されます</p>
                </div>

                <div class="form-group">
                    <label for="content">内容 <span style="color: #ff4444;">*</span></label>
                    <textarea id="content" name="content" required><?php print htmlspecialchars($rokkoku_content); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">画像 (最大1MB)</label>
                    <?php if($rokkoku_image_name_old != ''): ?>
                        <div style="margin-bottom: 10px;">
                            <p class="form-note">現在の画像:</p>
                            <img src="./images/<?php print htmlspecialchars($rokkoku_image_name_old); ?>" alt="現在の画像" style="max-width: 200px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*">
                    <p class="form-note">※ 新しい画像を選択すると、現在の画像が置き換えられます</p>
                </div>

                <div class="button-group">
                    <input type="button" onclick="history.back()" value="戻る" class="btn btn-back">
                    <input type="submit" value="確認画面へ" class="btn btn-submit">
                </div>
            </form>
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