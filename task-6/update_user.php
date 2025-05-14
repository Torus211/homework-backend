<?php

// Подключение к базе данных
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fio = $_POST['fio'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $languages = $_POST['languages'] ?? '';
    $biography = $_POST['biography'] ?? '';
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;

    // Валидация
    if (empty($fio) || empty($email) || empty($birthdate)) {
        die("Все обязательные поля должны быть заполнены.");
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE users SET
                fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?,
                languages = ?, biography = ?, contract_accepted = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $fio, $phone, $email, $birthdate, $gender,
            $languages, $biography, $contract_accepted, $id
        ]);
    } catch (PDOException $e) {
        die("Ошибка при обновлении данных: " . $e->getMessage());
    }

    header('Location: admin.php');
    exit();
}
