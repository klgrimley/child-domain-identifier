<?php
/*
Plugin Name: Child Domain Identifier
Plugin URI: https://github.com/klgrimley/child-domain-identifier
Description: Identifies which domain of a multisite install is currently active
Version: 1.0
Author: Kevin Grimley
Credit: modified from tutsplus
Author URI: kevingrimley.com
*/

include 'multisite-query.php';
define('SIMPLE_ANNOUNCEMENTS_PATH', plugin_dir_url( __FILE__ ));

function sap_register_announcements() {
 
    $labels = array(
        'name' => _x( 'Domain Indentification', 'post type general name' ),
        'singular_name' => _x( 'Announcement', 'post type singular name' ),
        'add_new' => _x( 'Add New', 'Announcement' ),
        'add_new_item' => __( 'Add New Announcement' ),
        'edit_item' => __( 'Edit Announcement' ),
        'new_item' => __( 'New Announcement' ),
        'view_item' => __( 'View Announcement' ),
        'search_items' => __( 'Search Announcements' ),
        'not_found' =>  __( 'No Announcements found' ),
        'not_found_in_trash' => __( 'No Announcements found in Trash' ),
        'parent_item_colon' => ''
    );
 
    $args = array(
        'labels' => $labels,
        'singular_label' => __('Announcement', 'simple-announcements'),
        'public' => true,
        'capability_type' => 'post',
        'rewrite' => false,
        'supports' => array('title', 'editor'),
    );
    register_post_type('announcements', $args);
}
add_action('init', 'sap_register_announcements');


add_action( 'admin_enqueue_scripts', 'mw_enqueue_color_picker' );
function mw_enqueue_color_picker( $hook_suffix ) {
    // first check that $hook_suffix is appropriate for your admin page
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'my-script-handle', plugins_url('my-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
}


function sap_add_metabox() {
    add_meta_box( 'sap_metabox_id', 'Site Indentification', 'sap_metabox', 'announcements', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sap_add_metabox' );

function sap_metabox( $post ) {
	
	$values = get_post_custom( $post->ID );
	
    $site_id = isset($values['sap_site_id']) ? esc_attr( $values['sap_site_id'][0] ) : '';
    $background_color = isset($values['sap_background_color']) ? esc_attr( $values['sap_background_color'][0] ) : '';
	$text_color = isset($values['sap_text_color']) ? esc_attr( $values['sap_text_color'][0] ) : '';
    wp_nonce_field( 'sap_metabox_nonce', 'metabox_nonce' );
	
        echo 'Location for this announcement: <b>'.$site_id.'</b><br><br>';
        echo '<br>values var_dump: ';
        var_dump($values);
    $blog_list = get_blog_list( 0, 'all' );
    
    $sites = array();
    foreach ($blog_list AS $blog) {
        $sites[] = $blog['domain'].$blog['path'];
    }; 
    ?>

    <select name="sap_site_id" id="sap_site_id">
        <?php
        foreach ($sites as $site) {
            echo '<option value="'. $site .'">'. $site . '</option>';
        }
        ?>
    </select>

    <p>Select Background Color</p>
    <input name="sap_background_color" type="text" value="<?php echo $background_color ?>" class="my-color-field" />
    <p>Select Text Color</p>
    <input name="sap_text_color" type="text" value="<?php echo $text_color ?>" class="my-color-field" />
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('.my-color-field').wpColorPicker();
        });
    </script>
    <?php
}

function sap_backend_scripts($hook) {
    global $post;
 
    if( ( !isset($post) || $post->post_type != 'announcements' ))
        return;
 
    wp_enqueue_style( 'jquery-ui-fresh', SIMPLE_ANNOUNCEMENTS_PATH . 'css/jquery-ui-fresh.css');
    wp_enqueue_script( 'announcements', SIMPLE_ANNOUNCEMENTS_PATH . 'js/announcements.js', array( 'jquery', 'jquery-ui-datepicker' ) );
}
add_action( 'admin_enqueue_scripts', 'sap_backend_scripts' );


function sap_metabox_save( $post_id ) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $post_id;
 
    if( !isset( $_POST['metabox_nonce'] ) || !wp_verify_nonce( $_POST['metabox_nonce'], 'sap_metabox_nonce' ) )
        return $post_id;
 
    if( !current_user_can( 'edit_post' ) )
        return $post_id;
 
    // Make sure data is set
    if( isset( $_POST['sap_site_id'] ) ) {

        $valid = 0;
        $old_value = get_post_meta($post_id, 'sap_site_id', true);

        if ( $_POST['sap_site_id'] != '' ) {

            $id = $_POST['sap_site_id'];
            $id = explode( '-', (string) $id );
            //$valid = checkdate($date[1],$date[2],$date[0]);
        }

        if ($id)
            update_post_meta( $post_id, 'sap_site_id', $_POST['sap_site_id'] );
        elseif (!$valid && $old_value)
            update_post_meta( $post_id, 'sap_site_id', $old_value );
        else
            update_post_meta( $post_id, 'sap_site_id', '');
    }
    if( isset( $_POST['sap_background_color'] ) ) {

        $valid = 0;
        $old_value = get_post_meta($post_id, 'sap_background_color', true);
        if ( $_POST['sap_background_color'] != '' ) {

            $id = $_POST['sap_background_color'];
            $id = explode( '-', (string) $id );
            //$valid = checkdate($date[1],$date[2],$date[0]);
        }

        if ($id)
            update_post_meta( $post_id, 'sap_background_color', $_POST['sap_background_color'] );
        elseif (!$valid && $old_value)
            update_post_meta( $post_id, 'sap_background_color', $old_value );
        else
            update_post_meta( $post_id, 'sap_background_color', '');
    }
    if( isset( $_POST['sap_text_color'] ) ) {

        $valid = 0;
        $old_value = get_post_meta($post_id, 'sap_text_color', true);
        if ( $_POST['sap_text_color'] != '' ) {

            $id = $_POST['sap_text_color'];
            $id = explode( '-', (string) $id );
            //$valid = checkdate($date[1],$date[2],$date[0]);
        }

        if ($id)
            update_post_meta( $post_id, 'sap_text_color', $_POST['sap_text_color'] );
        elseif (!$valid && $old_value)
            update_post_meta( $post_id, 'sap_text_color', $old_value );
        else
            update_post_meta( $post_id, 'sap_text_color', '');
    }
}
add_action( 'save_post', 'sap_metabox_save' );



