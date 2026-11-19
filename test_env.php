<?php
/**
 * ====================================================================
 * Environment Diagnostics & System Verification (test_env.php)
 * ====================================================================
 * Automated diagnostic tool to verify PHP version, MySQL connectivity,
 * schema tables, column migrations, and uploads write permissions.
 */

session_start();

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'internship_task_tracker';
$db_port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

// Optional action: Quick Database Seeder
$seed_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'seed_db') {
    $conn_seed = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
    if ($conn_seed) {
        $sql_file = __DIR__ . '/database/internship_tracker.sql';
        if (file_exists($sql_file)) {
            $sql_contents = file_get_contents($sql_file);
            if (mysqli_multi_query($conn_seed, $sql_contents)) {
                do {
                    if ($res = mysqli_store_result($conn_seed)) {
                        mysqli_free_result($res);
                    }
                } while (mysqli_more_results($conn_seed) && mysqli_next_result($conn_seed));
                $seed_message = "✅ Database successfully seeded with demo accounts and sample tasks!";
            } else {
                $seed_message = "❌ Seeding failed: " . mysqli_error($conn_seed);
            }
        }
        mysqli_close($conn_seed);
    } else {
        $seed_message = "❌ Cannot connect to MySQL to seed database.";
    }
}

// 1. PHP Version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');

// 2. Extensions
$required_extensions = ['mysqli', 'session', 'json', 'filter'];
$ext_status = [];
foreach ($required_extensions as $ext) {
    $ext_status[$ext] = extension_loaded($ext);
}

// 3. MySQL Connection
$db_connected = false;
$db_error = "";
$tables_found = [];
$columns_found = [];
$users_count = 0;
$tasks_count = 0;

$test_conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($test_conn) {
    $db_connected = true;

    // Check tables
    $tables_res = mysqli_query($test_conn, "SHOW TABLES");
    if ($tables_res) {
        while ($row = mysqli_fetch_array($tables_res)) {
            $tables_found[] = $row[0];
        }
    }

    // Check columns
    if (in_array('users', $tables_found)) {
        $c_res = mysqli_query($test_conn, "SHOW COLUMNS FROM users");
        while ($c = mysqli_fetch_assoc($c_res)) {
            $columns_found['users'][] = $c['Field'];
        }
        $u_cnt_res = mysqli_query($test_conn, "SELECT COUNT(*) AS c FROM users");
        $users_count = mysqli_fetch_assoc($u_cnt_res)['c'] ?? 0;
    }

    if (in_array('tasks', $tables_found)) {
        $c_res = mysqli_query($test_conn, "SHOW COLUMNS FROM tasks");
        while ($c = mysqli_fetch_assoc($c_res)) {
            $columns_found['tasks'][] = $c['Field'];
        }
        $t_cnt_res = mysqli_query($test_conn, "SELECT COUNT(*) AS c FROM tasks");
        $tasks_count = mysqli_fetch_assoc($t_cnt_res)['c'] ?? 0;
    }

    mysqli_close($test_conn);
} else {
    $db_error = mysqli_connect_error();
}

