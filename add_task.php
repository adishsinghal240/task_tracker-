<?php
/**
 * ====================================================================
 * Internship Task Tracker - Add Task (add_task.php)
 * ====================================================================
 * CREATE Operation: Allows an authenticated intern to create and save
 * a new task, supporting Categories, File Attachments, and Deliverable Links.
 * Managers/Admins can assign tasks directly to any intern.
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

// Form variables (preserved on error)
$title           = "";
$description     = "";
$category        = "General";
$deadline        = "";
$priority        = "Medium";
$status          = "Pending";
$submission_link = "";
$assigned_to     = $user_id;

// If Admin, fetch all interns for the assignee dropdown
$interns_list = [];
if ($user_role === 'admin') {
    $interns_query = mysqli_query($conn, "SELECT id, name, email FROM users ORDER BY name ASC");
    if ($interns_query) {
        while ($intern = mysqli_fetch_assoc($interns_query)) {
            $interns_list[] = $intern;
        }
    }
}

// Allowed categories, priorities, statuses
$allowed_categories = ['Development', 'Design', 'Testing', 'Research', 'Documentation', 'General'];
$allowed_priorities = ['Low', 'Medium', 'High'];
$allowed_statuses   = ['Pending', 'In Progress', 'Completed'];

// 3. Process Form Submission (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Retrieve and sanitize form inputs
    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $category        = trim($_POST['category'] ?? 'General');
    $deadline        = trim($_POST['deadline'] ?? '');
    $priority        = $_POST['priority'] ?? 'Medium';
    $status          = $_POST['status'] ?? 'Pending';
    $submission_link = trim($_POST['submission_link'] ?? '');

    // Assignee (Admin can choose intern, regular interns assign to themselves)
    if ($user_role === 'admin' && isset($_POST['assigned_to'])) {
        $assigned_to = intval($_POST['assigned_to']);
        if ($assigned_to <= 0) $assigned_to = $user_id;
    } else {
        $assigned_to = $user_id;
    }

    // Handle File Attachment Upload
    $attachment_filename = null;
    $upload_error = false;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];
        $file_name = $file['name'];
        $file_tmp  = $file['tmp_name'];
        $file_size = $file['size'];
        $file_err  = $file['error'];

        if ($file_err === UPLOAD_ERR_OK) {
            $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg', 'zip', 'docx', 'txt'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_exts, true)) {
                $error_msg = "Invalid file type. Allowed: PDF, PNG, JPG, JPEG, ZIP, DOCX, TXT.";
                $upload_error = true;
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                $error_msg = "File is too large. Maximum size is 5MB.";
                $upload_error = true;
            } else {
                // Generate secure random filename
                $safe_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
                $target_dir = __DIR__ . '/uploads/';
                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0755, true);
                }
                $target_file = $target_dir . $safe_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $attachment_filename = $safe_name;
                } else {
                    $error_msg = "Failed to upload file. Please check server permissions.";
                    $upload_error = true;
                }
            }
        } else {
            $error_msg = "Error during file upload. Code: " . $file_err;
            $upload_error = true;
        }
    }

    // Validate required fields
    if (!$upload_error) {
        if (empty($title)) {
            $error_msg = "Task title is required.";
        } elseif (strlen($title) > 150) {
            $error_msg = "Task title cannot exceed 150 characters.";
        } elseif (empty($deadline)) {
            $error_msg = "Deadline date is required.";
        } elseif ($deadline < date('Y-m-d')) {
            $error_msg = "Enter a valid date. The due date cannot be in the past.";
        } elseif (!in_array($category, $allowed_categories, true)) {
            $error_msg = "Invalid category selected.";
        } elseif (!in_array($priority, $allowed_priorities, true)) {
            $error_msg = "Invalid priority selected.";
        } elseif (!in_array($status, $allowed_statuses, true)) {
            $error_msg = "Invalid status selected.";
        } elseif (!empty($submission_link) && !filter_var($submission_link, FILTER_VALIDATE_URL)) {
            $error_msg = "Please enter a valid URL for the Deliverable Link (e.g. https://github.com/...).";
        } else {
            // 4. Insert into database using Prepared Statement (CREATE)
            $sql = "INSERT INTO tasks (user_id, title, description, category, deadline, priority, status, attachment, submission_link) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issssssss", $assigned_to, $title, $description, $category, $deadline, $priority, $status, $attachment_filename, $submission_link);

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Task added successfully.";
                // Reset form inputs after success
                $title           = "";
                $description     = "";
                $category        = "General";
                $deadline        = "";
                $priority        = "Medium";
                $status          = "Pending";
                $submission_link = "";
            } else {
                $error_msg = "Failed to add task: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Add Task</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Navigation Bar -->
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
                <li><a href="add_task.php" class="active">+ Add Task</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main>
        <section class="form-container form-large">
            <h2>➕ Add New Task</h2>
            <p>Create a task, choose its category, assign deadlines, and attach deliverables.</p>

            <!-- Success Alert -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    ✓ <?php echo htmlspecialchars($success_msg); ?>
                    <a href="tasks.php" style="color: #047857; font-weight: 700; text-decoration: underline; margin-left: 8px;">View in Task List &rarr;</a>
                </div>
            <?php endif; ?>

            <!-- Error Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    ⚠️ <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Task Creation Form with file upload support -->
            <form action="add_task.php" method="POST" enctype="multipart/form-data" id="taskForm">
                
                <!-- If Manager/Admin, show Assignee selection -->
                <?php if ($user_role === 'admin'): ?>
                    <div class="form-group" style="background: rgba(79, 70, 229, 0.05); padding: 0.9rem; border-radius: var(--radius); border: 1px dashed var(--primary-color);">
                        <label for="assigned_to" style="color: var(--primary-color);">👤 Assign Task To Intern:</label>
                        <select id="assigned_to" name="assigned_to">
                            <?php foreach ($interns_list as $intern): ?>
                                <option value="<?php echo $intern['id']; ?>" <?php echo ($intern['id'] == $user_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($intern['name'] . ' (' . $intern['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Task Title -->
                <div class="form-group">
                    <label for="title">Task Title *</label>
                    <input type="text" id="title" name="title" maxlength="150" value="<?php echo htmlspecialchars($title); ?>" placeholder="e.g. Build REST API for authentication" required>
                    <small class="error-msg" id="titleError"></small>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Task Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe requirements, milestone steps, or notes..."><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <!-- Row 1: Category & Priority -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category / Domain *</label>
                        <select id="category" name="category">
                            <?php foreach ($allowed_categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select id="priority" name="priority">
                            <option value="Low" <?php echo ($priority === 'Low') ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo ($priority === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo ($priority === 'High') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Deadline & Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="deadline">Deadline Date *</label>
                        <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($deadline); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        <small class="error-msg" id="deadlineError"></small>
                    </div>

                    <div class="form-group">
                        <label for="status">Initial Status</label>
                        <select id="status" name="status">
                            <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo ($status === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Deliverable Link & Attachment -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="submission_link">Deliverable / Git URL (Optional)</label>
                        <input type="url" id="submission_link" name="submission_link" value="<?php echo htmlspecialchars($submission_link); ?>" placeholder="https://github.com/username/repo">
                    </div>

                    <div class="form-group">
                        <label for="attachment">Attachment File (PDF, Images, ZIP - max 5MB)</label>
                        <input type="file" id="attachment" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.txt" style="padding: 0.5rem;">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Task</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>

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
