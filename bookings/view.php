<?php
require '../includes/auth_check.php';
require '../config/db.php';

// Get booking ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/bookings/');
    exit;
}

// Fetch booking
$stmt = $pdo->prepare("SELECT b.*, g.first_name, g.last_name, g.email, g.phone, 
                       r.room_number, rt.name as room_type, rt.base_price
                       FROM bookings b 
                       LEFT JOIN guests g ON b.guest_id = g.id 
                       LEFT JOIN rooms r ON b.room_id = r.id 
                       LEFT JOIN room_types rt ON r.room_type_id = rt.id
                       WHERE b.id = ?");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: /pms_hotel/bookings/');
    exit;
}

$pageTitle = 'Booking #' . $booking['id'];

// Get transactions
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE booking_id = ? ORDER BY transaction_date DESC");
$stmt->execute([$id]);
$transactions = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-book"></i> Booking #<?php echo $booking['id']; ?></h1>
        <div class="topbar-right">
            <?php if ($booking['status'] === 'confirmed'): ?>
                <a href="/pms_hotel/bookings/?action=checkin&id=<?php echo $booking['id']; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-sign-in-alt"></i> Check-in
                </a>
            <?php endif; ?>
            
            <?php if ($booking['status'] === 'checked_in'): ?>
                <a href="/pms_hotel/bookings/?action=checkout&id=<?php echo $booking['id']; ?>" 
                   class="btn btn-warning" style="color: white;">
                    <i class="fas fa-sign-out-alt"></i> Check-out
                </a>
            <?php endif; ?>
            
            <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                <a href="/pms_hotel/bookings/edit.php?id=<?php echo $booking['id']; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
            
            <a href="/pms_hotel/bookings/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Booking Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Booking Information</h2>
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
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 1rem;">
                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                </span>
            </div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Room</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <strong><?php echo htmlspecialchars($booking['room_number']); ?></strong>
                        (<?php echo htmlspecialchars($booking['room_type']); ?>)
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Check-in</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <strong><?php echo date('D, d M Y', strtotime($booking['check_in_date'])); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Check-out</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <strong><?php echo date('D, d M Y', strtotime($booking['check_out_date'])); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Nights</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <?php 
                        $start = new DateTime($booking['check_in_date']);
                        $end = new DateTime($booking['check_out_date']);
                        $nights = $start->diff($end)->days;
                        echo $nights . ' night(s)';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Rate/Night</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <?php echo number_format($booking['base_price'], 2); ?> ฿
                    </td>
                </tr>
                <tr style="background: #f8fafc;">
                    <td style="padding: 12px 0; font-weight: bold;">Total</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: bold; font-size: 1.25rem; color: var(--primary);">
                        <?php echo number_format($booking['total_price'], 2); ?> ฿
                    </td>
                </tr>
            </table>
        </div>

        <!-- Guest Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Guest Information</h2>
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <div class="user-avatar" style="width: 60px; height: 60px; font-size: 1.5rem; margin: 0 auto;">
                    <?php echo strtoupper(substr($booking['first_name'], 0, 1) . substr($booking['last_name'], 0, 1)); ?>
                </div>
                <h3 style="margin-top: 10px;">
                    <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                </h3>
            </div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);"><i class="fas fa-envelope"></i> Email</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo htmlspecialchars($booking['email'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);"><i class="fas fa-phone"></i> Phone</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo htmlspecialchars($booking['phone'] ?? '-'); ?>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 20px;">
                <a href="/pms_hotel/guests/view.php?id=<?php echo $booking['guest_id']; ?>" 
                   class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-user"></i> View Guest Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Payment History</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($txn['transaction_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $txn['type'] === 'refund' ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo ucfirst($txn['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($txn['payment_method'] ?? '-'); ?></td>
                                <td><?php echo number_format($txn['amount'], 2); ?> ฿</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted);">
                                No payment records
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
