<?php
session_start();
require_once 'config/conexion.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    if(empty($usuario) || empty($correo) || empty($contrasena)) {
        $error = "Todos los campos son obligatorios";
    } elseif(!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo electrónico inválido";
    } elseif(strlen($contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden";
    } elseif(strlen($usuario) < 3) {
        $error = "El usuario debe tener al menos 3 caracteres";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $check_query = "SELECT id FROM usuarios WHERE usuario = :usuario OR correo = :correo";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':usuario', $usuario);
        $check_stmt->bindParam(':correo', $correo);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            $error = "El usuario o correo ya está registrado";
        } else {
            // Obtener el ID del rol 'usuario'
            $rol_query = "SELECT id FROM roles WHERE nombre = 'usuario'";
            $rol_stmt = $db->prepare($rol_query);
            $rol_stmt->execute();
            $rol = $rol_stmt->fetch();
            $rol_id = $rol ? $rol['id'] : null;
            
            $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT);
            
            $insert_query = "INSERT INTO usuarios (usuario, correo, contrasena, rol_id, estado) 
                           VALUES (:usuario, :correo, :contrasena, :rol_id, 'activo')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':usuario', $usuario);
            $insert_stmt->bindParam(':correo', $correo);
            $insert_stmt->bindParam(':contrasena', $hashed_password);
            $insert_stmt->bindParam(':rol_id', $rol_id);
            
            if($insert_stmt->execute()) {
                $success = "¡Registro exitoso! Redirigiendo...";
                header("refresh:2;url=index.php");
            } else {
                $error = "Error al registrar usuario";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style_register.css">
    <title>Registro</title>
    <style>
        .alert-error, .alert-success {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            text-align: center;
            min-width: 300px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <?php if($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-box">
            <form action="" method="POST">
                <h1>Crear Cuenta</h1>
                
                <div class="input-box">
                    <input type="text" name="usuario" placeholder="Usuario" required>
                    <i class='bx bx-user'></i>
                </div>
                
                <div class="input-box">
                    <input type="email" name="correo" placeholder="Correo electrónico" required>
                    <i class='bx bx-envelope'></i>
                </div>
                
                <div class="input-box">
                    <input type="password" name="contrasena" placeholder="Contraseña" required>
                    <i class='bx bx-lock-alt'></i>
                </div>
                
                <div class="input-box">
                    <input type="password" name="confirmar_contrasena" placeholder="Confirmar contraseña" required>
                    <i class='bx bx-check-circle'></i>
                </div>
                
                <div class="forgot-link">
                    <a href="index.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
                
                <button type="submit" class="btn">Registrarse</button>
                
                <p>o Regístrate con tus Redes Sociales</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google'></i></a>
                    <a href="#"><i class='bx bxl-facebook-circle'></i></a>
                    <a href="#"><i class='bx bxl-github'></i></a>
                    <a href="#"><i class='bx bxl-linkedin'></i></a>
                </div>
            </form>
        </div>
        
        <div class="register-panel">
            <h1>¡Bienvenido!</h1>
            <p>¿Ya tienes una cuenta?</p>
            <button class="btn register-btn" onclick="window.location.href='index.php'">Iniciar Sesión</button>
        </div>
    </div>
    
    <script>
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert-error, .alert-success');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>
</html>