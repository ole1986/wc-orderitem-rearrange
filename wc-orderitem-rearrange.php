<?php
/*
 * Plugin Name: Rearrange Order Items for WooCommerce
 * Description: Rearrange Woocommerce Order Item from admin backend
 * Version: 1.0.4
 * Author: ole1986 <ole.koeckemann@gmail.com>
 * Author URI: https://github.com/ole1986/wc-orderitem-rearrange
 * Plugin URI: https://github.com/ole1986/wc-orderitem-rearrange/releases
 * Text Domain: wc-orderitem-rearrange
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 7.5
 */

namespace Ole1986\WcOrderItemRearrange;

defined('ABSPATH') or die('No script kiddies please!');

define('WCORDERITEMREARRANGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCORDERITEMREARRANGE_PLUGIN_URL', plugin_dir_url(__FILE__));


class WcOrderItemRearrange
{
    public function init()
    {
        add_action('woocommerce_after_order_itemmeta', [$this, 'add_item_mover'], 10, 3);
        // enable ajax request for Order Item positioning
        add_action('wp_ajax_OrderItemRearrange', [$this, 'DoAjax']);

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'loadJS']);
        }
    }

    public function loadJS()
    {
        wp_enqueue_script('WcOrderItemRearrange_script', WCORDERITEMREARRANGE_PLUGIN_URL . 'js/admin.js?_' . time());
    }

    public function DoAjax()
    {
        global $wpdb;
        $item_id = intval($_POST['item_id']);
        $direction = intval($_POST['direction']);

        if (empty($item_id)) wp_die();
        if (is_null($direction)) wp_die();

        $orderItem = new \WC_Order_Item_Product($item_id);
        $order = wc_get_order($orderItem->get_order_id());

        $itemIds = array_keys($order->get_items());
        $key = array_search($item_id, $itemIds);

        $swapKey = $direction == 1 ? $key + 1 : $key - 1;
        $swap_id = $itemIds[$swapKey];

        if (!$swap_id) return;

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d OR order_item_id = %d", $item_id, $swap_id), OBJECT);
        $meta_ids = array_column($results, 'meta_id');

        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_order_itemmeta SET order_item_id = IF(order_item_id = %d, %d, %d) WHERE meta_id IN (" . implode(',', $meta_ids) . ")", $item_id, $swap_id, $item_id));

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d OR order_item_id = %d", $item_id, $swap_id), OBJECT);
        $resultIds = array_column($results, 'order_item_id');

        foreach ($results as $item) {
            unset($item->order_item_id);
            $id = array_pop($resultIds);
            $affected = $wpdb->update("{$wpdb->prefix}woocommerce_order_items", (array)$item, ["order_item_id" => $id]);
        }
    }

    public function add_item_mover($item_id, $item, $product)
    {
        $css = 'font-size:1.2em; margin-right: 0.5em; padding: 0.2em; text-decoration:none';
        ?>
        <div style="margin-top: 1em;">
            <a href="javascript:void(0)" style="<?php echo esc_attr($css) ?>" onclick="WcOrderItemRearrange.MoveOrderItem(this, <?php echo esc_attr($item_id) ?>, 0)">⇧</span></a>
            <a href="javascript:void(0)" style="<?php echo esc_attr($css) ?>" onclick="WcOrderItemRearrange.MoveOrderItem(this, <?php echo esc_attr($item_id) ?>, 1)">⇩</span></a>
        </div>
        <?php
    }
}


$app = new WcOrderItemRearrange();

add_action('init', [$app, 'init']);
