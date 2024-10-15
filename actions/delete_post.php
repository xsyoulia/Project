<?php
// Подключаем сессию и проверяем авторизацию
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: ../templates/login.php');
	exit();
}

// Подключаем конфигурационный файл базы данных
require_once '../config.php';

// Получаем ID поста
$post_id = $_GET['id'];

// Удаляем пост
$stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $post_id, 'user_id' => $_SESSION['user_id']]);

// Перенаправляем на страницу просмотра постов
header('Location: ../templates/view_posts.php');
exit();
?>