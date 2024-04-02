<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://mahmoodshakir.com/
 * @since             1.0.0
 * @package           Fib_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       FIB Gateway: UnOfficial Gateway Integration
 * Plugin URI:        https://fib.mahmoodshaki.com/
 * Description:       Add FIB as payment method for Wordpress easily.
 * Version:           1.4.0
 * Author:            Mahmood A.Shakir
 * Author URI:        https://mahmoodshaki.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fib-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('FIB_GATEWAY_VERSION', '1.4.0');


/**
 * Api URL
 */
define('FIB_API_DOMAIN', 'https://fib.prod.fib.iq');
define('TESTING_FIB_API_DOMAIN', 'https://fib.stage.fib.iq');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fib-gateway-activator.php
 */
function activate_fib_gateway()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fib-gateway-activator.php';
    Fib_Gateway_Activator::activate();
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fib-gateway-deactivator.php
 */
function deactivate_fib_gateway()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fib-gateway-deactivator.php';
    Fib_Gateway_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_fib_gateway');
register_deactivation_hook(__FILE__, 'deactivate_fib_gateway');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-fib-gateway.php';


/**
 * Update checker for plugin
 *
 * @since 1.0.0
 */
if (is_admin()) {
    if (!class_exists('Puc_v4_Factory')) {
        require_once plugin_dir_path(__FILE__) . 'includes/libraries/plugin-update-checker-4.9/plugin-update-checker.php';
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://plugins.mahmoodshakir.com/fib.json',
            __FILE__, //Full path to the main plugin file or functions.php.
            'fib-gateway'
        );
    }
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fib_gateway()
{
    if (class_exists('WC_Payment_Gateway')) {
        $plugin = new Fib_Gateway();
        $plugin->run();
    } else {
        add_action('admin_notices', function () {
            ?>
            <div class="error notice">
                <p><?php echo __('Sorry, you need to install Woocommerce plugin to use FIB Gateway') ?></p>
            </div>
            <?php
        });
    }
}

add_filter('plugins_loaded', 'run_fib_gateway');

/**
 * Enable payment gateway block feature for WooCommerce
 *
 * @since 1.4.0
 */
function woocommerce_fib_blocks_support()
{
    if (class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        // here we're including our "gateway block support class"
        require_once __DIR__ . '/includes/class-fib-gateway-woocommerce-blocks.php';

        // registering the PHP class we have just included
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new WC_Fib_Gateway_Blocks_Support);
            }
        );
    }
}

add_action('woocommerce_blocks_loaded', 'woocommerce_fib_blocks_support');