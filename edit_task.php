<?php
/**
 * ====================================================================
 * Internship Task Tracker - Edit Task (edit_task.php)
 * ====================================================================
 * UPDATE Operation: Fetches existing task details, supports Category,
 * File Attachments, Submission Links, and smart overdue handling.
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
$task_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;

$success_msg = "";
$error_msg   = "";

if ($task_id <= 0) {
    header("Location: tasks.php");
    exit();
}

// Allowed values
$allowed_categories = ['Development', 'Design', 'Testing', 'Research', 'Documentation', 'General'];
$allowed_priorities = ['Low', 'Medium', 'High'];
$allowed_statuses   = ['Pending', 'In Progress', 'Completed'];

// 3. Fetch existing task record FIRST
// If Admin, can edit any task; if Intern, can only edit own task
if ($user_role === 'admin') {
    $fetch_sql = "SELECT id, user_id, title, description, category, deadline, priority, status, attachment, submission_link FROM tasks WHERE id = ?";
    $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
    mysqli_stmt_bind_param($fetch_stmt, "i", $task_id);
} else {
    $fetch_sql = "SELECT id, user_id, title, description, category, deadline, priority, status, attachment, submission_link FROM tasks WHERE id = ? AND user_id = ?";
    $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
    mysqli_stmt_bind_param($fetch_stmt, "ii", $task_id, $user_id);
}

mysqli_stmt_execute($fetch_stmt);
$result = mysqli_stmt_get_result($fetch_stmt);

if (!$task = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($fetch_stmt);
    header("Location: tasks.php");
    exit();
}
mysqli_stmt_close($fetch_stmt);

// Fetch interns if admin
$interns_list = [];
if ($user_role === 'admin') {
    $interns_query = mysqli_query($conn, "SELECT id, name, email FROM users ORDER BY name ASC");
    if ($interns_query) {
        while ($intern = mysqli_fetch_assoc($interns_query)) {
            $interns_list[] = $intern;
        }
    }
}

// 4. Process Form Submission (POST - UPDATE)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $category        = trim($_POST['category'] ?? 'General');
    $deadline        = trim($_POST['deadline'] ?? '');
    $priority        = $_POST['priority'] ?? 'Medium';
    $status          = $_POST['status'] ?? 'Pending';
    $submission_link = trim($_POST['submission_link'] ?? '');
    $target_user_id  = $task['user_id'];

    if ($user_role === 'admin' && isset($_POST['assigned_to'])) {
        $reassigned = intval($_POST['assigned_to']);
        if ($reassigned > 0) $target_user_id = $reassigned;
    }

    // Attachment handling
    $attachment_filename = $task['attachment'];
    $upload_error = false;

    // Check if user requested to remove existing attachment
    if (isset($_POST['remove_attachment']) && $_POST['remove_attachment'] === '1') {
        if (!empty($task['attachment'])) {
            $file_to_del = __DIR__ . '/uploads/' . $task['attachment'];
            if (file_exists($file_to_del)) {
                @unlink($file_to_del);
            }
        }
        $attachment_filename = null;
    }

    // Check for newly uploaded replacement attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['attachment'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg', 'zip', 'docx', 'txt'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_exts, true)) {
                $error_msg = "Invalid file type. Allowed: PDF, PNG, JPG, JPEG, ZIP, DOCX, TXT.";
                $upload_error = true;
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error_msg = "File is too large. Maximum size is 5MB.";
                $upload_error = true;
            } else {
                $safe_name = time() . '_' . bin2hex(random_bytes(6)) . '.' . $file_ext;
                $target_dir = __DIR__ . '/uploads/';
                if (!is_dir($target_dir)) {
                    @mkdir($target_dir, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $target_dir . $safe_name)) {
                    // Delete old attachment if existing
                    if (!empty($task['attachment'])) {
                        @unlink($target_dir . $task['attachment']);
                    }
                    $attachment_filename = $safe_name;
                } else {
                    $error_msg = "Failed to upload file. Please check folder permissions.";
                    $upload_error = true;
                }
            }
        }
    }

    // Validations
    if (!$upload_error) {
        if (empty($title)) {
            $error_msg = "Task title cannot be empty.";
        } elseif (strlen($title) > 150) {
            $error_msg = "Task title cannot exceed 150 characters.";
        } elseif (empty($deadline)) {
            $error_msg = "Deadline date cannot be empty.";
        } elseif (!in_array($category, $allowed_categories, true)) {
            $error_msg = "Invalid category selected.";
        } elseif (!in_array($priority, $allowed_priorities, true)) {
            $error_msg = "Invalid priority selected.";
        } elseif (!in_array($status, $allowed_statuses, true)) {
            $error_msg = "Invalid status selected.";
        } elseif (!empty($submission_link) && !filter_var($submission_link, FILTER_VALIDATE_URL)) {
            $error_msg = "Please enter a valid URL for the Deliverable Link.";
        } elseif ($deadline !== $task['deadline'] && $deadline < date('Y-m-d') && $status !== 'Completed') {
            $error_msg = "Enter a valid date. New deadlines cannot be in the past unless the task is marked Completed.";
        } else {
            // Execute SQL UPDATE query
            $update_sql = "UPDATE tasks SET user_id = ?, title = ?, description = ?, category = ?, deadline = ?, priority = ?, status = ?, attachment = ?, submission_link = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "issssssssi", $target_user_id, $title, $description, $category, $deadline, $priority, $status, $attachment_filename, $submission_link, $task_id);

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Task updated successfully.";
                $task['user_id']         = $target_user_id;
                $task['title']           = $title;
                $task['description']     = $description;
                $task['category']        = $category;
                $task['deadline']        = $deadline;
                $task['priority']        = $priority;
                $task['status']          = $status;
                $task['attachment']      = $attachment_filename;
                $task['submission_link'] = $submission_link;
            } else {
                $error_msg = "Failed to update task: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$today_date = date('Y-m-d');
$is_overdue = ($task['status'] !== 'Completed' && $task['deadline'] < $today_date);
$is_today   = ($task['status'] !== 'Completed' && $task['deadline'] === $today_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Edit Task</title>
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
                <li><a href="profile.php">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main>
        <section class="form-container form-large">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                <h2>✏️ Edit Task #<?php echo $task['id']; ?></h2>
                <div>
                    <?php if ($is_overdue): ?>
                        <span class="badge-deadline overdue">⚠️ Overdue Task</span>
                    <?php elseif ($is_today): ?>
                        <span class="badge-deadline due-today">⏳ Due Today</span>
                    <?php elseif ($task['status'] === 'Completed'): ?>
                        <span class="badge status-completed">✓ Completed</span>
                    <?php endif; ?>
                </div>
            </div>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Modify task specifications, category, deadlines, and deliverables.</p>

            <!-- Success Alert -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    ✓ <?php echo htmlspecialchars($success_msg); ?>
                    <a href="tasks.php" style="color: #047857; font-weight: 700; text-decoration: underline; margin-left: 8px;">Back to Tasks &rarr;</a>
                </div>
            <?php endif; ?>

            <!-- Error Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-weight: 500;">
                    ⚠️ <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Edit Form pre-filled with database values -->
            <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST" enctype="multipart/form-data" id="taskForm" data-mode="edit">
                
                <!-- If Manager/Admin, show Assignee selection -->
                <?php if ($user_role === 'admin'): ?>
                    <div class="form-group" style="background: rgba(79, 70, 229, 0.05); padding: 0.9rem; border-radius: var(--radius); border: 1px dashed var(--primary-color);">
                        <label for="assigned_to" style="color: var(--primary-color);">👤 Assigned Intern:</label>
                        <select id="assigned_to" name="assigned_to">
                            <?php foreach ($interns_list as $intern): ?>
                                <option value="<?php echo $intern['id']; ?>" <?php echo ($intern['id'] == $task['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($intern['name'] . ' (' . $intern['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Task Title -->
                <div class="form-group">
                    <label for="title">Task Title *</label>
                    <input type="text" id="title" name="title" maxlength="150" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                    <small class="error-msg" id="titleError"></small>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description">Task Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>

                <!-- Row 1: Category & Priority -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category / Domain *</label>
                        <select id="category" name="category">
                            <?php foreach ($allowed_categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($task['category'] === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select id="priority" name="priority">
                            <option value="Low" <?php echo ($task['priority'] === 'Low') ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo ($task['priority'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo ($task['priority'] === 'High') ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Deadline & Status -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="deadline">
                            Deadline Date *
                            <?php if ($is_overdue): ?>
                                <small style="color: var(--danger-color); font-weight: 600;">(Overdue date kept)</small>
                            <?php endif; ?>
                        </label>
                        <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($task['deadline']); ?>" required>
                        <small class="error-msg" id="deadlineError"></small>
                    </div>

                    <div class="form-group">
                        <label for="status">Current Status</label>
                        <select id="status" name="status">
                            <option value="Pending" <?php echo ($task['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo ($task['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo ($task['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Deliverable Link & File Attachment -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="submission_link">Deliverable / Git URL (Optional)</label>
                        <input type="url" id="submission_link" name="submission_link" value="<?php echo htmlspecialchars($task['submission_link'] ?? ''); ?>" placeholder="https://github.com/username/repo">
                    </div>

                    <div class="form-group">
                        <label for="attachment">Attachment File (Upload new to replace)</label>
                        <input type="file" id="attachment" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.zip,.docx,.txt" style="padding: 0.5rem;">
                        <?php if (!empty($task['attachment'])): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span>📎 Current: <a href="uploads/<?php echo urlencode($task['attachment']); ?>" target="_blank" style="color: var(--primary-color); font-weight: 600; text-decoration: underline;"><?php echo htmlspecialchars($task['attachment']); ?></a></span>
                                <label style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: normal; cursor: pointer; color: var(--danger-color); font-size: 0.8rem;">
                                    <input type="checkbox" name="remove_attachment" value="1" style="width: auto;"> Remove Attachment
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Task</button>
                    <a href="tasks.php" class="btn btn-secondary">Cancel</a>
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
