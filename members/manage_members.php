<?php
require_once '../config.php';

// Require user to be logged in
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
        header("Location: ../club/browse_clubs.php");
        exit;
    }

    $is_club_admin = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');

    if (!$is_club_admin && !$is_super_admin) {
        header("Location: ../club/view_club.php?id=" . $club_id);
        exit;
    }

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Handle Form Submissions (Update Permissions / Kick Member)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_user_id = $_POST['member_user_id'] ?? null;
    
    // Check if we are kicking the club creator (don't allow)
    if ($member_user_id == $club['creator_id_fk']) {
        $errors[] = "You cannot manage the club's creator.";
    } else {
        try {
            if (isset($_POST['toggle_post_permission'])) {
                // Toggle the 'can_post' value
                $new_permission = (int)($_POST['current_permission'] == '1' ? 0 : 1);
                $stmt = $pdo->prepare("UPDATE club_memberships SET can_post = ? WHERE user_id_fk = ? AND club_id_fk = ?");
                $stmt->execute([$new_permission, $member_user_id, $club_id]);
                $messages[] = "Member permissions updated.";
            } elseif (isset($_POST['kick_member'])) {
                // Remove member from club
                $stmt = $pdo->prepare("DELETE FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
                $stmt->execute([$member_user_id, $club_id]);
                $messages[] = "Member has been removed from the club.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error managing member: " . $e->getMessage();
        }
    }
}

// --- Fetch Member List ---
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.email, m.can_post, m.join_date
        FROM users u
        JOIN club_memberships m ON u.user_id = m.user_id_fk
        WHERE m.club_id_fk = ?
        ORDER BY u.username
    ");
    $stmt->execute([$club_id]);
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "Error fetching members: " . $e->getMessage();
}


include '../template/header.php';
?>

<div class="content-box">
    <h2>Manage Members for "<?php echo htmlspecialchars($club['club_name']); ?>"</h2>
    <p>Grant or revoke posting permissions, or remove members from your club.</p>
    <a href="../club/view_club.php?id=<?php echo $club_id; ?>">&laquo; Back to Club Page</a>
    <hr>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($messages)): ?>
        <div class="alert alert-success">
            <?php foreach ($messages as $message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <ul class="member-list">
        <?php foreach ($members as $member): ?>
            <li class="member-list-item">
                <div class="member-info">
                    <?php echo htmlspecialchars($member['username']); ?>
                    (<?php echo htmlspecialchars($member['email']); ?>)
                    <?php if ($member['user_id'] == $club['creator_id_fk']): ?>
                        <strong>(Club Admin)</strong>
                    <?php endif; ?>
                </div>
                
                <div class="member-actions">
                    <?php if ($member['user_id'] != $club['creator_id_fk']): // Can't manage the creator ?>
                        <form action="manage_members.php?id=<?php echo $club_id; ?>" method="POST" style="display:inline-block;">
                            <input type="hidden" name="member_user_id" value="<?php echo $member['user_id']; ?>">
                            <input type="hidden" name="current_permission" value="<?php echo $member['can_post']; ?>">
                            <button type="submit" name="toggle_post_permission" class="btn btn-secondary">
                                <?php echo $member['can_post'] ? 'Revoke Post Permission' : 'Grant Post Permission'; ?>
                            </button>
                        </form>
                        
                        <form action="manage_members.php?id=<?php echo $club_id; ?>" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to kick this member?');">
                            <input type="hidden" name="member_user_id" value="<?php echo $member['user_id']; ?>">
                            <button type="submit" name="kick_member" class="btn btn-danger">Kick</button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php include '../template/footer.php'; ?>