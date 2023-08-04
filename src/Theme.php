<?php
/**
 * Defines the plugin updater class
 *
 * @version     1.0.2
 * @since       1.0.2
 * @package     Client
 * @link        https://licenseserver.io/
 */

namespace Madvault\Slswc\Client;

use SoftwareUpdaterInterface;

/**
 * Theme update class
 *
 * @version 1.0.0
 * @since   1.0.0
 */
class Theme extends GenericSoftwareUpdater implements SoftwareUpdaterInterface {

	/**
	 * The license details.
	 *
	 * @var LicenseDetails
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $license;

	/**
	 * The plugin slug.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $slug;

	/**
	 * The theme version
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin base file name.
	 *
	 * @var string
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public $theme_file;

	/**
	 * Get an instance of this class..
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $base_file          - path to the plugin file or directory, relative to the plugins directory.
	 * @param   array  $args               - array of additional arguments to override default ones.
	 */
	public static function get_instance( $license_server_url, $base_file, $args ) {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $license_server_url, $base_file, $args );
		}

		return self::$instance;
	}

	/**
	 * Initialize the class actions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 * @param   string $license_server_url - The base url to your WooCommerce shop.
	 * @param   string $theme_file - path to the plugin file or directory, relative to the plugins directory.
	 * @param   string $software_type - the type of software this is. plugin|theme, default: plugin.
	 * @param   array  $args - array of additional arguments to override default ones.
	 */
	public function __construct( $license_server_url, $theme_file, $args ) {
		parent::__construct( $license_server_url, $theme_file, $args );

		$this->theme_file = $theme_file;

		$args = Helper::get_file_details( $this->theme_file );

		$this->slug = $args['slug'];

		$this->client = ApiClient::get_instance( $license_server_url, $this->slug );

		$this->license = new LicenseDetails(
			$license_server_url,
			$theme_file
		);
	}

	/**
	 * Initialize the hooks
	 *
	 * @return void
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	public function init_hooks() {
		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_check' ), 21, 1 );
		add_filter( 'extra_theme_headers', array( $this, 'extra_headers' ) );
	}
	
	/**
	 * Check if there are updates for themes.
	 *
	 * @param   mixed $transient transient object from update api.
	 * @return  mixed $transient transient object from update api.
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	public function update_check( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$server_response = $this->client->request( 'check_update', $this->get_license_details() );

		if ( $this->license->check_license( $server_response ) ) {

			if ( isset( $server_response ) && is_object( $server_response->software_details ) ) {

				$theme_update_info = $server_response->software_details;

				if ( isset( $theme_update_info->new_version ) ) {
					if ( version_compare( $theme_update_info->new_version, $this->version, '>' ) ) {
						// Required to cast as array due to how object is returned from api.
						$theme_update_info->sections = (array) $theme_update_info->sections;
						$theme_update_info->banners  = (array) $theme_update_info->banners;
						$theme_update_info->url      = $theme_update_info->homepage;
						// Theme name.
						$transient->response[ $this->slug ] = (array) $theme_update_info;
					}
				}
			}
		}

		return $transient;
	}
}