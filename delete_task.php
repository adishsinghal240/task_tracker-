<?php
/**
 * ====================================================================
 * Internship Task Tracker - Delete Task (delete_task.php)
 * ====================================================================
 * DELETE Operation: Safely deletes a specific task record from MySQL
 * and removes any attached file from the uploads directory.
 * Admins can delete any task; interns can only delete their own tasks.
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
$task_id   = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

// 3. Execute Delete Query if ID is Valid
if ($task_id > 0) {
    // A. Check task existence & retrieve attached file
    if ($user_role === 'admin') {
        $f_stmt = mysqli_prepare($conn, "SELECT attachment FROM tasks WHERE id = ?");
        mysqli_stmt_bind_param($f_stmt, "i", $task_id);
    } else {
        $f_stmt = mysqli_prepare($conn, "SELECT attachment FROM tasks WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($f_stmt, "ii", $task_id, $user_id);
    }
    mysqli_stmt_execute($f_stmt);
    $res = mysqli_stmt_get_result($f_stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        // Clean up attached file from disk if present
        if (!empty($row['attachment'])) {
            $f_path = __DIR__ . '/uploads/' . $row['attachment'];
            if (file_exists($f_path)) {
                @unlink($f_path);
            }
        }
        mysqli_stmt_close($f_stmt);

        // B. Delete database record
        if ($user_role === 'admin') {
            $del_stmt = mysqli_prepare($conn, "DELETE FROM tasks WHERE id = ?");
            mysqli_stmt_bind_param($del_stmt, "i", $task_id);
        } else {
            $del_stmt = mysqli_prepare($conn, "DELETE FROM tasks WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($del_stmt, "ii", $task_id, $user_id);
        }
        mysqli_stmt_execute($del_stmt);
        mysqli_stmt_close($del_stmt);
    }
}

// 4. Redirect back to Task List
header("Location: tasks.php?msg=deleted");
exit();
?>
