<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span>
            Dashboard
        </a>
        <a href="dns-config.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dns-config.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🌐</span>
            Configurações DNS
        </a>
        <a href="clients.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            Clientes
        </a>
        <a href="settings.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <span class="nav-icon">⚙️</span>
            Configurações
        </a>
    </nav>
</aside>
