<?php
require 'config/db.php';

try {
    // 1. Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'receptionist', 'housekeeper') NOT NULL DEFAULT 'receptionist',
        full_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Room Types
    $pdo->exec("CREATE TABLE IF NOT EXISTS room_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        base_price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        capacity INT DEFAULT 2
    )");

    // 3. Rooms
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_number VARCHAR(20) NOT NULL UNIQUE,
        room_type_id INT,
        status ENUM('available', 'occupied', 'cleaning', 'maintenance') DEFAULT 'available',
        floor VARCHAR(10),
        FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE SET NULL
    )");

    // 4. Guests
    $pdo->exec("CREATE TABLE IF NOT EXISTS guests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        id_card_passport VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Contracts
    $pdo->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_id INT,
        room_id INT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('active', 'expired', 'terminated') DEFAULT 'active',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE SET NULL,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
    )");

    // 6. Bookings
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_id INT NOT NULL,
        room_id INT,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
    )");

    // 7. Transactions
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        amount DECIMAL(10, 2) NOT NULL,
        type ENUM('payment', 'refund', 'deposit') DEFAULT 'payment',
        payment_method VARCHAR(50),
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
    )");

    // 8. Housekeeping Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS housekeeping_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        user_id INT,
        status ENUM('pending', 'completed') DEFAULT 'pending',
        cleaned_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Seed Admin User (Default: admin/admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $passHash = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, role, full_name) VALUES ('admin', '$passHash', 'admin', 'System Admin')";
        $pdo->exec($sql);
        echo "Default admin user created.\n";
    }

    echo "Database setup completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
