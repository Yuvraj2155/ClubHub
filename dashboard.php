<?php
require_once 'config.php';

// Require user to be logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$user_id = getCurrentUserId();
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$errors = [];
$messages = [];

// --- 1. Handle Join / Leave Actions (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $club_id = $_POST['club_id'] ?? null;
    
    if ($club_id) {
        try {
            if (isset($_POST['join_club'])) {
                $stmt = $pdo->prepare("INSERT INTO club_memberships (user_id_fk, club_id_fk, can_post) VALUES (?, ?, 0)");
                $stmt->execute([$user_id, $club_id]);
                $messages[] = "Successfully joined the club!";
            } elseif (isset($_POST['leave_club'])) {
                // Prevent club creator from leaving their own club (optional safeguard)
                // First check if user is creator
                $stmt = $pdo->prepare("SELECT creator_id_fk FROM clubs WHERE club_id = ?");
                $stmt->execute([$club_id]);
                $club_check = $stmt->fetch();
                
                if ($club_check && $club_check['creator_id_fk'] == $user_id) {
                    $errors[] = "You cannot leave a club you created. You must delete it or transfer ownership.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM club_memberships WHERE user_id_fk = ? AND club_id_fk = ?");
                    $stmt->execute([$user_id, $club_id]);
                    $messages[] = "Successfully left the club.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Action failed: " . $e->getMessage();
        }
    }
}

// --- 2. Fetch Data ---
$my_clubs = [];
$explore_clubs = [];

try {
    // A. Get list of Club IDs the user has joined
    $stmt = $pdo->prepare("SELECT club_id_fk FROM club_memberships WHERE user_id_fk = ?");
    $stmt->execute([$user_id]);
    $joined_ids = $stmt->fetchAll(PDO::FETCH_COLUMN); // Returns simple array like [1, 5, 8]

    // B. Get ALL clubs with Creator Name
    $stmt = $pdo->query("
        SELECT c.*, u.username as creator_name 
        FROM clubs c 
        JOIN users u ON c.creator_id_fk = u.user_id 
        ORDER BY c.created_at DESC
    ");
    $all_clubs = $stmt->fetchAll();

    // C. Separate into two lists
    foreach ($all_clubs as $club) {
        if (in_array($club['club_id'], $joined_ids)) {
            $my_clubs[] = $club;
        } else {
            $explore_clubs[] = $club;
        }
    }

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

include 'template/header.php';
?>

<div class="content-box">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p style="color:#666;">Here is what's happening in your community.</p>
        </div>
        <a href="club/create_club.php" class="btn btn-primary">+ Create New Club</a>
    </div>

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
</div>

<!-- Section 1: My Joined Clubs -->
<div class="content-box">
    <h3>My Clubs</h3>
    <?php if (empty($my_clubs)): ?>
        <p>You haven't joined any clubs yet.</p>
    <?php else: ?>
        <div class="club-grid">
            <?php foreach ($my_clubs as $club): ?>
                <div class="club-card joined-card">
                    <div class="card-body">
                        <h4><a href="club/view_club.php?id=<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['club_name']); ?></a></h4>
                        <p class="meta">Made by: <?php echo htmlspecialchars($club['creator_name']); ?></p>
                        <p class="desc"><?php echo htmlspecialchars(substr($club['club_description'], 0, 100)) . '...'; ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="club/view_club.php?id=<?php echo $club['club_id']; ?>" class="btn btn-sm btn-primary">View</a>
                        <!-- Leave Form -->
                        <form method="POST" action="dashboard.php" style="display:inline;">
                            <input type="hidden" name="club_id" value="<?php echo $club['club_id']; ?>">
                            <button type="submit" name="leave_club" class="btn btn-sm btn-secondary" onclick="return confirm('Leave this club?');">Leave</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Section 2: Explore (Clubs I haven't joined) -->
<div class="content-box" style="background-color: #f8f9fa;">
    <h3>Explore New Clubs</h3>
    <?php if (empty($explore_clubs)): ?>
        <p>You've joined all the available clubs!</p>
    <?php else: ?>
        <div class="club-grid">
            <?php foreach ($explore_clubs as $club): ?>
                <div class="club-card">
                    <div class="card-body">
                        <h4><a href="club/view_club.php?id=<?php echo $club['club_id']; ?>"><?php echo htmlspecialchars($club['club_name']); ?></a></h4>
                        <p class="meta">Made by: <?php echo htmlspecialchars($club['creator_name']); ?></p>
                        <p class="desc"><?php echo htmlspecialchars(substr($club['club_description'], 0, 100)) . '...'; ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="club/view_club.php?id=<?php echo $club['club_id']; ?>" class="btn btn-sm btn-secondary">View</a>
                        <!-- Join Form -->
                        <form method="POST" action="dashboard.php" style="display:inline;">
                            <input type="hidden" name="club_id" value="<?php echo $club['club_id']; ?>">
                            <button type="submit" name="join_club" class="btn btn-sm btn-primary">Join</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Dashboard specific styles */
.club-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 15px;
}
.club-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: box-shadow 0.2s;
}
.joined-card {
    border-left: 5px solid #007bff; /* Blue strip to indicate joined */
}
.club-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.card-body {
    padding: 15px;
    flex-grow: 1;
}
.card-body h4 { margin-top: 0; margin-bottom: 5px; }
.card-body .meta { font-size: 0.85rem; color: #888; margin-bottom: 10px; font-style: italic; }
.card-body .desc { font-size: 0.95rem; color: #444; }
.card-footer {
    padding: 10px 15px;
    background: #f9f9f9;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}
.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
}
</style>

<?php include 'template/footer.php'; ?>