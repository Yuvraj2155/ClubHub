<?php
/*
 * create_club.php
 * A form for logged-in users to create a new club.
 * When they create a club, they become its "Club Admin".
 */

// 1. CONFIG & SESSION
require_once '../config.php';

// 2. PAGE PROTECTION (Req #5: Session Management)
// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// 3. BACKEND LOGIC (Handles form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Server-Side Validation (Req #7)
    $club_name = trim($_POST['club_name'] ?? '');
    $club_description = trim($_POST['club_description'] ?? '');
    $creator_id = $_SESSION['user_id']; // The logged-in user is the creator

    if (empty($club_name)) $errors[] = 'Club Name is required.';
    if (empty($club_description)) $errors[] = 'Club Description is required.';

    // Check if club name already exists
    if (empty($errors)) {
        // Data Sanitization (Req #6) - Prepared Statement
        $stmt = $pdo->prepare("SELECT 1 FROM clubs WHERE club_name = ?");
        $stmt->execute([$club_name]);
        if ($stmt->fetch()) {
            $errors[] = 'A club with this name already exists.';
        }
    }

    // If no errors, create the club
    if (empty($errors)) {
        // We need to use a TRANSACTION here because we are writing to
        // TWO tables: `clubs` and `club_memberships`.
        // If one fails, we must roll back the other.
        
        $pdo->beginTransaction();
        
        try {
            // PHP for Adding Records (Req #2)
            // 1. Insert into `clubs` table
            $stmt = $pdo->prepare("INSERT INTO clubs (club_name, club_description, creator_id_fk) VALUES (?, ?, ?)");
            $stmt->execute([$club_name, $club_description, $creator_id]);
            
            // Get the ID of the club we just created
            $new_club_id = $pdo->lastInsertId();

            // 2. Insert into `club_memberships` (make the creator a member)
            // We also give them 'can_post' permission by default.
            $stmt = $pdo->prepare("INSERT INTO club_memberships (user_id_fk, club_id_fk, can_post) VALUES (?, ?, 1)");
            $stmt->execute([$creator_id, $new_club_id]);
            
            // If both queries worked, commit the changes
            $pdo->commit();

            // Redirect to the new club's page
            header('Location: view_club.php?id=' . $new_club_id);
            exit;

        } catch (PDOException $e) {
            // If anything went wrong, roll back all changes
            $pdo->rollBack();
            $errors[] = 'Database error. Could not create club. ' . $e->getMessage();
        }
    }
}

// 4. HTML VIEW
require_once '../template/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Create a New Club</h1>

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

        <form action="create_club.php" method="POST">
            <!-- Client-Side Validation (Req #7) -->
            <div class="form-group">
                <label for="club_name">Club Name</label>
                <input type="text" id="club_name" name="club_name" required>
            </div>
            <div class="form-group">
                <label for="club_description">Club Description</label>
                <!-- Use a textarea for longer descriptions -->
                <textarea id="club_description" name="club_description" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn">Create Club</button>
        </form>
    </div>
</div>

<?php
require_once '../template/footer.php';
?>
