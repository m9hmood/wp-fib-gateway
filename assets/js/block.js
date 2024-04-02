const _wp_fib_gateway_settings = window.wc.wcSettings.getSetting( 'fib-gateway_data', {} );
const _wp_fib_gateway_label = window.wp.htmlEntities.decodeEntities( _wp_fib_gateway_settings.title ) || window.wp.i18n.__('FIB Gateway', 'fib-gateway');
const WpFibContent = () => {
    return window.wp.htmlEntities.decodeEntities( _wp_fib_gateway_settings.description || '' );
};
const WP_FIB_Block_Gateway = {
    name: 'fib-gateway',
    label: _wp_fib_gateway_label,
    content: Object( window.wp.element.createElement )( WpFibContent, null ),
    edit: Object( window.wp.element.createElement )( WpFibContent, null ),
    canMakePayment: () => true,
    ariaLabel: _wp_fib_gateway_label,
    supports: {
        features: _wp_fib_gateway_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( WP_FIB_Block_Gateway );