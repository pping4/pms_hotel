<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Booking Management';

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'checkin':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
            $stmt->execute([$id]);
            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = (SELECT room_id FROM bookings WHERE id = ?)");
            $stmt->execute([$id]);
            break;
        case 'checkout':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?");
            $stmt->execute([$id]);
            // Update room status to cleaning
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'cleaning' WHERE id = (SELECT room_id FROM bookings WHERE id = ?)");
            $stmt->execute([$id]);
            break;
        case 'cancel':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            // Update room status back to available
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = (SELECT room_id FROM bookings WHERE id = ?)");
            $stmt->execute([$id]);
            break;
    }
    header('Location: /pms_hotel/bookings/?msg=' . $action);
    exit;
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /pms_hotel/bookings/?msg=deleted');
    exit;
}

// Filter by status
$statusFilter = $_GET['status'] ?? '';
$where = '';
$params = [];
if (!empty($statusFilter)) {
    $where = "WHERE b.status = ?";
    $params = [$statusFilter];
}

// Get all bookings
$sql = "SELECT b.*, g.first_name, g.last_name, r.room_number 
        FROM bookings b 
        LEFT JOIN guests g ON b.guest_id = g.id 
        LEFT JOIN rooms r ON b.room_id = r.id 
        $where
        ORDER BY b.check_in_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-book"></i> Booking Management</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/bookings/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Booking
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
            echo match($_GET['msg']) {
                'created' => 'Booking created successfully!',
                'updated' => 'Booking updated successfully!',
                'deleted' => 'Booking deleted successfully!',
                'checkin' => 'Guest checked in successfully!',
                'checkout' => 'Guest checked out successfully!',
                'cancel' => 'Booking cancelled!',
                default => 'Operation completed!'
            };
            ?>
        </div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="/pms_hotel/bookings/" 
               class="btn <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                All
            </a>
            <a href="/pms_hotel/bookings/?status=pending" 
               class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Pending
            </a>
            <a href="/pms_hotel/bookings/?status=confirmed" 
               class="btn <?php echo $statusFilter === 'confirmed' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Confirmed
            </a>
            <a href="/pms_hotel/bookings/?status=checked_in" 
               class="btn <?php echo $statusFilter === 'checked_in' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Checked In
            </a>
            <a href="/pms_hotel/bookings/?status=checked_out" 
               class="btn <?php echo $statusFilter === 'checked_out' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                Checked Out
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Bookings (<?php echo count($bookings); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <a href="/pms_hotel/guests/view.php?id=<?php echo $booking['guest_id']; ?>">
                                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($booking['room_number'] ?? 'N/A'); ?></td>
                                <td><?php echo $booking['check_in_date']; ?></td>
                                <td><?php echo $booking['check_out_date']; ?></td>
                                <td><?php echo number_format($booking['total_price'], 2); ?> ฿</td>
                                <td>
                                    <?php
                                    $badgeClass = match($booking['status']) {
                                        'pending' => 'badge-warning',
                                        'confirmed' => 'badge-info',
                                        'checked_in' => 'badge-success',
                                        'checked_out' => 'badge-secondary',
                                        'cancelled' => 'badge-danger',
                                        default => 'badge-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <a href="/pms_hotel/bookings/?action=checkin&id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-success btn-sm" title="Check-in">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] === 'checked_in'): ?>
                                        <a href="/pms_hotel/bookings/?action=checkout&id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-warning btn-sm" title="Check-out" style="color: white;">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="/pms_hotel/bookings/view.php?id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-secondary btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <a href="/pms_hotel/bookings/edit.php?id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/pms_hotel/bookings/?action=cancel&id=<?php echo $booking['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           data-confirm="Are you sure you want to cancel this booking?"
                                           title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                <i class="fas fa-calendar-alt" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                No bookings found. <a href="/pms_hotel/bookings/create.php">Create your first booking</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
