<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規投稿 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
</head>

<body class="add-page">
    <div class="container">
        <header class="page-header">
            <h1>新規投稿</h1>
        </header>

        <section class="form-section">
            <h2>投稿を作成</h2>
            <form method="post" action="index_add_check.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">タイトル <span style="color: #ff4444;">*</span></label>
                    <input type="text" id="title" name="title" placeholder="タイトルを入力してください" required>
                </div>

                <div class="form-group">
                    <label for="name">名前</label>
                    <input type="text" id="name" name="name" placeholder="空欄の場合は「匿名」として投稿されます">
                    <p class="form-note">※ 名前を入力しない場合は「匿名」として表示されます</p>
                </div>

                <div class="form-group">
                    <label for="content">内容 <span style="color: #ff4444;">*</span></label>
                    <textarea id="content" name="content" placeholder="投稿内容を入力してください" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">画像 (最大1MB)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <p class="form-note">※ 画像は任意です</p>
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