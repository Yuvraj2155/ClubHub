<?php
/*
 * view_club.php
 * The main "hub" page for a single club.
 * - Shows club info, posts, and events.
 * - Handles all user actions: joining, leaving, posting, creating events.
 * - Shows admin controls based on user's permission level.
 */

// 1. CONFIG & SESSION
require_once 'config.php';
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? 'guest';

// 2. GET CLUB ID
// If no club ID is provided in the URL, redirect back to browse page.
if (!isset($_GET['id'])) {
    header('Location: browse_clubs.php');
    exit;
}
$club_id = (int)$_GET['id'];

// 3. HANDLE POST ACTIONS
// This block handles forms submitted on this page (Join, Leave, Post, Event)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            // --- ACTION: JOIN CLUB ---
            case 'join_club':
                // Data Sanitization (Req #6) - Prepared Statement
                $stmt = $pdo->prepare("INSERT INTO club_memberships (user_id_fk, club_id_fk, can_post) VALUES (?, ?, 0)");
                $stmt->execute([$user_id, $club_id]);
                $messages[] = "Welcome! You have joined the club.";
                break;

            // --- ACTION: LEAVE CLUB ---
            case 'leave_club':
                // Data Sanitization (Req #6) - Prepared Statement
                $stmt = $pdo->prepare("DELETE FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
                $stmt->execute([$user_id, $club_id]);
                $messages[] = "You have left the club.";
                break;

            // --- ACTION: CREATE POST ---
            case 'create_post':
                $content = trim($_POST['content'] ?? '');
                // Server-Side Validation (Req #7)
                if (empty($content)) {
                    $errors[] = "Post content cannot be empty.";
                } else {
                    // Data Sanitization (Req #6) - Prepared Statement
                    $stmt = $pdo->prepare("INSERT INTO posts (content, user_id_fk, club_id_fk) VALUES (?, ?, ?)");
                    $stmt->execute([$content, $user_id, $club_id]);
                    $messages[] = "Post created successfully.";
                }
                break;
            
            // --- ACTION: CREATE EVENT ---
            case 'create_event':
                // Server-Side Validation (Req #7)
                $event_name = trim($_POST['event_name'] ?? '');
                $event_desc = trim($_POST['event_description'] ?? '');
                $event_date = trim($_POST['event_date'] ?? '');
                $location = trim($_POST['location'] ?? '');

                if (empty($event_name) || empty($event_date) || empty($location)) {
                    $errors[] = "Event Name, Date, and Location are required.";
                } else {
                    // Data Sanitization (Req #6) - Prepared Statement
                    $stmt = $pdo->prepare(
                        "INSERT INTO events (event_name, event_description, event_date, location, user_id_fk, club_id_fk) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([$event_name, $event_desc, $event_date, $location, $user_id, $club_id]);
                    $messages[] = "Event created successfully.";
                }
                break;
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// 4. FETCH CLUB & PERMISSION DATA
// Get club details
$stmt = $pdo->prepare("SELECT c.*, u.username AS creator_name FROM clubs c JOIN users u ON c.creator_id_fk = u.user_id WHERE c.club_id = ?");
$stmt->execute([$club_id]);
$club = $stmt->fetch();

// If club doesn't exist, redirect
if (!$club) {
    header('Location: browse_clubs.php');
    exit;
}

// Check user's relationship to this club
$is_member = false;
$can_post = false;
$is_club_admin = ($club['creator_id_fk'] == $user_id);
$is_super_admin = ($user_role == 'superadmin');

if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
    $stmt->execute([$user_id, $club_id]);
    $membership = $stmt->fetch();
    
    if ($membership) {
        $is_member = true;
        $can_post = $membership['can_post'] == 1;
    }
}

// Authorization: User can post if they are a Club Admin, Super Admin, or a member with 'can_post' permission.
$has_post_permission = ($is_club_admin || $is_super_admin || ($is_member && $can_post));


// 5. FETCH CLUB CONTENT (POSTS & EVENTS)
// Fetch Posts (join with users to get poster's name)
$post_stmt = $pdo->prepare(
    "SELECT p.*, u.username 
     FROM posts p 
     JOIN users u ON p.user_id_fk = u.user_id 
     WHERE p.club_id_fk = ? 
     ORDER BY p.post_date DESC"
);
$post_stmt->execute([$club_id]);
$posts = $post_stmt->fetchAll();

// Fetch Events (join with users to get creator's name)
$event_stmt = $pdo->prepare(
    "SELECT e.*, u.username 
     FROM events e 
     JOIN users u ON e.user_id_fk = u.user_id 
     WHERE e.club_id_fk = ? AND e.event_date >= CURDATE()
     ORDER BY e.event_date ASC"
);
$event_stmt->execute([$club_id]);
$events = $event_stmt->fetchAll();


// 6. HTML VIEW
require_once 'header.php';
?>

<!-- Club Header -->
<div class="club-header">
    <h1><?php echo htmlspecialchars($club['club_name']); ?></h1>
    <p class="club-header-desc"><?php echo htmlspecialchars($club['club_description']); ?></p>
    <p class="club-header-creator">Admin: <?php echo htmlspecialchars($club['creator_name']); ?></p>
</div>

<!-- Action Bar -->
<div class="action-bar">
    <form action="view_club.php?id=<?php echo $club_id; ?>" method="POST" class="action-form">
        <?php if ($user_id): // Only show Join/Leave if logged in ?>
            <?php if ($is_member): ?>
                <?php if (!$is_club_admin): // Club Admin cannot leave their own club ?>
                    <input type="hidden" name="action" value="leave_club">
                    <button type="submit" class="btn btn-danger">Leave Club</button>
                <?php else: ?>
                    <span class="admin-notice">You are the admin of this club.</span>
                <?php endif; ?>
            <?php else: ?>
                <input type="hidden" name="action" value="join_club">
                <button type="submit" class="btn">Join Club</button>
            <?php endif; ?>
        <?php endif; ?>
    </form>
    
    <div class="admin-links">
        <?php if ($is_club_admin || $is_super_admin): // Show admin links ?>
            <a href="manage_members.php?club_id=<?php echo $club_id; ?>" class="btn-small">Manage Members</a>
            <a href="edit_club.php?club_id=<?php echo $club_id; ?>" class="btn-small">Edit Club Settings</a>
        <?php endif; ?>
    </div>
</div>

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

<!-- Main Club Content (2-column layout) -->
<div class="club-content-layout">

    <!-- Column 1: Posts & Events -->
    <div class="club-main-feed">
        
        <?php
        // --- CREATE POST/EVENT FORMS ---
        // Show forms only if user is a member and has permission
        if ($is_member):
            if ($has_post_permission):
        ?>
            <!-- Create Post Form -->
            <div class="content-card">
                <h3>Create New Post</h3>
                <form action="view_club.php?id=<?php echo $club_id; ?>" method="POST">
                    <input type="hidden" name="action" value="create_post">
                    <div class="form-group">
                        <textarea name="content" rows="4" placeholder="What's on your mind?" required></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Post</button>
                </form>
            </div>
            
            <!-- Create Event Form -->
            <div class="content-card">
                <h3>Create New Event</h3>
                <form action="view_club.php?id=<?php echo $club_id; ?>" method="POST" class="event-form">
                    <input type="hidden" name="action" value="create_event">
                    <div class="form-group">
                        <label for="event_name">Event Name</label>
                        <input type="text" id="event_name" name="event_name" required>
                    </div>
                    <div class="form-group">
                        <label for="event_description">Description</label>
                        <textarea id="event_description" name="event_description" rows="3"></textarea>
                    </div>
                    <div class="form-group-row">
                        <div class="form-group">
                            <label for="event_date">Date and Time</label>
                            <!-- Client-Side Validation (Req #7) -->
                            <input type="datetime-local" id="event_date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">Create Event</button>
                </form>
            </div>
        <?php
            else: // Is a member, but doesn't have post permission
        ?>
            <div class="content-card permission-notice">
                <p>You are a member of this club, but you do not have permission to create posts or events. A club admin must grant you permission.</p>
            </div>
        <?php
            endif; // end $has_post_permission check
        endif; // end $is_member check
        ?>

        <!-- --- POSTS FEED --- -->
        <div class="content-feed">
            <h2>Posts</h2>
            <?php if (empty($posts)): ?>
                <div class="content-card-light">
                    <p>No posts yet. Be the first to say something!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="content-card-light post-card">
                        <p class="post-meta">
                            <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                            <span class="post-date"><?php echo date('M j, Y \a\t g:ia', strtotime($post['post_date'])); ?></span>
                        </p>
                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div> <!-- /club-main-feed -->

    <!-- Column 2: Upcoming Events -->
    <div class="club-sidebar">
        <div class="content-card">
            <h3>Upcoming Events</h3>
            <?php if (empty($events)): ?>
                <p>No upcoming events.</p>
            <?php else: ?>
                <ul class="event-list">
                    <?php foreach ($events as $event): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                            <span class="event-date"><?php echo date('M j, g:ia', strtotime($event['event_date'])); ?></span>
                            <span class="event-location"><?php echo htmlspecialchars($event['location']); ?></span>
                            <p class="event-desc"><?php echo htmlspecialchars($event['event_description']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div> <!-- /club-sidebar -->

</div> <!-- /club-content-layout -->


<?php
require_once 'footer.php';
?>
