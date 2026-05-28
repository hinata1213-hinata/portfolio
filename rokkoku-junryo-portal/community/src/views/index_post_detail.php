<?php
session_start();

try {
    if (!isset($_GET['code'])) {
        header('Location: index.blade.php');
        exit();
    }
    $post_code = $_GET['code'];

    $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 投稿情報を取得
    $sql = 'SELECT code, name, title, content, image FROM mst_rokkoku WHERE code=?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$post_code]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo '投稿が見つかりませんでした。';
        exit();
    }

    // 返信を取得
    $sql = 'SELECT reply_code, name, content, created_at FROM mst_reply WHERE post_code=? ORDER BY reply_code ASC';
    $stmt = $dbh->prepare($sql);
    $stmt->execute([$post_code]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dbh = null;

} catch (Exception $e) {
    echo 'エラーが発生しました: ' . htmlspecialchars($e->getMessage());
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
    <style>
        body {
            padding-top: 100px;
            padding-bottom: 40px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-link a {
            color: #0066CC;
            text-decoration: none;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #0080FF;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.5);
        }

        .post-detail {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 40px;
            backdrop-filter: blur(10px);
        }

        .post-detail h1 {
            color: #ffffff;
            font-size: 2rem;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(0, 102, 204, 0.3);
            padding-bottom: 15px;
        }

        .post-meta {
            color: #0066CC;
            font-size: 0.95rem;
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .post-meta span {
            background: rgba(0, 102, 204, 0.1);
            padding: 6px 18px;
            border-radius: 20px;
            border: 1px solid rgba(0, 102, 204, 0.3);
        }

        .post-content {
            color: #cccccc;
            line-height: 1.8;
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .post-image {
            margin: 25px 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .post-image img {
            width: 100%;
            max-width: 600px;
            height: auto;
            display: block;
            border-radius: 12px;
        }

        .replies-section {
            margin-top: 50px;
        }

        .replies-section h2 {
            color: #0066CC;
            font-size: 1.8rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reply-count {
            font-size: 1.2rem;
            color: #0080FF;
            background: rgba(0, 102, 204, 0.1);
            padding: 4px 15px;
            border-radius: 20px;
        }

        .reply-item {
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .reply-item:hover {
            border-color: rgba(0, 102, 204, 0.5);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.2);
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .reply-author {
            color: #0066CC;
            font-weight: bold;
        }

        .reply-date {
            color: #888;
            font-size: 0.9rem;
        }

        .reply-content {
            color: #cccccc;
            line-height: 1.7;
        }

        .no-replies {
            text-align: center;
            color: #888;
            font-size: 1.1rem;
            padding: 40px;
            background: rgba(255, 255, 255, 0.02);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .reply-form-section {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .reply-form-section h3 {
            color: #0066CC;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .post-detail {
                padding: 25px;
            }

            .post-detail h1 {
                font-size: 1.5rem;
            }

            .post-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="index.blade.php">← 投稿一覧に戻る</a>
        </div>

        <div class="post-detail">
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="post-meta">
                <span>👤 <?php echo htmlspecialchars($post['name'] ?: '匿名'); ?></span>
            </div>

            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <?php if($post['image']): ?>
                <div class="post-image">
                    <img src="./images/<?php echo htmlspecialchars($post['image']); ?>" alt="投稿画像">
                </div>
            <?php endif; ?>
        </div>

        <div class="replies-section">
            <h2>
                💬 返信
                <span class="reply-count"><?php echo count($replies); ?></span>
            </h2>

            <?php if(count($replies) > 0): ?>
                <?php foreach($replies as $reply): ?>
                    <div class="reply-item">
                        <div class="reply-header">
                            <span class="reply-author">
                                👤 <?php echo htmlspecialchars($reply['name'] ?: '匿名'); ?>
                            </span>
                            
                        </div>
                        <div class="reply-content">
                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-replies">
                    まだ返信がありません。最初の返信をしてみませんか？
                </div>
            <?php endif; ?>

            <div class="reply-form-section">
                <h3>返信を投稿</h3>
                <form method="post" action="index_reply_check.php">
                    <input type="hidden" name="post_code" value="<?php echo $post_code; ?>">
                    
                    <div class="form-group">
                        <label for="name">名前</label>
                        <input type="text" id="name" name="name" placeholder="空欄の場合は「匿名」として投稿されます">
                        <p class="form-note">※ 名前を入力しない場合は「匿名」として表示されます</p>
                    </div>

                    <div class="form-group">
                        <label for="content">返信内容 <span style="color: #ff4444;">*</span></label>
                        <textarea id="content" name="content" placeholder="返信内容を入力してください" required></textarea>
                    </div>

                    <div class="button-group">
                        <input type="submit" value="返信する" class="btn btn-submit">
                    </div>
                </form>
            </div>
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