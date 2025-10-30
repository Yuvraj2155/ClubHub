<?php
/*
 * create_post.php
 * Allows a permitted user to create a new post in a club.
 */

// 1. CONFIG & SESSION
require_once '../config.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 2. GET CLUB ID
if (!isset($_GET['club_id'])) {
    header('Location: ../club/browse_clubs.php');
    exit;
}
$club_id = (int)$_GET['club_id'];

// 3. FETCH CLUB & MEMBERSHIP DATA
$stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
$stmt->execute([$club_id]);
$club = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM club_memberships WHERE club_id_fk = ? AND user_id_fk = ?");
$stmt->execute([$club_id, $user_id]);
$membership = $stmt->fetch();

if (!$club || !$membership) {
    $_SESSION['error_message'] = "You are not a member of this club.";
    header('Location: view_club.php?id=' . $club_id);
    exit;
}

// 4. AUTHORIZATION (Req #4: Web Authentication & Custom Rule)
$is_club_admin = ($club['creator_id_fk'] == $user_id);
$is_super_admin = ($user_role == 'superadmin');
$has_post_permission = ($membership['can_post'] == 1);

if (!$is_club_admin && !$is_super_admin && !$has_post_permission) {
    // If user is not an admin AND does not have post permission, deny access.
    $_SESSION['error_message'] = "You do not have permission to post in this club.";
    header('Location: ../club/view_club.php?id=' . $club_id);
    exit;
}

// 5. HANDLE FORM SUBMISSION (Create Post)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-Side Validation (Req #7)
    $post_title = trim($_POST['post_title'] ?? '');
    $post_content = trim($_POST['post_content'] ?? '');

    if (empty($post_title) || empty($post_content)) {
        $errors[] = "Post Title and Content cannot be empty.";
    } else {
        try {
            // Data Sanitization (Req #6: Prepared Statements)
            // PHP for Adding Records (Req #2)
            $stmt = $pdo->prepare(
                "INSERT INTO posts (title, content, user_id_fk, club_id_fk) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$post_title, $post_content, $user_id, $club_id]);

            // Redirect back to the club page to see the new post
            $_SESSION['message'] = "New post created successfully!";
            header('Location: ../club/view_club.php?id=' . $club_id);
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// 6. HTML VIEW
require_once '../template/header.php';
?>

<!-- Re-using the auth-wrapper and auth-card for styling -->
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Create New Post</h1>
        <p>Posting in: <strong><?php echo htmlspecialchars($club['club_name']); ?></strong></p>

        <?php
        // Display any errors
        if (!empty($errors)) {
            echo '<ul class="message-box errors">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
        ?>

        <form action="create_post.php?club_id=<?php echo $club_id; ?>" method="POST">
            <div class="form-group">
                <label for="post_title">Post Title</label>
                <input type="text" id="post_title" name="post_title" required>
            </div>
            <div class="form-group">
                <label for="post_content">Content</label>
                <textarea id="post_content" name="post_content" rows="8" required></textarea>
            </div>
            <button type="submit" class="btn">Create Post</button>
        </form>
        <a href="view_club.php?id=<?php echo $club_id; ?>" class="auth-link">Cancel</a>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
