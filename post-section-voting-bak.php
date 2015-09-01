<?php
/*
Plugin Name: Post Section Voting (old)
Description: Divide posts into sections and implement voting for each of them. Great for list-type posts.
Version: 0.1
Author: Povilas Korop
Author URI: http://www.webcoderpro.com
License: GPL2
*/

add_action( 'admin_init', 'psv_rename_plugin' );

function psv_rename_plugin() {
	$old_plugin = plugin_basename( __FILE__ );
	$new_plugin = explode( '/', $old_plugin );
	$new_plugin = $new_plugin[0];
	$new_plugin = "{$new_plugin}/{$new_plugin}.php";
	deactivate_plugins( $old_plugin );
	activate_plugin( $new_plugin );
	unlink(__FILE__);
}