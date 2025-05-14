<?php
session_start();

// Настройки подключения к БД
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $fio = $_POST['fio'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $languages = isset($_POST['languages']) ? implode(',', $_POST['languages']) : '';
    $biography = $_POST['biography'] ?? '';
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;

    // Валидация полей
    $errors = [];

    if (empty($fio)) $errors[] = 'Поле ФИО не должно быть пустым.';
    if (empty($email)) $errors[] = 'Поле E-mail не должно быть пустым.';
    if (empty($birthdate)) $errors[] = 'Поле Дата рождения не должно быть пустым.';
    if (empty($gender)) $errors[] = 'Выберите пол.';
    if (empty($languages)) $errors[] = 'Выберите хотя бы один язык программирования.';
    if (empty($biography)) $errors[] = 'Поле Биография не должно быть пустым.';
    if (!$contract_accepted) $errors[] = 'Необходимо принять контракт.';

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['values'] = $_POST;
        header('Location: index.php');
        exit();
    }

    // Генерируем логин и пароль
    $login = generateLogin();
    $pass = generatePassword();
    $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, password_hash, fio, phone, email, birthdate, gender, languages, biography, contract_accepted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$login, $pass_hash, $fio, $phone, $email, $birthdate, $gender, $languages, $biography, $contract_accepted]);
    } catch (PDOException $e) {
        die("Ошибка при сохранении данных: " . $e->getMessage());
    }

    // Устанавливаем куки
    setcookie('login', $login, time() + 3600 * 24 * 7); // на неделю
    setcookie('pass', $pass, time() + 3600 * 24 * 7);   // на неделю

    // Перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}

function generateLogin() {
    return substr(md5(uniqid(rand(), true)), 0, 10);
}

function generatePassword() {
    return substr(md5(uniqid(rand(), true)), 0, 10);
}
?>
