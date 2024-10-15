<?php
// Подключаем файл config.php для работы с базой данных
require_once '../config.php';

// Проверяем, был ли отправлен POST-запрос для регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	// Получаем данные из формы
	$username = trim($_POST['username']);
	$email = trim($_POST['email']);
	$password = trim($_POST['password']);
	$confirm_password = trim($_POST['confirm_password']);

	// Массив для хранения ошибок
	$errors = [];

	// Проверка: заполнены ли все поля
	if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
		$errors[] = 'Пожалуйста, заполните все поля';
	}

	// Проверка на соответствие паролей
	if ($password !== $confirm_password) {
		$errors[] = 'Пароли не совпадают';
	}

	// Проверка длины пароля
	if (strlen($password) < 6) {
		$errors[] = 'Пароль должен содержать минимум 6 символов';
	}

	// Проверка на уникальность пользователя и email
	$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :email');
	$stmt->execute(['username' => $username, 'email' => $email]);
	$user = $stmt->fetch();

	if ($user) {
		if ($user['username'] === $username) {
			$errors[] = 'Такой пользователь уже существует';
		}
		if ($user['email'] === $email) {
			$errors[] = 'Этот email уже зарегистрирован';
		}
	}

	// Если нет ошибок, регистрируем пользователя
	if (empty($errors)) {
		// Хешируем пароль для безопасного хранения
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);

		// Добавляем нового пользователя в базу данных
		$stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
		$stmt->execute([
			'username' => $username,
			'email' => $email,
			'password' => $hashed_password
		]);

		// Перенаправляем на страницу входа
		header('Location: ../templates/login.php');
		exit();
	}
}
?>

<!-- HTML форма для регистрации -->
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Регистрация</title>
	<link rel="stylesheet" href="../assets/css/styles.css"> <!-- подключаем стили -->
</head>

<body>
	<h2>Регистрация</h2>

	<?php
	// Если есть ошибки, выводим их
	if (!empty($errors)) {
		foreach ($errors as $error) {
			echo '<p style="color:red;">' . $error . '</p>';
		}
	}
	?>

	<form action="register.php" method="POST">
		<label for="username">Логин:</label>
		<input type="text" id="username" name="username" required>

		<label for="email">Email:</label>
		<input type="email" id="email" name="email" required>

		<label for="password">Пароль:</label>
		<input type="password" id="password" name="password" required>

		<label for="confirm_password">Подтвердите пароль:</label>
		<input type="password" id="confirm_password" name="confirm_password" required>

		<button type="submit">Зарегистрироваться</button>
	</form>
</body>

</html>