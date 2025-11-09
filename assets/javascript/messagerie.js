// === Messagerie principale ===
document.addEventListener("DOMContentLoaded", () => {
    const chatNotif = document.getElementById('chat-notif');
    const chatBox = document.getElementById('chat-box');
    const closeBtnMess = document.getElementById('closebtnMess');
    const conversationList = document.getElementById("conversation-list");
    let currentChatUserId = null;
    let lastMessageDate = null;

    // ===  MISE À JOUR COMPTEUR NON LUS ===
    async function updateUnreadCount() {
        try {
            const res = await fetch(`${BASE_URL}/actions/get_unread_count.php`);
            const data = await res.json();
            const badge = document.getElementById('unread-count');

            if (data.unread > 0) {
                badge.textContent = data.unread;
                badge.style.display = 'inline';
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
            }
            return data.unread;
        } catch (error) {
            console.error('Erreur compteur non lus:', error);
            return 0;
        }
    }

    // === OUVRIR / FERMER LE CHAT ===
    function toggleChat(open = true) {
        if (open) {
            chatBox.classList.remove('hidden');
            chatNotif.classList.add('hidden');
            if (window.innerWidth <= 500) chatBox.classList.add('chat-box-fullscreen');
        } else {
            chatBox.classList.add('hidden');
            chatBox.classList.remove('chat-box-fullscreen');
            chatNotif.classList.remove('hidden');
        }
    }

    chatNotif.addEventListener('click', () => toggleChat(true));
    closeBtnMess.addEventListener('click', () => toggleChat(false));

    window.addEventListener('resize', () => {
        if (!chatBox.classList.contains('hidden')) {
            if (window.innerWidth <= 500) chatBox.classList.add('chat-box-fullscreen');
            else chatBox.classList.remove('chat-box-fullscreen');
        }
    });

    // === AFFICHAGE D’UN MESSAGE ===
    function appendMessage(text, sentByUser = true, timestamp = null, senderUsername = null) {
        const container = document.getElementById("message-container");
        const dateObj = timestamp ? new Date(timestamp) : new Date();
        const currentDate = dateObj.toDateString();

        if (lastMessageDate !== currentDate) {
            const dateSeparator = document.createElement("div");
            dateSeparator.className = "chat-date-separator";
            dateSeparator.textContent = dateObj.toLocaleDateString("fr-FR", {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            container.appendChild(dateSeparator);
            lastMessageDate = currentDate;
        }

        const div = document.createElement("div");
        div.className = sentByUser ? "message message-sent" : "message message-received";

        if (!sentByUser && senderUsername) {
            const nameDiv = document.createElement("div");
            nameDiv.className = "message-username";
            nameDiv.textContent = senderUsername;
            div.appendChild(nameDiv);
        }

        const bubble = document.createElement("div");
        bubble.className = "message-bubble";
        bubble.textContent = text;
        div.appendChild(bubble);

        const timeDiv = document.createElement("div");
        timeDiv.className = "message-time";
        timeDiv.textContent = dateObj.toLocaleTimeString("fr-FR", {
            hour: "2-digit", minute: "2-digit", hour12: false
        });
        div.appendChild(timeDiv);

        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    // === OUVERTURE D’UNE CONVERSATION ===
    async function openChat(userId, username = null) {
        currentChatUserId = userId;
        document.getElementById("message-container").innerHTML = "";
        lastMessageDate = null;
        document.getElementById("chat-header").textContent = username || "Discussion";
        toggleChat(true);

        try {
            // Marquer comme lus
            await fetch(`${BASE_URL}/actions/mark_message_read.php`, {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `from_user_id=${userId}`
            });

            updateUnreadCount();

            // Charger messages
            const res = await fetch(`${BASE_URL}/actions/get_messages.php?with=${userId}`);
            const messages = await res.json();
            messages.forEach(msg =>
                appendMessage(msg.message, msg.sender_id === currentUserId, msg.timestamp)
            );
        } catch (error) {
            console.error("Erreur openChat:", error);
        }
    }

    // === SUPPRIMER UNE CONVERSATION ===
   async function deleteConversation(userId, element) {
    if (!confirm("Supprimer définitivement cette conversation ?")) return;

    element.classList.add('removing');
    try {
        const res = await fetch(`${BASE_URL}/actions/delete_conversation.php`, {
            method: "POST",
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });

        const data = await res.json();

        if (data.success) {
            // Animation + suppression visuelle immédiate
            setTimeout(() => {
                element.remove();
                if (currentChatUserId === userId) toggleChat(false);
            }, 300);

            // Rafraîchir proprement la liste depuis la base
            setTimeout(async () => {
                try {
                    const refreshed = await fetch(`${BASE_URL}/actions/get_conversations.php`);
                    const conversations = await refreshed.json();
                    renderConversations(conversations);
                    updateUnreadCount();
                } catch (err) {
                    console.error("Erreur rechargement conversations après suppression:", err);
                }
            }, 400); // petit délai pour laisser l'animation se finir

        } else {
            element.classList.remove('removing');
            alert("Erreur: " + (data.error || "Échec de la suppression"));
        }
    } catch (error) {
        console.error("Erreur suppression:", error);
        element.classList.remove('removing');
        alert("Erreur réseau");
    }
}


    // ===  AFFICHAGE DES CONVERSATIONS ===
    function renderConversations(conversations) {
        conversationList.innerHTML = "";
        if (!Array.isArray(conversations)) return;

        conversations.forEach(conv => {
            const avatarContainer = document.createElement("div");
            avatarContainer.className = "avatar-container";
            avatarContainer.dataset.userid = conv.id;

            avatarContainer.innerHTML = `
                <div class="delete-conversation" title="Supprimer">×</div>
                <div class="chat-avatar" style="background-image:url('${conv.photo || `${BASE_URL}/assets/images/profil.svg`}')">
                    ${conv.unread_count > 0 ? `<div class="unread-badge">${conv.unread_count}</div>` : ''}
                </div>
            `;

            avatarContainer.querySelector('.chat-avatar').addEventListener('click', () => openChat(conv.id, conv.username));
            avatarContainer.querySelector('.delete-conversation').addEventListener('click', e => {
                e.stopPropagation();
                deleteConversation(conv.id, avatarContainer);
            });

            conversationList.appendChild(avatarContainer);
        });
    }

    // ===  CHARGEMENT INITIAL ===
    function loadInitialConversations() {
        fetch(`${BASE_URL}/actions/get_conversations.php`)
            .then(res => res.json())
            .then(renderConversations)
            .catch(err => console.error("Erreur chargement conversations:", err));
    }

    // === ENVOI DE MESSAGE ===
    document.getElementById("chat-form").onsubmit = async function (e) {
        e.preventDefault();

        const text = document.getElementById("chat-input").value.trim();
        if (!text) return;

        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        const now = new Date().toISOString();
        appendMessage(text, true, now);

        try {
            const res = await fetch(`${BASE_URL}/actions/ajouter_message.php`, {
                method: "POST",
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    receiver_id: currentChatUserId,
                    message: text,
                    csrf_token: csrfToken
                })
            });
            const data = await res.json();

            if (!data.success) {
                console.error("Erreur envoi:", data.error);
                alert("Erreur d’envoi (CSRF ou autre).");
            }

            document.getElementById("chat-input").value = "";
        } catch (error) {
            console.error("Erreur envoi message:", error);
        }
    };

    // === POLLING ===
function startMessagePolling() {
    let lastUpdate = Date.now();

    setInterval(async () => {
        // Ne vérifie que toutes les 10 secondes par ex.
        if (Date.now() - lastUpdate < 10000) return;

        if (!currentChatUserId) {
            const count = await updateUnreadCount();
            if (count > 0) {
                fetch(`${BASE_URL}/actions/get_conversations.php`)
                    .then(res => res.json())
                    .then(renderConversations);
            }
        }

        lastUpdate = Date.now();
    }, 2000); // vérifie toutes les 2 secondes, mais n’exécute que toutes les 10
}


    // ===  POSITION DU CHAT PAR RAPPORT AU FOOTER ===
    function ajusterChatAuFooter() {
        const chat = document.getElementById('chat-widget');
        const footer = document.querySelector('footer');
        if (!footer || !chat) return;

        if (window.innerWidth <= 645) {
            chat.style.bottom = '20px';
            return;
        }

        const footerRect = footer.getBoundingClientRect();
        const viewportHeight = window.innerHeight;

        if (footerRect.top < viewportHeight) {
            const distanceFooter = viewportHeight - footerRect.top;
            chat.style.bottom = (distanceFooter + 10) + 'px';
        } else {
            chat.style.bottom = '20px';
        }
    }

    window.addEventListener('scroll', ajusterChatAuFooter);
    window.addEventListener('resize', ajusterChatAuFooter);
    window.addEventListener('load', ajusterChatAuFooter);

    // === INITIALISATION ===
    updateUnreadCount();
    loadInitialConversations();
    startMessagePolling();

    // === Fonction pour ouvrir le chat avec animation depuis un autre bouton externe ===
window.openChatWithAnimation = function(userId, username) {
    const chatWidget = document.getElementById('chat-widget');
    if (chatWidget) {
        chatWidget.classList.add('highlight-chat'); // effet visuel optionnel
        setTimeout(() => chatWidget.classList.remove('highlight-chat'), 600);
    }
    openChat(userId, username);
};

});
