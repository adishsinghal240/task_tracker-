<?php
/**
 * ====================================================================
 * Internship Task Tracker - User Registration (register.php)
 * ====================================================================
 * Handles user account creation: server-side validation, duplicate email check,
 * secure password hashing, and MySQL INSERT using Prepared Statements.
 */

// Include the database connection
require_once 'includes/db.php';

// Initialize variables for messages and form fields
$success_msg = "";
$error_msg = "";
$name = "";
$email = "";

// Check if form is submitted via HTTP POST method
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Retrieve and sanitize input data
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 2. Server-side Validation
    if (empty($name)) {
        $error_msg = "Name is required.";
    } elseif (empty($email)) {
        $error_msg = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email.";
    } elseif (empty($password)) {
        $error_msg = "Password is required.";
    } elseif (
        strlen($password) < 8 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        $error_msg = "Password must contain at least 8 characters, one uppercase letter, one lowercase letter and one number.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // 3. Check if email already exists in database (Prepared Statement)
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error_msg = "Email already registered.";
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);

            // 4. Hash password securely using PHP's built-in password_hash()
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 5. Insert new user record into database (Prepared Statement)
            $insert_sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "sss", $name, $email, $hashed_password);

            if (mysqli_stmt_execute($insert_stmt)) {
                $success_msg = "Registration successful. Please login.";
                // Clear input fields after successful registration
                $name = "";
                $email = "";
            } else {
                $error_msg = "Registration failed due to a server error. Please try again.";
            }

            mysqli_stmt_close($insert_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Register</title>
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
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="active">Register</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Registration Container -->
    <main>
        <section class="form-container">
            <h2>Create an Account</h2>
            <p>Register to start organizing your internship tasks.</p>

            <!-- Display PHP Success Message -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    <?php echo htmlspecialchars($success_msg); ?>
                    <a href="login.php" style="color: #047857; font-weight: 700; text-decoration: underline; margin-left: 5px;">Go to Login</a>
                </div>
            <?php endif; ?>

            <!-- Display PHP Error Message -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form action="register.php" method="POST" id="registerForm">
                
                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter your full name" required>
                    <small class="error-msg" id="nameError"></small>
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="example@internship.com" required>
                    <small class="error-msg" id="emailError"></small>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="password" name="password" placeholder="At least 8 chars, 1 uppercase, 1 lowercase, 1 number" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('password', this)" title="Show/Hide Password" style="position: absolute; right: 0.6rem; background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 0.2rem; line-height: 1;">👁️</button>
                    </div>
                    <small class="error-msg" id="passwordError"></small>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required style="padding-right: 2.75rem;">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" title="Show/Hide Password" style="position: absolute; right: 0.6rem; background: none; border: none; cursor: pointer; font-size: 1.1rem; padding: 0.2rem; line-height: 1;">👁️</button>
                    </div>
                    <small class="error-msg" id="confirmPasswordError"></small>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" id="registerBtn">Register</button>
            </form>

            <p class="form-footer-text">
                Already have an account? <a href="login.php">Login here</a>
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
