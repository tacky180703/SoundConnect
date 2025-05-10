<?php
header('Content-Type: application/json');

try {
    include "connection.php";
    $track_id = $_GET['track_id'] ?? '';
    if ($track_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS post_count FROM Posts WHERE track_id = :track_id;");
        $stmt->bindValue(":track_id", $track_id, PDO::PARAM_STR);
        $stmt->execute();
        $post_count = $stmt->fetchColumn();
        echo json_encode($post_count);
    }
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage()); // エラーメッセージをログに記録
    echo json_encode(['error' => 'Connection failed']);
    exit();
}
