<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>削除完了 - コミュニティ</title>
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
        }
        
        .success-message {
            background: rgba(0, 204, 102, 0.1);
            border: 2px solid rgba(0, 204, 102, 0.3);
            border-radius: 15px;
            padding: 60px 40px;
            margin: 40px 0;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .success-message:hover {
            border-color: #00CC66;
            box-shadow: 0 10px 30px rgba(0, 204, 102, 0.3);
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .success-title {
            color: #00CC66;
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(0, 204, 102, 0.5);
            animation: fadeInUp 0.5s ease 0.3s backwards;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success-description {
            color: #cccccc;
            font-size: 1.2rem;
            margin-bottom: 10px;
            animation: fadeInUp 0.5s ease 0.5s backwards;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 40px;
            padding: 18px 50px;
            background: linear-gradient(45deg, #0066CC, #0080FF);
            color: white;
            text-decoration: none;
            border-radius: 999px;
            font-weight: bold;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
            animation: fadeInUp 0.5s ease 0.7s backwards;
        }
        
        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 102, 204, 0.5);
        }
        
        .error-message {
            background: rgba(255, 68, 68, 0.1);
            border: 2px solid rgba(255, 68, 68, 0.3);
            border-radius: 15px;
            padding: 40px;
            margin: 40px 0;
            text-align: center;
        }
        
        .error-message h2 {
            color: #ff4444;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .error-message p {
            color: #cccccc;
            font-size: 1.1rem;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .success-message {
                padding: 40px 20px;
            }
            
            .success-icon {
                font-size: 4rem;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .success-description {
                font-size: 1.1rem;
            }
            
            .back-link {
                padding: 15px 35px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
        </header>

        <?php
        try {
            $rokkoku_code = $_POST['code'];
            $rokkoku_image = isset($_POST['image']) ? $_POST['image'] : '';

            // データベースに接続
            $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
            $dbh = new PDO($dsn);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 投稿削除のSQL文を実行
            $sql = 'DELETE FROM mst_rokkoku WHERE code=?';
            $stmt = $dbh->prepare($sql);
            $data[] = $rokkoku_code;
            $stmt->execute($data);

            $dbh = null;

            // 画像ファイルも削除
            if($rokkoku_image != '') {
                $image_path = './images/' . $rokkoku_image;
                if(file_exists($image_path)) {
                    unlink($image_path);
                }
            }

            // セッションのrokkokucodeをクリア
            session_start();
            unset($_SESSION['rokkokucode']);

            ?>
            <div class="success-message">
                <div class="success-icon">✅</div>
                <h1 class="success-title">削除完了</h1>
                <p class="success-description">投稿が正常に削除されました。</p>
                <a href="index.blade.php" class="back-link">📋 コミュニティに戻る</a>
            </div>
            <?php

        } catch (Exception $e) {
            ?>
            <div class="error-message">
                <h2>❌ エラーが発生しました</h2>
                <p>ただいま障害により大変ご迷惑をおかけしております。</p>
                <p style="color: #ff4444; margin-top: 20px;">エラー詳細: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                <a href="index.blade.php" class="back-link" style="margin-top: 30px;">コミュニティに戻る</a>
            </div>
            <?php
            exit();
        }
        ?>
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