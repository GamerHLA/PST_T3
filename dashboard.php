<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Debes iniciar sesión primero");
    exit();
}

require_once 'config/conexion.php';

$database = new Database();
$db = $database->getConnection();

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';

// ============================================
// PROCESAR ACCIONES DE ADMINISTRADOR
// ============================================

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_admin'])) {
    $accion = $_POST['accion_admin'];
    
    // Crear usuario
    if($accion == 'crear') {
        $usuario = trim($_POST['usuario']);
        $correo = trim($_POST['correo']);
        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $rol_id = $_POST['rol_id'];
        
        $query = "INSERT INTO usuarios (usuario, correo, contrasena, rol_id, estado) 
                  VALUES (:usuario, :correo, :contrasena, :rol_id, 'activo')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':contrasena', $contrasena);
        $stmt->bindParam(':rol_id', $rol_id);
        
        if($stmt->execute()) {
            $mensaje = "Usuario creado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear usuario";
            $tipo_mensaje = "error";
        }
    }
    
    // Editar usuario
    if($accion == 'editar') {
        $id = $_POST['id'];
        $usuario = trim($_POST['usuario']);
        $correo = trim($_POST['correo']);
        $rol_id = $_POST['rol_id'];
        
        $query = "UPDATE usuarios SET usuario = :usuario, correo = :correo, rol_id = :rol_id WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':rol_id', $rol_id);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensaje = "Usuario actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar usuario";
            $tipo_mensaje = "error";
        }
    }
    
    // Cambiar estado (activar/desactivar)
    if($accion == 'cambiar_estado') {
        $id = $_POST['id'];
        $nuevo_estado = $_POST['estado'];
        
        $query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $id);
        
        if($stmt->execute()) {
            $mensaje = "Usuario " . ($nuevo_estado == 'activo' ? 'activado' : 'desactivado');
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al cambiar estado";
            $tipo_mensaje = "error";
        }
    }
    
    // Eliminar usuario
    if($accion == 'eliminar') {
        $id = $_POST['id'];
        
        if($id == $_SESSION['user_id']) {
            $mensaje = "No puedes eliminar tu propia cuenta";
            $tipo_mensaje = "error";
        } else {
            $query = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if($stmt->execute()) {
                $mensaje = "Usuario eliminado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar usuario";
                $tipo_mensaje = "error";
            }
        }
    }
    
    if($mensaje) {
        header("Location: dashboard.php?mensaje=" . urlencode($mensaje) . "&tipo=" . $tipo_mensaje);
        exit();
    }
}

// Obtener mensajes de URL
$mensaje = $_GET['mensaje'] ?? '';
$tipo_mensaje = $_GET['tipo'] ?? '';

// Obtener estadísticas
$stats_query = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                FROM usuarios";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Obtener lista de usuarios con sus roles
$usuarios_query = "SELECT u.*, r.nombre as rol_nombre 
                   FROM usuarios u
                   LEFT JOIN roles r ON u.rol_id = r.id
                   ORDER BY u.id DESC";
$usuarios_stmt = $db->prepare($usuarios_query);
$usuarios_stmt->execute();
$usuarios = $usuarios_stmt->fetchAll();

// Obtener lista de roles para selects
$roles_query = "SELECT * FROM roles ORDER BY nivel DESC";
$roles_stmt = $db->prepare($roles_query);
$roles_stmt->execute();
$roles = $roles_stmt->fetchAll();

