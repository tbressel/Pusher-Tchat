document.addEventListener('DOMContentLoaded', function () {
    // Initialisation de Pusher en dehors de la fonction submit
    const pusher = new Pusher("f72327941966ca2f1fe5", {
        cluster: "eu",
        encrypted: true,
        forceTLS: true
    });
    // Suscribe to a channel
    const channel = pusher.subscribe('tchat-amstrad');
    // Listen to event on your channel
    channel.bind('my-event', function (data) {
        let jData = JSON.stringify(data);
    // Ajoute le message à la fenêtre de chat
        displayMessage(data.message,data.user_name, data.pen, data.paper);
    });
    // Suscribe to a channel
    const channel2 = pusher.subscribe('tchat-amstrad');


    // Listen to event on your channel
    channel2.bind('new-user', function(data) {
        // Obtenez la liste des utilisateurs connectés
        let userList = document.getElementById('user-list');  
        // Vérifiez si l'utilisateur est déjà dans la liste
        let existingUser = Array.from(userList.children).find(function(user) {
            return user.textContent === data.user_name;
        });   
        // Si l'utilisateur n'est pas déjà dans la liste, ajoutez-le
        if (!existingUser) {
            let newUser = document.createElement('li');
            let url = window.location.protocol + '//' + window.location.host;
            let imageLocation = '/wp-content/plugins/tchat-amstrad/assets/img/icon_general2.png';
            let imageUrl = url + imageLocation;
            newUser.innerHTML = '<img src="' + imageUrl + '" alt="">' + data.user_name;      
            userList.appendChild(newUser);
        }
    });

    // Ajoutez un gestionnaire d'événements pour "pusher:subscription_succeeded"
    channel.bind('pusher:subscription_succeeded', function () {
    });
    const form = document.getElementById('tchatamstrad-form');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const penColor = localStorage.getItem("amstradtchat__penColor");
        const paperColor = localStorage.getItem("amstradtchat__paperColor");

        let message = document.getElementById('tchatamstrad-message').value;
        message = htmlSpecialChars(message);
        message = stripTags(message);
        if (message === '') {
            return;
        }
                // Nettoyer le champ de saisie de texte
                document.getElementById('tchatamstrad-message').value = '';
        try {
            const nonce = tchatamstrad_ajax.nonce; // Récupérer le nonce côté client
            const response = await fetch(tchatamstrad_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=tchat_amstrad_send_message&nonce=' + encodeURIComponent(nonce) 
                + '&message=' + encodeURIComponent(message)
                + '&pen=' + encodeURIComponent(penColor)
                + '&paper=' + encodeURIComponent(paperColor)
            });
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            const data = await response.json();
        } catch (error) {
            console.error('Erreur lors de la requête AJAX : ', error);
        }

    });

    // It was in Pusher documentation on their website :
    // https://pusher.com/docs/channels/getting_started/javascript/?ref=docs-index
    Pusher.logToConsole = true;





let chatWindow = document.querySelector('.tchatamstrad__windows--left');
if (chatWindow !== null) {
    chatWindow.addEventListener('click', function (event) {   
        let usernameElement = event.target.closest('.username');             
        if (usernameElement !== null) {
             let username = usernameElement.textContent.trim(); 
            insertUsername(username);
        }
    });
}

let emoteWindow = document.querySelector('.tchatamstrad__windows--right');
if (emoteWindow !== null) {
    emoteWindow.addEventListener('click', function (event) {   
        let emoteElement = event.target.closest('.emoticon');             
        if (emoteElement !== null) {
                console.log(emoteElement, "est du type : ", typeof(emoteElement.textContent));
            let code;
            if (emoteElement.textContent === "") { 
                code =  emoteElement.querySelector('img').alt;
            } else {
                code = emoteElement.textContent;
            }

            console.log(code);
            insertEmoticon(code);
        }
    });
}


