<?php 
/*
Plugin Name: Cursus Webhop
Plugin URI: http://www.sebsoft.nl
Description: Cursuswebshop |  Connect moodle course to wordpress Webshop
Author: Sebsoft.nl
Version: 1.2.0
Author URI: http://www.sebsoft.nl

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
 * Fase 2 / 3
 * -----------------------------------------------------------------
 * @todo	Verkomen dat meerder bankId geselecteerd kunnen worden
 * @todo	Uitgever weergeven 
 * @todo	Categorie filter
 * @todo	Niveau filter
 * @todo	Uitgever filter
 * @todo	DB install update voor nieuwe installatie 
 * -----------------------------------------------------------------
 * Op termijn
 * -----------------------------------------------------------------
 * @todo	Instelbaar Extra CSS voor de style van de shop
 * @todo	Shopping cart dynamice maken via ajax
 * 
 * 
 * @todo	Eigen stylesheet aan / uit functie 
 * @todo	Pay.nl Token function
 * @todo	__() function voor de stappen en cart
 * @todo	CW_SLUG option en niet een define
 * @todo	Losse producten in de tekst zetten om te bestellen.
 * @todo	Cron functie van WP gebruiken
 * @todo	Zoek pagina
 * @todo	Eigen shop gedeelte in adminpaneel
 * @todo	Aanbieden van andere producten
 * @todo	Een calculatie gebruiken voor alles en niet apart (cart / shoppingcart / paydata)
 * 	-----------------------------------------------------------------
 */
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) 
{ 
	die('You are not allowed to call this page directly.');
}

require_once 'config.php';

