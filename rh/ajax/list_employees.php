<?php
require_once '../../../app/config/db.php';
session_start();

$company_id = $_SESSION['user']['company_id']; // ou ajuste conforme sua sessão

$stmt = $pdo->prepare("SELECT id, name, bi, position, salary_base AS salary, status, document_type, birth_date, marital_status, academic_level, contract_type, admission_date, iban, photo_url, doc1_url, doc2_url
FROM employees
WHERE company_id = ?");
$stmt->execute([$company_id]);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $employees]);
