<?php
// Подключаем сессию и проверяем авторизацию
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

require_once '../config.php';

// Получаем посты текущего пользователя (включая скрытые, так как это его посты)
$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Ваши посты</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Ваши посты</h2>

	<?php if (!empty($posts)): ?>
		<ul>
			<?php foreach ($posts as $post): ?>
				<li>
					<h3><?php echo htmlspecialchars($post['title']); ?></h3>
					<p><?php echo htmlspecialchars($post['content']); ?></p>
					<p><small>Опубликовано: <?php echo htmlspecialchars($post['created_at']); ?></small></p>
					<a href="edit_post.php?id=<?php echo $post['id']; ?>">Редактировать</a> |
					<a href="../actions/delete_post.php?id=<?php echo $post['id']; ?>"
						onclick="return confirm('Вы уверены, что хотите удалить этот пост?');">Удалить</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>У вас еще нет постов.</p>
	<?php endif; ?>

	<p><a href="profile.php">Назад в профиль</a></p>
</body>

</html>

<?php
// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

// Проверяем, передан ли параметр 'id' в URL
if (isset($_GET['id'])) {
	$post_id = $_GET['id'];

	// Получаем пост по его ID
	$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
	$stmt->execute(['id' => $post_id]);
	$post = $stmt->fetch();

	// Проверяем, найден ли пост и является ли текущий пользователь его автором
	if (!$post || ($post['is_hidden'] == 1 && $post['user_id'] != $_SESSION['user_id'])) {
		echo "Пост не найден или у вас нет прав на его просмотр.";
		exit();
	}
} else {
	echo "ID поста не передан.";
	exit();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($post['title']); ?></title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>

	<h2><?php echo htmlspecialchars($post['title']); ?></h2>
	<p><?php echo htmlspecialchars($post['content']); ?></p>
	<p><small>Дата публикации: <?php echo htmlspecialchars($post['created_at']); ?></small></p>

	<!-- Отображаем комментарии к посту -->
	<h4>Комментарии:</h4>
	<ul>
		<?php
		$stmt = $pdo->prepare('
			SELECT comments.comment, comments.created_at, users.username 
			FROM comments 
			JOIN users ON comments.user_id = users.id 
			WHERE post_id = :post_id 
			ORDER BY comments.created_at ASC
		');
		$stmt->execute(['post_id' => $post['id']]);
		$comments = $stmt->fetchAll();

		foreach ($comments as $comment): ?>
			<li>
				<strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
				<p><?php echo htmlspecialchars($comment['comment']); ?></p>
				<small>Дата: <?php echo htmlspecialchars($comment['created_at']); ?></small>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- Форма для добавления нового комментария -->
	<form action="../actions/add_comment.php" method="POST">
		<input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
		<textarea name="comment" placeholder="Оставьте комментарий" required></textarea>
		<button type="submit">Добавить комментарий</button>
	</form>

	<p><a href="subscribed_posts.php">Назад к постам</a></p>

</body>

</html>