$es_admin = ($_SESSION['rol_nombre'] == 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/style_dashboard.css">
    <title>Menú Administrador</title>
    <style>
    </style>
</head>
<body>

<button class="menu-toggle" id="menuToggle">
    <i class='bx bx-menu'></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <p>Sistema</p>
    </div>
    <div class="sidebar-menu">
        <div class="menu-item active" data-page="dashboard">
            <i class='bx bxs-dashboard'></i>
            <span>Dashboard</span>
        </div>
        <?php if($es_admin): ?>
        <div class="menu-item" data-page="usuarios">
            <i class='bx bxs-group'></i>
            <span>Gestión de Usuarios</span>
        </div>
        <?php endif; ?>
        <div class="menu-item" data-page="perfil">
            <i class='bx bxs-user'></i>
            <span>Mi Perfil</span>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <div class="top-bar">
        <div class="page-title">
            <h1 id="pageTitle">Dashboard</h1>
            <p id="pageDesc">Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        <div class="user-info">
            <span class="user-name">
                <i class='bx bx-user-circle'></i>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <span class="role-badge"><?php echo htmlspecialchars($_SESSION['rol_nombre']); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class='bx bx-log-out'></i> Salir
            </button>
        </div>
    </div>

    <?php if($mensaje): ?>
    <div class="toast toast-<?php echo $tipo_mensaje; ?>" id="toastMessage">
        <i class='bx bx-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'error-circle'; ?>'></i>
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>

    <!-- Página Dashboard -->
    <div id="dashboardPage">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $stats['total_usuarios']; ?></h3>
                    <p>Total Usuarios</p>
                </div>
                <div class="stat-icon">
                    <i class='bx bxs-group'></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $stats['activos']; ?></h3>
                    <p>Usuarios Activos</p>
                </div>
                <div class="stat-icon">
                    <i class='bx bxs-user-check'></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $stats['inactivos']; ?></h3>
                    <p>Usuarios Inactivos</p>
                </div>
                <div class="stat-icon">
                    <i class='bx bxs-user-x'></i>
                </div>
            </div>
        </div>

        <div class="content-panel">
            <div class="panel-header">
                <h2>Bienvenido al Panel de Control</h2>
            </div>
            <p>Has iniciado sesión como <strong><?php echo htmlspecialchars($_SESSION['rol_nombre']); ?></strong>.</p>
            <?php if($es_admin): ?>
            <p>Desde aquí puedes gestionar usuarios, activar/desactivar cuentas y administrar el sistema.</p>
            <?php else: ?>
            <p>Este es tu panel de usuario. Si necesitas ayuda, contacta al administrador.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Página Gestión de Usuarios (solo admin) -->
    <div id="usuariosPage" style="display: none;">
        <div class="content-panel">
            <div class="panel-header">
                <h2>Gestión de Usuarios</h2>
                <button class="btn-primary" onclick="openModal('crear')">
                    <i class='bx bx-user-plus'></i> Nuevo Usuario
                </button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['rol_nombre'] ?? 'usuario'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $usuario['estado']; ?>">
                                    <?php echo $usuario['estado'] == 'activo' ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-edit" onclick='editarUsuario(<?php echo json_encode($usuario); ?>)'>
                                    <i class='bx bx-edit'></i>
                                </button>
                                <?php if($usuario['estado'] == 'activo'): ?>
                                <button class="btn-disable" onclick="cambiarEstado(<?php echo $usuario['id']; ?>, 'inactivo')">
                                    <i class='bx bx-block'></i>
                                </button>
                                <?php else: ?>
                                <button class="btn-enable" onclick="cambiarEstado(<?php echo $usuario['id']; ?>, 'activo')">
                                    <i class='bx bx-check'></i>
                                </button>
                                <?php endif; ?>
                                <?php if($usuario['id'] != $_SESSION['user_id']): ?>
                                <button class="btn-delete" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)">
                                    <i class='bx bx-trash'></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Página Perfil -->
    <div id="perfilPage" style="display: none;">
        <div class="content-panel">
            <div class="panel-header">
                <h2>Mi Perfil</h2>
            </div>
            <form method="POST" action="actualizar_perfil.php">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" value="<?php echo htmlspecialchars($_SESSION['user_correo']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña (dejar en blanco para no cambiar)</label>
                    <input type="password" name="nueva_contrasena">
                </div>
                <button type="submit" class="btn-primary">Actualizar Perfil</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Crear Usuario</h3>
            <i class='bx bx-x modal-close' onclick="closeModal()"></i>
        </div>
        <form method="POST" id="userForm">
            <input type="hidden" name="accion_admin" id="formAccion">
            <input type="hidden" name="id" id="userId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="usuario" id="usuarioInput" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" id="correoInput" required>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label>Contraseña</label>
                    <input type="password" name="contrasena" id="contrasenaInput">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol_id" id="rolSelect" required>
                        <?php foreach($roles as $rol): ?>
                        <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars($rol['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Navegación por pestañas
const menuItems = document.querySelectorAll('.menu-item');
const pages = {
    dashboard: document.getElementById('dashboardPage'),
    usuarios: document.getElementById('usuariosPage'),
    perfil: document.getElementById('perfilPage')
};

menuItems.forEach(item => {
    item.addEventListener('click', () => {
        const page = item.dataset.page;
        
        menuItems.forEach(mi => mi.classList.remove('active'));
        item.classList.add('active');
        
        Object.keys(pages).forEach(key => {
            if(pages[key]) pages[key].style.display = 'none';
        });
        
        if(pages[page]) pages[page].style.display = 'block';
        
        const titles = {
            dashboard: 'Dashboard',
            usuarios: 'Gestión de Usuarios',
            perfil: 'Mi Perfil'
        };
        document.getElementById('pageTitle').innerText = titles[page] || 'Dashboard';
    });
});

// Modal functions
function openModal(action, user = null) {
    const modal = document.getElementById('userModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAccion = document.getElementById('formAccion');
    const passwordGroup = document.getElementById('passwordGroup');
    
    if(action === 'crear') {
        modalTitle.innerText = 'Crear Usuario';
        formAccion.value = 'crear';
        document.getElementById('userId').value = '';
        document.getElementById('usuarioInput').value = '';
        document.getElementById('correoInput').value = '';
        document.getElementById('contrasenaInput').value = '';
        document.getElementById('contrasenaInput').required = true;
        passwordGroup.style.display = 'block';
    } else if(action === 'editar' && user) {
        modalTitle.innerText = 'Editar Usuario';
        formAccion.value = 'editar';
        document.getElementById('userId').value = user.id;
        document.getElementById('usuarioInput').value = user.usuario;
        document.getElementById('correoInput').value = user.correo;
        document.getElementById('contrasenaInput').value = '';
        document.getElementById('contrasenaInput').required = false;
        passwordGroup.style.display = 'none';
        const rolSelect = document.getElementById('rolSelect');
        for(let i = 0; i < rolSelect.options.length; i++) {
            if(rolSelect.options[i].text.toLowerCase() === user.rol_nombre) {
                rolSelect.selectedIndex = i;
                break;
            }
        }
    }
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('active');
}

function editarUsuario(user) {
    openModal('editar', user);
}

function cambiarEstado(id, estado) {
    if(confirm('¿Estás seguro de ' + (estado === 'activo' ? 'activar' : 'desactivar') + ' este usuario?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion_admin" value="cambiar_estado">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="estado" value="${estado}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function eliminarUsuario(id) {
    if(confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion_admin" value="eliminar">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Cerrar toast automáticamente
setTimeout(() => {
    const toast = document.getElementById('toastMessage');
    if(toast) {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }
}, 4000);

// Menú toggle para móvil
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
if(menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}
</script>
</body>
</html>