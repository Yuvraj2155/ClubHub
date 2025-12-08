Club Hub Platform

Club Hub is a dynamic, database-driven web application designed to facilitate the creation and management of social clubs. It features user authentication, role-based access control (Super Admin, Club Owner, Member), and full CRUD capabilities for clubs, posts, and events.

This project was built as a Capstone Project for BCS 350.

📋 Prerequisites

Before running this application, you must have a local web server environment installed that supports PHP and MySQL.

Windows: WAMP Server (Recommended) or XAMPP

Mac: MAMP or XAMPP

Linux: LAMP Stack

Required Extensions:
Ensure the pdo_mysql extension is enabled in your PHP configuration (usually enabled by default in WAMP/XAMPP).

🚀 Installation & Setup

Follow these steps to get the project running on your local machine:

1. Clone or Download

Clone this repository into your web server's root directory:

WAMP: C:\wamp64\www\ClubHub

XAMPP: C:\xampp\htdocs\ClubHub

git clone [https://github.com/Yuvraj2155/ClubHub.git](https://github.com/Yuvraj2155/ClubHub.git)


2. Start Your Server

Open your WAMP or XAMPP control panel and ensure both Apache and MySQL services are running (Green status).

3. Run the Database Setup (CRITICAL STEP)

This project comes with an automated setup script that creates the database, tables, and a default admin user.

Open your web browser.

Navigate to:

http://localhost/ClubHub/setup.php


(Note: Adjust the path if you named your folder something other than ClubHub)

You should see a "Setup Complete" message indicating that the database club_hub_db and all tables were created successfully.

4. Log In

After running the setup, you can log in with the default Super Admin account:

Email: admin@clubhub.com

Password: password123

🛠️ Configuration

The database connection settings are located in config.php. The default settings are configured for standard WAMP setups:

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default WAMP password is empty
define('DB_NAME', 'club_hub_db');


If you have a password on your MySQL root account (common in MAMP), please edit config.php with your credentials.

✨ Features

User System: Secure Login, Signup, and Profile management.

Roles:

Super Admin: Can manage all users and delete any club.

Club Owner: Can manage their specific club, transfer ownership, and delete the club.

Member: Can join/leave clubs and view content.

Club Management: Create clubs, update settings, manage memberships.

Content: Create posts and events within clubs.

Security: Password hashing, Prepared Statements (SQL Injection prevention), and Session management.

📂 Project Structure

club/ - Scripts for club creation, editing, and viewing.

events/ - CRUD operations for club events.

posts/ - CRUD operations for club posts.

members/ - Member management logic.

profile/ - User profile editing and account deletion.

Resources/css/ - Global stylesheets.

template/ - Header and Footer partials.

setup.php - Run this first to initialize the database.
