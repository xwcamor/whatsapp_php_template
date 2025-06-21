<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Amigos</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 10px;
            top: 5px;
            font-size: 24px;
            cursor: pointer;
            color: #aaa;
        }

        .close-modal:hover {
            color: #000;
        }

        #mensajeResultado {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }

        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="friends-container">
            <div class="friends-header">
                <h2>Lista de Amigos</h2>
                <button id="btnAgregarAmigo" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Agregar Nuevo Amigo
                </button>
                <a href="chat.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Volver al Chat
                </a>
            </div>

            <!-- Modal para agregar amigos -->
            <div id="modalAgregarAmigo" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h3>Agregar Nuevo Amigo</h3>
                    <form id="formAgregarAmigo">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Ingresa el correo electrónico de tu amigo" required>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i> Enviar Solicitud de Amistad
                        </button>
                    </form>
                    <div id="mensajeResultado"></div>
                </div>
            </div>

            <?php if(isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="friends-list">
                <h3>Solicitudes de Amistad Pendientes</h3>
                <?php
                require_once '../models/Amigo.php';
                require_once '../config/database.php';

                $database = new Database();
                $db = $database->getConnection();
                $amigo = new Amigo($db);
                
                $solicitudes = $amigo->obtenerSolicitudes($_SESSION['usuario_id']);
                
                if ($solicitudes && count($solicitudes) > 0): ?>
                    <ul class="friend-requests">
                        <?php foreach($solicitudes as $solicitud): ?>
                            <li class="friend-item">
                                <div class="friend-info">
                                    <span class="friend-name"><?php echo htmlspecialchars($solicitud['nombre']); ?></span>
                                    <span class="friend-email"><?php echo htmlspecialchars($solicitud['email']); ?></span>
                                </div>
                                <div class="friend-actions">
                                    <?php if($solicitud['estado'] == 'pendiente'): ?>
                                        <form class="formRespuestaSolicitud">
                                            <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                                            <button type="button" class="btn-success responderSolicitud" data-respuesta="aceptar" title="Aceptar solicitud">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn-danger responderSolicitud" data-respuesta="rechazar" title="Rechazar solicitud">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="friend-status status-<?php echo $solicitud['estado']; ?>">
                                            <?php 
                                            echo $solicitud['estado'] == 'aceptado' ? 'Aceptada' : 'Rechazada'; 
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-friends">No tienes solicitudes de amistad pendientes.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Modal
        const modal = document.getElementById('modalAgregarAmigo');
        const btnAgregar = document.getElementById('btnAgregarAmigo');
        const closeModal = document.querySelector('.close-modal');
        const mensajeResultado = document.getElementById('mensajeResultado');

        btnAgregar.onclick = function() {
            modal.style.display = "block";
        }

        closeModal.onclick = function() {
            modal.style.display = "none";
            mensajeResultado.style.display = "none";
            mensajeResultado.className = "";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                mensajeResultado.style.display = "none";
                mensajeResultado.className = "";
            }
        }

        // Manejar envío del formulario de agregar amigo
        document.getElementById('formAgregarAmigo').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../controllers/UsuarioController.php?action=agregarAmigo', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                mensajeResultado.style.display = "block";
                if(data.success) {
                    mensajeResultado.className = "mensaje-exito";
                    mensajeResultado.textContent = data.mensaje;
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mensajeResultado.className = "mensaje-error";
                    mensajeResultado.textContent = data.mensaje;
                }
            })
            .catch(error => {
                mensajeResultado.style.display = "block";
                mensajeResultado.className = "mensaje-error";
                mensajeResultado.textContent = "Error al procesar la solicitud. Por favor, intenta nuevamente.";
            });
        });

        // Manejar respuestas a solicitudes de amistad
        document.querySelectorAll('.responderSolicitud').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('form');
                const solicitudId = form.querySelector('input[name="solicitud_id"]').value;
                const respuesta = this.dataset.respuesta;

                const formData = new FormData();
                formData.append('solicitud_id', solicitudId);
                formData.append('respuesta', respuesta);

                fetch('../controllers/UsuarioController.php?action=responderSolicitud', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert(data.mensaje || 'Error al procesar la solicitud');
                    }
                })
                .catch(error => {
                    alert("Error al procesar la solicitud. Por favor, intenta nuevamente.");
                });
            });
        });
    </script>
</body>
</html> 