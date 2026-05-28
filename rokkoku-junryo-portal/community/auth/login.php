<?php
session_start();

// リダイレクト先を取得（デフォルトは部屋一覧）
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../rooms/index.php';

// 既にログイン済みの場合はリダイレクト
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $redirect);
    exit;
}

$error_message = '';

// ログイン処理
if (isset($_POST['login'])) {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // バリデーション
        if (empty($username) || empty($password)) {
            $error_message = 'ユーザー名とパスワードを入力してください。';
        } else {
            // データベース接続
            $dsn = 'sqlite:' . __DIR__ . '/../data/rokkoku_users.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ユーザーテーブルが存在しない場合は作成
            $sql = 'CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                created_at TEXT NOT NULL
            )';
            $dbh->exec($sql);

            // ユーザー情報を取得
            $sql = 'SELECT id, username, password FROM users WHERE username = :username';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // ログイン成功
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : '../rooms/index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = 'ユーザー名またはパスワードが正しくありません。';
            }

            $dbh = null;
        }
    } catch (Exception $e) {
        $error_message = 'エラーが発生しました: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 六刻巡旅</title>
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

        .auth-container {
            max-width: 450px;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.3);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .auth-header .logo {
            font-size: 2.5rem;
            color: #0066CC;
            text-shadow: 0 0 30px rgba(0, 102, 204, 0.8);
            margin-bottom: 10px;
            font-family: 'Creepster', cursive;
        }

        .auth-header h1 {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 10px;
        }

        .auth-header p {
            color: #888888;
            font-size: 0.95rem;
        }

        .auth-form {
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

        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 102, 204, 0.2);
        }

        .auth-links a {
            color: #0066CC;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .auth-links a:hover {
            color: #0080FF;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.5);
        }

        .back-home {
            display: inline-block;
            margin-top: 20px;
            color: #888888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-home:hover {
            color: #0066CC;
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 30px 25px;
            }

            .auth-header .logo {
                font-size: 2rem;
            }

            .auth-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">六刻巡旅</div>
            <h1>ログイン</h1>
            <p>旅を続ける</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            <div class="form-group">
                <label for="username">ユーザー名</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" name="login" class="submit-button">ログイン</button>
        </form>

        <div class="auth-links">
            <p style="color: #888888;">アカウントをお持ちでないですか？</p>
            <a href="register.php">新規登録はこちら</a>
        </div>

        <div style="text-align: center;">
            <a href="../../game.html" class="back-home">← トップページに戻る</a>
        </div>
    </div>
</body>
</html>
