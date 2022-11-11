<?php

session_start();
mb_internal_encoding('utf8');

if (!isset($_SESSION['id'])) {
    header('Location:login.php');
}

$errors = array();

// 投稿されたデータをDBに登録する
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input['title'] = htmlentities($_POST['title'] ?? '', ENT_QUOTES);
    $input['comments'] = htmlentities($_POST['comments'] ?? '', ENT_QUOTES);

    $errors = validateForm();

    if (empty($errors)) {
        try {
            $pdo = new PDO('mysql:dbname=php_jissen;host=localhost;', 'root', 'root');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare(' INSERT INTO post (user_id, title, comments) VALUES (?, ?, ?) ');
            $stmt->execute(array($_SESSION['id'], $input['title'], $input['comments']));
            $pdo = null;
        } catch (PDOException $e) {
            $e->getMessage();
        }
    }
}

// 投稿を表示するためのデータをDBから取ってくる
// => HTMLへ出力するために配列へ格納する
try {
    $pdo = new PDO('mysql:dbname=php_jissen;host=localhost;', 'root', 'root');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query(' SELECT title, comments, name, posted_at FROM post
        JOIN user ON post.user_id = user.id ORDER BY posted_at DESC ');
    $pdo = null;

    $posts = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = (new DateTime($row['posted_at']))->format('Y年m月d日 H:i');
        $posts[] = array(
            'title' => $row['title'],
            'comments' => $row['comments'],
            'name' => $row['name'],
            'posted_at' => $date
        );
    }
} catch (PDOException $e) {
    $e->getMessage();
}

function validateForm() {
    $errors = array();
    if (strlen(trim($_POST['title'] ?? '')) == 0) {
        $errors['title'] = 'タイトルを入力してください';
    }
    if (strlen(trim($_POST['comments'] ?? '')) == 0) {
        $errors['comments'] = 'コメントを入力してください';
    }
    return $errors;
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>掲示板</title>
    <link rel="stylesheet" type="text/css" href="style/board.css">
</head>
<body>
    <div class="top">
        <div class="logo">
            <img src="images/4eachblog_logo.jpg">
        </div>
        <div class="user-info">
            <p>こんにちは<?= $_SESSION['name'] ?? ''; ?>さん</p>
            <form method="POST" action="logout.php">
                <input type="submit" value="ログアウト">
            </form>
        </div>
    </div>
    <header>
        <ul class="menu">
            <li>トップ</li>
            <li>プロフィール</li>
            <li>4eachについて</li>
            <li>登録フォーム</li>
            <li>問い合わせ</li>
            <li>その他</li>
        </ul>
    </header>
    <main>
        <div class="container">
            <div class="board">
                <h1 class="title">プログラミングに役立つ掲示板</h1>
                <form method="POST" action="" class="wrapper">
                    <h2 class="form-title" class="text">入力フォーム</h2>
                    <div class="item">
                        <label>タイトル</label>
                        <input type="text" name="title" class="text">
                        <?php if (!empty($errors['title'])): ?>
                            <p class="err_message"><?= $errors['title']; ?></p>
                        <?php endif; ?>
                    </div>      
                    <div class="item">
                        <label>コメント</label>
                        <textarea name="comments" rows="8"></textarea>
                        <?php if (!empty($errors['comments'])): ?>
                            <p class="err_message"><?= $errors['comments']; ?></p>
                        <?php endif; ?>
                    </div> 
                    <div class="item">
                        <input class="submit" type="submit" value="送信する">
                    </div>
                </form>
                <?php foreach ($posts as $post): ?>
                    <div class="content">
                        <h3><?= $post['title']; ?></h3>
                        <hr>
                        <p><?= $post['comments']; ?></p>
                        <p class="meta">投稿者：<?= $post['name']; ?></p>
                        <p class="meta">投稿時間：<?= $post['posted_at']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sidebar">
                <h2>人気の記事</h2>
                <ul>
                    <li>PHPオススメ本</li>
                    <li>PHP MyAdminの使い方</li>
                    <li>今人気のエディタ Top5</li>
                    <li>HTMLの基礎</li>
                </ul>
                <h2>オススメリンク</h2>
                <ul>
                    <li>インターノウス株式会社</li>
                    <li>XAMPPのダウンロード</li>
                    <li>Eclipseのダウンロード</li>
                    <li>Bracketsのダウンロード</li>
                </ul>
                <h2>カテゴリ</h2>
                <ul>
                    <li>HTML</li>
                    <li>PHP</li>
                    <li>MySQL</li>
                    <li>Javascript</li>
                </ul>
            </div>
        </div>
    </main>
</body>
</html>