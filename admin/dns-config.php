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
            $name = sanitize_input($_POST['name']);
            $dns_url = sanitize_input($_POST['dns_url']);
            
            $stmt = $conn->prepare("INSERT INTO dns_configs (name, dns_url) VALUES (?, ?)");
            $stmt->execute([$name, $dns_url]);
            $success = "DNS adicionado com sucesso!";
            header("Location: dns-config.php?success=1");
            exit;
        } elseif ($_POST['action'] === 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE dns_configs SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: dns-config.php");
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM dns_configs WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: dns-config.php");
            exit;
        }
    }
}

// Get all DNS configs
$stmt = $conn->query("SELECT * FROM dns_configs ORDER BY created_at DESC");
$dns_configs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações DNS - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="page-header">
                <h1>Configurações DNS</h1>
                <button class="btn-primary" onclick="showAddModal()">Adicionar DNS</button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">DNS adicionado com sucesso!</div>
            <?php endif; ?>
            
            <!-- Updated info box with better contrast and darker text -->
            <div class="info-box" style="margin-bottom: 20px; padding: 20px; background: #1e3a5f; border-left: 4px solid #3b82f6; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #60a5fa; font-size: 16px; font-weight: 600;">ℹ️ Formato da URL M3U</h3>
                <p style="margin-bottom: 12px; color: #e5e7eb; font-size: 14px;">O sistema constrói automaticamente a URL M3U no seguinte formato:</p>
                <code style="display: block; padding: 12px; background: #0f172a; border-radius: 6px; font-family: monospace; color: #94a3b8; font-size: 13px; overflow-x: auto;">
                    <strong style="color: #60a5fa;">URL_DNS</strong>/get.php?username=<strong style="color: #34d399;">USUARIO</strong>&password=<strong style="color: #fbbf24;">SENHA</strong>&type=m3u_plus&output=<strong style="color: #f472b6;">mpegts</strong>
                </code>
                <p style="margin-top: 12px; margin-bottom: 8px; color: #e5e7eb; font-size: 13px;"><strong style="color: #60a5fa;">Exemplo completo:</strong></p>
                <code style="display: block; padding: 12px; background: #0f172a; border-radius: 6px; font-family: monospace; color: #94a3b8; font-size: 13px; overflow-x: auto;">
                    http://blder.xyz/get.php?username=064397679&password=337776300&type=m3u_plus&output=mpegts
                </code>
                <p style="margin-top: 8px; font-size: 13px; color: #e5e7eb; line-height: 1.5;">
                    <strong style="color: #fbbf24;">⚠️ Importante:</strong> Cadastre apenas a URL base do servidor (ex: <code style="background: #0f172a; padding: 2px 6px; border-radius: 3px; color: #60a5fa;">http://blder.xyz</code>). 
                    O sistema adiciona automaticamente <code style="background: #0f172a; padding: 2px 6px; border-radius: 3px; color: #94a3b8;">/get.php</code> e os parâmetros necessários.
                </p>
                <p style="margin-top: 8px; font-size: 13px; color: #e5e7eb;">
                    <strong style="color: #34d399;">✅ Formatos testados automaticamente:</strong> mpegts, m3u8, hls, ts
                </p>
            </div>

            <!-- Added URL tester tool -->
            <div class="info-box" style="margin-bottom: 20px; padding: 20px; background: #1e293b; border-left: 4px solid #10b981; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #34d399; font-size: 16px; font-weight: 600;">🔧 Testador de URL</h3>
                <p style="margin-bottom: 12px; color: #e5e7eb; font-size: 14px;">Use esta ferramenta para testar se uma URL M3U está funcionando:</p>
                
                <div style="display: grid; gap: 12px;">
                    <div>
                        <label style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 4px;">URL Base do Servidor:</label>
                        <input type="text" id="testDnsUrl" placeholder="http://blder.xyz" style="width: 100%; padding: 8px 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e5e7eb; font-size: 14px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 4px;">Usuário:</label>
                            <input type="text" id="testUsername" placeholder="064397679" style="width: 100%; padding: 8px 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e5e7eb; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 4px;">Senha:</label>
                            <input type="text" id="testPassword" placeholder="337776300" style="width: 100%; padding: 8px 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #e5e7eb; font-size: 14px;">
                        </div>
                    </div>
                    <button onclick="testUrl()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">
                        Testar URL
                    </button>
                    <div id="testResult" style="display: none; padding: 12px; border-radius: 6px; font-size: 13px;"></div>
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>URL DNS</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dns_configs as $config): ?>
                        <tr>
                            <td><?php echo $config['id']; ?></td>
                            <td><?php echo htmlspecialchars($config['name']); ?></td>
                            <td><?php echo htmlspecialchars($config['dns_url']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $config['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $config['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($config['created_at'])); ?></td>
                            <td class="actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $config['id']; ?>">
                                    <button type="submit" class="btn-small">
                                        <?php echo $config['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $config['id']; ?>">
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
    
    <!-- Add DNS Modal -->
    <div id="addModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adicionar DNS</h2>
                <button class="modal-close" onclick="hideAddModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="dns_url">URL DNS</label>
                    <input type="url" id="dns_url" name="dns_url" placeholder="http://exemplo.com" required>
                    <small>Exemplo: http://blinder.space (apenas a URL base, sem parâmetros)</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideAddModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Adicionar</button>
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

        async function testUrl() {
            const dnsUrl = document.getElementById('testDnsUrl').value.trim();
            const username = document.getElementById('testUsername').value.trim();
            const password = document.getElementById('testPassword').value.trim();
            const resultDiv = document.getElementById('testResult');
            
            if (!dnsUrl || !username || !password) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = '#7f1d1d';
                resultDiv.style.color = '#fca5a5';
                resultDiv.innerHTML = '⚠️ Preencha todos os campos';
                return;
            }
            
            resultDiv.style.display = 'block';
            resultDiv.style.background = '#1e3a5f';
            resultDiv.style.color = '#93c5fd';
            resultDiv.innerHTML = '🔄 Testando URL...';
            
            const formats = ['mpegts', 'm3u8', 'hls', 'ts'];
            let results = [];
            
            for (const format of formats) {
                const testUrl = `${dnsUrl.replace(/\/$/, '')}/get.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&type=m3u_plus&output=${format}`;
                
                try {
                    const response = await fetch('../api/test-url.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ url: testUrl })
                    });
                    
                    const data = await response.json();
                    results.push({
                        format: format,
                        url: testUrl,
                        success: data.success,
                        http_code: data.http_code,
                        is_valid_m3u: data.is_valid_m3u,
                        response_preview: data.response_preview,
                        looks_like_html: data.looks_like_html,
                        response_length: data.response_length,
                        error: data.error
                    });
                    
                    if (data.success && data.is_valid_m3u) {
                        break; // Found working format
                    }
                } catch (error) {
                    results.push({
                        format: format,
                        url: testUrl,
                        success: false,
                        error: error.message
                    });
                }
            }
            
            const successResult = results.find(r => r.success && r.is_valid_m3u);
            
            if (successResult) {
                resultDiv.style.background = '#064e3b';
                resultDiv.style.color = '#6ee7b7';
                resultDiv.innerHTML = `
                    <strong>✅ URL FUNCIONANDO!</strong><br>
                    <strong>Formato:</strong> ${successResult.format}<br>
                    <strong>URL:</strong> <code style="background: #0f172a; padding: 2px 6px; border-radius: 3px; font-size: 12px; display: block; margin-top: 4px; word-break: break-all;">${successResult.url}</code><br>
                    <strong style="color: #34d399; margin-top: 8px; display: block;">💡 Cadastre apenas: ${dnsUrl}</strong>
                `;
            } else {
                let errorHtml = '<strong>❌ NENHUM FORMATO FUNCIONOU</strong><br><br>';
                
                results.forEach(r => {
                    const statusText = r.is_valid_m3u ? 'M3U válido ✓' : (r.looks_like_html ? 'Resposta HTML (erro)' : 'M3U inválido');
                    errorHtml += `<strong>${r.format}:</strong> HTTP ${r.http_code || 'erro'} - ${statusText}`;
                    if (r.response_length) {
                        errorHtml += ` (${r.response_length} bytes)`;
                    }
                    errorHtml += '<br>';
                });
                
                errorHtml += `<br><strong>URL testada:</strong><br><code style="background: #0f172a; padding: 2px 6px; border-radius: 3px; font-size: 11px; display: block; margin-top: 4px; word-break: break-all;">${results[0].url}</code>`;
                
                if (results[0].response_preview) {
                    errorHtml += `<br><br><strong>📄 Prévia da resposta do servidor:</strong><br>`;
                    errorHtml += `<pre style="background: #0f172a; padding: 8px; border-radius: 4px; font-size: 11px; overflow-x: auto; max-height: 200px; margin-top: 4px; color: #94a3b8;">${escapeHtml(results[0].response_preview)}</pre>`;
                    
                    if (results[0].looks_like_html) {
                        errorHtml += `<p style="margin-top: 8px; color: #fbbf24;">⚠️ O servidor está retornando HTML ao invés de M3U. Verifique se as credenciais estão corretas ou se o servidor está funcionando.</p>`;
                    }
                }
                
                resultDiv.style.background = '#7f1d1d';
                resultDiv.style.color = '#fca5a5';
                resultDiv.innerHTML = errorHtml;
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
