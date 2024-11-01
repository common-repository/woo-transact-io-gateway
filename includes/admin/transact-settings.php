<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wc_transact_settings',
	array(
		'enabled'                       => array(
			'title'       => __( 'Enable/Disable', 'woocommerce-gateway-transact' ),
			'label'       => __( 'Enable Transact.io payment gateway', 'woocommerce-gateway-transact' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
    ),
    'description' => array(
      'title' => __( 'Customer Message', 'woocommerce-gateway-transact' ),
      'type' => 'textarea',
      'default' => 'Place your order to complete your payment with transact. Do not close the tab or window until payment has been completed.  Create a new account for free',
    ),
    'account_id'                    => array(
			'title'       => __( 'Account ID', 'woocommerce-gateway-transact' ),
			'type'        => 'text',
			'description' => __( 'Get your ID from your transact account in the publisher settings', 'woocommerce-gateway-transact' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'secret_key'                    => array(
			'title'       => __( 'Signing Secret Key', 'woocommerce-gateway-transact' ),
			'type'        => 'text',
			'description' => __( 'Get your keys from your transact account in the publisher settings', 'woocommerce-gateway-transact' ),
			'default'     => '',
			'desc_tip'    => true,
		),
	)
);
