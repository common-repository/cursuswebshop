<?php 
/**
 * @desc 		Cursus Webshop
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.2
 * @since		2011
 */
//Long posts should require a higher limit, see http://core.trac.wordpress.org/ticket/8553
//@ini_set('pcre.backtrack_limit', 500000);
if(!isset($_SESSION)) 
{
	session_start();
}
ob_start();
load_plugin_textdomain('cursuswebshop', false, dirname( plugin_basename(__FILE__) ) . '/langs');
//lang default for where var is used
__('firstname','cursuswebshop');
__('lastname','cursuswebshop');
__('address','cursuswebshop');
__('organisation','cursuswebshop');
__('postalcode','cursuswebshop');
__('city','cursuswebshop');
__('email','cursuswebshop');
//-------------Defines
define('CW_FOLDER', 			dirname(plugin_basename(__FILE__)));
define('CW_ABSPATH', 			trailingslashit( str_replace("\\","/", WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) ) ) );
define('CW_MERLIN_EXCHANGE',	'http://merlin.sebsoft.nl/v1/cws_exchange.php');
define('CW_MERLIN_ADDING',		'http://merlin.sebsoft.nl/v1/cws_add.php');
define('CW_DISPLAY_NAME',		__('cw_display_name','cursuswebshop'));
//define('WP_CART_FOLDER',		'cursuswebshop/');
//
define('CW_URL', 				trailingslashit( plugins_url( '', __FILE__ ) ) );
define('CW_VERSION',			2011010602);
define('CW_OUTPAYMENT',			'ppt'); //ppp =PayProductProduct//ppt = PayProductTransaction
define('CW_SANDBOX_MODE',		true);//Pay.nl sandboxmodus
define('CW_SLUG',				'webshop');
define('SEBSOFT_DEBUG',			false);//debug
define('SEBSOFT_MAIL',			'luuk@sebsoft.nl');

//Loads the plugin's translated strings. 
load_plugin_textdomain( 'cursuswebshop', null, CW_FOLDER);

//------Getting default settings from WP
$companyId 	= get_option('paynlcompanyid');
$username 	= get_option('paynlusername');
$password 	= get_option('paynlpassword');
$programId 	= get_option('paynlprogramid');
$websiteId 	= get_option('paynlwebsiteid');
$locationId = get_option('paynlwebsitelocationid');
$basemoodle = get_option('moodlesitelocation');

$moodleSiteKey 	= get_option('moodlesitekey');
$merlinCode 	= get_option('merlincode');
$companyname	= get_option('companyname');

$cw_setting	= array();
$cw_setting = array('companyid'=> $companyId,'username'=> $username,'password'=> $password,
					'programid'=> $programId,'websiteid'=> $websiteId,
					'locationid'=> $locationId,'basemoodle'=> $basemoodle,'moodlesitekey'=> $moodleSiteKey,
					'merlincode'=> $merlinCode,'companyname'=> $companyname);

if(SEBSOFT_DEBUG)
{
	//ini_set('display_errors', '1');
 	//ini_set('error_reporting', E_ALL);
 	global $wpdb;
	$wpdb->show_errors();
}

wp_enqueue_style('style', CW_URL.'style.css');
wp_enqueue_script( 'jquery' );
wp_enqueue_script('cw_js',CW_URL.'js/jquery.ajax.js');
wp_enqueue_script('cw_js_cart',CW_URL.'js/cart.jquery.js');
?>