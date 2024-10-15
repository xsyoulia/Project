<?php
// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключаем сессию и проверяем авторизацию
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}


require_once '../config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = $_POST['title'];
	$content = $_POST['content'];
	$tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];

	// Проверяем видимость поста
	if (isset($_POST['is_hidden']) && $_POST['is_hidden'] == 'on') {
		$visibility = 2; // Скрытый пост
	} elseif (isset($_POST['is_private']) && $_POST['is_private'] == 'on') {
		$visibility = 1; // Приватный пост
	} else {
		$visibility = 0; // Публичный пост
	}

	// SQL запрос для добавления поста с учетом нового поля visibility
	$stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content, visibility, created_at) VALUES (:user_id, :title, :content, :visibility, NOW())');
	$result = $stmt->execute([
		'user_id' => $_SESSION['user_id'],
		'title' => $title,
		'content' => $content,
		'visibility' => $visibility
	]);

	if ($result) {
		$post_id = $pdo->lastInsertId();
		$tagsAdded = false;  // Флаг для проверки добавления тегов

		// Обработка тегов
		foreach ($tags as $tag_name) {
			$tag_name = trim($tag_name);
			if (empty($tag_name))
				continue; // Пропустить пустые теги

			// Проверяем, существует ли тег
			$stmt = $pdo->prepare('SELECT id FROM tags WHERE name = :name');
			$stmt->execute(['name' => $tag_name]);
			$tag = $stmt->fetch();

			if (!$tag) {
				// Если тег не найден, добавляем новый тег
				$stmt = $pdo->prepare('INSERT INTO tags (name) VALUES (:name)');
				$stmt->execute(['name' => $tag_name]);
				$tag_id = $pdo->lastInsertId();
			} else {
				$tag_id = $tag['id'];
			}

			// Привязываем тег к посту
			$stmt = $pdo->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)');
			$stmt->execute(['post_id' => $post_id, 'tag_id' => $tag_id]);

			$tagsAdded = true;  // Устанавливаем флаг, если добавлены теги
		}

		if ($tagsAdded) {
			echo "Пост успешно добавлен с тегами!";
		} else {
			echo "Пост успешно добавлен без тегов.";
		}
	} else {
		echo "Ошибка при добавлении поста.";
	}
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Добавить пост</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Добавить пост</h2>

	<?php if (!empty($error)): ?>
		<p style="color: red;"><?php echo $error; ?></p>
	<?php endif; ?>

	<form action="add_post.php" method="POST">
		<input type="text" name="title" placeholder="Заголовок" required>
		<textarea name="content" placeholder="Содержимое" required></textarea>
		<input type="text" name="tags" placeholder="Теги (через запятую)">
		<label for="is_private">
			<input type="checkbox" id="is_private" name="is_private"> Приватный пост
		</label>
		<label for="is_hidden">
			<input type="checkbox" id="is_hidden" name="is_hidden"> Скрытый пост
		</label>
		<button type="submit">Добавить пост</button>
	</form>

	<p><a href="profile.php">Назад в профиль</a></p>
</body>

</html>