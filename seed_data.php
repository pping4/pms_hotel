<?php
require 'config/db.php';

echo "Start seeding demo data...\n";

try {
    // 1. Seed Room Types
    echo "Seeding Room Types...\n";
    $roomTypes = [
        ['name' => 'Standard', 'price' => 1000, 'capacity' => 2, 'desc' => 'Comfortable standard room'],
        ['name' => 'Deluxe', 'price' => 2500, 'capacity' => 3, 'desc' => 'Spacious deluxe room with view'],
        ['name' => 'Suite', 'price' => 5000, 'capacity' => 4, 'desc' => 'Luxury suite with living area'],
    ];

    $roomTypeIds = [];
    foreach ($roomTypes as $rt) {
        $stmt = $pdo->prepare("SELECT id FROM room_types WHERE name = ?");
        $stmt->execute([$rt['name']]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            $stmt = $pdo->prepare("INSERT INTO room_types (name, base_price, capacity, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$rt['name'], $rt['price'], $rt['capacity'], $rt['desc']]);
            $id = $pdo->lastInsertId();
        }
        $roomTypeIds[$rt['name']] = $id;
    }

    // 2. Seed Rooms (12 rooms)
    echo "Seeding Rooms...\n";
    $floors = [1, 2, 3];
    $roomsCreated = 0;
    
    // Clear existing rooms to avoid duplicates for this demo seed
    $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->query("TRUNCATE TABLE rooms");
    $pdo->query("TRUNCATE TABLE bookings");
    $pdo->query("TRUNCATE TABLE contracts");
    $pdo->query("TRUNCATE TABLE guests");
    $pdo->query("SET FOREIGN_KEY_CHECKS = 1");

    $roomNumber = 101;
    foreach ($roomTypes as $rt) {
        for ($i = 0; $i < 4; $i++) {
            $rNum = $roomNumber + $i;
            $floor = floor($rNum / 100);
            $typeId = $roomTypeIds[$rt['name']];
            
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES (?, ?, ?, 'available')");
            $stmt->execute([$rNum, $typeId, $floor]);
            $roomsCreated++;
        }
        $roomNumber += 100; // Next floor/block
    }

    // Get all room IDs
    $stmt = $pdo->query("SELECT r.id, room_number, base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id");
    $allRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // 3. Seed Guests (12 guests)
    echo "Seeding Guests...\n";
    $guestsData = [
        ['Alice', 'Smith', 'alice@example.com', '0811111111'],
        ['Bob', 'Johnson', 'bob@example.com', '0822222222'],
        ['Charlie', 'Brown', 'charlie@example.com', '0833333333'],
        ['David', 'Lee', 'david@example.com', '0844444444'],
        ['Eve', 'Wilson', 'eve@example.com', '0855555555'],
        ['Frank', 'Miller', 'frank@example.com', '0866666666'],
        ['Grace', 'Taylor', 'grace@example.com', '0877777777'],
        ['Henry', 'Anderson', 'henry@example.com', '0888888888'],
        ['Ivy', 'Thomas', 'ivy@example.com', '0899999999'],
        ['Jack', 'Martinez', 'jack@example.com', '0900000000'],
        ['Kevin', 'White', 'kevin@example.com', '0911111111'],
        ['Laura', 'Garcia', 'laura@example.com', '0922222222'],
    ];

    $guestIds = [];
    foreach ($guestsData as $g) {
        $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute($g);
        $guestIds[] = $pdo->lastInsertId();
    }


    // 4. Seed Bookings (12 bookings)
    echo "Seeding Bookings...\n";
    $statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
    $bookingCount = 0;

    foreach ($guestIds as $index => $guestId) {
        // Shuffle rooms to pick one
        $room = $allRooms[$index % count($allRooms)];
        
        // Random dates
        $start = date('Y-m-d', strtotime(rand(-30, 30) . ' days'));
        $days = rand(1, 7);
        $end = date('Y-m-d', strtotime("$start +$days days"));
        
        $price = $room['base_price'] * $days;
        $status = $statuses[array_rand($statuses)];

        // Adjust status based on date
        if ($end < date('Y-m-d')) $status = 'checked_out';
        elseif ($start <= date('Y-m-d') && $end >= date('Y-m-d')) $status = 'checked_in';
        elseif ($start > date('Y-m-d')) $status = array_rand(['confirmed' => 1, 'pending' => 1]);

        $stmt = $pdo->prepare("INSERT INTO bookings (guest_id, room_id, check_in_date, check_out_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$guestId, $room['id'], $start, $end, $price, $status]);
        
        if ($status === 'checked_in') {
            $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$room['id']]);
        }
    }


    // 5. Seed Contracts (12 contracts)
    echo "Seeding Contracts...\n";
    foreach ($guestIds as $index => $guestId) {
        $room = $allRooms[($index + 2) % count($allRooms)]; // Shift room selection
        
        $start = date('Y-m-d', strtotime(rand(-60, -10) . ' days'));
        $months = rand(1, 6);
        $end = date('Y-m-d', strtotime("$start +$months months"));
        
        $total = ($room['base_price'] * 30 * $months) * 0.8; // 20% discount
        $status = ($end < date('Y-m-d')) ? 'expired' : 'active';
        
        // Avoid conflict with occupied rooms if active
        // Simplification: Just insert, might overlap in this dummy seeder
        
        $stmt = $pdo->prepare("INSERT INTO contracts (guest_id, room_id, start_date, end_date, total_amount, status, description) VALUES (?, ?, ?, ?, ?, ?, 'Monthly rental agreement')");
        $stmt->execute([$guestId, $room['id'], $start, $end, $total, $status]);

         if ($status === 'active') {
             // Overwrite room status to occupied if contract is active (priority over booking in this seed logic)
            $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$room['id']]);
        }
    }

    echo "Seed completed successfully!\n";
    echo "Added 12 Rooms, 12 Guests, ~12 Bookings, ~12 Contracts.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
