<?php
/*
Plugin Name: Tchat Amstrad
Plugin URI: https://amstrad.eu/
Description: Ceci est mon premier plugin créé spécialement pour la communauté Amstrad.eu
Author: Zisquier
Version: 1.12.1
*/



// version 1.0 : Création de l'interface du tchat avec les onglets, la fenêtre de discussion et le formulaire d'envoi de message
// version 1.1 : Ajout de la fonctionnalité d'envoie de message en AJAX vers le serveur de Pusher
// version 1.2 : Ajout de la fonctionnalité d'affichage des utilisateurs connectés en temps réel dans le client
// version 1.3 : Ajout de la fonctionnalité d'affichage de l'historique des messages
// version 1.4 : Ajoute de la fonctionnalité d'affichage des onglets et de la fenêtre de discussion en fonction des options de l'administrateur
// version 1.5 : Ajout de la possibilité de répondre à un message en cliquant dessus
// version 1.6 : Correcttion du champ de saisie de message qui ne se vide pas après l'envoie d'un message
// version 1.7 : Ajout de fake-users (MissX, Allan, Amélie Minuit, nuts) nuts est visible uniquement par l'utilisateur 'ben'
// version 1.8 : Ajout d'un nonce stocké dans les options de WordPress pour protéger contre les attaques CSRF
// version 1.8.1 : Correction de l'affichage de l'icone croco à coté du pseudo selon le slug de la page
// version 1.9 : Ajout du responsive design pour les mobiles et les tablettes
// version 1.10 : Persistance des messages dans le Tchat quand on navige sur le site.
// version 1.10.1 : Désactivation temporaire de la gestinon de l'historique
// version 1.10.2 : Ajout d'une banque de gif animés.
// version 1.11 : Ajout d'une banque de emojis modenre à la place des gif animés.
// version 1.11.2 : Correction de bug sur le moteur Chromium version windows où les emoticons ne s'affichaient pas.
// version 1.12 : Ajoute da la barre de controle incluant les couleurs de pen et de paper, les notifications sonores, les emoticones et le thème sombre
// version 1.12.1 : Ajout de la fonctionnalité de couleur de pen et de paper ajouté en base de donnée pour la persistance des couleurs.



// à améliorer :  
// nettoyer l'input du champ de saisie juste apres le clique du bouton d'envoie de message pour éviter les doublons
// les liens doivent être cliquables
// limiter le nombre de caractère à 512 dans le champ de saisie de message
// ajouter des emoticones



// include('tchat-install.php');

// include_once('tchat-admin.php');

// include('tchat-config.php');

// ADMINISTRATION  ---> Ajout d'une page de réglages dans l'administration
function tchat_amstrad_menu_page()
{
    add_menu_page(
        'Réglage du Tchat Amstrad',
        'Tchat Amstrad',
        'manage_options',
        'tchat_amstrad_settings',
        'tchat_amstrad_settings_page'
    );
}
add_action('admin_menu', 'tchat_amstrad_menu_page');



/////////////////////////////////////////////////////////////////////////////
////////////////////////   CONFIGURATION GENERAL   //////////////////////////
/////////////////////////////////////////////////////////////////////////////


// créer un fichier log dans le dossier du plugin quand celui-ci est activé
register_activation_hook(__FILE__, function () {
    touch(__DIR__ . '/tchat-amstrad.log');
});

// efface un fichier log dans le dossier du plugin quand celui-ci est activé
register_deactivation_hook(__FILE__, function () {
    unlink(__DIR__ . '/tchat-amstrad.log');
});



// Après avoir installé Composer : nécessaire pour l'utilisation de Pusher
require 'vendor/autoload.php';



// // Lorsqu'un utilisateur se connecte, définir la métadonnée 'currently_logged_in' à '1'
// add_action('wp_login', function ($user_login, $user) {
//     update_user_meta($user->ID, 'currently_logged_in', '1');
// }, 10, 2);

// Lorsqu'un utilisateur est sur le point de se déconnecter, supprimer la métadonnée 'currently_logged_in'
add_action('clear_auth_cookie', function () {
    $current_user = wp_get_current_user();
    delete_user_meta($current_user->ID, 'currently_logged_in');
}, 10, 0);



/////////////////////////////////////////////////////////////////////////////
////////////////////////      SECURITE GENERAL     //////////////////////////
/////////////////////////////////////////////////////////////////////////////

// On vérifie que le plugin est bien appelé depuis WordPress
defined('ABSPATH') or die('Circulez y\'a rien à voir !');


/////////////////////////////////////////////////////////////////////////////
////////////////////////      MOTEUR WEBSOCKET     //////////////////////////
/////////////////////////////////////////////////////////////////////////////



