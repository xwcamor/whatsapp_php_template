<?php 
session_start();
if (!isset($_SESSION['usuario_id'])) {
    $base_url = dirname($_SERVER['PHP_SELF']);
    header("Location: " . $base_url . "/login.php");
    exit();
}

// Verificar si la sesión ha expirado
$session_lifetime = ini_get('session.gc_maxlifetime');
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    // La sesión ha expirado
    session_unset();
    session_destroy();
    $base_url = dirname($_SERVER['PHP_SELF']);
    header("Location: " . $base_url . "/login.php");
    exit();
}
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Clone - Chat</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.18.3/index.js" type="module"></script>
    <style>
        :root {
            --primary-color: #075E54;
            --secondary-color: #128C7E;
            --accent-color: #25D366;
            --light-bg: #f0f2f5;
            --chat-bg: #E5DDD5;
            --message-sent: #DCF8C6;
            --message-received: #ffffff;
            --text-primary: #111b21;
            --text-secondary: #667781;
            --border-color: #e9edef;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
        }

        /* Container principal */
        .chat-container {
            display: flex;
            height: 100vh;
            background-color: var(--light-bg);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar */
        .sidebar {
            width: 30%;
            background-color: #ffffff;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background-color: var(--primary-color);
            padding: 15px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .user-info h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .btn-icon:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Búsqueda */
        .chat-search {
            padding: 12px;
            background-color: var(--light-bg);
        }

        .chat-search input {
            width: 100%;
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            background-color: white;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .chat-search input:focus {
            outline: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Lista de chats */
        .chat-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .chat-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
        }

        .chat-item:hover {
            background-color: var(--light-bg);
        }

        .chat-item.active {
            background-color: #f0f2f5;
            border-left: 4px solid var(--accent-color);
        }

        .chat-item-content h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .chat-item-content p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Chat principal */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--chat-bg);
            position: relative;
        }

        .chat-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="%23ffffff" fill-opacity="0.05"/></svg>');
            opacity: 0.5;
            pointer-events: none;
        }

        #currentChatHeader {
            background-color: var(--primary-color);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        #currentChatHeader .profile-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        #currentChatHeader h3 {
            margin: 0;
            color: white;
        }

        /* Mensajes */
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 65%;
            padding: 10px 15px;
            border-radius: 12px;
            position: relative;
            font-size: 0.95rem;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .message p {
            margin: 0;
            padding-right: 35px;
            word-wrap: break-word;
        }

        .message-info {
            position: absolute;
            bottom: -18px;
            font-size: 0.75rem;
            color: #8696a0;
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .message-sent .message-info {
            right: 5px;
        }

        .message-received .message-info {
            left: 5px;
        }

        .edited-indicator {
            font-size: 0.7rem;
            color: #8696a0;
            font-style: italic;
        }

        .message-sent {
            background-color: var(--message-sent);
            margin-left: auto;
            border-radius: 12px 12px 2px 12px;
        }

        .message-sent::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: -8px;
            width: 8px;
            height: 13px;
            background-color: var(--message-sent);
            clip-path: polygon(0 0, 100% 100%, 0 100%);
        }

        .message-received {
            background-color: var(--message-received);
            margin-right: auto;
            border-radius: 12px 12px 12px 2px;
        }

        .message-received::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -8px;
            width: 8px;
            height: 13px;
            background-color: var(--message-received);
            clip-path: polygon(0 100%, 100% 100%, 100% 0);
        }

        .message-time {
            position: absolute;
            bottom: 8px;
            right: 12px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Estilos para mensajes eliminados */
        .message.deleted {
            background-color: #f0f0f0;
            font-style: italic;
            color: #666;
        }

        .message.deleted p {
            opacity: 0.8;
        }

        /* Estilos para mensajes editados */
        .message.edited .message-time::before {
            content: '(editado) ';
            font-style: italic;
        }

        /* Menú de opciones de mensaje */
        .message-options {
            position: absolute;
            top: 50%;
            right: 5px;
            transform: translateY(-50%);
            display: none;
            gap: 2px;
            z-index: 100;
        }

        .message:hover .message-options {
            display: flex;
        }

        .message-option-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .message-option-btn:hover {
            background-color: rgba(0, 0, 0, 0.1);
            color: var(--accent-color);
        }

        /* Modal de edición de mensaje */
        .edit-message-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1001;
            width: 90%;
            max-width: 500px;
        }

        .edit-message-modal.show {
            display: block;
        }

        .edit-message-modal textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            resize: vertical;
        }

        .edit-message-modal .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-message-modal button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-message-modal .save-btn {
            background-color: var(--accent-color);
            color: white;
        }

        .edit-message-modal .cancel-btn {
            background-color: #ddd;
        }

        /* Input de mensaje */
        .chat-input {
            padding: 15px 20px;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid var(--border-color);
        }

        .chat-input input {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 24px;
            background-color: white;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .chat-input input:focus {
            outline: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .chat-input button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        .chat-input button:hover:not(:disabled) {
            background-color: var(--secondary-color);
            transform: scale(1.05);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .chat-input button:active:not(:disabled) {
            transform: scale(0.95);
        }

        .chat-input button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
            opacity: 0.7;
        }

        .chat-input button i {
            font-size: 1.2rem;
        }

        /* Badges y notificaciones */
        .solicitudes-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #FF3B30;
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 0.75rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(255, 59, 48, 0.3);
        }

        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message {
            animation: fadeIn 0.3s ease;
        }

        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        /* Clase adicional para cuando el modal está activo */
        .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
            margin: 0 auto;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.3s;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--light-bg);
        }

        .close-modal:hover {
            color: var(--text-primary);
            background-color: #e5e5e5;
        }

        /* Estilos para el modal de perfil */
        #modalPerfil .modal-content {
            padding-top: 40px;
        }

        #modalPerfil h3 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-primary);
            font-size: 1.5rem;
        }

        #formPerfil .form-group {
            margin-bottom: 20px;
        }

        #formPerfil label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        #formPerfil input,
        #formPerfil textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: white;
        }

        #formPerfil input:focus,
        #formPerfil textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        }

        #formPerfil input:read-only {
            background-color: var(--light-bg);
            cursor: not-allowed;
        }

        #formPerfil button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        #formPerfil button[type="submit"]:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        #formPerfil button[type="submit"]:active {
            transform: translateY(0);
        }

        #mensajePerfilResultado {
            margin-top: 20px;
            padding: 12px 15px;
            border-radius: 8px;
            text-align: center;
        }

        /* Mensajes de estado */
        .mensaje-resultado {
            padding: 12px 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 0.95rem;
            display: none;
        }

        .mensaje-exito {
            background-color: #dcf8e9;
            color: #0a5d3d;
            border: 1px solid #b8e6d1;
        }

        .mensaje-error {
            background-color: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f8c4c4;
        }

        /* Estilos para el emoji picker */
        .emoji-picker-container {
            position: absolute;
            bottom: 100%;
            left: 0;
            z-index: 1000;
            display: none;
        }

        .emoji-picker-container.show {
            display: block;
        }

        emoji-picker {
            width: 300px;
            height: 400px;
            margin-bottom: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            border-radius: 10px;
        }

        .btn-emoji {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            color: #666;
            transition: color 0.3s;
        }

        .btn-emoji:hover {
            color: var(--accent-color);
        }

        /* Estilos para imágenes de perfil */
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }

        .user-info-left {
            display: flex;
            align-items: center;
        }

        /* Estilos para el modal de perfil */
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .profile-image-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto;
            display: block;
            border: 3px solid var(--accent-color);
        }

        .change-photo-btn {
            position: absolute;
            bottom: 0;
            right: 50%;
            transform: translateX(50%);
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .change-photo-btn:hover {
            background-color: var(--secondary-color);
        }

        #imageInput {
            display: none;
        }

        /* Estilos para el menú de opciones de foto */
        .photo-options-menu {
            position: absolute;
            bottom: 45px;
            right: 50%;
            transform: translateX(50%);
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            padding: 8px 0;
            display: none;
            z-index: 1000;
            min-width: 200px;
        }

        .photo-options-menu.show {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        .photo-option {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
            color: var(--text-primary);
        }

        .photo-option:hover {
            background-color: var(--light-bg);
        }

        .photo-option i {
            width: 20px;
            text-align: center;
            color: var(--secondary-color);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate(50%, 10px);
            }
            to {
                opacity: 1;
                transform: translate(50%, 0);
            }
        }

        /* Estilos para la vista previa de la cámara */
        .camera-preview {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .camera-preview.show {
            display: flex;
        }

        #videoElement {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 8px;
        }

        .camera-controls {
            margin-top: 20px;
            display: flex;
            gap: 20px;
        }

        .camera-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .camera-btn.capture {
            background-color: var(--accent-color);
            color: white;
        }

        .camera-btn.cancel {
            background-color: #dc3545;
            color: white;
        }

        .camera-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        #canvasElement {
            display: none;
        }

        .message.deleted {
            opacity: 0.7;
        }
        
        .message.deleted p {
            font-style: italic;
            color: #666;
        }
        
        .message.edited .message-time {
            font-size: 0.8em;
            color: #666;
        }
        
        .message-time {
            display: block;
            font-size: 0.8em;
            margin-top: 4px;
            color: #888;
        }
        
        .message-options {
            display: none;
            position: absolute;
            right: 5px;
            top: 5px;
        }
        
        .message:hover .message-options {
            display: flex;
            gap: 5px;
        }
        
        .message-option-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .message-option-btn:hover {
            background-color: rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .last-message {
            color: #667781;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90%;
            margin: 0;
        }

        .last-message.deleted-message {
            font-style: italic;
            color: #8696a0;
        }

        .chat-item-content {
            flex: 1;
            min-width: 0; /* Para que text-overflow funcione correctamente */
            padding-right: 10px;
        }

        .chat-item-content h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="sidebar">
            <div class="chat-header">
                <div class="user-info">
                    <div class="user-info-left">
                        <img src="../controllers/UsuarioController.php?action=obtenerImagenPerfil" alt="Tu perfil" class="profile-image">
                        <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                    </div>
                    <div class="actions">
                        <button class="btn-icon btn-solicitudes" id="btnVerSolicitudes" title="Ver solicitudes">
                            <i class="fas fa-user-friends"></i>
                            <span class="solicitudes-badge" id="solicitudesBadge">0</span>
                        </button>
                        <button class="btn-icon" id="btnAgregarAmigo" title="Agregar amigos">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button class="btn-icon" id="btnPerfil" title="Mi Perfil">
                            <i class="fas fa-user-cog"></i>
                        </button>
                        <button class="btn-icon" id="btnLogout" title="Cerrar sesión">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="chat-search">
                <input type="text" placeholder="Buscar o empezar un nuevo chat" id="searchChat">
            </div>
            <ul class="chat-list" id="chatList">
                <!-- Los chats se cargarán dinámicamente aquí -->
            </ul>
        </div>
        
        <div class="chat-main">
            <div class="chat-header" id="currentChatHeader">
                <h3>Selecciona un chat para comenzar</h3>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Los mensajes se cargarán dinámicamente aquí -->
            </div>
            
            <div class="chat-input">
                <div style="position: relative;">
                    <button type="button" class="btn-emoji" id="btnEmoji">
                        <i class="far fa-smile"></i>
                    </button>
                    <div class="emoji-picker-container" id="emojiPicker">
                        <emoji-picker></emoji-picker>
                    </div>
                </div>
                <input type="text" id="messageInput" placeholder="Escribe un mensaje aquí" disabled>
                <button type="button" class="btn-primary" id="sendMessage" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
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
                    <i class="fas fa-user-plus"></i> Enviar Solicitud
                </button>
            </form>
            <div id="mensajeResultado"></div>
        </div>
    </div>

    <!-- Modal para ver solicitudes de amistad -->
    <div id="modalSolicitudes" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeSolicitudes">&times;</span>
            <h3>Solicitudes de Amistad</h3>
            <div id="solicitudesContainer" class="solicitudes-container">
                <!-- Las solicitudes se cargarán aquí dinámicamente -->
            </div>
        </div>
    </div>

    <!-- Modal para el perfil -->
    <div id="modalPerfil" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closePerfil">&times;</span>
            <div class="profile-image-container">
                <img src="../controllers/UsuarioController.php?action=obtenerImagenPerfil" alt="Foto de perfil" class="profile-image-large" id="profileImage">
                <button type="button" class="change-photo-btn" id="changePhotoBtn" title="Cambiar foto">
                    <i class="fas fa-camera"></i>
                </button>
                <div class="photo-options-menu" id="photoOptionsMenu">
                    <div class="photo-option" id="uploadPhotoOption">
                        <i class="fas fa-upload"></i>
                        <span>Subir foto</span>
                    </div>
                    <div class="photo-option" id="takePhotoOption">
                        <i class="fas fa-camera"></i>
                        <span>Tomar foto</span>
                    </div>
                </div>
            </div>

            <!-- Vista previa de la cámara -->
            <div class="camera-preview" id="cameraPreview">
                <video id="videoElement" autoplay playsinline></video>
                <canvas id="canvasElement"></canvas>
                <div class="camera-controls">
                    <button class="camera-btn capture" id="captureBtn">
                        <i class="fas fa-camera"></i>
                        Capturar
                    </button>
                    <button class="camera-btn cancel" id="cancelCameraBtn">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </div>

            <h3>Mi Perfil</h3>
            <form id="formPerfil" enctype="multipart/form-data">
                <input type="file" id="imageInput" name="imagen_perfil" accept="image/*" style="display: none;">
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" id="perfilNombre" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="perfilEmail" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" id="perfilDescripcion" rows="3" maxlength="255" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña (dejar en blanco para mantener la actual):</label>
                    <input type="password" name="password" id="perfilPassword">
                </div>
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña:</label>
                    <input type="password" name="confirm_password" id="perfilConfirmPassword">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
            <div id="mensajePerfilResultado" class="mensaje-resultado"></div>
        </div>
    </div>

    <script>
        let currentChatId = null;
        let lastMessageId = 0;
        let messageUpdateInterval = null;
        let chatsUpdateInterval = null;
        let lastChatUpdate = {};

        // Función para comparar si hay cambios en los chats
        function hasChatsChanged(newChats) {
            if (Object.keys(lastChatUpdate).length !== newChats.length) return true;
            
            return newChats.some(chat => {
                const lastUpdate = lastChatUpdate[chat.usuario_id];
                if (!lastUpdate) return true;
                
                return lastUpdate.ultimo_mensaje !== chat.ultimo_mensaje ||
                       lastUpdate.mensaje_editado !== chat.mensaje_editado ||
                       lastUpdate.mensaje_eliminado !== chat.mensaje_eliminado;
            });
        }

        // Función para actualizar el último estado de los chats
        function updateLastChatState(chats) {
            lastChatUpdate = {};
            chats.forEach(chat => {
                lastChatUpdate[chat.usuario_id] = {
                    ultimo_mensaje: chat.ultimo_mensaje,
                    mensaje_editado: chat.mensaje_editado,
                    mensaje_eliminado: chat.mensaje_eliminado
                };
            });
        }

        // Función modificada para cargar chats
        function loadChats(forceUpdate = false) {
            fetch('../controllers/ChatController.php?action=getChats')
                .then(verificarRespuestaAjax)
                .then(data => {
                    if (data && data.success && Array.isArray(data.chats)) {
                        // Verificar si hay cambios antes de actualizar la UI
                        if (forceUpdate || hasChatsChanged(data.chats)) {
                            const chatList = document.getElementById('chatList');
                            const scrollPos = chatList.scrollTop; // Guardar posición del scroll
                            
                            // Mantener el filtro de búsqueda actual
                            const searchTerm = document.getElementById('searchChat').value.toLowerCase();
                            
                            chatList.innerHTML = '';

                            if (data.chats.length > 0) {
                                data.chats.forEach(chat => {
                                    const li = document.createElement('li');
                                    li.className = 'chat-item';
                                    if (currentChatId === chat.usuario_id) {
                                        li.classList.add('active');
                                    }
                                    li.onclick = () => openChat(chat.usuario_id, chat.nombre);

                                    // Procesar el último mensaje
                                    let ultimoMensaje = chat.ultimo_mensaje || 'No hay mensajes aún';
                                    const isDeleted = chat.mensaje_eliminado === "1" || chat.mensaje_eliminado === 1;
                                    const isEdited = chat.mensaje_editado === "1" || chat.mensaje_editado === 1;

                                    if (isDeleted) {
                                        ultimoMensaje = 'Mensaje eliminado';
                                    } else if (isEdited) {
                                        ultimoMensaje += ' (editado)';
                                    }

                                    li.innerHTML = `
                                        <div class="chat-item-content">
                                            <h4>${chat.nombre}</h4>
                                            <p class="last-message ${isDeleted ? 'deleted-message' : ''}">${ultimoMensaje}</p>
                                        </div>
                                    `;

                                    // Aplicar filtro de búsqueda si existe
                                    if (searchTerm) {
                                        const nombre = chat.nombre.toLowerCase();
                                        li.style.display = nombre.includes(searchTerm) ? 'block' : 'none';
                                    }

                                    chatList.appendChild(li);
                                });
                            } else {
                                chatList.innerHTML = '<li class="no-chats">No tienes chats activos</li>';
                            }

                            // Restaurar posición del scroll
                            chatList.scrollTop = scrollPos;
                            
                            // Actualizar el estado de los chats
                            updateLastChatState(data.chats);
                        }
                    } else {
                        console.error('Formato de respuesta inválido:', data);
                    }
                })
                .catch(error => {
                    console.error('Error al cargar chats:', error);
                });
        }

        // Función para abrir un chat
        function openChat(chatId, nombre) {
            // Limpiar el intervalo anterior si existe
            if (messageUpdateInterval) {
                clearInterval(messageUpdateInterval);
            }

            currentChatId = chatId;
            document.getElementById('currentChatHeader').innerHTML = `<h3>${nombre}</h3>`;
            
            // Habilitar el input y el botón de enviar
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendMessage');
            messageInput.disabled = false;
            sendButton.disabled = false;
            messageInput.value = ''; // Limpiar el input
            
            document.getElementById('chatMessages').innerHTML = ''; // Limpiar mensajes anteriores
            lastMessageId = 0; // Resetear el último ID de mensaje
            loadMessages();

            // Configurar actualización automática de mensajes
            messageUpdateInterval = setInterval(loadMessages, 3000);

            // Activar el chat seleccionado en la lista
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.remove('active');
            });
            const selectedChat = document.querySelector(`.chat-item[onclick*="${chatId}"]`);
            if (selectedChat) {
                selectedChat.classList.add('active');
            }

            // Enfocar el input de mensaje
            messageInput.focus();
        }

        // Función para cargar mensajes
        function loadMessages() {
            if (!currentChatId) return;
            
            console.log("Cargando mensajes para chat:", currentChatId, "último ID:", lastMessageId);
            
            fetch(`../controllers/ChatController.php?action=getMessages&chat_id=${currentChatId}&last_id=0`)
                .then(verificarRespuestaAjax)
                .then(data => {
                    console.log("Mensajes recibidos:", data);
                    if (data && data.success && Array.isArray(data.mensajes)) {
                        const chatMessages = document.getElementById('chatMessages');
                        let shouldScroll = chatMessages.scrollTop + chatMessages.clientHeight >= chatMessages.scrollHeight - 100;
                        
                        // Crear un mapa de los mensajes existentes
                        const existingMessages = new Map();
                        chatMessages.querySelectorAll('.message').forEach(msg => {
                            existingMessages.set(msg.getAttribute('data-message-id'), msg);
                        });
                        
                        data.mensajes.forEach(message => {
                            const messageId = message.id.toString();
                            const existingMessage = existingMessages.get(messageId);
                            const isOwnMessage = message.emisor_id == <?php echo $_SESSION['usuario_id']; ?>;
                            
                            // Convertir los valores de eliminado y editado a booleanos
                            const isDeleted = message.eliminado === "1" || message.eliminado === 1 || message.eliminado === true;
                            const isEdited = message.editado === "1" || message.editado === 1 || message.editado === true;
                            
                            let messageDiv;
                            if (existingMessage) {
                                // Actualizar mensaje existente
                                messageDiv = existingMessage;
                                messageDiv.className = `message ${isOwnMessage ? 'message-sent' : 'message-received'}`;
                                if (isDeleted) messageDiv.classList.add('deleted');
                                if (isEdited) messageDiv.classList.add('edited');
                            } else {
                                // Crear nuevo mensaje
                                messageDiv = document.createElement('div');
                                messageDiv.className = `message ${isOwnMessage ? 'message-sent' : 'message-received'}`;
                                if (isDeleted) messageDiv.classList.add('deleted');
                                if (isEdited) messageDiv.classList.add('edited');
                                messageDiv.setAttribute('data-message-id', messageId);
                                chatMessages.appendChild(messageDiv);
                            }
                            
                            // Crear el contenido del mensaje
                            const messageContent = document.createElement('div');
                            messageContent.className = 'message-content';

                            // Agregar el texto del mensaje
                            const messageText = document.createElement('p');
                            if (isDeleted) {
                                messageText.innerHTML = '<i>Mensaje eliminado</i>';
                            } else {
                                messageText.textContent = message.mensaje;
                            }
                            messageContent.appendChild(messageText);

                            // Agregar botones de opciones si es mensaje propio y no está eliminado
                            if (isOwnMessage && !isDeleted) {
                                const optionsDiv = document.createElement('div');
                                optionsDiv.className = 'message-options';

                                const editButton = document.createElement('button');
                                editButton.className = 'message-option-btn';
                                editButton.innerHTML = '<i class="fas fa-edit"></i>';
                                editButton.onclick = () => editMessage(messageId, message.mensaje);

                                const deleteButton = document.createElement('button');
                                deleteButton.className = 'message-option-btn';
                                deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                                deleteButton.onclick = () => deleteMessage(messageId);

                                optionsDiv.appendChild(editButton);
                                optionsDiv.appendChild(deleteButton);
                                messageContent.appendChild(optionsDiv);
                            }

                            // Agregar información del mensaje si no está eliminado
                            if (!isDeleted) {
                                const infoDiv = document.createElement('div');
                                infoDiv.className = 'message-info';
                                
                                const timeSpan = document.createElement('span');
                                timeSpan.className = 'message-time';
                                timeSpan.textContent = formatDate(message.fecha_envio);
                                infoDiv.appendChild(timeSpan);

                                if (isEdited) {
                                    const editedSpan = document.createElement('span');
                                    editedSpan.className = 'edited-indicator';
                                    editedSpan.textContent = 'editado';
                                    infoDiv.appendChild(editedSpan);
                                }

                                messageContent.appendChild(infoDiv);
                            }

                            // Limpiar y agregar el contenido al mensaje
                            messageDiv.innerHTML = '';
                            messageDiv.appendChild(messageContent);
                            
                            // Actualizar el último ID solo si es un mensaje nuevo
                            if (!existingMessage && message.id > lastMessageId) {
                                lastMessageId = message.id;
                            }
                        });
                        
                        // Eliminar mensajes que ya no existen en el servidor
                        existingMessages.forEach((msgElement, msgId) => {
                            if (!data.mensajes.some(m => m.id.toString() === msgId)) {
                                msgElement.remove();
                            }
                        });
                        
                        if (shouldScroll) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al cargar mensajes:', error);
                });
        }

        // Función para formatear la fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        // Función para actualizar imágenes de perfil
        function actualizarImagenesPerfil() {
            const timestamp = new Date().getTime();
            const profileImages = document.querySelectorAll('.profile-image, .profile-image-large');
            profileImages.forEach(img => {
                const originalSrc = img.src.split('?')[0];
                img.src = `${originalSrc}?t=${timestamp}`;
            });
        }

        // Función mejorada para verificar respuestas AJAX
        async function verificarRespuestaAjax(response) {
            try {
                if (!response.ok) {
                    console.error(`Error HTTP: ${response.status} - ${response.statusText}`);
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    console.error("Tipo de contenido no válido:", contentType);
                    const text = await response.text();
                    console.error("Respuesta no JSON:", text);
                    throw new Error("La respuesta no es JSON");
                }

                const data = await response.json();
                console.log("Respuesta del servidor completa:", data);

                if (data.success === false) {
                    console.error("Error en la respuesta:", data.message);
                    throw new Error(data.message || "Error en la respuesta del servidor");
                }
                return data;
            } catch (error) {
                console.error("Error detallado en verificarRespuestaAjax:", error);
                console.error("Stack:", error.stack);
                throw error;
            }
        }

        // Función para enviar mensaje
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const mensaje = input.value.trim();
            const sendButton = document.getElementById('sendMessage');
            
            if (!mensaje || !currentChatId) {
                console.error("No hay mensaje o chat_id", { mensaje, currentChatId });
                return;
            }
            
            // Deshabilitar el botón mientras se envía
            sendButton.disabled = true;
            input.disabled = true;
            
            const formData = new FormData();
            formData.append('chat_id', currentChatId);
            formData.append('mensaje', mensaje);

            fetch('../controllers/ChatController.php?action=sendMessage', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin' // Incluir cookies en la petición
            })
            .then(response => {
                console.log("Respuesta inicial del servidor:", {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });
                return verificarRespuestaAjax(response);
            })
            .then(result => {
                console.log("Resultado del envío procesado:", result);
                if (result.success) {
                    input.value = '';
                    loadMessages();
                    loadChats(); // Actualizar la lista de chats para mostrar el último mensaje
                } else {
                    throw new Error(result.message || 'Error al enviar el mensaje');
                }
            })
            .catch(error => {
                console.error('Error detallado al enviar mensaje:', error);
                alert('Error al enviar el mensaje. Por favor, intenta nuevamente.');
            })
            .finally(() => {
                // Rehabilitar el botón y el input después del envío
                sendButton.disabled = false;
                input.disabled = false;
                input.focus();
            });
        }

        // Event listeners para enviar mensajes
        document.getElementById('sendMessage').addEventListener('click', function(e) {
            e.preventDefault();
            sendMessage();
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Búsqueda de chats
        document.getElementById('searchChat').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const chatItems = document.querySelectorAll('.chat-item');
            
            chatItems.forEach(item => {
                const nombre = item.querySelector('h4').textContent.toLowerCase();
                item.style.display = nombre.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Manejo de modales
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Event listeners para los modales
        btnAgregarAmigo.onclick = function() {
            showModal('modalAgregarAmigo');
        }

        btnPerfil.onclick = function() {
            showModal('modalPerfil');
            cargarDatosPerfil();
        }

        btnVerSolicitudes.onclick = function() {
            showModal('modalSolicitudes');
            cargarSolicitudes();
        }

        // Cerrar modales
        document.querySelectorAll('.close-modal').forEach(closeBtn => {
            closeBtn.onclick = function() {
                const modal = this.closest('.modal');
                hideModal(modal.id);
                if (modal.id === 'modalAgregarAmigo') {
                    mensajeResultado.style.display = "none";
                    mensajeResultado.className = "";
                }
            }
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                hideModal(event.target.id);
                if (event.target.id === 'modalAgregarAmigo') {
                    mensajeResultado.style.display = "none";
                    mensajeResultado.className = "";
                }
            }
        }

        // Función para mostrar mensajes de error
        function mostrarError(mensaje, elemento = null) {
            console.error(mensaje);
            if (elemento) {
                const mensajeError = document.createElement('div');
                mensajeError.className = 'mensaje-error';
                mensajeError.textContent = mensaje;
                elemento.appendChild(mensajeError);
                setTimeout(() => mensajeError.remove(), 3000);
            }
        }

        // Modificar la función de verificar solicitudes pendientes
        function verificarSolicitudesPendientes() {
            fetch('../controllers/UsuarioController.php?action=obtenerSolicitudes')
                .then(verificarRespuestaAjax)
                .then(data => {
                    if (!data) return;
                    
                    if (data.solicitudes && data.solicitudes.length > 0) {
                        solicitudesBadge.textContent = data.solicitudes.length;
                        solicitudesBadge.style.display = 'block';
                    } else {
                        solicitudesBadge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error al verificar solicitudes:', error.message);
                    // No mostrar el error al usuario para esta función automática
                });
        }

        // Manejar envío del formulario de agregar amigo
        document.getElementById('formAgregarAmigo').addEventListener('submit', function(e) {
            e.preventDefault();
            mensajeResultado.style.display = "none";
            mensajeResultado.className = "";
            
            const formData = new FormData(this);
            
            fetch('../controllers/UsuarioController.php?action=agregarAmigo', {
                method: 'POST',
                body: formData
            })
            .then(verificarRespuestaAjax)
            .then(data => {
                mensajeResultado.style.display = "block";
                if(data.success) {
                    mensajeResultado.className = "mensaje-exito";
                    mensajeResultado.textContent = data.message;
                    this.reset();
                    setTimeout(() => {
                        hideModal('modalAgregarAmigo');
                        mensajeResultado.style.display = "none";
                        loadChats();
                    }, 2000);
                } else {
                    mensajeResultado.className = "mensaje-error";
                    mensajeResultado.textContent = data.message;
                }
            })
            .catch(error => {
                mensajeResultado.style.display = "block";
                mensajeResultado.className = "mensaje-error";
                mensajeResultado.textContent = "Error al procesar la solicitud. Por favor, intenta nuevamente.";
                console.error('Error:', error);
            });
        });

        // Función para cargar solicitudes
        function cargarSolicitudes() {
            fetch('../controllers/UsuarioController.php?action=obtenerSolicitudes')
                .then(verificarRespuestaAjax)
                .then(data => {
                    const container = document.getElementById('solicitudesContainer');
                    container.innerHTML = '';
                    
                    if (data.solicitudes && data.solicitudes.length > 0) {
                        data.solicitudes.forEach(solicitud => {
                            const div = document.createElement('div');
                            div.className = 'solicitud-item';
                            div.setAttribute('data-solicitud-id', solicitud.id);
                            div.innerHTML = `
                                <div class="solicitud-info">
                                    <strong>${solicitud.nombre}</strong>
                                    <div>${solicitud.email}</div>
                                </div>
                                <div class="solicitud-acciones">
                                    <button class="btn-aceptar" onclick="responderSolicitud(${solicitud.id}, 'aceptar')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn-rechazar" onclick="responderSolicitud(${solicitud.id}, 'rechazar')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            container.appendChild(div);
                        });
                        
                        solicitudesBadge.textContent = data.solicitudes.length;
                        solicitudesBadge.style.display = 'block';
                    } else {
                        container.innerHTML = '<p>No tienes solicitudes de amistad pendientes</p>';
                        solicitudesBadge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar solicitudes:', error);
                    const container = document.getElementById('solicitudesContainer');
                    container.innerHTML = '<p class="error-message">Error al cargar las solicitudes</p>';
                });
        }

        // Manejo del modal de perfil
        function cargarDatosPerfil() {
            fetch('../controllers/UsuarioController.php?action=obtenerPerfil')
                .then(verificarRespuestaAjax)
                .then(data => {
                    if (data.success && data.perfil) {
                        document.getElementById('perfilNombre').value = data.perfil.nombre;
                        document.getElementById('perfilEmail').value = data.perfil.email;
                        document.getElementById('perfilDescripcion').value = data.perfil.descripcion;
                    }
                })
                .catch(error => {
                    console.error('Error al cargar perfil:', error);
                });
        }

        // Manejar el cierre de sesión
        document.getElementById('btnLogout').addEventListener('click', function() {
            if (confirm('¿Estás seguro que deseas cerrar sesión?')) {
                fetch('../controllers/UsuarioController.php?action=logout')
                    .then(verificarRespuestaAjax)
                    .then(data => {
                        if (data.success && data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            throw new Error('Error al cerrar sesión');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al cerrar sesión. Por favor, intenta nuevamente.');
                    });
            }
        });

        // Función para responder a una solicitud
        function responderSolicitud(solicitudId, respuesta) {
            const formData = new FormData();
            formData.append('solicitud_id', solicitudId);
            formData.append('respuesta', respuesta);
            
            // Ocultar inmediatamente el elemento de la solicitud
            const solicitudElement = document.querySelector(`[data-solicitud-id="${solicitudId}"]`);
            if (solicitudElement) {
                solicitudElement.style.display = 'none';
            }
            
            fetch('../controllers/ChatController.php?action=responderSolicitud', {
                method: 'POST',
                body: formData
            })
            .then(verificarRespuestaAjax)
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    cargarSolicitudes();
                    loadChats();
                    
                    // Si no hay más solicitudes, cerrar el modal
                    const solicitudesContainer = document.getElementById('solicitudesContainer');
                    if (!solicitudesContainer.querySelector('.solicitud-item:not([style*="display: none"])')) {
                        hideModal('modalSolicitudes');
                    }
                } else {
                    // Si hay error, mostrar nuevamente el elemento
                    if (solicitudElement) {
                        solicitudElement.style.display = '';
                    }
                    alert(data.message || "Error al procesar la solicitud");
                }
            })
            .catch(error => {
                // Si hay error, mostrar nuevamente el elemento
                if (solicitudElement) {
                    solicitudElement.style.display = '';
                }
                console.error('Error:', error);
                alert("Error al procesar la solicitud");
            });
        }

        // Inicializar
        loadChats(true);
        verificarSolicitudesPendientes();
        setInterval(verificarSolicitudesPendientes, 30000);

        // Manejo de emojis
        const btnEmoji = document.getElementById('btnEmoji');
        const emojiPicker = document.getElementById('emojiPicker');
        const messageInput = document.getElementById('messageInput');

        btnEmoji.addEventListener('click', () => {
            emojiPicker.classList.toggle('show');
        });

        // Cerrar el emoji picker cuando se hace clic fuera de él
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.emoji-picker-container') && 
                !e.target.closest('.btn-emoji')) {
                emojiPicker.classList.remove('show');
            }
        });

        // Manejar la selección de emojis
        document.querySelector('emoji-picker')
            .addEventListener('emoji-click', event => {
                const input = document.getElementById('messageInput');
                if (!input) return;

                const emoji = event.detail.unicode;
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const text = input.value;
                const before = text.substring(0, start);
                const after = text.substring(end, text.length);
                const newValue = before + emoji + after;
                
                input.value = newValue;
                input.selectionStart = input.selectionEnd = start + emoji.length;
                input.focus();
                
                // Cerrar el picker después de seleccionar
                document.getElementById('emojiPicker').classList.remove('show');
            });

        // Deshabilitar el botón de emoji cuando el input está deshabilitado
        const updateEmojiButtonState = () => {
            btnEmoji.disabled = messageInput.disabled;
            if (messageInput.disabled) {
                btnEmoji.style.opacity = '0.5';
                btnEmoji.style.cursor = 'not-allowed';
            } else {
                btnEmoji.style.opacity = '1';
                btnEmoji.style.cursor = 'pointer';
            }
        };

        // Observar cambios en el estado del input
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'disabled') {
                    updateEmojiButtonState();
                }
            });
        });

        observer.observe(messageInput, {
            attributes: true
        });

        // Estado inicial del botón de emoji
        updateEmojiButtonState();

        // Manejo de imagen de perfil
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const imageInput = document.getElementById('imageInput');
        const profileImage = document.getElementById('profileImage');
        const photoOptionsMenu = document.getElementById('photoOptionsMenu');
        const uploadPhotoOption = document.getElementById('uploadPhotoOption');
        const takePhotoOption = document.getElementById('takePhotoOption');
        const cameraPreview = document.getElementById('cameraPreview');
        const videoElement = document.getElementById('videoElement');
        const canvasElement = document.getElementById('canvasElement');
        const captureBtn = document.getElementById('captureBtn');
        const cancelCameraBtn = document.getElementById('cancelCameraBtn');
        let stream = null;

        // Mostrar menú de opciones al hacer clic en el botón de cambiar foto
        changePhotoBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            photoOptionsMenu.classList.toggle('show');
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!photoOptionsMenu.contains(e.target) && !changePhotoBtn.contains(e.target)) {
                photoOptionsMenu.classList.remove('show');
            }
        });

        // Opción de subir foto
        uploadPhotoOption.addEventListener('click', () => {
            photoOptionsMenu.classList.remove('show');
            imageInput.click();
        });

        // Opción de tomar foto
        takePhotoOption.addEventListener('click', async () => {
            photoOptionsMenu.classList.remove('show');
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                videoElement.srcObject = stream;
                cameraPreview.classList.add('show');
            } catch (err) {
                console.error('Error al acceder a la cámara:', err);
                alert('No se pudo acceder a la cámara. Por favor, verifica los permisos.');
            }
        });

        // Capturar foto
        captureBtn.addEventListener('click', () => {
            const context = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Convertir la imagen a formato de archivo
            canvasElement.toBlob((blob) => {
                const file = new File([blob], "camera_photo.jpg", { type: "image/jpeg" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                imageInput.files = dataTransfer.files;
                
                // Mostrar vista previa
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                }
                reader.readAsDataURL(file);
                
                // Cerrar la cámara
                stopCamera();
            }, 'image/jpeg', 0.8);
        });

        // Cancelar la cámara
        cancelCameraBtn.addEventListener('click', stopCamera);

        // Función para detener la cámara
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            cameraPreview.classList.remove('show');
            videoElement.srcObject = null;
        }

        // Manejar la subida de archivos
        imageInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Verificar el tamaño del archivo (máximo 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('La imagen es demasiado grande. El tamaño máximo es 5MB.');
                    this.value = '';
                    return;
                }

                // Verificar el tipo de archivo
                if (!file.type.match('image.*')) {
                    alert('Por favor, selecciona una imagen válida.');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Modificar el manejador del formulario de perfil
        document.getElementById('formPerfil').addEventListener('submit', function(e) {
            e.preventDefault();
            const mensajeResultado = document.getElementById('mensajePerfilResultado');
            const modalPerfil = document.getElementById('modalPerfil');
            mensajeResultado.style.display = "none";
            mensajeResultado.className = "mensaje-resultado";

            const formData = new FormData(this);
            
            fetch('../controllers/UsuarioController.php?action=actualizarPerfil', {
                method: 'POST',
                body: formData,
                cache: 'no-store' // Prevenir caché en la petición
            })
            .then(verificarRespuestaAjax)
            .then(data => {
                mensajeResultado.style.display = "block";
                if(data.success) {
                    mensajeResultado.className = "mensaje-resultado mensaje-exito";
                    mensajeResultado.textContent = data.message;
                    
                    // Actualizar el nombre mostrado en la interfaz si fue cambiado
                    if (data.nombre) {
                        document.querySelector('.user-info h3').textContent = data.nombre;
                    }
                    
                    // Limpiar campos de contraseña
                    document.getElementById('perfilPassword').value = '';
                    document.getElementById('perfilConfirmPassword').value = '';

                    // Actualizar imágenes inmediatamente
                    actualizarImagenesPerfil();
                    
                    // Programar múltiples actualizaciones para asegurar que las imágenes se refresquen
                    const updateTimes = [500, 1000, 2000]; // Tiempos en milisegundos
                    updateTimes.forEach(time => {
                        setTimeout(actualizarImagenesPerfil, time);
                    });

                    // Cerrar el modal después de un breve delay
                    setTimeout(() => {
                        modalPerfil.classList.remove('show');
                        loadChats();
                        // Una actualización final después de cerrar el modal
                        actualizarImagenesPerfil();
                    }, 1500);
                } else {
                    mensajeResultado.className = "mensaje-resultado mensaje-error";
                    mensajeResultado.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mensajeResultado.style.display = "block";
                mensajeResultado.className = "mensaje-resultado mensaje-error";
                mensajeResultado.textContent = "Error al actualizar el perfil";
            });
        });

        // Funciones para editar y eliminar mensajes
        function editMessage(messageId, currentText) {
            // Crear elementos del modal
            const modal = document.createElement('div');
            modal.id = 'editMessageModal';
            modal.className = 'edit-message-modal';

            const title = document.createElement('h3');
            title.textContent = 'Editar mensaje';

            const textarea = document.createElement('textarea');
            textarea.id = 'editMessageText';
            textarea.value = currentText;

            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'buttons';

            const cancelButton = document.createElement('button');
            cancelButton.className = 'cancel-btn';
            cancelButton.textContent = 'Cancelar';
            cancelButton.onclick = closeEditModal;

            const saveButton = document.createElement('button');
            saveButton.className = 'save-btn';
            saveButton.textContent = 'Guardar';
            saveButton.onclick = () => saveEditedMessage(messageId);

            // Construir el modal
            buttonsDiv.appendChild(cancelButton);
            buttonsDiv.appendChild(saveButton);
            modal.appendChild(title);
            modal.appendChild(textarea);
            modal.appendChild(buttonsDiv);
            
            // Remover modal existente si hay uno
            const existingModal = document.getElementById('editMessageModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Agregar el nuevo modal al documento
            document.body.appendChild(modal);
            modal.classList.add('show');
            textarea.focus();
        }

        function closeEditModal() {
            const modal = document.getElementById('editMessageModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }

        function saveEditedMessage(messageId) {
            const newText = document.getElementById('editMessageText').value.trim();
            if (!newText) return;

            const formData = new FormData();
            formData.append('mensaje_id', messageId);
            formData.append('nuevo_mensaje', newText);

            fetch('../controllers/ChatController.php?action=editarMensaje', {
                method: 'POST',
                body: formData
            })
            .then(verificarRespuestaAjax)
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageDiv) {
                        const messageContent = messageDiv.querySelector('p');
                        if (messageContent) {
                            messageContent.textContent = newText;
                        }
                        messageDiv.classList.add('edited');
                        const messageInfo = messageDiv.querySelector('.message-info');
                        if (messageInfo) {
                            messageInfo.innerHTML = `
                                <span class="message-time">${formatDate(new Date())}</span>
                                <span class="edited-indicator">editado</span>
                            `;
                        }
                    }
                } else {
                    alert(data.message || 'Error al editar el mensaje');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al editar el mensaje');
            });
        }

        function deleteMessage(messageId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este mensaje?')) return;

            const formData = new FormData();
            formData.append('mensaje_id', messageId);

            fetch('../controllers/ChatController.php?action=eliminarMensaje', {
                method: 'POST',
                body: formData
            })
            .then(verificarRespuestaAjax)
            .then(data => {
                if (data.success) {
                    // Actualizar el mensaje inmediatamente
                    const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageDiv) {
                        messageDiv.classList.add('deleted');
                        messageDiv.innerHTML = '<p><i>Mensaje eliminado</i></p>';
                    }
                } else {
                    alert(data.message || 'Error al eliminar el mensaje');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el mensaje');
            });
        }

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar chats inicialmente
            loadChats(true);
            
            // Configurar intervalo de actualización de chats
            chatsUpdateInterval = setInterval(() => loadChats(), 3000);
            
            // ... resto del código de inicialización ...
        });

        // Limpiar intervalos al cerrar la página
        window.addEventListener('beforeunload', function() {
            if (messageUpdateInterval) clearInterval(messageUpdateInterval);
            if (chatsUpdateInterval) clearInterval(chatsUpdateInterval);
        });
    </script>
</body>
</html> 