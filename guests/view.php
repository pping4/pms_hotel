<?php
require '../includes/auth_check.php';
require '../config/db.php';

// Get guest ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/guests/');
    exit;
}

// Fetch guest with stats
$stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
$stmt->execute([$id]);
$guest = $stmt->fetch();

if (!$guest) {
    header('Location: /pms_hotel/guests/');
    exit;
}

$pageTitle = $guest['first_name'] . ' ' . $guest['last_name'];

// Get bookings history
$stmt = $pdo->prepare("SELECT b.*, r.room_number 
                       FROM bookings b 
                       LEFT JOIN rooms r ON b.room_id = r.id 
                       WHERE b.guest_id = ? 
                       ORDER BY b.check_in_date DESC");
$stmt->execute([$id]);
$bookings = $stmt->fetchAll();

// Get contracts
$stmt = $pdo->prepare("SELECT c.*, r.room_number 
                       FROM contracts c 
                       LEFT JOIN rooms r ON c.room_id = r.id 
                       WHERE c.guest_id = ? 
                       ORDER BY c.start_date DESC");
$stmt->execute([$id]);
$contracts = $stmt->fetchAll();

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-user"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <div class="topbar-right">
            <a href="/pms_hotel/guests/edit.php?id=<?php echo $guest['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="/pms_hotel/guests/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <!-- Guest Info Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Guest Information</h2>
            </div>
            <div style="text-align: center; margin-bottom: 20px;">
                <div class="user-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto;">
                    <?php echo strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)); ?>
                </div>
            </div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);">Full Name</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <strong><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);">Email</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo htmlspecialchars($guest['email'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);">Phone</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo htmlspecialchars($guest['phone'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);">ID/Passport</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo htmlspecialchars($guest['id_card_passport'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; color: var(--text-muted);">Member Since</td>
                    <td style="padding: 10px 0; text-align: right;">
                        <?php echo date('d M Y', strtotime($guest['created_at'])); ?>
                    </td>
                </tr>
            </table>
            <div style="margin-top: 20px;">
                <a href="/pms_hotel/bookings/create.php?guest_id=<?php echo $guest['id']; ?>" 
                   class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        </div>

        <!-- History -->
        <div>
            <!-- Bookings -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Booking History (<?php echo count($bookings); ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings) > 0): ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['id']; ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted);">
                                        No booking history
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contracts -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Contracts (<?php echo count($contracts); ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Room</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($contracts) > 0): ?>
                                <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td>#<?php echo $contract['id']; ?></td>
                                        <td><?php echo htmlspecialchars($contract['room_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo $contract['start_date']; ?></td>
                                        <td><?php echo $contract['end_date']; ?></td>
                                        <td><?php echo number_format($contract['total_amount'], 2); ?> ฿</td>
                                        <td>
                                            <?php
                                            $badgeClass = match($contract['status']) {
                                                'active' => 'badge-success',
                                                'expired' => 'badge-secondary',
                                                'terminated' => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo ucfirst($contract['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted);">
                                        No contracts
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
