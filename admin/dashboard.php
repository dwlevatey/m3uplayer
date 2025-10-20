<?php
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM dns_configs WHERE is_active = 1");
$active_dns = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM client_credentials WHERE is_active = 1");
$active_clients = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM client_credentials WHERE expiry_date < CURDATE()");
$expired_clients = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Bem-vindo, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🌐</div>
                    <div class="stat-info">
                        <h3><?php echo $active_dns; ?></h3>
                        <p>DNS Ativos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $active_clients; ?></h3>
                        <p>Clientes Ativos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-info">
                        <h3><?php echo $expired_clients; ?></h3>
                        <p>Clientes Expirados</p>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2>Ações Rápidas</h2>
                <div class="action-buttons">
                    <a href="dns-config.php" class="btn-action">
                        <span>➕</span>
                        Adicionar DNS
                    </a>
                    <a href="clients.php" class="btn-action">
                        <span>👤</span>
                        Gerenciar Clientes
                    </a>
                    <a href="settings.php" class="btn-action">
                        <span>⚙️</span>
                        Configurações
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
