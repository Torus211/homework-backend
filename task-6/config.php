<?php
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'u68649';
    $username = 'u68649';
    $password = '9841747';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Ошибка подключения: " . $e->getMessage());
    }
}

function validateAdmin($login, $password) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT password_hash FROM admin WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    return $admin && password_verify($password, $admin['password_hash']);
}
?>
