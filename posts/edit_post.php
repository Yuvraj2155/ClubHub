<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = getCurrentUserId();
$post_id = $_GET['id'] ?? null;
$title = "";
$content = "";
$errors = [];
$messages = [];

if (!$post_id) {
    header("Location: ../dashboard.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        header("Location: ../dashboard.php");
        exit;
    }
    
    $club_id = $post['club_id_fk'];
    $title = $post['title'];
    $content = $post['content'];

    $stmt = $pdo->prepare("SELECT creator_id_fk FROM clubs WHERE club_id = ?");
    $stmt->execute([$club_id]);
    $club = $stmt->fetch();

    $is_author = ($post['user_id_fk'] == $user_id);
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
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (empty($title)) $errors[] = "Title is required.";
    if (empty($content)) $errors[] = "Content is required.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE post_id = ?");
            $stmt->execute([$title, $content, $post_id]);
            $messages[] = "Post updated successfully!";
        } catch (PDOException $e) {
            $errors[] = "Error updating post: " . $e->getMessage();
        }
    }
}

include '../template/header.php';
?>

<div class="content-box form-container">
    <h2>Edit Post</h2>
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

    <form action="edit_post.php?id=<?php echo $post_id; ?>" method="POST">
        <div class="form-group">
            <label for="title">Post Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" required><?php echo htmlspecialchars($content); ?></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Post</button>
        </div>
    </form>
</div>

<?php include '../template/footer.php'; ?>