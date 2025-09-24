<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['rol_id'] !== 2) { // 2 = Cliente
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user']['id'];
$nombre     = $_SESSION['user']['nombre'];
$correo     = $_SESSION['user']['correo'];

require_once __DIR__ . '/../modelos/Ticket.php';
require_once __DIR__ . '/../modelos/Usuario.php';

$ticketModel = new Ticket();
$usuarioModel = new Usuario();

// Obtener lista de técnicos
$tecnicos = $usuarioModel->obtenerTecnicos();

$mensaje = "";

// Ver ticket específico
$ticket_detalle = null;
if (isset($_GET['ver_ticket'])) {
    $ticket_id = $_GET['ver_ticket'];
    $ticket_detalle = $ticketModel->obtenerPorId($ticket_id, $usuario_id);
    
    if (!$ticket_detalle) {
        $mensaje = "Ticket no encontrado o no tienes permisos para verlo.";
    }
}

// Crear ticket
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['nuevo_ticket'])) {
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $categoria = $_POST['categoria'];
        $tecnico_id = !empty($_POST['tecnico_id']) ? $_POST['tecnico_id'] : null;

        // Validar que si se selecciona un técnico, exista en la lista
        if ($tecnico_id && !array_key_exists($tecnico_id, $tecnicos)) {
            $mensaje = "Error: El técnico seleccionado no es válido.";
        } else {
            $result = $ticketModel->crear($usuario_id, $titulo, $descripcion, $categoria, "media", $tecnico_id);
            $mensaje = $result['message'];
        }
    }
    // Eliminar ticket
    elseif (isset($_POST['eliminar_ticket'])) {
        $ticket_id = $_POST['ticket_id'];
        $result = $ticketModel->eliminar($ticket_id, $usuario_id);
        $mensaje = $result['message'];
    }
}

// Listar tickets del usuario
$tickets = $ticketModel->listarPorUsuario($usuario_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-accion {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            margin: 0 2px;
        }
        .badge-estado {
            font-size: 0.85em;
        }
    </style>
</head>
<body class="container py-5">
    <h2 class="mb-4">Mesa de Ayuda - Cliente</h2>
    <p>Bienvenido: <?= htmlspecialchars($nombre) ?> (<?= htmlspecialchars($correo) ?>)</p>

    <?php if ($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Modal para ver detalles del ticket -->
    <?php if ($ticket_detalle): ?>
    <div class="modal fade show" id="modalVerTicket" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-ticket-alt"></i> Detalles del Ticket #<?= $ticket_detalle['id'] ?></h5>
                    <a href="cliente.php" class="btn-close btn-close-white"></a>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Información del Ticket</h6>
                            <p><strong>Título:</strong> <?= htmlspecialchars($ticket_detalle['titulo']) ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="badge badge-estado bg-<?= 
                                    $ticket_detalle['estado'] == 'pendiente' ? 'warning' : 
                                    ($ticket_detalle['estado'] == 'en_proceso' ? 'info' : 
                                    ($ticket_detalle['estado'] == 'resuelto' ? 'success' : 'secondary')) 
                                ?>">
                                    <?= htmlspecialchars($ticket_detalle['estado']) ?>
                                </span>
                            </p>
                            <p><strong>Prioridad:</strong> 
                                <span class="badge bg-<?= 
                                    $ticket_detalle['prioridad'] == 'alta' ? 'danger' : 
                                    ($ticket_detalle['prioridad'] == 'media' ? 'warning' : 'success') 
                                ?>">
                                    <?= ucfirst($ticket_detalle['prioridad']) ?>
                                </span>
                            </p>
                            <p><strong>Categoría:</strong> <?= htmlspecialchars($ticket_detalle['categoria']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Información de Asignación</h6>
                            <p><strong>Técnico Asignado:</strong> 
                                <?= !empty($ticket_detalle['tecnico_id']) ? htmlspecialchars($ticket_detalle['nombre_tecnico'] ?? 'Asignado') : 'Sin asignar' ?>
                            </p>
                            <p><strong>Fecha Creación:</strong> <?= date('d/m/Y H:i', strtotime($ticket_detalle['fecha_creacion'])) ?></p>
                            <?php if (!empty($ticket_detalle['fecha_actualizacion'])): ?>
                                <p><strong>Última Actualización:</strong> <?= date('d/m/Y H:i', strtotime($ticket_detalle['fecha_actualizacion'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <h6>Descripción Completa</h6>
                    <div class="border p-3 rounded bg-light">
                        <p><?= nl2br(htmlspecialchars($ticket_detalle['descripcion'])) ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="cliente.php" class="btn btn-secondary">Cerrar</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario nuevo ticket -->
    <div class="card mb-4">
        <div class="card-header">Nuevo Ticket</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="nuevo_ticket" value="1">
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-control">
                        <option value="General">General</option>
                        <option value="Soporte Técnico">Soporte Técnico</option>
                        <option value="Facturación">Facturación</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Asignar a técnico (opcional)</label>
                    <select name="tecnico_id" class="form-control">
                        <option value="">Sin Asignar</option>
                        <?php foreach ($tecnicos as $id => $nombre): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Puede dejar sin asignar para que un administrador asigne posteriormente</small>
                </div>
                <button type="submit" class="btn btn-success">Crear Ticket</button>
            </form>
        </div>
    </div>

    <!-- Listado de tickets -->
    <h3>Mis Tickets</h3>
    <?php if (empty($tickets)): ?>
        <div class="alert alert-info">
            No tienes tickets creados.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Técnico</th>
                        <th>Fecha</th>
                        <th width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td>#<?= $t['id'] ?></td>
                            <td><?= htmlspecialchars($t['titulo']) ?></td>
                            <td><?= htmlspecialchars($t['categoria']) ?></td>
                            <td>
                                <span class="badge badge-estado bg-<?= 
                                    $t['estado'] == 'pendiente' ? 'warning' : 
                                    ($t['estado'] == 'en_proceso' ? 'info' : 
                                    ($t['estado'] == 'resuelto' ? 'success' : 'secondary')) 
                                ?>">
                                    <?= htmlspecialchars($t['estado']) ?>
                                </span>
                            </td>
                            <td><?= !empty($t['tecnico_id']) ? htmlspecialchars($t['nombre_tecnico'] ?? 'Asignado') : 'Sin asignar' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></td>
                            <td>
                                <div class="d-flex">
                                    <!-- Botón para ver ticket -->
                                    <a href="cliente.php?ver_ticket=<?= $t['id'] ?>" class="btn btn-info btn-accion" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Botón para eliminar ticket -->
                                    <form method="POST" onsubmit="return confirm('¿Está seguro de que desea eliminar este ticket? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="eliminar_ticket" value="1">
                                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-accion" title="Eliminar ticket">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="logout.php" class="btn btn-danger mt-3">Cerrar Sesión</a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>