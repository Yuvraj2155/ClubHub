<?php
/*
 * browse_clubs.php
 * Displays a list of all clubs.
 * - Super Admins see links to delete clubs.
 * - All users can see this page.
 */

// 1. CONFIG & SESSION
require_once '../config.php';

// Check for admin action (e.g., deleting a club)
// This is a Super Admin action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['club_id'])) {
    // Check if user is logged in AND is a superadmin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
        $errors[] = "You do not have permission to delete clubs.";
    } else {
        // Data Sanitization (Req #6) - Prepared Statement
        try {
            $stmt = $pdo->prepare("DELETE FROM clubs WHERE club_id = ?");
            $stmt->execute([$_GET['club_id']]);
            $messages[] = "Club successfully deleted.";
        } catch (PDOException $e) {
            $errors[] = "Error deleting club: " . $e->getMessage();
        }
    }
}

// 2. FETCH DATA
// PHP for Reading Records (Req #2)
// Fetch all clubs and join with users table to get creator's username
$stmt = $pdo->query(
    "SELECT clubs.*, users.username AS creator_name 
     FROM clubs 
     JOIN users ON clubs.creator_id_fk = users.user_id
     ORDER BY clubs.created_at DESC"
);
$clubs = $stmt->fetchAll();

// 3. HTML VIEW
require_once '../template/header.php';
?>

<div class="dashboard-card">
    <h1>Browse All Clubs</h1>
    <p>Find a community or create your own.</p>

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

    <div class="club-list">
        <?php if (empty($clubs)): ?>
            <p>No clubs have been created yet. <a href="create_club.php">Be the first!</a></p>
        <?php else: ?>
            <?php foreach ($clubs as $club): ?>
                <div class="club-card">
                    <div class="club-card-body">
                        <h3 class="club-card-title"><?php echo htmlspecialchars($club['club_name']); ?></h3>
                        <p class="club-card-creator">Created by: <?php echo htmlspecialchars($club['creator_name']); ?></p>
                        <p><?php echo htmlspecialchars($club['club_description']); ?></p>
                    </div>
                    <div class="club-card-footer">
                        <a href="view_club.php?id=<?php echo $club['club_id']; ?>" class="btn-small">View Club</a>
                        
                        <?php
                        // Show Admin delete link if:
                        // 1. User is logged in
                        // 2. User's role is 'superadmin'
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'):
                        ?>
                            <a href="browse_clubs.php?action=delete&club_id=<?php echo $club['club_id']; ?>" 
                               class="btn-small btn-danger" 
                               onclick="return confirm('Are you sure you want to permanently delete this club? This action cannot be undone.');">
                               Delete (Admin)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
