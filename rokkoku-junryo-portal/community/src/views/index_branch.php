<?php
session_start();

if (isset($_POST['edit'])) {
    if(!isset($_POST['rokkokucode']) || $_POST['rokkokucode'] == '') {
        header('Location: index_ng.php');
        exit();
    }
    $rokkoku_code = $_POST['rokkokucode'];
    $_SESSION['rokkokucode'] = $rokkoku_code;
    header('Location: index_content_edit.php');
    exit();
}

if (isset($_POST['delete'])) {
    if(!isset($_POST['rokkokucode']) || $_POST['rokkokucode'] == '') {
        header('Location: index_ng.php');
        exit();
    }
    $rokkoku_code = $_POST['rokkokucode'];
    $_SESSION['rokkokucode'] = $rokkoku_code;
    header('Location: index_delete.php');
    exit();
}

header('Location: index.blade.php');
exit();
?>