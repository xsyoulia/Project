<form action="search_by_tag.php" method="GET">
	<input type="text" name="tag" placeholder="Введите тег" required>
	<button type="submit">Поиск</button>
</form>
< <?php
session_start();
require '../config.php';

// Получаем все существующие теги для отображения
$stmt = $pdo->prepare('SELECT * FROM tags');
$stmt->execute();
$all_tags = $stmt->fetchAll();

// Настройки пагинации
$posts_per_page = 5;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($current_page - 1) * $posts_per_page;

if (isset($_GET['tag'])) {
	$tag = trim($_GET['tag']);
	echo htmlspecialchars($tag) . ": "; // Для отладки

	// Находим ID тега
	$stmt = $pdo->prepare('SELECT id FROM tags WHERE name = :name');
	$stmt->execute(['name' => $tag]);
	$tag_data = $stmt->fetch();

	if (!$tag_data) {
		echo "Тег не найден.";
		exit();
	}

	$tag_id = $tag_data['id'];

	// Получаем порядок сортировки
	$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc' ? 'ASC' : 'DESC';

	// Формируем форму для сортировки
	echo '<form action="" method="GET">
					<input type="hidden" name="tag" value="' . htmlspecialchars($tag) . '">
					<select name="sort_order">
							<option value="desc" ' . ($sort_order === 'DESC' ? 'selected' : '') . '>От новых к старым</option>
							<option value="asc" ' . ($sort_order === 'ASC' ? 'selected' : '') . '>От старых к новым</option>
					</select>
					<button type="submit">Сортировать</button>
				</form>';



	// Получаем посты, связанные с этим тегом, исключая скрытые и приватные посты
	$stmt = $pdo->prepare('
    SELECT posts.*, users.username
    FROM posts
    JOIN post_tags ON posts.id = post_tags.post_id
    JOIN users ON posts.user_id = users.id
    LEFT JOIN subscriptions ON posts.user_id = subscriptions.following_id AND subscriptions.follower_id = :current_user_id
    WHERE post_tags.tag_id = :tag_id
    AND (posts.is_hidden = 0 AND (posts.visibility = 0 OR (posts.visibility = 1 AND subscriptions.follower_id IS NOT NULL)))
    ORDER BY posts.created_at ' . $sort_order . '
    LIMIT :limit OFFSET :offset
');

	$stmt->bindValue('tag_id', $tag_id, PDO::PARAM_INT);
	$stmt->bindValue('current_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->bindValue('limit', $posts_per_page, PDO::PARAM_INT);
	$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
	$stmt->execute();
	$posts = $stmt->fetchAll();

	// Обработка результатов
	if (empty($posts)) {
		echo "Нет постов с этим тегом.";
	} else {
		foreach ($posts as $post) {
			echo "<h2>" . htmlspecialchars($post['title']) . "</h2>";
			echo "<p>Автор: " . htmlspecialchars($post['username']) . "</p>"; // Выводим автора
			echo "<p>" . htmlspecialchars($post['content']) . "</p>";
			echo "<small>Дата: " . htmlspecialchars($post['created_at']) . "</small><hr>"; // Вывод даты
		}
	}

	// Получаем общее количество постов с этим тегом для пагинации
	$stmt = $pdo->prepare('
            SELECT COUNT(*) as count
            FROM posts
            JOIN post_tags ON posts.id = post_tags.post_id
            LEFT JOIN subscriptions ON posts.user_id = subscriptions.following_id AND subscriptions.follower_id = :current_user_id
            WHERE post_tags.tag_id = :tag_id
            AND (posts.is_hidden = 0 AND (posts.visibility = 0 OR (posts.visibility = 1 AND subscriptions.follower_id IS NOT NULL)))
        ');

	$stmt->execute(['tag_id' => $tag_id, 'current_user_id' => $_SESSION['user_id']]);
	$total_posts = $stmt->fetchColumn();
	$total_pages = ceil($total_posts / $posts_per_page);

	// Пагинация
	echo "<div class='pagination'>";
	for ($i = 1; $i <= $total_pages; $i++) {
		echo "<a href='?tag=" . urlencode($tag) . "&page=$i'>" . $i . "</a> ";
	}
	echo "</div>";
} else {
	echo "Тег не указан.";
}



// Вывод всех существующих тегов
echo "<h3>Все существующие теги:</h3><ul>";
foreach ($all_tags as $tag) {
	echo "<li>" . htmlspecialchars($tag['name']) . "</li>";
}
echo "</ul>";
?>

	<form action="../templates/profile.php" method="GET">
		<input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
		<button type="submit">Вернуться в профиль</button>
	</form>