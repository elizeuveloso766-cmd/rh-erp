<?php
require_once '../../../app/config/db.php';
session_start();

$company_id = $_SESSION['user']['company_id'];
$term = $_GET['term'] ?? '';

$sql = "SELECT e.id, e.name, e.salary_base AS salary, e.position, p.name as position_name, p.suggested_salary, p.food_allowance, p.transport_allowance, p.vacation_subsidy_pct, p.thirteenth_subsidy_pct FROM employees as e
        JOIN positions as p ON p.name = e.position
        WHERE e.company_id = ? AND e.status = 'ativo' AND e.name LIKE ? 
        ORDER BY e.name ASC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute([$company_id, "%$term%"]);

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'id' => $row['id'],
        'text' => $row['name'],
        'salary' => $row['salary'],
        'position' => $row['position'],
        'sub_suge' => $row['suggested_salary'],
        'sub_alim' => $row['food_allowance'],
        'sub_trans' => $row['transport_allowance'],
        'sub_ferias' => $row['vacation_subsidy_pct'],
        'sub_decimo' => $row['thirteenth_subsidy_pct']
    ];
}

echo json_encode($results);
