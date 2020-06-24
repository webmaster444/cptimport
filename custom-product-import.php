<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://webmaster444.github.io
 * @since             1.0.0
 * @package           Custom_Product_Import
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Product Import
 * Plugin URI:        https://webmaster444.github.io/wp-plugins/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Webmaster444
 * Author URI:        https://webmaster444.github.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       custom-product-import
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CUSTOM_PRODUCT_IMPORT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-product-import-activator.php
 */
function activate_custom_product_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-product-import-activator.php';
	Custom_Product_Import_Activator::activate();	
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-product-import-deactivator.php
 */
function deactivate_custom_product_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-product-import-deactivator.php';
	Custom_Product_Import_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_custom_product_import' );
register_deactivation_hook( __FILE__, 'deactivate_custom_product_import' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-product-import.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_custom_product_import() {

	$plugin = new Custom_Product_Import();
	$plugin->run();

}
run_custom_product_import();

// upload csv file and return file url
add_action( 'wp_ajax_upload_custom_csv', 'upload_custom_csv_request' );
add_action( 'wp_ajax_nopriv_upload_custom_csv', 'upload_custom_csv_request' );
function upload_custom_csv_request(){
	$fileName = $_FILES['filepond']['name'];
	$fileType = $_FILES['filepond']['type'];
	$fileError = $_FILES['filepond']['error'];
	$fileContent = file_get_contents($_FILES['filepond']['tmp_name']);

	$upload = wp_upload_bits($_FILES['filepond']['name'], null, file_get_contents($_FILES['filepond']['tmp_name']));

	echo $upload['url'];
	exit;
}

// get all custom fields and csv columns
add_action( 'wp_ajax_parse_custom_csv', 'parse_custom_csv_request' );
add_action( 'wp_ajax_nopriv_parse_custom_csv', 'parse_custom_csv_request' );
function parse_custom_csv_request(){
	$url = $_POST['url'];
	$csvAsArray = array_map('str_getcsv', file($url));

	if (function_exists('acf_get_field_groups')){
		$field_groups = acf_get_field_groups();
		foreach ( $field_groups as $group ) {
			if ($group['title']==$_POST['group_name']){
			  	$fields = get_posts(array(
				    'posts_per_page'   => -1,
				    'post_type'        => 'acf-field',
				    'orderby'          => 'menu_order',
				    'order'            => 'ASC',
				    'suppress_filters' => true, // DO NOT allow WPML to modify the query
				    'post_parent'      => $group['ID'],
				    'post_status'      => 'any',
				    'update_post_meta_cache' => false
				));
				foreach ( $fields as $field ) {
				    $options[$field->post_name] = $field->post_title;
				}
			}
		}
	}

	$result = array();
	$result['customfields'] = $options;
	$result['filecolumns'] = $csvAsArray[0];
	$result['noofrows']= sizeof($csvAsArray);
	$result['fileurl'] = $url;
	echo json_encode($result);
	exit;	
}

// update products custom fields
add_action( 'wp_ajax_update_product_fields', 'update_product_fields_request' );
add_action( 'wp_ajax_nopriv_update_product_fields', 'update_product_fields_request' );
function update_product_fields_request(){
	$url = $_POST['uploaded_url'];
	$csv = array_map('str_getcsv', file($url));

    array_walk($csv, function(&$a) use ($csv) {
      	$a = array_combine($csv[0], $a);
    });
    array_shift($csv);

    $res = array();
    $updatedFieldsCnt = 0;
    $foundCnt = 0;
    $notfoundCnt = 0;
    foreach ($csv as $col){
    	$pName = $col['Name'];
    	$pCategory = $col['Category'];

		$args = array("post_type" => "product", "s" => $pName);
		$query = get_posts( $args );

		if(count($query)==1){
			$foundCnt++;
			$post_id = $query[0]->ID;		
			if (function_exists('acf_get_field_groups')){
				$field_groups = acf_get_field_groups();
				foreach ( $field_groups as $group ) {
					if ($group['title']==$_POST['group_name']){
					  	$fields = get_posts(array(
						    'posts_per_page'   => -1,
						    'post_type'        => 'acf-field',
						    'orderby'          => 'menu_order',
						    'order'            => 'ASC',
						    'suppress_filters' => true, // DO NOT allow WPML to modify the query
						    'post_parent'      => $group['ID'],
						    'post_status'      => 'any',
						    'update_post_meta_cache' => false
						));
						foreach ( $fields as $field ) {
						    if($_POST[$field->post_name] !=""){
						    	$colname = stripslashes($_POST[$field->post_name]);
						    	$value = $col[$colname];
						    	$updatedFieldsCnt++;
						    	update_field($field->post_name, $value, $post_id);
						    }
						}
					}
				}
			}
		}else{
			$notfoundCnt++;
		}
    }	
    $res['updatedFieldsCnt'] = $updatedFieldsCnt;
    $res['notfoundCnt'] = $notfoundCnt;
    $res['foundCnt'] = $foundCnt;
 	echo json_encode($res);   
	exit;
}

// parse category meta description csv file to return mapping fields
add_action( 'wp_ajax_parse_meta_csv', 'parse_meta_csv_func' );
add_action( 'wp_ajax_nopriv_parse_meta_csv', 'parse_meta_csv_func' );
function parse_meta_csv_func(){
	$url = $_POST['url'];
	$csvAsArray = array_map('str_getcsv', file($url));
	$result = array();

	$result['filecolumns'] = $csvAsArray[0];
	$result['fileurl'] = $url;
	echo json_encode($result);
	exit;	
}

// update category meta description
add_action( 'wp_ajax_update_termsdescription', 'update_termsdescription_request' );
add_action( 'wp_ajax_nopriv_update_termsdescription', 'update_termsdescription_request' );
function update_termsdescription_request(){
	$url = $_POST['uploaded_url'];
	$csv = array_map('str_getcsv', file($url));

    array_walk($csv, function(&$a) use ($csv) {
      	$a = array_combine($csv[0], $a);
    });
    array_shift($csv);

    $res = array();
    $updatedFieldsCnt = 0;
    $foundCnt = 0;
    $notfoundCnt = 0;
    foreach ($csv as $row){

    	$colname = $_POST['metacolumn'];    
    	$catname = $_POST['catname'];
		$term = get_term_by( 'name', $row[$catname], 'product_cat');
		$term_id = $term->term_id;		
    	
		$teststring = $row[$colname];
		
		// wp_update_term( $term_id, 'product_cat', array(
  //   		'description' => htmlspecialchars($teststring)
		// ));
		
        $taxmeta = get_option('wpseo_taxonomy_meta');

        if (!is_array($taxmeta)) {

            $taxmeta = array();

            $taxmeta['product_cat'] = array($term_id=>array('wpseo_desc'=>$teststring));

        } else {

            $taxmeta['product_cat'][$term_id]['wpseo_desc'] = $teststring;

        }

        update_option('wpseo_taxonomy_meta', $taxmeta);

    }	
    $res['updatedFieldsCnt'] = $updatedFieldsCnt;
    $res['notfoundCnt'] = $notfoundCnt;
    $res['foundCnt'] = $foundCnt;
 	echo json_encode($res);   
	exit;
}

// export products in specific category as a csv file
add_action( 'wp_ajax_cpiexport_products', 'cpiexport_products_func' );
add_action( 'wp_ajax_nopriv_cpiexport_products', 'cpiexport_products_func' );

//delete all products in certian category
function cpiexport_products_func1(){
	$args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'product_cat'    => 'Diamants-Gia'
    );

	$values=array();
    $loop = new WP_Query( $args );
	$term = get_term_by( 'name', $_POST['export_category'], 'product_cat' );
	
    while ( $loop->have_posts() ) : $loop->the_post();
    	wp_delete_post(get_the_ID(), true);
    endwhile;
    echo "#";
	exit;
}
function cpiexport_products_func(){
	$date = new DateTime();

	$filename = $_POST['export_category'].$date->getTimestamp().'.csv';
    $path = wp_upload_dir();   // or where ever you want the file to go
    $outstream = fopen($path['path']."/".$filename, "w");  // the file name you choose

    // check if selected category is diamand or not
	$diamandCats = ['achat diamant','Diamants-Gia', 'Diamants Fancy'];
	if(in_array($_POST['export_category'], $diamandCats)){
		$fields = ['Product','Category','Shape','Weight','Color','Clarity','LAB','Cut','Polish','Symmetry','Depth%','Table%','Flour.','Measurements','Girdle thick','Girdle  thin','Culet','Price/carat','discount' ,'Cert#','Cert Link','360 Video'];

		fputcsv($outstream, $fields);  //creates the first line in the csv file
    
	    $args = array(
	        'post_type'      => 'product',
	        'posts_per_page' => -1,
	        'product_cat'    => $_POST['export_category']
	    );

		$values=array();
	    $loop = new WP_Query( $args );
		$term = get_term_by( 'name', $_POST['export_category'], 'product_cat' );
		
	    while ( $loop->have_posts() ) : $loop->the_post();
	        global $product;
	        $tmp = array();
	        $tmp[0] = get_the_title();
	        $tmp[1] = $term->name;
	        $tmp[17] = get_post_meta( $product->get_id(), '_price',true);
			
			$attributes = $product->get_attributes();
			
			foreach ( $attributes as $attribute ) {
		        // if ( $attribute->get_variation() ) {	        	
		        //     continue;
		        // }
		        $name = $attribute->get_name();
		        
		        if($name == "pa_purete"){
		        	$termvalues = [];	
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[5] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_couleur"){
		        	$termvalues = [];	
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[4] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_forme"){
		        	$termvalues = [];	
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[2] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_poids"){
	        		$termvalues = [];	
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[3] = esc_html( implode( ', ', $termvalues ) );
	        	}


		        if ( $attribute->is_taxonomy() ) {
		            // $terms = wp_get_post_terms( $product->get_id(), $name, 'all' );
		            // $cwtax = $terms[0]->taxonomy;
		            // $cw_object_taxonomy = get_taxonomy($cwtax);
		            // if ( isset ($cw_object_taxonomy->labels->singular_name) ) {
		            //     $tax_label = $cw_object_taxonomy->labels->singular_name;
		            // } elseif ( isset( $cw_object_taxonomy->label ) ) {
		            //     $tax_label = $cw_object_taxonomy->label;
		            //     if ( 0 === strpos( $tax_label, 'Product ' ) ) {
		            //         $tax_label = substr( $tax_label, 8 );
		            //     }
		            // }
		            // $display_result .= $tax_label . ': ';
		            // $tax_terms = array();
		            // foreach ( $terms as $term ) {
		            //     $single_term = esc_html( $term->name );
		            //     array_push( $tax_terms, $single_term );
		            // }
		            // $display_result .= implode(', ', $tax_terms) .  '<br />';
		        } else {

		        	
		            // $display_result .= $name . ': ';
		            // $display_result .= esc_html( implode( ', ', $attribute->get_options() ) ) . '<br />';
		        }
		    }
		    $tmp[18] = get_post_meta($product->get_id(),  'product_discount',true);			
		    $tmp[6] = get_post_meta($product->get_id(),  'lab',true);			
		    $tmp[7] = get_post_meta($product->get_id(),  'cut',true);			
		    $tmp[8] = get_post_meta($product->get_id(),  'polish',true);			
		    $tmp[9] = get_post_meta($product->get_id(),  'symmetry',true);			
		    $tmp[10] = get_post_meta($product->get_id(),  'diametere_de_la_table',true);			
		    $tmp[11] = get_post_meta($product->get_id(),  'percentage_de_profondeur',true);			
		    $tmp[12] = get_post_meta($product->get_id(),  'flour',true);			
		    $tmp[13] = get_post_meta($product->get_id(),  'measurements',true);			
		    $tmp[14] = get_post_meta($product->get_id(),  'girdle__thick',true);			
		    $tmp[15] = get_post_meta($product->get_id(),  'girdle__thin',true);			
		    $tmp[16] = get_post_meta($product->get_id(),  'culet',true);			
			$tmp[19] = get_post_meta($product->get_id(),  'cert#',true);
			
			$tmp[20] = get_post_meta($product->get_id(),  'cert_link',true);
			$tmp[21] = get_post_meta($product->get_id(),  '360_video',true);
			

			for($i=0;$i<sizeof($fields);$i++){
				if($tmp[$i]==""||$tmp[$i]==undefined){
					$tmp[$i] = "";
				}
			}
			ksort($tmp);

	        array_push($values, $tmp);
	    endwhile;

	    wp_reset_query();
	}else{
		$fields = ['Name', 'Active','Category','Price','Purity','Color','Shape','Weight','Metal','Discount','SET','Gram','Nr Diamand','Description','Meta description','Finger Size','Number of images'];	

		fputcsv($outstream, $fields);  //creates the first line in the csv file
    
	    $args = array(
	        'post_type'      => 'product',
	        'posts_per_page' => -1,
	        'product_cat'    => $_POST['export_category']
	    );

		$values=array();
	    $loop = new WP_Query( $args );
		$term = get_term_by( 'slug', $_POST['export_category'], 'product_cat' );
		
	    while ( $loop->have_posts() ) : $loop->the_post();
	        global $product;
	        $tmp = array();
	        $tmp[0] = get_the_title();
	        $tmp[1]=get_post_meta( $product->get_id(), '_stock_status', true)=="instock"?1:0;
	        $tmp[2] = $term->name;
	        $tmp[3] = get_post_meta( $product->get_id(), '_price',true);
			
			$attributes = $product->get_attributes();
			
			foreach ( $attributes as $attribute ) {
		        // if ( $attribute->get_variation() ) {	        	
		        //     continue;
		        // }
		        $name = $attribute->get_name();
		        
		        if($name == "pa_purete"){		 
		        	$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[4] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_couleur"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[5] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_forme"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[6] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_poids"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[7] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_metal"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[8] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_serti"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[10] = esc_html( implode( ', ', $termvalues ) );
	        	}else if($name == "pa_taille-de-doigt"){
	        		$termvalues = [];
		        	foreach($attribute->get_options() as $option){
		        		$termvalues[] = get_term($attribute->get_options()[0])->name;
		        	}
	        		$tmp[15] = esc_html( implode( ', ', $termvalues ) );
	        	}
		        if ( $attribute->is_taxonomy() ) {
		            // $terms = wp_get_post_terms( $product->get_id(), $name, 'all' );
		            // $cwtax = $terms[0]->taxonomy;
		            // $cw_object_taxonomy = get_taxonomy($cwtax);
		            // if ( isset ($cw_object_taxonomy->labels->singular_name) ) {
		            //     $tax_label = $cw_object_taxonomy->labels->singular_name;
		            // } elseif ( isset( $cw_object_taxonomy->label ) ) {
		            //     $tax_label = $cw_object_taxonomy->label;
		            //     if ( 0 === strpos( $tax_label, 'Product ' ) ) {
		            //         $tax_label = substr( $tax_label, 8 );
		            //     }
		            // }
		            // $display_result .= $tax_label . ': ';
		            // $tax_terms = array();
		            // foreach ( $terms as $term ) {
		            //     $single_term = esc_html( $term->name );
		            //     array_push( $tax_terms, $single_term );
		            // }
		            // $display_result .= implode(', ', $tax_terms) .  '<br />';
		        } else {

		        	
		            // $display_result .= $name . ': ';
		            // $display_result .= esc_html( implode( ', ', $attribute->get_options() ) ) . '<br />';
		        }
		    }
		    $tmp[9] = get_post_meta($product->get_id(),  'product_discount',true);
	        $tmp[13] = get_the_content();				
			$tmp[14] = get_post_meta($product->get_id(),  'meta_decepraction',true);
			
			$tmp[11] = get_post_meta($product->get_id(),  'poids_dor_approximatif',true);
			$tmp[12] = get_post_meta($product->get_id(),  'nombre_de_diamants',true);
			$tmp[16] = sizeof($product->get_gallery_image_ids());

			for($i=0;$i<sizeof($fields);$i++){
				if($tmp[$i]==""||$tmp[$i]==undefined){
					$tmp[$i] = "";
				}
			}
			ksort($tmp);

	        array_push($values, $tmp);
	    endwhile;

	    wp_reset_query();
	}       

    foreach ($values as $value) {
	  fputcsv($outstream, $value);
	}
	
    // fputcsv($outstream, $values);  //output the user info line to the csv file
 
    fclose($outstream); 
    echo $path['url'].'/'.$filename;
	exit;
}

