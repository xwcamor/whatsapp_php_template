<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="stylesheet" href="/whatsapp/views/css/style.css">
    <style>
        .error-container {
            text-align: center;
            padding: 50px;
        }
        .error-code {
            font-size: 120px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-code">404</div>
            <div class="error-message">¡Ups! Página no encontrada</div>
            <p>Lo sentimos, la página que estás buscando no existe.</p>
            <br>
            <a href="/whatsapp/" class="back-button">Volver al inicio</a>
        </div>
    </div>
</body>
</html> 