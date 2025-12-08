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

    if (!$club || $club['creator_id_fk'] != $user_id) {
        // Only the actual CREATOR can transfer ownership
        header("Location: ../dashboard.php?error=notowner");
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// --- Handle Transfer ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_owner_id'])) {
    $new_owner_id = $_POST['new_owner_id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Update Club Creator
        $stmt = $pdo->prepare("UPDATE clubs SET creator_id_fk = ? WHERE club_id = ?");
        $stmt->execute([$new_owner_id, $club_id]);

        // 2. Ensure new owner has 'can_post' permission
        $stmt = $pdo->prepare("UPDATE club_memberships SET can_post = 1 WHERE user_id_fk = ? AND club_id_fk = ?");
        $stmt->execute([$new_owner_id, $club_id]);

        $pdo->commit();
        
        // Redirect to dashboard because user is no longer the owner
        header("Location: ../dashboard.php?msg=transferred");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Transfer failed: " . $e->getMessage();
    }
}

// --- Fetch Members ---
try {
    // Get all members EXCEPT current owner
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email 
        FROM users u
        JOIN club_memberships m ON u.user_id = m.user_id_fk
        WHERE m.club_id_fk = ? AND u.user_id != ?
    ");
    $stmt->execute([$club_id, $user_id]);
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = $e->getMessage();
}

include '../template/header.php';
?>

<div class="content-box form-container">
    <h2>Transfer Ownership</h2>
    <p>Transfer <strong><?php echo htmlspecialchars($club['club_name']); ?></strong> to another member.</p>
    <a href="edit_club.php?id=<?php echo $club_id; ?>">&laquo; Cancel</a>
    <hr>

    <div class="alert alert-danger">
        <strong>Warning:</strong> Once you transfer ownership, you will lose administrative control of this club. You cannot undo this action.
    </div>

    <?php if (empty($members)): ?>
        <p>There are no other members in this club to transfer ownership to.</p>
    <?php else: ?>
        <form action="transfer_club.php?id=<?php echo $club_id; ?>" method="POST" onsubmit="return confirm('Are you sure you want to give away this club?');">
            <div class="form-group">
                <label>Select New Owner:</label>
                <select name="new_owner_id" class="form-control" required>
                    <option value="">-- Select a Member --</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['user_id']; ?>">
                            <?php echo htmlspecialchars($member['username']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Transfer Club</button>
        </form>
    <?php endif; ?>
</div>

<?php include '../template/footer.php'; ?>