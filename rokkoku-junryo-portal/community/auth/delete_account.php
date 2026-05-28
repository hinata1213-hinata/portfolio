<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error_message = '';

try {
    // データベース接続
    $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_users.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ユーザー情報を取得
    $sql = 'SELECT id, username FROM users WHERE id = :id';
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // 削除処理
    if (isset($_POST['delete_confirmed'])) {
        $password = $_POST['password'];
        $confirmation = trim($_POST['confirmation']);

        // バリデーション
        if (empty($password)) {
            $error_message = 'パスワードを入力してください。';
        } elseif ($confirmation !== $user['username']) {
            $error_message = 'ユーザー名が正しくありません。';
        } else {
            // パスワード確認
            $sql = 'SELECT password FROM users WHERE id = :id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($password, $user_data['password'])) {
                $error_message = 'パスワードが正しくありません。';
            } else {
                // コメントデータベースからもユーザーの投稿を削除
                try {
                    $rooms_dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
                    $rooms_dbh = new PDO($rooms_dsn);
                    $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // ユーザーの投稿を削除
                    $sql = 'DELETE FROM room_comments WHERE user_id = :user_id';
                    $stmt = $rooms_dbh->prepare($sql);
                    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmt->execute();
                } catch (Exception $e) {
                    // コメントDBが存在しない場合は無視
                }

                // ユーザーアカウントを削除
                $sql = 'DELETE FROM users WHERE id = :id';
                $stmt = $dbh->prepare($sql);
                $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                // セッション破棄
                session_destroy();

                // 削除完了ページへリダイレクト
                header('Location: delete_complete.php');
                exit;
            }
        }
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
    <title>アカウント削除 - 六刻巡旅</title>
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

        .delete-container {
            max-width: 500px;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(139, 0, 0, 0.6);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(139, 0, 0, 0.4);
        }

        .delete-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .delete-header .logo {
            font-size: 2.5rem;
            color: #CC0000;
            text-shadow: 0 0 30px rgba(204, 0, 0, 0.8);
            margin-bottom: 10px;
            font-family: 'Creepster', cursive;
        }

        .delete-header h1 {
            font-size: 1.8rem;
            color: #CC0000;
            margin-bottom: 10px;
        }

        .delete-header p {
            color: #888888;
            font-size: 0.95rem;
        }

        .warning-box {
            background: rgba(139, 0, 0, 0.2);
            border: 2px solid rgba(139, 0, 0, 0.5);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .warning-title {
            color: #ff6666;
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .warning-list {
            color: #e0e0e0;
            font-size: 0.95rem;
            line-height: 1.8;
            padding-left: 20px;
        }

        .warning-list li {
            margin-bottom: 8px;
        }

        .delete-form {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #CC0000;
            font-size: 0.95rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(139, 0, 0, 0.4);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #CC0000;
            box-shadow: 0 0 15px rgba(204, 0, 0, 0.4);
        }

        .form-note {
            color: #888888;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .delete-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #8B0000, #CC0000);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .delete-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 0, 0, 0.6);
        }

        .error-message {
            background: rgba(139, 0, 0, 0.3);
            border: 1px solid rgba(139, 0, 0, 0.6);
            color: #ff6666;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
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

        @media (max-width: 768px) {
            .delete-container {
                padding: 30px 25px;
            }

            .delete-header .logo {
                font-size: 2rem;
            }

            .delete-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="delete-container">
        <div class="delete-header">
            <div class="logo">⚠️</div>
            <h1>アカウント削除</h1>
            <p>本当に削除しますか？</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="warning-box">
            <div class="warning-title">
                ⚠️ 重要な警告
            </div>
            <ul class="warning-list">
                <li>この操作は取り消すことができません</li>
                <li>投稿したすべてのコメントが削除されます</li>
                <li>アカウント情報は完全に消去されます</li>
                <li>同じユーザー名で再登録が必要になります</li>
            </ul>
        </div>

        <form method="post" class="delete-form" onsubmit="return confirm('本当にアカウントを削除してもよろしいですか？');">
            <div class="form-group">
                <label for="confirmation">ユーザー名を入力して確認</label>
                <input type="text" id="confirmation" name="confirmation" required
                       placeholder="<?php echo htmlspecialchars($user['username']); ?>">
                <div class="form-note">削除するには「<?php echo htmlspecialchars($user['username']); ?>」と入力してください</div>
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
                <div class="form-note">本人確認のためパスワードを入力してください</div>
            </div>

            <button type="submit" name="delete_confirmed" class="delete-button">
                🗑️ アカウントを完全に削除する
            </button>
        </form>

        <div class="back-link">
            <a href="account.php">← キャンセル</a>
        </div>
    </div>
</body>
</html>
