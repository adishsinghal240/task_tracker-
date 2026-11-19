<?php
/**
 * ====================================================================
 * Internship Task Tracker - View, Search & Filter Tasks (tasks.php)
 * ====================================================================
 * READ + SEARCH + MULTI-CRITERIA FILTERING:
 * Supports Title search, Category filter, Status filter, Priority filter,
 * Smart Due Soon / Overdue Reminders, Deliverables & Attachments.
 * If logged in as Manager/Admin, displays all interns' tasks with Assignee names.
 */

// 1. Start Session & Check Authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Include Database Connection
require_once 'includes/db.php';

$user_id     = $_SESSION['user_id'];
$user_role   = $_SESSION['user_role'] ?? 'intern';
$deleted_msg = (isset($_GET['msg']) && $_GET['msg'] === 'deleted');

// 3. Capture Search & Filter GET Parameters
$search    = trim($_GET['search'] ?? '');
$category  = trim($_GET['category'] ?? '');
$status    = trim($_GET['status'] ?? '');
$priority  = trim($_GET['priority'] ?? '');
$intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;

// 4. Build Dynamic SQL Query
if ($user_role === 'admin') {
    $sql = "SELECT t.id, t.user_id, t.title, t.description, t.category, t.deadline, t.priority, t.status, t.attachment, t.submission_link, t.created_at, u.name AS intern_name, u.email AS intern_email 
            FROM tasks t 
            JOIN users u ON t.user_id = u.id 
            WHERE 1=1";
    $types  = "";
    $params = [];

    if ($intern_id > 0) {
        $sql .= " AND t.user_id = ?";
        $types .= "i";
        $params[] = $intern_id;
    }
} else {
    $sql = "SELECT t.id, t.user_id, t.title, t.description, t.category, t.deadline, t.priority, t.status, t.attachment, t.submission_link, t.created_at, '' AS intern_name, '' AS intern_email 
            FROM tasks t 
            WHERE t.user_id = ?";
    $types  = "i";
    $params = [$user_id];
}

// Search keyword in title or description
if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $types .= "ss";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}

// Category filter
if (!empty($category)) {
    $sql .= " AND t.category = ?";
    $types .= "s";
    $params[] = $category;
}

// Status filter
if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $types .= "s";
    $params[] = $status;
}

// Priority filter
if (!empty($priority)) {
    $sql .= " AND t.priority = ?";
    $types .= "s";
    $params[] = $priority;
}

// Order results by deadline ascending (soonest due dates first)
$sql .= " ORDER BY t.deadline ASC, t.created_at DESC";

// 5. Execute Prepared Statement
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch interns list if Admin for the filter dropdown
$all_interns = [];
if ($user_role === 'admin') {
    $interns_res = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
    if ($interns_res) {
        while ($row = mysqli_fetch_assoc($interns_res)) {
            $all_interns[] = $row;
        }
    }
}

