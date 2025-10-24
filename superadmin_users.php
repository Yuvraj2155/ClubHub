<?php
/*
 * superadmin_users.php
 * Super Admin Only Page
 * - View all users in the database.
 * - Change any user's role.
 * - Delete any user.
 */

// 1. CONFIG & SESSION
require_once 'config.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 2. AUTHORIZATION (Req #4: Web Authentication)
// This is the highest level of protection.
if ($user_role !== 'superadmin') {
    // If user is not a super admin, deny access.
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    header('Location: dashboard.php');
    exit;
}

// 3. HANDLE POST ACTIONS (Update Role, Delete User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = (int)($_POST['target_user_id'] ?? 0);
    
    // Prevent super admin from deleting their own account
    if ($target_user_id === $user_id && $action === 'delete_user') {
        $errors[] = "You cannot delete your own account.";
    } 
    // Prevent super admin from demoting themselves
    elseif ($target_user_id === $user_id && $action === 'update_role' && $_POST['new_role'] !== 'superadmin') {
        $errors[] = "You cannot demote your own account.";
    }
    // Proceed with action
    elseif ($target_user_id > 0) {
        try {
            switch ($action) {
                // PHP for Updating Records (Req #2)
                case 'update_role':
                    $new_role = $_POST['new_role'] === 'superadmin' ? 'superadmin' : 'member';
                    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                    $stmt->execute([$new_role, $target_user_id]);
                    $messages[] = "User role updated successfully.";
                    break;
                
                // PHP for Removing Records (Req #2)
                case 'delete_user':
                    // ON DELETE CASCADE in the database will handle removing
                    // all the user's clubs, posts, events, and memberships.
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$target_user_id]);
                    $messages[] = "User has been permanently deleted.";
                    break;
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: ". $e->getMessage();
        }
    } else {
        $errors[] = "Invalid user or action.";
    }
}

// 4. FETCH ALL USERS
// PHP for Reading Records (Req #2)
$stmt = $pdo->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY username");
$all_users = $stmt->fetchAll();

// 5. HTML VIEW
require_once 'header.php';
?>

<div class="dashboard-card">
    <h1>Super Admin: Manage All Users</h1>
    <p>You can manage all registered users on the platform.</p>
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

    <!-- User List -->
    <div class="member-list">
        <!-- Re-using the .member-table style from manage_members.php -->
        <table class="member-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <!-- Role Update Form -->
                            <form action="superadmin_users.php" method="POST" class="role-form">
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="target_user_id" value="<?php echo $user['user_id']; ?>">
                                <select name="new_role" onchange="this.form.submit()">
                                    <option value="member" <?php if ($user['role'] === 'member') echo 'selected'; ?>>
                                        Member
                                    </option>
                                    <option value="superadmin" <?php if ($user['role'] === 'superadmin') echo 'selected'; ?>>
                                        Super Admin
                                    </option>
                                </select>
                            </form>
                        </td>
                        <td class="action-cell">
                            <!-- Delete User Form -->
                            <form action="superadmin_users.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user? This will delete all their clubs, posts, and events.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="target_user_id" value="<?php echo $user['user_id']; ?>">
                                <?php if ($user['user_id'] !== $user_id): // Don't show delete button for self ?>
                                    <button type="submit" class="btn-small btn-danger">Delete User</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// We need a tiny bit of CSS for the role-form
// Adding it here instead of the main .css file
?>
<style>
.role-form {
    margin: 0;
}
.role-form select {
    padding: 0.5rem;
    border-radius: 8px;
    border: 1px solid #ced4da;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
}
</style>

<?php
require_once 'footer.php';
?>
