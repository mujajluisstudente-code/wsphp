<?php
// Abilita la visualizzazione di tutti gli errori
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Style CSS per una migliore leggibilità
echo '<!DOCTYPE html>
<html>
<head>
    <title>PHP Info - Debug</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        h1 { 
            color: #333; 
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 { 
            color: #667eea; 
            margin-top: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        th { 
            background: #667eea; 
            color: white; 
            padding: 10px; 
            text-align: left; 
        }
        td { 
            padding: 10px; 
            border-bottom: 1px solid #eee; 
            vertical-align: top;
        }
        tr:hover { 
            background: #f8f9fa; 
        }
        .success { 
            color: green; 
            font-weight: bold; 
        }
        .error { 
            color: red; 
            font-weight: bold; 
        }
        .warning { 
            color: orange; 
            font-weight: bold; 
        }
        .info-box {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .endpoint {
            background: #2d3748;
            color: #a0aec0;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 5px 0;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }
        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
        }
        .badge-warning {
            background: #feebc8;
            color: #744210;
        }
    </style>
</head>
<body>';
?>

<h1>🔍 PHP Debug Information</h1>

<div class="info-box">
    <strong>📅 Data e ora:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
    <strong>⏰ Timezone:</strong> <?php echo date_default_timezone_get(); ?><br>
    <strong>🌐 Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?><br>
    <strong>📁 Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?><br>
    <strong>📂 Cartella corrente:</strong> <?php echo __DIR__; ?><br>
</div>

<h2>📊 Test Connessione Database/File</h2>
<?php
// Test scrittura file
$testFile = __DIR__ . '/test_write.txt';
$writeTest = @file_put_contents($testFile, 'Test write at ' . date('Y-m-d H:i:s'));
if ($writeTest !== false) {
    echo '<p class="success">✅ Scrittura file: Riuscita (test_write.txt creato)</p>';
    @unlink($testFile); // Pulizia
} else {
    echo '<p class="error">❌ Scrittura file: Fallita - permessi insufficienti</p>';
}

// Test cartella data
$dataDir = __DIR__ . '/../data';
if (is_dir($dataDir)) {
    echo '<p class="success">✅ Cartella data: Esiste</p>';
    if (is_writable($dataDir)) {
        echo '<p class="success">✅ Cartella data: Scrivibile</p>';
    } else {
        echo '<p class="error">❌ Cartella data: NON scrivibile</p>';
    }
} else {
    echo '<p class="warning">⚠️ Cartella data: NON esiste</p>';
    $createDir = @mkdir($dataDir, 0777, true);
    if ($createDir) {
        echo '<p class="success">✅ Cartella data: Creata automaticamente</p>';
    } else {
        echo '<p class="error">❌ Cartella data: Impossibile creare</p>';
    }
}

// Test file users.json
$usersFile = __DIR__ . '/../data/users.json';
if (file_exists($usersFile)) {
    echo '<p class="success">✅ File users.json: Esiste</p>';
    if (is_writable($usersFile)) {
        echo '<p class="success">✅ File users.json: Scrivibile</p>';
    } else {
        echo '<p class="error">❌ File users.json: NON scrivibile</p>';
    }
    $content = file_get_contents($usersFile);
    $json = json_decode($content, true);
    if ($json === null && !empty($content)) {
        echo '<p class="error">❌ File users.json: JSON non valido</p>';
    } else {
        echo '<p class="success">✅ File users.json: JSON valido</p>';
        echo '<p>📦 Contenuto: ' . count($json ?? []) . ' elementi</p>';
    }
} else {
    echo '<p class="warning">⚠️ File users.json: NON esiste (verrà creato al primo POST)</p>';
}
?>

<h2>🔌 Test Endpoints API</h2>
<?php
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'];

function testEndpoint($url, $description) {
    echo "<div class='endpoint'><strong>$description</strong><br>";
    echo "URL: $url<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "Status: <span class='error'>❌ Errore CURL: $error</span>";
    } else {
        $statusClass = ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'error';
        echo "Status: <span class='$statusClass'>$httpCode</span>";
    }
    echo "</div>";
}

testEndpoint($baseUrl . '/api/users', 'GET /api/users');
testEndpoint($baseUrl . '/api/debug.php', 'Debug endpoint (se esiste)');
testEndpoint($baseUrl . '/client/index.html', 'Client HTML');
?>

<h2>📋 Informazioni PHP</h2>
<table>
    <tr><th>Configurazione</th><th>Valore</th></tr>
    <tr><td>Versione PHP</td><td><?php echo phpversion(); ?></td></tr>
    <tr><td>PHP API</td><td><?php echo php_sapi_name(); ?></td></tr>
    <tr><td>Memory Limit</td><td><?php echo ini_get('memory_limit'); ?></td></tr>
    <tr><td>Max Execution Time</td><td><?php echo ini_get('max_execution_time'); ?>s</td></tr>
    <tr><td>Max Upload Size</td><td><?php echo ini_get('upload_max_filesize'); ?></td></tr>
    <tr><td>Post Max Size</td><td><?php echo ini_get('post_max_size'); ?></td></tr>
    <tr><td>Display Errors</td><td><?php echo ini_get('display_errors') ? 'On' : 'Off'; ?></td></tr>
    <tr><td>Error Reporting</td><td><?php echo error_reporting(); ?></td></tr>
    <tr><td>Allow URL Fopen</td><td><?php echo ini_get('allow_url_fopen') ? 'On' : 'Off'; ?></td></tr>
</table>

<h2>📦 Estensioni PHP</h2>
<table>
    <tr><th>Estensione</th><th>Stato</th></tr>
    <?php
    $extensions = ['curl', 'json', 'xml', 'mbstring', 'pdo_mysql', 'mysqli', 'sqlite3'];
    foreach ($extensions as $ext) {
        $loaded = extension_loaded($ext);
        $status = $loaded ? '<span class="success">✅ Caricata</span>' : '<span class="warning">⚠️ Non caricata</span>';
        echo "<tr><td>$ext</td><td>$status</td></tr>";
    }
    ?>
</table>

<h2>🌐 Variabili Server</h2>
<table>
    <tr><th>Variabile</th><th>Valore</th></tr>
    <?php
    $serverVars = [
        'REQUEST_METHOD', 'REQUEST_URI', 'QUERY_STRING', 
        'SCRIPT_NAME', 'PATH_INFO', 'CONTENT_TYPE', 
        'HTTP_ACCEPT', 'HTTP_HOST', 'REMOTE_ADDR',
        'HTTPS', 'SERVER_PORT', 'SERVER_NAME'
    ];
    foreach ($serverVars as $var) {
        $value = $_SERVER[$var] ?? '<em>non impostato</em>';
        echo "<tr><td>\$_SERVER['$var']</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    ?>
</table>

<h2>📁 Struttura Directory</h2>
<table>
    <tr><th>Percorso</th><th>Esiste?</th><th>Permessi</th></tr>
    <?php
    $paths = [
        __DIR__,
        __DIR__ . '/../data',
        __DIR__ . '/../client',
        __DIR__ . '/../api',
        $_SERVER['DOCUMENT_ROOT']
    ];
    
    foreach ($paths as $path) {
        $exists = file_exists($path) ? '✅' : '❌';
        $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : '-';
        echo "<tr><td>$path</td><td>$exists</td><td>$perms</td></tr>";
    }
    ?>
</table>

<h2>🔍 Headers della Richiesta</h2>
<table>
    <tr><th>Header</th><th>Valore</th></tr>
    <?php
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            echo "<tr><td>" . htmlspecialchars($name) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='2'>getallheaders() non disponibile</td></tr>";
    }
    ?>
</table>

<h2>🧪 Test Manuali</h2>
<div class="info-box">
    <h3>CURL dalla riga di comando:</h3>
    <pre class="endpoint">
# Test GET
curl -X GET <?php echo $baseUrl; ?>/api/users -H "Accept: application/json"

# Test POST
curl -X POST <?php echo $baseUrl; ?>/api/users \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"nome":"Test","email":"test@test.com","eta":25}'
    </pre>
    
    <h3>Test con JavaScript:</h3>
    <pre class="endpoint">
fetch('<?php echo $baseUrl; ?>/api/users')
  .then(res => res.json())
  .then(console.log)
  .catch(console.error);
    </pre>
</div>

<h2>⚠️ Errori PHP (se presenti)</h2>
<?php
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $errors = file($errorLog);
    $lastErrors = array_slice($errors, -10);
    echo "<pre class='endpoint'>";
    foreach ($lastErrors as $error) {
        echo htmlspecialchars($error);
    }
    echo "</pre>";
} else {
    echo "<p>Nessun error log trovato o accessibile</p>";
}
?>

