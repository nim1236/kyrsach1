<?php
require 'db.php';

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$sessionId = htmlspecialchars($data['session_id']);
$friendId = (int)$data['friend_id'];
$message = htmlspecialchars($data['message']);

// Шифрование сообщения
$encryption_key = 'your_secret_key';
$encrypted_message = openssl_encrypt($message, 'aes-256-cbc', $encryption_key, 0, '1234567890123456');

// Получаем текущего пользователя
$stmt = $pdo->prepare("SELECT id FROM user WHERE session_id = :session_id");
$stmt->execute([':session_id' => $sessionId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    echo json_encode(['message' => 'Ошибка отправки сообщения.']);
    exit;
}

$user_id = $userData['id'];

// Добавление сообщения в базу данных
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)");
$stmt->execute([':sender_id' => $user_id, ':receiver_id' => $friendId, ':message' => $encrypted_message]);

echo json_encode(['message' => 'Сообщение отправлено']);
