<?php

// Load API key from .env file

function getEnvVar($key) {
    $env = parse_ini_file(__DIR__ . '/.env');
    return $env[$key] ?? null;
}

$api_key = getEnvVar('OPENAI_API_KEY');

if (!$api_key) {
    die("Error: API key is missing. Please check your .env file.");
} else {
    echo "Success! Your API key is loaded.";
}


// Connect to the database
$conn = new mysqli("localhost", "root", "", "ai_app");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get user message
$user_message = $_POST['message'] ?? '';

if (!empty($user_message)) {
    // Custom responses for specific keywords
    $custom_responses = [
        "who are you" => "I'm Leo, your friendly AI mentor! I love coding and cracking jokes.",
        "tell me a joke" => "Why did the programmer quit his job? Because he didn't get arrays. 😆",
        "how to learn PHP" => "Start by understanding basic syntax, then build small projects like login systems.",
        "who created you" => "I was created by an amazing developer named Hassane Jaber! 🚀",
    ];

    foreach ($custom_responses as $keyword => $custom_reply) {
        if (stripos($user_message, $keyword) !== false) {
            echo json_encode(["response" => nl2br($custom_reply)]); // Convert \n to <br>
            exit;
        }
    }

    $url = "https://api.openai.com/v1/chat/completions";

    // Define bot's personality and behavior
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "You are Leo, a friendly AI mentor who helps people learn programming. You love cracking jokes, but you're always professional and supportive."],
            ["role" => "user", "content" => $user_message]
        ],
        "temperature" => 0.8,  // Higher temperature = more creative responses
    ];

    $headers = [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);
    $ai_response = $response_data['choices'][0]['message']['content'] ?? "I'm not sure how to respond.";

    // Ensure AI response respects new lines
   // Format code blocks properly
   if (strpos($ai_response, "```") !== false) {
    $ai_response = preg_replace_callback('/```(\w+)?\n([\s\S]*?)```/', function ($matches) {
        return '<div class="code-container">
                    <pre><code id="code-block">' . htmlspecialchars(trim($matches[2])) . '</code></pre>
                    <button class="copy-btn" onclick="copyCode(\'code-block\')">📋 Copy</button>
                </div>';
    }, $ai_response);
}



    // Save the chat in the database
    $stmt = $conn->prepare("INSERT INTO messages (user_message, ai_response) VALUES (?, ?)");
    $stmt->bind_param("ss", $user_message, $ai_response);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(["response" => $ai_response]);
}
?>