<h2>✅ Checklist Test</h2>
<table>
    <tr><th>Test</th><th>Stato</th><th>Note</th></tr>
    <?php
    $tests = [
        'PHP Version >= 7.0' => version_compare(phpversion(), '7.0.0', '>='),
        'Extension JSON' => extension_loaded('json'),
        'Extension cURL' => extension_loaded('curl'),
        'Cartella scrivibile' => is_writable(__DIR__),
        'mod_rewrite attivo' => in_array('mod_rewrite', apache_get_modules() ?? []),
        'Allow URL Fopen' => ini_get('allow_url_fopen')
    ];
    
    foreach ($tests as $test => $result) {
        $status = $result ? '<span class="success">✅ OK</span>' : '<span class="error">❌ FAIL</span>';
        $note = $result ? '' : 'Verifica configurazione';
        echo "<tr><td>$test</td><td>$status</td><td>$note</td></tr>";
    }
    ?>
</table>

<?php
// Mostra phpinfo() in un iframe per non sporcare la pagina
echo '<h2>📚 PHP Info Completa</h2>';
echo '<iframe src="?phpinfo=1" width="100%" height="600" style="background:white; border:1px solid #ddd;"></iframe>';

if (isset($_GET['phpinfo'])) {
    phpinfo();
    exit;
}
?>

</body>
</html>