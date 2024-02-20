document.addEventListener('DOMContentLoaded', function() {
    let userDisplayTchatCheckbox = document.getElementById('user_display_tchat');
    let visitorDisplayTchatCheckbox = document.getElementById('visitor_display_tchat');
    let userDisplaySendbarCheckbox = document.getElementById('user_display_send_bar');
    let visitorDisplaySendbarCheckbox = document.getElementById('visitor_display_send_bar');

    userDisplayTchatCheckbox.addEventListener('change', function() {
        if (!this.checked) {
            // Si user_display_tchat est décoché, décocher également visitor_display_tchat
            visitorDisplayTchatCheckbox.checked = false;
        }
    });

    visitorDisplayTchatCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Si visitor_display_tchat est coché, cocher également user_display_tchat
            userDisplayTchatCheckbox.checked = true;
        }
    });

    userDisplaySendbarCheckbox.addEventListener('change', function() {
        if (!this.checked) {
            // Si user_display_send_bar est décoché, décocher également visitor_display_send_bar
            visitorDisplaySendbarCheckbox.checked = false;
        }
    });

    visitorDisplaySendbarCheckbox.addEventListener('change', function() {
        if (this.checked) {
            // Si visitor_display_send_bar est coché, cocher également user_display_send_bar
            userDisplaySendbarCheckbox.checked = true;
        }
    });
});
