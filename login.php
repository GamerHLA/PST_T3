<?php
session_start();
require_once 'config/conexion.php';

// Verificar si se envió el formulario
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Obtener datos del formulario
    $usuario = trim($_POST['txtUsuario']);
    $contrasena = $_POST['txtPassword'];
    
    // Validaciones básicas
    if(empty($usuario) || empty($contrasena)) {
        header("Location: index.php?error=Todos los campos son obligatorios");
        exit();
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por nombre de usuario o correo (incluyendo rol)
    $query = "SELECT u.id, u.usuario, u.correo, u.contrasena, u.estado,
                     r.nombre as rol_nombre, r.id as rol_id
              FROM usuarios u
              LEFT JOIN roles r ON u.rol_id = r.id
              WHERE u.usuario = :usuario OR u.correo = :usuario";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    
    // Verificar si existe el usuario
    if($stmt->rowCount() == 1) {
        $user = $stmt->fetch();
        
        // Verificar estado de la cuenta
        if($user['estado'] != 'activo') {
            header("Location: index.php?error=Tu cuenta está desactivada");
            exit();
        }
        
        // Verificar la contraseña
        if(password_verify($contrasena, $user['contrasena'])) {
            // Credenciales correctas - crear sesión con rol
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['usuario'];
            $_SESSION['user_correo'] = $user['correo'];
            $_SESSION['rol_nombre'] = $user['rol_nombre'] ?? 'usuario';
            $_SESSION['rol_id'] = $user['rol_id'];
            $_SESSION['login_time'] = time();
            
            // Redirigir al dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: index.php?error=Contraseña incorrecta");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: index.php?error=Usuario o correo no registrado");
        exit();
    }
} else {
    // Si alguien intenta acceder directamente
    header("Location: index.php");
    exit();
}
?>