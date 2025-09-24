<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['rol_id'] != 3) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../modelos/Ticket.php';
require_once __DIR__ . '/../modelos/Usuario.php';

$ticketModel = new Ticket();
$usuarioModel = new Usuario();

$tecnico_id = $_SESSION['user']['id'];
$mensaje = "";

// Cambiar estado del ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_estado'])) {
    $ticket_id = $_POST['ticket_id'];
    $nuevo_estado = $_POST['estado'];
    
    $result = $ticketModel->actualizarEstado($ticket_id, $nuevo_estado);
    $mensaje = $result['message'];
}

// Obtener tickets asignados al técnico
$tickets = $ticketModel->listarPorTecnico($tecnico_id);

// Obtener estadísticas
$estadisticas = [
    'pendientes' => 0,
    'en_proceso' => 0,
    'resueltos' => 0,
    'total' => count($tickets)
];

foreach ($tickets as $ticket) {
    if ($ticket['estado'] == 'pendiente') $estadisticas['pendientes']++;
    if ($ticket['estado'] == 'en_proceso') $estadisticas['en_proceso']++;
    if ($ticket['estado'] == 'resuelto') $estadisticas['resueltos']++;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Técnico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-stat {
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .badge-estado {
            font-size: 0.85em;
        }
    </style>
</head>
<body class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Panel de Técnico</h2>
        <div>
            <span class="me-3">Bienvenido: <strong><?= htmlspecialchars($_SESSION['user']['nombre']) ?></strong></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stat bg-primary text-white text-center">
                <div class="card-body">
                    <h5><i class="fas fa-tasks"></i></h5>
                    <h3><?= $estadisticas['total'] ?></h3>
                    <p class="mb-0">Total Tickets</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-warning text-dark text-center">
                <div class="card-body">
                    <h5><i class="fas fa-clock"></i></h5>
                    <h3><?= $estadisticas['pendientes'] ?></h3>
                    <p class="mb-0">Pendientes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-info text-white text-center">
                <div class="card-body">
                    <h5><i class="fas fa-cog"></i></h5>
                    <h3><?= $estadisticas['en_proceso'] ?></h3>
                    <p class="mb-0">En Proceso</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-stat bg-success text-white text-center">
                <div class="card-body">
                    <h5><i class="fas fa-check-circle"></i></h5>
                    <h3><?= $estadisticas['resueltos'] ?></h3>
                    <p class="mb-0">Resueltos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tickets asignados -->
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Tickets Asignados</h4>
        </div>
        <div class="card-body">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p>No tienes tickets asignados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Cliente</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td><?= $t['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($t['titulo']) ?></strong>
                                        <br><small class="text-muted"><?= substr(htmlspecialchars($t['descripcion']), 0, 50) ?>...</small>
                                    </td>
                                    <td><?= htmlspecialchars($t['nombre_usuario']) ?></td>
                                    <td>
                                        <span class="badge badge-estado bg-<?= 
                                            $t['estado'] == 'pendiente' ? 'warning' : 
                                            ($t['estado'] == 'en_proceso' ? 'info' : 
                                            ($t['estado'] == 'resuelto' ? 'success' : 'secondary')) 
                                        ?>">
                                            <?= htmlspecialchars($t['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $t['prioridad'] == 'alta' ? 'danger' : 
                                            ($t['prioridad'] == 'media' ? 'warning' : 'success') 
                                        ?>">
                                            <?= ucfirst($t['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVerTicket<?= $t['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalCambiarEstado<?= $t['id'] ?>">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Ver Ticket -->
                                <div class="modal fade" id="modalVerTicket<?= $t['id'] ?>">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title">Detalles del Ticket #<?= $t['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Información del Ticket</h6>
                                                        <p><strong>Título:</strong> <?= htmlspecialchars($t['titulo']) ?></p>
                                                        <p><strong>Estado:</strong> <span class="badge bg-<?= 
                                                            $t['estado'] == 'pendiente' ? 'warning' : 
                                                            ($t['estado'] == 'en_proceso' ? 'info' : 
                                                            ($t['estado'] == 'resuelto' ? 'success' : 'secondary')) 
                                                        ?>"><?= $t['estado'] ?></span></p>
                                                        <p><strong>Prioridad:</strong> <span class="badge bg-<?= 
                                                            $t['prioridad'] == 'alta' ? 'danger' : 
                                                            ($t['prioridad'] == 'media' ? 'warning' : 'success') 
                                                        ?>"><?= ucfirst($t['prioridad']) ?></span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Información del Cliente</h6>
                                                        <p><strong>Cliente:</strong> <?= htmlspecialchars($t['nombre_usuario']) ?></p>
                                                        <p><strong>Fecha Creación:</strong> <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <h6>Descripción Completa</h6>
                                                <div class="border p-3 rounded bg-light">
                                                    <p><?= nl2br(htmlspecialchars($t['descripcion'])) ?></p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Cambiar Estado -->
                                <div class="modal fade" id="modalCambiarEstado<?= $t['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title">Cambiar Estado del Ticket #<?= $t['id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="cambiar_estado" value="1">
                                                    <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Seleccionar nuevo estado:</label>
                                                        <select name="estado" class="form-select" required>
                                                            <option value="pendiente" <?= $t['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                            <option value="en_proceso" <?= $t['estado'] == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                                                            <option value="resuelto" <?= $t['estado'] == 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-warning">Actualizar Estado</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>