<?php
// modelos/Ticket.php
require_once __DIR__ . '/../config/conexion.php';

class Ticket {
    
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear un nuevo ticket
    // En tu modelo Ticket.php - método crear()
    public function crear($usuario_id, $titulo, $descripcion, $categoria = "General", $prioridad = "media", $tecnico_id = null) {
        try {
            $query = "INSERT INTO tickets (usuario_id, titulo, descripcion, categoria, prioridad, estado, tecnico_id, fecha_creacion) 
                    VALUES (:usuario_id, :titulo, :descripcion, :categoria, :prioridad, 'pendiente', :tecnico_id, NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':categoria', $categoria);
            $stmt->bindParam(':prioridad', $prioridad);
            $stmt->bindParam(':tecnico_id', $tecnico_id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Ticket creado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al crear el ticket'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    // En Ticket.php - Agregar este método para eliminar tickets
public function eliminar($ticket_id, $usuario_id) {
    try {
        // Verificar que el ticket pertenece al usuario antes de eliminar
        $query = "DELETE FROM tickets WHERE id = :ticket_id AND usuario_id = :usuario_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ticket_id', $ticket_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Ticket eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se puede eliminar el ticket o no existe'];
            }
        } else {
            return ['success' => false, 'message' => 'Error al eliminar el ticket'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

    // Listar tickets de un usuario
    // En tu modelo Ticket.php - método listarPorUsuario()
        // En Ticket.php - Agregar este método
        public function listarPorTecnico($tecnico_id) {
            try {
                $query = "SELECT t.*, u.nombre_usuario 
                        FROM tickets t 
                        INNER JOIN usuarios u ON t.usuario_id = u.id 
                        WHERE t.tecnico_id = :tecnico_id 
                        ORDER BY t.fecha_creacion DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':tecnico_id', $tecnico_id);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                return [];
            }
}

// Listar tickets de un usuario - MÉTODO FALTANTE
            public function listarPorUsuario($usuario_id) {
                try {
                    $query = "SELECT t.*, u.nombre_usuario, tech.nombre_usuario as nombre_tecnico 
                            FROM tickets t 
                            INNER JOIN usuarios u ON t.usuario_id = u.id 
                            LEFT JOIN usuarios tech ON t.tecnico_id = tech.id 
                            WHERE t.usuario_id = :usuario_id 
                            ORDER BY t.fecha_creacion DESC";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':usuario_id', $usuario_id);
                    $stmt->execute();
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    return [];
                }
            }

    // Obtener todos los tickets (para admin)
    public function obtenerTodosLosTickets() {
        try {
            $query = "SELECT t.*, u.nombre_usuario, u.correo_usuario 
                      FROM tickets t 
                      INNER JOIN usuarios u ON t.usuario_id = u.id 
                      ORDER BY t.fecha_creacion DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Buscar tickets (para admin)
    public function buscarTickets($termino = '', $estado = '') {
        try {
            $query = "SELECT t.*, u.nombre_usuario, u.correo_usuario 
                      FROM tickets t 
                      INNER JOIN usuarios u ON t.usuario_id = u.id 
                      WHERE 1=1";
            
            $params = [];

            if (!empty($termino)) {
                $query .= " AND (t.titulo LIKE :termino OR t.descripcion LIKE :termino OR u.nombre_usuario LIKE :termino)";
                $params[':termino'] = "%$termino%";
            }

            if (!empty($estado)) {
                $query .= " AND t.estado = :estado";
                $params[':estado'] = $estado;
            }

            $query .= " ORDER BY t.fecha_creacion DESC";

            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // Actualizar estado de un ticket
    // En Ticket.php - actualizar el método actualizarEstado
    public function actualizarEstado($ticket_id, $nuevo_estado) {
        try {
            $query = "UPDATE tickets SET estado = :estado, fecha_actualizacion = NOW() WHERE id = :ticket_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':estado', $nuevo_estado);
            $stmt->bindParam(':ticket_id', $ticket_id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar el estado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Obtener ticket por ID
    // En Ticket.php - el método obtenerPorId() ya existe, pero vamos a mejorarlo
public function obtenerPorId($ticket_id, $usuario_id = null) {
    try {
        $query = "SELECT t.*, u.nombre_usuario, u.correo_usuario, tech.nombre_usuario as nombre_tecnico
                  FROM tickets t 
                  INNER JOIN usuarios u ON t.usuario_id = u.id 
                  LEFT JOIN usuarios tech ON t.tecnico_id = tech.id 
                  WHERE t.id = :ticket_id";
        
        // Si se proporciona usuario_id, verificar que el ticket pertenece al usuario
        if ($usuario_id !== null) {
            $query .= " AND t.usuario_id = :usuario_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':ticket_id', $ticket_id);
        
        if ($usuario_id !== null) {
            $stmt->bindParam(':usuario_id', $usuario_id);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

    // Asignar técnico a un ticket
    public function asignarTecnico($ticket_id, $tecnico_id) {
        try {
            $query = "UPDATE tickets SET tecnico_id = :tecnico_id WHERE id = :ticket_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tecnico_id', $tecnico_id);
            $stmt->bindParam(':ticket_id', $ticket_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Técnico asignado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al asignar el técnico'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Obtener estadísticas de tickets
    public function obtenerEstadisticas() {
        try {
            $query = "SELECT 
                        estado, 
                        COUNT(*) as cantidad,
                        COUNT(CASE WHEN prioridad = 'alta' THEN 1 END) as alta,
                        COUNT(CASE WHEN prioridad = 'media' THEN 1 END) as media,
                        COUNT(CASE WHEN prioridad = 'baja' THEN 1 END) as baja
                      FROM tickets 
                      GROUP BY estado";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
}