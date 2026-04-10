<?php
/*
==========================================
	 Include scripts
==========================================
*/
function konato_script_enqueue() {
    //CCS STYLSHEET
    wp_enqueue_style('bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '3.3.4', 'all');
    wp_enqueue_style('customstyle', get_template_directory_uri() . '/css/', array(), '1.0.0', 'all');
    wp_enqueue_style('style-konato', get_template_directory_uri() . '/css/style-konato.css', array(), '1.0.0', 'all');
    wp_enqueue_style('load-simple-line-icons', 'https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.css');
    wp_enqueue_style('load-fa', 'https://use.fontawesome.com/releases/v5.3.1/css/all.css');

    //JAVASCRIPT FILE
    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrapjs', get_template_directory_uri() . '/js/bootstrap.min.js', array(), '3.3.4', true);
	wp_enqueue_script('customjs', get_template_directory_uri() . '/js/konato_js.js', array(), '1.0.0', true);

}
add_action('wp_enqueue_scripts', 'konato_script_enqueue');

/*
==========================================
	 Activate menus
==========================================
*/
function navigation_konato_setup(){
    //Menu toevoegen
    add_theme_support('menus');

    register_nav_menu('primary', 'Header Navigation');
	register_nav_menu('secondary', 'Footer Navigation');
	register_nav_menu('tertiary', 'Legal Navigation');


}

add_action('init', 'navigation_konato_setup');

/*
	==========================================
	 Theme support function
	==========================================
	
*/

//custom header image

//custom blog post thumbnail image
add_theme_support('post-thumbnails');

add_theme_support('post-formats', array( 'aside', 'gallery' ));

// Add other useful image sizes for use through Add Media modal
add_image_size( 'medium-small-square', 480, 480 );
add_image_size( 'medium-large-square', 900, 900 );

// Register the three useful image sizes for use in Add Media modal
add_filter( 'image_size_names_choose', 'wpshout_custom_sizes' );
function wpshout_custom_sizes( $sizes ) {
    return array_merge( $sizes, array(
        'medium-small-square' => __( 'Medium Small Square' ),
        'medium-large-square' => __( 'Medium Large Square' ),
    ) );
}
/*
	==========================================
	 Custom logo function
	==========================================
*/
function custom_logo_setup($width = 155, $height = 55) {
	add_theme_support( 'custom-logo' );

	$defaults = array(
		'height'      =>  absint( $height ),
		'width'       =>  absint( $width ),
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => array( 'site-title', 'site-description' ),
	);
	add_theme_support( 'custom-logo', $defaults );
}
add_action( 'after_setup_theme', 'custom_logo_setup' );


/**
 * ===============================================
 * Register widget
 *===================================================
 */
function arphabet_widgets_init() {

	register_sidebar( array(
		'name'          => 'Office Hours ',
		'id'            => 'office_hours',
	) );
}
add_action( 'widgets_init', 'arphabet_widgets_init' );



/*
==========================================
	Creating a function to create our Custom Post Type
==========================================
*/

function all_custom_post_types() {

	$types = array(

		// Job
		array('the_type' => 'job',
		      'single' => 'Job',
		      'plural' => 'Jobs'),

		// Freelance Job
		array('the_type' => 'freelance-job',
		'single' => 'FreelanceJob',
		'plural' => 'Freelance Jobs'),

		// Teamleden
		array('the_type' => 'recruiters',
		      'single' => 'Recruiter',
		      'plural' => 'Recruiters'),

		// Teamleden
		array('the_type' => 'teamleden',
		      'single' => 'Teamlid',
		      'plural' => 'Teamleden'),

		// Testimonial
		array('the_type' => 'testimonial',
		      'single' => 'Testimonial',
		      'plural' => 'Testimonials'),

		// Service
		array('the_type' => 'service',
		      'single' => 'Service',
			  'plural' => 'Services'),

	);

	foreach ($types as $type) {

		$the_type = $type['the_type'];
		$single = $type['single'];
		$plural = $type['plural'];

		$labels = array(
			'name' => _x($plural, 'post type general name', 'Konato'),
			'singular_name' => _x($single, 'post type singular name', 'Konato'),
			'add_new' => _x('Add New', $single, 'Konato'),
			'add_new_item' => __('Add New '. $single, 'Konato'),
			'edit_item' => __('Edit '.$single, 'Konato'),
			'new_item' => __('New '.$single, 'Konato'),
			'view_item' => __('View '.$single, 'Konato'),
			'search_items' => __('Search '.$plural, 'Konato'),
			'not_found' =>  __('No '.$plural.' found', 'Konato'),
			'not_found_in_trash' => __('No '.$plural.' found in Trash', 'Konato'),
			'parent_item_colon' => __(''),
			'menu_name'  => __( $plural, 'Konato' ),
			'all_items' => __( 'All'.' '.$plural, 'Konato' ),
			'update_item' => __( 'Update'.$single, 'Konato' ),
		);

		$args = array(
			'label' => __( $single, 'Konato' ),
			'description' => __( $single, 'Konato' ),
			'labels' => $labels,
			'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),

			'hierarchical' => false,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'query_var' => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'capability_type'     => 'page',
			'rewrite' => true,

		);

		register_post_type($the_type, $args);

	}

}

/* Hook into the 'init' action so that the function
* Containing our post type registration is not
* unnecessarily executed.
*/

//add_action( 'init', 'custom_post_type', 0 );

// ADDING CUSTOM POST TYPE
add_action('init', 'all_custom_post_types');

if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page();
	
	acf_add_options_sub_page('Actions');
	acf_add_options_sub_page('High Quality');
	
}

function pagination_bar( $query_wp ) 
{
    $pages = $query_wp->max_num_pages;
    $big = 999999999; // need an unlikely integer
    if ($pages > 1)
    {
        $page_current = max(1, get_query_var('paged'));
        echo paginate_links(array(
            'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format' => '?paged=%#%',
            'current' => $page_current,
            'total' => $pages,
        ));
    }
}

function custom_file_download( $url, $type = 'json' ) {
   
    // Set our default cURL options.
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
  
	$headers = array();
    $headers[] = "Authorization: Token 53feceea7142401bc9a46283cd4c425e";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Retrieve file from $url.
    $result = curl_exec( $ch );

    // Return error if cURL fails.
    if ( curl_errno( $ch ) ) {
        exit( 'Error:' . curl_error( $ch ) );
    }
    curl_close( $ch );

    // Identify the upload directory path.
    $uploads  = wp_upload_dir();

    // Generate full file path and set extension to $type.
    $filename = $uploads['basedir'] . '/' . strtok( basename( $url ), "?" ) . '.' . $type;

    // If the file exists locally, mark it for deletion.
    if ( file_exists( $filename ) ) {
        @unlink( $filename );
    }

    // Save the new file retrieved from FTP.
    file_put_contents( $filename, $result );

    // Return the URL to the newly created file.
    return str_replace( $uploads['basedir'], $uploads['baseurl'], $filename );

}

add_action( 'http_api_curl', 'my_setopt_http_api_curl', 10, 3 ); 

function my_setopt_http_api_curl( $handle, $r, $url ) {     
     curl_setopt( $handle, CURLOPT_HTTPHEADER, array(
            'Authorization: Token 53feceea7142401bc9a46283cd4c425e'
     ) );
}