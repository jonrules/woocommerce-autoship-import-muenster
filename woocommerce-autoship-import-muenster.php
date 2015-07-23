<?php
/*
Plugin Name: WC Autoship Import Muenster
Plugin URI: http://wooautoship.com
Description: Import autoship schedules for Muenster customers
Version: 1.0.0
Author: Patterns in the Cloud
Author URI: http://patternsinthecloud.com
License: Single-site
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) ) {
	
	function wc_autoship_import_muenster_install() {

	}
	register_activation_hook( __FILE__, 'wc_autoship_import_muenster_install' );
	
	function wc_autoship_import_muenster_deactivate() {
	
	}
	register_deactivation_hook( __FILE__, 'wc_autoship_import_muenster_deactivate' );
	
	function wc_autoship_import_muenster_uninstall() {

	}
	register_uninstall_hook( __FILE__, 'wc_autoship_import_muenster_uninstall' );
	
	function wc_autoship_import_muenster_admin_scripts() {
		wp_enqueue_style( 'wc-autoship-import-muenster-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.0.0' );
		wp_enqueue_style( 'jquery.dropandpop', plugin_dir_url( __FILE__ ) . 'js/jquery.dropandpop/css/jquery.dropandpop.css', array(), '1.0.0' );
		wp_enqueue_script( 'jquery.dropandpop', plugin_dir_url( __FILE__ ) . 'js/jquery.dropandpop/jquery.dropandpop.js', array( 'jquery' ), '1.0.0' );
		wp_register_script( 'wc-autoship-import-muenster-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'jquery.dropandpop' ), '1.0.0', true );
		wp_localize_script( 'wc-autoship-import-muenster-admin', 'WC_Autoship_Import_Muenster', array(
			'ajax_url' => admin_url( '/admin-ajax.php' )
		) );
		wp_enqueue_script( 'wc-autoship-import-muenster-admin' );
	}
	add_action( 'admin_enqueue_scripts', 'wc_autoship_import_muenster_admin_scripts' );
	
	function wc_autoship_import_muenster_settings( $settings ) {
		$settings[] = array(
			'title' => __( 'Import Muenster Schedules', 'wc-autoship-import-muenster' ),
			'desc' => __( 'Import autoship schedules for Muenster customers.', 'wc-autoship-import-muenster' ),
			'desc_tip' => false,
			'type' => 'title',
			'id' => 'wc_autoship_import_muenster_title'
		);
		$settings[] = array(
			'name' => __( 'Import File', 'wc-autoship-import-muenster' ),
			'desc' => __( 
			'The Autoship CSV file to import. Required fields: Customer Email, Next Shipping Date, Shipping Method, Frequency, Quantity, Product SKU',
				'wc-autoship-import-muenster'
			),
			'desc_tip' => true,
			'type' => 'wc_autoship_import_muenster_file',
			'id' => 'wc_autoship_import_muenster_file'
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'wc_autoship_import_muenster_sectionend'
		);
		return $settings;
	}
	add_filter( 'wc_autoship_settings', 'wc_autoship_import_muenster_settings', 10, 1 );
	
	function wc_autoship_import_muenster_file_settings_field( $value ) {
		$vars = array(
			'value' => $value,
			'description' => WC_Admin_Settings::get_field_description( $value ),
			'frequency_options' => get_option( $value['id'] )
		);
		$relative_path = 'admin/wc-settings/wc-autoship/import-muenster-file';
		wc_autoship_import_muenster_include_plugin_template( $relative_path, $vars );
	}
	add_action( 'woocommerce_admin_field_wc_autoship_import_muenster_file', 'wc_autoship_import_muenster_file_settings_field' );
	
	function wc_autoship_import_muenster_file() {
		set_time_limit( 300 );
		header( 'Content-Type: application/json' );
		
		// Check if file is valid
		if ( empty( $_FILES['uploadedFiles']['error'] ) || $_FILES['uploadedFiles']['error'][0] !== UPLOAD_ERR_OK ) {
			echo json_encode( array(
				'error' => 'Upload error code: ' . $_FILES['uploadedFiles']['error'][0]
			) );
			die();
		}
		
		// Open file
		$file = fopen( $_FILES['uploadedFiles']['tmp_name'][0], 'r' );
		if ( ! $file ) {
			echo json_encode( array(
				'error' => 'Could not open file'
			) );
			die();
		}
		// Read column headers
		$column_headers = fgetcsv( $file );
		if ( empty( $column_headers ) ) {
			echo json_encode( array(
				'error' => 'Invalid file format'
			) );
			die();
		}
		$columns = array();
		foreach ( $column_headers as $index => $name ) {
			$columns[ $name ] = $index;
		}
		$column_names = array( 'Customer Email', 'Next Shipping Date', 'Shipping Method', 'Frequency', 'Quantity', 'Product SKU' );
		foreach ( $column_names as $name ) {
			if ( ! isset( $columns[ $name ] ) ) {
				echo json_encode( array(
					'error' => 'Missing column: ' . $name
				) );
				die();
			}
		}
		
		// Include dependencies
		require_once ( WP_PLUGIN_DIR . '/woocommerce-autoship/classes/wc-autoship-customer.php' );
		require_once ( WP_PLUGIN_DIR . '/woocommerce-autoship/classes/wc-autoship-schedule.php' );
		require_once ( WP_PLUGIN_DIR . '/woocommerce-autoship/classes/wc-autoship-schedule-item.php' );
		
		// Process file
		$result = array();
		while ( ( $row = fgetcsv( $file ) ) ) {
			// Get email
			$email = $row[ $columns['Customer Email'] ];
			if ( empty( $email ) ) {
				// Email is empty, skip this record
				continue;
			}
			$result_item = array(
				'data' => array_combine( $column_headers, $row )
			);
			// Find existing user
			$user_id = 0;
			$user = get_user_by( 'email', $email );
			if ( ! $user ) {
				$result_item['error'] = "User not found for email $email";
				$result[] = $result_item;
				continue;
			} else {
				$user_id = $user->ID;
			}
			$result_item['data']['user_id'] = $user_id;
			if ( empty( $user_id ) || is_object( $user_id ) ) {
				$result_item['error'] = 'Invalid user_id';
				$result[] = $result_item;
				continue;
			}
			
			// Create autoship customer
			$customer = new WC_Autoship_Customer( $user_id );
			$customer->set( 'shipping_method', $row[ $columns['Shipping Method'] ] );
			if ( false === $customer->save() ) {
				// Error creating autoship customer
				$result_item['error'] = 'Error creating autoship customer';
				$result[] = $result_item;
				continue;
			}
			
			// Create autoship schedule item
			$product_id = wc_get_product_id_by_sku( $row[ $columns['Product SKU'] ] );
			if ( empty( $product_id ) ) {
				// Product does not exist
				$result_item['error'] = 'Product SKU not found';
				$result[] = $result_item;
				continue;
			}
			$result_item['data']['product_id'] = $product_id;
			$product = wc_get_product( $product_id );
			$item = new WC_Autoship_Schedule_Item();
			if ( $product->is_type( 'simple' ) ) {
				$item->set( 'product_id', $product_id );
			} elseif ( $product->is_type( 'variation' ) ) {
				$item->set( 'variation_id', $product_id );
				$item->set( 'product_id', $product->get_parent() );
			}
			$item->set( 'qty', $row[ $columns['Quantity'] ] );
			
			// Create autoship schedule
			$frequency_desc = $row[ $columns['Frequency'] ];
			$frequency_matches = null;
			if ( ! preg_match( '/(\d+) Month/', $frequency_desc, $frequency_matches ) ) {
				// Error saving autoship schedule
				$result_item['error'] = "Invalid Frequency: $frequency_desc";
				$result[] = $result_item;
				continue;
			}
			$frequency = 30 * ( (int) $frequency_matches[1] );
			$schedule = WC_Autoship_Schedule::get_schedule( $user_id, $frequency );
			$schedule->set_autoship_status( WC_Autoship::STATUS_PAUSED );
			$schedule->set_next_order_date( date( 'Y-m-d', strtotime( $row[ $columns['Next Shipping Date'] ] ) ) );
			$schedule->add_item( $item );
			if ( false === $schedule->save() ) {
				// Error saving autoship schedule
				$result_item['error'] = 'Error saving autoship schedule';
				$result[] = $result_item;
				continue;
			}
			
			$result[] = $result_item;
		}
		fclose( $file );
		echo json_encode( $result );
		die();
	}
	add_action( 'wp_ajax_wc_autoship_import_muenster_file', 'wc_autoship_import_muenster_file' );
	
	function wc_autoship_import_muenster_get_plugin_template_path( $relative_path ) {
		return plugin_dir_path( __FILE__ ) . 'templates/' . $relative_path . '.php';
	}
	
	function wc_autoship_import_muenster_include_plugin_template( $relative_path, $vars = array() ) {
		extract( $vars );
		include ( wc_autoship_import_muenster_get_plugin_template_path( $relative_path, $vars ) );
	}
}
