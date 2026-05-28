<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>削除完了 - 六刻巡旅</title>
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

        .complete-container {
            max-width: 500px;
            width: 100%;
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.4);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 102, 204, 0.3);
            text-align: center;
        }

        .complete-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .complete-title {
            font-size: 1.8rem;
            color: #0066CC;
            margin-bottom: 15px;
        }

        .complete-message {
            color: #e0e0e0;
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .home-button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #0066CC, #0080FF);
            border: none;
            border-radius: 25px;
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.6);
        }

        @media (max-width: 768px) {
            .complete-container {
                padding: 30px 25px;
            }

            .complete-icon {
                font-size: 3rem;
            }

            .complete-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="complete-container">
        <div class="complete-icon">✓</div>
        <h1 class="complete-title">アカウント削除完了</h1>
        <p class="complete-message">
            アカウントが正常に削除されました。<br>
            ご利用ありがとうございました。<br><br>
            またいつでもお待ちしております。
        </p>
        <a href="../../game.html" class="home-button">トップページへ戻る</a>
    </div>
</body>
</html>
