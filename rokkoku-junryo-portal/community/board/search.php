<?php
try {
    // データベースに接続（utf8mb4を使用して、文字化けを防止）
    $dsn = 'mysql:host=localhost;dbname=board;charset=utf8mb4';
    $username = 'root';
    $password = '';
    $pdo = new PDO($dsn, $username, $password);

    $pdo->exec("SET NAMES 'utf8mb4'");

    // エラーモードを例外に設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 検索キーワードを取得し、前後の空白を削除
    $search_name = isset($_POST['search_name']) ? trim($_POST['search_name']) : '';

    // 結果の初期化
    $results = [];

    // 検索処理
    if (!empty($search_name)) {
        // messages テーブルの content カラムを検索
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE content LIKE :search_name");
        $stmt->bindValue(':search_name', '%' . $search_name . '%', PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>検索結果</title>
    <link rel="stylesheet" href="styles.css"> <!-- 上記のCSSファイルを適用 -->
</head>
<body>
    <h1>検索結果</h1>

    <!-- 検索フォーム -->
    <form method="POST">
        <label for="search_name">名前を検索:</label>
        <input type="text" name="search_name" id="search_name" value="<?php echo htmlspecialchars($search_name, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit">検索</button>
    </form>

    <!-- 検索結果の表示 -->
    <?php if (!empty($results)): ?>
        <h2>検索結果</h2>
        <?php foreach ($results as $row): ?>
        <div class="post">
            <h3>[<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><strong><?php echo htmlspecialchars($row['submit_name'], ENT_QUOTES, 'UTF-8'); ?></strong> - <?php echo htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')); ?></p>

            <?php if (!empty($row['image'])): ?>
            <img src="<?php echo htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="投稿画像" style="max-width: 300px; height: auto;">
            <?php endif; ?>

            <!-- 削除ボタン（必要に応じて） -->
            <form class="deleteform" method="post" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" onclick="return confirm('この投稿を削除してもよろしいですか？')">削除</button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>検索結果はありません。</p>
    <?php endif; ?>

</body>
</html>