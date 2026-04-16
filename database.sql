CREATE DATABASE hotel_booking;
USE hotel_booking;

-- Таблица пользователей
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_blocked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица номеров
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    is_available TINYINT(1) DEFAULT 1
);

-- Таблица бронирований
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Вставка тестовых данных
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@hotel.com', '$2y$10$YourHashedPasswordHere', 'admin');

-- Примеры номеров
INSERT INTO rooms (room_number, type, price_per_night, capacity, description) VALUES
('101', 'Стандарт', 3000.00, 2, 'Уютный номер с кондиционером'),
('102', 'Стандарт+', 4000.00, 2, 'Вид на город, завтрак включен'),
('201', 'Люкс', 8000.00, 4, 'Просторный люкс с джакузи');