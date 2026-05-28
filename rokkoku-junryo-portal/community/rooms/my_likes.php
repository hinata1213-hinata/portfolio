<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

require_once __DIR__ . '/likes.php';

$user_id = $_SESSION['user_id'];

// ユーザーがいいねした投稿を取得
function getUserLikedPosts($user_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 5000');

        // likesテーブルとroom_commentsを結合して、いいねした投稿の情報を取得
        // 孤立した返信（親投稿が削除されたもの）を除外する
        // 返信数も取得
        $sql = "SELECT c.id, c.name, c.content, c.created_at, c.room_id, c.parent_id, c.root_post_id, l.created_at as liked_at,
                       (SELECT COUNT(*) FROM room_comments r WHERE r.parent_id = c.id) as reply_count
                FROM likes l
                INNER JOIN room_comments c ON l.comment_id = c.id
                LEFT JOIN room_comments parent ON c.parent_id = parent.id
                LEFT JOIN room_comments root ON c.root_post_id = root.id
                WHERE l.user_id = :user_id
                  AND (c.parent_id IS NULL OR parent.id IS NOT NULL)
                  AND (c.root_post_id IS NULL OR root.id IS NOT NULL)
                ORDER BY l.created_at DESC";
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
        return $posts;
    } catch (Exception $e) {
        return array();
    }
}

$liked_posts = getUserLikedPosts($user_id);

// 部屋名を取得
function getRoomName($room_id) {
    $room_names = array(
        2 => '攻略情報共有部屋',
        3 => '考察・感想部屋',
        4 => '運営Q&A部屋'
    );
    return isset($room_names[$room_id]) ? $room_names[$room_id] : '部屋';
}

// 投稿詳細ページへのリンク用post_idを取得
function getPostDetailId($post) {
    if ($post['parent_id'] === null) {
        return $post['id'];
    } else {
        return $post['root_post_id'] ? $post['root_post_id'] : $post['parent_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>いいねした投稿 - 六刻巡旅コミュニティ</title>
    <link rel="stylesheet" href="../../game.css">
    <style>
        body {
            background: linear-gradient(180deg, #000000, #0a0a0a);
            min-height: 100vh;
            padding-top: 80px;
            padding-bottom: 40px;
        }

        .site-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 102, 204, 0.3);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-family: 'Creepster', cursive;
            font-size: 24px;
            color: #0066CC;
            text-decoration: none;
            text-shadow: 0 0 10px #0066CC;
        }

        .back-button {
            padding: 10px 25px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 25px;
            color: #0066CC;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .page-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
        }

        .page-header .page-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .page-description {
            color: #888888;
            font-size: 1rem;
        }

        .liked-post-item {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .liked-post-item:hover {
            border-color: rgba(0, 102, 204, 0.5);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.2);
            transform: translateY(-2px);
        }

        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .post-author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-author {
            color: #0066CC;
            font-weight: bold;
            font-size: 1rem;
        }

        .post-time {
            color: #888888;
            font-size: 0.85rem;
        }

        .post-room {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.8rem;
        }

        .post-content {
            color: #cccccc;
            line-height: 1.7;
            margin-bottom: 10px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 102, 204, 0.1);
        }

        .liked-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            background: linear-gradient(135deg, rgba(255, 68, 68, 0.3), rgba(255, 100, 100, 0.2));
            border: 1px solid rgba(255, 68, 68, 0.5);
            border-radius: 15px;
            color: #ff6666;
            font-size: 0.85rem;
        }

        .liked-at {
            color: #666666;
            font-size: 0.8rem;
        }

        .reply-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            background: rgba(100, 100, 100, 0.2);
            border: 1px solid rgba(100, 100, 100, 0.4);
            border-radius: 15px;
            color: #888888;
            font-size: 0.8rem;
        }

        .reply-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            background: rgba(0, 102, 204, 0.15);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.8rem;
        }

        .reply-count.has-replies {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.3), rgba(0, 128, 255, 0.2));
            border-color: rgba(0, 102, 204, 0.6);
            font-weight: bold;
        }

        .no-likes {
            text-align: center;
            color: #888888;
            padding: 60px 20px;
            font-size: 1.1rem;
        }

        .no-likes-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .footer {
            background: #0a0a0a;
            border-top: 1px solid rgba(0, 102, 204, 0.3);
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #888888;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #0066CC;
        }

        .copyright {
            color: #666666;
            font-size: 0.9rem;
        }

        .copyright-large {
            color: #888888;
            font-size: 1.2rem;
            font-weight: 500;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.3);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="../../game.html" class="logo">六刻巡旅</a>
            <a href="index.php" class="back-button">← コミュニティに戻る</a>
        </nav>
    </header>

    <div class="page-container">
        <div class="page-header">
            <div class="page-icon">❤️</div>
            <h1>いいねした投稿</h1>
            <p class="page-description">あなたがいいねした投稿の一覧です</p>
        </div>

        <?php if (empty($liked_posts)): ?>
            <div class="no-likes">
                <div class="no-likes-icon">🤍</div>
                <p>まだいいねした投稿がありません</p>
                <p style="font-size: 0.9rem; margin-top: 10px;">気になる投稿にいいねしてみましょう！</p>
            </div>
        <?php else: ?>
            <?php foreach ($liked_posts as $post): ?>
                <?php
                $post_detail_id = getPostDetailId($post);
                $room_id = $post['room_id'];
                $room_name = getRoomName($room_id);
                $is_reply = ($post['parent_id'] !== null);
                $reply_count = isset($post['reply_count']) ? intval($post['reply_count']) : 0;

                $time_diff = time() - strtotime($post['created_at']);
                if ($time_diff < 3600) {
                    $time_display = floor($time_diff / 60) . '分前';
                } elseif ($time_diff < 86400) {
                    $time_display = floor($time_diff / 3600) . '時間前';
                } else {
                    $time_display = floor($time_diff / 86400) . '日前';
                }

                $liked_time_diff = time() - strtotime($post['liked_at']);
                if ($liked_time_diff < 3600) {
                    $liked_display = floor($liked_time_diff / 60) . '分前にいいね';
                } elseif ($liked_time_diff < 86400) {
                    $liked_display = floor($liked_time_diff / 3600) . '時間前にいいね';
                } else {
                    $liked_display = floor($liked_time_diff / 86400) . '日前にいいね';
                }
                ?>
                <a href="post_detail.php?id=<?php echo $post_detail_id; ?>&room_id=<?php echo $room_id; ?>" class="liked-post-item">
                    <div class="post-header">
                        <div class="post-author-info">
                            <span class="post-author"><?php echo htmlspecialchars($post['name']); ?></span>
                            <span class="post-time"><?php echo $time_display; ?></span>
                        </div>
                        <span class="post-room"><?php echo htmlspecialchars($room_name); ?></span>
                    </div>
                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                    <div class="post-meta">
                        <span class="liked-badge">❤️ いいね済み</span>
                        <?php if ($is_reply): ?>
                            <span class="reply-indicator">↳ 返信</span>
                        <?php else: ?>
                            <span class="reply-count<?php echo $reply_count > 0 ? ' has-replies' : ''; ?>">💬 <?php echo $reply_count; ?>件の返信</span>
                        <?php endif; ?>
                        <span class="liked-at"><?php echo $liked_display; ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="copyright-large">
                &copy; 2025 六刻巡旅 - All rights reserved
            </div>
        </div>
    </footer>
</body>
</html>