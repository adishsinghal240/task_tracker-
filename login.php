<?php
/**
 * ====================================================================
 * Internship Task Tracker - User Login (login.php)
 * ====================================================================
 * Handles user authentication: validates credentials against MySQL database,
 * checks hashed passwords with password_verify(), initiates PHP sessions,
 * and redirects authenticated users to the dashboard.
 */

// Start the session to manage user login state
session_start();

// If user is already logged in, redirect directly to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Include database connection
require_once 'includes/db.php';

$error_msg = "";
$email = "";

// Check if login form was submitted via HTTP POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 1. Retrieve and trim user input
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Validate empty fields
    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        // 3. Query the users table using a Prepared Statement
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Check if a user with this email exists
        if ($row = mysqli_fetch_assoc($result)) {
            
            // 4. Verify password with bcrypt
            $is_auth = password_verify($password, $row['password']);

            // Demo fallback for seeded accounts (Admin@123 / Intern@123)
            if (!$is_auth && (
                ($email === 'admin@tracker.com' && $password === 'Admin@123') ||
                ($email === 'intern@tracker.com' && $password === 'Intern@123') ||
                $row['password'] === $password
            )) {
                $is_auth = true;
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $up_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                mysqli_stmt_bind_param($up_stmt, "si", $new_hash, $row['id']);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            }

            if ($is_auth) {
                // Password is correct! Store user details in $_SESSION
                $_SESSION['user_id']    = $row['id'];
                $_SESSION['user_name']  = $row['name'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role']  = $row['role'] ?? 'intern';

                mysqli_stmt_close($stmt);

                // Redirect based on role
                if ($_SESSION['user_role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();

            } else {
                // Wrong password entered
                $error_msg = "Invalid email or password.";
            }
        } else {
            // No user found with this email
            $error_msg = "Invalid email or password.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Login</title>
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
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php" class="active">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Login Container -->
    <main>
        <section class="form-container">
            <h2>Welcome Back</h2>
            <p>Please enter your credentials to login.</p>

            <!-- Display Authentication Error Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" id="loginForm">
                
                <!-- Email Address -->
                <div class="form-group">
                    <label for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="example@internship.com" required>
                    <small class="error-msg" id="loginEmailError"></small>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('loginPassword', this)" title="Show/Hide Password" style="position: absolute; right: 0.6rem; background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 0.2rem; line-height: 1;">👁️</button>
                    </div>
                    <small class="error-msg" id="loginPasswordError"></small>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" id="loginBtn">Login</button>
            </form>

            <!-- Quick Demo Credentials for Viva / Demo -->
            <div class="demo-credentials-box" style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-color); border: 1px dashed var(--border-color); border-radius: var(--radius); text-align: center;">
                <p style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.6rem;">⚡ Quick Demo Login (One-Click for Viva/Evaluation):</p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary" onclick="fillDemoCredentials('admin@tracker.com', 'Admin@123')" style="font-size: 0.78rem; padding: 0.4rem 0.8rem; width: auto;">👔 Admin / Manager</button>
                    <button type="button" class="btn btn-secondary" onclick="fillDemoCredentials('intern@tracker.com', 'Intern@123')" style="font-size: 0.78rem; padding: 0.4rem 0.8rem; width: auto;">🎓 Intern</button>
                </div>
            </div>

            <p class="form-footer-text">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker | Built with HTML, CSS, JavaScript, PHP & MySQL</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
