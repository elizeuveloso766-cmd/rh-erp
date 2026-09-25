<?php

session_start();

require_once '../app/config/db.php';
require_once '../app/helpers/authentication.php';
require_once '../app/helpers/subscription.php';

$company_id = (int)($_SESSION['user']['company_id'] ?? 0);

if ($company_id <= 0) {
    header('Location: login.php');
    exit;
}

try {

    subscription_require_feature($pdo, $company_id, 'rh');
} catch (Throwable $e) {

    header('Location: subscription.php?company_id=' . urlencode($company_id) . '&upgrade=rh');
    exit;
}

require_once '../app/views/layout_creation.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
    /* ===== TABELA ESTILO ===== */
    #employeesTable {
        border-collapse: separate;
        border-spacing: 0 12px;
        width: 100%;
    }

    /* HEADER */
    #employeesTable thead th {
        border: none;
        font-size: 12px;
        color: #9ca3af;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 12px 16px;
        text-align: left;
    }

    /* ROW */
    #employeesTable tbody tr {
        background: #fff !important;
        border-radius: 14px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        text-align: left !important;
    }

    /* HOVER PRO */
    #employeesTable tbody tr:hover {
        transform: translateY(-4px) scale(1.01);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
    }

    /* CELLS */
    #employeesTable tbody td {
        border: none;
        padding: 18px 16px;
        vertical-align: middle;
        text-align: left;
        font-size: 0.95rem;
        background: #fff !important;
    }

    /* BORDAS ARREDONDADAS */
    #employeesTable tbody td:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
        background: #fff !important;
    }

    #employeesTable tbody th {
        text-align: right !important;
    }

    #employeesTable tbody td:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
        text-align: right;
        padding-right: 24px;
    }

    /* ===== NOME (PRINCIPAL) ===== */
    #employeesTable tbody td:first-child {
        font-weight: 600;
        color: #111;
    }

    /* SUBINFO */
    #employeesTable tbody td small {
        display: block;
        color: #6b7280;
    }


    /* ===== MOBILE ===== */
    @media (max-width: 768px) {

        #employeesTable thead {
            display: none;
        }

        #employeesTable,
        #employeesTable tbody,
        #employeesTable tr,
        #employeesTable td {
            display: block;
            width: 100%;
        }

        #employeesTable tbody tr {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        #employeesTable tbody td {
            padding: 6px 0;
            text-align: left;
        }

        #employeesTable tbody td:first-child {
            font-size: 1.1rem;
            font-weight: 600;
        }

        #employeesTable tbody td[data-label="Ações"] {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 10px;
        }
    }

    .dt-paging-button:hover {
        background: none !important;
        color: #111 !important;
        background: #fff;
    }

    .dt-paging-button.current {
        background: #007abd !important;
        padding: 2px 2px !important;
        border-radius: 10px !important;
    }

    .dt-paging-button.current::content {
        color: #fff !important;
    }

    .modal-header {
        background: linear-gradient(135deg, #005a87, #007abd);
        color: #fff;
    }

    .modal-header button.btn-close {
        color: #fff !important;
    }

    .steps {
        display: flex;
        gap: 10px;
    }

    .step {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #6b7280;
    }

    .step.active {
        background: #4f46e5;
        color: #fff;
    }

    .step-content {
        display: none;
    }

    .step-content.active {
        display: block;
    }

    .profile-header {
        height: 120px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
    }

    .profile-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        margin-top: 0px;
        border: 4px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        align-content: center;
        align-self: center;
        margin: auto;
        background: linear-gradient(45deg, #007abd, #005a87);
    }

    #formEditEmployee label,
    #formEmployee label {
        font-weight: bold;
        margin-bottom: 5px;
    }

    /* MODAL BASE */
    .custom-modal {
        height: 90vh;
        border-radius: 16px;
        overflow: hidden;
    }

    /* SCROLL DIREITO */
    .form-scroll {
        height: 100%;
        overflow-y: auto;
        background: none;
    }

    /* SCROLLBAR */
    .form-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .form-scroll::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }

    /* MOBILE */
    @media (max-width: 768px) {

        .modal-dialog {
            margin: 0;
            max-width: 100%;
        }

        .custom-modal {
            height: 100vh;
            border-radius: 0;
        }

        .form-scroll {
            height: auto;
            overflow: visible;
        }
    }

    .custom-tabs .nav-link {
        border: none;
        color: #6b7280;
        font-weight: 500;
    }

    .custom-tabs .nav-link.active {
        color: #1f6fb2;
        border-bottom: 2px solid #1f6fb2;
        background: transparent;
    }

    .tab-content-item {
        display: none;
    }

    .tab-content-item.active {
        display: block;
    }

    /* opcional visual */
    .nav-tabs .nav-link {
        cursor: pointer;
    }

    .tabs-inline {
        display: flex;
        gap: 10px;
        border-bottom: 1px solid #e5e5e5;
        margin-bottom: 15px;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        font-weight: 500;
    }

    .tab-btn.active {
        border-bottom: 2px solid #0d6efd;
        color: #0d6efd;
    }
