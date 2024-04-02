<?php

/**
 * Payment gateway callback handling class
 *
 * @package    Fib_Gateway
 * @subpackage Fib_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
class Fib_Gateway_Callback
{
    /**
     * Check order status by token
     *
     * @throws \Exception
     */
    public function callback_endpoint_handler(WP_REST_Request $request)
    {
        $body = $request->get_body_params();
        $params = $request->get_params();
        $id = $id ?? $params['id'] ?? null;

        if (!$id) {
            return new WP_Error('Not acceptable', 'The POST request is invalid', array(
                'status' => 406,
                'id' => $id,
            ));
        }


        $payment_gateways = WC_Payment_Gateways::instance()->payment_gateways()['fib-gateway'];
        $base_url = $payment_gateways->settings['is_test'] === 'yes' ? FIB_API_DOMAIN_TEST : FIB_API_DOMAIN;
        // Get the desired WC_Payment_Gateway object
        $orders = wc_get_orders(array('_fib_order_id' => $id));


        if (count($orders) === 0) {
            return new WP_Error('Not acceptable', 'The order is not found', array(
                'status' => 406,
                'id' => $id,
            ));
        }

        $login_response = Fib_Gateway_Helper::login($payment_gateways->settings['client_id'], $payment_gateways->settings['client_secret']);
        $check_request = Fib_Gateway_Helper::request($base_url . '/protected/v1/payments/' . $id . '/status', 'GET', array(), array(
            'Authorization' => 'Bearer ' . $login_response['access_token'],
        ));
        $check_response = json_decode($check_request['body'], true);

        if ($check_response['status'] === 'PAID') {
            // Update order status
            $orders[0]->set_status('processing');
            $orders[0]->save();
            $response = new WP_REST_Response(array(
                'code' => 'Accepted',
                'message' => 'Order Status Updated Successfully',
                'data' => array(
                    'id' => $id,
                    'status' => 202
                )
            ));
            $response->set_status(202);
            return $response;
        }

        return $check_response;
    }

    public function add_callback()
    {
        register_rest_route('v1/fib', '/order/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'callback_endpoint_handler'),
            'permission_callback' => '__return_true',
            'args' => [
            ],
        ));
    }

}
