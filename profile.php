<?php
/**
 * ====================================================================
 * Internship Task Tracker - User Profile (profile.php)
 * ====================================================================
 * Displays registered user details (Name, Email, Account Creation Date)
 * and allows updating the user's display name.
 */

// 1. Start Session & Check Authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Include Database Connection
require_once 'includes/db.php';

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'intern';
$success_msg = "";
$error_msg   = "";

// 3. Process Profile Update Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error_msg = "Full Name cannot be empty.";
    } elseif (strlen($name) > 100) {
        $error_msg = "Full Name cannot exceed 100 characters.";
    } else {
        // Update user name in MySQL using Prepared Statement
        $update_sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "si", $name, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            // Update active session name so navigation bar reflects changes instantly
            $_SESSION['user_name'] = $name;
            $success_msg = "Profile updated successfully.";
        } else {
            $error_msg = "Failed to update profile. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}

// 4. Fetch Latest User Information from Database
$fetch_sql = "SELECT name, email, role, created_at FROM users WHERE id = ?";
$fetch_stmt = mysqli_prepare($conn, $fetch_sql);
mysqli_stmt_bind_param($fetch_stmt, "i", $user_id);
mysqli_stmt_execute($fetch_stmt);
$result = mysqli_stmt_get_result($fetch_stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($fetch_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <nav>
            <div class="logo">
                <h2>📋 TaskTracker</h2>
            </div>
            <ul>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="admin_dashboard.php" style="color: var(--primary-color); font-weight: 700;">⭐ Manager Panel</a></li>
                <?php endif; ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="tasks.php">All Tasks</a></li>
                <li><a href="add_task.php">+ Add Task</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Profile Container -->
    <main>
        <section class="form-container">
            <h2>👤 My Profile</h2>
            <p>Manage your account settings and personal details.</p>

            <!-- Success Alert -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Error Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Update Form -->
            <form action="profile.php" method="POST">
                
                <!-- Full Name (Editable) -->
                <div class="form-group">
                    <label for="profileName">Full Name</label>
                    <input type="text" id="profileName" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <!-- Email Address (Read-Only) -->
                <div class="form-group">
                    <label for="profileEmail">Email Address (Cannot be changed)</label>
                    <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
                </div>

                <!-- Account Role -->
                <div class="form-group">
                    <label>Account Role</label>
                    <input type="text" value="<?php echo ($user['role'] === 'admin') ? 'Manager / Administrator (Privileged)' : 'Intern (Standard Access)'; ?>" disabled style="opacity: 0.7; cursor: not-allowed; font-weight: 600;">
                </div>

                <!-- Account Created Timestamp -->
                <div class="form-group">
                    <label>Member Since</label>
                    <input type="text" value="<?php echo date('F d, Y - h:i A', strtotime($user['created_at'])); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Save Changes</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker </p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
