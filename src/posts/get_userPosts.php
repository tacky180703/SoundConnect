<?php
header('Content-Type: application/json');

try {
    include "connection.php";
    $posts = [];
    $user_id = $_GET['user_id'] ?? '';
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM Posts INNER JOIN Tracks ON Posts.track_id=Tracks.track_id WHERE Posts.user_id = :user_id ORDER BY Posts.timestamp ASC;");
        $stmt->bindValue(":user_id", $user_id, PDO::PARAM_STR);
        $stmt->execute();
        $posts = $stmt->fetchAll();
    }
    echo json_encode($posts);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage()); // エラーメッセージをログに記録
    echo json_encode(['error' => 'Connection failed']);
    exit();
}
