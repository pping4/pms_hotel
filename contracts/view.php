<?php
require '../includes/auth_check.php';
require '../config/db.php';

// Get contract ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /pms_hotel/contracts/');
    exit;
}

// Fetch contract
$stmt = $pdo->prepare("SELECT c.*, g.first_name, g.last_name, g.email, g.phone, 
                       r.room_number, rt.name as room_type, rt.base_price
                       FROM contracts c 
                       LEFT JOIN guests g ON c.guest_id = g.id 
                       LEFT JOIN rooms r ON c.room_id = r.id 
                       LEFT JOIN room_types rt ON r.room_type_id = rt.id
                       WHERE c.id = ?");
$stmt->execute([$id]);
$contract = $stmt->fetch();

if (!$contract) {
    header('Location: /pms_hotel/contracts/');
    exit;
}

$pageTitle = 'Contract #' . $contract['id'];

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-file-contract"></i> Contract #<?php echo $contract['id']; ?></h1>
        <div class="topbar-right">
            <?php if ($contract['status'] === 'active'): ?>
                <a href="/pms_hotel/contracts/edit.php?id=<?php echo $contract['id']; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="/pms_hotel/contracts/?action=terminate&id=<?php echo $contract['id']; ?>" 
                   class="btn btn-danger"
                   data-confirm="Are you sure you want to terminate this contract?">
                    <i class="fas fa-times"></i> Terminate
                </a>
            <?php endif; ?>
            <a href="/pms_hotel/contracts/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Contract Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Contract Information</h2>
                <?php
                $badgeClass = match($contract['status']) {
                    'active' => 'badge-success',
                    'expired' => 'badge-secondary',
                    'terminated' => 'badge-danger',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 1rem;">
                    <?php echo ucfirst($contract['status']); ?>
                </span>
            </div>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Room</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <?php if ($contract['room_number']): ?>
                            <strong><?php echo htmlspecialchars($contract['room_number']); ?></strong>
                            (<?php echo htmlspecialchars($contract['room_type']); ?>)
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Start Date</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <strong><?php echo date('D, d M Y', strtotime($contract['start_date'])); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">End Date</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <strong><?php echo date('D, d M Y', strtotime($contract['end_date'])); ?></strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; color: var(--text-muted);">Duration</td>
                    <td style="padding: 12px 0; text-align: right;">
                        <?php 
                        $start = new DateTime($contract['start_date']);
                        $end = new DateTime($contract['end_date']);
                        $days = $start->diff($end)->days;
                        $months = floor($days / 30);
                        $remainingDays = $days % 30;
                        echo $months > 0 ? $months . ' month(s) ' : '';
                        echo $remainingDays > 0 ? $remainingDays . ' day(s)' : '';
                        ?>
                    </td>
                </tr>
                <tr style="background: #f8fafc;">
                    <td style="padding: 12px 0; font-weight: bold;">Total Amount</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: bold; font-size: 1.25rem; color: var(--primary);">
                        <?php echo number_format($contract['total_amount'], 2); ?> ฿
                    </td>
                </tr>
            </table>
        </div>

        <!-- Guest Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Guest Information</h2>
            </div>
            <?php if ($contract['guest_id']): ?>
                <div style="text-align: center; margin-bottom: 20px;">
                    <div class="user-avatar" style="width: 60px; height: 60px; font-size: 1.5rem; margin: 0 auto;">
                        <?php echo strtoupper(substr($contract['first_name'], 0, 1) . substr($contract['last_name'], 0, 1)); ?>
                    </div>
                    <h3 style="margin-top: 10px;">
                        <?php echo htmlspecialchars($contract['first_name'] . ' ' . $contract['last_name']); ?>
                    </h3>
                </div>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 10px 0; color: var(--text-muted);"><i class="fas fa-envelope"></i> Email</td>
                        <td style="padding: 10px 0; text-align: right;">
                            <?php echo htmlspecialchars($contract['email'] ?? '-'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: var(--text-muted);"><i class="fas fa-phone"></i> Phone</td>
                        <td style="padding: 10px 0; text-align: right;">
                            <?php echo htmlspecialchars($contract['phone'] ?? '-'); ?>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 20px;">
                    <a href="/pms_hotel/guests/view.php?id=<?php echo $contract['guest_id']; ?>" 
                       class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-user"></i> View Guest Profile
                    </a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted);">No guest assigned</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description -->
    <?php if ($contract['description']): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Description / Terms</h2>
        </div>
        <p style="white-space: pre-wrap;"><?php echo htmlspecialchars($contract['description']); ?></p>
    </div>
    <?php endif; ?>
</main>

<?php require '../includes/footer.php'; ?>
