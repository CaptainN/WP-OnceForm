<?php
/*
Plugin Name: OnceForm WordPress Library
Plugin URI: https://github.com/adcSTUDIO/OnceForm
Description: A plugin for running unit and integration tests for OnceForm.
Author: Kevin Newman, Ken Newman, adcSTUDIO LLC
Version: 0.2
Author URI: http://adcSTUDIO.com/
*/

require_once 'OnceForm/WP_OnceForm.php';
require_once 'WP_MetaForm.php';

if ( is_admin() )
{
	add_action( 'admin_menu', function() {

		add_menu_page(
			'onceformtests',
			'OnceForm Tests',
			'manage_options',
			'onceformtests',

			function()
			{
				if (!current_user_can('manage_options')) {
					wp_die( __('You do not have sufficient permissions to access this page.') );
				}

				echo do_shortcode( '[simpletest name="OnceForm Unit Tests" path="'.
					'/WP-OnceForm/tests/all_tests.php" passes="y"]' );
			}

		);

	} );
}

