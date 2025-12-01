<?php
/*
 * setup.php
 * * Run this file ONE TIME on any new device.
 * It will automatically create the database, all tables, and a default admin user.
 * * Usage: Go to http://localhost/CLUBHUB/Setup.php
 */

// WAMP Default Credentials
$host = 'localhost';
$root_user = 'root';
$root_pass = ''; // Default WAMP password is empty
$db_name = 'club_hub_db';

try {
    // 1. Connect to MySQL Server (without selecting a DB yet)
    $pdo = new PDO("mysql:host=$host", $root_user, $root_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px;'>";
    echo "<h2>🛠️ Club Hub Database Setup</h2>";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database <strong>$db_name</strong> checked/created.</p>";

    // 3. Select Database
    $pdo->exec("USE `$db_name`");

    // 4. Create Tables
    
    // Users Table
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'member',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql_users);
    echo "<p>✅ Table <strong>users</strong> checked/created.</p>";

    // Clubs Table
    $sql_clubs = "CREATE TABLE IF NOT EXISTS clubs (
        club_id INT AUTO_INCREMENT PRIMARY KEY,
        club_name VARCHAR(100) NOT NULL,
        club_description TEXT,
        creator_id_fk INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (creator_id_fk) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql_clubs);
    echo "<p>✅ Table <strong>clubs</strong> checked/created.</p>";

    // Memberships Table
    $sql_memberships = "CREATE TABLE IF NOT EXISTS club_memberships (
        membership_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id_fk INT NOT NULL,
        club_id_fk INT NOT NULL,
        can_post TINYINT(1) NOT NULL DEFAULT 0,
        join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id_fk) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (club_id_fk) REFERENCES clubs(club_id) ON DELETE CASCADE,
        UNIQUE KEY user_club_unique (user_id_fk, club_id_fk)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql_memberships);
    echo "<p>✅ Table <strong>club_memberships</strong> checked/created.</p>";

    // Posts Table
    $sql_posts = "CREATE TABLE IF NOT EXISTS posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id_fk INT NOT NULL,
        club_id_fk INT NOT NULL,
        FOREIGN KEY (user_id_fk) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (club_id_fk) REFERENCES clubs(club_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql_posts);
    echo "<p>✅ Table <strong>posts</strong> checked/created.</p>";

    // Events Table
    $sql_events = "CREATE TABLE IF NOT EXISTS events (
        event_id INT AUTO_INCREMENT PRIMARY KEY,
        event_name VARCHAR(255) NOT NULL,
        event_description TEXT NOT NULL,
        event_date DATETIME NOT NULL,
        location VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id_fk INT NOT NULL,
        club_id_fk INT NOT NULL,
        FOREIGN KEY (user_id_fk) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (club_id_fk) REFERENCES clubs(club_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql_events);
    echo "<p>✅ Table <strong>events</strong> checked/created.</p>";

    // 5. Create Default Super Admin (Optional but recommended)
    $admin_email = 'admin@clubhub.com';
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    
    if (!$stmt->fetch()) {
        // Create default admin: password is 'password123'
        $pass_hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'superadmin')");
        $stmt->execute(['SuperAdmin', $admin_email, $pass_hash]);
        
        echo "<hr>";
        echo "<p>✅ <strong>Default Super Admin account created:</strong></p>";
        echo "<ul>";
        echo "<li>Email: <strong>admin@clubhub.com</strong></li>";
        echo "<li>Password: <strong>password123</strong></li>";
        echo "</ul>";
    } else {
        echo "<p>ℹ️ Super Admin account already exists.</p>";
    }

    echo "<hr>";
    echo "<h3>🎉 Setup Complete!</h3>";
    echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    die("<div style='color: red; padding: 20px;'>❌ <strong>Setup Failed:</strong> " . $e->getMessage() . "</div>");
}
?>