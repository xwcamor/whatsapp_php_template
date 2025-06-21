<?php 
session_start();
// Redirigir si ya está autenticado
if (isset($_SESSION['usuario_id'])) {
    header('Location: chat.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Registro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <img src="img/whatsapp-logo.png" alt="WhatsApp Logo">
            </div>
            <h2>Crear Cuenta</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="../controllers/UsuarioController.php?action=registrar" method="POST" id="registroForm">
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre completo" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirmar contraseña" required>
                </div>
                <button type="submit" class="btn-primary">Registrarse</button>
            </form>
            
            <div class="login-link">
                ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            var password = document.querySelector('input[name="password"]').value;
            var confirm = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
            }
        });
    </script>
</body>
</html> 