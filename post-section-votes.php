<?php
/*
Plugin Name: Post Section Voting (new)
Description: Divide posts into sections and implement voting for each of them. Great for list-type posts.
Version: 0.1
Author: Povilas Korop
Author URI: http://www.webcoderpro.com
License: GPL2
*/

/* On activation - create database table for section votes */
function post_section_voting_activate() {
	global $wpdb;
	$table_name = $wpdb->prefix . "posts_sections_votes";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
	    $sql = "CREATE TABLE $table_name (
	      id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	      post_id INT(11) NOT NULL,
	      section_name VARCHAR(50) NOT NULL,
	      vote INT(1) NOT NULL,
	      create_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      ip_address VARCHAR(255) NULL
	    );";
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    dbDelta( $sql );
	}
}
register_activation_hook( __FILE__, 'post_section_voting_activate' );
/* End of activation function */

/* On uninstall - delete database table for section votes */
function post_section_voting_uninstall() {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->base_prefix . "posts_sections_votes`;" );
}
register_uninstall_hook( __FILE__, 'post_section_voting_uninstall' );
/* End of uninstall function */

/* Shortcode change to a section code */
function post_section_voting_section($atts, $content = null) {
	if (!is_single()) return $content;
	global $wpdb;
	extract(shortcode_atts(array(
      'section' => '1',
	), $atts, 'post_section'));
	$section = preg_replace("/[^a-zA-Z0-9]+/", "", $section);
	$current_rating = (int)$wpdb->get_var( 
		$wpdb->prepare( "SELECT sum(vote) FROM ".$wpdb->base_prefix."posts_sections_votes
			WHERE post_id = %d and section_name = %s", 
		get_the_ID(), $section) );

	if ($current_rating > 0) { $current_rating = '+' . $current_rating; }
	$return_string = '<div class="psv-section" id="'.$section.'">';
	$return_string .= '<span class="psv-result" id="psv-rating-'.$section.'">'.$current_rating.'</span>';
	$return_string .= $content;
	$return_string .= '<div class="psv-voting-block">';
	$return_string .= '<span class="psv-vote-response" id="response_'.$section.'"></span> ';
	$return_string .= '<a href="javascript: void(0);" class="psv-vote-link" id="psv-vote-plus-'.get_the_ID().'-'.$section.'">+1</a>';
	$return_string .= ' <a href="javascript: void(0);" class="psv-vote-link" id="psv-vote-minus-'.get_the_ID().'-'.$section.'">-1</a>';
	$return_string .= '</div>';
	$return_string .= '</div>';
	return $return_string;
}
/* End of shortcode change */

/* Register shortcode */
function register_post_section_voting_shortcodes() {
	add_shortcode('psv-section', 'post_section_voting_section');
}
add_action( 'init', 'register_post_section_voting_shortcodes');
/* End of register shortcode */

/* Register styles and scripts */
function post_section_voting_scripts() {
	wp_register_style('post_section_voting_style', plugins_url('style.css',__FILE__ ));
	wp_enqueue_style('post_section_voting_style');
	wp_register_script( 'post_section_voting_script', plugins_url('voting_script.js',__FILE__ ));
	wp_enqueue_script('post_section_voting_script');
	wp_localize_script( 'post_section_voting_script', 'PsvAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php')));
}
add_action( 'wp_footer', 'post_section_voting_scripts' );
/* End of register styles and scripts */

/* Saving the actual vote to database */
function post_section_voting_vote() {	
	global $wpdb;
	if (!isset($_POST)) return;
	if (!isset($_POST['section_name'])) return;
	if (!isset($_POST['post_id'])) return;
	$ip_votes = (int)$wpdb->get_var( 
		$wpdb->prepare( "SELECT sum(id) FROM ".$wpdb->base_prefix."posts_sections_votes
			WHERE post_id = %d and section_name = %s and ip_address = %s", 
		(int)$_POST['post_id'], $_POST['section_name'], $_SERVER['REMOTE_ADDR']) );
	if ($ip_votes > 0) {
		echo 'voted';
	} else {
		$wpdb->insert(
			$wpdb->base_prefix . 'posts_sections_votes',
			array(
				'post_id' => (int)$_POST['post_id'],
				'section_name' => $_POST['section_name'],
				'vote' => $_POST['vote'],
				'ip_address' => $_SERVER['REMOTE_ADDR']
			),
			array(
				'%d', '%s', '%s', '%s'
			)
		);
		echo 'ok';		
	}
	die();
	return true;
}
add_action( 'wp_ajax_post_section_voting_vote', 'post_section_voting_vote' ); 
add_action( 'wp_ajax_nopriv_post_section_voting_vote', 'post_section_voting_vote' ); 
/* End of saving the vote */
