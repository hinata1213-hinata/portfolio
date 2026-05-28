<?php
session_start();

// 通知機能を読み込み
require_once __DIR__ . '/notifications.php';

// ログインユーザーの未読通知を取得（user_idベース）
$unread_notifications = array();
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $unread_notifications = getUnreadNotificationsByUserId($_SESSION['user_id']);
    $unread_count = count($unread_notifications);
}

// 通知を既読にする処理
if (isset($_POST['mark_notification_read'])) {
    $notification_id = intval($_POST['notification_id']);
    markNotificationAsRead($notification_id);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// 全ての通知を既読にする処理（user_idベース）
if (isset($_POST['mark_all_read'])) {
    if (isset($_SESSION['user_id'])) {
        markAllNotificationsAsReadByUserId($_SESSION['user_id']);
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>話題の部屋 - 六刻巡旅コミュニティ</title>
    <link rel="stylesheet" href="../../game.css">
    <style>
        body {
            background: linear-gradient(180deg, #000000, #0a0a0a);
            min-height: 100vh;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
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

        .auth-nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .auth-nav a, .auth-nav span {
            padding: 8px 18px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 20px;
            color: #0066CC;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .auth-nav a:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .auth-nav .likes-link {
            background: rgba(255, 68, 68, 0.15);
            border-color: rgba(255, 68, 68, 0.4);
            color: #ff6666;
        }

        .auth-nav .likes-link:hover {
            background: rgba(255, 68, 68, 0.25);
            box-shadow: 0 0 15px rgba(255, 68, 68, 0.4);
        }

        .auth-nav .search-link {
            background: rgba(255, 200, 0, 0.15);
            border-color: rgba(255, 200, 0, 0.4);
            color: #ffcc00;
        }

        .auth-nav .search-link:hover {
            background: rgba(255, 200, 0, 0.25);
            box-shadow: 0 0 15px rgba(255, 200, 0, 0.4);
        }

        .user-info {
            color: #0080FF;
            font-weight: 600;
        }

        .rooms-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 30px 30px;
            flex: 1;
        }

        .page-title {
            text-align: center;
            margin-bottom: 15px;
        }

        .page-title h1 {
            font-size: 2.2rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
            animation: titleGlow 3s ease-in-out infinite;
        }

        @keyframes titleGlow {
            0%, 100% {
                text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            }
            50% {
                text-shadow: 0 0 50px rgba(0, 102, 204, 1), 0 0 70px rgba(0, 102, 204, 0.6);
            }
        }

        .page-subtitle {
            font-size: 1rem;
            color: #e0e0e0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
            margin-bottom: 35px;
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 0;
            max-width: 100%;
        }

        .room-card {
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 28px 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 260px;
        }

        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 102, 204, 0.15), transparent);
            transition: left 0.5s;
        }

        .room-card:hover::before {
            left: 100%;
        }

        .room-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(0, 102, 204, 0.8);
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.4);
            background: rgba(26, 26, 26, 0.95);
        }

        .room-icon {
            font-size: 3.2rem;
            text-align: center;
            margin-bottom: 12px;
            filter: drop-shadow(0 0 10px rgba(0, 102, 204, 0.5));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .room-title {
            font-size: 1.15rem;
            color: #0066CC;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            text-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
            line-height: 1.4;
        }

        .room-description {
            color: #e0e0e0;
            line-height: 1.6;
            text-align: center;
            font-size: 0.85rem;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        .room-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.4);
        }

        /* ネタバレ吹き出し */
        .spoiler-bubble {
            position: absolute;
            bottom: 12px;
            left: 12px;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
        }

        .spoiler-bubble::after {
            content: '';
            position: absolute;
            top: -6px;
            left: 15px;
            border-width: 0 6px 6px 6px;
            border-style: solid;
        }

        .spoiler-bubble.no-spoiler {
            background: linear-gradient(135deg, #00CC66, #00AA55);
            color: white;
        }

        .spoiler-bubble.no-spoiler::after {
            border-color: transparent transparent #00CC66 transparent;
        }

        .spoiler-bubble.has-spoiler {
            background: linear-gradient(135deg, #FF6600, #FF4400);
            color: white;
        }

        .spoiler-bubble.has-spoiler::after {
            border-color: transparent transparent #FF6600 transparent;
        }

        .footer {
            background: #0a0a0a;
            border-top: 1px solid rgba(0, 102, 204, 0.3);
            padding: 30px 30px;
            text-align: center;
            margin-top: 50px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            font-size: 0.9rem;
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
            font-size: 0.8rem;
        }

        .copyright-large {
            color: #888888;
            font-size: 1.2rem;
            font-weight: 500;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.3);
        }

        .home-button-container {
            text-align: center;
            margin-top: 35px;
        }

        .home-button {
            display: inline-block;
            padding: 12px 30px;
            background: rgba(0, 102, 204, 0.2);
            border: 2px solid rgba(0, 102, 204, 0.5);
            border-radius: 20px;
            color: #0066CC;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .home-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 20px rgba(0, 102, 204, 0.5);
            transform: translateY(-3px);
        }

        @media (max-width: 1200px) {
            .rooms-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 25px;
            }

            .room-card {
                min-height: 300px;
            }
        }

        @media (max-width: 768px) {
            .rooms-container {
                padding: 40px 20px;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .page-subtitle {
                font-size: 1rem;
            }

            .rooms-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .room-card {
                padding: 35px 25px;
                min-height: 280px;
            }

            .room-icon {
                font-size: 3.5rem;
            }

            .room-title {
                font-size: 1.3rem;
            }

            .room-description {
                font-size: 0.95rem;
            }

            .auth-nav {
                flex-direction: column;
                gap: 8px;
            }

            .auth-nav a, .auth-nav span {
                padding: 6px 14px;
                font-size: 0.85rem;
            }

            .footer {
                padding: 40px 20px;
            }
        }

        /* 通知関連スタイル */
        .notification-wrapper {
            position: relative;
            display: inline-block;
        }

        .notification-button {
            padding: 8px 18px;
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.5);
            border-radius: 20px;
            color: #0066CC;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .notification-button:hover {
            background: rgba(0, 102, 204, 0.3);
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .notification-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .notification-panel {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            background: rgba(20, 20, 20, 0.98);
            border: 2px solid rgba(0, 102, 204, 0.5);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            z-index: 1001;
        }

        .notification-panel.show {
            display: block;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.3);
        }

        .notification-header h3 {
            color: #0066CC;
            font-size: 1rem;
            margin: 0;
        }

        .mark-all-read-btn {
            background: rgba(0, 102, 204, 0.2);
            border: 1px solid rgba(0, 102, 204, 0.4);
            color: #0066CC;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .mark-all-read-btn:hover {
            background: rgba(0, 102, 204, 0.3);
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 102, 204, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .notification-item:hover {
            background: rgba(0, 102, 204, 0.1);
        }

        .notification-item.unread {
            background: rgba(0, 102, 204, 0.05);
            border-left: 3px solid #0066CC;
        }

        .notification-message {
            color: #e0e0e0;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #888;
        }

        .notification-room {
            color: #0066CC;
        }

        .notification-empty {
            padding: 30px 20px;
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>
    <!-- ヘッダー -->
    <header class="site-header">
        <nav class="navbar">
            <a href="../../game.html" class="logo">六刻巡旅</a>
            <div class="auth-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="notification-wrapper">
                        <button class="notification-button" onclick="toggleNotificationPanel(event)">
                            🔔 通知
                            <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notification-panel" id="notificationPanel">
                            <div class="notification-header">
                                <h3>🔔 通知</h3>
                                <?php if ($unread_count > 0): ?>
                                <form method="post" style="margin: 0;">
                                    <button type="submit" name="mark_all_read" class="mark-all-read-btn">すべて既読</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php if (empty($unread_notifications)): ?>
                            <div class="notification-empty">
                                新しい通知はありません
                            </div>
                            <?php else: ?>
                            <?php foreach ($unread_notifications as $notification): ?>
                            <div class="notification-item unread" onclick="goToNotification(<?php echo $notification['post_id']; ?>, <?php echo $notification['room_id']; ?>, <?php echo $notification['id']; ?>)">
                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-meta">
                                    <span class="notification-room"><?php echo htmlspecialchars(getRoomName($notification['room_id'])); ?></span>
                                    <span><?php echo date('n/j H:i', strtotime($notification['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="search.php" class="search-link">🔍 検索</a>
                    <a href="my_likes.php" class="likes-link">❤️ いいね</a>
                    <a href="../auth/account.php">アカウント設定</a>
                    <a href="../auth/logout.php">ログアウト</a>
                <?php else: ?>
                    <a href="search.php" class="search-link">🔍 検索</a>
                    <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">ログイン</a>
                    <a href="../auth/register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">新規登録</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <script>
        function toggleNotificationPanel(event) {
            event.stopPropagation();
            const panel = document.getElementById('notificationPanel');
            panel.classList.toggle('show');
        }

        function goToNotification(postId, roomId, notificationId) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = '<input type="hidden" name="mark_notification_read" value="1"><input type="hidden" name="notification_id" value="' + notificationId + '">';
            document.body.appendChild(form);
            form.submit();
            setTimeout(function() {
                window.location.href = 'post_detail.php?id=' + postId + '&room_id=' + roomId;
            }, 100);
        }

        document.addEventListener('click', function(event) {
            const panel = document.getElementById('notificationPanel');
            const wrapper = document.querySelector('.notification-wrapper');
            if (panel && wrapper && !wrapper.contains(event.target)) {
                panel.classList.remove('show');
            }
        });
    </script>

    <div class="rooms-container">
        <div class="page-title">
            <h1>話題の部屋</h1>
            <p class="page-subtitle">ファンが集まる特別な場所へようこそ</p>
        </div>

        <div class="rooms-grid">
            <!-- ゲーム攻略情報部屋 -->
            <a href="room_guide.php" class="room-card">
                <div class="room-badge">✨ おすすめ</div>
                <div class="spoiler-bubble no-spoiler">🛡️ ネタバレ✖</div>
                <div class="room-icon">🎮</div>
                <h2 class="room-title">ゲーム攻略情報部屋</h2>
                <p class="room-description">
                    エンディング到達のヒントや隠し要素の情報交換！<br>
                    困ったときはここで相談しよう。
                </p>
            </a>

            <!-- 考察・感想部屋 -->
            <a href="room_discussion.php" class="room-card">
                <div class="room-badge">🔮 ディープ</div>
                <div class="spoiler-bubble has-spoiler">⚠️ ネタバレ〇</div>
                <div class="room-icon">🕰️</div>
                <h2 class="room-title">考察・感想部屋</h2>
                <p class="room-description">
                    ストーリーの深い考察や感想をシェア！<br>
                    ネタバレOKの部屋で、心ゆくまで語り合おう。
                </p>
            </a>

            <!-- 運営Q&A部屋 -->
            <a href="room_qa.php" class="room-card">
                <div class="room-badge">⭐ 公式</div>
                <div class="room-icon">💬</div>
                <h2 class="room-title">運営Q&A部屋</h2>
                <p class="room-description">
                    運営チームに直接質問できる特別な部屋！<br>
                    ゲームや今後の展開について気になることを聞いてみよう。
                </p>
            </a>
        </div>

        <div class="home-button-container">
            <a href="../../game.html" class="home-button">ホームへ戻る</a>
        </div>
    </div>

    <!-- フッター -->
    <footer class="footer">
        <div class="footer-content">
            <div class="copyright-large">
                &copy; 2025 六刻巡旅 - All rights reserved
            </div>
        </div>
    </footer>
</body>
</html>
