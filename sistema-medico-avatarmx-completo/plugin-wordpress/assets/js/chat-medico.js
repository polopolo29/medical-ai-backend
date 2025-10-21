jQuery(document).ready(function($) {
    var messagesContainer = $('#avatarmx-chat-messages');
    var input = $('#avatarmx-chat-input');
    var sendButton = $('#avatarmx-chat-send-button');
    var sessionId = null;

    function addMessage(sender, text) {
        messagesContainer.append('<p><strong>' + sender + ':</strong> ' + text + '</p>');
        messagesContainer.scrollTop(messagesContainer.prop("scrollHeight"));
    }

    async function startConversation() {
        try {
            const response = await $.ajax({
                url: avatarmx_vars.ajax_url,
                method: 'POST',
                data: {
                    action: 'avatarmx_iniciar_conversacion',
                    nonce: avatarmx_vars.nonce
                }
            });
            sessionId = response.session_id;
            addMessage('Asistente', response.message);
        } catch (error) {
            addMessage('Error', 'No se pudo iniciar la conversación.');
        }
    }

    async function sendMessage() {
        var message = input.val();
        if (message.trim() === '' || !sessionId) {
            return;
        }

        addMessage('Tú', message);
        input.val('');

        try {
            const response = await $.ajax({
                url: avatarmx_vars.ajax_url,
                method: 'POST',
                data: {
                    action: 'avatarmx_procesar_respuesta',
                    nonce: avatarmx_vars.nonce,
                    session_id: sessionId,
                    answer: message
                }
            });
            addMessage('Asistente', response.message);
        } catch (error) {
            addMessage('Error', 'No se pudo enviar el mensaje.');
        }
    }

    sendButton.on('click', sendMessage);
    input.on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    startConversation();
});
