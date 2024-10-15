<form action="../actions/search_by_tag.php" method="GET">
	<label for="tag">Поиск постов по тегу:</label>
	<input type="text" name="tag" id="tag" placeholder="Введите тег">
	<button type="submit">Найти</button>
</form>

<?php
// Подключаем сессию и проверяем авторизацию
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

// Подключаем конфигурационный файл базы данных
require_once '../config.php';

// Получаем ID пользователя, чей профиль мы просматриваем
$profile_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];

// Получаем данные пользователя из базы данных
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $profile_user_id]);
$user = $stmt->fetch();

if (!$user) {
	echo "Пользователь не найден.";
	exit();
}

// Проверка, просматривает ли пользователь свой профиль или чужой
$is_own_profile = $profile_user_id == $_SESSION['user_id'];

// Получаем посты пользователя: скрытые и видимые
if ($is_own_profile) {
	$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id = :user_id');
} else {
	$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id = :user_id AND is_hidden = 0');
}

$stmt->execute(['user_id' => $profile_user_id]);
$posts = $stmt->fetchAll();




// Обрабатываем подписку или отписку на пользователя до вывода HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribed_to'], $_POST['action'])) {
	$subscribed_to = $_POST['subscribed_to'];
	$action = $_POST['action'];

	if ($action === 'subscribe') {
		// Проверяем, подписан ли пользователь уже
		$stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE follower_id = :follower_id AND following_id = :following_id');
		$stmt->execute([
			'follower_id' => $_SESSION['user_id'],
			'following_id' => $subscribed_to
		]);

		if ($stmt->rowCount() === 0) {
			// Подписка не существует, добавляем её
			$stmt = $pdo->prepare('INSERT INTO subscriptions (follower_id, following_id) VALUES (:follower_id, :following_id)');
			$stmt->execute([
				'follower_id' => $_SESSION['user_id'],
				'following_id' => $subscribed_to
			]);
		}
	} elseif ($action === 'unsubscribe') {
		// Удаляем подписку
		$stmt = $pdo->prepare('DELETE FROM subscriptions WHERE follower_id = :follower_id AND following_id = :following_id');
		$stmt->execute([
			'follower_id' => $_SESSION['user_id'],
			'following_id' => $subscribed_to
		]);
	}

	// Перенаправляем обратно на страницу профиля
	header('Location: profile.php?user_id=' . $profile_user_id);
	exit();
}

// Получаем ID пользователя, чей профиль мы просматриваем
$profile_user_id = isset($_GET['user_id']) ? $_GET['user_id'] : $_SESSION['user_id'];

// Получаем данные пользователя из базы данных
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $profile_user_id]);
$user = $stmt->fetch();

if (!$user) {
	echo "Пользователь не найден.";
	exit();
}

// Проверка, просматривает ли пользователь свой профиль или чужой
$is_own_profile = $profile_user_id == $_SESSION['user_id'];

// Получаем посты пользователя
// и посты пользователей, на которых подписан текущий пользователь
$stmt = $pdo->prepare('
    SELECT posts.* 
    FROM posts 
    LEFT JOIN subscriptions ON posts.user_id = subscriptions.following_id 
    WHERE (posts.user_id = :profile_user_id OR subscriptions.follower_id = :current_user_id) 
    AND (posts.visibility = 0 OR (posts.visibility = 1 AND subscriptions.follower_id IS NOT NULL))
    ORDER BY posts.created_at DESC
');
$stmt->execute([
	'profile_user_id' => $profile_user_id,
	'current_user_id' => $_SESSION['user_id']
]);
$posts = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Профиль пользователя</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Профиль пользователя: <?php echo htmlspecialchars($user['username']); ?></h2>
	<p>Email: <?php echo htmlspecialchars($user['email']); ?></p>

	<?php if ($is_own_profile): ?>
		<h3>Ваши действия:</h3>
		<ul>
			<li><a href="add_post.php">Добавить пост</a></li>
			<li><a href="view_posts.php">Просмотреть ваши посты</a></li>
			<li><a href="/blog/actions/logout.php">Выйти</a></li>
		</ul>
	<?php endif; ?>

	<h3>Стена постов:</h3>
	<?php if (empty($posts)): ?>
		<p>Нет постов для отображения.</p>
	<?php else: ?>
		<?php foreach ($posts as $post): ?>
			<div class="post">
				<h2><?php echo htmlspecialchars($post['title']); ?></h2>
				<p><?php echo htmlspecialchars($post['content']); ?></p>
				<?php if ($post['visibility'] == 2): ?>
					<span>(Скрытый пост)</span>
				<?php elseif ($post['visibility'] == 1): ?>
					<span>(Приватный пост(В дальнейшем для VIP-подписчиков))</span>
				<?php endif; ?>

				<!-- Комментарии к посту -->
				<h4>Комментарии:</h4>
				<ul>
					<?php
					// Получение комментариев к данному посту
					$stmt = $pdo->prepare('
					SELECT comments.content, comments.created_at, users.username 
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
							<p><?php echo htmlspecialchars($comment['content']); ?></p>
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
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

</html>
<?php
// Получаем всех пользователей, кроме текущего
$stmt = $pdo->prepare('SELECT * FROM users WHERE id != :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$users = $stmt->fetchAll();

foreach ($users as $user):
	// Проверяем, подписан ли текущий пользователь на данного пользователя
	$stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE follower_id = :follower_id AND following_id = :following_id');
	$stmt->execute([
		'follower_id' => $_SESSION['user_id'],
		'following_id' => $user['id']
	]);
	$is_subscribed = $stmt->rowCount() > 0;
	?>
	<li>
		<?php echo htmlspecialchars($user['username']); ?>
		<form action="profile.php?user_id=<?php echo $profile_user_id; ?>" method="POST" style="display:inline;">
			<input type="hidden" name="subscribed_to" value="<?php echo $user['id']; ?>">
			<input type="hidden" name="action" value="<?php echo $is_subscribed ? 'unsubscribe' : 'subscribe'; ?>">
			<button type="submit"><?php echo $is_subscribed ? 'Отписаться' : 'Подписаться'; ?></button>
		</form>
	</li>
<?php endforeach; ?>
</ul>


</body>

</html>