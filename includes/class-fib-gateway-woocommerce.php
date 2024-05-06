<?php
/**
 *
 * define the hooks and settings of woocommerce payment gateway
 *
 * @package    Fib_Gateway
 * @subpackage Fib_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */

defined('ABSPATH') || exit;

class Fib_Gateway_WC extends WC_Payment_Gateway
{
    /**
     * @since 1.0.0
     * @var string
     */
    public $title;
    /**
     * @since 1.0.0
     * @var string
     */
    public $description;
    /**
     * Private Client ID
     *
     * @since 1.0.0
     * @var string
     */
    private $client_id;
    /**
     * Private Client Secret
     *
     * @since 1.0.0
     * @var string
     */
    private $client_secret;
    /**
     * store currency
     *
     * @since 1.0.0
     * @var boolean
     */
    private $currency;
    /**
     * authorization token
     *
     * @since 1.0.0
     * @var boolean
     */
    private $access_token;
    /**
     * @since 1.0.0
     * @var boolean
     */
    private $isTest = false;

    /**
     * Define the core functionality of the plugin and add Fib to woocommerce gateways.
     *.
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->id = 'fib-gateway';
        $this->method_title = __('FIB Gateway', 'fib-gateway');
        $this->method_description = __('Have your customers pay with FIB (First Iraq Bank).', 'fib-gateway');
        $this->supports = array(
            'products',
        );
        // Load form fields
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();
        // Define user set variables
        $this->title = $this->get_option('title') ? $this->get_option('title') : 'Fib Gateway';
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->client_id = $this->get_option('client_id');
        $this->client_secret = $this->get_option('client_secret');
        $this->currency = $this->get_option('currency');
        $this->isTest = $this->get_option('is_test') == 'yes' ? true : false;


        if (empty($this->client_secret) || empty($this->client_id)) {
            Fib_Gateway_Helper::show_error(__('FIB Gateway was disabled, You must set Client ID and Secret from  settings', 'fib-gateway'));
            $this->enabled = "no";
            $this->update_option('enabled', 'no');
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_before_thankyou', array($this, 'order_received_text'), 5);

    }

    /**
     * initial settings fields for the gateway
     *
     * @since 1.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'fib-gateway'),
                'label' => __('Enable FIB Gateway', 'fib-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'fib-gateway'),
                'label' => __('Payment Option Title', 'fib-gateway'),
                'type' => 'text',
                'default' => 'FIB Gateway',
            ),
            'description' => array(
                'title' => __('Description', 'fib-gateway'),
                'type' => 'textarea',
                'description' => __('This description is what user see when choose FIB gateway', 'fib-gateway'),
                'default' => __('Pay via FIB'),
            ),
            'client_id' => array(
                'title' => __('Client ID', 'fib-gateway'),
                'type' => 'text',
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'fib-gateway'),
                'type' => 'text',
            ),
            'currency' => array(
                'title' => __('Currency', 'fib-gateway'),
                'type' => 'select',
                'options' => array(
                    'IQD' => 'IQD',
                    'USD' => 'USD',
                )
            ),
            'is_test' => array(
                'title' => __('Test Mode', 'fib-gateway'),
                'label' => __('Enable Test Mode', 'fib-gateway'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
        );
    }

    /**
     * Customize order received text for woocommerce when customer use fib gateway.
     *
     * @since    1.0.0
     */
    public function order_received_text($order_id)
    {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() === 'fib-gateway') {
            echo '<div class="fib-gateway-container">';
            echo __('Dear Mr/Mrs.', 'fib-gateway') . PHP_EOL . $order->get_billing_first_name() . '<br />';
            echo __('An invoice has been created for your order, please pay the bill through Fib Application', 'fib-gateway') . '<br />';
            echo '<img src="' . get_post_meta($order_id, '_fib_order_qr_code', true) . '">';
            echo $order->get_meta('_fib_or  der_id');
            echo '<div class="fib-gateway-buttons">';
            echo '<a href="' . get_post_meta($order_id, '_fib_personal_app_link', true) . '" class="button wc-fib-button">' . __('FIB Personal', 'fib-gateway') . '</a>';
            echo '<a href="' . get_post_meta($order_id, '_fib_business_app_link', true) . '" class="button wc-fib-button">' . __('FIB Business', 'fib-gateway') . '</a>';
            echo '<a href="' . get_post_meta($order_id, '_fib_corporate_app_link', true) . '" class="button wc-fib-button">' . __('FIB Corporate', 'fib-gateway') . '</a>';
            echo '</div>';
            echo '</div>';

        }
    }

    /**
     * on choose pay using fib gateway crate bill for user order and change the status
     * of the order to pending and store Fib meta in the order
     *
     * @param $order_id
     * @return array|void
     * @since 1.0.0
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = wc_get_order($order_id);

        // create fib bill
        $request = $this->create_payment($order);


        // decode request response
        $response = json_decode(wp_remote_retrieve_body($request), true);
        $statusCode = wp_remote_retrieve_response_code($request);

        /**
         * handle request status code
         * 201 - order has been created
         * default - something wrong
         */
        switch ($statusCode) {
            case 201:
                // update order status to pending
                $order->update_status('pending', __('Awaiting bill payment', 'fib-gateway'));

                // store fib meta data
                add_post_meta($order->get_id(), "_fib_order_id", $response['paymentId']);
                add_post_meta($order->get_id(), "_fib_order_qr_code", $response['qrCode']);
                add_post_meta($order->get_id(), "_fib_order_readable_code", $response['readableCode']);
                add_post_meta($order->get_id(), "_fib_personal_app_link", $response['personalAppLink']);
                add_post_meta($order->get_id(), "_fib_business_app_link", $response['businessAppLink']);
                add_post_meta($order->get_id(), "_fib_corporate_app_link", $response['corporateAppLink']);
                // empty cart
                $woocommerce->cart->empty_cart();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            default:
                wc_add_notice(__('Something Wrong, Please try again later.', 'fib-gateway') . $statusCode, 'error');
                break;
        }
    }

    /**
     * Create bill in FIB Gateway for the order
     * @param WC_Order $order
     * @return array|WP_Error
     */
    private function create_payment(WC_Order $order)
    {
        $amount = $order->get_total() + 0;
        $base_url = $this->isTest ? TESTING_FIB_API_DOMAIN : FIB_API_DOMAIN;

        // request body for creating new bill for fib
        $login_response = Fib_Gateway_Helper::login($this->client_id, $this->client_secret);

        $args = array(
            'monetaryValue' => array(
                'amount' => $amount,
                'currency' => $this->currency,
            ),
            'statusCallbackUrl' => rest_url('v1/fib/order/callback'),
        );
        // add Authorization to request header
        $headers = array(
            'Authorization' => 'Bearer ' . $login_response['access_token'],
        );
        return Fib_Gateway_Helper::request($base_url . '/protected/v1/payments', 'POST', $args, $headers);
    }
}
