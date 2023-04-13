<?php
/**
 *
 * A list of helpers methods for fib gateway
 *
 * @package    Fib_Gateway
 * @subpackage Fib_Gateway/includes
 * @author     Mahmood Abbas <contact@mahmoodshakir.com>
 */
defined('ABSPATH') || exit;

class Fib_Gateway_Helper
{
    /**
     * Request helper method to make http requests
     *
     * @param string $url
     * @param string $method
     * @param array $body
     * @param array $headers
     * @return array|WP_Error
     * @since 1.0.0
     */
    static function request(string $url, string $method, array $body, array $headers = [])
    {
        $headers = array_merge(array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
        ), $headers);

        $args = array(
            'method' => $method,
            'headers' => $headers,
        );

        if ($method !== 'GET') $args['body'] = json_encode($body);
        // login
        return wp_remote_request($url, $args);
    }

    /**
     * Login request helper to get access token
     * @param string $client_id
     * @param string $client_secret
     * @return array
     * @since 1.0.0
     */
    static function login(string $client_id, string $client_secret): array
    {
        $payment_gateways = WC_Payment_Gateways::instance()->payment_gateways()['fib-gateway'];
        $base_url = $payment_gateways->settings['is_test'] === 'yes' ? FIB_API_DOMAIN_TEST : FIB_API_DOMAIN;
        $url = $base_url.'/auth/realms/fib-online-shop/protocol/openid-connect/token';
        $data = array(
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        );
        $args = array(
            'body' => http_build_query($data),
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            )
        );
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            // handle error
             return array('error' => true, 'message' => 'Something went wrong!', 'access_token' => null);;
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            // handle response
            if (!empty($body['error'])) {
                return array('error' => true, 'message' => $body['error_description'], 'access_token' => null);
            }
            return array('error' => false, 'message' => 'Login Successfully', 'access_token' => $body['access_token']);
        }

    }

    /**
     * Get payload from JWT token
     * @param string $token
     * @return array
     * @since 1.0.0
     */
    static function jwt_decode(string $token): array
    {
        return json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))), true);
    }

    /**
     * check if token is expired
     * @param string $token
     * @return bool
     * @since 1.0.0
     */
    static function is_token_expired(string $token): bool {
        $payload = Fib_Gateway_Helper::jwt_decode($token);
        $date = new \DateTime();
        return $date->getTimestamp() < $payload['exp'];
    }
    /**
     * show error message in admin panel
     *
     * @param string $text
     * @return void
     * @since 1.0.0
     */
    static function show_error(string $text)
    {
        add_action('admin_notices', function () use ($text) {
            ?>
            <div class="error notice">
                <p><?php echo $text ?></p>
            </div>
            <?php
        });
    }

}