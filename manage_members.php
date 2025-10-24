<?php
/*
 * manage_members.php
 * Allows a Club Admin or Super Admin to:
 * - View all members of their club.
 * - Grant/Revoke posting permissions.
 * - Kick (remove) members from the club.
 */

// 1. CONFIG & SESSION
require_once 'config.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 2. GET CLUB ID
if (!isset($_GET['club_id'])) {
    header('Location: browse_clubs.php');
    exit;
}
$club_id = (int)$_GET['club_id'];

// 3. FETCH CLUB & PERMISSION DATA
$stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
$stmt->execute([$club_id]);
$club = $stmt->fetch();

if (!$club) {
    header('Location: browse_clubs.php');
    exit;
}

// 4. AUTHORIZATION (Req #4: Web Authentication)
$is_club_admin = ($club['creator_id_fk'] == $user_id);
$is_super_admin = ($user_role == 'superadmin');

if (!$is_club_admin && !$is_super_admin) {
    // If user is not the club admin or a super admin, deny access.
    $_SESSION['error_message'] = "You do not have permission to manage this club.";
    header('Location: view_club.php?id=' . $club_id);
    exit;
}

// 5. HANDLE POST ACTIONS (Grant, Revoke, Kick)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $member_id = (int)($_POST['member_id'] ?? 0);

    // Check if the member is actually in this club (security check)
    $stmt = $pdo->prepare("SELECT * FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
    $stmt->execute([$member_id, $club_id]);
    $membership = $stmt->fetch();

    if ($member_id > 0 && $membership) {
        try {
            switch ($action) {
                // PHP for Updating Records (Req #2)
                case 'grant_permission':
                    $stmt = $pdo->prepare("UPDATE club_memberships SET can_post = 1 WHERE user_id_fk = ? AND club_id_fk = ?");
                    $stmt->execute([$member_id, $club_id]);
                    $messages[] = "Posting permission granted.";
                    break;

                case 'revoke_permission':
                    $stmt = $pdo->prepare("UPDATE club_memberships SET can_post = 0 WHERE user_id_fk = ? AND club_id_fk = ?");
                    $stmt->execute([$member_id, $club_id]);
                    $messages[] = "Posting permission revoked.";
                    break;
                
                // PHP for Removing Records (Req #2)
                case 'kick_member':
                    // Club admin cannot kick themselves
                    if ($member_id == $club['creator_id_fk']) {
                        $errors[] = "You cannot kick the club creator.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
                        $stmt->execute([$member_id, $club_id]);
                        $messages[] = "Member has been removed from the club.";
                    }
                    break;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    } else {
        $errors[] = "Invalid member or action.";
    }
}


// 6. FETCH ALL MEMBERS
// PHP for Reading Records (Req #2)
$stmt = $pdo->prepare(
    "SELECT u.user_id, u.username, m.can_post, m.join_date 
     FROM users u
     JOIN club_memberships m ON u.user_id = m.user_id_fk
     WHERE m.club_id_fk = ?
     ORDER BY u.username"
);
$stmt->execute([$club_id]);
$members = $stmt->fetchAll();

// 7. HTML VIEW
require_once 'header.php';
?>

<div class="dashboard-card">
    <h1>Manage Members: <?php echo htmlspecialchars($club['club_name']); ?></h1>
    <p>As the admin, you can grant/revoke posting permissions or remove members from your club.</p>
    <a href="view_club.php?id=<?php echo $club_id; ?>">&larr; Back to Club</a>

    <?php
    // Display any errors or success messages
    if (!empty($errors)) {
        echo '<ul class="message-box errors">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }
    if (!empty($messages)) {
        echo '<div class="message-box success">' . htmlspecialchars($messages[0]) . '</div>';
    }
    ?>

    <!-- Member List -->
    <div class="member-list">
        <table class="member-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Can Post?</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                            <?php if ($member['user_id'] == $club['creator_id_fk']) echo " (Admin)"; ?>
                        </td>
                        <td>
                            <?php if ($member['can_post']): ?>
                                <span class="status-yes">Yes</span>
                            <?php else: ?>
                                <span class="status-no">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-cell">
                            <!-- Admins can't edit their own permissions or kick themselves -->
                            <?php if ($member['user_id'] != $club['creator_id_fk']): ?>
                                <form action="manage_members.php?club_id=<?php echo $club_id; ?>" method="POST">
                                    <input type="hidden" name="member_id" value="<?php echo $member['user_id']; ?>">
                                    <?php if ($member['can_post']): ?>
                                        <input type="hidden" name="action" value="revoke_permission">
                                        <button type="submit" class="btn-small btn-warning">Revoke</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="grant_permission">
                                        <button type="submit" class="btn-small">Grant</button>
                                    <?php endif; ?>
                                    
                                    <input type="hidden" name="action" value="kick_member">
                                    <button type="submit" name="action" value="kick_member" class="btn-small btn-danger" onclick="return confirm('Are you sure you want to kick this member?');">Kick</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
?>