$allowed_categories = ['Development', 'Design', 'Testing', 'Research', 'Documentation', 'General'];
$today_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - All Tasks</title>
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
                <li><a href="tasks.php" class="active">All Tasks</a></li>
                <li><a href="add_task.php">+ Add Task</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main>
        
        <!-- Page Title & Export Actions -->
        <div class="page-header">
            <div>
                <h1><?php echo ($user_role === 'admin') ? 'All Intern Tasks (Manager View)' : 'My Internship Tasks'; ?></h1>
                <p style="color: var(--text-muted);">Manage your milestones, track deliverable progress, and monitor upcoming deadlines.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="export_csv.php" class="btn btn-secondary" style="font-size: 0.88rem; padding: 0.6rem 1rem;">📥 Export CSV</a>
                <a href="add_task.php" class="btn btn-primary" style="font-size: 0.88rem; padding: 0.6rem 1.2rem; width: auto;">+ New Task</a>
            </div>
        </div>

        <!-- Deletion Confirmation Message -->
        <?php if ($deleted_msg): ?>
            <div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 0.75rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500;">
                ✓ Task was successfully deleted.
            </div>
        <?php endif; ?>

        <!-- Search & Multi-Criteria Filter Bar -->
        <section class="filter-bar">
            <form action="tasks.php" method="GET" class="filter-form">
                
                <!-- Keyword Search -->
                <div class="filter-group search-group">
                    <label for="search">Search Keywords</label>
                    <input type="text" id="liveTaskSearch" name="search" placeholder="Type to filter titles or notes..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- Manager View: Filter by Intern -->
                <?php if ($user_role === 'admin'): ?>
                    <div class="filter-group">
                        <label for="intern_id">Intern</label>
                        <select id="intern_id" name="intern_id">
                            <option value="">All Interns</option>
                            <?php foreach ($all_interns as $i): ?>
                                <option value="<?php echo $i['id']; ?>" <?php echo ($intern_id === (int)$i['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($i['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($allowed_categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo ($status === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo ($status === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo ($status === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="filter-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="">All Priorities</option>
                        <option value="Low" <?php echo ($priority === 'Low') ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?php echo ($priority === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?php echo ($priority === 'High') ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.25rem;">Filter</button>
                    <a href="tasks.php" class="btn btn-reset">Reset</a>
                </div>

            </form>
        </section>

        <!-- Tasks Table Section -->
        <section class="table-container">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <?php if ($user_role === 'admin'): ?>
                                <th>Intern</th>
                            <?php endif; ?>
                            <th>Task Title</th>
                            <th>Category</th>
                            <th>Deadline & Smart Alert</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Deliverables</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serial = 1;
                        $today_obj = new DateTime('today');

                        while ($task = mysqli_fetch_assoc($result)): 
                            $priorityClass = strtolower($task['priority']);
                            $statusClass   = strtolower(str_replace(' ', '-', $task['status']));
                            $catClass      = strtolower($task['category'] ?? 'general');
                            $task_deadline = $task['deadline'];

                            // Calculate smart due reminder
                            $due_obj = new DateTime($task_deadline);
                            $diff_days = (int)$today_obj->diff($due_obj)->format("%r%a");

                            $smart_badge = "";
                            if ($task['status'] !== 'Completed') {
                                if ($diff_days < 0) {
                                    $smart_badge = '<span class="badge-deadline overdue">⚠️ Overdue (' . abs($diff_days) . 'd)</span>';
                                } elseif ($diff_days === 0) {
                                    $smart_badge = '<span class="badge-deadline due-today">⏳ Due Today</span>';
                                } elseif ($diff_days <= 2) {
                                    $smart_badge = '<span class="badge-deadline due-soon">🔔 Due in ' . $diff_days . 'd</span>';
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo $serial++; ?></td>
                                
                                <?php if ($user_role === 'admin'): ?>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['intern_name']); ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($task['intern_email']); ?></div>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <?php if (!empty($task['description'])): ?>
                                        <div style="font-size: 0.82rem; color: var(--text-muted); max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($task['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="badge category-<?php echo $catClass; ?>">
                                        <?php echo htmlspecialchars($task['category'] ?? 'General'); ?>
                                    </span>
                                </td>

                                <td>
                                    <span><?php echo date('M d, Y', strtotime($task_deadline)); ?></span>
                                    <?php echo $smart_badge; ?>
                                </td>

                                <td>
                                    <span class="badge priority-<?php echo $priorityClass; ?>">
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge status-<?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </td>

                                <!-- Deliverables (Files & Links) -->
                                <td>
                                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        <?php if (!empty($task['attachment'])): ?>
                                            <a href="uploads/<?php echo urlencode($task['attachment']); ?>" target="_blank" class="btn-action" style="background-color: #ede9fe; color: #6d28d9; padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="Download Attachment">
                                                📎 File
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($task['submission_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($task['submission_link']); ?>" target="_blank" class="btn-action" style="background-color: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="View Deliverable URL">
                                                🔗 Link
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($task['attachment']) && empty($task['submission_link'])): ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn-action edit">Edit</a>
                                    <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn-action delete" data-task-title="<?php echo htmlspecialchars($task['title']); ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <h3>No matching tasks found</h3>
                    <p style="margin: 0.5rem 0 1.5rem;">Try adjusting your keyword filter or clearing search criteria.</p>
                    <a href="tasks.php" class="btn btn-secondary" style="width: auto; display: inline-block;">Clear Filters</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker </p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
<?php 
mysqli_stmt_close($stmt); 
?>
