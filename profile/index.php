<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$errors = [];

try {
    // Fetch User Details
    $stmt = $pdo->prepare("SELECT username, email, created_at, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        // Should rarely happen if logged in
        header("Location: ../logout.php");
        exit;
    }

    // Fetch User's Clubs (Joined)
    $stmt = $pdo->prepare("
        SELECT c.club_id, c.club_name, m.join_date 
        FROM clubs c
        JOIN club_memberships m ON c.club_id = m.club_id_fk
        WHERE m.user_id_fk = ?
        ORDER BY m.join_date DESC
    ");
    $stmt->execute([$user_id]);
    $joined_clubs = $stmt->fetchAll();

    // Fetch Clubs Created by User (Ownership)
    $stmt = $pdo->prepare("SELECT club_id, club_name FROM clubs WHERE creator_id_fk = ?");
    $stmt->execute([$user_id]);
    $owned_clubs = $stmt->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include '../template/header.php';
?>

<div class="content-box">
    <h2>My Profile</h2>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success">Profile updated successfully!</div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left Column: User Info -->
        <div style="flex: 1; min-width: 300px;">
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            
            <div style="margin-top: 20px;">
                <a href="edit.php" class="btn btn-primary" style="margin-bottom: 10px;">Edit Profile</a>
                <a href="delete.php" class="btn btn-danger">Delete Account</a>
            </div>
        </div>

        <!-- Right Column: Clubs -->
        <div style="flex: 1; min-width: 300px;">
            <h3>Clubs I Own</h3>
            <?php if (empty($owned_clubs)): ?>
                <p>You don't own any clubs.</p>
            <?php else: ?>
                <ul class="club-list">
                    <?php foreach ($owned_clubs as $club): ?>
                        <li class="club-list-item">
                            <a href="../club/view_club.php?id=<?php echo $club['club_id']; ?>">
                                <?php echo htmlspecialchars($club['club_name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3 style="margin-top: 20px;">Clubs I Joined</h3>
            <?php if (empty($joined_clubs)): ?>
                <p>You haven't joined any clubs yet.</p>
            <?php else: ?>
                <ul class="club-list">
                    <?php foreach ($joined_clubs as $club): ?>
                        <li class="club-list-item">
                            <a href="../club/view_club.php?id=<?php echo $club['club_id']; ?>">
                                <?php echo htmlspecialchars($club['club_name']); ?>
                            </a>
                            <br><small>Joined: <?php echo date('M j, Y', strtotime($club['join_date'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>