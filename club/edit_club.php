<?php
/*
 * edit_club.php
 * Allows a Club Admin or Super Admin to:
 * - Edit the club's name and description.
 * - Delete the club.
 */

// 1. CONFIG & SESSION
require_once '../config.php';
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
    $_SESSION['error_message'] = "You do not have permission to edit this club.";
    header('Location: view_club.php?id=' . $club_id);
    exit;
}

// 5. HANDLE POST ACTIONS (Update Details or Delete Club)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_details') {
            // Server-Side Validation (Req #7)
            $club_name = trim($_POST['club_name'] ?? '');
            $club_description = trim($_POST['club_description'] ?? '');

            if (empty($club_name) || empty($club_description)) {
                $errors[] = "Club Name and Description cannot be empty.";
            } else {
                // PHP for Updating Records (Req #2)
                $stmt = $pdo->prepare("UPDATE clubs SET club_name = ?, club_description = ? WHERE club_id = ?");
                $stmt->execute([$club_name, $club_description, $club_id]);
                
                // Refresh club data to show updated info
                $club['club_name'] = $club_name;
                $club['club_description'] = $club_description;
                
                $messages[] = "Club details updated successfully.";
            }
        } 
        elseif ($action === 'delete_club') {
            // PHP for Removing Records (Req #2)
            // The database is set up with ON DELETE CASCADE,
            // so deleting the club will also delete all:
            // - memberships
            // - posts
            // - events
            $stmt = $pdo->prepare("DELETE FROM clubs WHERE club_id = ?");
            $stmt->execute([$club_id]);
            
            // Redirect to browse page as this club no longer exists
            $_SESSION['message'] = "Club '" . htmlspecialchars($club['club_name']) . "' has been permanently deleted.";
            header('Location: browse_clubs.php');
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// 6. HTML VIEW
require_once '../template/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Edit Club Settings</h1>

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

        <!-- Edit Details Form -->
        <form action="edit_club.php?club_id=<?php echo $club_id; ?>" method="POST">
            <input type="hidden" name="action" value="update_details">
            <div class="form-group">
                <label for="club_name">Club Name</label>
                <input type="text" id="club_name" name="club_name" value="<?php echo htmlspecialchars($club['club_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="club_description">Club Description</label>
                <textarea id="club_description" name="club_description" rows="5" required><?php echo htmlspecialchars($club['club_description']); ?></textarea>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
        
        <hr class="form-divider">

        <!-- Delete Club Form -->
        <div class="danger-zone">
            <h3>Danger Zone</h3>
            <p>Deleting your club is permanent and cannot be undone. All posts, events, and member data will be lost.</p>
            <form action="edit_club.php?club_id=<?php echo $club_id; ?>" method="POST">
                <input type="hidden" name="action" value="delete_club">
                <button type="submit" class="btn btn-danger" onclick="return confirm('ARE YOU SURE?\n\nThis will permanently delete your club and all its content.');">
                    Delete This Club
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
