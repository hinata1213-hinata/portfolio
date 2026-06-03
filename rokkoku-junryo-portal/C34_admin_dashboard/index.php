<?php
session_start();

$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] !== '#' && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD'));

$error_message = '';
$success_message = '';
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// 現在のタブ
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

// ログアウト処理
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    $is_logged_in = false;
}

// ログイン処理
if (isset($_POST['admin_login'])) {
    $password = $_POST['admin_password'];
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $error_message = 'パスワードが正しくありません。';
    }
}

// 部屋名の定義
$room_names = array(
    2 => '攻略情報共有部屋',
    3 => '考察・感想部屋',
    4 => '運営Q&A部屋'
);

// 運営アカウント名
define('OFFICIAL_ACCOUNT_NAME', '六刻巡旅 公式');
define('OFFICIAL_ACCOUNT_PASSWORD', '123456');

// 運営アカウントの存在確認と作成
if ($is_logged_in) {
    try {
        $users_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_users.db';
        $users_dbh = new PDO($users_dsn);
        $users_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $users_dbh->exec('PRAGMA busy_timeout = 15000');
        $users_dbh->exec('PRAGMA journal_mode = WAL');

        $check_sql = 'SELECT id FROM users WHERE username = :username';
        $check_stmt = $users_dbh->prepare($check_sql);
        $check_stmt->bindValue(':username', OFFICIAL_ACCOUNT_NAME, PDO::PARAM_STR);
        $check_stmt->execute();

        if (!$check_stmt->fetch()) {
            // 運営アカウントを作成
            $hashed_password = password_hash(OFFICIAL_ACCOUNT_PASSWORD, PASSWORD_DEFAULT);
            $create_sql = 'INSERT INTO users (username, password, created_at) VALUES (:username, :password, datetime("now", "localtime"))';
            $create_stmt = $users_dbh->prepare($create_sql);
            $create_stmt->bindValue(':username', OFFICIAL_ACCOUNT_NAME, PDO::PARAM_STR);
            $create_stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);
            $create_stmt->execute();
            $create_stmt = null;
        }

        $check_stmt = null;
        $users_dbh = null;
    } catch (Exception $e) {
        // エラーは無視
    }
}

// アカウント削除処理
if ($is_logged_in && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $username = $_POST['username'];

    // リトライ処理付きで削除を実行
    $max_retries = 3;
    $retry_count = 0;
    $delete_success = false;

    while ($retry_count < $max_retries && !$delete_success) {
        try {
            // ユーザーデータベースから先に削除（軽い処理を先に）
            $users_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_users.db';
            $users_dbh = new PDO($users_dsn);
            $users_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $users_dbh->exec('PRAGMA busy_timeout = 10000');
            $users_dbh->exec('PRAGMA journal_mode = WAL');

            $delete_sql = 'DELETE FROM users WHERE id = :id';
            $delete_stmt = $users_dbh->prepare($delete_sql);
            $delete_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $delete_stmt->execute();

            // 接続を完全に閉じる
            $delete_stmt = null;
            $users_dbh = null;

            // 少し待機してからroomsデータベースに接続
            usleep(100000); // 0.1秒待機

            // コメントデータベースから該当ユーザーの投稿を削除
            $rooms_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_rooms.db';
            $rooms_dbh = new PDO($rooms_dsn);
            $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rooms_dbh->exec('PRAGMA busy_timeout = 10000');
            $rooms_dbh->exec('PRAGMA journal_mode = WAL');

            // user_idカラムを追加（存在しない場合）
            try {
                $rooms_dbh->exec('ALTER TABLE room_comments ADD COLUMN user_id INTEGER');
            } catch (Exception $e) {
                // カラムが既に存在する場合はエラーになるが無視
            }

            // user_idベースで削除（セキュリティ対応）
            // user_idがnullの古い投稿はユーザー名で削除
            $delete_comments_sql = 'DELETE FROM room_comments WHERE user_id = :user_id OR (user_id IS NULL AND name = :username)';
            $delete_comments_stmt = $rooms_dbh->prepare($delete_comments_sql);
            $delete_comments_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $delete_comments_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $delete_comments_stmt->execute();
            $delete_comments_stmt = null;

            // notificationsテーブルにuser_idカラムを追加（存在しない場合）
            try {
                $rooms_dbh->exec('ALTER TABLE notifications ADD COLUMN user_id INTEGER');
            } catch (Exception $e) {
                // カラムが既に存在する場合はエラーになるが無視
            }

            // 該当ユーザーの通知も削除
            $delete_notifications_sql = 'DELETE FROM notifications WHERE user_id = :user_id OR (user_id IS NULL AND user_name = :username)';
            $delete_notifications_stmt = $rooms_dbh->prepare($delete_notifications_sql);
            $delete_notifications_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $delete_notifications_stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $delete_notifications_stmt->execute();
            $delete_notifications_stmt = null;

            // 接続を閉じる
            $rooms_dbh = null;

            $delete_success = true;
            $success_message = 'ユーザー「' . htmlspecialchars($username) . '」を削除しました。';
        } catch (Exception $e) {
            $retry_count++;
            // ロックエラーの場合はリトライ
            if (strpos($e->getMessage(), 'locked') !== false && $retry_count < $max_retries) {
                usleep(500000); // 0.5秒待機してリトライ
                // 接続をクリア
                $users_dbh = null;
                $rooms_dbh = null;
            } else {
                $error_message = 'エラーが発生しました: ' . $e->getMessage();
                break;
            }
        }
    }
}