// Import new products from csv
add_action('wp_ajax_new_import_products', 'new_import_products_func');
add_action('wp_ajax_nopriv_new_import_products', 'new_import_products_func');

function new_import_products_func(){
	
	$url = $_POST['uploaded_url'];
	$perpage = $_POST['perpage'];
	$cupage = $_POST['currentpage'];
	$csv = array_map('str_getcsv', file($url));

    $res = array();
    $updatedFieldsCnt = 0;
    $foundCnt = 0;
    $notfoundCnt = 0;

    // array_walk($csv, function(&$a) use ($csv) {
    //   	$a = array_combine($csv[0], $a);
    // });
    // array_shift($csv);

    $header = $csv[0];
    
    $slicedheader = array_slice($csv, 1);

    $products = [];    

    $slicedcsv = array_slice($slicedheader, $cupage*$perpage, $perpage);

	foreach ($slicedcsv as $i => $row) {
		$tmp = [];
		foreach ($header as $j=>$key) {
			$tmp[$key] = $row[$j];
		}
		array_push($products, $tmp);
    }
        
    if($_POST['product_type_select']!='Diamand'){
    	$namefield = $_POST['Name'];
    	$descfield = $_POST['Description'];
    	$catfield = $_POST['Category'];
    	$stockStatusfield =$_POST['Active'];
    	$pricefield = $_POST['Price'];    	
    	$metaDescfield = $_POST['Meta_description'];
    	$noimgfield = $_POST['Number_of_images'];
    	$discountfield = $_POST['Discount'];
    	$colorfield = $_POST['Color'];
    	$shapefield = $_POST['Shape'];
    	$fingersizefield = $_POST['Finger_Size'];
    	$metalfield = $_POST['Metal'];
    	$purityfield = $_POST['Purity'];
    	$setfield = $_POST['SET'];
    	$weightfield = $_POST['Weight'];
    	$grammefield = $_POST['Gram'];
    	$nrdiamandfield = $_POST['Nr_Diamand'];


    	foreach ($products as $row){
	  		$pdctName = $row[$namefield];
	  		$pdctDesc = $row[$descfield];
	  		$pdctCat  = $row[$catfield];
	  		$pdctStockStatus = $row[$stockStatusfield];
	  		$pdctPrice = $row[$pricefield];	  			  		
	  		$noOfImages = $row[$noimgfield];

	  		// attributes
	  		$pdctWeight = $row[$weightfield];
	  		$pdctColor = $row[$colorfield];
	  		$pdctShape = $row[$shapefield];
	  		$pdctDiscount = $row[$discountfield];
	  		$pdctFingerSize = $row[$fingersizefield];
	  		$pdctMetal = $row[$metalfield];
	  		$pdctPurity = $row[$purityfield];
	  		$pdctSet = $row[$setfield];

	  		// ACF meta fields
			$pdctMetaDesc = $row[$metaDescfield];
			$pdctgram = $row[$grammefield];
			$pdctNrdiamand = $row[$nrdiamandfield];

			$args = array("post_type" => "product", "s" => $pdctName);
			$query = get_posts( $args );

			if(count($query)==1){
				$post_id = $query[0]->ID;

				  $my_post = array(
				      'ID'           => $post_id,
				      'post_content' => $pdctDesc,
				      'post_name' => sanitize_title_with_dashes($pdctName,'','save'),
				  );
				 
				// Update the post into the database
				wp_update_post( $my_post );
				$foundCnt++;
			}else if(count($query)==0){
				$notfoundCnt++;
				$post_id = wp_insert_post(array(
				    'post_title' => $pdctName,
				    'post_type' => 'product',
				    'post_author' =>  get_current_user_id(),
				    'post_staus' => 'publish', 
				    'post_name' => sanitize_title_with_dashes($pdctName,'','save'),
				    'post_content' => $pdctDesc,
				    'post_excerpt' => $pdctDesc
				));				
			}

			// delete all variations
			$args1 = array("post_parent"=>$post_id, "post_type"=>'product_variation');
			$loop1 = new WP_Query( $args1 );
			
		    while ( $loop1->have_posts() ) : $loop1->the_post();
		    	wp_delete_post(get_the_ID(), true);
		    endwhile;			

			//set category
			if($catfield!=""){
				wp_set_object_terms( $post_id, $pdctCat, 'product_cat' );
			}			
			wp_set_object_terms( $post_id, 'variable', 'product_type');

			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', $pdctStockStatus==1?'instock':'outstock');
			update_post_meta( $post_id, 'total_sales', '0' );
			update_post_meta( $post_id, '_downloadable', 'no' );
			update_post_meta( $post_id, '_regular_price', '');
			update_post_meta( $post_id, '_sale_price', '' );
			update_post_meta( $post_id, '_purchase_note', '' );
			update_post_meta( $post_id, '_featured', 'no' );
			update_post_meta( $post_id, '_weight',  '');
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
			update_post_meta( $post_id, '_sku', $pdctName );
			update_post_meta( $post_id, '_product_attributes', array() );
			update_post_meta( $post_id, '_sale_price_dates_from', '' );
			update_post_meta( $post_id, '_sale_price_dates_to', '' );
			update_post_meta( $post_id, '_price', $pdctPrice );
			update_post_meta( $post_id, '_sold_individually', '' );
			update_post_meta( $post_id, '_manage_stock', 'no' );				
			update_post_meta( $post_id, '_backorders', 'no' );
			if($metaDescfield!=""){
				update_post_meta($post_id,  'meta_decepraction', $pdctMetaDesc);
			}			

			if($noimgfield!=""){
				if($noOfImages!=0){
					// make images url from product name
					$imgpath = get_stylesheet_directory().'/product_images/';
					$extension = ".jpg";
					$imgname = $pdctName.$extension;
					$featuredImg = get_stylesheet_directory().'/product_images/'.$imgname;


					// $attid = attachment_url_to_postid($featuredImg);
					attach_product_thumbnail($post_id, $featuredImg, 0);

					$galleryImages = [];
					
					update_post_meta($post_id,'_product_image_gallery', "");
					
					for($i=0;$i<$noOfImages;$i++){
						if($i==0){
							$imgUrl = $imgpath.$imgname;	
							array_push($galleryImages, $imgUrl);
						}else{
							$imgname = $pdctName.'-'.$i.$extension;
							$imgUrl = $imgpath.$imgname;	
							array_push($galleryImages, $imgUrl);
						}					
					}
					foreach($galleryImages as $screenshots){
						//set gallery image
						attach_product_thumbnail($post_id, $screenshots, 1);
					}
				}	
			}				

			$attributesArray = [];

			if($purityfield!=""){
				array_push($attributesArray, 'purete');
				update_post_meta($post_id,  'pa_purett_display', sanitize_title_with_dashes($pdctPurity));
			}
			if($colorfield!=""){
				array_push($attributesArray, 'couleur');
				update_post_meta($post_id,  'pa_cauleurr_display', sanitize_title_with_dashes($pdctColor));
			}
			if($weightfield!=""){
				array_push($attributesArray, 'poids');	
				update_post_meta($post_id,  'poidsss_display', sanitize_title_with_dashes($pdctWeight));
			}
			if($shapefield!=""){
				array_push($attributesArray, 'forme');
				update_post_meta($post_id,  'pa_formee_display', sanitize_title_with_dashes($pdctShape));	
			}
			if($metalfield!=""){
				array_push($attributesArray, 'metal');
				$metalArray = explode (',' , $pdctMetal);
				update_post_meta($post_id,  'pa_metaltt_display', sanitize_title_with_dashes($pdctMetal));	
			}
			if($fingersizefield!=""){
				array_push($attributesArray, 'taille-de-doigt');
				$fingerSizes =  explode (',' , $pdctFingerSize);
			}

			$conPrice = floatval(str_replace(',', '.', str_replace('.', '', $pdctPrice)));
			$salesPrice = $conPrice * (100 - $pdctDiscount) / 100;

			$product_data = array(
			    'available_attributes' => $attributesArray,
			    'variations' => array(
			    	array(
				    	'attributes'=>array(
				    		'purete' =>array($pdctPurity), 
				        	'couleur' => array($pdctColor),
				        	'poids' =>array($pdctWeight),
				        	'forme'=>array($pdctShape),
				        	'metal'=>$metalArray,
				        	'taille-de-doigt'=>$fingerSizes
				        ),
				        'price' => $pdctPrice,
				        'sales_price' => $salesPrice
				    )
			    )
			);

			insert_product_attributes($post_id, $product_data['available_attributes'], $product_data['variations']); // Add attributes passing the new post id, attributes & variations
    		insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations

			// update meta fields
			if($grammefield!=""){
				update_post_meta($post_id,  'poids_dor_approximatif', $pdctgram);
			}			
			if($nrdiamandfield!=""){
				update_post_meta($post_id,  'nombre_de_diamants', $pdctNrdiamand);
			}				

			if($setfield!=""){
				update_post_meta($post_id,  'sertimeta', $pdctSet);
			}			
			if($discountfield!=""){
				update_post_meta($post_id,  'product_discount', $pdctDiscount);
			}			

			wp_publish_post($post_id);
	    }	
    }else{
		$namefield = $_POST['Name'];
    	$catfield = $_POST['Category'];
    	$pricefield = $_POST['Price'];    	
    	$colorfield = $_POST['Color'];
    	$shapefield = $_POST['Shape'];
    	$purityfield = $_POST['Purity'];
    	$weightfield = $_POST['Weight'];
    	$discountfield = $_POST['Discount'];
    	$labfield = $_POST['LAB'];
    	$cutfield = $_POST['Cut'];
    	$polishfield = $_POST['Polish'];
    	$symmetryfield = $_POST['Symmetry'];
    	$depthperfield = $_POST['Depth%'];
    	$tableperfield = $_POST['Table%'];
    	$certnofield = $_POST['Cert#'];
    	$flourfield = $_POST['Flour'];
    	$measurefield = $_POST['Measurements'];
    	$girdlethickfield = $_POST['Girdle_thick'];
    	$girdlethinfield = $_POST['Girdle_thin'];
    	$culetfield = $_POST['Culet'];
    	$certlinkfield = $_POST['Cert_Link'];
    	$video360field = $_POST['360_Video'];

    	$attachment_ids = [];

    	foreach ($products as $row){
	  		$pdctName = $row[$namefield];
	  		$pdctCat  = $row[$catfield];
	  		$pdctPrice = $row[$pricefield];	  			  		

	  		// attributes
	  		$pdctWeight = $row[$weightfield];
	  		$pdctColor = $row[$colorfield];
	  		$pdctShape = $row[$shapefield];
	  		$pdctPurity = $row[$purityfield];

	  		// ACF meta fields
			$pdctLab = $row[$labfield];
			$pdctCut = $row[$cutfield];
			$pdctDiscount = $row[$discountfield];
			$pdctPolish = $row[$polishfield];
			$pdctSymmetry = $row[$symmetryfield];
			$pdctDepthPer = $row[$depthperfield];
			$pdctTablePer = $row[$tableperfield];
			$pdctCertNo = $row[$certnofield];
			$pdctFlour = $row[$flourfield];
			$pdctMeasure = $row[$measurefield];
			$pdctGirdlethick = $row[$girdlethickfield];
			$pdctGirdlethin = $row[$girdlethinfield];
			$pdctCulet = $row[$culetfield];
			$pdctCertlink = $row[$certlinkfield];
			$pdct360Vid = $row[$video360field];		
			
			if($shapefield!=""){
				if(!array_key_exists($pdctShape, $attachment_ids)){
					$imgpath = get_stylesheet_directory().'/product_images/';
					$extension = ".jpg";
					if($pdctShape=="Rond Brillant"){
						$imgname = "round".$extension;
					}else{
						$imgname = strtolower($pdctShape).$extension;
					}					
					$featuredImg = $imgpath.$imgname;
					
					$attachment_ids[$pdctShape] = create_diamand_thumbnail($featuredImg);				
				}
			}			

			$args = array("post_type" => "product", "s" => $pdctName);
			$query = get_posts( $args );

			if(count($query)==1){
				$post_id = $query[0]->ID;

				  $my_post = array(
				      'ID'           => $post_id,
				      'post_name' => sanitize_title_with_dashes($pdctName,'','save'),
				  );
				 
				// Update the post into the database
				wp_update_post( $my_post );
				$foundCnt++;
			}else if(count($query)==0){
				$notfoundCnt++;
				$post_id = wp_insert_post(array(
				    'post_title' => $pdctName,
				    'post_type' => 'product',
				    'post_author' =>  get_current_user_id(),
				    'post_staus' => 'publish', 
				    'post_name' => sanitize_title_with_dashes($pdctName,'','save')
				));				
			}

			// delete all variations
			$args1 = array("post_parent"=>$post_id, "post_type"=>'product_variation');
			$loop1 = new WP_Query( $args1 );
			
		    while ( $loop1->have_posts() ) : $loop1->the_post();
		    	wp_delete_post(get_the_ID(), true);
		    endwhile;			

			//set category
			wp_set_object_terms( $post_id, $pdctCat, 'product_cat' );
			wp_set_object_terms( $post_id, 'variable', 'product_type');
			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', 'instock');
			update_post_meta( $post_id, 'total_sales', '0' );
			update_post_meta( $post_id, '_downloadable', 'no' );
			update_post_meta( $post_id, '_regular_price', '');
			update_post_meta( $post_id, '_sale_price', '' );
			update_post_meta( $post_id, '_purchase_note', '' );
			update_post_meta( $post_id, '_featured', 'no' );
			update_post_meta( $post_id, '_weight',  '');
			update_post_meta( $post_id, '_length', '' );
			update_post_meta( $post_id, '_width', '' );
			update_post_meta( $post_id, '_height', '' );
			update_post_meta( $post_id, '_sku', $pdctName );
			update_post_meta( $post_id, '_product_attributes', array() );
			update_post_meta( $post_id, '_sale_price_dates_from', '' );
			update_post_meta( $post_id, '_sale_price_dates_to', '' );
			update_post_meta( $post_id, '_price', $pdctPrice );
			update_post_meta( $post_id, '_sold_individually', '' );
			update_post_meta( $post_id, '_manage_stock', 'no' );				
			update_post_meta( $post_id, '_backorders', 'no' );

			if($shapefield!=""){	
				$thumb_id = $attachment_ids[$pdctShape];
				set_post_thumbnail($post_id, $thumb_id);			
			}			
			
			if($purityfield!=""){
				update_post_meta($post_id,  'pa_purett_display', sanitize_title_with_dashes($pdctPurity));
			}
			if($colorfield!=""){
				update_post_meta($post_id,  'pa_cauleurr_display', sanitize_title_with_dashes($pdctColor));
			}
			if($weightfield!=""){
				update_post_meta($post_id,  'poidsss_display', sanitize_title_with_dashes($pdctWeight));
			}
			if($shapefield!=""){
				update_post_meta($post_id,  'pa_formee_display', sanitize_title_with_dashes($pdctShape));	
			}
			
			if($metalfield!=""){			
				update_post_meta($post_id,  'pa_metaltt_display', sanitize_title_with_dashes($pdctMetal));	
			}
			$conPrice = floatval(str_replace(',', '.', str_replace('.', '', $pdctPrice)));
			$salesPrice = $conPrice * (100 - $pdctDiscount) / 100;

			$product_data = array(
			    'available_attributes' => array('purete', 'couleur','poids','forme'),
			    'variations' => array(
			    	array(
				    	'attributes'=>array(
				    		'purete' =>array($pdctPurity), 
				        	'couleur' => array($pdctColor),
				        	'poids' =>array($pdctWeight),
				        	'forme'=>array($pdctShape)
				        ),
				        'price' => $pdctPrice,
				        'sales_price' => $salesPrice
				    )
			    )
			);

			insert_product_attributes($post_id, $product_data['available_attributes'], $product_data['variations']); // Add attributes passing the new post id, attributes & variations
    		insert_product_variations($post_id, $product_data['variations']); // Insert variations passing the new post id & variations

			// After inserting post
			

			// update meta fields
			if($tableperfield!=""){
				update_post_meta($post_id,  'diametere_de_la_table', $pdctTablePer);
			}			
			if($depthperfield!=""){
				update_post_meta($post_id,  'percentage_de_profondeur', $pdctDepthPer);
			}
			
			if($labfield!=""){
				update_post_meta($post_id,  'lab', $pdctLab);
			}			
			if($cutfield!=""){
				update_post_meta($post_id,  'cut', $pdctCut);
			}			
			if($polishfield!=""){
				update_post_meta($post_id,  'polish', $pdctPolish);
			}			
			if($symmetryfield!=""){
				update_post_meta($post_id,  'symmetry', $pdctSymmetry);
			}			
			if($flourfield!=""){
				update_post_meta($post_id,  'flour', $pdctFlour);
			}			
			if($measurefield!=""){
				update_post_meta($post_id,  'measurements', $pdctMeasure);	
			}
			
			if($girdlethinfield!=""){
				update_post_meta($post_id,  'girdle__thin', $pdctGirdlethin);
			}
			if($girdlethickfield!=""){
				update_post_meta($post_id,  'girdle__thick', $pdctGirdlethick);			
			}			

			if($discountfield!=""){
				update_post_meta($post_id,  'product_discount', $pdctDiscount);
			}			

			if($culetfield!=""){
				update_post_meta($post_id,  'culet', $pdctCulet);
			}			
			if($certnofield!=""){
				update_post_meta($post_id,  'cert#', $pdctCertNo);	
			}
			
			if($certlinkfield!=""){
				update_post_meta($post_id,  'cert_link', $pdctCertlink);	
			}
			
			if($video360field!=""){
				update_post_meta($post_id,  '360_video', $pdct360Vid);	
			}
			

			wp_publish_post($post_id);
	    }	
    }    
    
    
    $res['updated'] = $foundCnt;
    $res['created'] = $notfoundCnt;
    $res['productType'] = $_POST['product_type_select'];
    $res['lastitem'] = ($cupage)*$perpage + sizeof($slicedcsv);
    $res['nextpage'] = ++$cupage;    
 	echo json_encode($res);  
 	exit;
}


