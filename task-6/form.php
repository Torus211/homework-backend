<?php
session_start();

$host = 'localhost';
$dbname = 'u68649';
$username = 'u68649';
$password = '9841747';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Подключение к базе данных успешно<br>";
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Валидация данных
    if (empty($_POST['fio']) || !preg_match("/^[а-яА-Яa-zA-Z\s]+$/u", $_POST['fio'])) {
        $errors[] = 'Неверный формат ФИО. Оно должно содержать только буквы и пробелы.';
    }

    if (!empty($_POST['phone']) && !preg_match("/^\+?[0-9]{1,15}$/", $_POST['phone'])) {
        $errors[] = 'Неверный формат телефона.';
    }

    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат e-mail.';
    }

    if (empty($_POST['birthdate']) || strtotime($_POST['birthdate']) === false) {
        $errors[] = 'Неверный формат даты рождения.';
    }

    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
        $errors[] = 'Выберите пол.';
    }

    if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
        $errors[] = 'Выберите хотя бы один язык программирования.';
    } else {
        $valid_languages = range(1, 12);
        foreach ($_POST['languages'] as $language) {
            if (!in_array($language, $valid_languages)) {
                $errors[] = 'Некорректный выбор языка программирования.';
                break;
            }
        }
    }

    if (empty($_POST['biography']) || strlen(trim($_POST['biography'])) < 10) {
        $errors[] = 'Биография должна содержать хотя бы 10 символов.';
    }

    if (!isset($_POST['contract_accepted'])) {
        $errors[] = 'Вы должны подтвердить ознакомление с контрактом.';
    }

    if ($errors) {
        echo "Ошибки валидации: " . implode(", ", $errors) . "<br>";
        setcookie('errors', serialize($errors), time() + 3600, '/');
        setcookie('form[fio]', $_POST['fio'], time() + 3600, '/');
        setcookie('form[phone]', $_POST['phone'], time() + 3600, '/');
        setcookie('form[email]', $_POST['email'], time() + 3600, '/');
        setcookie('form[birthdate]', $_POST['birthdate'], time() + 3600, '/');
        setcookie('form[gender]', $_POST['gender'], time() + 3600, '/');
        setcookie('form[languages]', serialize($_POST['languages']), time() + 3600, '/');
        setcookie('form[biography]', $_POST['biography'], time() + 3600, '/');
        setcookie('form[contract_accepted]', isset($_POST['contract_accepted']) ? 1 : 0, time() + 3600, '/');
        header('Location: index.php');
        exit();
    }

    try {
        $pdo->beginTransaction();
        echo "Начало транзакции<br>";

        if (!isset($_SESSION['user_id'])) {
            // Генерация логина и пароля
            $login = 'user_' . uniqid();
            $password = bin2hex(random_bytes(8));
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "Логин: $login, Пароль: $password<br>";

            // Сохранение данных в таблицу application
            $stmt = $pdo->prepare("INSERT INTO application (fio, phone, email, birthdate, gender, biography, contract_accepted) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['biography'], 1]);
            $application_id = $pdo->lastInsertId();
            echo "Данные сохранены в application, application_id: $application_id<br>";

            // Сохранение учетных данных в таблицу users
            $stmt = $pdo->prepare("INSERT INTO users (application_id, login, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$application_id, $login, $password_hash]);
            echo "Данные сохранены в users<br>";

            // Сохранение языков
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($_POST['languages'] as $language_id) {
                $stmt->execute([$application_id, $language_id]);
            }
            echo "Языки сохранены в application_languages<br>";

            // Отображение логина и пароля один раз
            setcookie('credentials', json_encode(['login' => $login, 'password' => $password]), time() + 3600, '/');
            setcookie('success', 'Регистрация успешна! Логин и пароль отображены ниже.', time() + 3600, '/');
        } else {
            // Обновление данных для авторизованного пользователя
            $application_id = $_SESSION['application_id'];
            $stmt = $pdo->prepare("UPDATE application SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, biography = ?, contract_accepted = ? WHERE id = ?");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'], $_POST['gender'], $_POST['biography'], 1, $application_id]);

            $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt->execute([$application_id]);
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($_POST['languages'] as $language_id) {
                $stmt->execute([$application_id, $language_id]);
            }

            setcookie('success', 'Данные успешно обновлены!', time() + 3600, '/');
        }

        $pdo->commit();
        echo "Транзакция завершена<br>";

        if (!isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT u.id AS user_id, a.id AS application_id, a.fio, a.phone, a.email, a.birthdate, a.gender, a.biography, a.contract_accepted 
                                 FROM users u JOIN application a ON u.application_id = a.id WHERE u.login = ?");
            $stmt->execute([$login]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['user_id'] = $user_data['user_id'];
            $_SESSION['application_id'] = $user_data['application_id'];
            $_SESSION['fio'] = $user_data['fio'];
            $_SESSION['phone'] = $user_data['phone'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['birthdate'] = $user_data['birthdate'];
            $_SESSION['gender'] = $user_data['gender'];
            $_SESSION['biography'] = $user_data['biography'];
            $_SESSION['contract_accepted'] = $user_data['contract_accepted'];
            $stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
            $stmt->execute([$_SESSION['application_id']]);
            $_SESSION['languages'] = serialize($stmt->fetchAll(PDO::FETCH_COLUMN));
        } else {
            $_SESSION['fio'] = $_POST['fio'];
            $_SESSION['phone'] = $_POST['phone'];
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['birthdate'] = $_POST['birthdate'];
            $_SESSION['gender'] = $_POST['gender'];
            $_SESSION['biography'] = $_POST['biography'];
            $_SESSION['contract_accepted'] = 1;
            $_SESSION['languages'] = serialize($_POST['languages']);
        }

        setcookie('form[fio]', '', time() - 3600, '/');
        setcookie('form[phone]', '', time() - 3600, '/');
        setcookie('form[email]', '', time() - 3600, '/');
        setcookie('form[birthdate]', '', time() - 3600, '/');
        setcookie('form[gender]', '', time() - 3600, '/');
        setcookie('form[languages]', '', time() - 3600, '/');
        setcookie('form[biography]', '', time() - 3600, '/');
        setcookie('form[contract_accepted]', '', time() - 3600, '/');
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        setcookie('errors', serialize($errors), time() + 3600, '/');
        header('Location: index.php');
        exit();
    }
}
