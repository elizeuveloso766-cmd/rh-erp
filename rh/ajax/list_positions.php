<?php
require_once '../../../app/config/db.php';
session_start();
header('Content-Type: application/json');
$company_id = $_SESSION['user']['company_id'] ?? null;

if (!$company_id) {
    echo json_encode(['data' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, suggested_salary, food_allowance, transport_allowance, vacation_subsidy_pct, thirteenth_subsidy_pct FROM positions WHERE company_id = ?");
$stmt->execute([$company_id]);

$positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $positions]);