// 投稿削除処理（親投稿）
if ($is_logged_in && isset($_POST['delete_post'])) {
    $post_id = intval($_POST['post_id']);

    try {
        $rooms_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_rooms.db';
        $rooms_dbh = new PDO($rooms_dsn);
        $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $rooms_dbh->exec('PRAGMA busy_timeout = 10000');
        $rooms_dbh->exec('PRAGMA journal_mode = WAL');

        // まず返信を削除（parent_idまたはroot_post_idが該当する投稿）
        $delete_replies_sql = 'DELETE FROM room_comments WHERE parent_id = :post_id OR root_post_id = :post_id2';
        $delete_replies_stmt = $rooms_dbh->prepare($delete_replies_sql);
        $delete_replies_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $delete_replies_stmt->bindParam(':post_id2', $post_id, PDO::PARAM_INT);
        $delete_replies_stmt->execute();

        // 投稿本体を削除
        $delete_post_sql = 'DELETE FROM room_comments WHERE id = :id';
        $delete_post_stmt = $rooms_dbh->prepare($delete_post_sql);
        $delete_post_stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
        $delete_post_stmt->execute();

        $rooms_dbh = null;

        $success_message = '投稿を削除しました。';
    } catch (Exception $e) {
        $error_message = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// 運営からの返信処理（Q&A部屋用）
if ($is_logged_in && isset($_POST['official_reply'])) {
    $post_id = intval($_POST['reply_to_post_id']);
    $reply_content = trim($_POST['reply_content']);

    if (!empty($reply_content)) {
        try {
            $rooms_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_rooms.db';
            $rooms_dbh = new PDO($rooms_dsn);
            $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rooms_dbh->exec('PRAGMA busy_timeout = 10000');
            $rooms_dbh->exec('PRAGMA journal_mode = WAL');

            $insert_sql = 'INSERT INTO room_comments (room_id, name, content, created_at, parent_id, root_post_id) VALUES (:room_id, :name, :content, datetime("now", "localtime"), :parent_id, :root_post_id)';
            $insert_stmt = $rooms_dbh->prepare($insert_sql);
            $insert_stmt->bindValue(':room_id', 4, PDO::PARAM_INT); // Q&A部屋
            $insert_stmt->bindValue(':name', OFFICIAL_ACCOUNT_NAME, PDO::PARAM_STR);
            $insert_stmt->bindValue(':content', $reply_content, PDO::PARAM_STR);
            $insert_stmt->bindValue(':parent_id', $post_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(':root_post_id', $post_id, PDO::PARAM_INT);
            $insert_stmt->execute();

            $rooms_dbh = null;

            $success_message = '運営からの返信を投稿しました。';
        } catch (Exception $e) {
            $error_message = 'エラーが発生しました: ' . $e->getMessage();
        }
    } else {
        $error_message = '返信内容を入力してください。';
    }
}

// 返信削除処理
if ($is_logged_in && isset($_POST['delete_reply'])) {
    $reply_id = intval($_POST['reply_id']);

    try {
        $rooms_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_rooms.db';
        $rooms_dbh = new PDO($rooms_dsn);
        $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $rooms_dbh->exec('PRAGMA busy_timeout = 10000');
        $rooms_dbh->exec('PRAGMA journal_mode = WAL');

        // この返信への返信も削除
        $delete_child_replies_sql = 'DELETE FROM room_comments WHERE parent_id = :reply_id';
        $delete_child_stmt = $rooms_dbh->prepare($delete_child_replies_sql);
        $delete_child_stmt->bindParam(':reply_id', $reply_id, PDO::PARAM_INT);
        $delete_child_stmt->execute();

        // 返信本体を削除
        $delete_reply_sql = 'DELETE FROM room_comments WHERE id = :id';
        $delete_reply_stmt = $rooms_dbh->prepare($delete_reply_sql);
        $delete_reply_stmt->bindParam(':id', $reply_id, PDO::PARAM_INT);
        $delete_reply_stmt->execute();

        $rooms_dbh = null;

        $success_message = '返信を削除しました。';
    } catch (Exception $e) {
        $error_message = 'エラーが発生しました: ' . $e->getMessage();
    }
}

// ユーザー一覧を取得
$users = array();
$posts_by_room = array(2 => array(), 3 => array(), 4 => array());
$replies_by_post = array();

if ($is_logged_in) {
    // リトライ処理付きでデータ取得
    $max_retries = 3;

    // ユーザー一覧を取得
    for ($retry = 0; $retry < $max_retries; $retry++) {
        try {
            $dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_users.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->exec('PRAGMA busy_timeout = 15000');
            $dbh->exec('PRAGMA journal_mode = WAL');

            $sql = 'SELECT id, username, created_at FROM users ORDER BY id DESC';
            $stmt = $dbh->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $dbh = null;
            break; // 成功したらループを抜ける
        } catch (Exception $e) {
            $stmt = null;
            $dbh = null;
            if (strpos($e->getMessage(), 'locked') !== false && $retry < $max_retries - 1) {
                usleep(300000); // 0.3秒待機してリトライ
            } else if (empty($error_message)) {
                $error_message = 'データベース接続エラー: ' . $e->getMessage();
            }
        }
    }

    // 少し待機
    usleep(50000);

    // 投稿一覧を取得（部屋別、返信も含む）
    for ($retry = 0; $retry < $max_retries; $retry++) {
        try {
            $rooms_dsn = 'sqlite:' . __DIR__ . '/../community/data/rokkoku_rooms.db';
            $rooms_dbh = new PDO($rooms_dsn);
            $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $rooms_dbh->exec('PRAGMA busy_timeout = 15000');
            $rooms_dbh->exec('PRAGMA journal_mode = WAL');

            // 全ての投稿を取得
            $sql = 'SELECT id, room_id, name, content, created_at, parent_id, root_post_id FROM room_comments ORDER BY created_at DESC';
            $stmt = $rooms_dbh->query($sql);
            $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $rooms_dbh = null;

            foreach ($all_comments as $comment) {
                $room_id = intval($comment['room_id']);

                if ($comment['parent_id'] === null) {
                    // 親投稿
                    if (isset($posts_by_room[$room_id])) {
                        $posts_by_room[$room_id][] = $comment;
                    }
                } else {
                    // 返信
                    $parent_id = intval($comment['root_post_id'] ? $comment['root_post_id'] : $comment['parent_id']);
                    if (!isset($replies_by_post[$parent_id])) {
                        $replies_by_post[$parent_id] = array();
                    }
                    $replies_by_post[$parent_id][] = $comment;
                }
            }
            break; // 成功したらループを抜ける
        } catch (Exception $e) {
            $stmt = null;
            $rooms_dbh = null;
            if (strpos($e->getMessage(), 'locked') !== false && $retry < $max_retries - 1) {
                usleep(300000); // 0.3秒待機してリトライ
            }
            // エラーは無視（既にerror_messageがあれば上書きしない）
        }
    }
}

// 投稿総数を計算
$total_posts = 0;
foreach ($posts_by_room as $posts) {
    $total_posts += count($posts);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ダッシュボード - 六刻巡旅</title>
    <link rel="stylesheet" href="../game.css">
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

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .header-actions a {
            padding: 10px 25px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 25px;
            color: #0066CC;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .header-actions a:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
        }

        .page-title p {
            color: #888888;
            font-size: 1rem;
        }

        .login-container {
            max-width: 400px;
            margin: 60px auto;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.3);
        }

        .login-container h2 {
            color: #0066CC;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            text-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #0066CC;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .submit-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
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
            text-align: center;
        }

        .success-message {
            background: rgba(0, 139, 0, 0.2);
            border: 1px solid rgba(0, 139, 0, 0.5);
            color: #66ff66;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: rgba(0, 102, 204, 0.6);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        }

        .stat-card .stat-number {
            font-size: 3rem;
            color: #0066CC;
            font-weight: bold;
            text-shadow: 0 0 20px rgba(0, 102, 204, 0.5);
        }

        .stat-card .stat-label {
            color: #cccccc;
            margin-top: 10px;
            font-size: 1.1rem;
        }

        /* タブナビゲーション */
        .tab-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tab-nav a {
            padding: 12px 25px;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            color: #888888;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .tab-nav a:hover {
            border-color: rgba(0, 102, 204, 0.6);
            color: #0066CC;
        }

        .tab-nav a.active {
            background: rgba(0, 102, 204, 0.2);
            border-color: #0066CC;
            color: #0066CC;
        }

        .section-title {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0, 102, 204, 0.3);
            text-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .users-table-container {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            overflow: hidden;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: rgba(0, 102, 204, 0.2);
            padding: 18px 15px;
            text-align: left;
            color: #0066CC;
            font-weight: 600;
            border-bottom: 1px solid rgba(0, 102, 204, 0.3);
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
            color: #cccccc;
        }

        .users-table tr:hover {
            background: rgba(0, 102, 204, 0.05);
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .post-content {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .delete-button {
            padding: 8px 20px;
            background: rgba(139, 0, 0, 0.3);
            border: 1px solid rgba(139, 0, 0, 0.5);
            border-radius: 15px;
            color: #ff6666;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .delete-button:hover {
            background: rgba(139, 0, 0, 0.5);
            box-shadow: 0 0 15px rgba(139, 0, 0, 0.5);
        }

        .warning-text {
            color: #ff6666;
            font-size: 0.9rem;
            margin-top: 15px;
            padding: 15px;
            background: rgba(139, 0, 0, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(139, 0, 0, 0.3);
        }

        .no-data {
            text-align: center;
            color: #888888;
            padding: 60px 20px;
            font-size: 1.1rem;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: #888888;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #0066CC;
        }

        .room-badge {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            font-size: 0.85rem;
            color: #0099ff;
        }

        /* 投稿カード */
        .post-card {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .post-header {
            background: rgba(0, 102, 204, 0.15);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .post-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .post-id {
            color: #0066CC;
            font-weight: bold;
        }

        .post-author {
            color: #cccccc;
        }

        .post-date {
            color: #888888;
            font-size: 0.9rem;
        }

        .post-body {
            padding: 20px;
            color: #e0e0e0;
            line-height: 1.6;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
        }

        .replies-section {
            padding: 0;
        }

        .replies-header {
            background: rgba(0, 102, 204, 0.1);
            padding: 10px 20px;
            color: #0066CC;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .reply-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .reply-item:last-child {
            border-bottom: none;
        }

        .reply-item:hover {
            background: rgba(0, 102, 204, 0.05);
        }

        .reply-content {
            flex: 1;
        }

        .reply-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .reply-id {
            color: #0080FF;
            font-size: 0.85rem;
        }

        .reply-author {
            color: #aaaaaa;
            font-size: 0.9rem;
        }

        .reply-date {
            color: #666666;
            font-size: 0.85rem;
        }

        .reply-text {
            color: #cccccc;
            line-height: 1.5;
        }

        .reply-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(0, 128, 255, 0.2);
            border-radius: 8px;
            font-size: 0.75rem;
            color: #0099ff;
            margin-left: 5px;
        }

        .no-replies {
            padding: 15px 20px;
            color: #666666;
            font-size: 0.9rem;
        }

        /* 運営返信フォーム */
        .official-reply-form {
            background: rgba(0, 102, 204, 0.1);
            border-top: 2px solid rgba(0, 102, 204, 0.3);
            padding: 20px;
        }

        .official-reply-header {
            color: #0066CC;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .official-reply-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            margin-bottom: 15px;
        }

        .official-reply-form textarea:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .official-reply-button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 20px;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .official-reply-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.5);
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

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .users-table {
                font-size: 0.85rem;
            }

            .users-table th,
            .users-table td {
                padding: 10px 8px;
            }

            .post-content {
                max-width: 150px;
            }

            .login-container {
                margin: 40px 20px;
                padding: 30px 20px;
            }

            .tab-nav a {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .post-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .reply-item {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php if (!$is_logged_in): ?>
        <!-- ログインフォーム -->
        <header class="site-header">
            <nav class="navbar">
                <a href="../game.html" class="logo">六刻巡旅</a>
            </nav>
        </header>

        <div class="login-container">
            <h2>管理者ログイン</h2>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="admin_password">管理者パスワード</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                </div>
                <button type="submit" name="admin_login" class="submit-button">ログイン</button>
            </form>

            <div class="back-link">
                <a href="../game.html">← トップページに戻る</a>
            </div>
        </div>
    <?php else: ?>
        <!-- 管理画面 -->
        <header class="site-header">
            <nav class="navbar">
                <a href="../game.html" class="logo">六刻巡旅</a>
                <div class="header-actions">
                    <a href="../game.html">ゲームページへ</a>
                    <a href="../community/rooms/index.php">話題の部屋へ</a>
                    <a href="?logout=1">ログアウト</a>
                </div>
            </nav>
        </header>

        <div class="container">
            <div class="page-title">
                <h1>管理者ダッシュボード</h1>
                <p>ユーザー管理・投稿管理</p>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <!-- 統計情報 -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">登録ユーザー数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="stat-label">総投稿数</div>
                </div>
            </div>

            <!-- タブナビゲーション -->
            <div class="tab-nav">
                <a href="?tab=users" class="<?php echo $current_tab === 'users' ? 'active' : ''; ?>">ユーザー管理</a>
                <a href="?tab=room2" class="<?php echo $current_tab === 'room2' ? 'active' : ''; ?>">🎮 攻略部屋</a>
                <a href="?tab=room3" class="<?php echo $current_tab === 'room3' ? 'active' : ''; ?>">🕰️ 考察部屋</a>
                <a href="?tab=room4" class="<?php echo $current_tab === 'room4' ? 'active' : ''; ?>">💬 Q&A部屋</a>
            </div>

            <?php if ($current_tab === 'users'): ?>
                <!-- ユーザー管理 -->
                <h2 class="section-title">ユーザー管理</h2>

                <?php if (empty($users)): ?>
                    <div class="users-table-container">
                        <div class="no-data">登録されているユーザーはいません。</div>
                    </div>
                <?php else: ?>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ユーザー名</th>
                                    <th>登録日時</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        <td>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('本当に「<?php echo htmlspecialchars($user['username']); ?>」を削除しますか？\n\nこのユーザーの全ての投稿も削除されます。');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                <button type="submit" name="delete_user" class="delete-button">削除</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="warning-text">※ユーザーを削除すると、そのユーザーの全ての投稿も削除されます。</div>
                <?php endif; ?>

            <?php else: ?>
                <?php
                // 部屋番号を取得
                $room_id = intval(str_replace('room', '', $current_tab));
                $room_name = isset($room_names[$room_id]) ? $room_names[$room_id] : '不明な部屋';
                $room_posts = isset($posts_by_room[$room_id]) ? $posts_by_room[$room_id] : array();
                ?>

                <!-- 投稿管理 -->
                <h2 class="section-title"><?php echo htmlspecialchars($room_name); ?>の投稿管理</h2>

                <?php if (empty($room_posts)): ?>
                    <div class="users-table-container">
                        <div class="no-data">この部屋には投稿がありません。</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($room_posts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <div class="post-info">
                                    <span class="post-id">#<?php echo htmlspecialchars($post['id']); ?></span>
                                    <span class="post-author"><?php echo htmlspecialchars($post['name']); ?></span>
                                    <span class="post-date"><?php echo htmlspecialchars($post['created_at']); ?></span>
                                </div>
                                <form method="post" style="display: inline;" onsubmit="return confirm('この投稿を削除しますか？\n\nこの投稿への返信も全て削除されます。');">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" name="delete_post" class="delete-button">投稿を削除</button>
                                </form>
                            </div>
                            <div class="post-body">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>

                            <!-- 返信セクション -->
                            <div class="replies-section">
                                <?php
                                $post_replies = isset($replies_by_post[$post['id']]) ? $replies_by_post[$post['id']] : array();
                                if (!empty($post_replies)):
                                ?>
                                    <div class="replies-header">返信 (<?php echo count($post_replies); ?>件)</div>
                                    <?php foreach ($post_replies as $reply): ?>
                                        <div class="reply-item">
                                            <div class="reply-content">
                                                <div class="reply-meta">
                                                    <span class="reply-id">#<?php echo htmlspecialchars($reply['id']); ?></span>
                                                    <span class="reply-author"><?php echo htmlspecialchars($reply['name']); ?></span>
                                                    <span class="reply-date"><?php echo htmlspecialchars($reply['created_at']); ?></span>
                                                    <span class="reply-badge">返信</span>
                                                </div>
                                                <div class="reply-text">
                                                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                                                </div>
                                            </div>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('この返信を削除しますか？');">
                                                <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                                <button type="submit" name="delete_reply" class="delete-button">削除</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-replies">返信はありません</div>
                                <?php endif; ?>

                                <?php if ($room_id === 4): ?>
                                <!-- 運営からの返信フォーム（Q&A部屋のみ） -->
                                <div class="official-reply-form">
                                    <div class="official-reply-header">運営から返信する</div>
                                    <form method="post">
                                        <input type="hidden" name="reply_to_post_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="reply_content" placeholder="運営からの返信を入力..." required></textarea>
                                        <button type="submit" name="official_reply" class="official-reply-button">返信を投稿</button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="warning-text">※投稿を削除すると、その投稿への返信も全て削除されます。返信は個別に削除することもできます。</div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="../legal/privacy.html">プライバシーポリシー</a>
                    <a href="../legal/terms-of-service.html">利用規約</a>
                    <a href="../community/confirm/confirm.html">お問い合わせ</a>
                </div>
                <div class="copyright">
                    &copy; 2025 六刻巡旅 - All rights reserved
                </div>
            </div>
        </footer>
    <?php endif; ?>
</body>
</html>