/**
 * Sauvegarde des messages dans la base de données
 *
 * @param [type] $user
 * @param [type] $timestamp
 * @param [type] $message
 * @return void
 */
function tchatamstrad_messages_save($user, $message, $pen, $paper)
{
    // $wpdb is the global variable for WordPress database interaction
    global $wpdb;

    // $wpdb->prefix is the prefix of the WordPress database containing the table name
    $table_name = $wpdb->prefix . 'tchatamstrad_messages';

    // Get the current date and time
    $timestamp = current_time('mysql');

    // Créez une clé unique en combinant le nom d'utilisateur et le timestamp
    $message_key = $user . '_' . strtotime($timestamp);

    // Data to insert
    $data = array(
        'user' => $user,
        'timestamp' => $timestamp,
        'message' => $message,
        'message_key' => $message_key,
        'pen' => $pen,
        'paper' => $paper
    );

    // Format of each of the values in $data
    $format = array('%s', '%s', '%s');

    // Insert the data into the table
    $result = $wpdb->insert($table_name, $data, $format);

    // Check if the insertion was successful
    if ($result === false) {
        error_log('Failed to insert message into database');
    } else {
        error_log('Message inserted into database successfully');
    }
}



/**
 * function to load all messages in the current day
 *
 * @param [type] $display_dailyMessages
 * @return Array
 */
function get_daily_messages()
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'tchatamstrad_messages';

    // Get all daily messages
    $daily_messages = $wpdb->get_results("SELECT * FROM $table_name WHERE DATE(timestamp) = CURDATE() ORDER BY timestamp ASC");

    return $daily_messages;
}
add_action('wp_footer',  'get_daily_messages');



// /**
//  * function to loazd the last 10 messages from the database when wp_footer is called
//  *
//  * @param [type] $display_history
//  * @return void
//  */
// function get_last_10_messages($display_history)
// {
//     // Si l'option pour l'historique est désactivée, ne pas afficher l'historique
//     if (!$display_history) {
//         return;
//     }
//     global $wpdb;

//     $table_name = $wpdb->prefix . 'tchatamstrad_messages';

//     // Get the last 10 messages  
//     $historicMessages = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp ASC LIMIT 20");

//     return $historicMessages;

//     // // Convert the messages to JSON
//     // $json = json_encode($messages);

//     // // Output the JSON to the browser
//     // echo "<script>var messages = $json;</script>";
// }
// add_action('wp_footer', function () {
//     $display_history = get_option('display_history', 1);
//     get_last_10_messages($display_history);
// });




/**
 * Pour envoyer un message dans le tchat en AJAX récupéré depuis le front-end, vers le serveur de Pusher
 *
 * @return void
 */
