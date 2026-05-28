<?php
session_start();
header('Content-Type: application/json');

// いいね機能を読み込み
require_once __DIR__ . '/likes.php';

try {
    $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // room_idパラメータを取得（デフォルトは2）
    $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 2;

    $sql = 'SELECT id, name, user_id, content, created_at, parent_id FROM room_comments WHERE room_id = :room_id ORDER BY id DESC';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_INT);
    $stmt->execute();

    $comments = [];
    $all_comments = [];
    while ($rec = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $all_comments[] = $rec;
    }

    // コメントIDを収集していいね数を一括取得
    $comment_ids = array_column($all_comments, 'id');
    $like_counts = getLikeCounts($comment_ids);

    // ログイン中のユーザーがいいねしたコメントを取得
    $user_liked_comments = [];
    if (isset($_SESSION['user_id'])) {
        $user_liked_comments = getUserLikedComments($_SESSION['user_id'], $comment_ids);
    }

    // 各コメントの返信数を計算
    foreach ($all_comments as $rec) {
        $reply_count = 0;
        foreach ($all_comments as $potential_reply) {
            if ($potential_reply['parent_id'] == $rec['id']) {
                $reply_count++;
            }
        }

        $comments[] = [
            'id' => $rec['id'],
            'name' => htmlspecialchars($rec['name']),
            'content' => htmlspecialchars($rec['content']),
            'created_at' => $rec['created_at'],
            'parent_id' => $rec['parent_id'],
            'is_own' => isset($_SESSION['user_id']) && $rec['user_id'] !== null && $_SESSION['user_id'] == $rec['user_id'],
            'reply_count' => $reply_count,
            'like_count' => isset($like_counts[$rec['id']]) ? $like_counts[$rec['id']] : 0
        ];
    }

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'current_user' => isset($_SESSION['username']) ? $_SESSION['username'] : null,
        'is_logged_in' => isset($_SESSION['user_id']),
        'user_liked_comments' => $user_liked_comments
    ]);

    $dbh = null;
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
