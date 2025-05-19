<?php
require_once 'connect.php';
require_once 'helpers.php';
$title = 'Вход';
include 'header.php';

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (mb_strlen($login) < 4) $errors[] = 'Введите логин';
    if (mb_strlen($password) < 6) $errors[] = 'Введите пароль';

    if (!$errors) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $login;
            header('Location: bookings.php');
            exit;
        } else {
            $errors[] = "Неправильный логин или пароль";
        }
        $stmt->close();
    }
}
show_errors($errors);
?>
<form method="POST">
    <div class="mb-3"><label class="form-label">Логин</label><input type="text" class="form-control" name="login" required></div>
    <div class="mb-3"><label class="form-label">Пароль</label><input type="password" class="form-control" name="password" required></div>
    <button type="submit" class="btn btn-primary w-100">Войти</button>
</form>
<?php include 'footer.php'; ?>
