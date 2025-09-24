<?php
session_start();
require_once '../config/conexion.php';
require_once '../modelos/Usuario.php';

$error_message = '';
$usuario = new Usuario();

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $correo = trim($_POST['correo_usuario']);
    $password = trim($_POST['contra1_usuario']);
    
    if (!empty($correo) && !empty($password)) {
        $result = $usuario->login($correo, $password);
        
        if ($result['success']) {
            $user_data = $result['user'];
            
            // Guardamos toda la info del usuario en la sesión en UN SOLO ARRAY
            $_SESSION['user'] = [
                'id'     => $user_data['id'],
                'nombre' => $user_data['nombre_usuario'],
                'correo' => $user_data['correo_usuario'],
                'rol_id' => $user_data['rol_id'],
                'rol'    => $user_data['nombre_rol']
            ];
            
            // Redirigir según el rol - USANDO RUTAS RELATIVAS
            if ($user_data['rol_id'] == 1) {
                header("Location: admin.php");  // Mismo directorio
                exit();
            } elseif ($user_data['rol_id'] == 2) {
                header("Location: cliente.php"); // Mismo directorio
                exit();
            } elseif ($user_data['rol_id'] == 3) {
                header("Location: tecnico.php"); // Mismo directorio
                exit();
            } else {
                header("Location: login.php"); // Mismo directorio
                exit();
            }

        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = "Por favor complete todos los campos";
    }
}
?>

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style2.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Formulario de Login</title>
</head>
<body>

<main class="d-flex justify-content-center align-items-center vh-100">
    <div class="contenedor bg-light border border-success shadow-lg rounded-4 bg-white bg-opacity-50 p-4">

        <h1 class="d-flex justify-content-center">Bienvenido</h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="d-flex w-100">

            <div class="formulario align-items-center"> <br> <br>

                <div class="d-flex align-items-center justify-content-center p-3">
                    <img class="imagenUs rounded-circle mr-2" src="img/usuario2.png" alt="" width="110">
                </div>

                <div class="mb-3">
                    <label for="correo_usuario" class="form-label">Correo electrónico:</label>
                    <input type="email" name="correo_usuario" id="correo_usuario" class="form-control bg-white bg-opacity-50 rounded-pill" placeholder="ejemplo@correo.holis.com" required>
                </div>

                <div class="mb-3">
                    <label for="contra1_usuario" class="form-label">Contraseña:</label>
                    <input type="password" name="contra1_usuario" id="contra1_usuario" class="form-control bg-white bg-opacity-50 rounded-pill" placeholder="Ingrese su contraseña" required>
                </div>

                <div class="d-flex justify-content-center">
                    <button type="submit" name="login" class="btn btn-primary rounded-pill me-2">Ingresar</button>
                    <button type="button" onclick="window.location.href='Registro.php'" class="btn btn-success rounded-pill ms-2">Registrarse</button>
                </div>

            </div>

            <div class="d-flex align-items-center justify-content-center">
                <img class="lobby2 shadow" src="img/imagen2.jpg" alt="Imagen representativa" width="80" height="20">
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>