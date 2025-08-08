<?php
require_once 'config.php';
if (!isset($_SESSION['loggedin'])) {
    redirect('login.php');
}

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['id'];

if ($appointment_id === 0) {
    die("Invalid appointment ID.");
}

// Security Check: Verify that the logged-in user is part of this appointment
$verify_sql = "SELECT patient_id, doctor_id FROM appointments WHERE id = ?";
if ($verify_stmt = $conn->prepare($verify_sql)) {
    $verify_stmt->bind_param("i", $appointment_id);
    $verify_stmt->execute();
    $verify_stmt->store_result();

    if ($verify_stmt->num_rows == 1) {
        $verify_stmt->bind_result($patient_id, $doctor_id);
        $verify_stmt->fetch();

        if ($_SESSION['id'] != $patient_id && $_SESSION['id'] != $doctor_id) {
            die("Access Denied. You are not authorized to view this chat.");
        }
    } else {
        die("Appointment not found.");
    }
    $verify_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Consultation Chat</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles for sent and received chat bubbles */
        .chat-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .chat-message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        .sent {
            background-color: #0056b3;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 3px;
        }
        .received {
            background-color: #e9e9eb;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 3px;
        }
        .chat-message strong {
            display: block;
            font-size: 0.85em;
            margin-bottom: 4px;
            color: #555;
        }
        .sent strong {
            color: #cde4ff;
        }
        .no-appointments {
             text-align: center;
             padding: 20px;
             color: #777;
        }
        .error {
            color: red;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Consultation Chat</h2>
    <div class="chat-box" id="chat-box">
        <p class="no-appointments">Loading chat...</p>
    </div>
    <form id="chat-form" method="post">
        <input type="text" id="message-input" name="message" placeholder="Type your message..." required autocomplete="off">
        <input type="submit" value="Send">
    </form>
    <br>
    <a href="<?php echo $_SESSION['role'] == 'patient' ? 'patient_dashboard.php' : 'doctor_dashboard.php'; ?>" class="btn">Back to Dashboard</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const appointmentId = <?php echo $appointment_id; ?>;
    const currentUserId = <?php echo $user_id; ?>;

    // Helper function to escape HTML and prevent XSS attacks
    function escapeHTML(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Function to fetch and display messages
    const fetchMessages = async () => {
        try {
            // Added a cache-busting parameter to ensure fresh data
            const response = await fetch(`fetch_chat.php?id=${appointmentId}&_=${new Date().getTime()}`);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.statusText}`);
            }
            const messages = await response.json();
            
            // Check if user was scrolled to the bottom before refreshing
            const wasScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 1;

            chatBox.innerHTML = ''; // Clear previous messages
            
            if (messages.length === 0) {
                chatBox.innerHTML = '<p class="no-appointments">No messages yet. Start the conversation!</p>';
            } else {
                messages.forEach(msg => {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('chat-message');
                    messageElement.classList.add(msg.sender_id == currentUserId ? 'sent' : 'received');
                    
                    // Use innerHTML with escaped content for safety and simplicity
                    messageElement.innerHTML = `<strong>${escapeHTML(msg.sender_name)}:</strong> ${escapeHTML(msg.message)}`;
                    
                    chatBox.appendChild(messageElement);
                });
            }

            // Scroll to bottom only if the user was already at the bottom
            if (wasScrolledToBottom) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
            chatBox.innerHTML = '<p class="error">Could not load chat. Please check your connection and try again.</p>';
        }
    };

    // Function to send a message
    const sendMessage = async (event) => {
        event.preventDefault();
        const message = messageInput.value.trim();
        if (message === '') return;

        const formData = new FormData();
        formData.append('message', message);
        formData.append('appointment_id', appointmentId);

        try {
            const response = await fetch('send_message.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.statusText}`);
            }

            const result = await response.json();
            if (result.status === 'success') {
                messageInput.value = '';
                await fetchMessages(); // Immediately fetch new messages
            } else {
                console.error('Failed to send message:', result.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    };

    chatForm.addEventListener('submit', sendMessage);

    // Fetch messages every 3 seconds to keep the chat updated
    setInterval(fetchMessages, 3000);

    // Initial fetch on page load
    fetchMessages();
});
</script>

</body>
</html>