// parse category meta description csv file to return mapping fields
add_action( 'wp_ajax_parse_newproducts_csv', 'parse_newproducts_csv_func' );
add_action( 'wp_ajax_nopriv_parse_newproducts_csv', 'parse_newproducts_csv_func' );
function parse_newproducts_csv_func(){
	$url = $_POST['url'];
	// $url = 'http://localhost/wordpress/wp-content/uploads/2020/06/Task-meta-description-3.csv';
	$pType = $_POST['productType'];

	if($pType!="Diamand"){
		$fields = ['Name', 'Active','Category','Price','Purity','Color','Shape','Weight','Metal','Discount','SET','Gram','Nr Diamand','Description','Meta description','Finger Size','Number of images'];	
	}else{
		$fields = ['Name', 'Category','Price','Purity','Color','Shape','Weight','LAB','Cut','Discount','Polish','Symmetry','Depth%','Table%','Flour','Measurements','Girdle thick','Girdle thin','Culet','Cert#','Cert Link','360 Video'];	
	}

	$csvAsArray = array_map('str_getcsv', file($url));
	$slicedheader = array_slice($csvAsArray, 1);

	$result['filecolumns'] = $csvAsArray[0];
	$result['fileurl'] = $url;
	$result['noofrows'] = sizeof($slicedheader);
	$result['productFields'] = $fields;
	echo json_encode($result);
	exit;	
}


