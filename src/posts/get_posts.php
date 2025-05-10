<?php
header('Content-Type: application/json');

try {
    include "../auth/connection.php";
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage()); // エラーメッセージをログに記録
    echo json_encode(['error' => 'Connection failed']);
    exit();
}

$track_id = $_GET['track_id'] ?? '';

$posts = [];
if ($track_id) {
    $stmt = $pdo->prepare("SELECT Posts.*,Users.name FROM Posts INNER JOIN Users ON Posts.user_id=Users.user_id WHERE track_id = ? ORDER BY position DESC;");
    if ($stmt->execute([$track_id])) {
        $posts = $stmt->fetchAll();
    } else {
        error_log("Failed to fetch posts for track_id: $track_id"); // エラーメッセージをログに記録
        echo json_encode(['error' => 'Failed to fetch posts']);
        exit();
    }
} else {
    error_log("Track ID not provided"); // エラーメッセージをログに記録
    echo json_encode(['error' => 'Track ID not provided']);
    exit();
}

echo json_encode($posts);
