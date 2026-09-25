<?php
require_once '../../../app/config/db.php';
session_start();
$company_id = $_SESSION['user']['company_id'] ?? null;
$id = $_POST['id'] ?? null;
$employee_id = $_POST['employee_id'];

$date = $_POST['date'];
$type = $_POST['type'];
$justification = $_POST['justification'];

if ($id) {
    $stmt = $pdo->prepare("
        UPDATE attendance SET employee_id=?, company_id=?, date=?, type=?, justification=? WHERE id=?
    ");
    $stmt->execute([$employee_id, $company_id, $date, $type, $justification, $id]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO attendance (employee_id, company_id, date, type, justification)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$employee_id, $company_id, $date, $type, $justification]);
}

echo json_encode(['success' => true]);
