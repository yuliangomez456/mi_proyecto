<?php
session_start();
require_once '../config/conexion.php';
require_once '../modelos/Usuario.php';
require_once '../modelos/Ticket.php';

// Verificar que sea administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol_id'] != 1) {
    header("Location: ../index.php");
    exit();
}

$usuario = new Usuario();
$ticket = new Ticket();
$message = '';
$message_type = '';

// Determinar la sección activa
$seccion_activa = isset($_GET['seccion']) ? $_GET['seccion'] : 'usuarios';

// Procesar acciones para usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'crear':
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $celular = trim($_POST['celular']);
            $password = trim($_POST['password']);
            $rol = 3; // FORZAR que siempre sea técnico (rol_id = 3)
            
            if (!empty($nombre) && !empty($correo) && !empty($celular) && !empty($password)) {
                $result = $usuario->registrar($nombre, $correo, $celular, $password, $rol);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
            } else {
                $message = "Complete todos los campos";
                $message_type = "danger";
            }
            break;
            
        case 'actualizar':
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $celular = trim($_POST['celular']);
            $rol = $_POST['rol'];
            $nueva_password = !empty($_POST['password']) ? trim($_POST['password']) : null;
            
            if (!empty($nombre) && !empty($correo) && !empty($celular)) {
                $result = $usuario->actualizar($id, $nombre, $correo, $celular, $rol, $nueva_password);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
            } else {
                $message = "Complete los campos requeridos";
                $message_type = "danger";
            }
            break;
            
        case 'eliminar':
            $id = $_POST['id'];
            $result = $usuario->eliminar($id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
            break;

        case 'actualizar_estado_ticket':
            $ticket_id = $_POST['ticket_id'];
            $nuevo_estado = $_POST['estado'];
            $result = $ticket->actualizarEstado($ticket_id, $nuevo_estado);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
            $seccion_activa = 'tickets';
            break;

        // NUEVA ACCIÓN: Asignar técnico a ticket
        case 'asignar_tecnico':
            $ticket_id = $_POST['ticket_id'];
            $tecnico_id = $_POST['tecnico_id'];
            
            $result = $ticket->asignarTecnico($ticket_id, $tecnico_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'danger';
            $seccion_activa = 'tickets';
            break;
    }
}

