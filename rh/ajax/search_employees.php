<?php
require_once '../../../app/config/db.php';
session_start();

$company_id = $_SESSION['user']['company_id'];
$term = $_GET['term'] ?? '';

// LEFT JOIN + TRIM em ambos os lados: um funcionário sem cargo definido,
// ou com o nome do cargo gravado com espaços a mais/a menos, deixava de
// aparecer nos selects (folha, ponto) por causa do JOIN comparar texto
// exatamente igual. Isto é uma correção provisória — a solução definitiva
// é migrar para employees.position_id (Fase 1).
$sql = "SELECT e.id, e.name, e.salary_base AS salary, e.position, p.name as position_name, p.suggested_salary, p.food_allowance, p.transport_allowance, p.vacation_subsidy_pct, p.thirteenth_subsidy_pct FROM employees as e
        LEFT JOIN positions as p ON p.company_id = e.company_id AND TRIM(p.name) = TRIM(e.position)
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
        // Com LEFT JOIN, um funcionário sem cargo correspondente em `positions`
        // vem com estes campos a NULL — normaliza para 0 para não quebrar o JS.
        'sub_suge' => $row['suggested_salary'] ?? 0,
        'sub_alim' => $row['food_allowance'] ?? 0,
        'sub_trans' => $row['transport_allowance'] ?? 0,
        'sub_ferias' => $row['vacation_subsidy_pct'] ?? 0,
        'sub_decimo' => $row['thirteenth_subsidy_pct'] ?? 0
    ];
}

echo json_encode($results);
