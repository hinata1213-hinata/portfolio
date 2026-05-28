<?php
$host = 'localhost';
$dbname = 'posts';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}

$name = $_POST['name'];
$message = $_POST['message'];
$imagePath = null;


// データベースに投稿を保存
$sql = "INSERT INTO posts (name, message, image) VALUES (:name, :message, :image)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':name', $name);
$stmt->bindParam(':message', $message);
$stmt->bindParam(':image', $imagePath);
$stmt->execute();

header("Location: index.php");
exit;
?>