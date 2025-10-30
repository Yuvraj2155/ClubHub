<?php
/*
 * create_event.php
 * Allows a permitted user to create a new event in a club.
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
    header('Location: ../club/view_club.php?id=' . $club_id);
    exit;
}

// 4. AUTHORIZATION (Req #4: Web Authentication & Custom Rule)
// This logic is identical to create_post.php
$is_club_admin = ($club['creator_id_fk'] == $user_id);
$is_super_admin = ($user_role == 'superadmin');
$has_post_permission = ($membership['can_post'] == 1);

if (!$is_club_admin && !$is_super_admin && !$has_post_permission) {
    $_SESSION['error_message'] = "You do not have permission to create events in this club.";
    header('Location: ../club/view_club.php?id=' . $club_id);
    exit;
}

// 5. HANDLE FORM SUBMISSION (Create Event)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Server-Side Validation (Req #7)
    $event_name = trim($_POST['event_name'] ?? '');
    $event_description = trim($_POST['event_description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($event_name) || empty($event_description) || empty($event_date) || empty($location)) {
        $errors[] = "All event fields are required.";
    } else {
        try {
            // Data Sanitization (Req #6: Prepared Statements)
            // PHP for Adding Records (Req #2)
            $stmt = $pdo->prepare(
                "INSERT INTO events (event_name, event_description, event_date, location, user_id_fk, club_id_fk) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$event_name, $event_description, $event_date, $location, $user_id, $club_id]);

            // Redirect back to the club page to see the new event
            $_SESSION['message'] = "New event created successfully!";
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
        <h1>Create New Event</h1>
        <p>For: <strong><?php echo htmlspecialchars($club['club_name']); ?></strong></p>

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

        <form action="create_event.php?club_id=<?php echo $club_id; ?>" method="POST">
            <div class="form-group">
                <label for="event_name">Event Name</label>
                <input type="text" id="event_name" name="event_name" required>
            </div>
            <div class="form-group">
                <label for="event_date">Event Date and Time</label>
                <!-- Using the native datetime-local input for date/time picking -->
                <input type="datetime-local" id="event_date" name="event_date" required>
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Library Room 201 or 'Online (Discord)'" required>
            </div>
            <div class="form-group">
                <label for="event_description">Event Description</label>
                <textarea id="event_description" name="event_description" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn">Create Event</button>
        </form>
        <a href="view_club.php?id=<?php echo $club_id; ?>" class="auth-link">Cancel</a>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