if (!class_exists('cwLoader')) 
{
	class cwLoader 
	{
		public $version     = '1.2.0';
		public $dbversion   = '1.2.0';
		public $minium_WP   = '3.1';
		public $donators    = 'http://www.sebsoft.nl/';
		public $options     = '';
		public $manage_page;
		public $add_PHP5_notice = false;
		//Get value
		private $cw_option;
		private $cw_id;
		private $cw_cat;
		private $cw_title;
		private $cw_show_one_product;
		
		function __construct() 
		{
			//@TODO it wil be faster if we load this only when needed
			$this->load_dependencies();
			$this->plugin_name = plugin_basename(__FILE__);
			
			$this->start_rewrite_module();
		
			// Init options & tables during activation & deregister init option
			register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
			register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );	
	
			// Register a uninstall hook to remove all tables & option automatic
			register_uninstall_hook( $this->plugin_name, array(&$this, 'uninstall') );
			
			// Start this plugin once all other plugins are fully loaded
			add_action( 'plugins_loaded', array(&$this, 'start_plugin') );
			
			//Adding shopping cart widget
			wp_register_sidebar_widget('cw_shopping_cart',__('shopping_cart','cursuswebshop'), array(&$this,'widget_cw_shopping_cart'));
			
			$this->handler();
		}
		
		/** -------------------------DEFAULT FUNCTIONS NEEDED FOR WP ---------------------------------- **/
		function check_for_upgrade()
		{
			if( get_option( 'cw_db_version' ) != CW_VERSION)
			{
				//@TODO upgrade					
			}
		}
	
		function handler()
		{
			 //Admin instellingen
			 add_action('admin_menu', 		array(&$this,'show_cw_adminmenu'));
			 add_filter('the_content', 		array(&$this, 'cw_content'));
			 add_shortcode( 'cw_webshop', 	array(&$this, 'show_cw_webshop') );
			 add_shortcode( 'cw_cat', 		array(&$this, 'show_cw_cat') );
			 add_shortcode( 'cw_product', 	array(&$this, 'show_cw_product') );
			 add_shortcode( 'cw_search', 	array(&$this, 'show_cw_search') );
		}
		
		function cw_content($content)
		{
			if(!isset($this->cw_option))
			{
				$this->cw_option 	=  (get_query_var('cw_option')) ? get_query_var('cw_option') : '';
				$this->cw_id		=  (get_query_var('cw_id')) ? get_query_var('cw_id') : '';
				$this->cw_title		=  (get_query_var('cw_title')) ? get_query_var('cw_title') : '';
				$this->cw_cat		=  (get_query_var('cw_cat')) ? get_query_var('cw_cat') : '';
				
				//Switch for testing if we have data to show one product
				if($this->cw_cat !=''&& $this->cw_title !=''&&$this->cw_id !='')
				{
					$this->cw_show_one_product = true;
				}
				else 
				{
					$this->cw_show_one_product = false;
				}
			}
			return $content;
		}
		
		function load_dependencies()
		{
			require_once (dirname (__FILE__) . '/classes/cart.php');
			require_once (dirname (__FILE__) . '/classes/CWdatabase.php');
			require_once (dirname (__FILE__) . '/classes/shop.php');
			require_once (dirname (__FILE__) . '/classes/validator.php');
			require_once (dirname (__FILE__) . '/classes/class.xmltoarray.php');
			require_once (dirname (__FILE__) . '/classes/import.class.php');
			require_once (dirname (__FILE__) . '/classes/PayNL_Transactions.php');
		}
		
		function start_rewrite_module() 
		{
			require_once (dirname (__FILE__) . '/classes/rewrite.php');
			global $cwRewrite;	

			if (get_option('permalink_structure') != '' ) 
			{
				if (class_exists('cwRewrite'))
				{
					$cwRewrite = new cwRewrite();	
				}
			}
			else
			{
				_e('Url rewrite is off','cursuswebshop');
			}
		}
		
		function deactivate()
		{
			delete_option( 'cw_active' );
		}
		
		function uninstall() 
		{	
			CWdatabase::cw_backup();
			CWdatabase::cw_uninstall();
		}	
		function start_plugin() 
		{
			global $cwRewrite;
			
		}
		function activate() 
		{
			global $wpdb;
	        if (version_compare(PHP_VERSION, '5.2.0', '<')) //Starting from version 1.8.0 it's works only with PHP5.2
	        { 
	                deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourself
	                wp_die(__("Sorry, but you can't run this plugin, it requires PHP 5.2 or higher.",'cursuswebshop')); 
					return; 
	        }
	        else 
	        {
				CWdatabase::cw_install($wpdb); 
	        }
	    	if ( is_multisite() ) 
	    	{
	    		//We don`t support this	
	    	} 
		}
		function show_cw_adminmenu()
		{
			add_options_page('Cursuswebshop options', 'Cursuswebshop', 'manage_options', 'cw-admin-UID', array(&$this,'cw_admin_plugin_options'));
		}
		
		function cw_admin_plugin_options()
		{
			if (!current_user_can('manage_options'))  
			{
				wp_die( __('You do not have sufficient permissions to access this page.','cursuswebshop') );
			}
			include 'admin/options.php';
		}
		
	    /** ----------------- WIDGET -----------------**/
		function widget_cw_shopping_cart() 
		{
			global $webshopName;
			// 
			echo '<li>
					<h2><a href="'.site_url().'/'.CW_SLUG.'/cart">'.__('shopping_cart','cursuswebshop').' </a></h2>
					<div class="widget-title">
						<a id="shoppingcarticon" href="'.site_url().'/'.CW_SLUG.'"><img src="'.CW_URL.'images/shop.png" alt="" /></a>'.CW_Cart::getCartForCw().'
					</div>
				</li>';
		}
		
	    /** ------------------- CW functions ---------------------------**/
	    function show_cw_product($atts)
		{
			extract(shortcode_atts(array('id'=> 0), $atts ));
			
			$out =  'Show one product:'.$id;
			return $out;
		}
		
		/** ----------------Show overview for all products-------------- */
		function show_cw_webshop()
		{
			global $programId,$websiteId,$locationId,$companyId,$username,$password;
			
			$out = '';
			//SHOWING breadcrumbs
	
			//SHOWING ONE PRODUCT
			if($this->cw_show_one_product)
			{
				if(is_numeric($this->cw_id))
				{
					$array 				= array();
					$array['productid'] = $this->cw_id;
					$array['catname']	= $this->cw_cat;
					$out = CW_shop::showShop($array);
				}
				else
				{
					echo 'a';
					$out = __('We dont have found a product','cursuswebshop');
				}
			}
			
			if($out == '')
			{
				switch($this->cw_option)
				{
					case 'import':
						$out = $this->cw_import_moodle();
					break;
					
					case 'addtocart':
						
						if(isset($_POST))
						{
							//Updating session
							$this->cw_add_to_cart($_POST);
							
							CW_shop::cw_header(site_url()."/".CW_SLUG."/cart/");
							die();
						}
						
						//Showing the shopping cart
						$out = CW_Cart::getCart();
					break;
					
					case 'cart':
						//Updating session
						if(isset($_POST['item'])&& !isset($_POST['checkout']))
						{
							$this->cw_cart_update($_POST);
							//Prevent strange double post
							CW_shop::cw_header(site_url()."/".CW_SLUG."/cart/");
							die();
						}
						elseif(isset($_POST['checkout']))
						{
							CW_shop::cw_header(site_url()."/".CW_SLUG."/step1");
						}
						else 
						{
							$out = CW_Cart::getCart();
						}
					break;
					
					case 'step1':
						
						$post = (isset($_POST['checkout'])) ? $_POST : false;
	
						CW_shop::storeCheckoutData($post);
						//validation
						if (CW_shop::checkoutDataFormValidator($post))
						{
							if($post)
							{
								CW_shop::cw_header(site_url()."/".CW_SLUG."/step2");
							}
						}
						
						$out = CW_shop::showCheckoutDataForm(true);
					break;
					
					case 'step2':
						//Updating session
						$out =  CW_shop::showPaymentForm($programId,$websiteId,$locationId,$companyId,$username,$password,false);
					break;
					
					case 'step3':
						if(isset($_POST))
						{
							// storage
							$aInputFields = array();
							$aInputFields[] = array('name'=>'paymentProfile','required'=>true);
							$aInputFields[] = array('name'=>'bankId');
						}	
						if(!CW_shop::storeCheckoutData($_POST, $aInputFields))
						{
							echo 'Failed selection not completed';
						}
						else
						{
							CW_shop::createTransactionAndRedirect($programId,$websiteId,$locationId,$companyId,$username,$password);
						}
					break;
					
					case 'actioncodecheck':
						$contents = ob_get_contents();
						ob_end_clean();
						//validation couponcode JSON
						echo CW_Cart::jsonCodeValidator();
						die();
					break;
					
					case 'returnnopay'://paid failed
						$out = CW_Shop::showThankYouPage(false);
					break;
					
					case 'returnpaid'://paid succesfully
						$out = CW_Shop::showThankYouPage(true);
					break;
					
					case 'transactionerror'://something is wrong
						$out = CW_Shop::showTransactionErrorPage();
					break;
					
					case 'banktransaction'://show bankinformation
						$out = CW_Shop::showBankPaymentPage();
					break;
					
					case 'exchange':
						//Prevent header
						$contents = ob_get_contents();
						ob_end_clean();
						echo "true| WP cursus webshop";
						die();
					break;
					
					case 'email-backup': //Mail settings of this installation
						
						if (!current_user_can('manage_options'))  
						{
							CWdatabase::cw_backup();
						}
							
					break;
					
					case 'reset': //Reset shopping cart
						CW_Cart::resetCart();
						$array 				= array();
						$array['catid'] 	= 0;
						$out = CW_shop::showShop($array);
					break;
					
					case 'return':
						$paynl = new PayNL_Transactions($programId,$websiteId,$locationId,$companyId,$username,$password);
						$res = $paynl->getPaymentStatus(intval($_GET['paymentSessionId']));
						
						if ($res['status'] == "PAID")
						{
							CW_shop::cw_header(site_url()."/".CW_SLUG."/returnpaid");
						}
						else
						{
							CW_shop::cw_header(site_url()."/".CW_SLUG."/returnnopay");
						}
						die();				
					break;
					//AJAX for comments
					case 'ajax':
						$contents = ob_get_contents();
						ob_end_clean();
						
						$courseid = !empty($_GET['courseid']) ? $_GET['courseid'] : 0 ; 
						CW_shop::ajaxReview($courseid);
					break;
					
					case '':
						$array 				= array();
						$array['catid'] 	= 0;
						
						$out = CW_shop::showShop($array);
					break;
					default:
						//oke we must try to find category
						
						//convert string to cat id
						if($catid > 0)
						{
							//$this->cw_option	
							$array 				= array();
							$array['catid'] 	= 0;
							
							//find cat
							$out = CW_shop::showShop($array);
						}
						else
						{
							//maybe a education	
							if($education)
							{
								
							}
							else 
							{
								$out = __('We dont have found a product','cursuswebshop');
							}
						}
				
					break;
				}
			}
			$first = CW_shop::cw_breadcrumbs(true);
			
			return $first.$out;
		}
		/** ------------------------------------ **/
		function cw_add_to_cart(array $post) 
		{
			//adding to cart session
			if (isset($post['quantity']) && isset($post['productid']))
			{
				CW_Cart::addItemToCart(intval($post['productid']),intval($post['quantity']));
			}
		}
		
		function cw_cart_update(array $post)
		{
			//update cart session
			foreach($post['item'] as $id => $count)
			{
				CW_Cart::updateItemInCart(intval($id),intval($count));	
			}
		}
		function cw_import_moodle()
		{
			global $basemoodle;
			if(empty($basemoodle))
			{
				_e('baseurl-empty','cursuswebshop');
			}
			$import = new import_courses($basemoodle);
			$output = $import->saveToDB();
			return $import->output();
		}
	}

	global $CW;
	$CW = new cwLoader();
}
?>