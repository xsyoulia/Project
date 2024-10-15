<?php
// Настройки подключения к базе данных
$host = 'localhost';  // Хост, где находится сервер базы данных
$db = 'blog'; // Название базы данных
$user = 'root';        // Имя пользователя для доступа к базе данных
$pass = '';            // Пароль для пользователя (оставьте пустым для локальных серверов без пароля)

try {
	// Создаем подключение к базе данных с использованием PDO
	$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	// Обрабатываем ошибку подключения
	die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>