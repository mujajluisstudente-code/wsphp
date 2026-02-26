<?php
// Abilita CORS per test da browser
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestione preflight OPTIONS
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    http_response_code(200);
    exit();
}

// Nome del file JSON per memorizzare i dati
define('DATA_FILE', 'data.json');

// Inizializza il file JSON se non esiste
if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, json_encode([]));
}

// Legge il metodo HTTP
$metodo = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Legge il tipo di contenuto inviato dal client
$ct = $_SERVER["CONTENT_TYPE"] ?? 'application/json';
$type = explode("/", $ct);

// Legge il tipo di contenuto di ritorno richiesto dal client
$retct = $_SERVER["HTTP_ACCEPT"] ?? 'application/json';
$ret = explode("/", $retct);

// Funzione per leggere i dati dal file JSON
function readData() {
    return json_decode(file_get_contents(DATA_FILE), true) ?? [];
}

// Funzione per salvare i dati nel file JSON
function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Funzione per trovare un elemento per ID
function findById($id, $data) {
    foreach ($data as $index => $item) {
        if (isset($item['id']) && $item['id'] == $id) {
            return ['index' => $index, 'item' => $item];
        }
    }
    return null;
}

// Estrai l'ID dall'URI se presente (es. /index.php/123)
$id = null;
if (isset($uri[2]) && is_numeric($uri[2])) {
    $id = $uri[2];
}

// Legge il body della richiesta
$body = file_get_contents('php://input');

// Decodifica i dati in base al Content-Type
$data = [];
if (!empty($body)) {
    if ($type[1] == "json") {
        $data = json_decode($body, true) ?? [];
    } elseif ($type[1] == "xml") {
        $xml = simplexml_load_string($body);
        $json = json_encode($xml);
        $data = json_decode($json, true) ?? [];
    }
}

// Gestione delle richieste in base al metodo
switch ($metodo) {
    case 'GET':
        $db = readData();
        
        if ($id !== null) {
            // GET specifico: restituisce un singolo elemento
            $found = findById($id, $db);
            if ($found) {
                $response = $found['item'];
                http_response_code(200);
            } else {
                $response = ['error' => 'Elemento non trovato'];
                http_response_code(404);
            }
        } else {
            // GET generale: restituisce tutti gli elementi
            $response = $db;
            http_response_code(200);
        }
        break;
        
    case 'POST':
        $db = readData();
        
        // Validazione base
        if (empty($data)) {
            $response = ['error' => 'Dati non validi o mancanti'];
            http_response_code(400);
            break;
        }
        
        // Assegna un nuovo ID
        $newId = 1;
        if (!empty($db)) {
            $newId = max(array_column($db, 'id')) + 1;
        }
        
        $data['id'] = $newId;
        $db[] = $data;
        saveData($db);
        
        $response = [
            'message' => 'Elemento creato con successo',
            'item' => $data
        ];
        http_response_code(201);
        break;
        
    case 'PUT':
        if ($id === null) {
            $response = ['error' => 'ID richiesto per la modifica'];
            http_response_code(400);
            break;
        }
        
        $db = readData();
        $found = findById($id, $db);
        
        if ($found) {
            // Aggiorna l'elemento mantenendo l'ID originale
            $data['id'] = (int)$id;
            $db[$found['index']] = $data;
            saveData($db);
            
            $response = [
                'message' => 'Elemento aggiornato con successo',
                'item' => $data
            ];
            http_response_code(200);
        } else {
            $response = ['error' => 'Elemento non trovato'];
            http_response_code(404);
        }
        break;
        
    case 'DELETE':
        if ($id === null) {
            $response = ['error' => 'ID richiesto per la cancellazione'];
            http_response_code(400);
            break;
        }
        
        $db = readData();
        $found = findById($id, $db);
        
        if ($found) {
            array_splice($db, $found['index'], 1);
            saveData($db);
            
            $response = ['message' => 'Elemento eliminato con successo'];
            http_response_code(200);
        } else {
            $response = ['error' => 'Elemento non trovato'];
            http_response_code(404);
        }
        break;
        
    default:
        $response = ['error' => 'Metodo non supportato'];
        http_response_code(405);
}

// Imposta l'header Content-Type in base all'Accept del client
header("Content-Type: " . $retct);

// Restituisce la risposta nel formato richiesto
if ($ret[1] == "json") {
    echo json_encode($response, JSON_PRETTY_PRINT);
} elseif ($ret[1] == "xml") {
    // Conversione semplice in XML
    $xml = new SimpleXMLElement('<?xml version="1.0"?><response/>');
    array_to_xml($response, $xml);
    echo $xml->asXML();
}

// Funzione helper per convertire array in XML
function array_to_xml($data, &$xml) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }
            $subnode = $xml->addChild($key);
            array_to_xml($value, $subnode);
        } else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}
?>
