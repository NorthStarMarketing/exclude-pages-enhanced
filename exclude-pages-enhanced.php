<?php
/**
 * A WordPress plugin to exclude pages from get_pages().
 *
 * @package   Exclude Pages Enhanced
 * @author    North Star Marketing <tech@northstarmarketing.com>
 * @copyright 2019 North Star Marketing
 * @license  GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name: Exclude Pages Enhanced
 * Description: Provides a way to exclude pages retrieved with get_pages().  Carries on the spirit of <a href="https://wordpress.org/plugins/exclude-pages/">Exclude Pages</a> by Simon Wheatley.
 * Version:     1.0.0
 * Author:      North Star Marketing
 * Author URI:  https://www.northstarmarketing.com
 * Text Domain: exclude-pages-enhanced
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

// Option name for exclusion data.
define( 'EPE_OPTION_NAME', 'epe_exclude_pages' );

add_action( 'init', 'epe_init' );
add_action( 'admin_init', 'epe_admin_init' );

function epe_init() {
	add_filter( 'get_pages', 'epe_exclude_pages' );
}

function epe_admin_init() {

	// Add panels into the editing sidebar(s).
	add_meta_box( 'epe_admin_meta_box', 'Exclude Pages Enhanced', 'epe_admin_sidebar', 'page', 'side', 'low' );

	// Set the exclusion when the post is saved.
	add_action( 'save_post', 'epe_update_exclusions' );
}

function epe_exclude_pages( $pages ) {
	if ( is_admin() ) {
		return $pages;
	}

	$excluded_ids = get_option( EPE_OPTION_NAME, array() );
	$length       = count( $pages );

	// Loop though the $pages array and actually unset/delete stuff.
	for ( $i = 0; $i < $length; $i++ ) {
		$page = $pages[ $i ];
		if ( in_array( $page->ID, $excluded_ids, true ) ) {
			unset( $pages[ $i ] );
		}
	}

	return $pages;
}

/**
 * Callback function for the metabox on the page edit screen.
 **/
function epe_admin_sidebar() {
	echo '	<div id="excludepagediv">';
	echo '		<div class="outer"><div class="inner">';
	echo '		<p><label for="epe_this_page_excluded" class="selectit">';
	echo '		<input ';
	echo '			type="checkbox" ';
	echo '			name="epe_this_page_excluded" ';
	echo '			id="epe_this_page_excluded" ';
	echo '          value="1"';
	if ( epe_is_page_excluded() ) {
		echo 'checked="checked"';
	}
	echo ' />';
	echo '			Hide this page from lists of pages</label>';
	echo '		</p>';
	echo '		</div><!-- .inner --></div><!-- .outer -->';
	echo '	</div><!-- #excludepagediv -->';
}

// This function gets all the exclusions out of the options
// table, updates them, and resaves them in the options table.
// We're avoiding making this a postmeta (custom field) because we
// don't want to have to retrieve meta for every page in order to
// determine if it's to be excluded. Storing all the exclusions in
// one row seems more sensible.
function epe_update_exclusions( $post_id ) {

	$exclude_this_page = false;
	if ( '1' === $_POST['epe_this_page_excluded'] ) {
		$exclude_this_page = true;
	}

	$excluded_ids = get_option( EPE_OPTION_NAME, array() );

	if ( $exclude_this_page ) {
		// Add the post ID to the array of excluded IDs
		array_push( $excluded_ids, $post_id );
		$excluded_ids = array_unique( $excluded_ids );
	} else {
		// Find the post ID in the array of excluded IDs.
		$index = array_search( $post_id, $excluded_ids );
		// Delete any index found.
		if ( false !== $index ) {
			unset( $excluded_ids[ $index ] );
		}
	}

	update_option( EPE_OPTION_NAME, $excluded_ids );

}

function epe_is_page_excluded() {

	global $post_id;

	// New post? Must not be excluded then.
	if ( ! $post_id ) {
		return false;
	}

	$excluded_ids = get_option( EPE_OPTION_NAME, array() );

	if ( in_array( $post_id, $excluded_ids, true ) ) {
		return true;
	}

	return false;
}
