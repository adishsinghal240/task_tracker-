<?php
/**
 * ====================================================================
 * Internship Task Tracker - Export Tasks to CSV (export_csv.php)
 * ====================================================================
 * Exports task records into a well-formatted CSV file with Category,
 * Deadlines, Priority, Status, Deliverables, and Intern information.
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db.php';

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'intern';

if ($user_role === 'admin') {
    $stmt = mysqli_prepare($conn, 'SELECT t.id, u.name AS intern_name, u.email AS intern_email, t.title, t.description, t.category, t.deadline, t.priority, t.status, t.attachment, t.submission_link, t.created_at 
                                   FROM tasks t 
                                   JOIN users u ON t.user_id = u.id 
                                   ORDER BY t.deadline ASC');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cohort_tasks_' . date('Y-m-d_H-i') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Task ID', 'Intern Name', 'Intern Email', 'Title', 'Description', 'Category', 'Deadline', 'Priority', 'Status', 'Attachment File', 'Submission Link', 'Created At']);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, [
            $row['id'],
            $row['intern_name'],
            $row['intern_email'],
            $row['title'],
            $row['description'],
            $row['category'],
            $row['deadline'],
            $row['priority'],
            $row['status'],
            $row['attachment'],
            $row['submission_link'],
            $row['created_at']
        ]);
    }
} else {
    $stmt = mysqli_prepare($conn, 'SELECT id, title, description, category, deadline, priority, status, attachment, submission_link, created_at 
                                   FROM tasks 
                                   WHERE user_id = ? 
                                   ORDER BY deadline ASC');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="my_tasks_' . date('Y-m-d_H-i') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Task ID', 'Title', 'Description', 'Category', 'Deadline', 'Priority', 'Status', 'Attachment File', 'Submission Link', 'Created At']);

    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($out, [
            $row['id'],
            $row['title'],
            $row['description'],
            $row['category'],
            $row['deadline'],
            $row['priority'],
            $row['status'],
            $row['attachment'],
            $row['submission_link'],
            $row['created_at']
        ]);
    }
}

fclose($out);
mysqli_stmt_close($stmt);
exit();
?>
