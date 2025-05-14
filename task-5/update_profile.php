<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Подключение к БД
$host = 'localhost';
$dbname = 'u68643';
$username_db = 'u68643';
$password_db = '7475566';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Получаем ID пользователя
$user_id = $_SESSION['user_id'];

// Получаем данные из формы
$fio = $_POST['fio'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? '';
$biography = $_POST['biography'] ?? '';
$contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;

// Валидация
$errors = [];

if (empty($fio)) $errors[] = 'Поле ФИО не должно быть пустым.';
if (empty($email)) $errors[] = 'Поле Email не должно быть пустым.';
if (empty($birthdate)) $errors[] = 'Поле Дата рождения не должно быть пустым.';
if (empty($gender)) $errors[] = 'Выберите пол.';
if (empty($languages)) $errors[] = 'Введите любимые языки программирования.';
if (empty($biography)) $errors[] = 'Поле Биография не должно быть пустым.';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header('Location: edit_profile.php');
    exit();
}

// Обновление данных
try {
    $stmt = $pdo->prepare("
        UPDATE users SET
            fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?,
            languages = ?, biography = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fio, $phone, $email, $birthdate, $gender, $languages, $biography, $user_id
    ]);
} catch (PDOException $e) {
    die("Ошибка при обновлении данных: " . $e->getMessage());
}

// Перенаправление обратно в профиль
header('Location: profile.php');
exit();
