<?php
session_start();

// いいね機能を読み込み
require_once __DIR__ . '/likes.php';

// 投稿IDとroom_idを取得
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 2;

// room_idに対応する部屋のファイル名
$room_files = array(
    2 => 'room_guide.php',
    3 => 'room_discussion.php',
    4 => 'room_qa.php'
);
$back_url = isset($room_files[$room_id]) ? $room_files[$room_id] : 'room_guide.php';

if ($post_id <= 0) {
    // IDが無効な場合は部屋に戻る
    header('Location: ' . $back_url);
    exit;
}

// コメント編集処理
$edit_error_message = '';
if (isset($_POST['edit_comment'])) {
    // NGワードフィルターを読み込み
    require_once __DIR__ . '/ng_word_filter.php';

    $edit_comment_id = intval($_POST['edit_comment_id']);
    $edit_content = $_POST['edit_content'];
    $edit_error = false;

    // NGワードチェック
    $ng_check = checkNgWords($edit_content);
    if ($ng_check !== false) {
        $edit_error = true;
        $edit_error_message = '不適切な表現が含まれています。投稿内容を修正してください。';
    }

    if (!$edit_error && !empty($edit_content)) {
        try {
            $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 自分の投稿かどうか確認（user_idで照合）
            $check_sql = 'SELECT user_id FROM room_comments WHERE id = :id AND room_id = :room_id';
            $check_stmt = $dbh->prepare($check_sql);
            $check_stmt->bindParam(':id', $edit_comment_id, PDO::PARAM_INT);
            $check_stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($check_result && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $check_result['user_id']) {
                // 編集を実行
                $sql = 'UPDATE room_comments SET content = :content WHERE id = :id AND room_id = :room_id';
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(':content', $edit_content, PDO::PARAM_STR);
                $stmt->bindParam(':id', $edit_comment_id, PDO::PARAM_INT);
                $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
                $stmt->execute();
            }

            $dbh = null;
            header('Location: post_detail.php?id=' . $post_id . '&room_id=' . $room_id);
            exit;
        } catch (Exception $e) {
            // エラー処理
        }
    }
}

// コメント削除処理
if (isset($_POST['delete_comment'])) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $comment_id = $_POST['comment_id'];

        // 削除するコメントの情報を取得（user_idと親コメントかどうか）
        $check_sql = 'SELECT user_id, parent_id FROM room_comments WHERE id = :id';
        $check_stmt = $dbh->prepare($check_sql);
        $check_stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);

        // 自分の投稿かどうか確認（user_idで照合）
        if ($check_result && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $check_result['user_id']) {
            if ($check_result['parent_id'] === null) {
                // 親コメントの場合、全ての返信も削除
                $delete_replies_sql = 'DELETE FROM room_comments WHERE parent_id = :parent_id';
                $delete_replies_stmt = $dbh->prepare($delete_replies_sql);
                $delete_replies_stmt->bindParam(':parent_id', $comment_id, PDO::PARAM_INT);
                $delete_replies_stmt->execute();
            }

            // コメント自体を削除
            $sql = 'DELETE FROM room_comments WHERE id = :id AND room_id = :room_id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $comment_id, PDO::PARAM_INT);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();
        }

        $dbh = null;

        // 親コメントを削除した場合は部屋に戻る
        if ($check_result && $check_result['parent_id'] === null) {
            header('Location: ' . $back_url);
        } else {
            header('Location: post_detail.php?id=' . $post_id . '&room_id=' . $room_id);
        }
        exit;
    } catch (Exception $e) {
        // エラー処理
    }
}

// 運営アカウント名
define('OFFICIAL_ACCOUNT_NAME', '六刻巡旅 公式');

// 通知機能を読み込み
require_once __DIR__ . '/notifications.php';

