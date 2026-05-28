<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>コミュニティ - 六刻巡旅</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            padding-top: 80px;
            padding-bottom: 40px;
        }

        /* ヘッダー */
        .site-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .site-header.scrolled {
            background: rgba(0, 0, 0, 0.95);
            box-shadow: 0 2px 20px rgba(0, 102, 204, 0.3);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
            gap: 20px;
        }

        .logo {
            font-family: 'Creepster', cursive;
            font-size: 24px;
            color: #0066CC;
            text-decoration: none;
            text-shadow: 0 0 10px #0066CC;
            animation: glow 2s ease-in-out infinite alternate;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .logo:hover {
            text-shadow: 0 0 20px #0066CC, 0 0 30px #0066CC;
            transform: scale(1.05);
        }

        @keyframes glow {
            from { text-shadow: 0 0 10px #0066CC; }
            to { text-shadow: 0 0 20px #0066CC, 0 0 30px #0066CC; }
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 15px;
            margin: 0;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-menu a,
        .nav-menu .search-button {
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border: 1px solid rgba(0, 102, 204, 0.3);
            border-radius: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: transparent;
            cursor: pointer;
            font-size: 0.95rem;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-menu a::before,
        .nav-menu .search-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 102, 204, 0.3), transparent);
            transition: left 0.5s;
        }

        .nav-menu a:hover::before,
        .nav-menu .search-button:hover::before {
            left: 100%;
        }

        .nav-menu a:hover,
        .nav-menu .search-button:hover {
            color: #0066CC;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.5);
        }

        .btn-new-header {
            background: linear-gradient(45deg, #0066CC, #0080FF) !important;
            color: white !important;
            font-weight: bold;
            border: none !important;
        }

        .btn-new-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.5) !important;
        }

        /* 検索モーダル */
        .search-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 5000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .search-modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 1;
        }

        .search-modal-content {
            background: rgba(26, 26, 26, 0.95);
            border: 2px solid rgba(0, 102, 204, 0.5);
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.8) translateY(-50px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            color: #0066CC;
            cursor: pointer;
            transition: color 0.3s ease;
            line-height: 1;
        }

        .modal-close:hover {
            color: #0080FF;
            transform: scale(1.2);
        }

        .search-modal h2 {
            color: #0066CC;
            font-size: 1.8rem;
            margin-bottom: 25px;
            text-align: center;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.3);
        }

        .search-modal-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-modal-form input[type="text"] {
            width: 100%;
            padding: 15px 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            background: #0d0d0d;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-modal-form input[type="text"]:focus {
            outline: none;
            border-color: #0066CC;
            box-shadow: 0 0 15px rgba(0, 102, 204, 0.3);
        }

        .search-modal-form input[type="submit"] {
            padding: 15px 35px;
            border: none;
            border-radius: 25px;
            background: linear-gradient(45deg, #0066CC, #0080FF);
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-modal-form input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            padding: 40px 20px 20px;
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #0066CC;
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0, 102, 204, 0.5);
            animation: horror-flicker 3s infinite;
        }

        @keyframes horror-flicker {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
            25%, 75% { opacity: 0.9; text-shadow: 0 0 25px rgba(0, 102, 204, 0.8); }
        }

        .posts-section {
            margin-bottom: 60px;
        }

        .posts-section h2 {
            color: #0066CC;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 0 10px rgba(0, 102, 204, 0.3);
        }

        .post-item {
            background: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .post-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 102, 204, 0.1), transparent);
            transition: left 0.5s;
        }

        .post-item:hover::before {
            left: 100%;
        }

        .post-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 102, 204, 0.3);
            border-color: rgba(0, 102, 204, 0.5);
        }

        .post-item input[type="radio"] {
            position: absolute;
            top: 25px;
            left: 25px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0066CC;
        }

        .post-content {
            margin-left: 45px;
        }

        .post-title {
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 12px;
            display: block;
        }

        .post-author {
            color: #0066CC;
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: inline-block;
            background: rgba(0, 102, 204, 0.1);
            padding: 6px 18px;
            border-radius: 20px;
            border: 1px solid rgba(0, 102, 204, 0.3);
        }

        .post-text {
            color: #cccccc;
            line-height: 1.7;
            margin-top: 12px;
        }

        .post-image {
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            max-width: 400px;
        }

        .post-image img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .post-image img:hover {
            transform: scale(1.02);
        }

        .no-posts {
            text-align: center;
            color: #0066CC;
            font-size: 1.3rem;
            padding: 60px 20px;
            background: rgba(0, 102, 204, 0.05);
            border: 2px dashed rgba(0, 102, 204, 0.3);
            border-radius: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .action-buttons input[type="submit"] {
            padding: 12px 30px;
            border: none;
            border-radius: 999px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #0066CC, #0080FF);
            color: #ffffff;
        }

        .action-buttons input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 102, 204, 0.4);
        }

        .footer {
            background: #0a0a0a;
            border-top: 1px solid rgba(0, 102, 204, 0.3);
            padding: 40px 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
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

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-wrap: wrap;
            }

            .nav-menu {
                gap: 10px;
            }

            .nav-menu a,
            .nav-menu .search-button {
                padding: 8px 15px;
                font-size: 0.85rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .post-title {
                font-size: 1.2rem;
            }

            .post-content {
                margin-left: 35px;
            }

            .post-image {
                max-width: 100%;
            }

            .search-modal-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- ヘッダー -->
    <header class="site-header" id="header">
        <nav class="navbar">
            <a href="../../../game.html" class="logo">六刻巡旅</a>
            <ul class="nav-menu">
                <li><a href="../../../game.html">ホーム</a></li>
                <li><button class="search-button" onclick="openSearchModal()">🔍 検索</button></li>
                <li><a href="index_add.php" class="btn-new-header">✏️ 新規投稿</a></li>
            </ul>
        </nav>
    </header>

    <!-- 検索モーダル -->
    <div id="searchModal" class="search-modal">
        <div class="search-modal-content">
            <span class="modal-close" onclick="closeSearchModal()">&times;</span>
            <h2>🔍 検索</h2>
            <form action="index_search.php" method="post" class="search-modal-form">
                <input type="text" name="search_name" placeholder="名前で検索" required>
                <input type="submit" name="submit" value="検索">
            </form>
        </div>
    </div>

    <div class="container">
        <header class="page-header">
            <h1>六刻巡旅 - コミュニティ</h1>
        </header>

        <section class="posts-section">
            <h2>みんなの投稿</h2>
            <?php
            try {
                $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
                $dbh = new PDO($dsn);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $sql = 'SELECT code, name, title, content, image FROM mst_rokkoku ORDER BY code DESC';
                $stmt = $dbh->prepare($sql);
                $stmt->execute();

                print '<form method="post" action="index_branch.php" id="postForm">';

                $count = 0;
                while ($rec = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $count++;
                    
                    $name = isset($rec['name']) && $rec['name'] != '' ? htmlspecialchars($rec['name']) : '匿名';
                    $title = isset($rec['title']) && $rec['title'] != '' ? htmlspecialchars($rec['title']) : '(タイトルなし)';
                    $content = isset($rec['content']) && $rec['content'] != '' ? htmlspecialchars($rec['content']) : '(内容なし)';
                    $image = isset($rec['image']) && $rec['image'] != '' ? htmlspecialchars($rec['image']) : '';
                    
                    print '<div class="post-item">';
                    print '<input type="radio" name="rokkokucode" value="' . $rec['code'] . '">';
                    print '<div class="post-content">';
                    print '<a href="index_post_detail.php?code=' . $rec['code'] . '" style="text-decoration: none;">';
                    print '<span class="post-title" style="cursor: pointer; transition: color 0.3s;" onmouseover="this.style.color=\'#0080FF\'" onmouseout="this.style.color=\'#ffffff\'">' . $title . '</span>';
                    print '</a>';
                    print '<span class="post-author">👤 ' . $name . '</span>';
                    print '<div class="post-text">' . nl2br(mb_substr($content, 0, 200));
                    if (mb_strlen($content) > 200) {
                        print '...';
                    }
                    print '</div>';
                    
                    if ($image != '') {
                        print '<div class="post-image">';
                        print '<img src="./images/' . $image . '" alt="投稿画像">';
                        print '</div>';
                    }
                    
                    print '</div>';
                    print '</div>';
                }
                
                if ($count == 0) {
                    print '<div class="no-posts">まだ投稿がありません。最初の投稿をしてみませんか？</div>';
                }
                
                print '<div class="action-buttons">';
                print '<input type="submit" name="edit" value="✏️ 修正" onclick="return checkSelection()">'; 
                print '<input type="submit" name="delete" value="🗑️ 削除" onclick="return checkSelection()">';
                print '</div>';
                print '</form>';

                $dbh = null;

            } catch (Exception $e) {
                print '<p style="color: red;">ただいま障害により大変ご迷惑をおかけしております。</p>';
                print '<p>エラー詳細: ' . htmlspecialchars($e->getMessage()) . '</p>';
                exit();
            }
            ?>
        </section>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="../../../legal/privacy.html">プライバシーポリシー</a>
                <a href="../../../legal/terms-of-service.html">利用規約</a>
                <a href="../../community/confirm/confirm.html">お問い合わせ</a>
            </div>
            <div class="copyright">
                &copy; 2025 六刻巡旅 - All rights reserved
            </div>
        </div>
    </footer>
    
    <script>
        // ヘッダースクロール効果
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // 検索モーダル開閉
        function openSearchModal() {
            document.getElementById('searchModal').classList.add('active');
        }

        function closeSearchModal() {
            document.getElementById('searchModal').classList.remove('active');
        }

        // モーダル外クリックで閉じる
        window.onclick = function(event) {
            const modal = document.getElementById('searchModal');
            if (event.target === modal) {
                closeSearchModal();
            }
        }

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSearchModal();
            }
        });

        // 投稿選択チェック
        function checkSelection() {
            var radios = document.getElementsByName('rokkokucode');
            var checked = false;
            
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    checked = true;
                    break;
                }
            }
            
            if (!checked) {
                alert('投稿を選択してください。');
                return false;
            }
            
            return true;
        }
    </script>
    <script src="community.js"></script>
</body>

</html>