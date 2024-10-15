<?php
// Подключаем файл config.php для работы с базой данных
require_once '../config.php';

// Стартуем сессию
session_start();

// Проверяем, был ли отправлен POST-запрос для входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Получаем данные из формы
	$email = trim($_POST['email']);
	$password = trim($_POST['password']);

	// Массив для хранения ошибок
	$errors = [];

	// Проверка: заполнены ли поля
	if (empty($email) || empty($password)) {
		$errors[] = 'Пожалуйста, введите email и пароль';
	}

	// Если нет ошибок, проверяем учетные данные
	if (empty($errors)) {
		// Находим пользователя по email
		$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
		$stmt->execute(['email' => $email]);
		$user = $stmt->fetch();

		// Если пользователь найден и пароль совпадает
		if ($user && password_verify($password, $user['password'])) {
			// Сохраняем данные пользователя в сессию
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['username'] = $user['username'];

			// Перенаправляем на главную страницу или в профиль
			header('Location: ../templates/profile.php');
			exit();
		} else {
			// Если данные неверны, выводим ошибку
			$errors[] = 'Неверный email или пароль';
		}
	}
}
?>

<!-- HTML форма для входа -->
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Вход</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Вход</h2>

	<?php
	// Если есть ошибки, выводим их
	if (!empty($errors)) {
		foreach ($errors as $error) {
			echo '<p style="color:red;">' . $error . '</p>';
		}
	}
	?>

	<form action="login.php" method="POST">
		<label for="email">Email:</label>
		<input type="email" id="email" name="email" required>

		<label for="password">Пароль:</label>
		<input type="password" id="password" name="password" required>

		<button type="submit">Войти</button>
	</form>

	<p>Нет аккаунта? <a href="../actions/register.php">Зарегистрироваться</a></p>
</body>

</html>