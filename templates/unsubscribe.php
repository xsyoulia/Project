<?php
session_start();
require '../config.php'; // Обновленный путь

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$subscribed_to = $_POST['subscribed_to'];

	// Удаляем подписку
	$stmt = $pdo->prepare('DELETE FROM subscriptions WHERE follower_id = :follower_id AND following_id = :following_id');
	$stmt->execute([
		'follower_id' => $_SESSION['user_id'],
		'following_id' => $subscribed_to
	]);

	// Получаем только те посты, которые не скрыты (is_hidden = 0)
	$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id = :user_id AND is_hidden = 0');
	$stmt->execute(['user_id' => $profile_user_id]);
	$posts = $stmt->fetchAll();

	// Перенаправляем обратно на профиль
	header('Location: profile.php');
	exit();
}
