<?php
require_once 'includes/functions.php';

// If client is already logged in, redirect to player
if (is_client_logged_in()) {
    redirect('player.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3U Streaming - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>M3U Streaming</h1>
            </div>
            
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <!-- Added optional DNS server selector -->
                <div class="form-group">
                    <label for="dnsServer">Servidor DNS (opcional)</label>
                    <select id="dnsServer" name="dns_id">
                        <option value="">Detectar automaticamente</option>
                    </select>
                    <small class="form-hint">Deixe em "Detectar automaticamente" para verificar todos os servidores</small>
                </div>
                
                <div id="errorMessage" class="error-message" style="display: none;"></div>
                
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            
            <div class="admin-link">
                <a href="admin/login.php">Acesso Administrativo</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>
