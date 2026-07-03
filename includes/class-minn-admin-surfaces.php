<?php
/**
 * Surface registry — the extension point for third-party plugin views.
 *
 * A "surface" is a declarative descriptor (label, capability, REST collection,
 * columns, actions) that the Minn Admin app renders with its generic list /
 * detail / action primitives. Plugins register surfaces via the
 * `minn_admin_surfaces` filter; Minn also bundles adapters for popular plugins
 * under includes/adapters/. See docs/for-plugin-authors.md.
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

class Minn_Admin_Surfaces {

	/**
	 * All registered surfaces, keyed by id.
	 *
	 * @return array
	 */
	public static function all() {
		$surfaces = apply_filters( 'minn_admin_surfaces', array() );
		return is_array( $surfaces ) ? $surfaces : array();
	}

	/**
	 * Surfaces the current user may see, as a list ready for the boot payload.
	 *
	 * @return array
	 */
	public static function for_current_user() {
		$out = array();
		foreach ( self::all() as $id => $surface ) {
			$cap = isset( $surface['cap'] ) ? $surface['cap'] : 'manage_options';
			if ( ! current_user_can( $cap ) ) {
				continue;
			}
			unset( $surface['cap'] );
			$surface['id'] = sanitize_key( $id );
			$out[]         = $surface;
		}
		return $out;
	}

	/**
	 * Editor panels — per-post field panels shown in the editor sidebar.
	 * Registered via the `minn_admin_editor_panels` filter; same shape of
	 * capability gating as surfaces. See docs/for-plugin-authors.md.
	 *
	 * @return array
	 */
	public static function editor_panels_for_current_user() {
		$panels = apply_filters( 'minn_admin_editor_panels', array() );
		$out    = array();
		foreach ( ( is_array( $panels ) ? $panels : array() ) as $id => $panel ) {
			$cap = isset( $panel['cap'] ) ? $panel['cap'] : 'edit_posts';
			if ( ! current_user_can( $cap ) ) {
				continue;
			}
			unset( $panel['cap'] );
			$panel['id'] = sanitize_key( $id );
			$out[]       = $panel;
		}
		return $out;
	}
}
