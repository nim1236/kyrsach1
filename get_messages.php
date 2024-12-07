<?php
// Подключение к базе данных с параметром для установления кодировки
$dsn = 'mysql:host=localhost;dbname=users;charset=utf8';  // Указание кодировки UTF-8
$username = 'root';  // Имя пользователя
$password = '';  // Пароль

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",  // Установка кодировки
    PDO::ATTR_PERSISTENT => true  // Установка постоянного соединения (может помочь с ошибками)
];

try {
    // Подключение к базе данных
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Проверка успешного подключения
    error_log("Database connection successful.");
    
} catch (PDOException $e) {
    // Если ошибка подключения, выводим сообщение
    error_log("Database connection error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    exit;
}

// Получение session_id и friend_id из POST-запроса
$session_id = isset($_POST['session_id']) ? $_POST['session_id'] : null;
$friend_id = isset($_POST['friend_id']) ? $_POST['friend_id'] : null;

// Логирование входящих данных для отладки
error_log("Received request with session_id: " . $session_id);
error_log("Received request with friend_id: " . $friend_id);

// Проверка, что session_id и friend_id переданы
if (empty($session_id) || empty($friend_id)) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

// Проверка существования пользователей
$stmt = $pdo->prepare("SELECT * FROM user WHERE user_id = :session_id OR user_id = :friend_id");
$stmt->execute([
    ':session_id' => $session_id,
    ':friend_id' => $friend_id
]);

// Используем цикл while для совместимости с устаревшими версиями PHP
$userExists = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userExists[] = $row;
}

if (count($userExists) < 2) {
    // Если один или оба пользователя не существуют
    echo json_encode(["error" => "One or both users not found"]);
    exit;
}

// Запрос на получение сообщений между пользователями
$stmt = $pdo->prepare(
    "SELECT message, sender_id, receiver_id 
    FROM messages 
    WHERE (sender_id = :session_id AND receiver_id = :friend_id) 
    OR (sender_id = :friend_id AND receiver_id = :session_id) 
    ORDER BY sender_id ASC"
);
$stmt->execute([
    ':session_id' => $session_id,
    ':friend_id' => $friend_id
]);

// Используем цикл while для получения сообщений (для совместимости с PHP 5.x)
$messages = [];
while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = $message;
}

// Логирование количества сообщений для отладки
error_log("Messages found: " . count($messages));

// Отправка сообщений в формате JSON
header('Content-Type: application/json');
echo json_encode($messages);
?>
