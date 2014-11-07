<?php
/*
  Plugin Name: Voce Submenu Nav Menu Items
  Plugin URI: http://voceconnect.com
  Description: Avoid giant Nav Menus by aggregating multiple "submenus" into a single Menu.
  Version: 1.3
  Author: Jeff Stieler
  License: GPL2
*/

class Voce_Submenu_Nav_Menu_Items {

	/**
	 * Attach nav menu page hook to add metabox and nav menu object filter to expand submenu items
	 */
	function init() {

		add_action( 'admin_head-nav-menus.php', array( __CLASS__, 'add_nav_menu_metabox' ) );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'expand_submenu_items' ) );

	}

	/**
	 * Adds a "Navigation Menus" metabox to the Appearance > Menus page
	 */
	function add_nav_menu_metabox() {

		$tax = get_taxonomy( 'nav_menu' );
		$id  = $tax->name;

		// shamelessly stolen from wp_nav_menu_taxonomy_meta_boxes()
		add_meta_box( "add-{$id}", $tax->labels->name, array( __CLASS__, 'nav_menu_metabox' ), 'nav-menus', 'side', 'default', $tax );

	}

	/**
	 * Callback for the Menus page metabox
	 * Adds a filter to 'get_term_args' to exclude the current menu
	 *
	 * @param object $object
	 * @param string $taxonomy
	 */
	function nav_menu_metabox( $object, $taxonomy ) {

		add_filter( 'get_terms_args', array( __CLASS__, 'exclude_current_menu_from_metabox' ) );

		wp_nav_menu_item_taxonomy_meta_box( $object, $taxonomy );

		remove_filter( 'get_terms_args', array( __CLASS__, 'exclude_current_menu_from_metabox' ) );

	}

	/**
	 * Exclude the menu currently being edited from the query for the Menus page metabox
	 *
	 * @param array $args
	 * @return array
	 */
	function exclude_current_menu_from_metabox( $args ) {
		global $nav_menu_selected_id;

		if ( $nav_menu_selected_id ) {
			$args['exclude'] = array( $nav_menu_selected_id );
		}

		return $args;
	}

	/**
	 * Filters 'wp_nav_menu_objects', replaces items of type "nav_menu" with the items from that Nav Menu object
	 *
	 * @param array $sorted_menu_items
	 * @return array
	 */
	function expand_submenu_items( $sorted_menu_items ) {

		$i = 1;
		while ( $i <= count( $sorted_menu_items ) ) {
			$menu_item = $sorted_menu_items[$i];

			if ( ( 'taxonomy' === $menu_item->type ) &&
				 ( 'nav_menu' === $menu_item->object ) &&
				 ( $submenu_items = wp_get_nav_menu_items( $menu_item->object_id ) ) ) {

				// Loop through and get menu_items with children
				$items_with_children = array();
				foreach ( $submenu_items as $item1 ) {
					if ( $item1->menu_item_parent )
						$items_with_children[$item1->menu_item_parent] = true;
				}

				// If item has a parent, Give the ID to all items being spliced
				if ( $parent_id = $menu_item->menu_item_parent ) {
					foreach ( $submenu_items as &$item2 ) {
						if ( empty($item2->menu_item_parent) )
							$item2->menu_item_parent = $parent_id;
					}
				}

				// Add the menu-item-has-children class where applicable
				if ( $items_with_children ) {
					foreach ( $submenu_items as &$item3 ) {
						if ( isset( $items_with_children[ $item3->ID ] ) )
							$item3->classes[] = 'menu-item-has-children';
					}
				}

				array_splice( $sorted_menu_items, ( $i - 1 ), 1, $submenu_items );
				$sorted_menu_items = array_combine( range( 1, count( $sorted_menu_items ) ), array_values( $sorted_menu_items ) );
				$i = 0;
			}
			$i++;
		}

		return $sorted_menu_items;

	}

}

add_action( 'init', array( 'Voce_Submenu_Nav_Menu_Items', 'init' ) );
