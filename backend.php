<?php
header("Content-Type: application/json");

$api_key = "VOTRE_CLE_API_OPENAI";
$data = json_decode(file_get_contents("php://input"), true);
$user_message = $data["message"];

$prompt = "Tu es un expert du savoir-faire français. Réponds avec précision et bienveillance. \n\nUtilisateur : $user_message\nChatbot : ";

$api_url = "https://api.openai.com/v1/completions";
$post_fields = json_encode([
    "model" => "gpt-4",
    "prompt" => $prompt,
    "max_tokens" => 150,
    "temperature" => 0.7
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $api_key"
]);

$response = curl_exec($ch);
curl_close($ch);

$response_data = json_decode($response, true);
echo json_encode(["reply" => $response_data["choices"][0]["text"]]);
?>