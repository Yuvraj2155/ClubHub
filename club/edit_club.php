<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$club_id = $_GET['id'] ?? null;
$errors = [];
$messages = [];

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

    $is_club_admin = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');

    if (!$is_club_admin && !$is_super_admin) {
        header("Location: view_club.php?id=" . $club_id);
        exit;
    }

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle Update (Name/Description only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_club'])) {
    $club_name = trim($_POST['club_name']);
    $club_description = trim($_POST['club_description']);

    if (empty($club_name)) $errors[] = "Club name cannot be empty.";
    if (empty($club_description)) $errors[] = "Club description cannot be empty.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE clubs SET club_name = ?, club_description = ? WHERE club_id = ?");
            $stmt->execute([$club_name, $club_description, $club_id]);
            $messages[] = "Club updated successfully!";
            
            // Refresh Data
            $stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
            $stmt->execute([$club_id]);
            $club = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = "Error updating club: " . $e->getMessage();
        }
    }
}

include '../template/header.php';
?>

<div class="content-box">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Club Settings: <?php echo htmlspecialchars($club['club_name']); ?></h2>
        <a href="view_club.php?id=<?php echo $club_id; ?>" class="btn btn-secondary" style="width:auto;">&laquo; Back to Club</a>
    </div>
    <hr>

    <?php if (!empty($messages)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($messages[0]); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errors[0]); ?></div>
    <?php endif; ?>

    <!-- 1. Update Details Form -->
    <div style="margin-bottom: 40px;">
        <h3>Update Details</h3>
        <form action="edit_club.php?id=<?php echo $club_id; ?>" method="POST">
            <div class="form-group">
                <label for="club_name">Club Name</label>
                <input type="text" id="club_name" name="club_name" class="form-control" value="<?php echo htmlspecialchars($club['club_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="club_description">Club Description</label>
                <textarea id="club_description" name="club_description" class="form-control" required><?php echo htmlspecialchars($club['club_description']); ?></textarea>
            </div>
            <div class="form-group">
                <button type="submit" name="update_club" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- 2. Management Actions -->
    <div style="margin-bottom: 40px;">
        <h3>Management Actions</h3>
        <div style="display:flex; gap: 15px; flex-wrap:wrap;">
            
            <!-- Manage Members -->
            <a href="../members/manage_members.php?id=<?php echo $club_id; ?>" style="flex:1; min-width:250px; text-decoration:none;">
                <div style="border:1px solid #ccc; padding:20px; border-radius:8px; text-align:center; background:#f9f9f9; color:#333;">
                    <h4 style="margin:0 0 10px 0;">Manage Members</h4>
                    <p style="font-size:0.9rem; color:#666;">Kick members or grant posting permissions.</p>
                </div>
            </a>

            <!-- Transfer Ownership -->
            <a href="transfer_club.php?id=<?php echo $club_id; ?>" style="flex:1; min-width:250px; text-decoration:none;">
                <div style="border:1px solid #ccc; padding:20px; border-radius:8px; text-align:center; background:#f9f9f9; color:#333;">
                    <h4 style="margin:0 0 10px 0;">Transfer Ownership</h4>
                    <p style="font-size:0.9rem; color:#666;">Give full control of this club to another member.</p>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. Danger Zone (Delete) -->
    <div style="background-color: #fff5f5; border: 1px solid #ffcdd2; padding: 20px; border-radius: 8px;">
        <h3 style="color: var(--danger-color); margin-top:0;">Danger Zone</h3>
        <p>Deleting a club is permanent. It will remove all members, posts, and events associated with it.</p>
        <a href="delete_club.php?id=<?php echo $club_id; ?>" class="btn btn-danger" style="width: auto; display:inline-block;">Delete Club</a>
    </div>

</div>

<?php include '../template/footer.php'; ?>