<?php
/**
 * ====================================================================
 * Internship Task Tracker - Manager & Admin Dashboard (admin_dashboard.php)
 * ====================================================================
 * Protected Page for Mentors/Managers/Admins:
 * Monitors fleet performance, intern progress, task completions, and overdue items.
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Restrict access strictly to Admin role
if (($_SESSION['user_role'] ?? 'intern') !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'includes/db.php';

$admin_id   = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];

// 1. Overall Metrics across all interns
// Total Interns
$res_interns = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users WHERE role = 'intern'");
$total_interns = mysqli_fetch_assoc($res_interns)['count'] ?? 0;

// Total Tasks
$res_tasks = mysqli_query($conn, "SELECT COUNT(*) AS count FROM tasks");
$total_tasks = mysqli_fetch_assoc($res_tasks)['count'] ?? 0;

// Total Completed Tasks
$res_completed = mysqli_query($conn, "SELECT COUNT(*) AS count FROM tasks WHERE status = 'Completed'");
$completed_tasks = mysqli_fetch_assoc($res_completed)['count'] ?? 0;

// Total Overdue Tasks
$res_overdue = mysqli_query($conn, "SELECT COUNT(*) AS count FROM tasks WHERE status != 'Completed' AND deadline < CURDATE()");
$total_overdue = mysqli_fetch_assoc($res_overdue)['count'] ?? 0;

// Overall Company Completion Percentage
$company_progress = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;

// 2. Fetch Interns Performance Breakdown
$intern_sql = "SELECT u.id, u.name, u.email, u.created_at,
               COUNT(t.id) AS total_tasks,
               SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
               SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
               SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
               SUM(CASE WHEN t.status != 'Completed' AND t.deadline < CURDATE() THEN 1 ELSE 0 END) AS overdue_count
               FROM users u
               LEFT JOIN tasks t ON u.id = t.user_id
               WHERE u.role = 'intern'
               GROUP BY u.id, u.name, u.email, u.created_at
               ORDER BY u.name ASC";
$intern_res = mysqli_query($conn, $intern_sql);

// 3. Fetch Recent Deliverable Submissions across all interns
$recent_sub_sql = "SELECT t.id, t.title, t.category, t.status, t.deadline, t.attachment, t.submission_link, u.name AS intern_name 
                   FROM tasks t 
                   JOIN users u ON t.user_id = u.id 
                   WHERE t.attachment IS NOT NULL OR t.submission_link IS NOT NULL 
                   ORDER BY t.created_at DESC 
                   LIMIT 5";
$recent_sub_res = mysqli_query($conn, $recent_sub_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Manager & Admin Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header & Navigation Bar -->
    <header>
        <nav>
            <div class="logo">
                <h2>📋 TaskTracker <span style="font-size: 0.75rem; background: var(--primary-color); color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; vertical-align: middle; margin-left: 0.4rem;">Manager</span></h2>
            </div>
            <ul>
                <li><a href="admin_dashboard.php" class="active">Manager Panel</a></li>
                <li><a href="dashboard.php">My View</a></li>
                <li><a href="tasks.php">All Tasks</a></li>
                <li><a href="add_task.php">+ Assign Task</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="dashboard-layout">
        
        <!-- Welcome Greeting Banner -->
        <section class="welcome-banner">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1>Manager & Mentor Control Center 👔</h1>
                    <p>Logged in as <strong><?php echo htmlspecialchars($admin_name); ?></strong>. Review intern workloads and track milestones.</p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="add_task.php" class="btn btn-primary" style="width: auto; padding: 0.6rem 1.25rem;">+ Assign New Task</a>
                    <a href="tasks.php" class="btn btn-secondary" style="width: auto; padding: 0.6rem 1.25rem;">View Full Master List</a>
                </div>
            </div>
        </section>

        <!-- Company Metrics Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">🎓</div>
                <div class="stat-info">
                    <h3>Active Interns</h3>
                    <p class="stat-number"><?php echo $total_interns; ?></p>
                </div>
            </div>

            <div class="stat-card in-progress">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <h3>Total Assigned</h3>
                    <p class="stat-number"><?php echo $total_tasks; ?></p>
                </div>
            </div>

            <div class="stat-card completed">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Completed Tasks</h3>
                    <p class="stat-number"><?php echo $completed_tasks; ?></p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon" style="color: #dc2626;">⚠️</div>
                <div class="stat-info">
                    <h3>Overdue Tasks</h3>
                    <p class="stat-number" style="color: #dc2626;"><?php echo $total_overdue; ?></p>
                </div>
            </div>
        </section>

        <!-- Company Milestone Progress Card -->
        <section class="progress-card">
            <div class="progress-header">
                <h3>Overall Cohort Task Completion</h3>
                <span class="progress-percent"><?php echo $company_progress; ?>% Completed</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $company_progress; ?>%;"></div>
            </div>
        </section>

        <!-- Interns Performance Table -->
        <section class="table-container" style="margin-bottom: 2rem;">
            <h2>🎓 Intern Roster & Progress Breakdown</h2>
            <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.9rem;">Monitor individual milestone completion rates and overdue workloads.</p>

            <table>
                <thead>
                    <tr>
                        <th>Intern Name</th>
                        <th>Email</th>
                        <th>Total Tasks</th>
                        <th>Completed</th>
                        <th>In Progress</th>
                        <th>Overdue</th>
                        <th>Progress Rate</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($intern_res && mysqli_num_rows($intern_res) > 0): ?>
                        <?php while ($intern = mysqli_fetch_assoc($intern_res)): 
                            $i_total = (int)$intern['total_tasks'];
                            $i_comp  = (int)$intern['completed_count'];
                            $i_over  = (int)$intern['overdue_count'];
                            $i_rate  = ($i_total > 0) ? round(($i_comp / $i_total) * 100) : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($intern['name']); ?></strong></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($intern['email']); ?></td>
                                <td><?php echo $i_total; ?></td>
                                <td><span class="badge status-completed"><?php echo $i_comp; ?></span></td>
                                <td><span class="badge status-in-progress"><?php echo (int)$intern['in_progress_count']; ?></span></td>
                                <td>
                                    <?php if ($i_over > 0): ?>
                                        <span class="badge-deadline overdue"><?php echo $i_over; ?> Overdue</span>
                                    <?php else: ?>
                                        <span style="color: var(--success-color); font-weight: 600; font-size: 0.85rem;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width: 140px;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div class="progress-bar-bg" style="height: 8px; flex: 1;">
                                            <div class="progress-bar-fill" style="width: <?php echo $i_rate; ?>%;"></div>
                                        </div>
                                        <span style="font-size: 0.8rem; font-weight: 700;"><?php echo $i_rate; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="tasks.php?intern_id=<?php echo $intern['id']; ?>" class="btn-action edit" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">View Tasks &rarr;</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No interns registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Recent Deliverables / Submissions Section -->
        <section class="table-container">
            <h2>📎 Recent Deliverables & Submissions</h2>
            <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.9rem;">Quickly inspect submitted files and repository links from your interns.</p>

            <table>
                <thead>
                    <tr>
                        <th>Intern</th>
                        <th>Task Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Deliverable Access</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_sub_res && mysqli_num_rows($recent_sub_res) > 0): ?>
                        <?php while ($sub = mysqli_fetch_assoc($recent_sub_res)): 
                            $catClass = strtolower($sub['category'] ?? 'general');
                            $statClass = strtolower(str_replace(' ', '-', $sub['status']));
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sub['intern_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sub['title']); ?></td>
                                <td><span class="badge category-<?php echo $catClass; ?>"><?php echo htmlspecialchars($sub['category']); ?></span></td>
                                <td><span class="badge status-<?php echo $statClass; ?>"><?php echo htmlspecialchars($sub['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($sub['deadline'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <?php if (!empty($sub['attachment'])): ?>
                                            <a href="uploads/<?php echo urlencode($sub['attachment']); ?>" target="_blank" class="btn-action" style="background-color: #ede9fe; color: #6d28d9; padding: 0.2rem 0.5rem; font-size: 0.78rem;">
                                                📎 Open File
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($sub['submission_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($sub['submission_link']); ?>" target="_blank" class="btn-action" style="background-color: #e0f2fe; color: #0369a1; padding: 0.2rem 0.5rem; font-size: 0.78rem;">
                                                🔗 Git / URL
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="edit_task.php?id=<?php echo $sub['id']; ?>" class="btn-action edit">Review / Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No deliverables submitted yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker </p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>

