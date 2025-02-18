<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - Leo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Background Image Styling */
        body {
            background: url('leo-background.webp') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
        }

        /* Chat Container */
        .container {
            background: rgba(0, 0, 0, 0.7); /* Dark overlay for contrast */
            border-radius: 15px;
            padding: 20px;
            max-width: 600px;
            margin-top: 50px;
            color: white;
        }

        /* Chatbox Styling */
        #chatbox {
    background: rgba(255, 255, 255, 0.7); /* Reduce opacity (0.5 = 50%) */
    border-radius: 10px;
    padding: 15px;
    height: 400px;
    overflow-y: auto;
    backdrop-filter: blur(5px); /* Optional: Adds a glassy effect */
}


        /* Buttons & Inputs */
        .form-control, .btn {
            border-radius: 10px;
        }

        h2 {
            color: #00eaff;
        }


        /* Code Block Styling */
.code-container {
    position: relative;
    margin-top: 10px;
}

pre {
    background: #1e1e1e;
    color: #00eaff;
    padding: 10px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: "Courier New", monospace;
}

.copy-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #007bbf;
    color: white;
    border: none;
    padding: 5px 10px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
}

.copy-btn:hover {
    background: #00eaff;
}

    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Chat with Leo 🤖</h2>
        <div id="chatbox" class="border p-3"></div>
        <input type="text" id="user_input" class="form-control mt-2" placeholder="Type your message...">
        
        <!-- Buttons: Send, Voice Input, Text-to-Speech -->
        <div class="d-flex gap-2">
            <button class="btn btn-primary mt-2" onclick="sendMessage()">Send</button>
            <button class="btn btn-secondary mt-2" onclick="startListening()">🎤 Speak</button>
            <button class="btn btn-warning mt-2" onclick="speakResponse()">🔊 Listen</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Allow Enter key to send messages
            $("#user_input").keypress(function(event) {
                if (event.which === 13) {  // 13 = Enter key
                    event.preventDefault();
                    sendMessage();
                }
            });
        });

        function sendMessage() {
            let userMessage = $("#user_input").val().trim();
            if (userMessage === "") return;  

            $("#chatbox").append("<p class='text-primary'><strong>User:</strong> " + userMessage + "</p>");
            $("#user_input").val("");

            $("#typing").remove();
            $("#chatbox").append("<p id='typing' class='text-muted'><em>Leo is typing...</em></p>");

            $.post("chat.php", { message: userMessage }, function(data) {
                let response = JSON.parse(data);
                $("#typing").remove();
                typeResponse(response.response);
            });
        }

        function typeResponse(text) {
    let index = 0;
    let speed = 40;
    let formattedText = text.replace(/\n/g, "<br>");

    // Ensure each response has a unique ID
    let responseId = "bot-text-" + new Date().getTime();
    
    // If the response contains a code block, format it
    if (formattedText.includes("<pre><code>")) {
        let botMessage = $(`
            <div class='text-success'>
                <strong>Leo:</strong> 
                <div class="code-container">
                    <pre><code id="${responseId}">${formattedText}</code></pre>
                    <button class="copy-btn" onclick="copyCode('${responseId}')">📋 Copy</button>
                </div>
            </div>
        `);
        $("#chatbox").append(botMessage);
    } else {
        let botMessage = $("<p class='text-success'><strong>Leo:</strong> <span id='" + responseId + "'></span></p>");
        $("#chatbox").append(botMessage);
    }

    function typeLetter() {
        if (index < formattedText.length) {
            $("#" + responseId).html(formattedText.substring(0, index + 1));
            index++;
            setTimeout(typeLetter, speed);
        }
    }

    typeLetter();
    $("#chatbox").scrollTop($("#chatbox")[0].scrollHeight);
}

// Copy function for code blocks
function copyCode(codeId) {
    let codeElement = document.getElementById(codeId);
    if (codeElement) {
        let textArea = document.createElement("textarea");
        textArea.value = codeElement.textContent.trim(); // Trim extra spaces and newlines
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        
        // Show confirmation message
        let copyBtn = document.querySelector('.copy-btn');
        copyBtn.textContent = "✅ Copied!";
        setTimeout(() => copyBtn.textContent = "📋 Copy", 2000);
    }
}




        function startListening() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                alert("Your browser does not support speech recognition. Try using Google Chrome.");
                return;
            }

            var recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'en-US';
            recognition.start();

            recognition.onresult = function(event) {
                let voiceInput = event.results[0][0].transcript;
                $("#user_input").val(voiceInput);
                sendMessage();
            };

            recognition.onerror = function(event) {
                console.error("Speech recognition error:", event.error);
                alert("Speech recognition failed. Please try again.");
            };
        }

        function speakResponse() {
            let lastBotResponse = $("#chatbox p.text-success").last().find("span").html();
            
            if (!lastBotResponse || lastBotResponse.trim() === "") {
                alert("No response to read. Please send a message first!");
                return;
            }

            let plainText = lastBotResponse.replace(/<br\s*\/?>/g, " ");

            let speech = new SpeechSynthesisUtterance(plainText);
            speech.lang = 'en-US';
            speech.rate = 1;
            speech.pitch = 1;

            if ('speechSynthesis' in window) {
                window.speechSynthesis.speak(speech);
            } else {
                alert("Your browser does not support speech synthesis. Try using Google Chrome.");
            }
        }
    </script>
</body>
</html>
