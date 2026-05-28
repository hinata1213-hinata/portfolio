<?php
session_start();

require_once __DIR__ . '/likes.php';

// 検索クエリを取得
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$room_filter = isset($_GET['room']) ? intval($_GET['room']) : 0;

// 検索結果を格納
$search_results = array();

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

// 検索を実行
if (!empty($search_query)) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 5000');

        // 検索クエリを作成
        // 孤立した返信（親投稿が削除されたもの）を除外する
        // 返信数も取得
        $sql = "SELECT c.id, c.name, c.content, c.created_at, c.room_id, c.parent_id, c.root_post_id,
                       (SELECT COUNT(*) FROM room_comments r WHERE r.parent_id = c.id) as reply_count
                FROM room_comments c
                LEFT JOIN room_comments parent ON c.parent_id = parent.id
                LEFT JOIN room_comments root ON c.root_post_id = root.id
                WHERE (c.content LIKE :query OR c.name LIKE :query)
                  AND (c.parent_id IS NULL OR parent.id IS NOT NULL)
                  AND (c.root_post_id IS NULL OR root.id IS NOT NULL)";

        // 部屋フィルターがある場合
        if ($room_filter > 0) {
            $sql .= " AND c.room_id = :room_id";
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT 50";

        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':query', '%' . $search_query . '%', PDO::PARAM_STR);
        if ($room_filter > 0) {
            $stmt->bindValue(':room_id', $room_filter, PDO::PARAM_INT);
        }
        $stmt->execute();

        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // いいね情報を取得
        if (!empty($search_results) && isset($_SESSION['user_id'])) {
            $comment_ids = array_column($search_results, 'id');
            $like_counts = getLikeCounts($comment_ids);
            $user_liked_comments = getUserLikedComments($_SESSION['user_id'], $comment_ids);
        } else {
            $like_counts = array();
            $user_liked_comments = array();
        }

        $dbh = null;
    } catch (Exception $e) {
        // エラー処理
        $search_results = array();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿を検索 - 六刻巡旅コミュニティ</title>
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

        /* 検索フォーム */
        .search-form {
            background: rgba(26, 26, 26, 0.8);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .search-input-wrapper {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 25px;
            color: #ffffff;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .search-input::placeholder {
            color: #666666;
        }

        .search-button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.5);
        }

        .filter-section {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-label {
            color: #888888;
            font-size: 0.9rem;
        }

        .filter-button {
            padding: 6px 15px;
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            color: #888888;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .filter-button:hover, .filter-button.active {
            background: rgba(0, 102, 204, 0.3);
            border-color: rgba(0, 102, 204, 0.6);
            color: #0066CC;
        }

        /* 検索結果 */
        .results-header {
            color: #888888;
            font-size: 1rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.2);
        }

        .results-count {
            color: #0066CC;
            font-weight: bold;
        }

        .search-result-item {
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

        .search-result-item:hover {
            border-color: rgba(0, 102, 204, 0.5);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.2);
            transform: translateY(-2px);
        }

        .result-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .result-author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result-author {
            color: #0066CC;
            font-weight: bold;
            font-size: 1rem;
        }

        .result-time {
            color: #888888;
            font-size: 0.85rem;
        }

        .result-room {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.8rem;
        }

        .result-content {
            color: #cccccc;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .result-content mark {
            background: rgba(255, 200, 0, 0.3);
            color: #ffcc00;
            padding: 0 2px;
            border-radius: 2px;
        }

        .result-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 102, 204, 0.1);
        }

        .like-info {
            color: #888888;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .liked-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: linear-gradient(135deg, rgba(255, 68, 68, 0.3), rgba(255, 100, 100, 0.2));
            border: 1px solid rgba(255, 68, 68, 0.5);
            border-radius: 15px;
            color: #ff6666;
            font-size: 0.8rem;
        }

        .reply-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
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
            padding: 4px 10px;
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

        .no-results {
            text-align: center;
            color: #888888;
            padding: 60px 20px;
            font-size: 1.1rem;
        }

        .no-results-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .search-tips {
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .search-tips h3 {
            color: #0066CC;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .search-tips ul {
            color: #888888;
            font-size: 0.9rem;
            margin: 0;
            padding-left: 20px;
        }

        .search-tips li {
            margin-bottom: 5px;
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
            <div class="page-icon">🔍</div>
            <h1>投稿を検索</h1>
        </div>

        <div class="search-form">
            <form method="get" action="search.php">
                <div class="search-input-wrapper">
                    <input type="text" name="q" class="search-input" placeholder="キーワードを入力..." value="<?php echo htmlspecialchars($search_query); ?>" autofocus>
                    <button type="submit" class="search-button">🔍 検索</button>
                </div>
                <div class="filter-section">
                    <span class="filter-label">部屋で絞り込み:</span>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>" class="filter-button <?php echo $room_filter == 0 ? 'active' : ''; ?>">すべて</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&room=2" class="filter-button <?php echo $room_filter == 2 ? 'active' : ''; ?>">攻略情報</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&room=3" class="filter-button <?php echo $room_filter == 3 ? 'active' : ''; ?>">考察・感想</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&room=4" class="filter-button <?php echo $room_filter == 4 ? 'active' : ''; ?>">運営Q&A</a>
                </div>
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div class="results-header">
                「<?php echo htmlspecialchars($search_query); ?>」の検索結果: <span class="results-count"><?php echo count($search_results); ?>件</span>
            </div>

            <?php if (empty($search_results)): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <p>該当する投稿が見つかりませんでした</p>
                    <div class="search-tips">
                        <h3>検索のヒント</h3>
                        <ul>
                            <li>キーワードを短くしてみてください</li>
                            <li>別の言葉で検索してみてください</li>
                            <li>部屋のフィルターを外してみてください</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($search_results as $result): ?>
                    <?php
                    $post_detail_id = getPostDetailId($result);
                    $room_id = $result['room_id'];
                    $room_name = getRoomName($room_id);
                    $is_reply = ($result['parent_id'] !== null);
                    $reply_count = isset($result['reply_count']) ? intval($result['reply_count']) : 0;

                    $time_diff = time() - strtotime($result['created_at']);
                    if ($time_diff < 3600) {
                        $time_display = floor($time_diff / 60) . '分前';
                    } elseif ($time_diff < 86400) {
                        $time_display = floor($time_diff / 3600) . '時間前';
                    } else {
                        $time_display = floor($time_diff / 86400) . '日前';
                    }

                    // 検索キーワードをハイライト
                    $content = htmlspecialchars($result['content']);
                    $highlighted_content = preg_replace('/(' . preg_quote(htmlspecialchars($search_query), '/') . ')/iu', '<mark>$1</mark>', $content);

                    // いいね情報
                    $like_count = isset($like_counts[$result['id']]) ? $like_counts[$result['id']] : 0;
                    $has_liked = in_array($result['id'], $user_liked_comments);
                    ?>
                    <a href="post_detail.php?id=<?php echo $post_detail_id; ?>&room_id=<?php echo $room_id; ?>" class="search-result-item">
                        <div class="result-header">
                            <div class="result-author-info">
                                <span class="result-author"><?php echo htmlspecialchars($result['name']); ?></span>
                                <span class="result-time"><?php echo $time_display; ?></span>
                            </div>
                            <span class="result-room"><?php echo htmlspecialchars($room_name); ?></span>
                        </div>
                        <div class="result-content"><?php echo nl2br($highlighted_content); ?></div>
                        <div class="result-meta">
                            <span class="like-info">❤️ <?php echo $like_count; ?></span>
                            <?php if ($has_liked): ?>
                                <span class="liked-badge">いいね済み</span>
                            <?php endif; ?>
                            <?php if ($is_reply): ?>
                                <span class="reply-indicator">↳ 返信</span>
                            <?php else: ?>
                                <span class="reply-count<?php echo $reply_count > 0 ? ' has-replies' : ''; ?>">💬 <?php echo $reply_count; ?>件の返信</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <div class="no-results-icon">🔎</div>
                <p>キーワードを入力して検索してください</p>
                <div class="search-tips">
                    <h3>検索できる内容</h3>
                    <ul>
                        <li>投稿の本文</li>
                        <li>投稿者の名前</li>
                        <li>返信の内容</li>
                    </ul>
                </div>
            </div>
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