<?php
session_start();

// Проверяем, если пользователь не авторизован
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Соединение с базой данных
$host = 'localhost';
$dbname = 'u68643'; // Название базы данных
$username_db = 'u68643'; // Логин пользователя MySQL
$password_db = '7475566'; // Пароль пользователя MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Получаем информацию о пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Профиль пользователя</h1>
    <p><strong>ФИО:</strong> <?= htmlspecialchars($user['fio']) ?></p>
    <p><strong>Телефон:</strong> <?= htmlspecialchars($user['phone']) ?></p>
    <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Дата рождения:</strong> <?= htmlspecialchars($user['birthdate']) ?></p>
    <p><strong>Пол:</strong> <?= htmlspecialchars($user['gender']) ?></p>
    <p><strong>Любимые языки программирования:</strong> <?= htmlspecialchars($user['languages']) ?></p>
    <p><strong>Биография:</strong> <?= htmlspecialchars($user['biography']) ?></p>
    <p><strong>Контракт:</strong> <?= $user['contract_accepted'] ? 'Принят' : 'Не принят' ?></p>

    <!-- Кнопка Редактировать -->
    <a href="edit_profile.php">Редактировать</a>
    <a href="logout.php">Выйти</a>
</div>

</body>
</html>
