<?php
// Подключение к базе данных
include 'config.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null; // Получаем ID текущего пользователя, если он авторизован

if (isset($_GET['tag_id'])) {
	// Если выбран тег, отображаем только публичные посты или приватные для подписчиков
	$stmt = $pdo->prepare('
		SELECT posts.* 
		FROM posts 
		JOIN post_tags ON posts.id = post_tags.post_id 
		LEFT JOIN subscriptions ON posts.user_id = subscriptions.following_id AND subscriptions.follower_id = :user_id
		WHERE post_tags.tag_id = :tag_id 
		AND (posts.visibility = 0 OR (posts.visibility = 1 AND subscriptions.follower_id IS NOT NULL))
	');
	$stmt->execute([
		'tag_id' => $_GET['tag_id'],
		'user_id' => $user_id
	]);
} else {
	// Главная страница: публичные посты + приватные для подписчиков
	$stmt = $pdo->prepare('
		SELECT posts.* 
		FROM posts 
		LEFT JOIN subscriptions ON posts.user_id = subscriptions.following_id AND subscriptions.follower_id = :user_id
		WHERE (posts.visibility = 0 OR (posts.visibility = 1 AND subscriptions.follower_id IS NOT NULL))
		ORDER BY posts.created_at DESC
	');
	$stmt->execute(['user_id' => $user_id]);
}


// Приветственное сообщение
echo "<h1>Добро пожаловать в блог!</h1>";
echo "<p>Проверка подключения к базе данных.</p>";

// Проверка подключения к базе данных
try {
	$query = $pdo->query("SELECT 'Подключение к базе данных успешно!' AS message");
	$result = $query->fetch();
	echo "<p>" . $result['message'] . "</p>";
} catch (PDOException $e) {
	echo "<p>Ошибка подключения к базе данных: " . $e->getMessage() . "</p>";
}

// Пример ссылки на регистрацию
echo "<p><a href='actions/register.php'>Регистрация</a></p>";
echo "<p><a href='templates/login.php'>Вход</a></p>";

$stmt = $pdo->query('SELECT * FROM tags');
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h3>Фильтрация по тегам</h3>
<ul>
	<?php foreach ($tags as $tag): ?>
		<li><a href="index.php?tag_id=<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></a></li>
	<?php endforeach;

	if (isset($_GET['tag_id'])) {
		$stmt = $pdo->prepare('
		SELECT posts.* FROM posts
		JOIN post_tags ON posts.id = post_tags.post_id
		WHERE post_tags.tag_id = :tag_id AND posts.visibility = 0
	');
		$stmt->execute(['tag_id' => $_GET['tag_id']]);
	} else {
		$stmt = $pdo->prepare('SELECT * FROM posts WHERE visibility = 0 ORDER BY created_at DESC');
		$stmt->execute();
	}
	$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($posts as $post) {
		echo "<h2>" . htmlspecialchars($post['title']) . "</h2>";
		echo "<p>" . htmlspecialchars($post['content']) . "</p>";
	}




	?>


</ul>