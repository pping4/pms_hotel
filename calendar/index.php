<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Calendar View';

// Get current month/year from query params or default to current
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

// Ensure valid month/year
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = new DateTime("$year-$month-01");
$lastDay = new DateTime($firstDay->format('Y-m-t'));
$startDayOfWeek = (int)$firstDay->format('w'); // 0 = Sunday
$daysInMonth = (int)$firstDay->format('t');

// Get all rooms
$stmt = $pdo->query("SELECT r.*, rt.name as type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number");
$rooms = $stmt->fetchAll();

// Get bookings for this month
$stmt = $pdo->prepare("SELECT b.*, g.first_name, g.last_name 
                       FROM bookings b 
                       LEFT JOIN guests g ON b.guest_id = g.id 
                       WHERE b.status IN ('confirmed', 'checked_in') 
                       AND b.check_in_date <= ? AND b.check_out_date >= ?");
$stmt->execute([$lastDay->format('Y-m-d'), $firstDay->format('Y-m-d')]);
$bookings = $stmt->fetchAll();

// Get contracts for this month
$stmt = $pdo->prepare("SELECT c.*, g.first_name, g.last_name 
                       FROM contracts c 
                       LEFT JOIN guests g ON c.guest_id = g.id 
                       WHERE c.status = 'active' 
                       AND c.start_date <= ? AND c.end_date >= ?");
$stmt->execute([$lastDay->format('Y-m-d'), $firstDay->format('Y-m-d')]);
$contracts = $stmt->fetchAll();

// Build occupancy map: room_id => array of dates
$occupancy = [];
foreach ($bookings as $b) {
    if (!$b['room_id']) continue;
    $start = max(new DateTime($b['check_in_date']), $firstDay);
    $end = min(new DateTime($b['check_out_date']), $lastDay);
    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $occupancy[$b['room_id']][$d->format('Y-m-d')] = [
            'type' => 'booking',
            'guest' => $b['first_name'] . ' ' . $b['last_name'],
            'id' => $b['id']
        ];
    }
}

foreach ($contracts as $c) {
    if (!$c['room_id']) continue;
    $start = max(new DateTime($c['start_date']), $firstDay);
    $end = min(new DateTime($c['end_date']), $lastDay);
    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $occupancy[$c['room_id']][$d->format('Y-m-d')] = [
            'type' => 'contract',
            'guest' => $c['first_name'] . ' ' . $c['last_name'],
            'id' => $c['id']
        ];
    }
}

// Navigation
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: 100px repeat(<?php echo $daysInMonth; ?>, minmax(30px, 1fr));
    gap: 1px;
    background: var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.calendar-cell {
    background: white;
    padding: 8px 4px;
    text-align: center;
    font-size: 0.75rem;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.calendar-header {
    background: #f8fafc;
    font-weight: 600;
}
.room-label {
    background: var(--bg-sidebar);
    color: white;
    font-weight: 500;
    text-align: left;
    padding-left: 10px;
}
.cell-booking {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    cursor: pointer;
}
.cell-contract {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    cursor: pointer;
}
.cell-available {
    background: #f0fdf4;
}
.today {
    border: 2px solid var(--primary);
}
.calendar-legend {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}
.legend-box {
    width: 20px;
    height: 20px;
    border-radius: 4px;
}
</style>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-calendar-alt"></i> Calendar View</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/calendar/?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span style="padding: 10px 20px; font-weight: 600; font-size: 1.1rem;">
                <?php echo date('F Y', strtotime("$year-$month-01")); ?>
            </span>
            <a href="/pms_hotel/calendar/?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-right"></i>
            </a>
            <a href="/pms_hotel/calendar/" class="btn btn-primary">Today</a>
        </div>
    </div>

    <div class="calendar-legend">
        <div class="legend-item">
            <div class="legend-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"></div>
            <span>Booking</span>
        </div>
        <div class="legend-item">
            <div class="legend-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"></div>
            <span>Contract</span>
        </div>
        <div class="legend-item">
            <div class="legend-box" style="background: #f0fdf4; border: 1px solid #dcfce7;"></div>
            <span>Available</span>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow-x: auto;">
        <div class="calendar-grid">
            <!-- Header Row: Days -->
            <div class="calendar-cell calendar-header">Room</div>
            <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                $date = new DateTime("$year-$month-$d");
                $isToday = $date->format('Y-m-d') === date('Y-m-d');
                $dayName = $date->format('D');
            ?>
                <div class="calendar-cell calendar-header <?php echo $isToday ? 'today' : ''; ?>">
                    <?php echo $d; ?><br>
                    <small style="color: var(--text-muted);"><?php echo $dayName; ?></small>
                </div>
            <?php endfor; ?>

            <!-- Room Rows -->
            <?php foreach ($rooms as $room): ?>
                <div class="calendar-cell room-label">
                    <?php echo htmlspecialchars($room['room_number']); ?>
                </div>
                <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $isToday = $dateStr === date('Y-m-d');
                    $occ = $occupancy[$room['id']][$dateStr] ?? null;
                    
                    if ($occ) {
                        $cellClass = $occ['type'] === 'booking' ? 'cell-booking' : 'cell-contract';
                        $link = $occ['type'] === 'booking' 
                            ? "/pms_hotel/bookings/view.php?id={$occ['id']}"
                            : "/pms_hotel/contracts/view.php?id={$occ['id']}";
                        $title = $occ['guest'];
                    } else {
                        $cellClass = 'cell-available';
                        $link = '';
                        $title = '';
                    }
                ?>
                    <div class="calendar-cell <?php echo $cellClass; ?> <?php echo $isToday ? 'today' : ''; ?>"
                         title="<?php echo htmlspecialchars($title); ?>"
                         <?php if ($link): ?>onclick="window.location='<?php echo $link; ?>'"<?php endif; ?>>
                        <?php if ($occ): ?>
                            <i class="fas fa-<?php echo $occ['type'] === 'booking' ? 'user' : 'file-contract'; ?>" style="font-size: 0.7rem;"></i>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($rooms) == 0): ?>
        <div class="alert alert-danger" style="margin-top: 20px;">
            <i class="fas fa-exclamation-triangle"></i>
            No rooms found. Please <a href="/pms_hotel/rooms/create.php">add rooms</a> first.
        </div>
    <?php endif; ?>
</main>

<?php require '../includes/footer.php'; ?>
