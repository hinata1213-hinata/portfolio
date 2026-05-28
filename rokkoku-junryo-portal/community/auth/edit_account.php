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

    // 編集処理
    if (isset($_POST['update'])) {
        $new_username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // バリデーション
        if (empty($new_username)) {
            $error_message = 'ユーザー名を入力してください。';
        } elseif (mb_strlen($new_username) > 20) {
            $error_message = 'ユーザー名は20文字以内で入力してください。';
        } elseif (empty($current_password)) {
            $error_message = '現在のパスワードを入力してください。';
        } else {
            // 現在のパスワードを確認
            $sql = 'SELECT password FROM users WHERE id = :id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($current_password, $user_data['password'])) {
                $error_message = '現在のパスワードが正しくありません。';
            } else {
                // 新しいパスワードの処理
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error_message = '新しいパスワードが一致しません。';
                    } elseif (mb_strlen($new_password) < 4) {
                        $error_message = 'パスワードは4文字以上で設定してください。';
                    } else {
                        // パスワードも変更
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                        // ユーザー名の重複チェック（自分以外）
                        $sql = 'SELECT id FROM users WHERE username = :username AND id != :id';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
                        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
                        $stmt->execute();

                        if ($stmt->fetch()) {
                            $error_message = 'このユーザー名は既に使用されています。';
                        } else {
                            // 古いユーザー名を保存
                            $old_username = $user['username'];

                            $sql = 'UPDATE users SET username = :username, password = :password WHERE id = :id';
                            $stmt = $dbh->prepare($sql);
                            $stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
                            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                            $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
                            $stmt->execute();

                            // room_commentsテーブルの投稿者名も更新
                            $rooms_dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
                            $rooms_dbh = new PDO($rooms_dsn);
                            $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            $update_comments_sql = 'UPDATE room_comments SET name = :new_name WHERE name = :old_name';
                            $update_comments_stmt = $rooms_dbh->prepare($update_comments_sql);
                            $update_comments_stmt->bindParam(':new_name', $new_username, PDO::PARAM_STR);
                            $update_comments_stmt->bindParam(':old_name', $old_username, PDO::PARAM_STR);
                            $update_comments_stmt->execute();

                            $rooms_dbh = null;

                            $_SESSION['username'] = $new_username;
                            $success_message = 'アカウント情報を更新しました。';
                            $user['username'] = $new_username;
                        }
                    }
                } else {
                    // ユーザー名のみ変更
                    $sql = 'SELECT id FROM users WHERE username = :username AND id != :id';
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmt->execute();

                    if ($stmt->fetch()) {
                        $error_message = 'このユーザー名は既に使用されています。';
                    } else {
                        // 古いユーザー名を保存
                        $old_username = $user['username'];

                        $sql = 'UPDATE users SET username = :username WHERE id = :id';
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindParam(':username', $new_username, PDO::PARAM_STR);
                        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
                        $stmt->execute();

                        // room_commentsテーブルの投稿者名も更新
                        $rooms_dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_rooms.db';
                        $rooms_dbh = new PDO($rooms_dsn);
                        $rooms_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        $update_comments_sql = 'UPDATE room_comments SET name = :new_name WHERE name = :old_name';
                        $update_comments_stmt = $rooms_dbh->prepare($update_comments_sql);
                        $update_comments_stmt->bindParam(':new_name', $new_username, PDO::PARAM_STR);
                        $update_comments_stmt->bindParam(':old_name', $old_username, PDO::PARAM_STR);
                        $update_comments_stmt->execute();

                        $rooms_dbh = null;

                        $_SESSION['username'] = $new_username;
                        $success_message = 'ユーザー名を更新しました。';
                        $user['username'] = $new_username;
                    }
                }
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
    <title>アカウント編集 - 六刻巡旅</title>
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

        .edit-container {
            max-width: 500px;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.3);
        }

        .edit-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .edit-header .logo {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
            font-family: 'Creepster', cursive;
        }

        .edit-header h1 {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 10px;
        }

        .edit-header p {
            color: #888888;
            font-size: 0.95rem;
        }

        .edit-form {
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #0066CC;
            font-size: 0.95rem;
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
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.4);
        }

        .form-note {
            color: #888888;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .section-divider {
            border-top: 1px solid rgba(0, 102, 204, 0.2);
            margin: 30px 0;
            padding-top: 20px;
        }

        .section-title {
            color: #0066CC;
            font-size: 1.1rem;
            margin-bottom: 15px;
            font-weight: 600;
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
            margin-top: 10px;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.6);
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
            .edit-container {
                padding: 30px 25px;
            }

            .edit-header .logo {
                font-size: 2rem;
            }

            .edit-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="edit-header">
            <div class="logo">六刻巡旅</div>
            <h1>アカウント編集</h1>
            <p>情報を更新する</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="post" class="edit-form">
            <div class="form-group">
                <label for="username">ユーザー名</label>
                <input type="text" id="username" name="username" required maxlength="20"
                       value="<?php echo htmlspecialchars($user['username']); ?>">
                <div class="form-note">20文字以内で入力してください</div>
            </div>

            <div class="section-divider">
                <div class="section-title">パスワード変更（任意）</div>
                <div class="form-note" style="margin-bottom: 15px;">パスワードを変更しない場合は空欄のままにしてください</div>
            </div>

            <div class="form-group">
                <label for="new_password">新しいパスワード</label>
                <input type="password" id="new_password" name="new_password" minlength="4">
                <div class="form-note">4文字以上で設定してください</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">新しいパスワード（確認）</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>

            <div class="section-divider"></div>

            <div class="form-group">
                <label for="current_password">現在のパスワード（必須）</label>
                <input type="password" id="current_password" name="current_password" required>
                <div class="form-note">変更を確定するには現在のパスワードが必要です</div>
            </div>

            <button type="submit" name="update" class="submit-button">更新する</button>
        </form>

        <div class="back-link">
            <a href="account.php">← アカウント設定に戻る</a>
        </div>
    </div>
</body>
</html>
