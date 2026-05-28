<?php
session_start();

try {
    // セッションからコードを取得
    if (!isset($_SESSION['rokkokucode'])) {
        header('Location: index_ng.php');
        exit();
    }
    $rokkoku_code = $_SESSION['rokkokucode'];

    // データベースに接続
    $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 投稿情報を取得
    $sql = 'SELECT name, title, content, image FROM mst_rokkoku WHERE code=?';
    $stmt = $dbh->prepare($sql);
    $data[] = $rokkoku_code;
    $stmt->execute($data);

    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rec) {
        print '<p style="color: red;">指定された投稿が見つかりませんでした。</p>';
        print '<a href="index.blade.php">戻る</a>';
        exit();
    }
    
    $rokkoku_name = $rec['name'] ?? '匿名';
    $rokkoku_title = $rec['title'] ?? '(タイトルなし)';
    $rokkoku_content = $rec['content'] ?? '(内容なし)';
    $rokkoku_image = $rec['image'] ?? '';

    $dbh = null;

} catch (Exception $e) {
    print '<p style="color: red;">ただいま障害により大変ご迷惑をおかけしております。</p>';
    print '<p>エラー詳細: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿内容削除 - コミュニティ</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="community.css">
    <style>
        body {
            padding-top: 100px;
            padding-bottom: 40px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .page-header {
            text-align: center;
            padding: 40px 20px;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: #ff4444;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(255, 68, 68, 0.5);
        }
        
        .delete-info {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 68, 68, 0.3);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .delete-info:hover {
            border-color: #ff4444;
            box-shadow: 0 10px 30px rgba(255, 68, 68, 0.3);
        }
        
        .delete-item {
            margin: 20px 0;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .delete-item:last-child {
            border-bottom: none;
        }
        
        .delete-label {
            color: #ff4444;
            font-weight: bold;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 8px;
        }
        
        .delete-value {
            color: #cccccc;
            line-height: 1.8;
            margin-left: 10px;
        }
        
        .delete-image {
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            max-width: 300px;
        }
        
        .delete-image img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
        }
        
        .warning {
            background: rgba(255, 68, 68, 0.1);
            border: 2px solid rgba(255, 68, 68, 0.3);
            color: #ff4444;
            text-align: center;
            padding: 20px;
            margin: 30px 0;
            font-weight: bold;
            font-size: 1.2rem;
            border-radius: 10px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        input[type="button"],
        input[type="submit"] {
            padding: 15px 40px;
            border: none;
            border-radius: 999px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        input[type="button"] {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        input[type="button"]:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
        }
        
        input[type="submit"] {
            background: linear-gradient(45deg, #ff4444, #ff6b6b);
            color: white;
        }
        
        input[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 68, 68, 0.5);
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .delete-info {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
                gap: 15px;
            }
            
            input[type="button"],
            input[type="submit"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1>⚠️ 投稿削除確認</h1>
        </header>
        
        <p class="warning">⚠️ この操作は取り消せません。本当に削除しますか？</p>
        
        <div class="delete-info">
            <div class="delete-item">
                <span class="delete-label">📌 タイトル</span>
                <div class="delete-value"><?php echo htmlspecialchars($rokkoku_title); ?></div>
            </div>
            
            <div class="delete-item">
                <span class="delete-label">👤 投稿者</span>
                <div class="delete-value"><?php echo htmlspecialchars($rokkoku_name); ?></div>
            </div>
            
            <div class="delete-item">
                <span class="delete-label">📝 内容</span>
                <div class="delete-value"><?php echo nl2br(htmlspecialchars($rokkoku_content)); ?></div>
            </div>
            
            <?php if($rokkoku_image != ''): ?>
                <div class="delete-item">
                    <span class="delete-label">🖼️ 画像</span>
                    <div class="delete-image">
                        <img src="./images/<?php echo htmlspecialchars($rokkoku_image); ?>" alt="投稿画像">
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="post" action="index_delete_done.php">
            <input type="hidden" name="code" value="<?php echo htmlspecialchars($rokkoku_code); ?>">
            <input type="hidden" name="image" value="<?php echo htmlspecialchars($rokkoku_image); ?>">
            <div class="button-group">
                <input type="button" onclick="history.back()" value="キャンセル">
                <input type="submit" value="🗑️ 削除する">
            </div>
        </form>
    </div>
    
    <footer class="footer">
		<div class="footer-content">
			<div class="footer-links">
				<a href="legal/privacy.html">プライバシーポリシー</a>
				<a href="legal/terms-of-service.html">利用規約</a>
				<a href="community/confirm/confirm.html">お問い合わせ</a>
			</div>
			<div class="copyright">
				&copy; 2025 六刻巡旅 - All rights reserved
			</div>
		</div>
	</footer>
</body>
</html>