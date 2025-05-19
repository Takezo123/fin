<?php
require_once 'connect.php';
require_once 'helpers.php';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) $errors[] = 'Введите логин и пароль';

    if (!$errors) {
        
        if ($login === 'admin' && $password === 'restaurant') {
            $_SESSION['is_admin'] = true;
            header('Location: admin_panel.php');
            exit;
        } else {
            $errors[] = 'Неверный логин или пароль администратора';
        }
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Логин администратора</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
</form>
<?php include 'footer.php'; ?>
