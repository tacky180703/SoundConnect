<?php session_start() ?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>

<body>
    <form id="loginForm" method="POST" action="login.php">
        <label for="username">ユーザー名:</label>
        <input type="text" id="username" name="username" required><br>
        <label for="password">パスワード:</label>
        <input type="password" id="password" name="password" required><br>
        <button type="submit">ログイン</button>
    </form>
    <?php
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {
        try {
            include "../src/auth/connection.php";
            $sql = "SELECT * FROM Users WHERE name=:name;";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":name", $_POST["username"], PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($_POST["password"], $user['password'])) {
                $_SESSION["id"] = $user['user_id'];
                header("Location: index.php");
                exit();
            }
        } catch (Exception $e) {
            print($e->getMessage() . "<br>");
        }
    }
    ?>
</body>