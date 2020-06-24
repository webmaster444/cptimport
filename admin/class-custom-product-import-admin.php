<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://webmaster444.github.io
 * @since      1.0.0
 *
 * @package    Custom_Product_Import
 * @subpackage Custom_Product_Import/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Custom_Product_Import
 * @subpackage Custom_Product_Import/admin
 * @author     Webmaster444 <jlmobile710@gmail.com>
 */
class Custom_Product_Import_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Custom_Product_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Product_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name.'-filepond', '//unpkg.com/filepond/dist/filepond.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/custom-product-import-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Custom_Product_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Product_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */		

		wp_enqueue_script( $this->plugin_name.'-filepond-validate', 'https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name.'-filepond', plugin_dir_url( __FILE__ ) . 'js/filepond.min.js', array( 'jquery' ), $this->version, false );		
		wp_enqueue_script( $this->plugin_name.'-filepond.jquery', plugin_dir_url( __FILE__ ) . 'js/filepond.jquery.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/custom-product-import-admin.js', array( 'jquery' ), $this->version, false );
	    wp_localize_script($this->plugin_name, 'wpadmin',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}

}
