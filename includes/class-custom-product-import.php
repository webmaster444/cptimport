<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://webmaster444.github.io
 * @since      1.0.0
 *
 * @package    Custom_Product_Import
 * @subpackage Custom_Product_Import/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Custom_Product_Import
 * @subpackage Custom_Product_Import/includes
 * @author     Webmaster444 <jlmobile710@gmail.com>
 */
class Custom_Product_Import {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Custom_Product_Import_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CUSTOM_PRODUCT_IMPORT_VERSION' ) ) {
			$this->version = CUSTOM_PRODUCT_IMPORT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'custom-product-import';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();		
		add_action('init', array(__CLASS__, 'show_admin_menus'));
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Custom_Product_Import_Loader. Orchestrates the hooks of the plugin.
	 * - Custom_Product_Import_i18n. Defines internationalization functionality.
	 * - Custom_Product_Import_Admin. Defines all hooks for the admin area.
	 * - Custom_Product_Import_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-custom-product-import-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-custom-product-import-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-custom-product-import-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-custom-product-import-public.php';

		$this->loader = new Custom_Product_Import_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Custom_Product_Import_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Custom_Product_Import_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Custom_Product_Import_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Custom_Product_Import_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Custom_Product_Import_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


	public static function show_admin_menus(){
		if( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$role = ( array ) $user->roles;
		} 

		if ( is_user_logged_in() && ( current_user_can( 'author') || current_user_can('editor') || current_user_can('administrator')) ) {
			add_action('admin_menu',array(__CLASS__,'editor_menu'));
		}		
	}

	public function editor_menu (){		
		remove_menu_page('custom.product.import.csv.menu');
		remove_menu_page('cpi-metaimport.menu');
		remove_menu_page('cpi-import-products.menu');
		remove_menu_page('cpi-exportproduct.menu');
		$my_page = add_menu_page('Product Import', 'Product Import', '2',
			'custom.product.import.csv.menu',array(__CLASS__,'menu_testing_function'),'dashicons-products');

		$my_page = add_submenu_page('custom.product.import.csv.menu', 'Cat. Meta Import', 'Cat. Meta import', '2',
			'cpi-metaimport.menu',array(__CLASS__,'meta_import_function'));

		$my_page = add_submenu_page('custom.product.import.csv.menu', 'Import Rings', 'Import New Products', '2',
			'cpi-import-products.menu',array(__CLASS__,'import_products_function'));
		

		$my_page = add_submenu_page('custom.product.import.csv.menu', 'Export Products', 'Export Products', '2',
			'cpi-exportproduct.menu',array(__CLASS__,'export_product_function'));
	}

	// Update custom fields
	public static function menu_testing_function(){
		?><div id="wp-csv-importer-admin">
			<h2> Import your csv file </h2>
			<div class="field_groups" style="margin:10px 0">				
				<?php
				$field_groups = acf_get_field_groups();
				?>
				<label> Select field groups: </label>
				<select id="field_groups" name="field_groups">
					<?php
				foreach ( $field_groups as $group ) {
					echo '<option value="'.$group['title'].'">'.$group['title'].'</option>';
				}
				?>
			</select>
			</div>
			<input type="file" class="mycsvfile" data-max-file-size="3MB" name="filepond" accept=".csv, application/vnd.ms-excel"/>
			<div class="columns_mapping hide">
				<form id="mapping_form">
					<input type="hidden" name="uploaded_url" id="uploaded_url"/>
					<input type="hidden" name="group_name" id="group_name" />
					<table id="columns_mapping_table">
						<tr><th>Custom fields</th><th>CSV columns</th>
					</table>
					<button type="submit" id="submit_mapping"> Import Products <svg version="1.1" id="loadingSvg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve" class="hide">
					  <circle fill="#fff" stroke="none" cx="6" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite"
					      begin="0.1"/>    
					  </circle>
					  <circle fill="#fff" stroke="none" cx="26" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.2"/>       
					  </circle>
					  <circle fill="#fff" stroke="none" cx="46" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.3"/>     
					  </circle>
					</svg></button>

				</form>
			</div>
		</div><?php
	}	

