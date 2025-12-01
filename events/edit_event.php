<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$event_id = $_GET['id'] ?? null;
$event_name = "";
$event_description = "";
$event_date = "";
$location = "";
$errors = [];
$messages = [];

if (!$event_id) {
    header("Location: ../dashboard.php");
    exit;
}

try {
    // 1. Get Event Info
    $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header("Location: ../dashboard.php");
        exit;
    }
    
    $club_id = $event['club_id_fk'];
    $event_name = $event['event_name'];
    $event_description = $event['event_description'];
    $location = $event['location'];
    $event_date = date('Y-m-d\TH:i', strtotime($event['event_date']));

    // 2. Get Club Info
    $stmt = $pdo->prepare("SELECT creator_id_fk FROM clubs WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    // 3. Check Permissions
    $is_author = ($event['user_id_fk'] == $user_id);
    $is_club_admin = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');

    if (!$is_author && !$is_club_admin && !$is_super_admin) {
        header("Location: ../club/view_club.php?id=" . $club_id . "&error=nopermission");
        exit;
    }

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = trim($_POST['event_name']);
    $event_description = trim($_POST['event_description']);
    $event_date = $_POST['event_date'];
    $location = trim($_POST['location']);

    if (empty($event_name)) $errors[] = "Event name is required.";
    if (empty($event_description)) $errors[] = "Description is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";
    if (empty($location)) $errors[] = "Location is required.";
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET event_name = ?, event_description = ?, event_date = ?, location = ? WHERE event_id = ?");
            $stmt->execute([$event_name, $event_description, $event_date, $location, $event_id]);
            $messages[] = "Event updated successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error updating event: " . $e->getMessage();
        }
    }
}

include '../template/header.php';
?>

<div class="content-box form-container">
    <h2>Edit Event</h2>
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

    <form action="edit_event.php?id=<?php echo $event_id; ?>" method="POST">
        <div class="form-group">
            <label for="event_name">Event Name</label>
            <input type="text" id="event_name" name="event_name" class="form-control" value="<?php echo htmlspecialchars($event_name); ?>" required>
        </div>
        <div class="form-group">
            <label for="event_date">Event Date and Time</label>
            <input type="datetime-local" id="event_date" name="event_date" class="form-control" value="<?php echo htmlspecialchars($event_date); ?>" required>
        </div>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" required>
        </div>
        <div class="form-group">
            <label for="event_description">Description</label>
            <textarea id="event_description" name="event_description" class="form-control" required><?php echo htmlspecialchars($event_description); ?></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Event</button>
        </div>
    </form>
</div>

<?php include '../template/footer.php'; ?>