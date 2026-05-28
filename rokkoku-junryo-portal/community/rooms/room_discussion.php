<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>考察・感想部屋 - 六刻巡旅コミュニティ</title>
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

        .room-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .room-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .room-header h1 {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
        }

        .room-header .room-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .room-topic {
            background: rgba(0, 102, 204, 0.1);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
        }

        .topic-label {
            display: inline-block;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .topic-content {
            color: #e0e0e0;
            font-size: 1.2rem;
            line-height: 1.8;
        }

        .comments-section {
            margin-top: 40px;
        }

        .comments-title {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 25px;
            text-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        #comments-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            #comments-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            #comments-container {
                grid-template-columns: 1fr;
            }
        }

        .comment-item {
            background: rgba(26, 26, 26, 0.8);
            border: 1px solid rgba(0, 102, 204, 0.2);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }

        .comment-item:hover {
            border-color: rgba(0, 102, 204, 0.5);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.2);
        }

        .comment-item.comment-has-replies {
            border-left: 3px solid #0066CC;
            background: rgba(26, 26, 46, 0.85);
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 10px;
        }

        .comment-author {
            color: #0066CC;
            font-weight: bold;
            font-size: 1rem;
        }

        .own-label {
            color: #00CC66;
            font-size: 0.85rem;
            font-weight: normal;
            margin-left: 5px;
        }

        .comment-time {
            color: #888888;
            font-size: 0.85rem;
        }

        .comment-text {
            color: #cccccc;
            line-height: 1.7;
        }

        .comment-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(0, 102, 204, 0.1);
        }

        .like-info {
            color: #888888;
            font-size: 0.9rem;
        }

        .like-button {
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 20px;
            padding: 5px 15px;
            color: #888888;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .like-button:hover {
            background: rgba(0, 102, 204, 0.2);
            border-color: rgba(0, 102, 204, 0.5);
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

        .like-button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .liked-text {
            font-size: 0.8rem;
            margin-left: 3px;
        }

        .reply-count {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: rgba(0, 102, 204, 0.15);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            color: #0066CC;
            font-size: 0.85rem;
        }

        .reply-count.has-replies {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.3), rgba(0, 128, 255, 0.2));
            border-color: rgba(0, 102, 204, 0.6);
            font-weight: bold;
            animation: pulse-reply 2s infinite;
        }

        @keyframes pulse-reply {
            0%, 100% { box-shadow: 0 0 5px rgba(0, 102, 204, 0.3); }
            50% { box-shadow: 0 0 15px rgba(0, 102, 204, 0.5); }
        }

        .comment-form {
            background: rgba(26, 26, 26, 0.8);
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }

        .comment-form h3 {
            color: #0066CC;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .comment-form textarea {
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

        .comment-form textarea:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .comment-form button {
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

        .comment-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.5);
        }

        .no-comments {
            grid-column: 1 / -1;
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

        .parent-comment {
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .parent-comment:hover {
            background: rgba(0, 102, 204, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.2);
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
    <script>
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        let userLikedComments = [];

        function goToPostDetail(postId) {
            window.location.href = 'post_detail.php?id=' + postId + '&room_id=3';
        }

        function getTimeDisplay(createdAt) {
            const now = new Date();
            const created = new Date(createdAt);
            const diffMs = now - created;
            const diffMinutes = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMinutes < 60) {
                return diffMinutes + '分前';
            } else if (diffHours < 24) {
                return diffHours + '時間前';
            } else {
                return diffDays + '日前';
            }
        }

        function toggleLike(event, commentId) {
            event.stopPropagation();

            if (!isLoggedIn) {
                alert('いいねするにはログインが必要です');
                return;
            }

            const button = event.currentTarget;
            button.disabled = true;

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
                    if (data.liked) {
                        button.classList.add('liked');
                        button.innerHTML = '❤️ <span class="like-count">' + data.like_count + '</span> <span class="liked-text">いいね済み</span>';
                        if (!userLikedComments.some(id => Number(id) === Number(commentId))) {
                            userLikedComments.push(commentId);
                        }
                    } else {
                        button.classList.remove('liked');
                        button.innerHTML = '🤍 <span class="like-count">' + data.like_count + '</span>';
                        userLikedComments = userLikedComments.filter(id => Number(id) !== Number(commentId));
                    }
                } else {
                    alert(data.error || 'エラーが発生しました');
                }
            })
            .catch(error => {
                console.error('いいねエラー:', error);
            })
            .finally(() => {
                button.disabled = false;
            });
        }

        function updateComments() {
            fetch('get_comments.php?room_id=3')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderComments(data.comments, data.user_liked_comments || []);
                    }
                })
                .catch(error => {
                    console.error('コメント更新エラー:', error);
                });
        }

        function renderComments(comments, likedComments) {
            userLikedComments = likedComments;
            const commentsContainer = document.getElementById('comments-container');
            const parentComments = comments.filter(c => c.parent_id === null);

            if (parentComments.length === 0) {
                commentsContainer.innerHTML = '<div class="no-comments">まだコメントがありません。最初のコメントを投稿してみませんか？</div>';
                return;
            }

            let html = '';
            parentComments.forEach(comment => {
                const timeDisplay = getTimeDisplay(comment.created_at);
                const likeCount = comment.like_count || 0;
                const replyCount = comment.reply_count || 0;
                const isLiked = userLikedComments.some(id => Number(id) === Number(comment.id));
                const likedClass = isLiked ? ' liked' : '';
                const hasRepliesClass = replyCount > 0 ? ' has-replies' : '';

                const commentHasReplies = replyCount > 0 ? ' comment-has-replies' : '';
                html += '<div class="comment-item parent-comment' + commentHasReplies + '" onclick="goToPostDetail(' + comment.id + ')">';
                html += '<div class="comment-header">';
                const ownLabel = comment.is_own ? '<span class="own-label">（自分）</span>' : '';
                html += '<span class="comment-author">' + comment.name + ownLabel + '</span>';
                html += '<span class="comment-time">' + timeDisplay + '</span>';
                html += '</div>';
                html += '<div class="comment-text">' + comment.content.replace(/\n/g, '<br>') + '</div>';
                html += '<div class="comment-stats">';
                html += '<button class="like-button' + likedClass + '" onclick="toggleLike(event, ' + comment.id + ')">';
                if (isLiked) {
                    html += '❤️ <span class="like-count">' + likeCount + '</span> <span class="liked-text">いいね済み</span>';
                } else {
                    html += '🤍 <span class="like-count">' + likeCount + '</span>';
                }
                html += '</button>';
                html += '<span class="reply-count' + hasRepliesClass + '">💬 ' + replyCount + '件の返信</span>';
                html += '</div>';
                html += '</div>';
            });

            commentsContainer.innerHTML = html;
        }

        setInterval(updateComments, 5000);

        document.addEventListener('DOMContentLoaded', function() {
            updateComments();
        });
    </script>