function tchat_amstrad_send_message_callback()
{
    // La requête coté client est bien une requête POST, contenant un champ 'message', un champ 'user_name' et un champ 'channel'
    if (!isset($_POST['message'])) {
        wp_send_json_error(array('message' => 'Le message est vide.'));
    }

    // Sécurité : on nettoye le message reçu avec une fonction de WordPress
    // Cette fonction permet de supprimer les balises HTML et les caractères spéciaux à l'instart d'un strip_tags() ou d'un htmlspecialchars()
    $message = sanitize_text_field($_POST['message']);

    $message = strip_tags(stripslashes(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')));

    // Limiter le nombre de caractères à 1024
    $message = substr($message, 0, 1024);

    $pen = sanitize_text_field($_POST['pen']);
    $paper = sanitize_text_field($_POST['paper']);




    // Récupère le nom d'utilisateur de l'utilisateur actuellement connecté
    $currentUserName = wp_get_current_user()->user_login;

    if (!$currentUserName) {
        $currentUserName = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : 'Anonyme';
    }



    // Vérifie si l'utilisateur est nouveau
    $isNewUser = false;
    if (!isset($_SESSION['tchat_amstrad_users'])) {
        $_SESSION['tchat_amstrad_users'] = array();
    }
    if (!in_array($currentUserName, $_SESSION['tchat_amstrad_users'])) {
        $isNewUser = true;
        $_SESSION['tchat_amstrad_users'][] = $currentUserName;
    }

    // Configuration Pusher
    $options = array(
        'cluster' => 'eu',
        'useTLS' => true
    );

    $pusher = new Pusher\Pusher(
        "f72327941966ca2f1fe5", // Replace with 'key' from dashboard
        "9674757ea72d3884c866", // Replace with 'secret' from dashboard
        "1736321", // Replace with 'app_id' from dashboard
        array(
            'cluster' => 'eu' // Replace with 'cluster' from dashboard
        )
    );

    // Envoyer le message sur le canal 'tchat-amstrad' avec l'événement 'my-event'
    // $pusher->trigger('tchat-amstrad', 'my-event', array('message' => $message, 'user_name' => $currentUserName));
    $pusher->trigger('tchat-amstrad', 'my-event', array('message' => $message, 'pen' => $pen, 'paper' => $paper, 'user_name' => $currentUserName, 'new_user' => $isNewUser));
    if ($isNewUser) {
        $pusher->trigger('tchat-amstrad', 'new-user', array('user_name' => $currentUserName));
    }
    tchatamstrad_messages_save($currentUserName, $message, $pen, $paper);
    wp_send_json_success(array('message' => 'Message envoyé avec succès.'));
}
// Deux hooks d'action pour l'envoie de message en AJAX vers le serveur de Pusher
add_action('wp_ajax_tchat_amstrad_send_message', 'tchat_amstrad_send_message_callback');
add_action('wp_ajax_nopriv_tchat_amstrad_send_message', 'tchat_amstrad_send_message_callback');




/**
 * Styles loading for Tchat Amstrad plugin design
 *
 * @return void
 */
function tchat_amstrad_enqueue_styles()
{
    wp_enqueue_style('tchat-amstrad-style', plugin_dir_url(__FILE__) . 'assets/style/style.css');
}
add_action('wp_enqueue_scripts', 'tchat_amstrad_enqueue_styles');


/**
 * Scripts for Tchat Amstrad plugin
 * Pusher script is loaded from CDN, it's used to send messages in real time
 * 
 *
 * @return void
 */
function tchat_amstrad_enqueue_scripts()
{
    wp_enqueue_script('pusher-script', 'https://js.pusher.com/8.0.1/pusher.min.js', [], false, true);
    wp_enqueue_script('tchat-amstrad-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', [], false, true);
    wp_localize_script('tchat-amstrad-script', 'tchatamstrad_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'rest_url' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest')
    ));
}
add_action('wp_enqueue_scripts', 'tchat_amstrad_enqueue_scripts');



/**
 * Plugin activation generate database table to send archived messages
 *
 * @return void
 */
function tchatamstrad_install()
{

    // Générer un nonce pour protegé contre les attaque CSRF
    $nonce = wp_create_nonce('tchat-amstrad-nonce');

    // Enregistrer le nonce dans les options de WordPress pour une utilisation ultérieure si nécessaire
    update_option('tchat_amstrad_nonce', $nonce);


    // $wpdb is the global variable for WordPress database interaction
    global $wpdb;

    // get_charset_collate() is a WordPress function to get the charset collate of the database
    $charset_collate = $wpdb->get_charset_collate();

    // $wpdb->prefix is the prefix of the WordPress database containing the table name
    $table_name = $wpdb->prefix . 'tchatamstrad_messages';

    // SQL query to create the table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,        
        user varchar(255) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        message TEXT NOT NULL,
        message_key varchar(255) NOT NULL,
        pen varchar(8) NOT NULL,
        paper varchar(8) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}
// Activation hook
register_activation_hook(__FILE__, 'tchatamstrad_install');



function load_custom_admin_script($hook) {
    // Vérifie si ont est  sur la page de config en admin
    if ($hook == 'toplevel_page_tchat_amstrad_settings') {
        wp_enqueue_script('tchat-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', [], false, true);
    }
}
add_action('admin_enqueue_scripts', 'load_custom_admin_script');

