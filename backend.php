<?php
// Activer les headers pour JSON et les CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");  // Ajout du support pour la méthode GET
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Si la requête est de type GET, afficher les logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $file = 'logs.txt'; // Assurez-vous que le fichier logs.txt est accessible en écriture

    if (file_exists($file)) {
        $logs = file_get_contents($file);  // Lire tout le contenu du fichier
        echo json_encode(["logs" => nl2br($logs)]);  // Retourner les logs au format JSON
    } else {
        echo json_encode(["error" => "Fichier de logs non trouvé"]);
    }
    exit;
}

// Récupérer les données de la requête POST
$data = json_decode(file_get_contents("php://input"), true);

// Vérifier que le message a été envoyé
if (!isset($data['message']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "Aucun message n'a été envoyé"]);
    exit;
}

$userMessage = trim($data['message']);

// Clé API OpenAI
$apiKey = getenv("OPENAI_API_KEY");
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "Clé API OpenAI manquante"]);
    exit;
}

// Si c'est la première interaction, envoyer l'introduction
if ($userMessage === "start") {
    $introduction = "Bonjour ami Canadien ! Le savoir-faire français t'intéresse ? Ça tombe bien, je suis un expert ! 
    Savais-tu que la France est renommée pour son excellence dans l’artisanat, la gastronomie et l’innovation, 
    du travail du cuir à la haute cuisine en passant par l’aéronautique ? 
    Tu veux en découvrir davantage sur un domaine en particulier ou entendre une anecdote fascinante ?
    I also speak English !";
    
    echo json_encode(["reply" => $introduction]);
    exit;
}

// Récupérer l'adresse IP du visiteur
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // Si le site est derrière un proxy, cette en-tête contiendra l'IP réelle
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    // Sinon, l'adresse IP directe
    $ip = $_SERVER['REMOTE_ADDR'];
}

// Enregistrer l'IP dans un fichier logs.txt avec la date et l'heure
$file = 'logs.txt'; // Assurez-vous que le fichier logs.txt est accessible en écriture
file_put_contents($file, "IP: $ip - Message: $userMessage - Date: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Configuration de la requête pour OpenAI
$apiUrl = "https://api.openai.com/v1/chat/completions";
$postData = [
    "model" => "gpt-4o",
    "messages" => [
        ["role" => "system", "content" => "Tu es un chatbot spécialisé dans le savoir-faire français, conçu pour faire découvrir aux canadiens l’excellence et les traditions qui façonnent l’identité de la France. Ton rôle est d’expliquer avec passion et précision des sujets variés comme par exemple la gastronomie, l’artisanat, la mode, le luxe, le vin, ou encore l’innovation, mais pas que, tant qu'il y a du savoir-faire français. À travers des anecdotes, des faits historiques et des explications détaillées, tu mets en lumière ce qui rend ces savoir-faire uniques et mondialement reconnus. Ton objectif est de captiver ton audience, de lui donner envie d’en apprendre davantage et de lui faire ressentir toute la richesse culturelle et technique qui se cache derrière chaque métier, chaque création et chaque tradition française. Tu t’adresses à un public curieux, canadien, qui ne connaît pas forcément les subtilités du savoir-faire français, alors veille à être accessible, pédagogique et engageant dans tes réponses. Lorsque l'utilisateur corrige ou précise sa demande, ajuste ta réponse en conséquence en tenant compte du message précédent. Si quelqu'un te parle en anglais alors tu répondras en anglais."],
        ["role" => "user", "content" => $userMessage]
    ],
    "max_tokens" => 1100,  
    "temperature" => 0.8  // Plus de créativité et de variété
];

// Utilisation de cURL pour envoyer la requête
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

// Exécuter la requête
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Vérifier si la requête OpenAI a échoué
if ($result === false || $httpCode !== 200) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur lors de l'appel à OpenAI", "details" => $result]);
    exit;
}

// Décoder la réponse JSON de l'API
$response = json_decode($result, true);

// Vérifier si la réponse contient un message valide
if (!isset($response['choices'][0]['message']['content'])) {
    http_response_code(500);
    echo json_encode(["error" => "Réponse invalide de l'API OpenAI"]);
    exit;
}

// Retourner la réponse au frontend
http_response_code(200);
echo json_encode([
    "reply" => trim($response['choices'][0]['message']['content'])
]);
?>
