<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/likes.php';
require_once __DIR__ . '/notifications.php';

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    echo json_encode(array(
        'success' => false,
        'error' => 'ログインが必要です'
    ));
    exit;
}

// POSTリクエストのみ受け付け
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'error' => '不正なリクエストです'
    ));
    exit;
}

// コメントIDを取得
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

if ($comment_id <= 0) {
    echo json_encode(array(
        'success' => false,
        'error' => '無効なコメントIDです'
    ));
    exit;
}

$user_id = $_SESSION['user_id'];
$liker_name = isset($_SESSION['username']) ? $_SESSION['username'] : '誰か';

// いいねをトグル
$liked = toggleLike($user_id, $comment_id);
$like_count = getLikeCount($comment_id);

// いいねが追加された場合、通知を送る
if ($liked) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 5000');

        // コメントの所有者情報を取得
        $sql = 'SELECT user_id, name, room_id, parent_id, root_post_id FROM room_comments WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment && $comment['user_id'] && $comment['user_id'] != $user_id) {
            // 投稿詳細ページに飛ぶためのpost_idを決定
            // parent_idがnullなら、このコメント自体が親投稿
            // parent_idがあれば、root_post_idまたはparent_idを使う
            if ($comment['parent_id'] === null) {
                $post_id = $comment_id;
            } else {
                $post_id = $comment['root_post_id'] ? $comment['root_post_id'] : $comment['parent_id'];
            }

            $room_id = $comment['room_id'];
            $owner_user_id = $comment['user_id'];
            $owner_name = $comment['name'];

            // 通知メッセージを作成
            $message = $liker_name . ' さんがあなたの投稿にいいねしました';

            // 通知を作成
            createNotificationByUserId($owner_user_id, $owner_name, 'like', $message, $post_id, $room_id);
        }

        $dbh = null;
    } catch (Exception $e) {
        // 通知の失敗はいいね処理自体には影響させない
        error_log("Like notification error: " . $e->getMessage());
    }
}

echo json_encode(array(
    'success' => true,
    'liked' => $liked,
    'like_count' => $like_count
));
?>