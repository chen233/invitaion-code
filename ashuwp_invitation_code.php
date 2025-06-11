<?php
/**
 * Plugin Name: Ashuwp invitaion code
 * Description: Ashuwp_Invitation_Code is a wordpress plugin, It helps adding invitation codes for your site.
 * Version: 1.2
 * Author: Ashuwp
 * Author URI: http://www.ashuwp.com/package/ashuwp_invitation_code
 * Plugin URI: http://www.ashuwp.com/package/ashuwp_invitation_code
 * License: A "Slug" license name e.g. GPL2
*/

define( 'ASHUWP_INVITE_CODE_PATH', plugin_dir_path( __FILE__ ) );

function ashuwp_invitation_code_install(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
    $charset_collate = $wpdb->get_charset_collate();

    if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) :
        $sql = " CREATE TABLE `".$wpdb->prefix."ashuwp_invitation_code` (
          `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
          `code` varchar(40) NOT NULL,
          `max` INT NOT NULL,
          `users` varchar(20),
          `expiration` datetime,
          `status` varchar(20),
          UNIQUE (code)
          ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    endif;

    // 创建用户积分表
    $user_points_table = $wpdb->prefix . 'ashuwp_user_points';
    if( $wpdb->get_var("SHOW TABLES LIKE '$user_points_table'") != $user_points_table ) :
        $sql = " CREATE TABLE `".$user_points_table."` (
          `user_id` BIGINT NOT NULL PRIMARY KEY,
          `points` INT NOT NULL DEFAULT 0
          ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    endif;

    add_option('ashuwp_invitation_code_version','1.0');

    register_uninstall_hook( __FILE__, 'ashuwp_invitation_code_uninstall' );
}
register_activation_hook( __FILE__, 'ashuwp_invitation_code_install' );


add_action( 'plugins_loaded', 'ashuwp_invitation_code_load_textdomain' );
function ashuwp_invitation_code_load_textdomain() {
  load_plugin_textdomain( 'ashuwp', false, basename( dirname( __FILE__ ) ) . '/lang' ); 
}

function ashuwp_invitation_code_uninstall(){
  global $wpdb;
  $table_name = $wpdb->prefix . 'ashuwp_invitation_code';
  $sql = "DROP TABLE IF EXISTS $table_name;";
  $wpdb->query($sql);
  delete_option('ashuwp_invitation_code_version');
}
// 在前端页面显示购买按钮
function ashuwp_display_purchase_button() {
    $user_id = get_current_user_id();
    $user_points = ashuwp_get_user_points($user_id);

    if ($user_points >= 1) {
        echo '<button id="purchase-invitation-code">购买邀请码</button>';
        echo '<script>
            jQuery("#purchase-invitation-code").click(function() {
                jQuery.ajax({
                    url: "'.admin_url('admin-ajax.php').'",
                    type: "POST",
                    data: {
                        action: "ashuwp_purchase_invitation_code",
                        user_id: '.$user_id.'
                    },
                    success: function(response) {
                        alert(response);
                    }
                });
            });
        </script>';
    } else {
        echo '积分不足，无法购买邀请码。';
    }
}
add_shortcode('ashuwp_purchase_button', 'ashuwp_display_purchase_button');

// 处理购买邀请码的 AJAX 请求
function ashuwp_purchase_invitation_code() {
    $user_id = $_POST['user_id'];
    $user_points = ashuwp_get_user_points($user_id);

    if ($user_points >= 1) {
        // 扣除积分
        ashuwp_update_user_points($user_id, $user_points - 1);

        // 生成邀请码
        $code = ashuwp_generate_invitation_code();
        if ($code) {
            echo '购买成功，您的邀请码是：'.$code;
        } else {
            echo '购买失败，请稍后再试。';
        }
    } else {
        echo '积分不足，无法购买邀请码。';
    }

    wp_die();
}
add_action('wp_ajax_ashuwp_purchase_invitation_code', 'ashuwp_purchase_invitation_code');
add_action('wp_ajax_nopriv_ashuwp_purchase_invitation_code', 'ashuwp_purchase_invitation_code');

// 在前端页面显示查询按钮
function ashuwp_display_query_button() {
    $user_id = get_current_user_id();
    echo '<button id="query-unused-invitation-codes">查询未使用邀请码</button>';
    echo '<script>
        jQuery("#query-unused-invitation-codes").click(function() {
            jQuery.ajax({
                url: "'.admin_url('admin-ajax.php').'",
                type: "POST",
                data: {
                    action: "ashuwp_query_unused_invitation_codes",
                    user_id: '.$user_id.'
                },
                success: function(response) {
                    alert(response);
                }
            });
        });
    </script>';
}
add_shortcode('ashuwp_query_button', 'ashuwp_display_query_button');

// 处理查询未使用邀请码的 AJAX 请求
function ashuwp_query_unused_invitation_codes() {
    $user_id = $_POST['user_id'];
    $codes = ashuwp_get_unused_invitation_codes($user_id);

    if (!empty($codes)) {
        $code_list = implode(', ', $codes);
        echo '您未使用的邀请码有：'.$code_list;
    } else {
        echo '您没有未使用的邀请码。';
    }

    wp_die();
}
add_action('wp_ajax_ashuwp_query_unused_invitation_codes', 'ashuwp_query_unused_invitation_codes');
add_action('wp_ajax_nopriv_ashuwp_query_unused_invitation_codes', 'ashuwp_query_unused_invitation_codes');


require ASHUWP_INVITE_CODE_PATH .'/includes/functions.php';
require ASHUWP_INVITE_CODE_PATH .'/admin/admin.php';
require ASHUWP_INVITE_CODE_PATH .'/includes/invitation_code_login.php';
require ASHUWP_INVITE_CODE_PATH .'/tinymce/insert_invitation_code.php';