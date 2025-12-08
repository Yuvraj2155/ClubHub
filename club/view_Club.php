<?php
// We are in the 'club' folder, so we go up one level to find config
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

// Check for success messages from other pages (like create_post.php)
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'postdeleted') $messages[] = "Post successfully deleted.";
    if ($_GET['msg'] == 'eventdeleted') $messages[] = "Event successfully deleted.";
}
if (isset($_GET['error']) && $_GET['error'] == 'nopermission') {
    $errors[] = "You do not have permission to perform that action.";
}

if (!$club_id) {
    header("Location: ../dashboard.php");
    exit;
}

// --- Handle Join / Leave Actions (Still handled here) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['join_club'])) {
            $stmt = $pdo->prepare("INSERT INTO club_memberships (user_id_fk, club_id_fk, can_post) VALUES (?, ?, 0)");
            $stmt->execute([$user_id, $club_id]);
            $messages[] = "Successfully joined the club!";
        } elseif (isset($_POST['leave_club'])) {
            $stmt = $pdo->prepare("DELETE FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
            $stmt->execute([$user_id, $club_id]);
            $messages[] = "Successfully left the club.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $errors[] = "You are already a member of this club.";
        } else {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// --- Fetch Club Data & Permissions ---
try {
    // 1. Get Club Info
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    if (!$club) {
        header("Location: browse_clubs.php");
        exit;
    }
    
    // 2. Get User's Membership Info
    $stmt = $pdo->prepare("SELECT * FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
    $stmt->execute([$user_id, $club_id]);
    $membership = $stmt->fetch();
    
    $is_member = (bool)$membership;
    $can_post = $membership['can_post'] ?? 0;
    
    // 3. Check Permissions
    $is_club_admin = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');
    
    // User can post if they are Club Admin, Super Admin, or have can_post = 1
    $has_posting_permission = $is_club_admin || $is_super_admin || $can_post;

    // 4. Fetch Posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p
        JOIN users u ON p.user_id_fk = u.user_id
        WHERE p.club_id_fk = ? 
        ORDER BY p.post_date DESC
    ");
    $stmt->execute([$club_id]);
    $posts = $stmt->fetchAll();

    // 5. Fetch Events
    $stmt = $pdo->prepare("
        SELECT e.*, u.username 
        FROM events e
        JOIN users u ON e.user_id_fk = u.user_id
        WHERE e.club_id_fk = ? AND e.event_date >= NOW()
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$club_id]);
    $events = $stmt->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include '../template/header.php';
?>

<div class="content-box">
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

    <!-- Club Header -->
    <div class="club-header">
        <div class="club-header-info">
            <h1><?php echo htmlspecialchars($club['club_name']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($club['club_description'])); ?></p>
        </div>
        <div class="club-header-actions">
            <?php if ($is_member): ?>
                <form action="view_club.php?id=<?php echo $club_id; ?>" method="POST">
                    <button type="submit" name="leave_club" class="btn btn-secondary">Leave Club</button>
                </form>
            <?php else: ?>
                <form action="view_club.php?id=<?php echo $club_id; ?>" method="POST">
                    <button type="submit" name="join_club" class="btn btn-primary">Join Club</button>
                </form>
            <?php endif; ?>
            
            <?php if ($is_club_admin || $is_super_admin): ?>
                <a href="edit_club.php?id=<?php echo $club_id; ?>" class="btn btn-secondary">Edit Club</a>
                <a href="../members/manage_members.php?id=<?php echo $club_id; ?>" class="btn btn-secondary">Manage Members</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Club Content -->
    <?php if (!$is_member): ?>
        <div class="alert alert-danger">
            You must be a member of this club to view its posts and events.
        </div>
    <?php else: ?>
        <div class="club-content-container">
            <!-- Main Feed (Posts) -->
            <div class="club-main-feed">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>Club Posts</h3>
                    <?php if ($has_posting_permission): ?>
                        <!-- Link to the dedicated Create Post page -->
                        <a href="../posts/create_post.php?club_id=<?php echo $club_id; ?>" class="btn btn-primary">Create New Post</a>
                    <?php endif; ?>
                </div>

                <?php if (!$has_posting_permission): ?>
                    <p style="font-size: 0.9em; color: #666;">You do not have permission to create posts in this club.</p>
                <?php endif; ?>
                
                <?php if (empty($posts)): ?>
                    <p>No posts have been made in this club yet.</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-item">
                            <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                            <div class="post-meta">
                                By <?php echo htmlspecialchars($post['username']); ?> on <?php echo date('F j, Y, g:i a', strtotime($post['post_date'])); ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <?php 
                            // Check Permissions for Delete/Edit
                            $can_edit_post = ($post['user_id_fk'] == $user_id) || $is_club_admin || $is_super_admin;
                            ?>
                            
                            <?php if ($can_edit_post): ?>
                            <div class="post-actions">
                                <a href="../posts/edit_post.php?id=<?php echo $post['post_id']; ?>">Edit</a>
                                
                                <!-- Delete Button -->
                                <form action="../posts/delete_post.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id; ?>">
                                    <button type="submit" class="btn-link-danger">Delete</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar (Events) -->
            <div class="club-sidebar">
                <h3>Upcoming Events</h3>
                <?php if ($has_posting_permission): ?>
                    <!-- Link to the dedicated Create Event page -->
                    <a href="../events/create_event.php?club_id=<?php echo $club_id; ?>" class="btn btn-secondary" style="margin-bottom: 15px; width: 100%;">Create New Event</a>
                <?php endif; ?>
                
                <?php if (empty($events)): ?>
                    <p>No upcoming events.</p>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                            <div class="event-meta">
                                <strong>When:</strong> <?php echo date('D, M j, Y @ g:i a', strtotime($event['event_date'])); ?><br>
                                <strong>Where:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                <strong>Posted by:</strong> <?php echo htmlspecialchars($event['username']); ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>

                            <?php 
                            // Check Permissions for Delete/Edit
                            $can_edit_event = ($event['user_id_fk'] == $user_id) || $is_club_admin || $is_super_admin;
                            ?>
                            
                            <?php if ($can_edit_event): ?>
                            <div class="event-actions">
                                <a href="../events/edit_event.php?id=<?php echo $event['event_id']; ?>">Edit</a>
                                
                                <!-- Delete Button -->
                                <form action="../events/delete_event.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id; ?>">
                                    <button type="submit" class="btn-link-danger">Delete</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-link-danger {
    background: none;
    border: none;
    color: #dc3545;
    text-decoration: underline;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 0;
}
.btn-link-danger:hover {
    color: #c82333;
}
</style>

<?php include '../template/footer.php'; ?>