<?php
session_start();
require_once '../config/conexion.php';
require_once '../modelos/Usuario.php';

$message = '';
$message_type = '';
$usuario = new Usuario();

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registro'])) {
    $nombre = trim($_POST['nombre_usuario']);
    $correo = trim($_POST['correo_usuario']);
    $celular = trim($_POST['celular_usuario']);
    $password1 = trim($_POST['contrasena1_usuario']);
    $password2 = trim($_POST['contrasena2_usuario']);
    $rol = $_POST['rol_usuario'];
    
    // Validaciones
    if (empty($nombre) || empty($correo) || empty($celular) || empty($password1) || empty($password2)) {
        $message = "Por favor complete todos los campos";
        $message_type = "danger";
    } elseif ($password1 !== $password2) {
        $message = "Las contraseñas no coinciden";
        $message_type = "danger";
    } elseif (strlen($password1) < 6) {
        $message = "La contraseña debe tener al menos 6 caracteres";
        $message_type = "danger";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $message = "Por favor ingrese un correo válido";
        $message_type = "danger";
    } else {
        $result = $usuario->registrar($nombre, $correo, $celular, $password1, $rol);
        
        if ($result['success']) {
            $message = $result['message'] . " Ya puede iniciar sesión.";
            $message_type = "success";
            
            // Limpiar formulario
            $nombre = $correo = $celular = '';
             header("Location: index.php");
        } else {
            $message = $result['message'];
            $message_type = "danger";
           
        }
    }
}

// Obtener roles para el select
try {
    $db = getDB();
    $roles_query = "SELECT * FROM roles";
    $roles_stmt = $db->prepare($roles_query);
    $roles_stmt->execute();
    $roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $roles = [['id' => 2, 'nombre_rol' => 'Cliente']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style2.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Formulario de Registro</title>
</head>
<body>

<main class="d-flex justify-content-center align-items-center vh-100">
    <div class="contenedor bg-light border border-success shadow-lg rounded-4 bg-white bg-opacity-50 p-4">
        
        <h3 class="text-center mb-4">Registro Usuario</h3>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> text-center" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="d-flex w-100">
            <div class="formulario">
                <div class="mb-3">
                    <label for="nombre_usuario" class="form-label">Nombre usuario:</label>
                    <input type="text" name="nombre_usuario" id="nombre_usuario" 
                           class="form-control bg-white bg-opacity-50 rounded-pill" 
                           placeholder="Ingrese su nombre" 
                           value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="correo_usuario" class="form-label">Correo electrónico:</label>
                    <input type="email" name="correo_usuario" id="correo_usuario" 
                           class="form-control bg-white bg-opacity-50 rounded-pill" 
                           placeholder="ejemplo@correo.com"
                           value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="rol_usuario" class="form-label">Seleccione su rol:</label>
                    <select name="rol_usuario" id="rol_usuario" class="form-control bg-white bg-opacity-50 rounded-pill" required>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id']; ?>" <?php echo ($rol['id'] == 2) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="celular_usuario" class="form-label">Número de celular:</label>
                    <input type="text" name="celular_usuario" id="celular_usuario" 
                           class="form-control bg-white bg-opacity-50 rounded-pill" 
                           placeholder="Ingrese su número de celular"
                           value="<?php echo isset($celular) ? htmlspecialchars($celular) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="contrasena1_usuario" class="form-label">Contraseña:</label>
                    <input type="password" name="contrasena1_usuario" id="contrasena1_usuario" 
                           class="form-control bg-white bg-opacity-50 rounded-pill" 
                           placeholder="Ingrese su contraseña (mínimo 6 caracteres)" required>
                </div>
                <div class="mb-3">
                    <label for="contrasena2_usuario" class="form-label">Confirme su contraseña:</label>
                    <input type="password" name="contrasena2_usuario" id="contrasena2_usuario" 
                           class="form-control bg-white bg-opacity-50 rounded-pill" 
                           placeholder="Confirme su contraseña" required>
                </div>
                <div class="mb-3 d-flex justify-content-center">
                    <button type="button" onclick="window.location.href='index.php'" class="btn btn-primary rounded-pill ms-2 me-2">Regresar</button>
                    <input type="submit" name="registro" class="btn btn-success rounded-pill" value="Registrarse">
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-center">
                <img class="lobby2" src="img/imagen4.jpg" alt="Imagen representativa">
            </div>
        </form>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>