</head>
<body>
    <header class="site-header">
        <nav class="navbar">
            <a href="../../game.html" class="logo">六刻巡旅</a>
            <a href="index.php" class="back-button">← 部屋一覧に戻る</a>
        </nav>
    </header>

    <div class="room-container">
        <div class="room-header">
            <div class="room-icon">🕰️</div>
            <h1>考察・感想部屋</h1>
        </div>

        <div class="room-topic">
            <span class="topic-label">📌 運営からのメッセージ</span>
            <div class="topic-content">
                ストーリーの深い考察や感想をシェアしましょう！<br>
                ネタバレOKの部屋なので、心ゆくまで語り合ってください。<br>
                みなさんの考察を聞かせてください！
            </div>
        </div>

        <div class="comments-section">
            <?php
            $ng_error_message = '';
            if (isset($_POST['post_comment'])) {
                // NGワードフィルターを読み込み
                require_once __DIR__ . '/ng_word_filter.php';

                $content = $_POST['content'];
                $ng_error = false;

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

                        $name = isset($_SESSION['username']) ? $_SESSION['username'] : '匿名';
                        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

                        $sql = 'INSERT INTO room_comments (room_id, name, user_id, content, created_at, parent_id) VALUES (3, :name, :user_id, :content, datetime("now", "localtime"), NULL)';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                        $stmt->execute();

                        $dbh = null;
                    } catch (Exception $e) {
                        // エラー処理
                    }
                }
            }
            ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="comment-form" style="margin-top: 0; margin-bottom: 30px;">
                    <h3>コメントを投稿する</h3>
                    <?php if ($ng_error_message): ?>
                        <div style="background: rgba(139, 0, 0, 0.3); border: 1px solid #ff4444; border-radius: 10px; padding: 15px; margin-bottom: 20px; color: #ff6666; text-align: center;"><?php echo htmlspecialchars($ng_error_message); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <textarea name="content" placeholder="考察や感想を書いてください..." required></textarea>
                        <button type="submit" name="post_comment">投稿する</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-required" style="margin-top: 0; margin-bottom: 30px;">
                    <p>💬 コメントを投稿するにはログインが必要です</p>
                    <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">ログイン</a>
                    または
                    <a href="../auth/register.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">新規登録</a>
                </div>
            <?php endif; ?>

            <h2 class="comments-title">💬 みんなの会話</h2>
            <div id="comments-container"></div>
        </div>
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
