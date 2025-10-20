<?php
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Configurações</h1>
            </div>
            
            <div class="settings-container">
                <div class="settings-card">
                    <h3>Configurações do Sistema</h3>
                    <p>Edite o arquivo <code>config/config.php</code> para alterar as configurações do sistema.</p>
                    
                    <div class="settings-info">
                        <div class="info-item">
                            <strong>Tipo M3U:</strong>
                            <span><?php echo M3U_TYPE; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Output:</strong>
                            <span><?php echo M3U_OUTPUT; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Timeout de Sessão:</strong>
                            <span><?php echo SESSION_TIMEOUT; ?> segundos</span>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h3>Banco de Dados</h3>
                    <p>Configure a conexão do banco de dados em <code>config/database.php</code></p>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .settings-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 24px;
        }
        
        .settings-card h3 {
            margin-bottom: 12px;
        }
        
        .settings-card p {
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        
        .settings-card code {
            background: var(--bg-tertiary);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: var(--accent-primary);
        }
        
        .settings-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: 4px;
        }
    </style>
</body>
</html>
