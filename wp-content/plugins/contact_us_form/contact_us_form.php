<?php
/**
 * Plugin Name: Contact Us
 * Description: Easy to add and display contact us form
 * Author: Custom
 * Version: 1.3.3
 * @package WordPress
 * @author WP OnlineSupport
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( is_admin() ) {
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }
    require_once 'contact_us_admin_list.php';
}
function contact_us_load_textdomain() {
    load_theme_textdomain('tauro', get_template_directory() . '/lang');
}
add_action('plugins_loaded', 'contact_us_load_textdomain');

function contacts_form_shortcode_func() {
    $form='<form class="contact-form row" method="post" action="'.admin_url('admin-ajax.php').'" novalidate="">
        <fieldset class="form-group col-md-7">
            <input id="name" name="name" type="text" placeholder="'.__('Name', 'tauro') .'" required="">
            <label for="name"></label>
        </fieldset>
         <fieldset class="form-group col-md-7">
            <input id="phone" name="phone" type="text" placeholder="'.__('Phone', 'tauro') .'">
            <label for="phone"></label>
        </fieldset>
        <fieldset class="form-group col-md-7">
            <input id="email" name="email" type="email" placeholder="'.__('Email', 'tauro').'" required="">
            <label for="email"></label>
        </fieldset>
        <fieldset class="form-group col-md-7">
            <input id="subject" name="subject" type="text" placeholder="'.__('Theme', 'tauro').'" class="">
            <label for="subject"></label>
        </fieldset>
        <fieldset class="form-group col-md-12">
            <textarea id="message" name="message" placeholder="'.__('Message', 'tauro') .'" required="" class="" spellcheck="false"></textarea>
         
            <label for="message"></label>
            <input type="submit" class="btn btn-normal btn-md" value="'.__('Send', 'tauro').'"> 
                <input type="hidden" name="action" value="contact_form_action">
                '.wp_nonce_field( 'contact_form_action_nonce', 'name_of_nonce_field' ).'
        </fieldset>

        <div class="info col-md-12 contact_form_alert" style="display: none">
            <div class="alert alert-danger ">
                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">
                <i class="fa fa-remove"></i></button>
                <i class="fa fa-exclamation"></i> <p>'.__('An error occurred - email not sent successfully.', 'tauro').'</p>
            </div>
        </div>
    </form>';
    return $form;
}
function contacts_form_popup_shortcode_func(){
    $form='<div class="phoneWrapper" id="fixed_phone" data-toggle="modal" data-target="#contact_us_form">
    <div class="phoneIcon">
        <i class="fa fa-phone"></i>
    </div>
</div>

<div class="modal fade" id="contact_us_form" role="dialog">
    <div class="modal-dialog">

        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">'. __('Close', 'tauro').' <i class="fa fa-close"></i></button>
                '.get_custom_logo().'
            </div>
            <div class="modal-body">
                       <form class="contact-form-popup row form-inline" method="post" action="'.admin_url('admin-ajax.php').'" novalidate="">
                       <div class="form-row">
                        <fieldset class="form-group col-md-6">
                            <input id="name" name="name" type="text" class="form-control" placeholder="'.__('Name', 'tauro') .'" required="">
                            <label for="name"></label>
                        </fieldset>
                        <fieldset class="form-group col-md-6">
                            <input id="phone" name="phone" class="form-control" type="text" placeholder="'.__('Phone', 'tauro').'" required="">
                            <label for="phone"></label>
                        </fieldset>
                        </div>
                        <div class="form-row">
                        <fieldset class="form-group col-md-12">
                            <textarea id="message" name="message"  placeholder="'.__('Message', 'tauro') .'"  spellcheck="false"></textarea>
                           
                            
                            <label for="message"></label>							
                            <input type="submit" class="btn btn-normal btn-md" value="'.__('Call Me', 'tauro').'"> 
                                <input type="hidden" name="action" value="contact_form_popup_action">
                                '.wp_nonce_field( 'contact_form_action_nonce', 'name_of_nonce_field' ).'
                        </fieldset>
                        </div>
                        <div class="info col-md-12 contact_form_popup_alert" style="display: none">
                            <div class="alert alert-danger ">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">
                                <i class="fa fa-remove"></i></button>
                                <i class="fa fa-exclamation"></i> <p>'.__('An error occurred - email not sent seccessfuly.', 'tauro').'</p>
                            </div>
                        </div>
                    </form>
            </div>
        </div>

    </div>
</div>
';
    return $form;
}
function contacts_form_section_shortcode_func(){
    $form='<form class="contact-form-section row form-inline" method="post" action="'.admin_url('admin-ajax.php').'" novalidate="">
                       <div class="form-row">
                        <fieldset class="form-group col-md-4">
                            <input id="name" name="name" type="text" class="form-control" placeholder="'.__('Name', 'tauro') .'" required="">
                            <label for="name"></label>
                        </fieldset>
                        <fieldset class="form-group col-md-4">
                            <input id="phone" name="phone" class="form-control" type="text" placeholder="'.__('Phone', 'tauro').'" required="">
                            <label for="phone"></label>
                        </fieldset>
                        <fieldset class="form-group col-md-4">
                            <input type="submit" class="btn btn-normal btn-md" value="'.__('Call Me', 'tauro').'"> 
                                <input type="hidden" name="action" value="contact_form_section_action">
                                '.wp_nonce_field( 'contact_form_action_nonce', 'name_of_nonce_field' ).'
                        </fieldset>
                        </div>
                        <div class="info col-md-12 contact_form_section_alert" style="display: none">
                            <div class="alert alert-danger ">
                                <button aria-hidden="true" data-dismiss="alert" class="close" type="button">
                                <i class="fa fa-remove"></i></button>
                                <i class="fa fa-exclamation"></i> <p>'.__('An error occurred - email not sent seccessfuly.', 'tauro').'</p>
                            </div>
                        </div>
                    </form>';
    return $form;
}

add_action( 'wp_ajax_contact_form_action', 'contact_form_action' );
add_action( 'wp_ajax_nopriv_contact_form_action', 'contact_form_action' );
function contact_form_action() {
    global $wpdb;
    $response = array(
        'error' => false,
    );
    if (
        ! isset( $_POST['name_of_nonce_field'] )
        || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'contact_form_action_nonce')
    ) {
        $response['error'] = true;
        $response['error_message'] = __('The form is not valid', 'tauro');
        exit(json_encode($response));
    }

    if (trim($_POST['email']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Email is required', 'tauro');
        exit(json_encode($response));
    }
    if (trim($_POST['name']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Name is required', 'tauro');
        exit(json_encode($response));
    }
    if (trim($_POST['message']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Message is required', 'tauro');
        exit(json_encode($response));
    }
    $name   = ($_POST['name'])?esc_sql( $_POST['name'] ):'';
    $email   = ($_POST['email'])?esc_sql( $_POST['email'] ):'';
    $msg   = ($_POST['message'])?esc_sql( $_POST['message'] ):'';
    $subject   = ($_POST['subject'])?esc_sql( $_POST['subject'] ):'';
    $phone   = ($_POST['phone'])?esc_sql( $_POST['phone'] ):'';

    $checkexistcontacts = $wpdb->get_var($wpdb->prepare( 'SELECT * FROM tbl_contacts WHERE `name` = %s AND email = %s AND subject= %s', $name, $email,$subject ));
    if(!$checkexistcontacts){
        $wpdb->query(
            $wpdb->prepare(
                "
   INSERT INTO tbl_contacts
   ( `name`, email, subject,msg,phone,datetime )
   VALUES ( %s, %s, %s, %s, %s, %s )
   ",
                array(
                    $name,
                    $email,
                    $subject,
                    $msg,
                    $phone,
                    date('Y-m-d H:i:s'),

                )
            )
        );

        $html = __('Name', 'tauro')." :  ".$name."<br>";
        $html .= __('Phone', 'tauro')." :  ".$phone."<br>";
        $html .= __('Email', 'tauro')." :  ".$email."<br>";
        $html .= __('Theme', 'tauro')." :  ".$subject."<br>";
        $html .= __('Message', 'tauro')." :  ".$msg."<br>";
        
        wp_mail( get_option( 'admin_email' ), $subject, $html );
        $response['error'] = false;
        $response['success'] = __('Form Sent Successfull', 'tauro');
        exit(json_encode($response));

    }else{
        $response['error'] = true;
        $response['error_message'] = __('The Form Has Been Already Sent', 'tauro');
        exit(json_encode($response));
    }

}

add_action( 'wp_ajax_contact_form_popup_action', 'contact_form_popup_action' );
add_action( 'wp_ajax_nopriv_contact_form_popup_action', 'contact_form_popup_action' );
function contact_form_popup_action() {
    global $wpdb;
    $response = array(
        'error' => false,
    );
    if (
        ! isset( $_POST['name_of_nonce_field'] )
        || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'contact_form_action_nonce')
    ) {
        $response['error'] = true;
        $response['error_message'] = __('The form is not valid', 'tauro');
        exit(json_encode($response));
    }

    if (trim($_POST['phone']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Phone is required', 'tauro');
        exit(json_encode($response));
    }
    if (trim($_POST['name']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Name is required', 'tauro');
        exit(json_encode($response));
    }

    $name   = ($_POST['name'])?esc_sql( $_POST['name'] ):'';
    $phone   = ($_POST['phone'])?esc_sql( $_POST['phone'] ):'';
    $msg   = ($_POST['message'])?esc_sql( $_POST['message'] ):'';
    $subject = __('New Call Back Request', 'tauro');
    $checkexistcontacts = $wpdb->get_var($wpdb->prepare( 'SELECT * FROM tbl_contacts WHERE `name` = %s AND phone = %s ', $name, $phone ));
    if(!$checkexistcontacts){
        $wpdb->query(
            $wpdb->prepare(
                "
   INSERT INTO tbl_contacts
   ( `name`, phone,msg,datetime )
   VALUES ( %s, %s, %s, %s )
   ",
                array(
                    $name,
                    $phone,
                    $msg,
                    date('Y-m-d H:i:s'),

                )
            )
        );
$html = __('Name', 'tauro')." :  ".$name."<br>";
$html .= __('Phone', 'tauro')." :  ".$phone."<br>";
$html .= __('Message', 'tauro')." :  ".$msg."<br>";


         wp_mail( get_option( 'admin_email' ), $subject, $html );
        
        $response['error'] = false;
        $response['success'] = __('Form Sent Successfull', 'tauro');
        exit(json_encode($response));
    }else{
        $response['error'] = true;
        $response['error_message'] = __('The Form Has Been Already Sent', 'tauro');
        exit(json_encode($response));
    }

}



add_action( 'wp_ajax_contact_form_section_action', 'contact_form_section_action' );
add_action( 'wp_ajax_nopriv_contact_form_section_action', 'contact_form_section_action' );
function contact_form_section_action() {
    global $wpdb;
    $response = array(
        'error' => false,
    );
    if (
        ! isset( $_POST['name_of_nonce_field'] )
        || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'contact_form_action_nonce')
    ) {
        $response['error'] = true;
        $response['error_message'] = __('The form is not valid', 'tauro');
        exit(json_encode($response));
    }

    if (trim($_POST['phone']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Phone is required', 'tauro');
        exit(json_encode($response));
    }
    if (trim($_POST['name']) == '') {
        $response['error'] = true;
        $response['error_message'] = __('Name is required', 'tauro');
        exit(json_encode($response));
    }

    $name   = ($_POST['name'])?esc_sql( $_POST['name'] ):'';
    $phone   = ($_POST['phone'])?esc_sql( $_POST['phone'] ):'';
    $subject = __('New Call Back Request', 'tauro');
    $checkexistcontacts = $wpdb->get_var($wpdb->prepare( 'SELECT * FROM tbl_contacts WHERE `name` = %s AND phone = %s ', $name, $phone ));
    if(!$checkexistcontacts){
        $wpdb->query(
            $wpdb->prepare(
                "
   INSERT INTO tbl_contacts
   ( `name`, phone,datetime )
   VALUES ( %s, %s, %s )
   ",
                array(
                    $name,
                    $phone,
                    date('Y-m-d H:i:s'),

                )
            )
        );
        
        $html = __('Name', 'tauro')." :  ".$name."<br>";
        $html .= __('Phone', 'tauro')." :  ".$phone."<br>";
        wp_mail( get_option( 'admin_email' ), $subject, $html );
        $response['error'] = false;
        $response['success'] = __('Form Sent Successfull', 'tauro');
        exit(json_encode($response));
    }else{
        $response['error'] = true;
        $response['error_message'] = __('The Form Has Been Already Sent', 'tauro');
        exit(json_encode($response));
    }

}



add_action( 'init', 'wpdocs_add_custom_shortcode' );
function wpdocs_add_custom_shortcode() {
    add_shortcode( 'contacts_form', 'contacts_form_shortcode_func' );
    add_shortcode( 'contacts_form_popup', 'contacts_form_popup_shortcode_func' );
    add_shortcode( 'contacts_form_section', 'contacts_form_section_shortcode_func' );
}
?>
