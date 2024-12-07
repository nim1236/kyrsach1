<?php
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$sessionId = htmlspecialchars($data['session_id']);
$query = htmlspecialchars($data['query']);

// Получаем текущего пользователя
$stmt = $pdo->prepare("SELECT id FROM user WHERE session_id = :session_id");
$stmt->execute([':session_id' => $sessionId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo json_encode(['message' => 'Пользователь не найден.']);
    exit;
}

$user_id = $userData['id'];

// Поиск пользователя по имени
$stmt = $pdo->prepare("SELECT id, Nick FROM user WHERE Nick LIKE :query AND id != :user_id");
$stmt->execute([
    ':query' => '%' . $query . '%',
    ':user_id' => $user_id
]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo json_encode(['message' => 'Пользователь не найден.']);
    exit;
}

// Добавление в друзья
foreach ($results as $result) {
    $friend_id = $result['id'];

    // Проверяем, есть ли уже дружба
    $checkStmt = $pdo->prepare("SELECT * FROM friends WHERE user_id = :user_id AND friend_id = :friend_id");
    $checkStmt->execute([':user_id' => $user_id, ':friend_id' => $friend_id]);

    if ($checkStmt->rowCount() == 0) {
        // Создаем взаимную связь
        $insertStmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:user_id, :friend_id), (:friend_id, :user_id)");
        $insertStmt->execute([':user_id' => $user_id, ':friend_id' => $friend_id]);
    }
}

echo json_encode(['message' => 'Пользователь добавлен в друзья.']);