document.getElementById('emoticon').addEventListener('click', function () {    
    document.getElementById('emoticons-overlay').classList.toggle('hidden');
    document.getElementById('user-list').classList.toggle('hidden');
});


    function applyColorToSelector(selector, variableName, storageKey) {
        // Fonction pour charger la couleur depuis le stockage local
        function loadColorFromLocalStorage() {
            const storedColor = localStorage.getItem(storageKey);
            if (storedColor) {
                document.documentElement.style.setProperty(variableName, storedColor);
            }
        }
    
        // Parcourir chaque élément select avec la classe spécifiée
        document.querySelectorAll(selector).forEach(select => {
            // Ajout d'un écouteur d'événements pour le changement de sélection
            select.addEventListener('change', (event) => {
                const selectedOption = event.target.options[event.target.selectedIndex];
                const color = selectedOption.dataset.setColor;
                document.documentElement.style.setProperty(variableName, color);
                // Enregistrer la couleur dans le stockage local
                localStorage.setItem(storageKey, color);
            });
    
            // Charger la couleur depuis le stockage local (au chargement de la page)
            loadColorFromLocalStorage();
        });
    }
    
    // Appliquer la couleur aux éléments avec la classe .pen-color et enregistrer dans le stockage local
    applyColorToSelector('.pen-color', '--pen-color', 'amstradtchat__penColor');
    
    // Appliquer la couleur aux éléments avec la classe .paper-color et enregistrer dans le stockage local
    applyColorToSelector('.paper-color', '--paper-color', 'amstradtchat__paperColor');
    


    document.getElementById('tchatamstrad__controlbar--sound').addEventListener('click', function() {
        this.classList.toggle('active');
    });




    /**
     * 
     * Function to add a message to the chat window
     * 
     * @param {*} message 
     * @param {*} username 
     */
    function displayMessage(message, username, pen, paper) {

        timestamp = Math.floor(Date.now() / 1000)
        message_key = username + "_" + timestamp;

        // Sélectionne l'élément qui représente la fenêtre de chat à gauche
        let chatWindow = document.querySelector('.tchatamstrad__windows--left');

        // Crée un nouvel élément de message
        url = window.location.protocol + '//' + window.location.host;
        imageLocation = '/wp-content/plugins/tchat-amstrad/assets/img/icon-croco.png';
        imageUrl = url + imageLocation;

        let messageElement = document.createElement('div');
            messageElement.classList.add('message__container');
            messageElement.setAttribute('data-message-key', message_key);
            messageElement.setAttribute('style', `background-color: ${paper};`);
            messageElement.dataset.username = username;

       
            messageElement.innerHTML = `
            <div class="delete-icon"><img src="${imageUrl}" alt="delete"></div>
            <div class="timestamp" style="color: ${pen};" >${getCurrentTimestamp()}</div>
            <div class="username" style="color: ${pen};">${username} : </div>
            <div class="message" style="color: ${pen};">${message}</div>
        `;

            // Ajoute le nouvel élément de message à la fenêtre de chat
            chatWindow.appendChild(messageElement);

        // Supposons que #chat est l'ID de l'élément où vous affichez les messages
        const chatContainer = document.getElementById('chat');

        // Ensuite, après avoir ajouté un nouveau message, faites défiler vers le bas
        chatContainer.scrollTop = chatContainer.scrollHeight;

            // Vérifier si le bouton de notification sonore est activé
    if (document.getElementById('tchatamstrad__controlbar--sound').classList.contains('active')) {
        // Si le bouton est activé, jouer le son
        document.getElementById('message-sound').play();
    }

    }

    /**
     * 
     * Function to get the current timestamp with the format HH:MM:SS
     * 
     * @returns 
     */
    function getCurrentTimestamp() {
        let now = new Date();
        let hours = now.getHours().toString().padStart(2, '0');
        let minutes = now.getMinutes().toString().padStart(2, '0');
        let secondes = now.getSeconds().toString().padStart(2, '0');
        return `${hours}:${minutes}:${secondes}`;
    }

    /**
     * 
     * Function to insert the username in the message field
     * 
     * @param {*} username 
     */
    function insertUsername(username) {
        let messageInput = document.getElementById('tchatamstrad-message');
        // Ajouter le nom d'utilisateur au champ de texte
        messageInput.value += '@' + username + ' ';
    }
    /**
     * 
     * Function to insert the username in the message field
     * 
     * @param {*} code 
     */
    function insertEmoticon(code) {
        let messageInput = document.getElementById('tchatamstrad-message');
        messageInput.value += ' ' + code + ' ';
    }

    /**
     * 
     * Function to clean the message from html tags
     * 
     * @param {*} message 
     * @returns 
     */
    function htmlSpecialChars(message) {
        return message
            .replace(/&/g, "")
            .replace(/</g, "")
            .replace(/>/g, "")
            .replace(/{/g, "")
            .replace(/}/g, "")
            .replace(/]/g, "")
            .replace(/\[/g, "")
            .replace(/`/g, "")
    }

    /**
     * 
     * Function to remove script tags from the message
     * 
     * @param {*} message 
     * @returns 
     */
    function stripTags(message) {
        return message.replace(/<\/?[^>]+(>|$)/g, "").replace(/\\/g, '');
    }


/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */
/***************************************************************************** */






});