function sap_filter_where( $where = '' ) {
    // ...where dates are blank
    $where .= " OR (mt1.meta_key = 'sap_site_id' AND CAST(mt1.meta_value AS CHAR) = '')";
    return $where;
}

function sap_background_filter_where( $where = '' ) {
    // ...where dates are blank
    $where .= " OR (mt1.meta_key = 'sap_background_color' AND CAST(mt1.meta_value AS CHAR) = '')";
    return $where;
}

function sap_text_filter_where( $where = '' ) {
    // ...where dates are blank
    $where .= " OR (mt1.meta_key = 'sap_text_color' AND CAST(mt1.meta_value AS CHAR) = '')";
    return $where;
}
 
function sap_display_announcement() {
 
    global $wpdb;
 
    $active_site = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $needed_link = current(explode('/', substr($active_site, 7))).'/';
    //$today = date('Y-m-d');
    $args = array(
        'post_type' => 'announcements',
        'posts_per_page' => 0,
        'meta_key' => 'sap_site_id',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'sap_site_id',
                'value' => $needed_link,
                'compare' => '=',
            )
        )
    );

    
 
    // Add a filter to do complex 'where' clauses...
    add_filter( 'posts_where', 'sap_filter_where' );
    $query = new WP_Query_Multisite( $args );
    // Take the filter away again so this doesn't apply to all queries.
    remove_filter( 'posts_where', 'sap_filter_where' );

    $announcements = $query->posts;

    //Query setup for background color
    foreach ($announcements as $announcement) {
        $post_id = $announcement->ID;
    }
    
    $background_query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'sap_background_color' ";
    $display_background = $wpdb->get_results($background_query, OBJECT);

    foreach ( $display_background as $item ){ 
        $background_color = $item->meta_value; 
    } 

    $text_query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'sap_text_color' ";
    $display_text = $wpdb->get_results($text_query, OBJECT);

    foreach ( $display_text as $item ){ 
        $text_color = $item->meta_value; 
    } 
    echo '<br>';
    if($announcements) :
        ?>
        <br>
        <div id="announcements" class="hidden" style="background:<?php echo $background_color ?>">
            <div class="wrapper">
                <div class="sap_message">
                    <?php
                    foreach ($announcements as $announcement) {
                        echo '<p style="color:'.$text_color.' !important">'.$announcement->post_content.'</p>'; 
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    endif;
}
add_action('wp_footer', 'sap_display_announcement');

function sap_frontend_scripts() {
    wp_enqueue_style( 'announcements-style', SIMPLE_ANNOUNCEMENTS_PATH . 'css/announcements.css');
    wp_enqueue_script( 'announcements', SIMPLE_ANNOUNCEMENTS_PATH . 'js/announcements.js', array( 'jquery' ) );
    wp_enqueue_script( 'cookies', SIMPLE_ANNOUNCEMENTS_PATH . 'js/jquery.cookie.js', array( 'jquery' ) );
    wp_enqueue_script( 'cycle', SIMPLE_ANNOUNCEMENTS_PATH . 'js/jquery.cycle.min.js', array( 'jquery' ) );
}
add_action('wp_enqueue_scripts', 'sap_frontend_scripts');





?>