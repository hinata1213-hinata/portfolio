<?php
require_once 'config.php';

// 投稿を取得（データベース使用版）
function getPosts() {
    $pdo = getDB();
    $stmt = $pdo->query('SELECT * FROM posts ORDER BY created_at DESC LIMIT 50');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 投稿を取得（JSON使用版 - DB不要）
function getPostsFromJSON() {
    if (file_exists('posts.json')) {
        $json = file_get_contents('posts.json');
        $posts = json_decode($json, true);
        return $posts ? array_reverse($posts) : [];
    }
    return [];
}

// どちらかを使用
$posts = getPosts(); // DB版
// $posts = getPostsFromJSON(); // JSON版

$token = generateToken();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>六刻巡旅 - コミュニティ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <header class="header">
        <h1 class="title">六刻巡旅 コミュニティ</h1>
        <a href="../index.html" class="back-button">← ゲームに戻る</a>
    </header>

    <main class="container">
        <!-- 投稿フォーム -->
        <section class="post-form-section">
            <h2 class="section-title">新規投稿</h2>
            <form id="postForm" class="post-form" method="POST" action="post.php">
                <input type="hidden" name="token" value="<?php echo h($token); ?>">
                
                <div class="form-group">
                    <label for="username">名前</label>
                    <input type="text" id="username" name="username" 
                           placeholder="名前を入力（匿名可）" maxlength="50" required>
                </div>
                
                <div class="form-group">
                    <label for="content">内容</label>
                    <textarea id="content" name="content" 
                              placeholder="謎解きのヒントや感想を共有しよう..." 
                              maxlength="1000" required></textarea>
                    <div class="char-count">
                        <span id="charCount">0</span> / 1000
                    </div>
                </div>
                
                <button type="submit" class="submit-button">
                    <span class="button-icon">📝</span>
                    投稿する
                </button>
            </form>
        </section>

        <!-- 投稿一覧 -->
        <section class="posts-section">
            <h2 class="section-title">みんなの投稿</h2>
            <div id="postsContainer" class="posts-container">
                <?php if (empty($posts)): ?>
                    <div class="no-posts">
                        <p>まだ投稿がありません。最初の投稿者になりましょう！</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card" data-post-id="<?php echo h($post['id']); ?>">
                            <div class="post-header">
                                <span class="post-author"><?php echo h($post['username']); ?></span>
                                <span class="post-date"><?php echo h(date('Y/m/d H:i', strtotime($post['created_at']))); ?></span>
                            </div>
                            <div class="post-content">
                                <?php echo nl2br(h($post['content'])); ?>
                            </div>
                            <div class="post-actions">
                                <button class="reply-button" onclick="toggleReply(<?php echo h($post['id']); ?>)">
                                    💬 返信
                                </button>
                            </div>
                            
                            <!-- 返信フォーム -->
                            <div class="reply-form-container" id="replyForm<?php echo h($post['id']); ?>" style="display: none;">
                                <form class="reply-form" method="POST" action="post.php">
                                    <input type="hidden" name="token" value="<?php echo h($token); ?>">
                                    <input type="hidden" name="post_id" value="<?php echo h($post['id']); ?>">
                                    <input type="hidden" name="type" value="reply">
                                    
                                    <input type="text" name="username" placeholder="名前" maxlength="50" required>
                                    <textarea name="content" placeholder="返信内容..." maxlength="500" required></textarea>
                                    <button type="submit" class="reply-submit">返信を送信</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

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

    <script src="script.js"></script>
</body>
</html>