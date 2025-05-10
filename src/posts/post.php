<?php
session_start();
if (!empty($_POST["post"]) && !empty($_POST["track_id"]) && !empty($_POST["position"]) && !empty($_POST["track_name"])) {
    try {
        include "connection.php";
        $sql = "SELECT COUNT(*) FROM Tracks WHERE track_id=:track_id;";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':track_id', $_POST["track_id"], PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        if ($count == 0) {
            $sql = "INSERT INTO Tracks(track_id,name)VALUES(:track_id,:name);";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':track_id', $_POST["track_id"], PDO::PARAM_STR);
            $stmt->bindValue(':name', $_POST["track_name"], PDO::PARAM_STR);
            $stmt->execute();
        }

        $sql = "INSERT INTO Posts(track_id,user_id,position,content)VALUES(:track_id,:user_id,:position,:content);";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':track_id', $_POST["track_id"], PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $_SESSION['id'], PDO::PARAM_STR);
        if ($_POST["position"]) {
            $stmt->bindValue(':position', $_POST["position"], PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':position', 100, PDO::PARAM_STR);
        }
        $stmt->bindValue(':content', $_POST["post"], PDO::PARAM_STR);
        $result = $stmt->execute();

        if ($result == true) {
        } else {
            echo "エラー";
        }
    } catch (Exception $e) {
        print($e->getMessage() . "<br>");
    }
}
