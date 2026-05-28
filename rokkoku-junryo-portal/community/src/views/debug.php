<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>データベース確認</title>
</head>
<body>
    <h2>データベース内容確認</h2>
    <?php
    $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
    $dbh = new PDO($dsn);

    echo '接続中DB: ' . realpath(__DIR__ . '/../../data/rokkoku.db') . '<br>';

    $stmt = $dbh->query("SELECT name FROM sqlite_master WHERE type='table'");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;

    ?>
</body>
</html>