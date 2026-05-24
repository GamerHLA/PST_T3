<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--BoxIcons-->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style_login.css">
    <title>Login</title>
    <style>
        /* Estilos para los mensajes de alerta */
        .alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 1000;
            animation: slideDown 0.5s ease;
            text-align: center;
            min-width: 300px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!--No olvidar las etiquetas de apertura y de cierre-->
    <div class="container">
        <!-- Mostrar mensajes de error o éxito -->
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-box">
            <!--Formulario del Login - MODIFICADO para enviar a login.php -->
            <form action="login.php" method="POST">
                <h1>Iniciar Sesion</h1> 
                <div class="input-box">
                    <input type="text" name="txtUsuario" id="txtUsuario" placeholder="Usuario o Correo" required>
                    <i class='bx bx-user'></i>
                </div>
                <div class="input-box">
                    <input type="password" name="txtPassword" id="txtPassword" placeholder="Contraseña" required>
                    <i class='bx bx-lock-alt'></i>
                </div>
                <div class="forgot-link">
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit" class="btn">Iniciar Sesion</button>
                <p>o Iniciar Sesion con las Plataformas Sociales</p>
                <div class="social-icons">
                    <a href="#"><i class='bx bxl-google'></i></a>
                    <a href="#"><i class='bx bxl-facebook-circle' ></i></a>
                    <a href="#"><i class='bx bxl-github' ></i></a>
                    <a href="#"><i class='bx bxl-linkedin' ></i></a>
                </div>
            </form>
        </div>
        <!--Segunda seccion-->
        <div class="register-panel">
            <h1>Hola, Bienvenido!</h1>
            <p>¿No tienes una Cuenta?</p>
            <button class="btn register-btn" onclick="window.location.href='register.php'">Registrarse</button>
        </div>
    </div>
    
    <script>
        // Ocultar mensajes después de 3 segundos
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 3000);
    </script>
</body>
</html>