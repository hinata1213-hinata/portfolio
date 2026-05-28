<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// 返信先IDと投稿IDとroom_idを取得
$reply_to_id = isset($_GET['reply_to']) ? intval($_GET['reply_to']) : 0;
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 2;

// room_idに対応する部屋のファイル名
$room_files = array(
    2 => 'room_guide.php',
    3 => 'room_discussion.php',
    4 => 'room_qa.php'
);
$back_url = isset($room_files[$room_id]) ? $room_files[$room_id] : 'room_guide.php';

// 運営アカウント名
define('OFFICIAL_ACCOUNT_NAME', '六刻巡旅 公式');

// 通知機能を読み込み
require_once __DIR__ . '/notifications.php';

if ($reply_to_id <= 0 || $post_id <= 0) {
    header('Location: ' . $back_url);
    exit;
}

// 返信投稿処理
$error_message = '';
if (isset($_POST['post_reply'])) {
    // NGワードフィルターを読み込み
    require_once __DIR__ . '/ng_word_filter.php';

    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $ng_error = false;

    if (empty($content)) {
        $error_message = '返信内容を入力してください。';
    } else {
        // NGワードチェック
        $ng_check = checkNgWords($content);
        if ($ng_check !== false) {
            $ng_error = true;
            $error_message = '不適切な表現が含まれています。投稿内容を修正してください。';
        }

        if (!$ng_error) {
            try {
                $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
                $dbh = new PDO($dsn);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // root_post_idカラムを追加（存在しない場合）
                try {
                    $dbh->exec('ALTER TABLE room_comments ADD COLUMN root_post_id INTEGER');
                } catch (Exception $e) {
                    // カラムが既に存在する場合はエラーになるが無視
                }

                $name = isset($_SESSION['username']) ? $_SESSION['username'] : '匿名';

                // 返信先の情報を取得
                $check_sql = 'SELECT name, user_id FROM room_comments WHERE id = :id';
                $check_stmt = $dbh->prepare($check_sql);
                $check_stmt->bindValue(':id', $reply_to_id, PDO::PARAM_INT);
                $check_stmt->execute();
                $reply_to = $check_stmt->fetch(PDO::FETCH_ASSOC);

                // Q&A部屋（room_id=4）の返信制限チェック
                $can_reply = true;
                if ($room_id === 4) {
                    // 元の投稿者を取得
                    $post_check_sql = 'SELECT name FROM room_comments WHERE id = :id AND parent_id IS NULL';
                    $post_check_stmt = $dbh->prepare($post_check_sql);
                    $post_check_stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
                    $post_check_stmt->execute();
                    $original_post = $post_check_stmt->fetch(PDO::FETCH_ASSOC);
                    $original_poster = $original_post ? $original_post['name'] : '';

                    if ($reply_to) {
                        // 運営の返信への返信は、元の投稿者のみ可能
                        if ($reply_to['name'] === OFFICIAL_ACCOUNT_NAME) {
                            if ($name !== $original_poster) {
                                $can_reply = false;
                                $error_message = '運営への返信は投稿者のみ可能です。';
                            }
                        } else {
                            // 運営以外への返信は不可（運営のみ可能）
                            if ($name !== OFFICIAL_ACCOUNT_NAME) {
                                $can_reply = false;
                                $error_message = 'この部屋では運営のみが投稿に返信できます。';
                            }
                        }
                    }
                }

                if ($can_reply) {
                    if ($reply_to) {
                        // @ユーザー名を先頭に追加
                        $content = '@' . $reply_to['name'] . ' ' . $content;
                    }

                    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

                    $sql = 'INSERT INTO room_comments (room_id, name, user_id, content, created_at, parent_id, root_post_id) VALUES (:room_id, :name, :user_id, :content, datetime("now", "localtime"), :parent_id, :root_post_id)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
                    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
                    $stmt->bindValue(':parent_id', $reply_to_id, PDO::PARAM_INT);
                    $stmt->bindValue(':root_post_id', $post_id, PDO::PARAM_INT);
                    $stmt->execute();

                    // スレッド参加者全員に通知を送る（自分自身は除く）
                    // 元の投稿者 + スレッドに返信した全ユーザーを取得
                    $thread_users_sql = 'SELECT DISTINCT user_id, name FROM room_comments WHERE (id = :post_id OR root_post_id = :root_post_id) AND user_id IS NOT NULL AND user_id != :current_user_id';
                    $thread_users_stmt = $dbh->prepare($thread_users_sql);
                    $thread_users_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
                    $thread_users_stmt->bindValue(':root_post_id', $post_id, PDO::PARAM_INT);
                    $thread_users_stmt->bindValue(':current_user_id', $user_id, PDO::PARAM_INT);
                    $thread_users_stmt->execute();
                    $thread_users = $thread_users_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($thread_users)) {
                        // 通知テーブルを初期化（存在しない場合）
                        $dbh->exec("CREATE TABLE IF NOT EXISTS notifications (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER,
                            user_name TEXT NOT NULL,
                            type TEXT NOT NULL,
                            message TEXT NOT NULL,
                            post_id INTEGER,
                            room_id INTEGER,
                            is_read INTEGER DEFAULT 0,
                            created_at TEXT NOT NULL
                        )");

                        // user_idカラムを追加（存在しない場合）
                        try {
                            $dbh->exec('ALTER TABLE notifications ADD COLUMN user_id INTEGER');
                        } catch (Exception $e) {
                            // カラムが既に存在する場合はエラーになるが無視
                        }

                        // 元の投稿者のuser_idを取得
                        $owner_sql = 'SELECT user_id FROM room_comments WHERE id = :id';
                        $owner_stmt = $dbh->prepare($owner_sql);
                        $owner_stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
                        $owner_stmt->execute();
                        $owner_row = $owner_stmt->fetch(PDO::FETCH_ASSOC);
                        $post_owner_user_id = $owner_row ? $owner_row['user_id'] : null;

                        // 直接の返信先のuser_idを取得
                        $direct_reply_target_id = ($reply_to && $reply_to['user_id']) ? $reply_to['user_id'] : null;

                        $notify_insert_sql = 'INSERT INTO notifications (user_id, user_name, type, message, post_id, room_id, is_read, created_at) VALUES (:user_id, :user_name, :type, :message, :post_id, :room_id, 0, datetime("now", "localtime"))';

                        foreach ($thread_users as $thread_user) {
                            if ($thread_user['user_id'] == $post_owner_user_id) {
                                $message = $name . ' さんがあなたの投稿に返信しました';
                            } elseif ($thread_user['user_id'] == $direct_reply_target_id) {
                                $message = $name . ' さんがあなたの返信に返信しました';
                            } else {
                                $message = $name . ' さんがあなたが参加しているスレッドに返信しました';
                            }

                            $notify_insert_stmt = $dbh->prepare($notify_insert_sql);
                            $notify_insert_stmt->bindValue(':user_id', $thread_user['user_id'], PDO::PARAM_INT);
                            $notify_insert_stmt->bindValue(':user_name', $thread_user['name'], PDO::PARAM_STR);
                            $notify_insert_stmt->bindValue(':type', 'reply', PDO::PARAM_STR);
                            $notify_insert_stmt->bindValue(':message', $message, PDO::PARAM_STR);
                            $notify_insert_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
                            $notify_insert_stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
                            $notify_insert_stmt->execute();
                        }
                    }

                    // データベース接続を閉じる
                    $dbh = null;

                    header('Location: post_detail.php?id=' . $post_id . '&room_id=' . $room_id);
                    exit;
                }

                $dbh = null;
            } catch (Exception $e) {
                $error_message = 'エラーが発生しました。もう一度お試しください。';
                error_log($e->getMessage());
            }
        }
    }
}