// ADMINISTRATION  ---> Fonction qui affiche le contenu de la page de réglages
function tchat_amstrad_settings_page()
{


    // Vérifier si le formulaire a été soumis
    if (isset($_POST['submit'])) {


        // Récupérer les valeurs du formulaire
        $visitor_display_tabs = isset($_POST['visitor_display_tabs']) ? 1 : 0;
        $user_display_tabs = isset($_POST['user_display_tabs']) ? 1 : 0;
        $visitor_display_send_bar = isset($_POST['visitor_display_send_bar']) ? 1 : 0;
        $user_display_send_bar = isset($_POST['user_display_send_bar']) ? 1 : 0;
        $display_history = isset($_POST['display_history']) ? 1 : 0;
        $user_display_emoticons = isset($_POST['user_display_emoticons']) ? 1 : 0;
        $visitor_display_emoticons = isset($_POST['visitor_display_emoticons']) ? 1 : 0;

        // Enregistrer ces options
        update_option('visitor_display_tabs', $visitor_display_tabs);
        update_option('user_display_tabs', $user_display_tabs);
        update_option('visitor_display_send_bar', $visitor_display_send_bar);
        update_option('user_display_send_bar', $user_display_send_bar);
        update_option('display_history', $display_history);
        update_option('user_display_emoticons', $user_display_emoticons);
        update_option('visitor_display_emoticons', $visitor_display_emoticons);

        // Récupérer les options enregistrées
        $visitor_display_tabs = get_option('visitor_display_tabs', 1);
        $user_display_tabs = get_option('user_display_tabs', 1);
        $visitor_display_send_bar = get_option('visitor_display_send_bar', 1);
        $user_display_send_bar = get_option('user_display_send_bar', 1);
        $display_history = get_option('display_history', 1);
        $user_display_emoticons = get_option('user_display_emoticons', 1);
        $visitor_display_emoticons = get_option('visitor_display_emoticons', 1);
    }
?>
    <div class="wrap">
        <h1>Réglage du Tchat Amstrad</h1>
        <form method="post" action="options.php">
            <?php
            // Affichez directement les champs
            settings_fields('tchat_amstrad_settings');
            do_settings_sections('tchat_amstrad_settings');

            // Ajoutez ici les champs pour vos réglages
            ?>

            <div id="display-options" class="display-tchat__options">
                <h2>Affichage du Tchat</h2>
                <div class="display-tchat__options--user">
                    <label for="user_display_tchat">
                        <input type="checkbox" id="user_display_tchat" name="user_display_tchat" value="1" <?php checked(1, get_option('user_display_tchat'), true); ?> />
                        Affichage du Tchat pour les utilisateurs
                    </label><br>
                </div>

                <div class="display-tchat__options--visitor">
                    <label for="visitor_display_tchat">
                        <input type="checkbox" id="visitor_display_tchat" name="visitor_display_tchat" value="1" <?php checked(1, get_option('visitor_display_tchat'), true); ?> />
                        Affichage du Tchat pour les visiteurs
                    </label><br>
                </div>
            </div>

            <div class="display-tabs__options">
                <div class="display-tabs__options--user">
                    <h2>Affichage des onglets</h2>
                    <label for="user_display_tabs">
                        <input type="checkbox" id="user_display_tabs" name="user_display_tabs" value="1" <?php checked(1, get_option('user_display_tabs'), true); ?> />
                        Affichage des onglets pour les utilisateurs
                    </label><br>
                </div>
                <div class="display-tabs__options--visitor">
                    <label for="visitor_display_tabs">
                        <input type="checkbox" id="visitor_display_tabs" name="visitor_display_tabs" value="1" <?php checked(1, get_option('visitor_display_tabs'), true); ?> />
                        Affichage des onglets pour les visiteurs
                    </label><br>
                </div>
            </div>

            <div class="display-send-bar__options">
                <h2>Affichage de la barre d'envoi</h2>
                <div class="display-send-bar__options--user">
                    <label for="user_display_send_bar">
                        <input type="checkbox" id="user_display_send_bar" name="user_display_send_bar" value="1" <?php checked(1, get_option('user_display_send_bar'), true); ?> />
                        Affichage de la barre d'envoi pour les utilisateurs
                    </label><br>
                </div>

                <div class="display-send-bar__options--visitor">
                    <label for="visitor_display_send_bar">
                        <input type="checkbox" id="visitor_display_send_bar" name="visitor_display_send_bar" value="1" <?php checked(1, get_option('visitor_display_send_bar'), true); ?> />
                        Affichage de la barre d'envoi pour les visiteurs
                    </label><br>
                </div>
            </div>

            <div class="display-emoticons__options">
                <h2>Affichage des emoticons</h2>
                <div class="display-emoticons__options--user">
                    <label for="user_display_emoticons">
                        <input type="checkbox" id="user_display_emoticons" name="user_display_emoticons" value="1" <?php checked(1, get_option('user_display_emoticons'), true); ?> />
                        Affichage des emoticons pour les utilisateurs enregistrés
                    </label><br>

                </div>
                <div class="display-emoticons__options--visitor">
                    <label for="visitor_display_emoticons">
                        <input type="checkbox" id="visitor_display_emoticons" name="visitor_display_emoticons" value="1" <?php checked(1, get_option('visitor_display_emoticons'), true); ?> />
                        Affichage des emoticons pour les visiteurs
                    </label><br>
                </div>

                <label for="display_history">
                    <input type="checkbox" id="display_history" name="display_history" value="1" <?php checked(1, get_option('display_history'), true); ?> />
                    Affichage de l'historique
                </label><br>

                <?php
                submit_button();
                ?>
        </form>
    </div>
<?php
}


