<?php
require '../includes/auth_check.php';
require '../config/db.php';

$pageTitle = 'Add Guest';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_card_passport = trim($_POST['id_card_passport'] ?? '');

    // Validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, id_card_passport) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email ?: null, $phone ?: null, $id_card_passport ?: null]);
        header('Location: /pms_hotel/guests/?msg=created');
        exit;
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="topbar">
        <h1><i class="fas fa-user-plus"></i> Add Guest</h1>
        <div class="topbar-right">
            <a href="/pms_hotel/guests/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo implode('<br>', $errors); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Guest Information</h2>
        </div>
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                           placeholder="Enter first name" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name *</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                           placeholder="Enter last name" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="example@email.com">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                           placeholder="e.g., 081-234-5678">
                </div>

                <div class="form-group">
                    <label class="form-label" for="id_card_passport">ID Card / Passport</label>
                    <input type="text" class="form-control" id="id_card_passport" name="id_card_passport" 
                           value="<?php echo htmlspecialchars($_POST['id_card_passport'] ?? ''); ?>" 
                           placeholder="Enter ID or passport number">
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Guest
                </button>
                <a href="/pms_hotel/guests/" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<?php require '../includes/footer.php'; ?>
