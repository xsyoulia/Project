<?php
session_start();
session_destroy(); // Завершаем сессию
header('Location: ../templates/login.php'); // Перенаправляем на страницу входа
exit();
?>