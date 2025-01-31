<?php
// Activer les headers pour JSON et les CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Récupérer les données de la requête POST
$data = json_decode(file_get_contents("php://input"), true);

// Vérifier que le message a été envoyé
if (!isset($data['message']) || empty($data['message'])) {
    echo json_encode(["error" => "Aucun message n'a été envoyé"]);
    exit;
}

$userMessage = $data['message'];

// Clé API OpenAI (Assure-toi de la définir dans Render ou de la remplacer ici)
$apiKey = getenv("OPENAI_API_KEY");
if (!$apiKey) {
    echo json_encode(["error" => "Clé API OpenAI manquante"]);
    exit;
}

// Configuration de la requête pour OpenAI
$apiUrl = "https://api.openai.com/v1/chat/completions";
$postData = [
    "model" => "gpt-3.5-turbo",  // Utilisation d'un modèle compatible avec chat
    "messages" => [
        ["role" => "user", "content" => $userMessage]
    ],
    "max_tokens" => 150,
    "temperature" => 0.7
];

// Options pour l'appel à l'API
$options = [
    "http" => [
        "header" => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer $apiKey\r\n",
        "method" => "POST",
        "content" => json_encode($postData),
    ],
];

// Exécuter la requête vers l'API OpenAI
$context = stream_context_create($options);
$result = @file_get_contents($apiUrl, false, $context);

// Vérifier si la requête a échoué
if ($result === FALSE) {
    echo json_encode(["error" => "Erreur lors de l'appel à l'API OpenAI"]);
    exit;
}

// Décoder la réponse JSON de l'API
$response = json_decode($result, true);

// Vérification de la structure de la réponse pour débogage
var_dump($response);  // Affiche la réponse complète pour déboguer

// Vérifier si la clé "choices" existe et contient des données
if (!isset($response['choices'][0]['message']['content'])) {
    echo json_encode(["error" => "Réponse invalide de l'API OpenAI"]);
    exit;
}

// Retourner la réponse au frontend
echo json_encode([
    "reply" => trim($response['choices'][0]['message']['content'])
]);
?>
