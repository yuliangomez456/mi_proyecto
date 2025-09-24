<?php
// modelos/Usuario.php
require_once __DIR__ . '/../config/conexion.php';

class Usuario {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Registrar usuario
    public function registrar($nombre, $correo, $celular, $password, $rol_id = 2) {
        try {
            // Verificar si el correo ya existe
            if ($this->existeCorreo($correo)) {
                return ['success' => false, 'message' => 'Este correo ya está registrado'];
            }
            
            // Encriptar contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $query = "INSERT INTO usuarios (nombre_usuario, correo_usuario, celular_usuario, contrasena_usuario, rol_id) 
                     VALUES (:nombre, :correo, :celular, :password, :rol)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':celular', $celular);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':rol', $rol_id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al registrar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Login de usuario
    public function login($correo, $password) {
        try {
            $query = "SELECT u.*, r.nombre_rol FROM usuarios u 
                     INNER JOIN roles r ON u.rol_id = r.id 
                     WHERE u.correo_usuario = :correo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':correo', $correo);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['contrasena_usuario'])) {
                    return ['success' => true, 'user' => $user];
                } else {
                    return ['success' => false, 'message' => 'Credenciales incorrectas'];
                }
            } else {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Verificar si existe correo
    public function existeCorreo($correo) {
        $query = "SELECT id FROM usuarios WHERE correo_usuario = :correo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Obtener todos los roles
    public function obtenerRoles() {
        try {
            $query = "SELECT * FROM roles ORDER BY id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [['id' => 2, 'nombre_rol' => 'Cliente']];
        }
    }
    
    // Obtener estadísticas para admin
    public function obtenerEstadisticas() {
        try {
            $stats = [];
            
            // Total usuarios
            $stats['total_usuarios'] = $this->conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            
            // Total clientes
            $stats['total_clientes'] = $this->conn->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 2")->fetchColumn();
            
            // Total administradores
            $stats['total_admins'] = $this->conn->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 1")->fetchColumn();
            
            // Últimos usuarios registrados
            $query = "SELECT u.nombre_usuario, u.correo_usuario, u.fecha_registro, r.nombre_rol 
                     FROM usuarios u 
                     INNER JOIN roles r ON u.rol_id = r.id 
                     ORDER BY u.fecha_registro DESC LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $stats['ultimos_usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            return null;
        }
    }
        // Actualizar usuario
    public function actualizar($id, $nombre, $correo, $celular, $rol, $password = null) {
        try {
            if ($password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE usuarios 
                          SET nombre_usuario = :nombre, correo_usuario = :correo, celular_usuario = :celular, rol_id = :rol, contrasena_usuario = :password 
                          WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
            } else {
                $query = "UPDATE usuarios 
                          SET nombre_usuario = :nombre, correo_usuario = :correo, celular_usuario = :celular, rol_id = :rol 
                          WHERE id = :id";
                $stmt = $this->conn->prepare($query);
            }

            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':correo', $correo);
            $stmt->bindParam(':celular', $celular);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Eliminar usuario
    public function eliminar($id) {
        try {
            $query = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Usuario eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Buscar usuarios por nombre, correo o rol
    public function buscarUsuarios($termino) {
        try {
            $query = "SELECT u.*, r.nombre_rol 
                      FROM usuarios u 
                      INNER JOIN roles r ON u.rol_id = r.id 
                      WHERE u.nombre_usuario LIKE :termino 
                         OR u.correo_usuario LIKE :termino 
                         OR r.nombre_rol LIKE :termino
                      ORDER BY u.id DESC";
            $stmt = $this->conn->prepare($query);
            $like = "%$termino%";
            $stmt->bindParam(':termino', $like);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    //obtener los tecnicos
    // En tu modelo Usuario.php - método obtenerTecnicos()
    public function obtenerTecnicos() {
        try {
            // Obtenemos los usuarios con rol_id = 3 (Tecnico)
            $query = "SELECT id, nombre_usuario 
                    FROM usuarios 
                    WHERE rol_id = 3 
                    ORDER BY nombre_usuario";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $tecnicos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tecnicos[$row['id']] = $row['nombre_usuario'];
            }
            
            return $tecnicos;
            
        } catch (Exception $e) {
            // En caso de error, devolver array vacío
            return [];
        }
    }

    // Obtener todos los usuarios
    public function obtenerTodosLosUsuarios() {
        try {
            $query = "SELECT u.*, r.nombre_rol 
                      FROM usuarios u 
                      INNER JOIN roles r ON u.rol_id = r.id 
                      ORDER BY u.id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

}