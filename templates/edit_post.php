<?php
// Подключаем сессию и проверяем авторизацию
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

require_once '../config.php';

// Получаем ID поста для редактирования
$post_id = $_GET['id'];

// Получаем пост для редактирования
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $post_id, 'user_id' => $_SESSION['user_id']]);
$post = $stmt->fetch();

// Если пост не найден или не принадлежит текущему пользователю
if (!$post) {
	echo "Пост не найден или у вас нет прав на его редактирование.";
	exit();
}

// Обрабатываем отправку формы для обновления поста
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = trim($_POST['title']);
	$content = trim($_POST['content']);

	// Проверяем видимость поста
	if (isset($_POST['is_hidden']) && $_POST['is_hidden'] == 'on') {
		$visibility = 2; // Скрытый пост
	} elseif (isset($_POST['is_private']) && $_POST['is_private'] == 'on') {
		$visibility = 1; // Приватный пост
	} else {
		$visibility = 0; // Публичный пост
	}

	// Проверяем, заполнены ли поля
	if (empty($title) || empty($content)) {
		$error = 'Пожалуйста, заполните все поля.';
	} else {
		// Обновляем пост в базе данных
		$stmt = $pdo->prepare('UPDATE posts SET title = :title, content = :content, visibility = :visibility WHERE id = :id AND user_id = :user_id');
		$stmt->execute([
			'title' => $title,
			'content' => $content,
			'visibility' => $visibility,
			'id' => $post_id,
			'user_id' => $_SESSION['user_id']
		]);

		// Перенаправляем на страницу просмотра постов
		header('Location: view_posts.php');
		exit();
	}
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Редактировать пост</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Редактировать пост</h2>

	<?php if (!empty($error)): ?>
		<p style="color: red;"><?php echo $error; ?></p>
	<?php endif; ?>

	<form action="edit_post.php?id=<?php echo $post_id; ?>" method="POST">
		<label for="title">Заголовок поста:</label>
		<input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

		<label for="content">Содержание поста:</label>
		<textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>

		<label for="is_private">
			<input type="checkbox" id="is_private" name="is_private" <?php echo $post['visibility'] == 1 ? 'checked' : ''; ?>>
			Приватный пост
		</label>

		<label for="is_hidden">
			<input type="checkbox" id="is_hidden" name="is_hidden" <?php echo $post['visibility'] == 2 ? 'checked' : ''; ?>>
			Скрытый пост
		</label>

		<button type="submit">Сохранить изменения</button>
	</form>

	<p><a href="view_posts.php">Назад к постам</a></p>
</body>

</html>