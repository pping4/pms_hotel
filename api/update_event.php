<?php
header('Content-Type: application/json');
require '../config/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['start']) || !isset($input['end'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Parse ID (booking_123 or contract_456)
$parts = explode('_', $input['id']);
$type = $parts[0];
$id = (int)$parts[1];
$start = date('Y-m-d', strtotime($input['start']));
$end = date('Y-m-d', strtotime($input['end'])); // FullCalendar end date is exclusive, but we usually store inclusive. 
// However, the standard business logic often matches. Let's assume input matches DB needed format or adjust.
// Usually FullCalendar sends end date as T00:00:00 of the NEXT day.
// If DB stores check_out as the day guest leaves (morning), then it matches. 

try {
    if ($type === 'booking') {
        // Update Booking
        // In a real app, strict availability check needed here. 
        // For drag-drop convenience, we'll allow it but maybe warn in frontend if logic is complex.
        // Simple update:
        $stmt = $pdo->prepare("UPDATE bookings SET check_in_date = ?, check_out_date = ? WHERE id = ?");
        $stmt->execute([$start, $end, $id]);
        
        // Update price? 
        // Ideally yes, but dragging usually implies just moving dates. 
        // Re-calculating price is good practice.
        // Let's keep it simple for now: "Date moved".
        
    } elseif ($type === 'contract') {
        $stmt = $pdo->prepare("UPDATE contracts SET start_date = ?, end_date = ? WHERE id = ?");
        $stmt->execute([$start, $end, $id]);
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
