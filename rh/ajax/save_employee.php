<?php

require_once '../../../app/config/db.php';

session_start();

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

$company_id = $_SESSION['user']['company_id'] ?? null;

if (!$company_id) {
    http_response_code(401);
    exit('Sessão inválida.');
}

/*
|--------------------------------------------------------------------------
| INPUTS
|--------------------------------------------------------------------------
*/

$id = $_POST['id'] ?? $_POST['editid'] ?? null;
$id = !empty($id) ? (int)$id : null;

$name            = trim($_POST['employee_name'] ?? '');
$bi              = trim($_POST['bi'] ?? '');
$position        = trim($_POST['position'] ?? '');
$salary          = (float)($_POST['salary'] ?? 0);
$status          = trim($_POST['status'] ?? 'ativo');

$document_type   = trim($_POST['document_type'] ?? '');
$birth_date      = $_POST['birth_date'] ?? null;
$marital_status  = trim($_POST['marital_status'] ?? '');
$academic_level  = trim($_POST['academic_level'] ?? '');
$contract_type   = trim($_POST['contract_type'] ?? '');
$admission_date  = $_POST['admission_date'] ?? null;
$iban            = trim($_POST['iban'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDATIONS
|--------------------------------------------------------------------------
*/

if (empty($name)) {
    http_response_code(400);
    exit('Nome obrigatório.');
}

if (empty($position)) {
    http_response_code(400);
    exit('Cargo obrigatório.');
}

/*
|--------------------------------------------------------------------------
| UPLOAD PATHS
|--------------------------------------------------------------------------
*/

$uploadImgDir = __DIR__ . '/../../assets/img/employees/';
$uploadDocDir = __DIR__ . '/../../assets/docs/employees/';

if (!is_dir($uploadImgDir)) {
    mkdir($uploadImgDir, 0775, true);
}

if (!is_dir($uploadDocDir)) {
    mkdir($uploadDocDir, 0775, true);
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

// Extensão -> lista de MIME types reais aceites para essa extensão.
// A extensão sozinha é só o que está escrito no nome do ficheiro; quem faz
// upload pode renomear qualquer ficheiro para .jpg. finfo_file() lê a
// assinatura real do ficheiro no disco, que é o que importa para segurança.
const RH_ALLOWED_MIME_BY_EXT = [
    'png'  => ['image/png'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'webp' => ['image/webp'],
    'gif'  => ['image/gif'],
    'pdf'  => ['application/pdf'],
];

function saveUpload($fileKey, $destDir, array $allowedExts)
{
    if (
        !isset($_FILES[$fileKey]) ||
        $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $tmp  = $_FILES[$fileKey]['tmp_name'];
    $orig = $_FILES[$fileKey]['name'];

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        throw new Exception("Formato inválido para {$fileKey}");
    }

    // Valida o MIME real do ficheiro (não apenas a extensão do nome).
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = $finfo ? finfo_file($finfo, $tmp) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $expectedMimes = RH_ALLOWED_MIME_BY_EXT[$ext] ?? [];
    if (!$realMime || !in_array($realMime, $expectedMimes, true)) {
        throw new Exception("O ficheiro enviado para {$fileKey} não corresponde a um {$ext} válido.");
    }

    $fileName = uniqid($fileKey . '_', true) . '.' . $ext;

    $dest = rtrim($destDir, '/') . '/' . $fileName;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new Exception("Erro ao salvar {$fileKey}");
    }

    return $fileName;
}

function removeFileIfExists($dir, $file)
{
    if (!$file) return;

    $path = rtrim($dir, '/') . '/' . basename($file);

    if (is_file($path)) {
        @unlink($path);
    }
}

/*
|--------------------------------------------------------------------------
| TRANSACTION
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | UPLOADS
    |--------------------------------------------------------------------------
    */

    $newPhoto = saveUpload(
        'photo',
        $uploadImgDir,
        ['png', 'jpg', 'jpeg', 'webp', 'gif']
    );

    $newDoc1 = saveUpload(
        'doc1',
        $uploadDocDir,
        ['pdf', 'png', 'jpg', 'jpeg', 'webp']
    );

    $newDoc2 = saveUpload(
        'doc2',
        $uploadDocDir,
        ['pdf', 'png', 'jpg', 'jpeg', 'webp']
    );

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    if ($id) {

        $stmtOld = $pdo->prepare("
            SELECT 
                photo_url,
                doc1_url,
                doc2_url
            FROM employees
            WHERE id = ?
            AND company_id = ?
        ");

        $stmtOld->execute([$id, $company_id]);

        $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            throw new Exception('Funcionário não encontrado.');
        }

        $photoUrl = $newPhoto ?: $old['photo_url'];
        $doc1Url  = $newDoc1 ?: $old['doc1_url'];
        $doc2Url  = $newDoc2 ?: $old['doc2_url'];

        /*
        |--------------------------------------------------------------------------
        | REMOVE OLD FILES
        |--------------------------------------------------------------------------
        */

        if ($newPhoto && $old['photo_url']) {
            removeFileIfExists($uploadImgDir, $old['photo_url']);
        }

        if ($newDoc1 && $old['doc1_url']) {
            removeFileIfExists($uploadDocDir, $old['doc1_url']);
        }

        if ($newDoc2 && $old['doc2_url']) {
            removeFileIfExists($uploadDocDir, $old['doc2_url']);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE QUERY
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            UPDATE employees SET

                name = :name,
                bi = :bi,
                position = :position,
                salary_base = :salary,
                status = :status,

                document_type = :document_type,
                birth_date = :birth_date,
                marital_status = :marital_status,
                academic_level = :academic_level,
                contract_type = :contract_type,
                admission_date = :admission_date,
                iban = :iban,

                photo_url = :photo_url,
                doc1_url = :doc1_url,
                doc2_url = :doc2_url

            WHERE id = :id
            AND company_id = :company_id
        ");

        $stmt->execute([

            ':name'            => $name,
            ':bi'              => $bi,
            ':position'        => $position,
            ':salary'          => $salary,
            ':status'          => $status,

            ':document_type'   => $document_type,
            ':birth_date'      => $birth_date,
            ':marital_status'  => $marital_status,
            ':academic_level'  => $academic_level,
            ':contract_type'   => $contract_type,
            ':admission_date'  => $admission_date,
            ':iban'            => $iban,

            ':photo_url'       => $photoUrl,
            ':doc1_url'        => $doc1Url,
            ':doc2_url'        => $doc2Url,

            ':id'              => $id,
            ':company_id'      => $company_id

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */ else {

        $stmt = $pdo->prepare("
            INSERT INTO employees (

                company_id,
                name,
                bi,
                position,
                salary_base,
                status,

                document_type,
                birth_date,
                marital_status,
                academic_level,
                contract_type,
                admission_date,
                iban,

                photo_url,
                doc1_url,
                doc2_url

            ) VALUES (

                :company_id,
                :name,
                :bi,
                :position,
                :salary,
                :status,

                :document_type,
                :birth_date,
                :marital_status,
                :academic_level,
                :contract_type,
                :admission_date,
                :iban,

                :photo_url,
                :doc1_url,
                :doc2_url

            )
        ");

        $stmt->execute([

            ':company_id'      => $company_id,
            ':name'            => $name,
            ':bi'              => $bi,
            ':position'        => $position,
            ':salary'          => $salary,
            ':status'          => $status,

            ':document_type'   => $document_type,
            ':birth_date'      => $birth_date,
            ':marital_status'  => $marital_status,
            ':academic_level'  => $academic_level,
            ':contract_type'   => $contract_type,
            ':admission_date'  => $admission_date,
            ':iban'            => $iban,

            ':photo_url'       => $newPhoto,
            ':doc1_url'        => $newDoc1,
            ':doc2_url'        => $newDoc2

        ]);

        $id = $pdo->lastInsertId();
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'id'      => $id,
        'message' => $id ? 'Funcionário salvo com sucesso.' : 'Erro.'
    ]);
} catch (Exception $e) {

    $pdo->rollBack();

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
