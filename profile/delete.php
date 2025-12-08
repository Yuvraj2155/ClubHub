<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    try {
        // DELETE USER
        // Because your database uses ON DELETE CASCADE constraints on foreign keys:
        // 1. All clubs owned by this user will be deleted.
        // 2. All posts/events created by this user will be deleted.
        // 3. All memberships for this user will be deleted.
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Destroy session and logout
        session_destroy();
        header("Location: ../login.php?msg=account_deleted");
        exit;

    } catch (PDOException $e) {
        $errors[] = "Deletion failed: " . $e->getMessage();
    }
}

include '../template/header.php';
?>

<div class="content-box form-container">
    <h2 style="color: var(--error-color);">Delete Account</h2>
    <a href="index.php">&laquo; Cancel and Go Back</a>
    <hr>

    <div class="alert alert-danger">
        <strong>WARNING: This action is permanent!</strong>
        <p>Deleting your account will immediately remove:</p>
        <ul style="text-align: left;">
            <li>Your login access.</li>
            <li>All Clubs you created.</li>
            <li>All Posts and Events you created.</li>
            <li>All your memberships in other clubs.</li>
        </ul>
        <p>If you want to keep a Club active, please transfer ownership to another member before deleting your account.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="delete.php" method="POST">
        <div class="form-group">
            <label for="password_confirm">To confirm, type "DELETE"</label>
            <input type="text" name="confirmation" class="form-control" placeholder="DELETE" required pattern="DELETE">
        </div>
        <button type="submit" name="confirm_delete" class="btn btn-danger">Permanently Delete My Account</button>
    </form>
</div>

<?php include '../template/footer.php'; ?>