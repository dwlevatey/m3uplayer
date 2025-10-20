<?php
session_start();

// Check if already installed
if (file_exists('config/.installed')) {
    header('Location: admin/login.php');
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Step 1: Test database connection
        $host = trim($_POST['host'] ?? 'localhost');
        $dbname = trim($_POST['dbname'] ?? 'm3u_streaming');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username)) {
            $error = 'O usuário MySQL não pode estar vazio!';
        } else {
            try {
                // Test connection
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Store credentials in session
                $_SESSION['install_data'] = [
                    'host' => $host,
                    'dbname' => $dbname,
                    'username' => $username,
                    'password' => $password
                ];
                
                header('Location: install.php?step=2');
                exit;
            } catch (PDOException $e) {
                $error = 'Erro ao conectar: ' . $e->getMessage();
            }
        }
    } elseif ($step === 2) {
        // Step 2: Create admin user
        $admin_username = trim($_POST['admin_username'] ?? 'admin');
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_email = trim($_POST['admin_email'] ?? '');
        
        if (empty($admin_password) || strlen($admin_password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres!';
        } elseif (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, insira um email válido!';
        } else {
            $_SESSION['install_data']['admin_username'] = $admin_username;
            $_SESSION['install_data']['admin_password'] = $admin_password;
            $_SESSION['install_data']['admin_email'] = $admin_email;
            
            header('Location: install.php?step=3');
            exit;
        }
    } elseif ($step === 3) {
        // Step 3: Execute installation
        if (!isset($_SESSION['install_data'])) {
            header('Location: install.php?step=1');
            exit;
        }
        
        $data = $_SESSION['install_data'];
        
        try {
            // Connect to database
            $pdo = new PDO(
                "mysql:host={$data['host']};dbname={$data['dbname']}",
                $data['username'],
                $data['password']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Execute SQL schema
            if (file_exists('scripts/001_create_tables.sql')) {
                $sql = file_get_contents('scripts/001_create_tables.sql');
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
            }
            
            // Create admin user
            $admin_password_hash = password_hash($data['admin_password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, email, created_at) 
                                  VALUES (?, ?, ?, NOW())
                                  ON DUPLICATE KEY UPDATE password=VALUES(password), email=VALUES(email)");
            $stmt->execute([$data['admin_username'], $admin_password_hash, $data['admin_email']]);
            
            // Update database config file
            $escaped_password = addslashes($data['password']);
            $config_content = "<?php
// Database configuration
class Database {
    private \$host = '{$data['host']}';
    private \$db_name = '{$data['dbname']}';
    private \$username = '{$data['username']}';
    private \$password = '$escaped_password';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;

        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name,
                \$this->username,
                \$this->password
            );
            \$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            \$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException \$e) {
            error_log(\"Database Connection Error: \" . \$e->getMessage());
            die(\"Erro ao conectar ao banco de dados. Verifique as configurações em config/database.php\");
        }

        return \$this->conn;
    }
}
?>";
            file_put_contents('config/database.php', $config_content);
            
            // Generate JWT secret
            if (file_exists('config/config.php')) {
                $jwt_secret = bin2hex(random_bytes(32));
                $config_file = file_get_contents('config/config.php');
                $config_file = preg_replace(
                    "/define$$'JWT_SECRET', '.*?'$$;/",
                    "define('JWT_SECRET', '$jwt_secret');",
                    $config_file
                );
                file_put_contents('config/config.php', $config_file);
            }
            
            // Create installation marker
            file_put_contents('config/.installed', date('Y-m-d H:i:s'));
            
            // Clear session
            unset($_SESSION['install_data']);
            
            header('Location: install.php?step=4');
            exit;
            
        } catch (Exception $e) {
            $error = 'Erro durante a instalação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema M3U Streaming</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .progress {
            display: flex;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 10px 0;
        }
        
        .progress-step::before {
            content: attr(data-step);
            display: block;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            line-height: 36px;
            margin: 0 auto 8px;
            font-weight: bold;
        }
        
        .progress-step.active::before {
            background: #667eea;
            color: white;
        }
        
        .progress-step.completed::before {
            background: #28a745;
            color: white;
            content: '✓';
        }
        
        .progress-step span {
            font-size: 12px;
            color: #6c757d;
        }
        
        .progress-step.active span {
            color: #667eea;
            font-weight: 600;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .info-box h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .info-box p {
            font-size: 14px;
            color: #6c757d;
            margin: 5px 0;
        }
        
        .info-box strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Sistema M3U Streaming</h1>
            <p>Assistente de Instalação</p>
        </div>
        
        <div class="progress">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>" data-step="1">
                <span>Banco de Dados</span>
            </div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>" data-step="2">
                <span>Admin</span>
            </div>
            <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>" data-step="3">
                <span>Instalação</span>
            </div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?>" data-step="4">
                <span>Concluído</span>
            </div>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <h2 style="margin-bottom: 20px; color: #333;">Configuração do Banco de Dados</h2>
                <p style="color: #6c757d; margin-bottom: 30px;">Insira as credenciais do seu servidor MySQL.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Host do MySQL</label>
                        <input type="text" name="host" value="localhost" required>
                        <small>Geralmente é "localhost"</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nome do Banco de Dados</label>
                        <input type="text" name="dbname" value="m3u_streaming" required>
                        <small>O banco será criado automaticamente se não existir</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Usuário MySQL</label>
                        <input type="text" name="username" required>
                        <small>Usuário com permissões para criar bancos de dados</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Senha MySQL</label>
                        <input type="password" name="password">
                        <small>Deixe em branco se não houver senha</small>
                    </div>
                    
                    <button type="submit" class="btn">Testar Conexão e Continuar →</button>
                </form>
                
            <?php elseif ($step === 2): ?>
                <h2 style="margin-bottom: 20px; color: #333;">Criar Usuário Administrador</h2>
                <p style="color: #6c757d; margin-bottom: 30px;">Configure as credenciais do administrador do sistema.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Nome de Usuário</label>
                        <input type="text" name="admin_username" value="admin" required>
                        <small>Use apenas letras, números e underscore</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="admin_email" required>
                        <small>Email para recuperação de senha</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="admin_password" required minlength="6">
                        <small>Mínimo de 6 caracteres</small>
                    </div>
                    
                    <button type="submit" class="btn">Continuar →</button>
                </form>
                
            <?php elseif ($step === 3): ?>
                <h2 style="margin-bottom: 20px; color: #333;">Pronto para Instalar</h2>
                <p style="color: #6c757d; margin-bottom: 30px;">Clique no botão abaixo para iniciar a instalação do sistema.</p>
                
                <div class="info-box">
                    <h3>O que será feito:</h3>
                    <p>✓ Criação das tabelas no banco de dados</p>
                    <p>✓ Configuração do usuário administrador</p>
                    <p>✓ Geração de chaves de segurança</p>
                    <p>✓ Configuração dos arquivos do sistema</p>
                </div>
                
                <form method="POST" style="margin-top: 30px;">
                    <button type="submit" class="btn">🚀 Instalar Agora</button>
                </form>
                
            <?php elseif ($step === 4): ?>
                <div class="success-icon">✓</div>
                <h2 style="margin-bottom: 20px; color: #333; text-align: center;">Instalação Concluída!</h2>
                <p style="color: #6c757d; margin-bottom: 30px; text-align: center;">O sistema foi instalado com sucesso.</p>
                
                <div class="info-box">
                    <h3>Próximos Passos:</h3>
                    <p>1. Acesse o painel administrativo</p>
                    <p>2. Faça login com suas credenciais</p>
                    <p>3. Configure os servidores DNS</p>
                    <p>4. Cadastre os clientes</p>
                </div>
                
                <a href="admin/login.php" class="btn" style="margin-top: 30px; text-decoration: none; text-align: center; display: block;">
                    Acessar Painel Admin →
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