// Obtener datos según la sección
if ($seccion_activa == 'usuarios') {
    $termino_busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
    if (!empty($termino_busqueda)) {
        $usuarios = $usuario->buscarUsuarios($termino_busqueda);
    } else {
        $usuarios = $usuario->obtenerTodosLosUsuarios();
    }
    $roles = $usuario->obtenerRoles();
} elseif ($seccion_activa == 'tickets') {
    $termino_busqueda_tickets = isset($_GET['buscar_tickets']) ? $_GET['buscar_tickets'] : '';
    $filtro_estado = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : '';
    $filtro_asignacion = isset($_GET['filtro_asignacion']) ? $_GET['filtro_asignacion'] : '';
    
    if (!empty($termino_busqueda_tickets) || !empty($filtro_estado) || !empty($filtro_asignacion)) {
        $tickets = $ticket->buscarTickets($termino_busqueda_tickets, $filtro_estado);
        // Filtrar por asignación si se especificó
        if (!empty($filtro_asignacion)) {
            if ($filtro_asignacion == 'sin_asignar') {
                $tickets = array_filter($tickets, function($t) {
                    return empty($t['tecnico_id']);
                });
            } elseif ($filtro_asignacion == 'asignados') {
                $tickets = array_filter($tickets, function($t) {
                    return !empty($t['tecnico_id']);
                });
            }
        }
    } else {
        $tickets = $ticket->obtenerTodosLosTickets();
    }
    
    // Obtener técnicos para asignación
    $tecnicos = $usuario->obtenerTecnicos();
    
    $estados_ticket = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En Proceso',
        'resuelto' => 'Resuelto',
        'cerrado' => 'Cerrado'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <title>Panel de Administración</title>
    <style>
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
        .badge-estado {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        .table-responsive {
            min-height: 400px;
        }
        .estado-pendiente { background-color: #ffc107; color: #000; }
        .estado-en_proceso { background-color: #0dcaf0; color: #000; }
        .estado-resuelto { background-color: #198754; color: #fff; }
        .estado-cerrado { background-color: #6c757d; color: #fff; }
        .btn-asignar {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="admin.php">
            <i class="fas fa-users-cog"></i> Panel Admin
        </a>
        <div class="navbar-nav ms-auto">
            <span class="nav-link">Bienvenido: <?php echo $_SESSION['user']['nombre']; ?></span>
            <a class="nav-link" href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Pestañas de navegación -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $seccion_activa == 'usuarios' ? 'active' : ''; ?>" 
               href="admin.php?seccion=usuarios">
                <i class="fas fa-users"></i> Usuarios
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $seccion_activa == 'tickets' ? 'active' : ''; ?>" 
               href="admin.php?seccion=tickets">
                <i class="fas fa-ticket-alt"></i> Tickets
                <span class="badge bg-secondary">
                    <?php echo $seccion_activa == 'tickets' ? count($tickets) : '0'; ?>
                </span>
            </a>
        </li>
    </ul>

    <!-- Mensajes -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- SECCIÓN USUARIOS -->
    <?php if ($seccion_activa == 'usuarios'): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> Gestionar Usuarios</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="fas fa-plus"></i> Nuevo Técnico
            </button>
        </div>
    </div>

    <!-- Búsqueda de usuarios -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="seccion" value="usuarios">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="buscar" 
                           placeholder="Buscar por nombre, correo o rol..." 
                           value="<?php echo htmlspecialchars($termino_busqueda); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if (!empty($termino_busqueda)): ?>
                        <a href="admin.php?seccion=usuarios" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lista de Usuarios (<?php echo count($usuarios); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Celular</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted"></i>
                                    <br>No se encontraron usuarios
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['nombre_usuario']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['correo_usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($user['celular_usuario']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['rol_id'] == 1 ? 'danger' : ($user['rol_id'] == 2 ? 'info' : 'success'); ?>">
                                            <?php echo htmlspecialchars($user['nombre_rol']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['fecha_registro'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] != 1 && $user['rol_id'] != 1): ?>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="eliminarUsuario(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nombre_usuario']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN TICKETS -->
    <?php elseif ($seccion_activa == 'tickets'): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-ticket-alt"></i> Gestionar Tickets</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php
            $tickets_sin_asignar = array_filter($tickets, function($t) {
                return empty($t['tecnico_id']);
            });
            ?>
            <span class="badge bg-info">Total: <?php echo count($tickets); ?></span>
            <span class="badge bg-warning">Sin asignar: <?php echo count($tickets_sin_asignar); ?></span>
        </div>
    </div>

    <!-- Filtros de tickets MEJORADOS -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="seccion" value="tickets">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="buscar_tickets" 
                           placeholder="Buscar por título, descripción o usuario..." 
                           value="<?php echo htmlspecialchars($termino_busqueda_tickets); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="filtro_estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados_ticket as $valor => $texto): ?>
                            <option value="<?php echo $valor; ?>" 
                                <?php echo $filtro_estado == $valor ? 'selected' : ''; ?>>
                                <?php echo $texto; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="filtro_asignacion">
                        <option value="">Todos los tickets</option>
                        <option value="sin_asignar" <?php echo $filtro_asignacion == 'sin_asignar' ? 'selected' : ''; ?>>Sin asignar</option>
                        <option value="asignados" <?php echo $filtro_asignacion == 'asignados' ? 'selected' : ''; ?>>Asignados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <?php if (!empty($termino_busqueda_tickets) || !empty($filtro_estado) || !empty($filtro_asignacion)): ?>
                        <a href="admin.php?seccion=tickets" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de tickets MEJORADA -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Lista de Tickets</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Técnico</th>
                            <th>Fecha</th>
                            <th width="140">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted"></i>
                                    <br>No se encontraron tickets
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td><?php echo $t['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($t['titulo']); ?></strong>
                                        <br><small class="text-muted"><?php echo substr(htmlspecialchars($t['descripcion']), 0, 50); ?>...</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['nombre_usuario']); ?></td>
                                    <td>
                                        <span class="badge badge-estado estado-<?php echo $t['estado']; ?>">
                                            <?php echo $estados_ticket[$t['estado']] ?? $t['estado']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($t['tecnico_id'])): ?>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($t['nombre_tecnico'] ?? 'Asignado'); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($t['fecha_creacion'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="verTicket(<?php echo htmlspecialchars(json_encode($t)); ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="cambiarEstadoTicket(<?php echo $t['id']; ?>, '<?php echo $t['estado']; ?>')">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <?php if (empty($t['tecnico_id'])): ?>
                                            <button class="btn btn-sm btn-success btn-asignar" 
                                                    onclick="asignarTecnico(<?php echo $t['id']; ?>)">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para crear técnico (SOLO TÉCNICOS) -->
<div class="modal fade" id="modalCrear" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Nuevo Técnico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo:</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico:</label>
                        <input type="email" class="form-control" name="correo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="celular" class="form-label">Celular:</label>
                        <input type="tel" class="form-control" name="celular" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña:</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Se creará un usuario con rol de <strong>Técnico</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Técnico</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="actualizar">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre completo:</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_correo" class="form-label">Correo electrónico:</label>
                        <input type="email" class="form-control" name="correo" id="edit_correo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_celular" class="form-label">Celular:</label>
                        <input type="tel" class="form-control" name="celular" id="edit_celular" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_rol" class="form-label">Rol:</label>
                        <select class="form-select" name="rol" id="edit_rol" required>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nueva contraseña (opcional):</label>
                        <input type="password" class="form-control" name="password" id="edit_password">
                        <div class="form-text">Dejar en blanco para mantener la contraseña actual</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para eliminar usuario -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="delete_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        ¿Está seguro de que desea eliminar al usuario 
                        <strong id="delete_nombre"></strong>?
                        <br><br>
                        Esta acción no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado de ticket -->
<div class="modal fade" id="modalEstadoTicket" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-sync-alt"></i> Cambiar Estado del Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="actualizar_estado_ticket">
                    <input type="hidden" name="ticket_id" id="ticket_id">
                    
                    <div class="mb-3">
                        <label for="nuevo_estado" class="form-label">Seleccionar nuevo estado:</label>
                        <select class="form-select" name="estado" id="nuevo_estado" required>
                            <?php foreach ($estados_ticket as $valor => $texto): ?>
                                <option value="<?php echo $valor; ?>"><?php echo $texto; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Esta acción actualizará el estado del ticket.
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

<!-- Modal para asignar técnico -->
<div class="modal fade" id="modalAsignarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Asignar Técnico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="asignar_tecnico">
                    <input type="hidden" name="ticket_id" id="asignar_ticket_id">
                    
                    <div class="mb-3">
                        <label for="tecnico_id" class="form-label">Seleccionar técnico:</label>
                        <select class="form-select" name="tecnico_id" id="tecnico_id" required>
                            <option value="">Seleccionar técnico...</option>
                            <?php foreach ($tecnicos as $id => $nombre): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Asignará este ticket al técnico seleccionado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Asignar Técnico</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del ticket -->
<div class="modal fade" id="modalVerTicket" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Detalles del Ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información del Ticket</h6>
                        <p><strong>ID:</strong> <span id="detalle_id"></span></p>
                        <p><strong>Título:</strong> <span id="detalle_titulo"></span></p>
                        <p><strong>Estado:</strong> <span id="detalle_estado"></span></p>
                        <p><strong>Prioridad:</strong> <span id="detalle_prioridad"></span></p>
                        <p><strong>Categoría:</strong> <span id="detalle_categoria"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Información del Usuario</h6>
                        <p><strong>Usuario:</strong> <span id="detalle_usuario"></span></p>
                        <p><strong>Correo:</strong> <span id="detalle_correo"></span></p>
                        <p><strong>Técnico Asignado:</strong> <span id="detalle_tecnico"></span></p>
                        <p><strong>Fecha Creación:</strong> <span id="detalle_fecha"></span></p>
                    </div>
                </div>
                <hr>
                <h6>Descripción Completa</h6>
                <div class="border p-3 rounded bg-light">
                    <p id="detalle_descripcion"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Funciones para usuarios
function editarUsuario(usuario) {
    document.getElementById('edit_id').value = usuario.id;
    document.getElementById('edit_nombre').value = usuario.nombre_usuario;
    document.getElementById('edit_correo').value = usuario.correo_usuario;
    document.getElementById('edit_celular').value = usuario.celular_usuario;
    document.getElementById('edit_rol').value = usuario.rol_id;
    document.getElementById('edit_password').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
    modal.show();
}

function eliminarUsuario(id, nombre) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_nombre').textContent = nombre;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

// Funciones para tickets
function verTicket(ticket) {
    document.getElementById('detalle_id').textContent = ticket.id;
    document.getElementById('detalle_titulo').textContent = ticket.titulo;
    document.getElementById('detalle_estado').textContent = ticket.estado;
    document.getElementById('detalle_prioridad').textContent = ticket.prioridad;
    document.getElementById('detalle_categoria').textContent = ticket.categoria;
    document.getElementById('detalle_usuario').textContent = ticket.nombre_usuario;
    document.getElementById('detalle_correo').textContent = ticket.correo_usuario;
    document.getElementById('detalle_tecnico').textContent = ticket.nombre_tecnico || 'Sin asignar';
    document.getElementById('detalle_fecha').textContent = new Date(ticket.fecha_creacion).toLocaleString();
    document.getElementById('detalle_descripcion').textContent = ticket.descripcion;
    
    var modal = new bootstrap.Modal(document.getElementById('modalVerTicket'));
    modal.show();
}

function cambiarEstadoTicket(ticketId, estadoActual) {
    document.getElementById('ticket_id').value = ticketId;
    document.getElementById('nuevo_estado').value = estadoActual;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEstadoTicket'));
    modal.show();
}

function asignarTecnico(ticketId) {
    document.getElementById('asignar_ticket_id').value = ticketId;
    
    var modal = new bootstrap.Modal(document.getElementById('modalAsignarTecnico'));
    modal.show();
}
</script>

</body>
</html>