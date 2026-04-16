<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isBlocked($_SESSION['user_id'], $pdo)) {
    session_destroy();
    redirect('login.php');
}

// Получаем доступные номера
$rooms = $pdo->query("SELECT * FROM rooms WHERE is_available = 1")->fetchAll();

// Получаем бронирования пользователя
$stmt = $pdo->prepare("SELECT b.*, r.room_number, r.type FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? ORDER BY b.booking_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель пользователя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav">
            <h2>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <div class="nav-links">
                <?php if(isAdmin()): ?>
                    <a href="admin.php">Админ-панель</a>
                <?php endif; ?>
                <a href="logout.php">Выйти</a>
            </div>
        </div>

        <h2>Доступные номера</h2>
        <div class="rooms-list">
            <?php foreach($rooms as $room): ?>
                <div class="room-card">
                    <h3>Номер <?php echo $room['room_number']; ?> - <?php echo $room['type']; ?></h3>
                    <p>Цена: <?php echo $room['price_per_night']; ?> руб/ночь</p>
                    <p>Вместимость: <?php echo $room['capacity']; ?> чел.</p>
                    <p><?php echo $room['description']; ?></p>
                    <a href="book.php?room_id=<?php echo $room['id']; ?>" class="btn">Забронировать</a>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Мои бронирования</h2>
        <?php if(count($bookings) > 0): ?>
            <table>
                <thead>
                    <tr><th>Номер</th><th>Тип</th><th>Заезд</th><th>Выезд</th><th>Сумма</th><th>Статус</th></tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr>
                            <td><?php echo $booking['room_number']; ?></td>
                            <td><?php echo $booking['type']; ?></td>
                            <td><?php echo $booking['check_in']; ?></td>
                            <td><?php echo $booking['check_out']; ?></td>
                            <td><?php echo $booking['total_price']; ?> руб</td>
                            <td><?php echo $booking['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>У вас пока нет бронирований.</p>
        <?php endif; ?>
    </div>
</body>
</html>