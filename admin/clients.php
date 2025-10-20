<?php
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = sanitize_input($_POST['username']);
            $password = sanitize_input($_POST['password']);
            $dns_config_id = (int)$_POST['dns_config_id'];
            $expiry_date = $_POST['expiry_date'] ?: null;
            $output_format = sanitize_input($_POST['output_format'] ?? 'mpegts');
            
            $stmt = $conn->prepare("INSERT INTO client_credentials (username, password, output_format, dns_config_id, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $output_format, $dns_config_id, $expiry_date]);
            $success = "Cliente adicionado com sucesso!";
        } elseif ($_POST['action'] === 'add_from_link') {
            $username = sanitize_input($_POST['extracted_username']);
            $password = sanitize_input($_POST['extracted_password']);
            $dns_host = sanitize_input($_POST['extracted_dns']);
            $output_format = sanitize_input($_POST['extracted_output_format'] ?? 'mpegts');
            
            if (empty($username) || empty($password) || empty($dns_host)) {
                $error = "Não foi possível extrair username e password do link. Verifique se o link contém os parâmetros 'username' e 'password'.";
            } else {
                // Check if DNS already exists
                $stmt = $conn->prepare("SELECT id FROM dns_configs WHERE dns_url = ?");
                $stmt->execute([$dns_host]);
                $dns_config = $stmt->fetch();
                
                if ($dns_config) {
                    $dns_config_id = $dns_config['id'];
                } else {
                    // Create new DNS config
                    $stmt = $conn->prepare("INSERT INTO dns_configs (name, dns_url) VALUES (?, ?)");
                    $stmt->execute([$dns_host, $dns_host]);
                    $dns_config_id = $conn->lastInsertId();
                }
                
                // Insert client credentials
                try {
                    $stmt = $conn->prepare("INSERT INTO client_credentials (username, password, output_format, dns_config_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $password, $output_format, $dns_config_id]);
                    $success = "Cliente importado com sucesso via link M3U!";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = "Este cliente já existe no sistema.";
                    } else {
                        $error = "Erro ao adicionar cliente: " . $e->getMessage();
                    }
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id'];
            $username = sanitize_input($_POST['username']);
            $password = sanitize_input($_POST['password']);
            $dns_config_id = (int)$_POST['dns_config_id'];
            $expiry_date = $_POST['expiry_date'] ?: null;
            $output_format = sanitize_input($_POST['output_format'] ?? 'mpegts');
            
            $stmt = $conn->prepare("UPDATE client_credentials SET username = ?, password = ?, output_format = ?, dns_config_id = ?, expiry_date = ? WHERE id = ?");
            $stmt->execute([$username, $password, $output_format, $dns_config_id, $expiry_date, $id]);
            $success = "Cliente atualizado com sucesso!";
        } elseif ($_POST['action'] === 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE client_credentials SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM client_credentials WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}

// Get all clients with DNS info
$stmt = $conn->query("
    SELECT c.*, d.name as dns_name, d.dns_url 
    FROM client_credentials c 
    LEFT JOIN dns_configs d ON c.dns_config_id = d.id 
    ORDER BY c.created_at DESC
");
$clients = $stmt->fetchAll();

// Get active DNS configs for dropdown
$stmt = $conn->query("SELECT id, name FROM dns_configs WHERE is_active = 1");
$dns_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Gerenciar Clientes</h1>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-secondary" onclick="showLinkModal()">Cadastrar via Link</button>
                    <button class="btn-primary" onclick="showAddModal()">Adicionar Cliente</button>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message" style="background: #fee; border: 1px solid #fcc; color: #c33; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Senha</th>
                            <th>DNS</th>
                            <th>Status</th>
                            <th>Expira em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo $client['id']; ?></td>
                            <td><?php echo htmlspecialchars($client['username']); ?></td>
                            <td><?php echo htmlspecialchars($client['password']); ?></td>
                            <td><?php echo htmlspecialchars($client['dns_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $client['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $client['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($client['expiry_date']) {
                                    echo date('d/m/Y', strtotime($client['expiry_date']));
                                    if (strtotime($client['expiry_date']) < time()) {
                                        echo ' <span class="expired">Expirado</span>';
                                    }
                                } else {
                                    echo 'Sem expiração';
                                }
                                ?>
                            </td>
                            <td class="actions">
                                <button 
                                    class="btn-small" 
                                    onclick='showEditModal(<?php echo json_encode([
                                        "id" => $client["id"],
                                        "username" => $client["username"],
                                        "password" => $client["password"],
                                        "dns_config_id" => $client["dns_config_id"],
                                        "expiry_date" => $client["expiry_date"],
                                        "output_format" => $client["output_format"] ?? 'mpegts'
                                    ]); ?>)'
                                    style="background: #0070f3; color: white;"
                                >
                                    Editar
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                                    <button type="submit" class="btn-small">
                                        <?php echo $client['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                                    <button type="submit" class="btn-small btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add Client Modal -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adicionar Cliente</h2>
                <button class="modal-close" onclick="hideAddModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="text" id="password" name="password" required>
                </div>
                <!-- Added output_format field -->
                <div class="form-group">
                    <label for="output_format">Formato de Saída</label>
                    <select id="output_format" name="output_format">
                        <option value="mpegts">MPEG-TS</option>
                        <option value="m3u8">M3U8</option>
                        <option value="hls">HLS</option>
                        <option value="ts">TS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dns_config_id">DNS</label>
                    <select id="dns_config_id" name="dns_config_id" required>
                        <option value="">Selecione um DNS</option>
                        <?php foreach ($dns_list as $dns): ?>
                            <option value="<?php echo $dns['id']; ?>"><?php echo htmlspecialchars($dns['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="expiry_date">Data de Expiração (opcional)</label>
                    <input type="date" id="expiry_date" name="expiry_date">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideAddModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Client Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Cliente</h2>
                <button class="modal-close" onclick="hideEditModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_username">Usuário</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">Senha</label>
                    <input type="text" id="edit_password" name="password" required>
                </div>
                <!-- Added output_format field to edit modal -->
                <div class="form-group">
                    <label for="edit_output_format">Formato de Saída</label>
                    <select id="edit_output_format" name="output_format">
                        <option value="mpegts">MPEG-TS</option>
                        <option value="m3u8">M3U8</option>
                        <option value="hls">HLS</option>
                        <option value="ts">TS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_dns_config_id">DNS</label>
                    <select id="edit_dns_config_id" name="dns_config_id" required>
                        <option value="">Selecione um DNS</option>
                        <?php foreach ($dns_list as $dns): ?>
                            <option value="<?php echo $dns['id']; ?>"><?php echo htmlspecialchars($dns['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_expiry_date">Data de Expiração (opcional)</label>
                    <input type="date" id="edit_expiry_date" name="expiry_date">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideEditModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- M3U Link Import Modal -->
    <div id="linkModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cadastrar Cliente via Link M3U</h2>
                <button class="modal-close" onclick="hideLinkModal()">&times;</button>
            </div>
            <form method="POST" id="linkForm">
                <input type="hidden" name="action" value="add_from_link">
                <!-- Added hidden field for output_format -->
                <input type="hidden" id="extracted_username" name="extracted_username">
                <input type="hidden" id="extracted_password" name="extracted_password">
                <input type="hidden" id="extracted_dns" name="extracted_dns">
                <input type="hidden" id="extracted_output_format" name="extracted_output_format">
                
                <div class="form-group">
                    <label for="m3u_url">Link M3U</label>
                    <input 
                        type="text" 
                        id="m3u_url" 
                        name="m3u_url" 
                        placeholder="http://exemplo.com/get.php?username=...&password=..." 
                        required
                        style="width: 100%;"
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Cole o link M3U completo. O sistema extrairá automaticamente o DNS, username e password.
                    </small>
                </div>
                <div id="preview" style="display: none; background: #2a2a2a; padding: 12px; border-radius: 4px; margin-top: 10px; border: 1px solid #444;">
                    <strong style="color: #fff;">Dados extraídos:</strong>
                    <div style="margin-top: 8px; color: #e0e0e0;">
                        <div><strong>DNS:</strong> <span id="preview-dns"></span></div>
                        <div><strong>Username:</strong> <span id="preview-username"></span></div>
                        <div><strong>Password:</strong> <span id="preview-password"></span></div>
                        <!-- Added output format to preview -->
                        <div><strong>Formato:</strong> <span id="preview-output"></span></div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideLinkModal()">Cancelar</button>
                    <button type="submit" class="btn-primary" id="importBtn" disabled>Importar Cliente</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function hideAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function showEditModal(client) {
            document.getElementById('edit_id').value = client.id;
            document.getElementById('edit_username').value = client.username;
            document.getElementById('edit_password').value = client.password;
            document.getElementById('edit_output_format').value = client.output_format || 'mpegts';
            document.getElementById('edit_dns_config_id').value = client.dns_config_id;
            document.getElementById('edit_expiry_date').value = client.expiry_date || '';
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function hideEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function showLinkModal() {
            document.getElementById('linkModal').style.display = 'flex';
        }
        
        function hideLinkModal() {
            document.getElementById('linkModal').style.display = 'none';
            document.getElementById('m3u_url').value = '';
            document.getElementById('preview').style.display = 'none';
            document.getElementById('extracted_username').value = '';
            document.getElementById('extracted_password').value = '';
            document.getElementById('extracted_dns').value = '';
            document.getElementById('extracted_output_format').value = '';
            document.getElementById('importBtn').disabled = true;
        }
        
        document.getElementById('m3u_url')?.addEventListener('input', function(e) {
            const url = e.target.value.trim();
            const preview = document.getElementById('preview');
            const importBtn = document.getElementById('importBtn');
            
            if (!url) {
                preview.style.display = 'none';
                importBtn.disabled = true;
                return;
            }
            
            try {
                const urlObj = new URL(url);
                const params = new URLSearchParams(urlObj.search);
                
                const username = params.get('username');
                const password = params.get('password');
                const output = params.get('output') || 'mpegts';
                const dns = urlObj.hostname;
                
                if (username && password && dns) {
                    // Update preview
                    document.getElementById('preview-dns').textContent = dns;
                    document.getElementById('preview-username').textContent = username;
                    document.getElementById('preview-password').textContent = password;
                    document.getElementById('preview-output').textContent = output;
                    
                    // Update hidden fields
                    document.getElementById('extracted_username').value = username;
                    document.getElementById('extracted_password').value = password;
                    document.getElementById('extracted_dns').value = dns;
                    document.getElementById('extracted_output_format').value = output;
                    
                    preview.style.display = 'block';
                    importBtn.disabled = false;
                } else {
                    preview.style.display = 'none';
                    importBtn.disabled = true;
                }
            } catch (err) {
                preview.style.display = 'none';
                importBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
