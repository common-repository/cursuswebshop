<?php
class cwRewrite {

	private $flush = false;
	function __construct() 
	{
		global $wp_rewrite;
        $this->slug = CW_SLUG;
        
		add_filter( 'rewrite_rules_array',array(&$this,'cw_insert_rewrite_rules')); //adding in admin
		add_filter( 'query_vars',array(&$this,'cw_insert_query_vars' ));
		add_action( 'wp_loaded',array(&$this,'cw_flush_rules' ));
	}

	function cw_flush_rules()
	{
		//Adding the new rules if not there
		global $wp_rewrite;
		$rules = get_option( 'rewrite_rules' );

		if (!isset( $rules[CW_SLUG.'/([^/]+)/([^/]+)/([^/]+)/?$'] ) || $this->flush)
		{
			global $wp_rewrite;
		   	$wp_rewrite->flush_rules();
		   	echo 'FLUSHED - CW - ';
		}
	}
	
	// Adding a new rule
	function cw_insert_rewrite_rules( $rules )
	{
		global $wp_rewrite;
		$newrules = array();
		//courseid->option->title
		$newrules[CW_SLUG.'/([^/]+)/([^/]+)/([^/]+)/?$'] 	= 'index.php?pagename='.CW_SLUG.'&cw_id=$matches[1]&cw_cat=$matches[2]&cw_title=$matches[3]'; //getting a course with id
		//option //courseid->cat->title
		$newrules[CW_SLUG.'/([^/]+)/?$'] 					= 'index.php?pagename='.CW_SLUG.'&cw_option=$matches[1]'; //default for use with one param
		//$newrules[CW_SLUG.'/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.CW_SLUG.'&cw_id=$matches[1]&cw_cat=$matches[2]&cw_title=$matches[3]'; //getting a course with id
		return $newrules + $rules;
	}
	
	// Adding params
	function cw_insert_query_vars( $vars )
	{
		global $wp_rewrite;
	  	$vars[] =  	'cw_id';
	  	$vars[] =  	'cw_title';
	    $vars[] = 	'cw_option';
	    $vars[] = 	'cw_cat';
	    $vars[] = 	'cw_page';
	    return $vars;
	}
}
?>