function attach_product_thumbnail($post_id, $url, $flag){
    /*
     * If allow_url_fopen is enable in php.ini then use this
     */
    $image_url = $url;


    $url_array = explode('/',$url);
    $image_name = $url_array[count($url_array)-1];
    $image_data = file_get_contents($image_url); // Get image data
  /*
   * If allow_url_fopen is not enable in php.ini then use this
   */
  // $image_url = $url;
  // $url_array = explode('/',$url);
  // $image_name = $url_array[count($url_array)-1];
  // $ch = curl_init();
  // curl_setopt ($ch, CURLOPT_URL, $image_url);
  // // Getting binary data
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  // curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  // $image_data = curl_exec($ch);
  // curl_close($ch);
  	$upload_dir = wp_upload_dir(); // Set upload folder
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); //    Generate unique name
    $filename = basename( $unique_file_name ); // Create image file name
    // Check folder permission and define file location
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    // Create the image file on the server
    file_put_contents( $file, $image_data );
    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );
    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );


    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );
    // asign to feature image
    if( $flag == 0){
        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attach_id );
    }
    // assign to the product gallery
    if( $flag == 1 ){
        // Add gallery image to product
        $attach_id_array = get_post_meta($post_id,'_product_image_gallery', true);
        $attach_id_array .= ','.$attach_id;
        update_post_meta($post_id,'_product_image_gallery',$attach_id_array);
    }
}