// ADMINISTRATION  ---> Enregistrement des options
function tchat_amstrad_register_settings()
{
    register_setting('tchat_amstrad_settings', 'visitor_display_tchat');
    register_setting('tchat_amstrad_settings', 'visitor_display_tabs');
    register_setting('tchat_amstrad_settings', 'visitor_display_send_bar');
    register_setting('tchat_amstrad_settings', 'user_display_tchat');
    register_setting('tchat_amstrad_settings', 'user_display_tabs');
    register_setting('tchat_amstrad_settings', 'user_display_send_bar');
    register_setting('tchat_amstrad_settings', 'display_history');
    register_setting('tchat_amstrad_settings', 'user_display_emoticons');
    register_setting('tchat_amstrad_settings', 'visitor_display_emoticons');
}
add_action('admin_init', 'tchat_amstrad_register_settings');





/**
 * function to display colors in the select input
 *
 * @param [type] $isAnonymous
 * @param [type] $isUser
 * @return void
 */
function tchat_amstrad_display_control_bar($isAnonymous, $isUser)
{ ?>
    <div class="tchatamstrad__controlbar">
        <?php
        if ($isAnonymous && !is_user_logged_in()) {
            // Afficher les controle pour les visiteur connectés
        ?>
            <div class="tchatamstrad__controlbar--pen">

            </div>
            <div class="tchatamstrad__controlbar--paper">

            </div>
            <div class="tchatamstrad__controlbar--buttons">

                <label for="tchatamstrad__controlbar--sound">
                    <button id="tchatamstrad__controlbar--sound" class="tchatamstrad__controlbar--button">Notification sonore</button>
                </label>
                <label for="tchatamstrad__controlbar--color">
                    <button id="tchatamstrad__controlbar--color" class="tchatamstrad__controlbar--button">Couleur Pseudo</button>
                </label>
                <label for="tchatamstrad__controlbar--mode">
                    <button id="tchatamstrad__controlbar--mode" class="tchatamstrad__controlbar--button">Thème sombre</button>
                </label>
                <label for="tchatamstrad__controlbar--hour">
                    <button id="tchatamstrad__controlbar--hour" class="tchatamstrad__controlbar--button active">Heure</button>
                </label>
            </div>
            <div>
                <audio id="message-sound" src="<?php echo plugin_dir_url(__FILE__); ?>assets/sounds/message.mp3" preload="auto"></audio>
            </div>
        <?php
        }

        if ($isUser && is_user_logged_in()) {
            // Afficher les controle pour les visiteur connectés
        ?>
            <div class="tchatamstrad__controlbar--pen">
                <label for="tchatamstrad__controlbar--pen">
                    Pen :
                    <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/flacon_cpc_noir.png" alt="">
                    <select name="pen" id="tchatamstrad__controlbar--pen" class="pen-color">
                        <option data-set-color="#000000" value="">Encre pen</option>
                        <option data-set-color="#000201" value="0">Noir</option>
                        <option data-set-color="#00026B" value="1">Bleu</option>
                        <option data-set-color="#0C02F4" value="2">Bleu Vif</option>
                        <option data-set-color="#6C0201" value="3">Rouge</option>
                        <option data-set-color="#690268" value="4">Magenta</option>
                        <option data-set-color="#6C02F2" value="5">Mauve</option>
                        <option data-set-color="#F30506" value="6">Rouge Vif</option>
                        <option data-set-color="#F00268" value="7">Pourpre</option>
                        <option data-set-color="#F302F4" value="8">Magenta Vif</option>
                        <option data-set-color="#027801" value="9">Vert</option>
                        <option data-set-color="#007868" value="10">Turquoise</option>
                        <option data-set-color="#0C7BF4" value="11">Bleu Ciel</option>
                        <option data-set-color="#6E7B01" value="12">Jaune</option>
                        <option data-set-color="#6E7D6B" value="13">Blanc</option>
                        <option data-set-color="#6E7BF6" value="14">Bleu Pastel</option>
                        <option data-set-color="#F37D0D" value="15">Orange</option>
                        <option data-set-color="#F37D6B" value="16">Rose</option>
                        <option data-set-color="#FA80F9" value="17">Magenta Pastel</option>
                        <option data-set-color="#02F001" value="18">Vert Vif</option>
                        <option data-set-color="#00F36B" value="19">Vert Marin</option>
                        <option data-set-color="#0FF3F2" value="20">Turquoise Vif</option>
                        <option data-set-color="#71F504" value="21">Vert Citron</option>
                        <option data-set-color="#71F36B" value="22">Vert Pastel</option>
                        <option data-set-color="#71F3F4" value="23">Turquoise Pastel</option>
                        <option data-set-color="#F3F30D" value="24">Jaune Vif</option>
                        <option data-set-color="#F3F36D" value="25">Jaune Pastel</option>
                        <option data-set-color="#FFF3F9" value="26">Blanc Brillant</option>
                    </select>
                </label>
            </div>
            <div class="tchatamstrad__controlbar--paper">
                <label for="tchatamstrad__controlbar--paper">
                    Paper :
                    <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/img/flacon_cpc_noir.png" alt="">
                    <select name="paper" id="tchatamstrad__controlbar--paper" class="paper-color">
                    <option data-set-color="#000000" value="">Encre paper</option>
                        <option data-set-color="#000201" value="0">Noir</option>
                        <option data-set-color="#00026B" value="1">Bleu</option>
                        <option data-set-color="#0C02F4" value="2">Bleu Vif</option>
                        <option data-set-color="#6C0201" value="3">Rouge</option>
                        <option data-set-color="#690268" value="4">Magenta</option>
                        <option data-set-color="#6C02F2" value="5">Mauve</option>
                        <option data-set-color="#F30506" value="6">Rouge Vif</option>
                        <option data-set-color="#F00268" value="7">Pourpre</option>
                        <option data-set-color="#F302F4" value="8">Magenta Vif</option>
                        <option data-set-color="#027801" value="9">Vert</option>
                        <option data-set-color="#007868" value="10">Turquoise</option>
                        <option data-set-color="#0C7BF4" value="11">Bleu Ciel</option>
                        <option data-set-color="#6E7B01" value="12">Jaune</option>
                        <option data-set-color="#6E7D6B" value="13">Blanc</option>
                        <option data-set-color="#6E7BF6" value="14">Bleu Pastel</option>
                        <option data-set-color="#F37D0D" value="15">Orange</option>
                        <option data-set-color="#F37D6B" value="16">Rose</option>
                        <option data-set-color="#FA80F9" value="17">Magenta Pastel</option>
                        <option data-set-color="#02F001" value="18">Vert Vif</option>
                        <option data-set-color="#00F36B" value="19">Vert Marin</option>
                        <option data-set-color="#0FF3F2" value="20">Turquoise Vif</option>
                        <option data-set-color="#71F504" value="21">Vert Citron</option>
                        <option data-set-color="#71F36B" value="22">Vert Pastel</option>
                        <option data-set-color="#71F3F4" value="23">Turquoise Pastel</option>
                        <option data-set-color="#F3F30D" value="24">Jaune Vif</option>
                        <option data-set-color="#F3F36D" value="25">Jaune Pastel</option>
                        <option data-set-color="#FFF3F9" value="26">Blanc Brillant</option>
                    </select>
                </label>
            </div>
            <div class="tchatamstrad__controlbar--buttons">

                <label for="tchatamstrad__controlbar--sound">
                    <button id="tchatamstrad__controlbar--sound" class="tchatamstrad__controlbar--button">Notification sonore</button>
                </label>
                <label for="tchatamstrad__controlbar--color">
                    <button id="tchatamstrad__controlbar--color" class="tchatamstrad__controlbar--button">Couleur Pseudo</button>
                </label>
                <label for="tchatamstrad__controlbar--mode">
                    <button id="tchatamstrad__controlbar--mode" class="tchatamstrad__controlbar--button">Thème sombre</button>
                </label>
                <label for="tchatamstrad__controlbar--hour">
                    <button id="tchatamstrad__controlbar--hour" class="tchatamstrad__controlbar--button active">Heure</button>
                </label>
            </div>
            <div>
             
<audio id="message-sound" src="<?php echo plugin_dir_url(__FILE__); ?>assets/sounds/message.mp3" preload="auto"></audio>

            </div>
        <?php
        }
        ?>
    </div>
<?php }