// 返信先の情報とその返信を取得
try {
    $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 返信先のコメントを取得
    $sql = 'SELECT name, content, created_at FROM room_comments WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $reply_to_id, PDO::PARAM_INT);
    $stmt->execute();
    $reply_to_comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reply_to_comment) {
        header('Location: post_detail.php?id=' . $post_id . '&room_id=' . $room_id);
        exit;
    }

    // この返信に対するすべての返信を取得
    $replies_sql = 'SELECT id, name, content, created_at FROM room_comments WHERE parent_id = :parent_id ORDER BY created_at ASC';
    $replies_stmt = $dbh->prepare($replies_sql);
    $replies_stmt->bindParam(':parent_id', $reply_to_id, PDO::PARAM_INT);
    $replies_stmt->execute();
    $existing_replies = $replies_stmt->fetchAll(PDO::FETCH_ASSOC);

    $dbh = null;
} catch (Exception $e) {
    header('Location: post_detail.php?id=' . $post_id . '&room_id=' . $room_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返信する - 六刻巡旅コミュニティ</title>
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

        .reply-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            font-size: 2rem;
            color: #0066CC;
            text-shadow: 0 0 20px rgba(0, 102, 204, 0.6);
            margin-bottom: 30px;
            text-align: center;
        }

        .original-comment {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .original-label {
            display: inline-block;
            background: rgba(0, 102, 204, 0.2);
            color: #0066CC;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .original-author {
            color: #0066CC;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .original-content {
            color: #cccccc;
            line-height: 1.7;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
        }

        .reply-form {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            padding: 30px;
        }

        .reply-form h3 {
            color: #0066CC;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .reply-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 20px;
            font-family: inherit;
        }

        .reply-form textarea:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .reply-form button {
            padding: 12px 40px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reply-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.5);
        }

        .error-message {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid rgba(139, 0, 0, 0.5);
            color: #ff6666;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .existing-replies {
            margin-top: 30px;
        }

        .section-title {
            font-size: 1.3rem;
            color: #0066CC;
            margin-bottom: 20px;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.4);
        }

        .reply-item {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            margin-left: 20px;
            border-left: 3px solid rgba(0, 102, 204, 0.5);
        }

        .reply-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .reply-author {
            color: #0066CC;
            font-weight: bold;
        }

        .reply-time {
            color: #888888;
            font-size: 0.85rem;
        }

        .reply-content {
            color: #cccccc;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .reply-to-reply-button {
            display: inline-block;
            padding: 5px 12px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            border-radius: 12px;
            color: #0066CC;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 5px;
        }

        .reply-to-reply-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 10px rgba(0, 102, 204, 0.4);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="../../game.html" class="logo">六刻巡旅</a>
            <a href="post_detail.php?id=<?php echo $post_id; ?>&room_id=<?php echo $room_id; ?>" class="back-button">← 投稿に戻る</a>
        </nav>
    </header>

    <div class="reply-container">
        <h1 class="page-title">返信する</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="original-comment">
            <span class="original-label">返信先</span>
            <div class="original-author"><?php echo htmlspecialchars($reply_to_comment['name']); ?></div>
            <div class="original-content"><?php echo nl2br(htmlspecialchars($reply_to_comment['content'])); ?></div>
        </div>

        <!-- 既存の返信を表示 -->
        <?php if (!empty($existing_replies)): ?>
            <div class="existing-replies">
                <h2 class="section-title">💬 既存の返信 (<?php echo count($existing_replies); ?>件)</h2>
                <?php foreach ($existing_replies as $reply): ?>
                    <?php
                    $reply_time_diff = time() - strtotime($reply['created_at']);
                    if ($reply_time_diff < 3600) {
                        $reply_time_display = floor($reply_time_diff / 60) . '分前';
                    } elseif ($reply_time_diff < 86400) {
                        $reply_time_display = floor($reply_time_diff / 3600) . '時間前';
                    } else {
                        $reply_time_display = floor($reply_time_diff / 86400) . '日前';
                    }
                    ?>
                    <div class="reply-item">
                        <div class="reply-header">
                            <span class="reply-author"><?php echo htmlspecialchars($reply['name']); ?></span>
                            <span class="reply-time"><?php echo $reply_time_display; ?></span>
                        </div>
                        <div class="reply-content"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></div>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="reply_to.php?reply_to=<?php echo $reply['id']; ?>&post_id=<?php echo $post_id; ?>&room_id=<?php echo $room_id; ?>" class="reply-to-reply-button">💬 この返信に返信する</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="reply-form">
            <h3>あなたの返信</h3>
            <form method="post" action="reply_to.php?reply_to=<?php echo $reply_to_id; ?>&post_id=<?php echo $post_id; ?>&room_id=<?php echo $room_id; ?>">
                <textarea name="content" placeholder="@<?php echo htmlspecialchars($reply_to_comment['name']); ?> への返信を入力してください..." required></textarea>
                <button type="submit" name="post_reply">返信を投稿</button>
            </form>
        </div>
    </div>
</body>
</html>
