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
    <title>WhatsApp Clone - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <img src="img/whatsapp-logo.png" alt="WhatsApp Logo">
            </div>
            <h2>Iniciar Sesión</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="../controllers/UsuarioController.php?action=login" method="POST" id="loginForm">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
            
            <div class="register-link">
                ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
            </div>
        </div>
    </div>
</body>
</html> 