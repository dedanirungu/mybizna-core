<?php

/**
 * Mybizna Core
 *
 * @package           MybiznaCore
 * @author            Dedan Irungu
 * @copyright         2022 Mybizna.com
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Mybizna Core
 * Plugin URI:        https://wordpress.org/plugins/mybizna-core/
 * Description:       This is the base plugin for implementing a erp level intragration into wordpress and allow any post to be sellable.
 * Version:           1.0.0
 * Requires at least: 5.4
 * Requires PHP:      7.2
 * Author:            Dedan Irungu
 * Author URI:        https://mybizna.com
 * Text Domain:       mybizna-core
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 *http://127.0.0.1/php/wordpresserp/?add-invoice=1&first_name=Dedan&last_name=Irungu&phone=0713034569&email=dedanirungu%40gmail.com&country=Kenya&city=Nairobi&state=Nairobi&zip=00618&address=767&cart_details[0][id]=203&cart_details[0][quantity]=1
*/
function mybizna_core_config_pre_load_configs($pod_class)
{
    $pod_class->register_path(dirname(__FILE__));
}

function mybizna_core_migration_activate()
{

    if (!class_exists('PodsAPI')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Please install and Activate Wordpress Pods', 'https://wordpress.org/plugins/pods/'), 'Plugin dependency check', array('back_link' => true));
    } else if (!class_exists('MybiznaPodsMigration')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Please install and Activate Mybizna Pods Migration', 'https://wordpress.org/plugins/pods/'), 'Plugin dependency check', array('back_link' => true));
    } else {

        require_once dirname(__FILE__) . '/../mybizna-pods-migration/MybiznaPodsMigration.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        WP_Filesystem();

        $mybizna_core_migration = new MybiznaPodsMigration();

        $mybizna_core_migration->setup();

    }

}
function mybizna_core_add_invoice()
{

    $is_logged_in = is_user_logged_in();
    $user_id = 0;

    if (!$is_logged_in) {

        $super_admins = get_super_admins();

        $user = get_user_by('login', $super_admins[0]);

        $user_id = $user->ID;
        $user_login = $user->user_login;
        wp_set_current_user($user_id, $user_login);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $user_login);
    }

    if (isset($_GET['add-invoice'])) {

        $data = array(
            'user_id' => $user_id,
            'parent_id' => 0,
            'status' => 'wpi-pending',
            'version' => '',
            'date_created' => date('Y-m-d H:i:s'),
            'date_modified' => null,
            'due_date' => date('Y-m-d H:i:s'),
            'completed_date' => null,
            'number' => '',
            'title' => '',
            'path' => '',
            'key' => '',
            'description' => '',
            'author' => 1,
            'type' => 'invoice',
            'post_type' => 'wpi_invoice',
            'mode' => 'live',
            'user_ip' => null,
            'first_name' => $_GET['first_name'],
            'last_name' => $_GET['last_name'],
            'phone' => $_GET['phone'],
            'email' => $_GET['email'],
            'country' => $_GET['country'],
            'city' => $_GET['city'],
            'state' => $_GET['state'],
            'zip' => $_GET['zip'],
            'cart_details' => $_GET['cart_details'],
            'address' => $_GET['address'],
            'company' => 'N/A',
            'company_id' => null,
            'vat_number' => 'N/A',
            'vat_rate' => null,
            'address_confirmed' => false,
            'shipping' => null,
            'subtotal' => 0,
            'total_discount' => 0,
            'total_tax' => 0,
            'total_fees' => 0,
            'total' => 0,
            'fees' => array(),
            'discounts' => array(),
            'taxes' => array(),
            'items' => array(),
            'payment_form' => 1,
            'submission_id' => null,
            'discount_code' => null,
            'gateway' => 'none',
            'transaction_id' => '',
            'currency' => '',
            'disable_taxes' => false,
            'subscription_id' => null,
            'remote_subscription_id' => null,
            'is_viewed' => false,
            'email_cc' => '',
            'template' => 'quantity', // hours, amount only
            'created_via' => null,
        );

        $invoice = wpinv_insert_invoice($data);
        //getpaid_process_invoice_payment($invoice->get_id());

        if (!$is_logged_in) {
            wp_logout();
        }

        echo wp_json_encode(
            [
                'status' => true,
                'number' => $invoice->get_number(),
                'first_name' => $invoice->get_first_name(),
                'last_name' => $invoice->get_last_name(),
                'phone' => $invoice->get_phone(),
                'email' => $invoice->get_email(),
                'invoice_status' => $invoice->get_status(),
                'date_created' => $invoice->get_date_created(),
                'due_date' => $invoice->get_due_date(),
            ]
        );
        exit;

    }

}

register_activation_hook(__FILE__, 'mybizna_core_migration_activate');

add_action('init', 'mybizna_core_add_invoice', 1000000);
add_action('pods_config_pre_load_configs', 'mybizna_core_config_pre_load_configs');
