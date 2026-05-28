<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

try {
    // データベース接続
    $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_users.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ユーザー情報を取得
    $sql = 'SELECT id, username, created_at FROM users WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

} catch (Exception $e) {
    $error_message = 'エラーが発生しました: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アカウント設定 - 六刻巡旅</title>
    <link rel="stylesheet" href="../../game.css">
    <style>
        body {
            background: linear-gradient(180deg, #000000, #0a0a0a);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .account-container {
            max-width: 600px;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.3);
        }

        .account-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .account-header .logo {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
            font-family: 'Creepster', cursive;
        }

        .account-header h1 {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 10px;
        }

        .account-header p {
            color: #888888;
            font-size: 0.95rem;
        }

        .user-info {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 102, 204, 0.2);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #0066CC;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .info-value {
            color: #e0e0e0;
            font-size: 0.95rem;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        .action-button {
            padding: 14px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: block;
        }

        .edit-button {
            background: linear-gradient(135deg, #0066CC, #0080FF);
            color: white;
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.6);
        }

        .delete-button {
            background: linear-gradient(135deg, #8B0000, #CC0000);
            color: white;
        }

        .delete-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 0, 0, 0.6);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #888888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #0066CC;
        }

        .error-message {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid rgba(139, 0, 0, 0.5);
            color: #ff6666;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .success-message {
            background: rgba(0, 139, 0, 0.2);
            border: 1px solid rgba(0, 139, 0, 0.5);
            color: #66ff66;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .account-container {
                padding: 30px 25px;
            }

            .account-header .logo {
                font-size: 2rem;
            }

            .account-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="account-container">
        <div class="account-header">
            <div class="logo">六刻巡旅</div>
            <h1>アカウント設定</h1>
            <p>あなたの旅の記録</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="user-info">
            <div class="info-row">
                <span class="info-label">ユーザー名</span>
                <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">ユーザーID</span>
                <span class="info-value">#<?php echo htmlspecialchars($user['id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">登録日時</span>
                <span class="info-value"><?php echo htmlspecialchars($user['created_at']); ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="edit_account.php" class="action-button edit-button">
                ✏️ アカウント情報を編集
            </a>
            <button onclick="confirmDelete()" class="action-button delete-button">
                🗑️ アカウントを削除
            </button>
        </div>

        <div class="back-link">
            <a href="../rooms/index.php">← 話題の部屋に戻る</a>
        </div>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('本当にアカウントを削除しますか？\n\nこの操作は取り消せません。\n投稿したすべてのコメントも削除されます。')) {
                window.location.href = 'delete_account.php';
            }
        }
    </script>
</body>
</html>
