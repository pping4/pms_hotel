<?php
header('Content-Type: application/json');
require '../config/db.php';

try {
    // Fetch Rooms as Resources
    $stmt = $pdo->query("SELECT r.id, r.room_number as title, rt.name as type_name, r.floor 
                         FROM rooms r 
                         LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                         ORDER BY r.floor, r.room_number");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resources = [];
    foreach ($rooms as $r) {
        $resources[] = [
            'id' => $r['id'], // ID matches resourceId in events
            'title' => 'Room ' . $r['title'],
            'extendedProps' => [
                'type' => $r['type_name'],
                'floor' => $r['floor']
            ]
        ];
    }

    echo json_encode($resources);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