	// Export products in category
	public static function export_product_function(){		
		// $attribute_term = get_term_by('name', 'E - Blanc exceptionnel', 'pa_couleur');
		// echo 'slug'.$attribute_term->slug;
		// $post_id = 943982;

		// $args = array(
		//         'post_parent'      => $post_id,
		//         'posts_per_page' => -1,
		//         'post_type'    => 'product_variation'
		//     );


	 //    $loop = new WP_Query( $args );
		
	 //    while ( $loop->have_posts() ) : $loop->the_post();
	 //    	echo 'id'.get_the_ID();
	 //    	update_post_meta(get_the_ID(), 'attribute_pa_couleur', $attribute_term->slug);
	 //    endwhile;
		
		// var_dump($attribute_term);

		?><div id="wp-csv-importer-admin">
			<h2> Export Products </h2>			
			<div class="columns_mapping">
				<form id="mapping_form">
					<label>Select category you want to export </label><br/>
					<select id="export_category" name="export_category">
					<?php 
					// since wordpress 4.5.0
					$args = array(
					    'taxonomy'   => "product_cat",
					    'number'     => $number,
					    'orderby'    => $orderby,
					    'order'      => $order,
					    'hide_empty' => $hide_empty,
					    'include'    => $ids
					);
					$product_categories = get_terms($args);				

					foreach ($product_categories as $category) {
						echo '<option value="'.$category->name.'">'.$category->name.'('.$category->count.')</option>';
					}

					?>
					</select><br/>
					<button type="submit" id="export_products_btn"> Export Products <svg version="1.1" id="loadingSvg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve" class="hide">
					  <circle fill="#fff" stroke="none" cx="6" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite"
					      begin="0.1"/>    
					  </circle>
					  <circle fill="#fff" stroke="none" cx="26" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.2"/>       
					  </circle>
					  <circle fill="#fff" stroke="none" cx="46" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.3"/>     
					  </circle>
					</svg></button>
				</form>
			</div>
		</div><?php
	}

	// Update category meta description
	public static function meta_import_function(){
		?><div id="wp-csv-importer-admin">
			<h2> Import Meta Description </h2>
			<h2> Select your csv file </h2>
			<input type="file" class="mymetacsvfile" data-max-file-size="3MB" name="filepond" accept=".csv, application/vnd.ms-excel"/>
			<div class="columns_mapping hide">
				<form id="mapping_form" method="POST">
					<input type="hidden" name="uploaded_url" id="uploaded_url"/>
					<input type="hidden" name="group_name" id="group_name" />
					<table id="columns_mapping_table">
						<tr><th>Custom fields</th><th>CSV columns</th>
					</table>
					<button type="submit" id="import_metadescription"> Import Products <svg version="1.1" id="loadingSvg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve" class="hide">
					  <circle fill="#fff" stroke="none" cx="6" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite"
					      begin="0.1"/>    
					  </circle>
					  <circle fill="#fff" stroke="none" cx="26" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.2"/>       
					  </circle>
					  <circle fill="#fff" stroke="none" cx="46" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.3"/>     
					  </circle>
					</svg></button>
				</form>
			</div>
		</div><?php
	}	

	// Add new products
	public static function import_products_function(){
		?><div id="wp-csv-importer-admin">
			<h2> Import Products </h2>				
				<form id="mapping_form" method="POST">
					<input type="hidden" id="perpage" name="perpage" value="6"/>
					<input type="hidden" id="currentpage" name="currentpage" value="0"/>
					<input type="hidden" id="totalnumber" name="totalnumber" value=""/>
					<input type="hidden" id="importedrows" name="importedrows" value=""/>
					<div class="field_groups">		
					<label for="product_type_select"> Product Type: </label>
					<select id="product_type_select" name="product_type_select">
						<option value="Ring">Ring</option>
						<option value="Pendant">Pendant</option>
						<option value="Earring">Earring</option>
						<option value="Diamand">Diamand</option>
					</select>						
						<h2> Select your csv file </h2>					
						<input type="file" class="newproductscsvfile" data-max-file-size="3MB" name="filepond" accept=".csv, application/vnd.ms-excel"/>
					</div>
					<div class="columns_mapping hide">
					<table id="columns_mapping_table">
						<tr><th>Product Fields</th><th>CSV columns</th>
					</table>
					<input type="hidden" name="uploaded_url" id="uploaded_url"/>		
					<p id="import_progress" class="hide"><span id="importedspan">0</span>/<span id="totalrowspan"></span></p>
					<button type="submit" id="import_newproducts"> Import Products <svg version="1.1" id="loadingSvg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve" class="hide">
					  <circle fill="#fff" stroke="none" cx="6" cy="50" r="6">
					    <animate 
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite"
					      begin="0.1"/>    
					  </circle>
					  <circle fill="#fff" stroke="none" cx="26" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.2"/>       
					  </circle>
					  <circle fill="#fff" stroke="none" cx="46" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.3"/>     
					  </circle>
					</svg></button>
					<!-- <button type="submit" id="import_galleryimages"> Import Gallery Images <svg version="1.1" id="loadingSvg1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 0 0" xml:space="preserve" class="hide">
					  <circle fill="#fff" stroke="none" cx="6" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite"
					      begin="0.1"/>    
					  </circle>
					  <circle fill="#fff" stroke="none" cx="26" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.2"/>       
					  </circle>
					  <circle fill="#fff" stroke="none" cx="46" cy="50" r="6">
					    <animate
					      attributeName="opacity"
					      dur="1s"
					      values="0;1;0"
					      repeatCount="indefinite" 
					      begin="0.3"/>     
					  </circle>
					</svg></button> -->
					</div>
				</form>			
		</div><?php
	}
}