// 返信投稿処理
$ng_error_message = '';
$reply_error_message = '';
if (isset($_POST['post_reply'])) {
    // NGワードフィルターを読み込み
    require_once __DIR__ . '/ng_word_filter.php';

    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $ng_error = false;

    if (empty($content)) {
        $reply_error_message = '返信内容を入力してください。';
    } else {
        // NGワードチェック
        $ng_check = checkNgWords($content);
        if ($ng_check !== false) {
            $ng_error = true;
            $ng_error_message = '不適切な表現が含まれています。投稿内容を修正してください。';
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
                $reply_to_id = isset($_POST['reply_to_id']) ? intval($_POST['reply_to_id']) : 0;

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

                    // 返信先のチェック
                    if ($reply_to_id > 0 && $reply_to_id != $post_id) {
                        // 返信への返信の場合
                        $reply_check_sql = 'SELECT name FROM room_comments WHERE id = :id';
                        $reply_check_stmt = $dbh->prepare($reply_check_sql);
                        $reply_check_stmt->bindParam(':id', $reply_to_id, PDO::PARAM_INT);
                        $reply_check_stmt->execute();
                        $reply_target = $reply_check_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($reply_target) {
                            // 運営の返信への返信は、元の投稿者のみ可能
                            if ($reply_target['name'] === OFFICIAL_ACCOUNT_NAME) {
                                if ($name !== $original_poster) {
                                    $can_reply = false;
                                    $reply_error_message = '運営への返信は投稿者のみ可能です。';
                                }
                            } else {
                                // 運営以外への返信は不可（運営のみ可能）
                                if ($name !== OFFICIAL_ACCOUNT_NAME) {
                                    $can_reply = false;
                                    $reply_error_message = 'この部屋では運営のみが投稿に返信できます。';
                                }
                            }
                        }
                    } else {
                        // 元投稿への直接返信は運営のみ可能
                        if ($name !== OFFICIAL_ACCOUNT_NAME) {
                            $can_reply = false;
                            $reply_error_message = 'この部屋では運営のみが投稿に返信できます。';
                        }
                    }
                }

                if ($can_reply) {
                    // 返信先が指定されている場合はその情報を取得
                    if ($reply_to_id > 0) {
                        $check_sql = 'SELECT name FROM room_comments WHERE id = :id';
                        $check_stmt = $dbh->prepare($check_sql);
                        $check_stmt->bindParam(':id', $reply_to_id, PDO::PARAM_INT);
                        $check_stmt->execute();
                        $reply_to = $check_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($reply_to) {
                            // @ユーザー名を先頭に追加
                            $content = '@' . $reply_to['name'] . ' ' . $content;
                        }
                    }

                    // parent_idの値を決定
                    $parent_id_value = ($reply_to_id > 0) ? $reply_to_id : $post_id;
                    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

                    $sql = 'INSERT INTO room_comments (room_id, name, user_id, content, created_at, parent_id, root_post_id) VALUES (:room_id, :name, :user_id, :content, datetime("now", "localtime"), :parent_id, :root_post_id)';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
                    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
                    $stmt->bindValue(':parent_id', $parent_id_value, PDO::PARAM_INT);
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

                        $notify_insert_sql = 'INSERT INTO notifications (user_id, user_name, type, message, post_id, room_id, is_read, created_at) VALUES (:user_id, :user_name, :type, :message, :post_id, :room_id, 0, datetime("now", "localtime"))';

                        foreach ($thread_users as $thread_user) {
                            // 元の投稿者には「あなたの投稿に返信」、それ以外には「スレッドに新しい返信」
                            if ($thread_user['user_id'] == $post_owner_user_id) {
                                $message = $name . ' さんがあなたの投稿に返信しました';
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
                // エラー処理
                $reply_error_message = 'エラーが発生しました。もう一度お試しください。';
                error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿詳細 - 六刻巡旅コミュニティ</title>
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

        .post-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .main-post {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95), rgba(20, 20, 40, 0.95));
            border: 2px solid rgba(0, 102, 204, 0.6);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 0 30px rgba(0, 102, 204, 0.2);
            position: relative;
        }

        .main-post::before {
            content: '📝 投稿';
            position: absolute;
            top: -12px;
            left: 20px;
            background: linear-gradient(135deg, #0066CC, #0088FF);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .post-author {
            color: #0066CC;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .post-time {
            color: #888888;
            font-size: 0.9rem;
        }

        .post-content {
            color: #e0e0e0;
            line-height: 1.8;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .delete-button {
            padding: 8px 20px;
            background: rgba(139, 0, 0, 0.3);
            border: 1px solid rgba(139, 0, 0, 0.5);
            color: #ff6666;
            border-radius: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .delete-button:hover {
            background: rgba(139, 0, 0, 0.5);
            box-shadow: 0 0 15px rgba(139, 0, 0, 0.5);
        }

        .edit-button {
            padding: 8px 20px;
            background: rgba(0, 102, 204, 0.3);
            border: 1px solid rgba(0, 102, 204, 0.5);
            color: #0066CC;
            border-radius: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .edit-button:hover {
            background: rgba(0, 102, 204, 0.5);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            float: right;
        }

        .edit-form {
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }

        .edit-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 15px;
            font-family: inherit;
        }

        .edit-form textarea:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .edit-form-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-form button {
            padding: 10px 25px;
            border: none;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-form button[type="submit"] {
            background: linear-gradient(135deg, #0066CC, #0080FF);
            color: white;
        }

        .edit-form button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.5);
        }

        .edit-form button[type="button"] {
            background: rgba(100, 100, 100, 0.3);
            border: 1px solid rgba(150, 150, 150, 0.5);
            color: #cccccc;
        }

        .edit-form button[type="button"]:hover {
            background: rgba(100, 100, 100, 0.5);
        }

        .replies-section {
            margin-top: 40px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 25px;
            text-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .replies-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .replies-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .replies-grid {
                grid-template-columns: 1fr;
            }
        }

        .reply-item {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 12px;
            padding: 20px;
            border-left: 3px solid rgba(0, 102, 204, 0.5);
            display: flex;
            flex-direction: column;
        }

        .reply-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }

        .reply-icon {
            color: #0066CC;
            margin-right: 5px;
        }

        .reply-author {
            color: #0066CC;
            font-weight: bold;
        }

        .own-label {
            color: #00CC66;
            font-size: 0.85rem;
            font-weight: normal;
            margin-left: 5px;
        }

        .reply-time {
            color: #888888;
            font-size: 0.85rem;
        }

        .reply-count {
            display: inline-block;
            margin-left: 10px;
            padding: 4px 12px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.85rem;
        }

        .reply-content {
            color: #cccccc;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .reply-to-button {
            display: inline-block;
            padding: 6px 15px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .reply-to-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 10px rgba(0, 102, 204, 0.4);
        }

        .nested-reply {
            margin-left: 30px;
            margin-top: 10px;
            padding-left: 15px;
            border-left: 2px solid rgba(0, 102, 204, 0.3);
        }

        .reply-form {
            background: rgba(26, 26, 26, 0.8);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .reply-form h3 {
            color: #0066CC;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .reply-form textarea {
            width: 100%;
            min-height: 120px;
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

        .no-replies {
            text-align: center;
            color: #888888;
            padding: 40px 20px;
            font-size: 1.1rem;
        }

        .login-required {
            background: rgba(0, 102, 204, 0.1);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            text-align: center;
        }

        .login-required p {
            color: #e0e0e0;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .login-required a {
            color: #0066CC;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 25px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 25px;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .login-required a:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        @media (max-width: 768px) {
            .nested-reply {
                margin-left: 15px;
            }
        }

        /* いいねボタン */
        .like-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 102, 204, 0.2);
        }

        .like-button {
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 20px;
            padding: 8px 16px;
            color: #cccccc;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .like-button:hover {
            background: rgba(0, 102, 204, 0.2);
            border-color: rgba(0, 102, 204, 0.5);
            transform: scale(1.05);
        }

        .like-button.liked {
            background: linear-gradient(135deg, rgba(255, 68, 68, 0.4), rgba(255, 100, 100, 0.3));
            border-color: #ff6666;
            color: #ff4444;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.4);
            transform: scale(1.05);
        }

        .like-button.liked:hover {
            background: linear-gradient(135deg, rgba(255, 68, 68, 0.5), rgba(255, 100, 100, 0.4));
            box-shadow: 0 0 15px rgba(255, 68, 68, 0.6);
        }

        .liked-text {
            font-size: 0.8rem;
            margin-left: 3px;
        }

        .like-display {
            color: #888888;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .like-count {
            font-weight: bold;
        }

        .reply-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .reply-actions .like-button {
            padding: 5px 12px;
            font-size: 0.85rem;
        }

        .reply-actions .like-display {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="../../game.html" class="logo">六刻巡旅</a>
            <a href="<?php echo $back_url; ?>" class="back-button">← 部屋に戻る</a>
        </nav>
    </header>

    <div class="post-container">
        <?php
        // メイン投稿を取得
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

            $sql = 'SELECT id, name, user_id, content, created_at FROM room_comments WHERE id = :id AND room_id = :room_id AND parent_id IS NULL';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $stmt->execute();

            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                echo '<p style="color: red; text-align: center;">投稿が見つかりません。</p>';
                exit;
            }

            // すべての返信を先に取得して、いいね情報を取得する
            $all_replies_sql = 'SELECT id, name, user_id, content, created_at, parent_id FROM room_comments
                               WHERE room_id = :room_id AND (root_post_id = :post_id OR (parent_id = :post_id2 AND root_post_id IS NULL))
                               ORDER BY id ASC';
            $all_replies_stmt = $dbh->prepare($all_replies_sql);
            $all_replies_stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
            $all_replies_stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $all_replies_stmt->bindParam(':post_id2', $post_id, PDO::PARAM_INT);
            $all_replies_stmt->execute();

            $all_replies = [];
            while ($r = $all_replies_stmt->fetch(PDO::FETCH_ASSOC)) {
                $all_replies[] = $r;
            }

            // いいね情報を取得（メイン投稿と全返信のIDを収集）
            $all_comment_ids = array($post_id);
            foreach ($all_replies as $r) {
                $all_comment_ids[] = $r['id'];
            }
            $like_counts = getLikeCounts($all_comment_ids);
            $user_liked_comments = array();
            if (isset($_SESSION['user_id'])) {
                $user_liked_comments = getUserLikedComments($_SESSION['user_id'], $all_comment_ids);
            }

            // メイン投稿を表示
            $name = htmlspecialchars($post['name']);
            $content = htmlspecialchars($post['content']);
            $created_at = $post['created_at'];
            $post_user_id = $post['user_id'];

            $time_diff = time() - strtotime($created_at);
            if ($time_diff < 3600) {
                $time_display = floor($time_diff / 60) . '分前';
            } elseif ($time_diff < 86400) {
                $time_display = floor($time_diff / 3600) . '時間前';
            } else {
                $time_display = floor($time_diff / 86400) . '日前';
            }

            // 編集モードかどうかをチェック
            $is_editing_main = isset($_GET['edit']) && $_GET['edit'] == $post['id'];

            echo '<div class="main-post">';

            // 自分の投稿の場合のみ編集・削除ボタン（user_idで照合）
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post_user_id) {
                echo '<div class="action-buttons">';
                if (!$is_editing_main) {
                    echo '<a href="post_detail.php?id=' . $post_id . '&room_id=' . $room_id . '&edit=' . $post['id'] . '" class="edit-button">✏️ 編集</a>';
                }
                echo '<form method="post" style="display: inline;">';
                echo '<input type="hidden" name="comment_id" value="' . $post['id'] . '">';
                echo '<button type="submit" name="delete_comment" class="delete-button" onclick="return confirm(\'この投稿を削除しますか？削除すると全ての返信も削除されます。\')">🗑️ 削除</button>';
                echo '</form>';
                echo '</div>';
            }

            echo '<div class="post-header">';
            $is_own_post = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post_user_id;
            $own_label = $is_own_post ? '<span class="own-label">（自分）</span>' : '';
            echo '<span class="post-author">' . $name . $own_label . '</span>';
            echo '<span class="post-time">' . $time_display . '</span>';
            echo '</div>';

            if ($is_editing_main) {
                // 編集フォームを表示
                echo '<div class="edit-form">';
                if (!empty($edit_error_message)) {
                    echo '<div style="background: rgba(139, 0, 0, 0.3); border: 1px solid #ff4444; border-radius: 10px; padding: 15px; margin-bottom: 15px; color: #ff6666; text-align: center;">' . htmlspecialchars($edit_error_message) . '</div>';
                }
                echo '<form method="post">';
                echo '<input type="hidden" name="edit_comment_id" value="' . $post['id'] . '">';
                echo '<textarea name="edit_content" required>' . $post['content'] . '</textarea>';
                echo '<div class="edit-form-buttons">';
                echo '<button type="submit" name="edit_comment">保存する</button>';
                echo '<a href="post_detail.php?id=' . $post_id . '&room_id=' . $room_id . '"><button type="button">キャンセル</button></a>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
            } else {
                echo '<div class="post-content">' . nl2br($content) . '</div>';

                // いいねボタン
                $main_like_count = isset($like_counts[$post_id]) ? $like_counts[$post_id] : 0;
                $main_has_liked = in_array($post_id, $user_liked_comments);
                $main_liked_class = $main_has_liked ? 'liked' : '';
                echo '<div class="like-section">';
                if (isset($_SESSION['user_id'])) {
                    echo '<button class="like-button ' . $main_liked_class . '" data-comment-id="' . $post_id . '" onclick="toggleLike(' . $post_id . ', this)">';
                    if ($main_has_liked) {
                        echo '❤️ <span class="like-count">' . $main_like_count . '</span> <span class="liked-text">いいね済み</span>';
                    } else {
                        echo '🤍 <span class="like-count">' . $main_like_count . '</span>';
                    }
                    echo '</button>';
                } else {
                    echo '<span class="like-display">❤️ <span class="like-count">' . $main_like_count . '</span></span>';
                }
                echo '</div>';
            }
            echo '</div>';

            // 返信を階層構造で整理
            function buildReplyTree($replies, $parent_id) {
                $tree = [];
                foreach ($replies as $reply) {
                    if ($reply['parent_id'] == $parent_id) {
                        $tree[] = $reply;
                    }
                }
                return $tree;
            }

            // 返信を再帰的に表示する関数
            function displayReply($reply, $all_replies, $is_nested = false) {
                global $post_id, $room_id, $edit_error_message, $like_counts, $user_liked_comments;

                $reply_name = htmlspecialchars($reply['name']);
                $reply_content = htmlspecialchars($reply['content']);
                $reply_created_at = $reply['created_at'];
                $reply_user_id = $reply['user_id'];

                $reply_time_diff = time() - strtotime($reply_created_at);
                if ($reply_time_diff < 3600) {
                    $reply_time_display = floor($reply_time_diff / 60) . '分前';
                } elseif ($reply_time_diff < 86400) {
                    $reply_time_display = floor($reply_time_diff / 3600) . '時間前';
                } else {
                    $reply_time_display = floor($reply_time_diff / 86400) . '日前';
                }

                // この返信への返信数を計算
                $nested_replies = buildReplyTree($all_replies, $reply['id']);
                $reply_count = count($nested_replies);

                // 編集モードかどうかをチェック
                $is_editing_reply = isset($_GET['edit']) && $_GET['edit'] == $reply['id'];

                $class = $is_nested ? 'reply-item nested-reply' : 'reply-item';
                echo '<div class="' . $class . '">';

                // 自分の返信の場合のみ編集・削除ボタン（user_idで照合）
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply_user_id) {
                    echo '<div class="action-buttons">';
                    if (!$is_editing_reply) {
                        echo '<a href="post_detail.php?id=' . $post_id . '&room_id=' . $room_id . '&edit=' . $reply['id'] . '" class="edit-button">✏️ 編集</a>';
                    }
                    echo '<form method="post" style="display: inline;">';
                    echo '<input type="hidden" name="comment_id" value="' . $reply['id'] . '">';
                    echo '<button type="submit" name="delete_comment" class="delete-button" onclick="return confirm(\'この返信を削除しますか？\')">🗑️ 削除</button>';
                    echo '</form>';
                    echo '</div>';
                }

                echo '<div class="reply-header">';
                echo '<span class="reply-icon">↳</span>';
                $is_own_reply = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply_user_id;
                $own_label = $is_own_reply ? '<span class="own-label">（自分）</span>' : '';
                echo '<span class="reply-author">' . $reply_name . $own_label . '</span>';
                echo '<span class="reply-time">' . $reply_time_display . '</span>';
                echo '</div>';

                if ($is_editing_reply) {
                    // 編集フォームを表示
                    echo '<div class="edit-form">';
                    if (!empty($edit_error_message)) {
                        echo '<div style="background: rgba(139, 0, 0, 0.3); border: 1px solid #ff4444; border-radius: 10px; padding: 15px; margin-bottom: 15px; color: #ff6666; text-align: center;">' . htmlspecialchars($edit_error_message) . '</div>';
                    }
                    echo '<form method="post">';
                    echo '<input type="hidden" name="edit_comment_id" value="' . $reply['id'] . '">';
                    echo '<textarea name="edit_content" required>' . $reply['content'] . '</textarea>';
                    echo '<div class="edit-form-buttons">';
                    echo '<button type="submit" name="edit_comment">保存する</button>';
                    echo '<a href="post_detail.php?id=' . $post_id . '&room_id=' . $room_id . '"><button type="button">キャンセル</button></a>';
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';
                } else {
                    echo '<div class="reply-content">' . nl2br($reply_content) . '</div>';

                    // いいねボタン
                    $reply_like_count = isset($like_counts[$reply['id']]) ? $like_counts[$reply['id']] : 0;
                    $reply_has_liked = in_array($reply['id'], $user_liked_comments);
                    $reply_liked_class = $reply_has_liked ? 'liked' : '';

                    echo '<div class="reply-actions">';

                    // ログインユーザーのみ返信ボタンを表示
                    // Q&A部屋の場合は特別なルール：運営の返信には元投稿者のみが返信可能
                    if (isset($_SESSION['user_id'])) {
                        $show_reply_button = true;

                        if ($room_id === 4) {
                            // Q&A部屋の場合
                            // 運営の返信に対しては、元投稿者のみが返信可能
                            if ($reply['name'] === OFFICIAL_ACCOUNT_NAME) {
                                // 運営の返信 → 元投稿者のみ返信可能
                                // $post変数はグローバルスコープにないので、投稿者名を取得する必要がある
                                // ここでは返信ボタンを表示し、投稿時に検証する
                                $show_reply_button = true;
                            } else {
                                // 運営以外の返信 → 運営のみ返信可能（一般ユーザーは返信ボタンを非表示）
                                $show_reply_button = false;
                            }
                        }

                        if ($show_reply_button) {
                            echo '<a href="reply_to.php?reply_to=' . $reply['id'] . '&post_id=' . $post_id . '&room_id=' . $room_id . '" class="reply-to-button">💬 返信する</a>';
                        }

                        // いいねボタン（ログインユーザー）
                        echo '<button class="like-button ' . $reply_liked_class . '" data-comment-id="' . $reply['id'] . '" onclick="toggleLike(' . $reply['id'] . ', this)">';
                        if ($reply_has_liked) {
                            echo '❤️ <span class="like-count">' . $reply_like_count . '</span> <span class="liked-text">いいね済み</span>';
                        } else {
                            echo '🤍 <span class="like-count">' . $reply_like_count . '</span>';
                        }
                        echo '</button>';
                    } else {
                        // 非ログイン時はいいね数のみ表示
                        echo '<span class="like-display">❤️ <span class="like-count">' . $reply_like_count . '</span></span>';
                    }

                    echo '</div>';
                }

                // ネストされた返信は表示せず、返信ボタンから遷移した先で表示する
                // （返信数のバッジのみ表示済み）

                echo '</div>';
            }

            echo '<div class="replies-section">';
            $direct_replies = buildReplyTree($all_replies, $post_id);
            $total_count = count($all_replies);

            echo '<h2 class="section-title">💬 返信 (' . $total_count . ')</h2>';

            if ($total_count == 0) {
                echo '<div class="no-replies">まだ返信がありません。最初の返信を投稿してみませんか？</div>';
            } else {
                echo '<div class="replies-grid">';
                foreach ($direct_replies as $reply) {
                    displayReply($reply, $all_replies, false);
                }
                echo '</div>';
            }

            echo '</div>';

            $dbh = null;
        } catch (Exception $e) {
            echo '<p style="color: red;">エラーが発生しました: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>

        <!-- 返信フォーム - メイン投稿への直接返信はページ内で可能 -->
        <?php if (!empty($ng_error_message)): ?>
            <div style="background: rgba(139, 0, 0, 0.3); border: 1px solid #ff4444; border-radius: 10px; padding: 15px; margin: 20px 0; color: #ff6666; text-align: center;">
                <?php echo htmlspecialchars($ng_error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($reply_error_message)): ?>
            <div style="background: rgba(139, 0, 0, 0.3); border: 1px solid #ff4444; border-radius: 10px; padding: 15px; margin: 20px 0; color: #ff6666; text-align: center;">
                <?php echo htmlspecialchars($reply_error_message); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($room_id === 4): ?>
                <!-- Q&A部屋の場合は返信フォームを表示しない（運営のみ管理画面から返信） -->
                <div class="login-required" style="background: rgba(0, 102, 204, 0.1);">
                    <p>💬 この部屋では運営のみが投稿に返信できます</p>
                    <p style="font-size: 0.9rem; color: #888888; margin-top: 10px;">運営からの返信をお待ちください</p>
                </div>
            <?php else: ?>
                <div class="reply-form">
                    <h3>返信を投稿する</h3>
                    <form method="post" action="post_detail.php?id=<?php echo $post_id; ?>&room_id=<?php echo $room_id; ?>">
                        <textarea name="content" placeholder="返信内容を入力してください..." required></textarea>
                        <button type="submit" name="post_reply">返信を投稿</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="login-required">
                <p>💬 返信を投稿するにはログインが必要です</p>
                <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">ログイン</a>
                または
                <a href="../auth/register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">新規登録</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function toggleLike(commentId, button) {
        fetch('like_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'comment_id=' + commentId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const countSpan = button.querySelector('.like-count');
                countSpan.textContent = data.like_count;

                if (data.liked) {
                    button.classList.add('liked');
                    button.innerHTML = '❤️ <span class="like-count">' + data.like_count + '</span> <span class="liked-text">いいね済み</span>';
                } else {
                    button.classList.remove('liked');
                    button.innerHTML = '🤍 <span class="like-count">' + data.like_count + '</span>';
                }
            } else {
                alert(data.error || 'エラーが発生しました');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('通信エラーが発生しました');
        });
    }
    </script>
</body>
</html>
