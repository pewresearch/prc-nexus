<?php
/**
 * WP Abilities API Manager
 *
 * @package PRC\Platform\Nexus
 */

namespace PRC\Platform\Nexus\Abilities;

/**
 * WP Abilities API Manager
 *
 * Loads new abilities and manages interface for Abilities/Tools.
 */
class Abilities_Manager {
	/**
	 * List of available abilities.
	 *
	 * @var array
	 */
	public $available_abilities = array();

	/**
	 * Constructor.
	 *
	 * @param Loader $loader The loader.
	 */
	public function __construct( $loader ) {
		$this->load_abilities( $loader );
		$this->init_available_abilities( $loader );
	}

	/**
	 * Load all the class-*<ability-name> abilities in the abilities folder.
	 *
	 * @param Loader $loader The loader.
	 */
	public function load_abilities( $loader ) {
		$abilities_dir   = plugin_dir_path( __DIR__ ) . 'abilities/';
		$ability_folders = array_filter(
			scandir( $abilities_dir ),
			function ( $item ) use ( $abilities_dir ) {
				// Check if folders 1. is a directory 2. is not . or .. 3. does not start with _ 4. contains a class-<ability-name>.php file.
				return is_dir( $abilities_dir . $item ) && '.' !== $item && '..' !== $item && '_' !== substr( $item, 0, 1 ) && file_exists( $abilities_dir . $item . '/class-' . str_replace( '_', '-', $item ) . '.php' );
			}
		);
		foreach ( $ability_folders as $folder ) {
			$file               = str_replace( '_', '-', $folder );
			$file               = 'class-' . $file . '.php';
			$ability_class_file = $abilities_dir . $folder . '/' . $file;
			// If the file exists, require it and instantiate the class.
			if ( file_exists( $ability_class_file ) ) {
				require_once $ability_class_file;

				$ability_class = str_replace( '-', '_', ucwords( $folder, '-' ) );
				$full_class    = __NAMESPACE__ . '\\' . $ability_class;
				$ability_name  = isset( $full_class::$ability_name ) ? $full_class::$ability_name : null;
				if ( ! $ability_name ) {
					continue;
				}
				// Store the ability name and class.
				$this->available_abilities[ $ability_name ] = $full_class;
			}
		}
	}

	/**
	 * Initialize all available abilities.
	 *
	 * @param Loader $loader The loader.
	 */
	public function init_available_abilities( $loader ) {
		foreach ( $this->available_abilities as $ability_name => $ability_class ) {
			if ( class_exists( $ability_class ) ) {
				new $ability_class( $loader );
			}
		}
	}
}