/**
 * Affichage de la fenêtre des utilisateurs connectés, de la fenêtre de discussion et du formulaire d'envoi de message
 *
 * @return void
 */
function tchat_amstrad_display_windows($isAnonymous, $isUser)
{
    $current_user = wp_get_current_user();
    $wp_username = $current_user->user_login; ?>



    <div class="tchatamstrad__windowscontainer">
        <div id="chat" class="tchatamstrad__windows--left">


            <?php
            global $old_messages;
            if ($old_messages) {
                foreach ($old_messages as $message) {
                    echo '<div class="history">';
                    echo '<div class="timestamp">' . $message->timestamp . '</div>';
                    echo '<div class="username">' . $message->user . '</div>';
                    echo '<div class="message">' . $message->message . '</div></div>';
                }
            }
            ?>

            <?php
            global $daily_messages;


            if ($daily_messages) {
                foreach ($daily_messages as $message) {
                    $timestamp = time();
                    $message_key = $message->user . "_" . $timestamp;
                    $pen = $message->pen;
                    $paper = $message->paper;
                    $dateHour = $message->timestamp;
                    $hourString = strtotime($dateHour);
                    $hour = date('H:i:s', $hourString);
            
                    echo '<div class="message__container" style="background-color: ' . $paper . ';" data-message-key="' . $message_key . '" data-username="' . $message->user . '">';
                    echo '<div class="delete-icon">';
                    echo '<img src="' . plugin_dir_url(__FILE__) . 'assets/img/icon-croco.png' . '" alt=""></div>';
                    echo '<div style="color: ' . $pen . ';" class="timestamp">' . $hour . '</div>';
                    echo '<div style="color: ' . $pen . ';" class="username">' . $message->user . '</div>';
                    echo '<div style="color: ' . $pen . ';" class="message">' . $message->message . '</div></div>';
                }
            }
            ?>
        </div>
        <div class="tchatamstrad__windows--right">
            <div id="emoticons-overlay" class="emoticons-overlay hidden">
                <ul class="emoticons__list">
                    <?php
                    // Chemin vers le fichier JSON
                    $json_file = plugin_dir_path(__FILE__) . 'emoticons.json';

                    // Lire le contenu du fichier JSON
                    $json_data = file_get_contents($json_file);

                    // Convertir le JSON en tableau associatif
                    $emoteArray = json_decode($json_data, true);

                    // Parcourir le tableau associatif et afficher les émoticônes dans des balises <li>
                    foreach ($emoteArray['emotions'] as $emoticon) {
                        echo '<li class="emoticon">' . $emoticon . '</li>';
                    }
                    ?>
                </ul>
            </div>


            <ul id="user-list" class="users__list">
                <li>
                    <img src="<?= plugin_dir_url(__FILE__) . 'assets/img/icon_general2.png' ?>" alt="">
                    MissX
                </li>
                <li>
                    <img src="<?= plugin_dir_url(__FILE__) . 'assets/img/icon_general2.png' ?>" alt="">
                    Allan
                </li>
                <li>
                    <img src="<?= plugin_dir_url(__FILE__) . 'assets/img/icon_general2.png' ?>" alt="">
                    Amélie Minuit
                </li>
                <?php

                // Récupère tous les utilisateurs connectés et modifie leur statut 'currently_logged_in' à '1'
                $users = get_users(array(
                    'meta_key' => 'currently_logged_in',
                    'meta_value' => '1'
                ));
                // ensuite on boucle sur les utilisateurs récupérés pour ne prendre que ceux qui ont le statut 'currently_logged_in' à '1'
                foreach ($users as $user) {
                    echo '<li id="' . $user->ID . '">'
                        . '<img src="' . plugin_dir_url(__FILE__) . 'assets/img/icon_general2.png' . '" alt="">'
                        . $user->user_login . '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <form id="tchatamstrad-form" method="post">

        <?php if (($isAnonymous || is_user_logged_in()) && $isUser) { ?>

            <div class="tchatamstrad__windows--bottom">
                <div class="tchatamstrad__inputbox">
                    <label for="tchatamstrad-message" class="tchatamstrad__label">
                        <input id="tchatamstrad-message" class="tchatamstrad__input" type="text" name="message" placeholder="Tapez votre message ici..." maxlength="1024">
                    </label>
                </div>

                <div class="tchatamstrad__sendbox">
                    <div id="emoticon" class="tchatamstrad__image">
                        <?php

                        $user_display_emoticons = get_option('user_display_emoticons', 1);
                        $visitor_display_emoticons = get_option('visitor_display_emoticons', 1);

                        tchat_amstrad_display_emoticons($visitor_display_emoticons, $user_display_emoticons);
                        ?>
                    </div>
                    <div class="tchatamstrad__button">
                        <input type="submit" value="Send">
                    </div>
                </div>
            </div>

        <?php } ?>

    </form>
<?php   }


/**
 * header bar tabs display
 *
 * @return void
 */
function tchat_amstrad_display_tabs_bar($isAnonymous, $isUser)
{ ?>
    <div class="tchatamstrad__tabsbarcontainer">
        <?php
        tchat_amstrad_display_tab('Général', 'general', plugin_dir_url(__FILE__) . 'assets/img/icon_general2.png');

        if ($isAnonymous && !is_user_logged_in()) {
            // Afficher les onglets pour les visiteur connectés
            tchat_amstrad_display_tab('?', 'help');
        }

        if ($isUser && is_user_logged_in()) {
            // Afficher les onglets pour les utilisateurs connectés
            tchat_amstrad_display_tab('Laboratoire', 'laboratoire', plugin_dir_url(__FILE__) . 'assets/img/images_cpc11.png');
            tchat_amstrad_display_tab('Salon', 'salon', plugin_dir_url(__FILE__) . 'assets/img/icon_s2.png');
            tchat_amstrad_display_tab('Chambre Bleue', 'chambre-bleue', plugin_dir_url(__FILE__) . 'assets/img/icon_chb2.png');
            tchat_amstrad_display_tab('Privé', 'private');
            tchat_amstrad_display_tab('?', 'help');
        }
        ?>
    </div>
<?php
}


/**
 * display a single tab for the tchat
 *
 * @param string $tabName
 * @param string $tabClass
 * @param string $tabUrl
 * @return void
 */
function tchat_amstrad_display_tab(string $tabName, string $tabClass, string $tabUrl = null)
{ ?>
    <div class="tchatamstrad__tabcontainer--<?= $tabClass ?> tchatamstrad__tab">
        <div class="tchatamstrad__tabcontent--<?= $tabClass ?> tchatamstrad__content">
            <p><?= $tabName ?></p>
            <?php if ($tabUrl) { ?>
                <div class="tchatamstrad__tabimg">
                    <img src="<?= $tabUrl ?>">
                </div>
            <?php } ?>
        </div>
    </div>
<?php
}


/**
 * Emoticon menu display
 *
 * @return void
 */
function tchat_amstrad_display_emoticons($isAnonymous, $isUser)
{ ?>
    <?php

    if ($isAnonymous && !is_user_logged_in()) {
        // Afficher les onglets pour les visiteur connectés
        echo '<img src="' . plugin_dir_url(__FILE__) . 'assets/img/chat-smiles.png" alt="emote menu">';
    }

    if ($isUser && is_user_logged_in()) {
        // Afficher les onglets pour les utilisateurs connectés
        echo '<img src="' . plugin_dir_url(__FILE__) . 'assets/img/chat-smiles.png" alt="emote menu">';
    }
    ?>

    <?php
}



/**
 * utilise les variable ADMINISTRATION  ---> Fonction pricipale qui affiche le tchat avec une verification de connexion de l'utilisateur
 *
 * @return void
 */

function displayTchat($isAnonymous, $isUser)
{
    // Vérifier si l'utilisateur est connecté, si la fonction renvoie true alors on affiche le tchat
    if (($isAnonymous || is_user_logged_in()) && $isUser) {

        // Récupérer les options enregistrées
        $visitor_display_tabs = get_option('visitor_display_tabs', 1);
        $user_display_tabs = get_option('user_display_tabs', 1);
        $visitor_display_send_bar = get_option('visitor_display_send_bar', 1);
        $user_display_send_bar = get_option('user_display_send_bar', 1);
        //  $display_history = get_option('display_history', 1);

        global $daily_messages;
        $daily_messages = get_daily_messages();



        // global $old_messages;
        // $old_messages = get_last_10_messages($display_history);



    ?>
        <div class="tchatamstrad__mainwindows">
            <div class="tchatamstrad__controls">
        <?php
        tchat_amstrad_display_control_bar($isAnonymous, $isUser);
        // tchat_amstrad_display_control_bar($visitor_display_controls, $user_display_controls);
        echo '</div>';
        tchat_amstrad_display_tabs_bar($visitor_display_tabs, $user_display_tabs);
        tchat_amstrad_display_windows($visitor_display_send_bar, $user_display_send_bar);
        echo '</div>';


        // // Nouvelle option pour l'historique
        // if ($display_history) {
        //     echo '<div class="tchatamstrad__history">';

        //     // Ajouter ici le code pour afficher l'historique
        //     get_last_10_messages($display_history);
        //     echo '</div>';
        // }



        // $daily_messages = get_daily_messages();

        // // Vérifier si des messages sont présents
        // if (!empty($daily_messages)) {
        //     // Boucler à travers chaque message
        //     echo '<div class="tchatamstrad__daily">';

        //     foreach ($daily_messages as $message) {
        //         echo 'ID: ' . $message->id . '<br>';
        //         echo 'Utilisateur: ' . $message->user . '<br>';
        //         echo 'Timestamp: ' . $message->timestamp . '<br>';
        //         echo 'Message: ' . $message->message . '<br>';
        //         echo 'Clé de message: ' . $message->message_key . '<br><br>';
        //     }
        //     echo '</div>';
        // } else {
        //     echo 'Aucun message trouvé.';
        // }
    }
}


// Hook d'action pour afficher ou masquer le tchat dans le footer
add_action('wp_footer', function () {

    displayTchat(get_option('visitor_display_tchat', 1), get_option('user_display_tchat', 1));
});