function create_diamand_thumbnail($url){
    /*
     * If allow_url_fopen is enable in php.ini then use this
     */
    $image_url = $url;
    $url_array = explode('/',$url);
    $image_name = $url_array[count($url_array)-1];
    $image_data = file_get_contents($image_url); // Get image data
  /*
   * If allow_url_fopen is not enable in php.ini then use this
   */
  // $image_url = $url;
  // $url_array = explode('/',$url);
  // $image_name = $url_array[count($url_array)-1];
  // $ch = curl_init();
  // curl_setopt ($ch, CURLOPT_URL, $image_url);
  // // Getting binary data
  // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  // curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
  // $image_data = curl_exec($ch);
  // curl_close($ch);
  	$upload_dir = wp_upload_dir(); // Set upload folder
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); //    Generate unique name
    $filename = basename( $unique_file_name ); // Create image file name
    // Check folder permission and define file location
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    // Create the image file on the server
    file_put_contents( $file, $image_data );
    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );
    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );
    // asign to feature image
    
    return $attach_id;
}

function insert_product_attributes ($post_id, $available_attributes, $variations){

    foreach ($available_attributes as $attribute) // Go through each attribute
    {
        $values = array(); // Set up an array to store the current attributes values.

        foreach ($variations as $variation) // Loop each variation in the file
        {

            $attribute_keys = array_keys($variation['attributes']); // Get the keys for the current variations attributes

            foreach ($attribute_keys as $key) // Loop through each key
            {
                if ($key === $attribute) // If this attributes key is the top level attribute add the value to the $values array
                {
                    $values[] = $variation['attributes'][$key];
                }
            }
        }
        $values = array_unique($values); // Filter out duplicate values
        wp_set_object_terms($post_id, $values[0], 'pa_' . $attribute);
    }

    $product_attributes_data = array(); // Setup array to hold our product attributes data

    foreach ($available_attributes as $attribute) // Loop round each attribute
    {
        $product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'

            'name'         => 'pa_'.$attribute,
            'value'        => '',
            'is_visible'   => '1',
            'is_variation' => '1',
            'is_taxonomy'  => '1'

        );
    }

    update_post_meta($post_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
}

