<?php
// データベースに接続して、データへアクセスするためのPDOオブジェクトを返す関数
// (クラスメソッドにすることもできますが、今回は関数にしています)
function createPDO(array $config): PDO
{
    return new PDO(
        "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4",
        $config['DB_USER'],
        $config['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}


