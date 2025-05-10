<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>Regist account</title>
</head>

<body>
    <form action="regist.php" method="post">
        <label for:="user_id">ユーザーID:</label>
        <input type="text" id="user_id" name="user_id" required>

        <label for:="password">パスワード:</label>
        <input type="password" id="password" name="password" required>

        <label for:="confirm_password">パスワード確認:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">登録</button>
    </form>
    <?php
    if (!empty($_POST["user_id"]) && !empty($_POST["password"])) {
        try {
            include "../auth/connection.php";
            if ($_POST['password'] == $_POST['confirm_password']) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "INSERT INTO Users(name,password) VALUES(:name,:password);";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(":name", $_POST["user_id"], PDO::PARAM_STR);
                $stmt->bindValue(":password", $hashed_password, PDO::PARAM_STR);
                $stmt->execute();

                header("Location: ../../public/login.php");
                exit();
            }
        } catch (Exception $e) {
            print($e->getMessage() . "<br>");
        }
    }
    ?>
</body>