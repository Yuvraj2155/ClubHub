<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../dashboard.php");
    exit;
}

$user_id = getCurrentUserId();
$event_id = $_POST['event_id'] ?? null;
$club_id = $_POST['club_id'] ?? null;

if (!$event_id || !$club_id) {
    header("Location: ../dashboard.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id_fk, club_id_fk FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event || $event['club_id_fk'] != $club_id) {
        throw new Exception("Event not found or club ID mismatch.");
    }

    $stmt = $pdo->prepare("SELECT creator_id_fk FROM clubs WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    $is_author = ($event['user_id_fk'] == $user_id);
    $is_club_admin = ($club['creator_id_fk'] == $user_id);
    $is_super_admin = ($_SESSION['role'] == 'superadmin');

    if (!$is_author && !$is_club_admin && !$is_super_admin) {
        header("Location: ../club/view_club.php?id=" . $club_id . "&error=nopermission");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);

    header("Location: ../club/view_club.php?id=" . $club_id . "&msg=eventdeleted");
    exit;

} catch (Exception $e) {
    header("Location: ../club/view_club.php?id=" . $club_id . "&error=" . urlencode($e->getMessage()));
    exit;
}
?>