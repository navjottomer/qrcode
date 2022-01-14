<?php
/*
Plugin Name: QR Codes
Plugin URI: https://github.com/navjottomer/qrcode
Description: Add a qr code to your ad page, print it and share it offline, this is a modified version of original qrcode plugin.
Version: 2.0.0
Author: OSClass mindstellar
Author URI: https://github.com/navjottomer
Short Name: qrcode
Plugin update URI: qrcode
*/


function qrcode_install()
{
    @mkdir(osc_content_path() . 'uploads/qrcode/');
    osc_set_preference('upload_path', osc_content_path() . 'uploads/qrcode/', 'qrcode', 'STRING');
    osc_set_preference('upload_url', osc_base_url() . 'oc-content/uploads/qrcode/', 'qrcode', 'STRING');
    osc_set_preference('code_size', '2', 'qrcode', 'INTEGER');
    osc_reset_preferences();
}

function qrcode_uninstall()
{
    $upload_path = osc_get_preference('upload_path', 'qrcode');
    osc_delete_preference('upload_path', 'qrcode');
    osc_delete_preference('upload_url', 'qrcode');
    osc_delete_preference('code_size', 'qrcode');
    osc_reset_preferences();
    $files = glob($upload_path . "*.png");
    foreach ($files as $f) {
        @unlink($f);
    }
    @rmdir(osc_get_preference('upload_path', 'qrcode'));
}

// Register config Routes
osc_add_route('qrcode-conf', 'qrcode-conf', 'qrcode-conf', 'qrcode/conf.php');
// Register help route
osc_add_route('qrcode-help', 'qrcode-help', 'qrcode-help', 'qrcode/help.php');

function qrcode_admin_menu()
{

    osc_add_admin_submenu_divider('plugins', 'QR Codes', 'qrcode_divider', 'administrator');
    osc_add_admin_submenu_page('plugins', __('QR Settings', 'qrcode'), osc_route_admin_url('qrcode-conf'), 'qrcode_settings', 'administrator');
    osc_add_admin_submenu_page('plugins', __('QR Help', 'qrcode'), osc_route_admin_url('qrcode-help'), 'qrcode_help', 'administrator');
}

function qrcode_delete_item($itemId)
{
    $files = glob(osc_get_preference('upload_path', 'qrcode') . $itemId . "_*");
    foreach ($files as $f) {
        @unlink($f);
    }
}


function qrcode_generateqr($data, $id = '')
{
    include "lib/qrlib.php";
    if ($id != '') {
        $filename = $id . "_" . md5($data) . "_" . osc_get_preference("code_size", "qrcode") . ".png";
    } else {
        $filename = md5($data) . "_" . osc_get_preference("code_size", "qrcode") . ".png";
    }
    $filename = osc_get_preference('upload_path', 'qrcode') . $filename;
    QRcode::png($data, $filename, 'M', osc_get_preference("code_size", "qrcode"), 2);
}

function show_qrcode()
{
    $filename = osc_item_id() . "_" . md5(osc_item_url()) . "_" . osc_get_preference("code_size", "qrcode") . ".png";
    if (!file_exists(osc_get_preference('upload_path', 'qrcode') . $filename)) {
        qrcode_generateqr(osc_item_url(), osc_item_id());
    }
    echo '<img src="' . osc_get_preference('upload_url', 'qrcode') . $filename . '" alt="QR CODE" id="qrcode_' . osc_item_id() . '" class="qrcode" />';
}
// Register action for configuration saving
if (OC_ADMIN && Params::getParam('action') == 'qrcode-save-settings') {
    osc_set_preference('upload_path', Params::getParam('upload_path'), 'qrcode', 'STRING');
    osc_set_preference('upload_url', Params::getParam('upload_url'), 'qrcode', 'STRING');
    osc_set_preference('code_size', Params::getParam('code_size'), 'qrcode', 'INTEGER');
    osc_reset_preferences();
    osc_add_flash_ok_message('QR Code settings updated correctly', 'admin');
    osc_redirect_to(osc_route_admin_url('qrcode-conf'));
}
//Set Plugin Admin Header
if (OC_ADMIN && in_array(Params::getParam('route'), ['qrcode-conf', 'qrcode-help'])) {
    if (empty($headerTitle)) {
        $headerTitle = __('Manage all your `QrCode` settings here.', 'qrcode');
    }
    $qrcode_settings = function () use ($headerTitle) {
        osc_remove_hook('admin_page_header', 'customPageHeader');
        osc_add_hook(
            'admin_page_header',
            function () use ($headerTitle) {
                echo '<h1>' . __('QrCode Plugin', 'qrcode') . '</h1><h4>' .
                    $headerTitle . '</h4>';
?>
            <style>
                body #content-head {
                    background: #52a6ff;
                    background: linear-gradient(30deg, #52c8ff, #52a6ff);
                    color: rgba(255, 255, 255, 0.94);
                    text-shadow: 1px 1px 3px rgba(103, 101, 103, 0.6);
                    padding-bottom: 15px;
                    padding-top: 20px;
                    padding-left: 15px;
                    height: 60px;
                    z-index: 2;
                }

                body #content-head h1 {
                    color: rgba(255, 255, 255, 0.94);
                    float: left;
                    padding-right: 15px;
                }

                body #content-head h2 {
                    font-weight: 300;
                }

                body #content-page {
                    padding-right: 0;
                }
            </style>
<?php
                osc_remove_filter('admin_title', 'customPageTitle');
                osc_add_filter(
                    'admin_title',
                    function ($string) use ($headerTitle) {
                        return $headerTitle . $string;
                    }
                );
            }
        );
    };
    osc_add_hook('admin_header', $qrcode_settings);
}
/**
 * ADD HOOKS
 */
osc_register_plugin(osc_plugin_path(__FILE__), 'qrcode_install');
osc_add_hook(osc_plugin_path(__FILE__) . "_uninstall", 'qrcode_uninstall');

// DELETE ITEM
osc_add_hook('delete_item', 'qrcode_delete_item');

// FANCY MENU

osc_add_hook('admin_menu_init', 'qrcode_admin_menu');
