<?php
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: ../login.php');
	exit();
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$post_id = $_POST['post_id'];
	$comment = $_POST['comment'];
	$user_id = $_SESSION['user_id'];

	// Вставляем комментарий в базу данных
	$stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())');
	$stmt->execute([$post_id, $user_id, $comment]);

	// Перенаправляем на профиль
	header('Location: ../templates/profile.php?user_id=' . $_SESSION['user_id']);
	exit();
} else {
	echo "Неверный запрос.";
}
