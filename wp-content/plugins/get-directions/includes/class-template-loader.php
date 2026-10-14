<?php
/**
 * Created
 * User: alan
 * Date: 25/08/17
 * Time: 09:12
 */

namespace Get_Directions\Includes;

use Gamajo_Template_Loader;


require_once dirname( __FILE__ ) . '/vendor/gamajo/template-loader/class-gamajo-template-loader.php';

/**
 * Template loader
 *
 * Only need to specify class properties here.
 *
 */
class Template_Loader extends Gamajo_Template_Loader {
	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $filter_prefix = 'fullworks-get-directions';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'fullworks-get-directions';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_directory = GET_DIRECTIONS_PLUGIN_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * e.g. 'templates' or 'includes/templates', etc.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates';
}