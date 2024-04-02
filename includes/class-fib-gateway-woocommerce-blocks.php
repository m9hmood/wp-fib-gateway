<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;

/**
 * The WooCommerce blocks feature integration.
 *
 * This is used to define the payment gateway block integration
 * with WooCommerce feature
 *
 *
 * @since      1.4.0
 * @package    Fib_Gateway
 * @subpackage Fib_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
final class WC_Fib_Gateway_Blocks_Support extends AbstractPaymentMethodType
{

    /**
     * The gateway instance.
     *
     * @var WC_Gateway_Dummy
     * @since 1.4.0
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     * @since 1.4.0
     */
    protected $name = 'fib-gateway';

    /**
     * Initializes the payment method type.
     * @since 1.4.0
     */
    public function initialize()
    {
        $this->settings = get_option('fib-gateway_settings', []);
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[$this->name];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     * @since 1.4.0
     * @return boolean
     */
    public function is_active()
    {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     * @since 1.4.0
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $script_path = '/../assets/js/block.js';
        $script_asset_path = trailingslashit(plugin_dir_path(__FILE__)) . 'assets/js/frontend/blocks.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version' => '1.2.0'
            );
        $script_url = untrailingslashit(plugins_url('/', __FILE__)) . $script_path;

        wp_register_script(
            'wc-fib-payments-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-fib-payments-blocks', 'woocommerce-gateway-dummy', trailingslashit(plugin_dir_path(__FILE__)) . 'languages/');
        }

        return ['wc-fib-payments-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     * @since 1.4.0
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
        ];
    }
}