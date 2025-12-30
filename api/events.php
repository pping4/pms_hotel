<?php
header('Content-Type: application/json');
require '../config/db.php';

// Get start and end dates from FullCalendar (ISO8601)
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

try {
    // 1. Fetch Bookings
    $stmt = $pdo->prepare("SELECT b.id, b.room_id, b.check_in_date as start, b.check_out_date as end, b.total_price, b.status,
                           g.first_name, g.last_name, g.phone, r.room_number 
                           FROM bookings b 
                           LEFT JOIN guests g ON b.guest_id = g.id 
                           LEFT JOIN rooms r ON b.room_id = r.id 
                           WHERE b.status IN ('confirmed', 'checked_in', 'checked_out') 
                           AND b.check_out_date >= ? AND b.check_in_date <= ?");
    $stmt->execute([$start, $end]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($bookings as $b) {
        $events[] = [
            'id' => 'booking_' . $b['id'],
            'resourceId' => $b['room_id'],
            'title' => $b['first_name'] . ' ' . $b['last_name'], // Shorter title for timeline
            'start' => $b['start'],
            'end' => $b['end'],
            'backgroundColor' => '#22c55e',
            'borderColor' => '#16a34a',
            'url' => '/pms_hotel/bookings/view.php?id=' . $b['id'],
            'extendedProps' => [
                'type' => 'booking',
                'guest' => $b['first_name'] . ' ' . $b['last_name'],
                'phone' => $b['phone'],
                'price' => number_format($b['total_price'], 2),
                'status' => ucfirst($b['status']),
                'room' => $b['room_number']
            ]
        ];
    }

    // 2. Fetch Contracts
    $stmt = $pdo->prepare("SELECT c.id, c.room_id, c.start_date as start, c.end_date as end, c.total_amount, c.description,
                           g.first_name, g.last_name, r.room_number 
                           FROM contracts c 
                           LEFT JOIN guests g ON c.guest_id = g.id 
                           LEFT JOIN rooms r ON c.room_id = r.id 
                           WHERE c.status = 'active'
                           AND c.end_date >= ? AND c.start_date <= ?");
    $stmt->execute([$start, $end]);
    $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($contracts as $c) {
        $events[] = [
            'id' => 'contract_' . $c['id'],
            'resourceId' => $c['room_id'], 
            'title' => 'Contract: ' . $c['first_name'],
            'start' => $c['start'],
            'end' => $c['end'],
            'backgroundColor' => '#f59e0b',
            'borderColor' => '#d97706',
            'url' => '/pms_hotel/contracts/view.php?id=' . $c['id'],
            'extendedProps' => [
                'type' => 'contract',
                'guest' => $c['first_name'] . ' ' . $c['last_name'],
                'price' => number_format($c['total_amount'], 2),
                'desc' => $c['description'],
                'room' => $c['room_number']
            ]
        ];
    }

    echo json_encode($events);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
