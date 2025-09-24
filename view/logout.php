<?php
// logout.php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sesión Cerrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .modal-box {
            max-width: 400px;
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            text-align: center;
        }
        .modal-box h2 {
            margin-bottom: 1rem;
            color: #198754;
        }
    </style>
</head>
<body>
    <div class="modal-box">
        <h2>✅ Sesión cerrada</h2>
        <p>Has cerrado sesión correctamente.</p>
        <a href="login.php" class="btn btn-primary">Volver al Login</a>
    </div>
</body>
</html>
