<?php
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

require_once '../config.php';

// Получаем ID пользователей, на которых подписан текущий пользователь
$stmt = $pdo->prepare('SELECT following_id FROM subscriptions WHERE follower_id = :user_id');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Добавляем текущего пользователя к подпискам для показа его постов
$subscriptions[] = $_SESSION['user_id'];

// Проверяем, есть ли подписки или собственные посты
if (!empty($subscriptions)) {
	$in = str_repeat('?,', count($subscriptions) - 1) . '?';
	// Запрос для выборки постов
	$stmt = $pdo->prepare("
SELECT * FROM posts 
WHERE user_id IN (" . implode(',', array_fill(0, count($subscriptions), '?')) . ")
AND (
		visibility = 0 -- публичные посты
		OR (visibility = 1 AND user_id IN (" . implode(',', array_fill(0, count($subscriptions), '?')) . ")) -- приватные посты для подписчиков
		OR (visibility = 2 AND user_id = ?) -- скрытые посты только для самого пользователя
)
ORDER BY created_at DESC
");

	// Выполнение запроса с правильными параметрами
	$stmt->execute(array_merge($subscriptions, $subscriptions, [$_SESSION['user_id']]));

	$posts = $stmt->fetchAll();
} else {
	$posts = [];
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Посты подписанных пользователей</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Посты пользователей, на которых вы подписаны</h2>

	<?php if (!empty($posts)): ?>
		<ul>
			<?php foreach ($posts as $post): ?>
				<li>
					<h3><?php echo htmlspecialchars($post['title']); ?></h3>
					<p><?php echo htmlspecialchars($post['content']); ?></p>
					<p><small>Опубликовано: <?php echo htmlspecialchars($post['created_at']); ?></small></p>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>У вас еще нет подписок или у подписанных пользователей нет постов.</p>
	<?php endif; ?>

	<p><a href="profile.php">Назад в профиль</a></p>
</body>

</html>