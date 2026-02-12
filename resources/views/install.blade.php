<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar BM Planos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .app-icon {
            width: 100px;
            height: 100px;
            border-radius: 20px;
            margin: 0 auto 20px;
            background: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        .install-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="app-icon">🏥</div>
        <h1>BM Planos</h1>
        <p>Compare e contrate planos de saúde</p>
        
        <button class="install-btn" onclick="redirectToApp()">
            📲 Instalar Aplicativo
        </button>
    </div>

    <script>
        function redirectToApp() {
            window.location.href = 'https://app.bmsys.com.br';
        }
        
        // Auto-redirect em mobile
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            setTimeout(redirectToApp, 2000);
        }
    </script>
</body>
</html>