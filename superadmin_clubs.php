<?php
/*
 * superadmin_clubs.php
 * Super Admin Only Page
 * - View all clubs in the database.
 * - Delete any club.
 */

// 1. CONFIG & SESSION
require_once 'config.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 2. AUTHORIZATION (Req #4: Web Authentication)
if ($user_role !== 'superadmin') {
    // If user is not a super admin, deny access.
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    header('Location: dashboard.php');
    exit;
}

// 3. HANDLE POST ACTIONS (Delete Club)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_club_id = (int)($_POST['target_club_id'] ?? 0);

    if ($action === 'delete_club' && $target_club_id > 0) {
        try {
            // PHP for Removing Records (Req #2)
            // ON DELETE CASCADE in the database will handle removing
            // all memberships, posts, and events associated with this club.
            $stmt = $pdo->prepare("DELETE FROM clubs WHERE club_id = ?");
            $stmt->execute([$target_club_id]);
            $messages[] = "Club has been permanently deleted.";
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    } else {
        $errors[] = "Invalid club or action.";
    }
}

// 4. FETCH ALL CLUBS (with creator username)
// PHP for Reading Records (Req #2)
$stmt = $pdo->query(
    "SELECT c.club_id, c.club_name, c.created_at, u.username AS creator_name
     FROM clubs c
     JOIN users u ON c.creator_id_fk = u.user_id
     ORDER BY c.club_name"
);
$all_clubs = $stmt->fetchAll();

// 5. HTML VIEW
require_once 'header.php';
?>

<div class="dashboard-card">
    <h1>Super Admin: Manage All Clubs</h1>
    <p>You can manage all clubs on the platform.</p>
    <a href="dashboard.php">&larr; Back to Dashboard</a>

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

    <!-- Club List -->
    <div class="member-list">
        <!-- Re-using the .member-table style -->
        <table class="member-table">
            <thead>
                <tr>
                    <th>Club Name</th>
                    <th>Creator</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_clubs)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">No clubs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_clubs as $club): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($club['club_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($club['creator_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($club['created_at'])); ?></td>
                            <td class="action-cell">
                                <!-- Delete Club Form -->
                                <form action="superadmin_clubs.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this club? This will delete all its members, posts, and events.');">
                                    <input type="hidden" name="action" value="delete_club">
                                    <input type="hidden" name="target_club_id" value="<?php echo $club['club_id']; ?>">
                                    <button type="submit" class="btn-small btn-danger">Delete Club</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
?>