</style>

<main class="main-content">
    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-12">
                <div class="mt-4">
                    <div class=" d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 fw-bold">Funcionários</h2>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger rounded-pill" id="btnExportEmployeesPdf">
                                <i class="bi bi-file-pdf"></i>
                                Exportar PDF
                            </button>
                            <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalPosition">
                                <i class="bi bi-plus"></i>
                                Adicionar cargo
                            </button>
                            <button class="btn btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEmployee">
                                <i class="bi bi-person-plus"></i>
                                Adicionar Funcionário
                            </button>
                        </div>
                    </div>
                    <div class="card-body mt-4">
                        <div class="table-responsive">
                            <table id="employeesTable" class="table w-100">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>BI</th>
                                        <th>Cargo</th>
                                        <th>Salário</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dados via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Funcionário -->
<div class="modal fade" id="modalEmployee" tabindex="-1" aria-labelledby="modalEmployeeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEmployee" enctype="multipart/form-data">

                <div class="modal-header border-0">
                    <h5 class="modal-title">Cadastrar Funcionário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- STEPS -->
                    <div class="steps mb-4">
                        <div class="step active">1</div>
                        <div class="step">2</div>
                        <div class="step">3</div>
                    </div>

                    <!-- STEP 1 -->
                    <div class="step-content active">
                        <div class="card p-3 border-0 shadow-sm">
                            <h6 class="mb-3">Dados Pessoais</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <label>Nome Completo</label>
                                    <input type="text" name="name" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label>Data de Nascimento</label>
                                    <input type="date" name="birth_date" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Tipo de documento</label>
                                    <select name="document_type" class="form-select">
                                        <option>BI</option>
                                        <option>Passaporte</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Nº do BI/Passaporte</label>
                                    <input type="text" name="bi" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Estado civil</label>
                                    <select name="marital_status" class="form-select">
                                        <option>Selecione...</option>
                                        <option>Solteiro(a)</option>
                                        <option>Casado(a)</option>
                                        <option>Divorciado(a)</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div class="step-content">
                        <div class="card p-3 border-0 shadow-sm">
                            <h6 class="mb-3">Dados Profissionais</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <label>Cargo</label>
                                    <select name="position" id="positionSelect" class="form-select"></select>
                                </div>

                                <div class="col-md-6">
                                    <label>Salário</label>
                                    <input type="number" name="salary" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Status</label>
                                    <select name="status" class="form-select">
                                        <option>Ativo</option>
                                        <option>Inativo</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Tipo de vínculo</label>
                                    <select type="text" name="contract_type" class="form-select">
                                        <option selected>Selecione o tipo</option>
                                        <option value="efetivo">Efetivo</option>
                                        <option value="atermo">A Termo</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Data de Admissão</label>
                                    <input type="date" name="admission_date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3 -->
                    <div class="step-content">
                        <div class="card p-3 border-0 shadow-sm">
                            <h6 class="mb-3">Outros & Uploads</h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <label>IBAN</label>
                                    <input type="text" name="iban" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label>Nível acadêmico</label>
                                    <select name="academic_level" class="form-control">
                                        <option>Selecione...</option>
                                        <option>Ensino médio</option>
                                        <option>Superior</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Foto</label>
                                    <input type="file" name="photo" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Documento 1</label>
                                    <input type="file" name="doc1" class="form-control">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label>Documento 2</label>
                                    <input type="file" name="doc2" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" id="prevBtn">Voltar</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Próximo</button>
                    <button type="submit" class="btn btn-success d-none" id="submitBtn">Salvar</button>
                </div>

            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalEditEmployee" tabindex="-1" aria-labelledby="modalEmployeeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-0 overflow-hidden">

            <div class="modal-header border-0">
                <h5 class="modal-title">Editar Funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="formEditEmployee" enctype="multipart/form-data">

                <input type="hidden" name="id" id="editId">

                <div class="row g-0">

                    <!-- SIDEBAR -->
                    <div class="col-md-4 border-end bg-white p-4 text-center">

                        <div class="mb-3 rounded-circle profile-image text-white">
                            <i class="bi bi-person fs-1"></i>
                        </div>

                        <h6 id="previewName">Nome do Funcionário</h6>
                        <small class="text-muted small fw-semibold mb-3" style="margin-top: -100px;" id="previewPosition">Cargo</small>
                        <div class="col-md-12 mb-3">
                            <label for="photo" class="btn btn-outline-primary">Alterar Foto <i class="bi bi-camera"></i></label>
                            <input type="file" name="photo" id="photo" class="form-control d-none">
                        </div>

                        <hr>

                        <div class="text-start small">
                            <p style="margin-bottom:  0px;"><strong class="opacity-50">Status:</strong> <span id="previewStatus" class="text-capitalize">Ativo</span></p>
                            <p style="margin-bottom:  0px;"><strong class="opacity-50">Salário:</strong> <span id="previewSalary" class="text-capitalize">0 Kz</span></p>
                            <p style="margin-bottom:  0px;"><strong class="opacity-50">Admissão:</strong> <span id="previewAdmission" class="text-capitalize">--</span></p>
                        </div>

                        <div class="col-md-12 align-items-center mt-4">
                            <button type="submit" class="btn btn-success p-2" style="width: 160px !important;">Salvar</button>
                        </div>

                    </div>

                    <!-- FORM -->
                    <div class="col-md-8 p-4 overflow-hidden">

                        <h5 class="mb-3 fw-bold">Informações do Funcionário</h5>
                        <hr class="opacity-25">
                        <!-- NAV TABS -->
                        <div class="tabs-inline" id="employeeTabs">

                            <button class="tab-btn active" data-tab="tab1">Geral</button>
                            <button class="tab-btn" data-tab="tab2">Documentos</button>
                            <button class="tab-btn" data-tab="tab3">Outros</button>

                        </div>

                        <!-- TAB CONTENT -->
                        <div class="form-scroll">

                            <!-- TAB 1 -->
                            <div class="tab-content-item active" id="tab1">

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <label>Nome Completo</label>
                                    <input type="text" name="employee_name" class="form-control">
                                </div>

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Nº do BI</label>
                                            <input type="text" name="bi" class="form-control">
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Cargo</label>
                                            <select name="position" id="selectPosition" class="form-select">
                                                <option value="" selected>Selecione o cargo</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Salário</label>
                                            <input type="number" name="salary" id="salary1" class="form-control">
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Status</label>
                                            <select name="status" class="form-select">
                                                <option>Ativo</option>
                                                <option>Inativo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- TAB 2 -->
                            <div class="tab-content-item" id="tab2">

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Tipo de documento</label>
                                            <select name="document_type" class="form-select">
                                                <option>BI</option>
                                                <option>Passaporte</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Data de Nascimento</label>
                                            <input type="date" name="birth_date" class="form-control">
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Tipo de vínculo</label>
                                            <select type="text" name="contract_type" class="form-select">
                                                <option selected>Selecione o tipo</option>
                                                <option value="efetivo">Efetivo</option>
                                                <option value="atermo">A Termo</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Data de Admissão</label>
                                            <input type="date" name="admission_date" class="form-control">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- TAB 3 -->
                            <div class="tab-content-item" id="tab3">

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <label>IBAN</label>
                                    <input type="text" name="iban" class="form-control">
                                </div>

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Estado civil</label>
                                            <select name="marital_status" class="form-select">
                                                <option>Solteiro</option>
                                                <option>Casado</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Nível acadêmico</label>
                                            <select name="academic_level" class="form-select">
                                                <option>Ensino médio</option>
                                                <option>Superior</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-3 rounded mb-3 shadow-sm">
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Documento 1</label>
                                            <input type="file" name="doc1" class="form-control">
                                        </div>

                                        <div class="col-12 col-md-6 mb-3">
                                            <label>Documento 2</label>
                                            <input type="file" name="doc2" class="form-control">
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPosition" tabindex="-1" aria-labelledby="modalPositionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPosition">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPositionLabel">Cadastrar Cargo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nome do Cargo</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Salário Sugerido</label>
                        <input type="number" step="0.01" name="suggested_salary" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Subsídio de alimentação</label>
                        <input type="number" step="0.01" name="food_allowance" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label>Subsídio de transporte</label>
                        <input type="number" step="0.01" name="transport_allowance" class="form-control" value="0">
                    </div>

                    <div class="mb-3">
                        <label>Subsídio de férias</label>
                        <select name="vacation_subsidy_pct" class="form-control">
                            <option value="0">0%</option>
                            <option value="50">50%</option>
                            <option value="100">100%</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Subsídio de 13º</label>
                        <select name="thirteenth_subsidy_pct" class="form-control">
                            <option value="0">0%</option>
                            <option value="50">50%</option>
                            <option value="100">100%</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let currentStep = 0;
    let isEditing = false;
    let employeesTable = null;

    const steps = document.querySelectorAll(".step");
    const contents = document.querySelectorAll(".step-content");

    const nextBtn = document.getElementById("nextBtn");
    const prevBtn = document.getElementById("prevBtn");
    const submitBtn = document.getElementById("submitBtn");

    function updateSteps() {
        contents.forEach((c, i) => {
            c.classList.toggle("active", i === currentStep);
            steps[i].classList.toggle("active", i <= currentStep); // progresso visual
        });

        prevBtn.style.display = currentStep === 0 ? "none" : "inline-block";

        if (currentStep === contents.length - 1) {
            nextBtn.classList.add("d-none");
            submitBtn.classList.remove("d-none");
        } else {
            nextBtn.classList.remove("d-none");
            submitBtn.classList.add("d-none");
        }
    }

    nextBtn.onclick = () => {
        currentStep++;
        updateSteps();
    };

    prevBtn.onclick = () => {
        currentStep--;
        updateSteps();
    };

    updateSteps();

    // Modal vie employees details

    document.querySelectorAll(".tab-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();

            const target = this.dataset.tab;

            // ativa botão
            document.querySelectorAll(".tab-btn")
                .forEach(b => b.classList.remove("active"));

            this.classList.add("active");

            // troca conteúdo
            document.querySelectorAll(".tab-content-item")
                .forEach(c => c.classList.remove("active"));

            document.getElementById(target).classList.add("active");
        });
    });


    $(document).ready(function() {
        employeesTable = $('#employeesTable').DataTable({
            ajax: 'rh/ajax/list_employees.php',
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            },
            columns: [{
                    data: 'name'
                },
                {
                    data: 'bi'
                },
                {
                    data: 'position'
                },
                {
                    data: 'salary',
                    render: data => `Kz ${parseFloat(data).toLocaleString('pt-AO', { minimumFractionDigits: 2 })}`
                },
                {
                    data: 'status',
                    render: data => {
                        const cor = data === 'ativo' ? 'success' : 'danger';
                        return `<span class="badge bg-${cor} text-uppercase">${data}</span>`;
                    }
                },
                {
                    data: null,
                    render: row => `
                    <button class='btn btn-sm text-warning editEmployee'
                        data-id='${row.id}'
                        data-name='${row.name}'
                        data-bi='${row.bi}'
                        data-position='${row.position}'
                        data-salary='${row.salary}'
                        data-status='${row.status}'
                        data-document_type='${row.document_type}'
                        data-birth_date='${row.birth_date}'
                        data-contract_type='${row.contract_type}'
                        data-admission_date='${row.admission_date}'
                        data-iban='${row.iban}'
                        data-marital_status='${row.marital_status || ''}'
                        data-academic_level='${row.academic_level || ''}'
                        data-photo_url='${row.photo_url || ''}'
                        data-doc1_url='${row.doc1_url || ''}'
                        data-doc2_url='${row.doc2_url || ''}'><i class="bi bi-pencil"></i></button>
                    <button class='btn btn-sm text-danger deleteEmployee' onclick="deleteEmployee(${row.id})"><i class="bi bi-trash"></i></button>`
                }
            ]
        });


        // Submit do formulário
        $('#formEmployee').on('submit', function(e) {
            e.preventDefault();

            const form = document.getElementById('formEmployee');
            const fd = new FormData(form);

            $.ajax({
                url: 'rh/ajax/save_employee.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function() {
                    if (employeesTable) employeesTable.ajax.reload(null, false);
                    Swal.fire('Sucesso', 'Funcionário salvo com sucesso!', 'success');
                    $('#modalEmployee').modal('hide');
                    form.reset();
                    $('#positionSelect').val('').trigger('change');
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    Swal.fire('Erro', 'Falha ao salvar funcionário', 'error');
                }
            });
        });


        // Submit do formulário
        $('#formEditEmployee').on('submit', function(e) {
            e.preventDefault();

            const form = document.getElementById('formEditEmployee');
            const fd = new FormData(form);

            $.ajax({
                url: 'rh/ajax/save_employee.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function() {
                    if (employeesTable) employeesTable.ajax.reload(null, false);
                    Swal.fire('Sucesso', 'Funcionário salvo com sucesso!', 'success');
                    $('#editId').remove();
                    $('#modalEditEmployee').modal('hide');
                    setTimeout(() => {
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('padding-right', '');
                    }, 150);
                    form.reset();
                    $('#selectPosition').val('').trigger('change');
                }
            });
        });

        $('#btnExportEmployeesPdf').on('click', function() {
            window.open('rh/ajax/export_employees_pdf.php', '_blank');
        });

        // Excluir arquivo (foto/doc1/doc2)
        $('#modalEmployee').on('click', '.delete-emp-file', function() {

            const type = $(this).data('type');
            const employeeId = $('#formEmployee').data('employee_id');

            if (!employeeId) {
                return Swal.fire('Erro', 'Funcionário não identificado.', 'error');
            }

            Swal.fire({
                title: 'Excluir arquivo?',
                text: 'Essa ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: 'rh/ajax/delete_employee_file.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        employee_id: employeeId,
                        type: type
                    },
                    success: function(res) {

                        if (res && res.success) {

                            // limpa visualmente com segurança
                            const targets = {
                                photo: '#currentPhoto',
                                doc1: '#currentDoc1',
                                doc2: '#currentDoc2'
                            };

                            if (targets[type]) {
                                $(targets[type]).html('<span>Nenhum arquivo salvo.</span>');
                            }

                            // atualiza tabela se existir
                            if (employeesTable) {
                                employeesTable.ajax.reload(null, false);
                            }

                            Swal.fire('Sucesso', 'Arquivo excluído.', 'success');

                        } else {
                            Swal.fire('Erro', res?.error || 'Não foi possível excluir.', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Erro',
                            xhr.responseText || 'Falha ao excluir arquivo.',
                            'error'
                        );
                    }
                });
            });
        });


        // Editar funcionário
        $('#employeesTable').on('click', '.editEmployee', function() {
            const btn = $(this);
            const form = $('#formEditEmployee');

            // Helper seguro
            const get = (key) => btn.data(key) ?? '';

            // =========================
            // PREENCHER CAMPOS
            // =========================
            form.find('[name=id]').val(get('id'));
            form.find('[name=employee_name]').val(get('name'));
            form.find('[name=bi]').val(get('bi'));
            form.find('[name=salary]').val(get('salary'));
            form.find('[name=status]').val(get('status'));
            form.find('[name=document_type]').val(get('document_type'));
            form.find('[name=birth_date]').val(get('birth_date'));
            form.find('[name=contract_type]').val(get('contract_type'));
            form.find('[name=admission_date]').val(get('admission_date'));
            form.find('[name=iban]').val(get('iban'));
            form.find('[name=position]').val(get('position'));
            form.find('[name=marital_status]').val(get('marital_status'));
            form.find('[name=academic_level]').val(get('academic_level'));

            // =========================
            // PREVIEW (LAYOUT SaaS)
            // =========================
            $('#previewName').text(get('name') || 'Nome do Funcionário');
            $('#previewPosition').text(get('position') || 'Cargo');
            $('#previewSalary').text((get('salary') || 0) + ' Kz');
            $('#previewStatus').text(get('status') || '--');
            $('#previewAdmission').text(get('admission_date') || '--');

            // =========================
            // FOTO PREVIEW (se existir)
            // =========================
            if (get('photo_url')) {
                $('.profile-img').attr('src', `assets/img/employees/${get('photo_url')}`);
            }

            // =========================
            // FILES
            // =========================
            function renderFile(containerSel, type, filename) {
                const $c = $(containerSel);

                if (!filename) {
                    $c.html('<span class="text-muted">Nenhum arquivo salvo.</span>');
                    return;
                }

                const safeName = String(filename).replace(/[^a-zA-Z0-9_\-.]/g, '');
                const isPhoto = type === 'photo';

                const url = isPhoto ?
                    `assets/img/employees/${safeName}` :
                    `assets/docs/employees/${safeName}`;

                $c.html(`
                <a href="${url}" target="_blank">Ver</a>
                <span class="mx-2">|</span>
                <button type="button"
                    class="btn btn-link p-0 text-danger delete-emp-file"
                    data-type="${type}">
                    Excluir
                </button>
                `);
            }

            renderFile('#currentPhoto', 'photo', get('photo_url'));
            renderFile('#currentDoc1', 'doc1', get('doc1_url'));
            renderFile('#currentDoc2', 'doc2', get('doc2_url'));

            // =========================
            // POSITION (select async fix)
            // =========================
            $('#modalEditEmployee')
                .off('shown.bs.modal')
                .on('shown.bs.modal', function() {
                    form.find('[name=position]')
                        .val(get('position'))
                        .trigger('change');
                });

            // =========================
            // ID (sem duplicar)
            // =========================
            let idInput = form.find('#editId');

            if (idInput.length) {
                idInput.val(get('id'));
            } else {
                form.append(`<input type="hidden" name="id" id="editId" value="${get('id')}">`);
            }

            // guarda id para deletes
            form.data('employee_id', get('id'));

            // =========================
            // ABRIR MODAL
            // =========================
            $('#modalEditEmployee').modal('show');
        });

    });

    function deleteEmployee(employeeId) {

        if (!employeeId) {
            return Swal.fire('Erro', 'Funcionário não identificado.', 'error');
        }

        if ($.fn.DataTable.isDataTable("#employeesTable")) {
            $("#employeesTable").DataTable().ajax.reload(null, false);
        }

        Swal.fire({
            title: 'Excluir Funcionário?',
            text: 'Essa ação não pode ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Excluir',
            confirmButtonColor: '#d33'
        }).then((result) => {

            if (!result.isConfirmed) return;

            $.ajax({
                url: "rh/ajax/delete_employee.php",
                type: "POST",
                dataType: "json",
                data: {
                    employee_id: employeeId
                },
                success: function(res) {
                    console.log("DELETE RESPONSE:", res);

                    if (res && res.success === true) {
                        if (employeesTable) employeesTable.ajax.reload(null, false);

                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: "success",
                            title: "Funcionário excluído.",
                            timer: 3000,
                            showConfirmButton: false
                        });

                    } else {
                        Swal.fire("Erro", res?.error || "Resposta inválida do servidor", "error");
                    }
                },
                error: function(xhr) {
                    console.log("AJAX ERROR RAW:", xhr.responseText);

                    Swal.fire("Erro", "Falha na requisição", "error");
                }
            });
        });
    }

    $(document).ready(function() {

        const positionsSelects = $('#positionSelect, #selectPosition');

        function loadPositions() {
            $.ajax({
                url: "rh/ajax/list_positions.php",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    const data = response?.data || [];
                    const optionsHtml = data.map(r => `
                        <option data-salary="${r.suggested_salary || 0}" value="${r.name}">
                            ${r.name}
                        </option>
                    `).join('');

                    positionsSelects.each(function() {
                        const currentValue = $(this).val();
                        $(this).html(`
                            <option value="">Selecione</option>
                            ${optionsHtml}
                        `);

                        if (currentValue) {
                            $(this).val(currentValue);
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.log(error);
                }
            });
        }

        loadPositions();
        setInterval(loadPositions, 10000);

    });

    document.addEventListener('change', function(e) {

        // IDs suportados
        if (
            !e.target.matches('#positionSelect') &&
            !e.target.matches('#selectPosition')
        ) {
            return;
        }

        if (isEditing) return;

        const selectedOption =
            e.target.options[e.target.selectedIndex];

        const salario =
            selectedOption.dataset.salary;

        // Inputs de salário
        const salary1 =
            document.querySelector('input[name="salary"]');

        const salary2 =
            document.querySelector('#salary1');

        if (salario !== undefined && salario !== '') {

            const formatted =
                parseFloat(salario).toFixed(2);

            if (salary1) {
                salary1.value = formatted;
            }

            if (salary2) {
                salary2.value = formatted;
            }

        } else {

            if (salary1) {
                salary1.value = '';
            }

            if (salary2) {
                salary2.value = '';
            }

        }

    });

    $('#formPosition').on('submit', function(e) {
        e.preventDefault();

        $.post('rh/ajax/save_position.php', $(this).serialize(), function() {
            $('#modalPosition').modal('hide');

            setTimeout(function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
            }, 300);

            if (employeesTable) employeesTable.ajax.reload(null, false);

            Swal.fire(
                'Sucesso',
                'Cargo salvo com sucesso!',
                'success'
            );

            $('#editPositionId').remove();

        });
    });
</script>

<?php require_once '../app/views/footer.php'; ?>