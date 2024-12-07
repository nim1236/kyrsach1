<?php
session_start();
require 'db.php'; // Подключение к базе данных

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Неавторизованный доступ.']);
    exit();
}

$currentUserId = $_SESSION['user_id'];

// Функция для шифрования сообщений
function encryptMessage($message, $key)
{
    $cipher = "aes-256-cbc";
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_encrypt($message, $cipher, $key, 0, $iv);
}

// Функция для дешифрования сообщений
function decryptMessage($encryptedMessage, $key)
{
    $cipher = "aes-256-cbc";
    $iv = substr(hash('sha256', $key), 0, 16);
    return openssl_decrypt($encryptedMessage, $cipher, $key, 0, $iv);
}

// Ключ шифрования (желательно заменить на более сложный)
$encryptionKey = "secure-chat-key";

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['friend_id'])) {
    // Получение сообщений
    $friendId = intval($_GET['friend_id']);

    $stmt = $pdo->prepare("
        SELECT sender_id, receiver_id, message 
        FROM messages 
        WHERE (sender_id = :current_user AND receiver_id = :friend_id) 
           OR (sender_id = :friend_id AND receiver_id = :current_user)
        ORDER BY id ASC
    ");
    $stmt->execute([
        ':current_user' => $currentUserId,
        ':friend_id' => $friendId
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Расшифровываем сообщения
    foreach ($messages as &$msg) {
        $msg['message'] = decryptMessage($msg['message'], $encryptionKey);
    }

    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Отправка сообщения
    $data = json_decode(file_get_contents('php://input'), true);
    $friendId = intval($data['friend_id']);
    $message = trim($data['message']);

    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Сообщение не может быть пустым.']);
        exit();
    }

    // Шифруем сообщение
    $encryptedMessage = encryptMessage($message, $encryptionKey);

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)");
    $stmt->execute([
        ':sender_id' => $currentUserId,
        ':receiver_id' => $friendId,
        ':message' => $encryptedMessage
    ]);

    echo json_encode(['status' => 'success']);
    exit();
}
?>
