<?php
require_once '../../../app/config/db.php';
session_start();

$company_id = $_SESSION['user']['company_id'] ?? null;
if (!$company_id) exit;

$name = $_POST['name'] ?? '';
$suggested_salary = $_POST['suggested_salary'] ?? 0;
$food_allowance = $_POST['food_allowance'] ?? 0;
$transport_allowance = $_POST['transport_allowance'] ?? 0;
$vacation_subsidy_pct = (int)($_POST['vacation_subsidy_pct'] ?? 0);
$thirteenth_subsidy_pct = (int)($_POST['thirteenth_subsidy_pct'] ?? 0);
$id = $_POST['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("UPDATE positions SET name = ?, suggested_salary = ?, food_allowance = ?, transport_allowance = ?, vacation_subsidy_pct = ?, thirteenth_subsidy_pct = ? WHERE id = ? AND company_id = ?");
    $stmt->execute([$name, $suggested_salary, $food_allowance, $transport_allowance, $vacation_subsidy_pct, $thirteenth_subsidy_pct, $id, $company_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO positions (company_id, name, suggested_salary, food_allowance, transport_allowance, vacation_subsidy_pct, thirteenth_subsidy_pct) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$company_id, $name, $suggested_salary, $food_allowance, $transport_allowance, $vacation_subsidy_pct, $thirteenth_subsidy_pct]);
}

echo 'ok';
