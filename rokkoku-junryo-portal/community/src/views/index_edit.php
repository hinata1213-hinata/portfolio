<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿</title>
</head>
<body>
    <?php
    try {
        // 前の画面から入力データを受け取って、変数にコピーしています。
        $rokkoku_code = $_POST['code'];

        // データベースに接続しています。
        $dsn = 'sqlite:' . __DIR__ . '/../../data/rokkoku.db';
        $dbh = new PDO($dsn);  // SQLiteではユーザー名とパスワードは不要
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 投稿削除のSQL文を実行しています。
        $sql = 'DELETE FROM mst_rokkoku WHERE code=?';
        $stmt = $dbh->prepare($sql);
        $data[] = $rokkoku_code;
        $stmt->execute($data);

        // データベースから切断しています。
        $dbh = null;

    } catch (Exception $e) {
        print 'ただいま障害により大変ご迷惑をおかけしております。';
        exit();
    }
    ?>
    投稿修正<br>
    <br/>
    <?php print $rokkoku_code; ?>
    <br/>
    <br/>
    <form method="post" action="index_edit_check.php" enctype="multipart/form-data">
    <input type="hidden" name="code" value="<?php print $rokkoku_code; ?>">
    <input type="hidden" name="image_name_old" value="<?php print $rokkoku_image_name_old; ?>">
    名前<br/>
    <input type="text" name="name" style="width:200px" value="<?php print $rokkoku_name; ?>"><br/>
    投稿内容<br/>
    <input type="text" name="content" style="width:50px" value="<?php print $rokkoku_content;?>"><br/>
    タイトル<br/>
    <input type="text" name="title" style="width:400px" value="<?php print $rokkoku_title; ?>"><br/>
    <br/>
    画像を選んでください。<br/>
    <input type="file" name="image" style="width:400px"><br/>
    <br/>
    <input type="button" onclick="history.back()" value="戻る">
    <input type="submit" value="OK">
    </form> 
    
</body>
</html>