function insert_product_variations ($post_id, $variations){
    foreach ($variations as $index => $variation)
    {
        $variation_post = array( // Setup the post data for the variation

            'post_title'  => 'Variation #'.$index.' of '.count($variations).' for product#'. $post_id,
            'post_name'   => 'product-'.$post_id.'-variation-'.$index,
            'post_status' => 'publish',
            'post_parent' => $post_id,
            'post_type'   => 'product_variation',
            'guid'        => home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $index
        );

        $variation_post_id = wp_insert_post($variation_post); // Insert the variation
        foreach ($variation['attributes'] as $attribute => $value) // Loop through the variations attributes        
        {
        	if($attribute=="taille-de-doigt" || $attribute=="metal" || $attribute == "poids"){
        		// $attrValue = "";        	
            	// $attribute_term = get_term_by('name', $attrValue, 'pa_'.$attribute);        		
        	}else{        		        		
				$attrValue = $value[0];     				

        		if(! term_exists( $attrValue, 'pa_'.$attribute )){     
            		$addedTerm = wp_insert_term($attrValue, 'pa_'.$attribute);            		
            	}
            	$termIds = term_exists( $attrValue, 'pa_'.$attribute );
            	$termId = $termIds['term_id'];            	
            	$attribute_term = get_term($termId);            	
            	// get_term_by is not working properly with spaces
            	// $attribute_term = get_term_by('name', $attrValue, 'pa_'.$attribute);
            	// echo $attribute.'===>'.$attrValue.'====>'.$attribute_term->slug;
            	update_post_meta($variation_post_id, 'attribute_pa_'.$attribute, $attribute_term->slug);
        	}        	           
        }
		update_post_meta($variation_post_id, '_stock_status', 'instock');
        update_post_meta($variation_post_id, '_price', $variation['price']);
        update_post_meta($variation_post_id, '_regular_price', $variation['price']);
        update_post_meta($variation_post_id, '_sale_price', $variation['sales_price']);
    }
}