<?php
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// DB接続（index_add_done.php と同じパス）
$dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
try {
    $dbh = new PDO($dsn);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($q === '') {
        // 空検索の場合は最新100件を返す（必要なければ変更）
        $sql = 'SELECT id, title, name, content, image FROM mst_rokkoku ORDER BY id DESC LIMIT 100';
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
    } else {
        // 部分一致検索（SQLite用）
        $sql = 'SELECT id, title, name, content, image FROM mst_rokkoku WHERE title LIKE ? ORDER BY id DESC';
        $stmt = $dbh->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->execute([$like]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    $dbh = null;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}