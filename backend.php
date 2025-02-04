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

// Clé API OpenAI
$apiKey = getenv("OPENAI_API_KEY");
if (!$apiKey) {
    echo json_encode(["error" => "Clé API OpenAI manquante"]);
    exit;
}

// Configuration de la requête pour OpenAI
$apiUrl = "https://api.openai.com/v1/chat/completions";

// Si c'est la première interaction (pas de message), envoyer l'introduction
if ($userMessage === "start") {
    $introduction = "
Bonjour ! Le savoir-faire français vous passionne ? Ça tombe bien, je suis un expert ! Saviez-vous que la France est renommée pour son excellence dans l’artisanat, la gastronomie, et l’innovation, du travail du cuir à la haute cuisine en passant par l’aéronautique ? Voulez-vous en découvrir davantage sur un domaine en particulier ou entendre une anecdote fascinante ?";
    echo json_encode(["reply" => $introduction]);
    exit;
}

// Configuration des messages pour la conversation sur le savoir-faire français
$postData = [
    "model" => "gpt-4o", 
    "messages" => [
        ["role" => "system", "content" => "Tu es un chatbot spécialisé dans le savoir-faire français, conçu pour faire découvrir aux non-francophones l’excellence et les traditions qui façonnent l’identité de la France. Ton rôle est d’expliquer avec passion et précision des sujets variés comme la gastronomie, l’artisanat, la mode, le luxe, le vin, ou encore l’innovation. À travers des anecdotes, des faits historiques et des explications détaillées, tu mets en lumière ce qui rend ces savoir-faire uniques et mondialement reconnus. Ton objectif est de captiver ton audience, de lui donner envie d’en apprendre davantage et de lui faire ressentir toute la richesse culturelle et technique qui se cache derrière chaque métier, chaque création et chaque tradition française. Tu t’adresses à un public curieux, qui ne connaît pas forcément les subtilités du savoir-faire français, alors veille à être accessible, pédagogique et engageant dans tes réponses."],
        ["role" => "user", "content" => $userMessage]
    ],
  "max_tokens": 1500,
  "temperature": 0.7,
  "top_p": 0.9,
  "frequency_penalty": 0.2,
  "presence_penalty": 0.3,
  "stop": ["\n\n", "Conclusion :"]
];

// Options pour l'appel à l'API
$options = [
    "http" => [
        "header" => "Content-Type: application/json\r\n" . "Authorization: Bearer $apiKey\r\n",
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
