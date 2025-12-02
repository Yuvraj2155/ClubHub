<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$club_id = $_GET['id'] ?? null;
$errors = [];

if (!$club_id) {
    header("Location: ../dashboard.php");
    exit;
}

// --- Check Permissions ---
try {
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    if (!$club) {
        header("Location: ../dashboard.php");
        exit;
    }

    $is_owner = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');

    if (!$is_owner && !$is_super_admin) {
        header("Location: ../dashboard.php?error=no_permission");
        exit;
    }
} catch (PDOException $e) {
    $errors[] = $e->getMessage();
}

// --- Handle Deletion ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM clubs WHERE club_id = ?");
        $stmt->execute([$club_id]);
        
        header("Location: ../dashboard.php?msg=club_deleted");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Deletion failed: " . $e->getMessage();
    }
}

include '../template/header.php';
?>

<div class="content-box form-container">
    <h2 style="color: var(--danger-color);">Delete Club</h2>
    <p>You are about to delete <strong><?php echo htmlspecialchars($club['club_name']); ?></strong>.</p>
    <a href="edit_club.php?id=<?php echo $club_id; ?>">&laquo; Cancel</a>
    <hr>

    <div class="alert alert-danger">
        <strong>This action cannot be undone.</strong>
        <p>Deleting this club will permanently remove:</p>
        <ul style="text-align: left;">
            <li>The club page.</li>
            <li>All <?php echo htmlspecialchars($club['club_name']); ?> memberships.</li>
            <li>All posts and events within the club.</li>
        </ul>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors[0]); ?></div>
    <?php endif; ?>

    <form action="delete_club.php?id=<?php echo $club_id; ?>" method="POST">
        <div class="form-group">
            <label>Type "DELETE" to confirm:</label>
            <input type="text" name="confirmation" class="form-control" required pattern="DELETE" placeholder="DELETE">
        </div>
        <button type="submit" name="confirm_delete" class="btn btn-danger">Permanently Delete Club</button>
    </form>
</div>

<?php include '../template/footer.php'; ?>