// 4. Uploads Directory
$uploads_dir = __DIR__ . '/uploads';
$uploads_exists = is_dir($uploads_dir);
$uploads_writable = is_writable($uploads_dir);
$htaccess_exists = file_exists($uploads_dir . '/.htaccess');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - System Diagnostics</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .diag-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
        .diag-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); }
        .diag-item:last-child { border-bottom: none; }
        .badge-pass { background: #d1fae5; color: #065f46; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.8rem; }
        .badge-fail { background: #fee2e2; color: #991b1b; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.8rem; }
        .badge-warn { background: #fef3c7; color: #92400e; padding: 0.25rem 0.65rem; border-radius: 9999px; font-weight: 700; font-size: 0.8rem; }
    </style>
</head>
<body>

    <header>
        <nav>
            <div class="logo"><h2>📋 TaskTracker System Verification</h2></div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
            </ul>
        </nav>
    </header>

    <main style="max-width: 900px; margin: 2rem auto; padding: 0 1.5rem;">
        
        <div style="margin-bottom: 2rem;">
            <h1>🛠️ System Verification & Health Check</h1>
            <p style="color: var(--text-muted);">Use this diagnostics dashboard to ensure your local XAMPP or Docker environment meets all application requirements.</p>
        </div>

        <?php if (!empty($seed_message)): ?>
            <div style="background-color: #d1fae5; border-left: 5px solid #059669; color: #065f46; padding: 1rem 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 600;">
                <?php echo htmlspecialchars($seed_message); ?>
            </div>
        <?php endif; ?>

        <!-- 1. PHP Runtime & Extensions -->
        <div class="diag-card">
            <h3>1. PHP Runtime & Modules</h3>
            <div class="diag-item">
                <span>PHP Version (&ge; 7.4 recommended)</span>
                <div>
                    <strong><?php echo $php_version; ?></strong>
                    <span class="<?php echo $php_ok ? 'badge-pass' : 'badge-warn'; ?>"><?php echo $php_ok ? 'PASS' : 'UPGRADE REC'; ?></span>
                </div>
            </div>
            <?php foreach ($ext_status as $ext => $loaded): ?>
                <div class="diag-item">
                    <span>Extension <code><?php echo $ext; ?></code></span>
                    <span class="<?php echo $loaded ? 'badge-pass' : 'badge-fail'; ?>"><?php echo $loaded ? 'INSTALLED' : 'MISSING'; ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 2. Database Connectivity -->
        <div class="diag-card">
            <h3>2. MySQL Database Connection</h3>
            <div class="diag-item">
                <span>Database Host & Port</span>
                <strong><?php echo htmlspecialchars($db_host . ':' . $db_port); ?></strong>
            </div>
            <div class="diag-item">
                <span>Target Database Name</span>
                <strong><?php echo htmlspecialchars($db_name); ?></strong>
            </div>
            <div class="diag-item">
                <span>Connection Status</span>
                <?php if ($db_connected): ?>
                    <span class="badge-pass">CONNECTED (OK)</span>
                <?php else: ?>
                    <span class="badge-fail">FAILED</span>
                <?php endif; ?>
            </div>
            <?php if (!$db_connected): ?>
                <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: var(--radius); margin-top: 1rem; font-size: 0.9rem;">
                    <strong>Connection Error:</strong> <?php echo htmlspecialchars($db_error); ?><br><br>
                    <strong>Troubleshooting:</strong><br>
                    1. Open XAMPP Control Panel and start the <strong>MySQL</strong> service.<br>
                    2. Open <code>http://localhost/phpmyadmin/</code> and create database <code>internship_task_tracker</code>.<br>
                    3. Click "Import" and select <code>database/internship_tracker.sql</code>.
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Tables & Schemas -->
        <?php if ($db_connected): ?>
            <div class="diag-card">
                <h3>3. Database Tables & Columns</h3>
                <div class="diag-item">
                    <span><code>users</code> table</span>
                    <span class="<?php echo in_array('users', $tables_found) ? 'badge-pass' : 'badge-fail'; ?>">
                        <?php echo in_array('users', $tables_found) ? "PRESENT ({$users_count} records)" : 'NOT FOUND'; ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span><code>tasks</code> table</span>
                    <span class="<?php echo in_array('tasks', $tables_found) ? 'badge-pass' : 'badge-fail'; ?>">
                        <?php echo in_array('tasks', $tables_found) ? "PRESENT ({$tasks_count} records)" : 'NOT FOUND'; ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span><code>users.role</code> column (Manager role)</span>
                    <span class="<?php echo (isset($columns_found['users']) && in_array('role', $columns_found['users'])) ? 'badge-pass' : 'badge-warn'; ?>">
                        <?php echo (isset($columns_found['users']) && in_array('role', $columns_found['users'])) ? 'ACTIVE' : 'WILL AUTO-ADD'; ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span><code>tasks.category</code> column (Categories)</span>
                    <span class="<?php echo (isset($columns_found['tasks']) && in_array('category', $columns_found['tasks'])) ? 'badge-pass' : 'badge-warn'; ?>">
                        <?php echo (isset($columns_found['tasks']) && in_array('category', $columns_found['tasks'])) ? 'ACTIVE' : 'WILL AUTO-ADD'; ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span><code>tasks.attachment</code> column (File Uploads)</span>
                    <span class="<?php echo (isset($columns_found['tasks']) && in_array('attachment', $columns_found['tasks'])) ? 'badge-pass' : 'badge-warn'; ?>">
                        <?php echo (isset($columns_found['tasks']) && in_array('attachment', $columns_found['tasks'])) ? 'ACTIVE' : 'WILL AUTO-ADD'; ?>
                    </span>
                </div>
                <div class="diag-item">
                    <span><code>tasks.submission_link</code> column (Git / URLs)</span>
                    <span class="<?php echo (isset($columns_found['tasks']) && in_array('submission_link', $columns_found['tasks'])) ? 'badge-pass' : 'badge-warn'; ?>">
                        <?php echo (isset($columns_found['tasks']) && in_array('submission_link', $columns_found['tasks'])) ? 'ACTIVE' : 'WILL AUTO-ADD'; ?>
                    </span>
                </div>

                <!-- Seeder Action Button -->
                <div style="margin-top: 1.5rem; text-align: center;">
                    <form method="POST">
                        <input type="hidden" name="action" value="seed_db">
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.88rem; width: auto;">⚡ Seed / Reset Demo Data (Accounts + Tasks)</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. Storage & Upload Permissions -->
        <div class="diag-card">
            <h3>4. Upload Storage Permissions</h3>
            <div class="diag-item">
                <span><code>uploads/</code> directory exists</span>
                <span class="<?php echo $uploads_exists ? 'badge-pass' : 'badge-fail'; ?>"><?php echo $uploads_exists ? 'YES' : 'NO'; ?></span>
            </div>
            <div class="diag-item">
                <span><code>uploads/</code> is writable by PHP</span>
                <span class="<?php echo $uploads_writable ? 'badge-pass' : 'badge-fail'; ?>"><?php echo $uploads_writable ? 'WRITABLE' : 'READ-ONLY'; ?></span>
            </div>
            <div class="diag-item">
                <span>Uploads script execution protection (<code>.htaccess</code>)</span>
                <span class="<?php echo $htaccess_exists ? 'badge-pass' : 'badge-warn'; ?>"><?php echo $htaccess_exists ? 'ACTIVE' : 'NOT FOUND'; ?></span>
            </div>
        </div>

        <!-- Quick Jump Links -->
        <div style="display: flex; gap: 1rem; justify-content: center; margin: 2rem 0;">
            <a href="login.php" class="btn btn-primary" style="width: auto; padding: 0.75rem 1.5rem;">Go to Login &rarr;</a>
            <a href="register.php" class="btn btn-secondary" style="width: auto; padding: 0.75rem 1.5rem;">Register New Intern</a>
        </div>

    </main>

    <footer>
        <p>&copy; 2026 Internship Task Tracker | Diagnostics Utility</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>

