<?php

require_once '../../../app/config/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$company_id = (int)($_SESSION['user']['company_id'] ?? 0);
$employee_id = (int)($_POST['employee_id'] ?? 0);

try {

  if (!$employee_id || !$company_id) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'error' => 'Dados inválidos.'
    ]);
    exit;
  }

  $stmt = $pdo->prepare("
        DELETE FROM employees 
        WHERE id = ? AND company_id = ?
    ");

  $stmt->execute([$employee_id, $company_id]);

  $deleted = $stmt->rowCount();

  if ($deleted === 0) {
    http_response_code(404);
    echo json_encode([
      'success' => false,
      'error' => 'Funcionário não encontrado.'
    ]);
    exit;
  }

  echo json_encode([
    'success' => true
  ]);
} catch (Throwable $e) {

  http_response_code(500);

  echo json_encode([
    'success' => false,
    'error' => 'Erro interno no servidor.'
  ]);
}
