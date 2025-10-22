jQuery(document).ready(function($) {
    const chatWindow = $('#avatarmx-chat-window');
    const chatForm = $('#avatarmx-chat-form');
    const chatInput = $('#avatarmx-chat-input');
    const serverUrl = avatarmx_vars.server_url;
    const apiKey = avatarmx_vars.api_key; // Usar la clave de API de WordPress
    const userId = avatarmx_vars.user_id;

    let sessionId = null;
    let inProtocol = false;

    // ... (resto del código sin cambios)
    function addMessage(message, sender) {
        const messageElement = $('<div class="avatarmx-message"></div>')
            .addClass(sender === 'user' ? 'avatarmx-user-message' : 'avatarmx-bot-message')
            .html(`<p>${message}</p>`);
        chatWindow.append(messageElement);
        chatWindow.scrollTop(chatWindow[0].scrollHeight);
    }

    async function startConversation() {
        try {
            const response = await $.ajax({
                url: `${serverUrl}/api/v1/iniciar-conversacion?user_id=${userId}`,
                method: 'POST',
                headers: { 'Authorization': `Bearer ${apiKey}` }
            });
            sessionId = response.session_id;
            handleServerResponse(response);
        } catch (error) {
            addMessage('Error al iniciar la conversación.', 'bot');
        }
    }

    async function sendResponse(answer) {
        try {
            const response = await $.ajax({
                url: `${serverUrl}/api/v1/procesar-respuesta/${sessionId}`,
                method: 'POST',
                contentType: 'application/json',
                headers: { 'Authorization': `Bearer ${apiKey}` },
                data: JSON.stringify({ answer: answer })
            });
            handleServerResponse(response);
        } catch (error) {
            addMessage('Error al procesar la respuesta.', 'bot');
        }
    }

    async function executeNextStep() {
        try {
            const response = await $.ajax({
                url: `${serverUrl}/api/v1/ejecutar-siguiente-paso/${sessionId}`,
                method: 'POST',
                headers: { 'Authorization': `Bearer ${apiKey}` }
            });
            handleProtocolResponse(response);
        } catch (error) {
            addMessage('Error al ejecutar el paso del protocolo.', 'bot');
        }
    }

    function handleServerResponse(response) {
        if (response.siguiente_pregunta) {
            addMessage(response.siguiente_pregunta.texto, 'bot');
        } else if (response.siguiente_paso === 1) {
            addMessage(response.message, 'bot');
            inProtocol = true;
            chatInput.val("Continuar con el protocolo").prop('disabled', true);
            // Iniciar el protocolo automáticamente
            setTimeout(executeNextStep, 1000);
        }
    }

    function handleProtocolResponse(response) {
        addMessage(`<strong>Paso ${response.paso_ejecutado}:</strong><br><pre>${response.contenido}</pre>`, 'bot');

        if (response.siguiente_paso) {
            // Ejecutar el siguiente paso automáticamente tras una pausa
            setTimeout(executeNextStep, 2000);
        } else {
            addMessage("Protocolo finalizado. Recibirás un resumen detallado en tu correo electrónico.", 'bot');
            chatInput.prop('disabled', true);
        }
    }

    chatForm.on('submit', function(event) {
        event.preventDefault();
        const userAnswer = chatInput.val().trim();
        if (userAnswer === '' || inProtocol) return;

        addMessage(userAnswer, 'user');
        chatInput.val('');
        sendResponse(userAnswer);
    });

    startConversation();
});
