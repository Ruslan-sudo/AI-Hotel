<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('dashboard.php');
}

// Добавление номера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type, price_per_night, capacity, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['room_number'], $_POST['type'], $_POST['price'], $_POST['capacity'], $_POST['description']]);
}

// Удаление номера
if (isset($_GET['delete_room'])) {
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['delete_room']]);
}

// Блокировка/разблокировка пользователя
if (isset($_GET['block_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    $stmt->execute([$_GET['block_user']]);
}

if (isset($_GET['unblock_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
    $stmt->execute([$_GET['unblock_user']]);
}

$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$users = $pdo->query("SELECT * FROM users WHERE role = 'user'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav">
            <h2>Админ-панель</h2>
            <div class="nav-links">
                <a href="dashboard.php">На главную</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>

        <h2>Добавить номер</h2>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="room_number" placeholder="Номер комнаты" required>
            </div>
            <div class="form-group">
                <input type="text" name="type" placeholder="Тип (Стандарт, Люкс)" required>
            </div>
            <div class="form-group">
                <input type="number" step="0.01" name="price" placeholder="Цена за ночь" required>
            </div>
            <div class="form-group">
                <input type="number" name="capacity" placeholder="Вместимость" required>
            </div>
            <div class="form-group">
                <textarea name="description" placeholder="Описание"></textarea>
            </div>
            <button type="submit" name="add_room">Добавить номер</button>
        </form>

        <h2>Управление номерами</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Номер</th><th>Тип</th><th>Цена</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $room): ?>
                    <tr>
                        <td><?php echo $room['id']; ?></td>
                        <td><?php echo $room['room_number']; ?></td>
                        <td><?php echo $room['type']; ?></td>
                        <td><?php echo $room['price_per_night']; ?></td>
                        <td><a href="?delete_room=<?php echo $room['id']; ?>" class="btn-danger" onclick="return confirm('Удалить номер?')">Удалить</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Управление пользователями</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Имя</th><th>Email</th><th>Статус</th><th>Действия</th></tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['is_blocked'] ? 'Заблокирован' : 'Активен'; ?></td>
                        <td>
                            <?php if($user['is_blocked']): ?>
                                <a href="?unblock_user=<?php echo $user['id']; ?>">Разблокировать</a>
                            <?php else: ?>
                                <a href="?block_user=<?php echo $user['id']; ?>" onclick="return confirm('Блокировать пользователя?')">Заблокировать</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>