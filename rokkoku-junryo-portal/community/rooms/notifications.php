<?php
/**
 * 通知管理機能
 */

// 通知テーブルの作成（存在しない場合）
function initNotificationsTable($dbh) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        user_name TEXT NOT NULL,
        type TEXT NOT NULL,
        message TEXT NOT NULL,
        post_id INTEGER,
        room_id INTEGER,
        is_read INTEGER DEFAULT 0,
        created_at TEXT NOT NULL
    )";
    $dbh->exec($create_table_sql);

    // 既存のテーブルにuser_idカラムがない場合は追加
    try {
        $dbh->exec('ALTER TABLE notifications ADD COLUMN user_id INTEGER');
    } catch (Exception $e) {
        // カラムが既に存在する場合はエラーになるが無視
    }
}

// 通知を作成（user_idベース）
function createNotificationByUserId($user_id, $user_name, $type, $message, $post_id, $room_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // SQLiteのビジータイムアウトを5秒に設定
        $dbh->setAttribute(PDO::ATTR_TIMEOUT, 5);
        $dbh->exec('PRAGMA busy_timeout = 5000');

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'INSERT INTO notifications (user_id, user_name, type, message, post_id, room_id, is_read, created_at) VALUES (:user_id, :user_name, :type, :message, :post_id, :room_id, 0, datetime("now", "localtime"))';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        $dbh = null;
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

// 後方互換性のため、user_nameで通知を作成する関数も残す
function createNotification($user_name, $type, $message, $post_id, $room_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // SQLiteのビジータイムアウトを5秒に設定
        $dbh->setAttribute(PDO::ATTR_TIMEOUT, 5);
        $dbh->exec('PRAGMA busy_timeout = 5000');

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'INSERT INTO notifications (user_name, type, message, post_id, room_id, is_read, created_at) VALUES (:user_name, :type, :message, :post_id, :room_id, 0, datetime("now", "localtime"))';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->execute();

        $dbh = null;
        return true;
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

// ユーザーの未読通知を取得（user_idベース）
function getUnreadNotificationsByUserId($user_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
        return $notifications;
    } catch (Exception $e) {
        return array();
    }
}

// 後方互換性のため、user_nameで通知を取得する関数も残す
function getUnreadNotifications($user_name) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT * FROM notifications WHERE user_name = :user_name AND is_read = 0 ORDER BY created_at DESC';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
        return $notifications;
    } catch (Exception $e) {
        return array();
    }
}

// ユーザーの全通知を取得（最新20件、user_idベース）
function getAllNotificationsByUserId($user_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 20';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
        return $notifications;
    } catch (Exception $e) {
        return array();
    }
}

// 後方互換性のため、user_nameで通知を取得する関数も残す
function getAllNotifications($user_name) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT * FROM notifications WHERE user_name = :user_name ORDER BY created_at DESC LIMIT 20';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
        return $notifications;
    } catch (Exception $e) {
        return array();
    }
}

// 通知を既読にする
function markNotificationAsRead($notification_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = 'UPDATE notifications SET is_read = 1 WHERE id = :id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':id', $notification_id, PDO::PARAM_INT);
        $stmt->execute();

        $dbh = null;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ユーザーの全通知を既読にする（user_idベース）
function markAllNotificationsAsReadByUserId($user_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = 'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $dbh = null;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// 後方互換性のため、user_nameで既読にする関数も残す
function markAllNotificationsAsRead($user_name) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = 'UPDATE notifications SET is_read = 1 WHERE user_name = :user_name';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->execute();

        $dbh = null;
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// 未読通知の数を取得（user_idベース）
function getUnreadNotificationCountByUserId($user_id) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbh = null;
        return $result ? intval($result['count']) : 0;
    } catch (Exception $e) {
        return 0;
    }
}

// 後方互換性のため、user_nameでカウントする関数も残す
function getUnreadNotificationCount($user_name) {
    try {
        $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
        $dbh = new PDO($dsn);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // テーブルが存在しない場合は作成
        initNotificationsTable($dbh);

        $sql = 'SELECT COUNT(*) as count FROM notifications WHERE user_name = :user_name AND is_read = 0';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dbh = null;
        return $result ? intval($result['count']) : 0;
    } catch (Exception $e) {
        return 0;
    }
}

// 部屋名を取得
function getRoomName($room_id) {
    $room_names = array(
        2 => '攻略情報共有部屋',
        3 => '考察・感想部屋',
        4 => '運営Q&A部屋'
    );
    return isset($room_names[$room_id]) ? $room_names[$room_id] : '部屋';
}
?>