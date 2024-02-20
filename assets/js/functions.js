/**
 * 
 * Function to insert the username in the message field
 * 
 * @param {*} username 
 */
export function insertUsername(username) {
    let messageInput = document.getElementById('tchatamstrad-message');
    // Ajouter le nom d'utilisateur au champ de texte
    messageInput.value += '@' + username + ' ';
}




/**
 * 
 * Function to add a message to the chat window
 * 
 * @param {*} message 
 * @param {*} username 
 */
export function displayMessage(message, username) {

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
    messageElement.dataset.username = username;
    messageElement.innerHTML = `
        <div class="delete-icon"><img src="${imageUrl}" alt="delete"></div>
        <div class="timestamp">${getCurrentTimestamp()}</div>
        <div class="username">${username} : </div>
        <div class="message">${message}</div>
    `;

    // Ajoute le nouvel élément de message à la fenêtre de chat
    chatWindow.appendChild(messageElement);

    // Supposons que #chat est l'ID de l'élément où vous affichez les messages
const chatContainer = document.getElementById('chat');

// Ensuite, après avoir ajouté un nouveau message, faites défiler vers le bas
chatContainer.scrollTop = chatContainer.scrollHeight;

}



/**
 * 
 * Function to get the current timestamp with the format HH:MM:SS
 * 
 * @returns 
 */
export function getCurrentTimestamp() {
    let now = new Date();
    let hours = now.getHours().toString().padStart(2, '0');
    let minutes = now.getMinutes().toString().padStart(2, '0');
    let secondes = now.getSeconds().toString().padStart(2, '0');
    return `${hours}:${minutes}:${secondes}`;
}




/**
 * 
 * Function to clean the message from html tags
 * 
 * @param {*} message 
 * @returns 
 */
export function htmlSpecialChars(message) {
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
export function stripTags(message) {
    return message.replace(/<\/?[^>]+(>|$)/g, "").replace(/\\/g, '');
}
