<?php
require 'db.php';

// Проверка сессии
if (!isset($_GET['session_id'])) {
    header('Location: login.php');
    exit;
}


$sessionId = htmlspecialchars($_GET['session_id']);
$stmt = $pdo->prepare("SELECT id, Nick, Email FROM user WHERE session_id = :session_id");
$stmt->execute([':session_id' => $sessionId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    header('Location: login.php');
    exit;
}

$user_id = $userData['id'];
$user_nick = htmlspecialchars($userData['Nick']);
$user_email = htmlspecialchars($userData['Email']);

// Получение списка друзей
$stmt = $pdo->prepare("
    SELECT u.id, u.Nick 
    FROM friends f
    JOIN user u ON u.id = f.friend_id 
    WHERE f.user_id = :user_id
");
$stmt->execute([':user_id' => $user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой аккаунт</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .chat-container {
            display: none;
        }
        .chat-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-info">
            <h2><?php echo $user_nick; ?> (<?php echo $user_email; ?>)</h2>
        </div>
        <div class="search">
            <input type="text" id="searchFriend" placeholder="Поиск друзей">
            <button onclick="searchFriend()">Найти</button>
        </div>
    </div>

    <div class="main">
        <div class="friends-list">
            <h3>Мои друзья</h3>
            <ul>
                <?php foreach ($friends as $friend): ?>
                    <li>
                        <?php echo htmlspecialchars($friend['Nick']); ?>
                        <button onclick="openChat(<?php echo $friend['id']; ?>)">Чат</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="chat-container" id="chatContainer">
            <h3>Чат с <span id="chatFriendName"></span></h3>
            <div id="messages"></div>
            <textarea id="messageInput" placeholder="Введите сообщение"></textarea>
            <div class="chat-actions">
                <button onclick="sendMessage()">Отправить</button>
                <button id="refreshChatBtn">Обновить чат</button>
            </div>
        </div>
    </div>

    <script>
        let currentChatUser = null;

        function searchFriend() {
            const searchQuery = document.getElementById('searchFriend').value;
            axios.post('search_friend.php', {
                query: searchQuery,
                session_id: '<?php echo $sessionId; ?>'
            })
            .then(response => {
                alert(response.data.message);
            })
            .catch(error => {
                console.error(error);
                alert("Ошибка поиска!");
            });
        }

        function openChat(friendId) {
            console.log('Opening chat with friend ID:', friendId);
            currentChatUser = friendId;
            document.getElementById('chatContainer').style.display = 'block';

            const friendName = document.querySelector(`button[onclick="openChat(${friendId})"]`).parentElement.firstChild.nodeValue.trim();
            document.getElementById('chatFriendName').textContent = friendName;

            loadMessages(friendId);
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message) {
                alert("Сообщение не может быть пустым!");
                return;
            }

            axios.post('send_message.php', {
                friend_id: currentChatUser,
                session_id: '<?php echo $sessionId; ?>',
                message: message
            })
            .then(() => {
                const messageContainer = document.getElementById('messages');
                const userName = '<?php echo $user_nick; ?>';

                const messageElement = document.createElement('div');
                messageElement.innerHTML = `<strong>${userName}</strong>: ${message}`;
                messageContainer.appendChild(messageElement);

                messageInput.value = '';
                messageInput.focus();
            })
            .catch(error => {
                console.error("Ошибка отправки сообщения:", error);
                alert("Ошибка отправки сообщения!");
            });
        }

        document.getElementById('refreshChatBtn').addEventListener('click', function() {
            if (currentChatUser) {
                loadMessages(currentChatUser);
            }
        });

        function loadMessages(friendId) {
    const messageContainer = document.getElementById('messages');

    console.log('session_id:', '<?php echo $sessionId; ?>');
    console.log('friend_id:', friendId);

    axios.post('get_messages.php', {
        friend_id: friendId,
        session_id: '<?php echo $sessionId; ?>'
    })
    .then(response => {
        console.log('Response:', response.data); // Выводим ответ от сервера
        const messages = response.data.messages; // Получаем массив сообщений
        if (!messages || messages.length === 0) {
            messageContainer.innerHTML = '<div>Нет сообщений.</div>'; // Если сообщений нет
            return;
        }

        messageContainer.innerHTML = ''; // Очищаем контейнер

        // Обрабатываем каждое сообщение по одному
        messages.forEach((msg, index) => {
            setTimeout(() => {
                const messageElement = document.createElement('div');
                messageElement.innerHTML = `<strong>${msg.Nick}</strong>: ${msg.message}`;
                messageContainer.appendChild(messageElement);

                // Прокручиваем контейнер вниз для отображения последнего сообщения
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }, index * 300); // Выводим сообщение с задержкой (например, 300 мс)
        });
    })
    .catch(error => {
        console.error("Ошибка загрузки сообщений:", error);
        messageContainer.innerHTML = '<div>Ошибка загрузки сообщений</div>';
    });
}

    </script>
</body>
</html>
