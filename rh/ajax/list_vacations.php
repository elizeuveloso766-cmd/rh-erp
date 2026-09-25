<?php
require_once '../../../app/config/db.php';
session_start();

$company_id = $_SESSION['user']['company_id'];

$sql = "SELECT v.id, v.type, v.start_date, v.end_date, v.status, e.name AS employee_name
        FROM vacations v
        JOIN employees e ON e.id = v.employee_id
        WHERE v.company_id = ?";

$params = [$company_id];

// Filtro por funcionário
if (!empty($_REQUEST['funcionario'])) {
    $sql .= " AND v.employee_id = ?";
    $params[] = (int)$_REQUEST['funcionario'];
}

// Filtro por mês de referência (traz períodos que cruzam o mês)
if (!empty($_REQUEST['mes'])) {
    $mes = $_REQUEST['mes']; // YYYY-MM
    $firstDay = $mes . '-01';
    $lastDay = date('Y-m-t', strtotime($firstDay));

    $sql .= " AND (v.start_date <= ? AND v.end_date >= ?)";
    $params[] = $lastDay;
    $params[] = $firstDay;
}

$sql .= " ORDER BY v.start_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['data' => $rows]);
