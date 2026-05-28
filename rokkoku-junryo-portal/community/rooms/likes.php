<?php
// いいね機能

// いいねテーブルを初期化
function initLikesTable($dbh) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS likes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        comment_id INTEGER NOT NULL,
        created_at TEXT NOT NULL,
        UNIQUE(user_id, comment_id)
    )";
    $dbh->exec($create_table_sql);
}

// いいねを追加
function addLike($user_id, $comment_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');
        $dbh->exec('PRAGMA journal_mode = WAL');

        initLikesTable($dbh);

        $sql = 'INSERT OR IGNORE INTO likes (user_id, comment_id, created_at) VALUES (:user_id, :comment_id, datetime("now", "localtime"))';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = null;
        $dbh = null;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// いいねを削除
function removeLike($user_id, $comment_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');
        $dbh->exec('PRAGMA journal_mode = WAL');

        $sql = 'DELETE FROM likes WHERE user_id = :user_id AND comment_id = :comment_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();

        $stmt = null;
        $dbh = null;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// いいねをトグル（あれば削除、なければ追加）
function toggleLike($user_id, $comment_id) {
    if (hasLiked($user_id, $comment_id)) {
        removeLike($user_id, $comment_id);
        return false; // いいね解除
    } else {
        addLike($user_id, $comment_id);
        return true; // いいね追加
    }
}

// ユーザーがいいねしているかチェック
function hasLiked($user_id, $comment_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');

        // テーブルが存在するか確認
        initLikesTable($dbh);

        $sql = 'SELECT COUNT(*) FROM likes WHERE user_id = :user_id AND comment_id = :comment_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $stmt = null;
        $dbh = null;
        return $count > 0;
    } catch (Exception $e) {
        return false;
    }
}

// コメントのいいね数を取得
function getLikeCount($comment_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');

        // テーブルが存在するか確認
        initLikesTable($dbh);

        $sql = 'SELECT COUNT(*) FROM likes WHERE comment_id = :comment_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $stmt = null;
        $dbh = null;
        return intval($count);
    } catch (Exception $e) {
        return 0;
    }
}

// 複数コメントのいいね数を一括取得
function getLikeCounts($comment_ids) {
    if (empty($comment_ids)) {
        return array();
    }

    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');

        // テーブルが存在するか確認
        initLikesTable($dbh);

        $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
        $sql = "SELECT comment_id, COUNT(*) as count FROM likes WHERE comment_id IN ($placeholders) GROUP BY comment_id";
        $stmt = $dbh->prepare($sql);
        $stmt->execute($comment_ids);

        $result = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['comment_id']] = intval($row['count']);
        }

        $stmt = null;
        $dbh = null;
        return $result;
    } catch (Exception $e) {
        return array();
    }
}

// ユーザーがいいねしているコメントIDを一括取得
function getUserLikedComments($user_id, $comment_ids) {
    if (empty($comment_ids) || !$user_id) {
        return array();
    }

    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->exec('PRAGMA busy_timeout = 10000');

        // テーブルが存在するか確認
        initLikesTable($dbh);

        $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));
        $sql = "SELECT comment_id FROM likes WHERE user_id = ? AND comment_id IN ($placeholders)";
        $params = array_merge(array($user_id), $comment_ids);
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);

        $result = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = intval($row['comment_id']);
        }

        $stmt = null;
        $dbh = null;
        return $result;
    } catch (Exception $e) {
        return array();
    }
}
?>