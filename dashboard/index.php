<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Dashboard';

// Get statistics
$stats = [];

// Total Rooms
$stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
$stats['total_rooms'] = $stmt->fetchColumn() ?: 0;

// Available Rooms
$stmt = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
$stats['available_rooms'] = $stmt->fetchColumn() ?: 0;

// Active Bookings (checked_in)
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'");
$stats['active_bookings'] = $stmt->fetchColumn() ?: 0;

// Active Contracts
$stmt = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status = 'active'");
$stats['active_contracts'] = $stmt->fetchColumn() ?: 0;

// Total Guests
$stmt = $pdo->query("SELECT COUNT(*) FROM guests");
$stats['total_guests'] = $stmt->fetchColumn() ?: 0;

// Recent Bookings
$stmt = $pdo->query("SELECT b.*, g.first_name, g.last_name, r.room_number 
                     FROM bookings b 
                     LEFT JOIN guests g ON b.guest_id = g.id 
                     LEFT JOIN rooms r ON b.room_id = r.id 
                     ORDER BY b.created_at DESC LIMIT 5");
$recentBookings = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <div class="topbar-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-door-open"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_rooms']; ?></h3>
                <p>Total Rooms</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['available_rooms']; ?></h3>
                <p>Available Rooms</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['active_bookings']; ?></h3>
                <p>Active Check-ins</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-file-contract"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['active_contracts']; ?></h3>
                <p>Active Contracts</p>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> Recent Bookings</h2>
            <a href="/pms_hotel/bookings/" class="btn btn-primary btn-sm">
                <i class="fas fa-list"></i> View All
            </a>
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
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recentBookings) > 0): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['room_number'] ?? '-'); ?></td>
                                <td><?php echo $booking['check_in_date']; ?></td>
                                <td><?php echo $booking['check_out_date']; ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">
                                No bookings yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="/pms_hotel/bookings/create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Booking
            </a>
            <a href="/pms_hotel/guests/create.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Add Guest
            </a>
            <a href="/pms_hotel/rooms/create.php" class="btn btn-secondary">
                <i class="fas fa-door-open"></i> Add Room
            </a>
            <a href="/pms_hotel/contracts/create.php" class="btn btn-warning" style="color: white;">
                <i class="fas fa-file-contract"></i> New Contract
            </a>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
