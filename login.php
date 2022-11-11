<?php

session_start();
mb_internal_encoding('utf8');

// ログイン済みならば掲示板へリダイレクト
if (isset($_SESSION['id'])) {
    header('Location:board.php');
}

$errors = '';

// フォームが送信された際の処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input['mail'] = htmlentities($_POST['mail'] ?? '', ENT_QUOTES);
    $input['password'] = htmlentities($_POST['password'] ?? '', ENT_QUOTES);

    $errors = validateForm();

    // 入力された値に不備がなければログイン処理
    if (empty($errors)) {
        // DB操作
        try {
            $pdo = new PDO('mysql:dbname=php_jissen;host=localhost;', 'root', 'root');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare(' SELECT * FROM user WHERE mail = ? ');
            $stmt->execute(array($input['mail']));
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $pdo = null;
        } catch (PDOException $e) {
            $e->getMessage();
        }

        // ユーザー認証できればCookieに値を保存
        // => 掲示板へリダイレクト
        if ($user && password_verify($input['password'], $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['mail'] = $user['mail'];
            $_SESSION['password'] = $input['password'];
        
            if ($_POST['login_keep'] == 1) {
                $_SESSION['login_keep'] = $_POST['login_keep'];
            }

            if (!empty($_SESSION['id']) && !empty($_SESSION['login_keep'])) {
                setcookie('mail', $_SESSION['mail'], time()+60*60*24*7);
                setcookie('password', $_SESSION['password'], time()+60*60*24*7);
                setcookie('login_keep', $_SESSION['login_keep'], time()+60*60*24*7);
            } else if (empty($_SESSION['login_keep'])) {
                setcookie('mail', '', time()-1);
                setcookie('password', '', time()-1);
                setcookie('login_keep', '', time()-1);
            }
            
            header('Location:board.php');
        } else {
            $errors = 'メールアドレスとパスワードを正しく入力してください。';
        } 
    }
}

function validateForm() {
    if (!filter_input(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL)) {
        return 'メールアドレスとパスワードを正しく入力してください。';
    }
    if (strlen(trim($_POST['password'] ?? '')) == 0) {
        return 'メールアドレスとパスワードを正しく入力してください。';
    }
    return '';
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログインページ</title>
    <link rel="stylesheet" type="text/css" href="style/login.css">
</head>
<body>
    <h1 class="form_title">ログインページ</h1>
    <form method="POST" action="">
        <div class="item">
            <label>メールアドレス</label>
            <input type="text" class="text" size="35" name="mail" value="<?php
                if ($_COOKIE['login_keep'] ?? '') {
                    echo $_COOKIE['mail'];
                }
            ?>">
        </div>
        <div class="item">
            <label>パスワード</label>
            <input type="password" class="text" size="35" name="password" value="<?php
                if ($_COOKIE['login_keep'] ?? '') {
                    echo $_COOKIE['password'];
                }
            ?>">
            <?php if (!empty($errors)): ?>
                <p class="err_message"><?= $errors; ?></p>
            <?php endif; ?>
        </div>
        <div class="item">
            <label>
                <input type="checkbox" name="login_keep" value="1"
                <?php
                    if ($_COOKIE['login_keep'] ?? '') {
                        echo "checked='checked'";
                    }
                ?>>ログイン状態を保持する
            </label>
        </div>
        <div class="item">
            <input type="submit" class="submit" value="ログイン">
        </div>
    </form>
</body>
</html>