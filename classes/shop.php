<?php
/**
 * @desc 		Cursus Webshop
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.2
 * @since		2011
 */
class CW_shop
{
	static private $errors = array();
	static public $run;
	static public function showShop($array)
	{
		$switch = '';
		if(isset($array['catid'])&&is_numeric($array['catid']))
		{
			$products 		= CWdatabase::getProductsByCategory($array['catid']);
			$switch			= 'cat';
		}
		elseif(isset($array['productid'])&&is_numeric($array['productid']))
		{
			$products 		= CWdatabase::getProductsByCategory(0,$array['productid']);
			$switch			= 'product';
		}
		// Get all products and show them below eachother
		
		$cw_apic ='';
		$return = '';
		
		if (count($products)>0)
		{
			$count=1;
			$return.= '<ul id="cw_productlist">';

			foreach($products as $product)
			{
				self::shopCrumb($product,$switch,$array['catname']);
				if (SEBSOFT_DEBUG)
				{
					echo "<pre>";
					print_r($product);
					echo"</pre>";
				}
				
				$product->price 		= $product->price;
				$product->vat 			= $product->vat;		// price with vat
				$price 					= $product->price;	// price still in cents
				
				$description 			= !empty($product->description) ? $product->description :  __('noproducts','cursuswebshop');
				$items 					= CWdatabase::getAc($product->courseid);
				
				if(!empty($items))
				{
					foreach($items as $item)
					{
						$cw_apic .='<li><img height="75" src="'.$item->pic.'" alt="'.$item->alt.'"/><h3>'.$item->alt.'</h3></li>';
					}
				}
				$return.= '<li>
							<div id="cw_top_title">
								<h1><a href="'.site_url().'/'.CW_SLUG.'/'.$product->unique_id.'/'.self::cw_url($product->category).'/'.self::cw_url($product->name).'/">'.ucfirst($product->name).'</a></h1>
							</div>
							<span id="cw_education"><b>'.__('education','cursuswebshop').': </b>'.$product->education.'</span>&nbsp;&nbsp;&nbsp;
							<span id="cw_catogorie"><b>'.__('category','cursuswebshop').': </b>'.$product->category.'</span>
							'.self::getStarsCourse($product->courseid).'
							<div id="cw_productdescription">'
								.htmlspecialchars_decode($description).
							'</div>
							<ul class="cw_acpic">
									'.$cw_apic.'
							</ul>
							<div id="cw_bottom_price">
								'.self::showDiscount($product,'shop').'
								<div id="cw_priceinfo">
									<strong>
									'.self::getPrice($product,true,true).'
									</strong>
								</div>
							  	<div id="cw_addtocart">
									<form method="post" width="100" action="'.site_url().'/'.CW_SLUG.'/addtocart/">
										<input type="hidden" name="productid" value="'.$product->id.'" />
										<input type="text" name="quantity" class="text ui-widget-content ui-corner-all" class="cw_count" value="1" />
										<input class="cw_button" type="submit" value="'.__('addtocart','cursuswebshop').'" />
							 		</form>
							 	</div>
						 	</div>
						 	<div class="coursereview" id="cw_rev'.$count.'"></div>
						</li>';
				$count++;
			}
			$return.='</ul>';
		}
		if(empty($return))
		{
			$return = __('noitemsinshop','cursuswebshop');
		}
		return $return.'<br style="clear:both;"/>';
	}
	
	
	
	static private function shopCrumb($product=false,$switch,$catname='')
	{
		if(self::$run!=true)
		{
			//ADDING products to the breadcrumbs 
			if($switch=='cat')
			{
				$_SESSION['cw_urls'][1][__('all-cat','cursuswebshop')] 	= site_url().'/'.CW_SLUG.'/';	
			}
			elseif($switch=='product')
			{
				//een product
				$_SESSION['cw_urls'][1][$catname] 	= site_url().'/'.CW_SLUG.'/'.$catname.'/';	
				$_SESSION['cw_urls'][2][$product->name] 	= site_url().'/'.CW_SLUG.'/'.$product->unique_id.'/'.$catname.'/'.self::cw_url($product->name).'/';	
			}
			elseif($switch=='step1' || $switch=='step2' || $switch=='step3')
			{
				if($switch=='step1' || $switch=='step2' || $switch=='step3')
				{
					$_SESSION['cw_urls'][1][__('Personal information','cursuswebshop')] 	= site_url().'/'.CW_SLUG.'/step1/';	
				}
				if($switch=='step2' || $switch=='step3')
				{
					$_SESSION['cw_urls'][2][__('Payment','cursuswebshop')] 	= site_url().'/'.CW_SLUG.'/step2/';	
				}
			} 
		
			//adding one time run
			self::$run = true;
		}
	} 
	
	static public function getStarsCourse($courseid)
	{
		$stars = CWdatabase::getStarsDB($courseid);
		$extra='';
		$review = ''; 
		if($stars)
		{
			$ratingdiv = self::ratingDiv($stars);
			$count = CWdatabase::getCommentCount($courseid);
			
			if($count)
			{
				$count = $count;
			}
			else 
			{
				$count = 0;
			}
			$review = '<a class="cw_commentcount" href="'.site_url().'/'.CW_SLUG.'/ajax?courseid='.$courseid.'">'.__('comments','cursuswebshop').' ('.$count.')</a>';
		}
		else 
		{
			$extra 				= '<b class="cw_commentcount">'.__('norating','cursuswebshop').'</b>';
			$ratingdiv 			= '<div class="cw_rating">'.$extra.'</div>';
		}
		
		return $ratingdiv.$review;
	}
	
	
	static private function ratingDiv($stars)
	{
		if(is_numeric($stars))
		{
			$nr = -160 + (-16*$stars);
			$style = 'style="background:url('.CW_URL.'images/stars.png) no-repeat 0 '.$nr.'px;"';
			return '<div class="cw_rating" '.$style.'></div>';
		}
	}
	
	static public function ajaxReview($courseid)
	{
		if(is_numeric($courseid) && $courseid!=0)
		{
			$comments = CWdatabase::getComment($courseid);
			
			if(!empty($comments))
			{
				echo '<ul>';
				foreach($comments as $comment)
				{
					echo '<li>
							<div class="cw_review_holder">'.
							 self::ratingDiv($comment->stars).
								 '<div class="cw_time">'.date("F j, Y, g:i a",$comment->addedon).'</div>
							</div>
							<div class="cw_review">'.htmlspecialchars($comment->comment).'</div>
						  </li>';	
				}
				echo '</ul>';
			}
		}
		die();
	}
	/**
	 * 
	 * ShowDiscount Rules
	 * @param string $place
	 */
	static private function showDiscount($product, $place = 'shop')
	{
		$return = '';
		if($place=='shop')
		{
			$rules = CWdatabase::getDiscount($product->unique_id);
			
			if($rules)
			{
				$return .= '<ul class="cw_discount_rules">';
				$productPrice 	=  ($product->price / 100 * $product->vat) + $product->price;
				foreach($rules as $rule)
				{
					$price 			= ($productPrice / 100) * (100 - $rule->procent);
					$return 		.='<li>	
										<span class="cw_discount_1">'.self::getPrice($product,true,true).'</span>
										<span class="cw_discount_2">'.get_option('cw_currency','&euro;')." ".number_format($price,2,',','.').'</span>
										<span class="cw_discount_3">'.__('by','cursuswebshop').' '.$rule->count.' '.__('or more.','cursuswebshop').'</span>
									</li>';
				}
				$return .='</ul>'; 
			}
			$return .= $productid;
		}
		elseif($place=='cart')
		{
			
		}
		
		return $return;
	}
	
	static public function getPrice($product,$includevat=true,$includecurrency=true,$includeprice=true)
	{
		if ($includeprice == false)
		{
			return get_option('cw_currency','&euro;')." ";
		}
		//round(1.95583, 2); 
		$product->price = round($product->price, 2);
		
		if(!empty($product->vat))
		{
			$product->vat = round($product->vat,2);
		}
		// price with vat
		if ($includevat)
		{
			$price = ($product->price / 100 * $product->vat) + $product->price;
		}
		else
		{
			$price = $product->price;
		}
		
		// price still in cents
		$price = $price;
		$return = number_format($price,2,',','.');
		
		if ($includecurrency)
		{
			$return = get_option('cw_currency','&euro;')." ".number_format($price,2,',','.');
		}
		return $return;
	}
	
	static public function getDiscountPrice($product , $itemcount=1, $mode='text',$actioncode=0)
	{
		if($rules = CWdatabase::getDiscount($product->unique_id))
		{
			//Kijken welke rule we kunnen gebruiken
			foreach($rules as $rule)
			{
				if($itemcount >= $rule->count)
				{
					$sales = $rule;
				}
			}
			//Discount sales
			if(!empty($sales))
			{
				if($actioncode >0)
				{
					$class = 'cw_price_old';
				}
				else
				{
					$class = 'cw_new_price';
				}
				$priceInt 		 = ($product->price / 100) * (100 - $sales->procent);
				$priceString = '<span class="cw_price_old">'.get_option('cw_currency','&euro;').' '.number_format($product->price,2,',','.').'</span> 
								<span class="'.$class.'"> '.get_option('cw_currency','&euro;').' '.number_format($priceInt,2,',','.').'</span>';
				//Calc new price 
			}
			else
			{
				//no sales
				$priceString ='';
			}
		}
		else 
		{
			$return = false;
		}
		
		if($mode == 'text')
		{
			$return = $priceString;
		}
		elseif($mode == 'int')
		{
			$return = $priceInt;
		}
		
		return $return;
	}
	
	static private function getCheckoutDataFormFields()
	{
		$aInputFields = array();
		$aInputFields[] = array('name'=>'organisation');
		$aInputFields[] = array('name'=>'firstname','required'=>true);
		$aInputFields[] = array('name'=>'lastname','required'=>true);
		$aInputFields[] = array('name'=>'address','required'=>true);
		$aInputFields[] = array('name'=>'postalcode','size'=>7,'required'=>true);
		$aInputFields[] = array('name'=>'city','required'=>true);
		$aInputFields[] = array('name'=>'email','required'=>true, 'validatortype'=>'email');
		$aInputFields[] = array('name'=>'newsletter','type'=>'checkbox');
		$aInputFields[] = array('name'=>'wants_report','type'=>'checkbox');
		return $aInputFields;
	}
	
	static public function showCheckoutDataForm($doDisplay=false)
	{
		
		self::shopCrumb(false,'step1','');
		
		$aInputFields = self::getCheckoutDataFormFields();
		
		$variables = array();
		foreach($aInputFields as $field)
		{
			if (isset($_SESSION['CW_ORDERDATA'][$field['name']]))
			{
				$variables[$field['name']] = $_SESSION['CW_ORDERDATA'][$field['name']];
			}
			else
			{
				$variables[$field['name']] = '';
			}
		}
		$errors = array();
		
		$return = '';
		$return.="<div id='cw_checkoutformdisplay'>\n";
		$return.="<form method='post' class=\"cw_form_jquery\" action='".site_url()."/".CW_SLUG."/step1' id='cw-form-user'><fieldset>";
		$return.="<table id='cw_checkoutformdisplaytable'>";
		
		foreach($aInputFields as $field)
		{
			$size = 25;
			if (isset($field['size']))
			{
				$size = $field['size'];
			}
			if (isset($field['type']) && $field['type'] == 'checkbox')
			{
				if($field['name']=='newsletter')
				{
					$return.="<tr><td><b>".__('newsletter','cursuswebshop')."</b></td><td><input type='checkbox' value='true' name='newsletter'>";
				}
				else
				{
					$return.="<tr><td><b>".__('wants_report','cursuswebshop')."</b></td><td><input type='checkbox' value='true' name='wants_report'>";
				}
			}
			else 
			{
				$return.=self::showCheckoutDataFormLine($field['name'],$variables,$size,self::$errors);
			}
		}
		$return.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
				  <tr><td><input  class=\"cw_button\" type='button' name='goback' value='".__('goback','cursuswebshop')."' onClick=\"window.location = '".site_url()."/".CW_SLUG."/cart'\" /></td><td>
				  <input class=\"cw_button\" type='submit' name='checkout' value='".__('continuecheckout','cursuswebshop')."' /></td></tr>
				</table>
				</fieldset>
				</form>
				</div>";

		return $return;
	}
	
	static private function showCheckoutDataFormLine($name,array $variables,$size=15,array $errors=array())
	{
		$value = '';
		if (isset($variables[$name]))
		{
			$value = $variables[$name];
		}
		
		$class = 'cw_inputfield';
		if (isset($errors[$name]))
		{
			$class = 'cw_inputfield cw_inputfielderror';
		}
		
		$return = '';
		if($name=='organisation')
		{
			$return.="<tr><td><b>".__($name,'cursuswebshop')."</b></td><td><input type='text' name='$name' class='$class' value='$value' size='$size' /></td></tr>\n";
		}
		else 
		{
			$return.="<tr><td><font style=\"color:red\">* </font> <b>".__($name,'cursuswebshop')."</b></td><td><input type='text' name='$name' class='$class' value='$value' size='$size' /></td></tr>\n";
		}
		return $return;
	}
	
	static public function checkoutDataFormValidator($POST)
	{
		if(!$POST)
		{
			$POST = $_SESSION['CW_ORDERDATA'];
		}
		
		$aInputFields = self::getCheckoutDataFormFields();
		$errors = array();
		foreach($aInputFields as $field)
		{
			if (isset($field['required']))
			{
				if (isset($field['validatortype']))
				{
					if ($field['validatortype'] == 'email')
					{
						if (!CW_Validator_EmailValidator::validate($POST,$field['name'],true,$errors))
						{
							self::$errors[$field['name']] = $field['name'];
						}
					}
				}
				else
				{
					if (!CW_Validator_RequiredFieldValidator::validate($POST,$field['name'],$errors))
					{
						self::$errors[$field['name']] = $field['name'];
					}
				}
			}
		}
		if (count(self::$errors) > 0)
		{
			return false;
		}
		return true;
	}
	
	static public function storeCheckoutData($POST,array $extraFields = array())
	{
		$aInputFields = self::getCheckoutDataFormFields();
		$aInputFields = array_merge($aInputFields,$extraFields);
		
		foreach($aInputFields as $field)
		{
			if (isset($POST[$field['name']]))
			{
				$_SESSION['CW_ORDERDATA'][$field['name']] = strip_tags($POST[$field['name']]);
			}
		}
		
		return true;
	}
	
	static public function showPaymentForm($programId,$websiteId,$locationId,$companyId,$username,$password,$doDisplay=false)
	{
		self::shopCrumb(false,'step2','');
		
		try{
			$paynl = new PayNL_Transactions($programId,$websiteId,$locationId,$companyId,$username,$password,false);
		}
		catch (Exception $e)
		{
			//echo 'Caught exception: ',  $e->getMessage(), "\n";
			//DEBUG
			mail(SEBSOFT_MAIL, 'PAY ERROR!', print_r($e,true));
		}
		$return = '	<div id="cw_checkoutformdisplay">
					  <form class="cw_form_jquery" method="post" action="'.site_url().'/'.CW_SLUG.'/step3">
					  <fieldset>
						<table id="cw_checkoutformdisplaytable">
						<tbody>';
		
		// Betalingsmethoden ophalen die aan staan in Pay.nl voor deze gegevens
        $profielen = $paynl->getActivePaymentProfiles();
        $lijst = array();
        foreach ($profielen as $profiel)
        {
            $lijst[$profiel['id']] = $profiel['name'];
        }
        
        
        // En nu HTML van maken
        $betalingsmethodes = "<select id='paymentProfile' class='paymentProfile' name='paymentProfile'>";
        $betalingsmethodes.="<option value=''>-- Maak een selectie --</option>\n";
        foreach($lijst as $idNummer => $naam)
        {
            $betalingsmethodes.="<option value='$idNummer'>".__($naam,'cursuswebshop')."</option>\n";
        }
        $betalingsmethodes.="</select>";
        
        // Banken ophalen bij Pay.nl (dit is enkel nodig in geval van iDEAL - bij Giropay moet dit zelf worden opgegeven in (dat gaat dan om een nummer van enkele cijfers)
        // de mogelijkheden daarvan kunnen niet worden opgehaald bij Pay.nl.
        $banken = '';
        $bankenLijst = $paynl->getIdealBanks();
        foreach($bankenLijst as $bank)
        {
            $banken.="<input type='checkbox' name='bankId' value='".$bank['id']."'>".$bank['name']."</input><br />";
        }
		$return.="<tr><td width='30%'>".__('paymentmethod','cursuswebshop')."</td><td width='70%'>$betalingsmethodes</td></tr>";
		$return.="</tbody>";
		
		
		$return.="<tbody class='cw_idealbank' style='display:none;'>\n";
        $return.="<tr><td valign='top' width='30%'>Bank</td><td width='70%'>$banken</td></tr>";
        $return.="</tbody>";
        
		$return.="<tbody>";
        $return.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
		$return.="<tr><td><input class='cw_button' type='button' name='goback' value='".__('goback','cursuswebshop')."' onClick=\"window.location = '".site_url()."/".CW_SLUG."/step1'\" /></td><td>";
		$return.="<input class='submitBank cw_button' style='display:none;' type='submit' name='checkout' value='".__('continuecheckout','cursuswebshop')."' /></td></tr>";
		$return.="</tbody>";
		$return.="</table>
				  </fieldset>
				  </form>";
		$return.='</div>';
		
		if ($doDisplay)
		{
			echo $return;
			return true;
		}
		return $return;
	}
	static public function cw_header($location)
	{
		//Litle tweak 
		//$contents = ob_get_contents();
		//ob_end_clean();
		if(headers_sent($file,$line))
		{
			_e('Header already sent by server - Please fix this!','cursuswebshop');
		}
		else 
		{
			wp_redirect($location);
		}
		//header("Location: $location");
		die();
	}
	static public function createTransactionAndRedirect($programId,$websiteId,$locationId,$companyId,$username,$password)
	{
		global $siteurl,$webshopName;
		//var_dump($_POST);
		
		if(empty($_POST['paymentProfile']) || ($_POST['paymentProfile']=='10' && empty($_POST['bankId'])))
		{
			self::cw_header(site_url()."/".CW_SLUG."/step2");
			die();
		}
		
		try{
			$paynl = new PayNL_Transactions($programId,$websiteId,$locationId,$companyId,$username,$password,true);
		}
		catch (Exception $e)
		{
			mail(SEBSOFT_MAIL, 'PAY ERROR!', print_r($e,true));	//DEBUG
		}
		
		// Generate transferdata
		$transferData = array();
		$cartData = CW_Cart::getTransferDataForPay($totalPrice,true);
		
		//mail(SEBSOFT_MAIL,'pr',$totalPrice);
		$totalPrice 						= round($totalPrice / 100 ,2) * 100;
		$clientData 						= $_SESSION['CW_ORDERDATA'];
		$transferData['cartData']		 	= $cartData;
		$transferData['clientData'] 		= $clientData;
		
		$wp_lang 							= strtolower(WPLANG);
		$wp_lang 							= explode('_', $wp_lang);
		
		$transferData['shopData'] = array('lang'=>$wp_lang[0],'outpayment_method'=>CW_OUTPAYMENT,'moodleSiteKey'=>get_option('moodlesitekey'),'merlinCode'=>get_option('merlincode'),'companyname'=>get_option('companyname'));
		
		$settings = array();
		$settings['transferData'] = array('shopData'=>base64_encode(serialize($transferData)));

		if ($_SESSION['CW_ORDERDATA']['paymentProfile'] == 10) // iDEAL requires bank id
		{
			$paynl->setBankId($_SESSION['CW_ORDERDATA']['bankId']);
		}
		
		$exchange 		= CW_MERLIN_EXCHANGE;
		$return 		= site_url()."/".CW_SLUG."/return";
		$paynl->setExchangeUrl($exchange);
		$paynl->setReturnUrl($return);
		
		if(CW_SANDBOX_MODE==true)
		{
			$paynl->enableTestMode();
		}
		
		try{
			$result = $paynl->createTransaction($totalPrice,$_SESSION['CW_ORDERDATA']['paymentProfile'],$settings);
		}
		catch (Exception $e)
		{
			mail(SEBSOFT_MAIL, 'PAY ERROR!', print_r($e,true));
		}
		// send all this data to Merlin (Merlin should store it somewhere)
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 	1);
		curl_setopt($curl, CURLOPT_USERAGENT, 		'CWS @ '.site_url());
		curl_setopt($curl, CURLOPT_TIMEOUT, 		10);
		curl_setopt($curl, CURLOPT_URL, 			CW_MERLIN_ADDING); // Merlin CWS add connector
		curl_setopt($curl, CURLOPT_POST, 			1);
		
		$dataForMerlin 					= array();
		$dataForMerlin['PayTransfer'] 	= serialize($settings);
		$dataForMerlin['PayResponse'] 	= serialize($result);

		CWdatabase::saveCustomer($transferData,$totalPrice,$result);
		
		curl_setopt($curl, CURLOPT_POSTFIELDS, $dataForMerlin);

		$data 		= curl_exec($curl);
		$getinfo 	= curl_getinfo($curl);
		$error_nr 	= curl_errno($curl);
		
		if (stristr($data,'OK')==false || !isset($result['paymentSessionId']))
		{
			// Redirect to unable to complete payment page
			self::cw_header(site_url()."/".CW_SLUG."/transactionerror");
			exit;
		}
		
		if (isset($result['bankAccountNumber']))
		{
			// Store payment data in session
			$_SESSION['CW_PAYMENTDATA'] = $result;
			// Merlin_CWS should send an invoice now. (this is asynchronous from the cws_add call)
			self::cw_header(site_url()."/".CW_SLUG."/banktransaction");	// Redirect to payment page
			exit;
		}
		
		self::cw_header($result['issuerUrl']);
		exit;
	}
	
	static public function showThankYouPage($paymentOK=false)
	{
		if ($paymentOK)
		{
			CW_Cart::resetCart();

			echo "<h3>".__('thankyouforyourpurchase','cursuswebshop')."</h3>
					<div id='cw_comment'>
						<p>".__('paidokcomments','cursuswebshop')."</p>
			      	</div>".__('gobacktoshop','cursuswebshop');
		}
		else
		{
			echo "<h3>". __('notcompletedyet','cursuswebshop')."</h3>
				 	<div id='cw_comment'>
						<p>".__('nopaycomments','cursuswebshop')."</p>
					 </div>";
		}
	}
	/**
	 * Converting a string for a url
	 * 
	 * @param string $name
	 * @param boolean $replaceAccents - default false
	 * @param boolean $removeSlashes - default false
	 * @return string
	 */
	static public function cw_url( $name, $replaceAccents = false, $removeSlashes = false )
	{
	        $name = str_replace( array( ' ', ',', ':','.' ), '-', $name );
	        $name = str_replace( array( '!', '\'', ':', '&', '?' ), '', $name );
	        $name = strtolower( preg_replace( '/(-)+/', '-', $name ) );

	        if( $replaceAccents )
	        {
	                $accentsArr =   array('ç', 'ë');
	                $replaceByArr = array('c', 'e');
	                $replaceArr = array( 'ë' => 'e', 'ç' => 'c' );
	                $name = self::cw_replace_specials( $name );
	        }
	        
	        if( $removeSlashes )
	        {
	                $name = str_replace('/','', $name);
	                $name = strtolower( preg_replace( '/(-)+/', '-', $name ) );
	        }
	        
	        return( $name );
	}
	/**
	 * 
	 * Breadcrumbs for user friendly use
	 * 
	 * @param boolean $display
	 * @param string $active active url
	 */
	static public function cw_breadcrumbs($display=false,$active='')
	{
		if($active=='')
		{
			$active = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}

		if($display)
		{
			$c =0;
			//adding default to session
			$_SESSION['cw_urls'][0][CW_DISPLAY_NAME] 	= site_url().'/'.CW_SLUG;
			$total = count($_SESSION['cw_urls']);
			//
			ksort($_SESSION['cw_urls']);
			
			$return = '<ul class="cw_breadcrumbs">';
			foreach($_SESSION['cw_urls'] as $nr=>$item)
			{
				$c++;
				foreach($item as $title=>$url)
				{
					$more 		= ($total!=$c) ? ' >> ': '';
					$x 			= ($url==$active) ? ' class="active"' : '';
					$return 	.='<li'.$x.'><a href="'.$url.'">'.$title.'</a> '.$more.'</li>';
				}
			}
			$return .= '</ul>';
			//Clean session
			unset($_SESSION['cw_urls']);
			return $return;
		}
	}
	/**
	 * Convert to a normal string
	 * 
	 * @param string $string
	 * @return string with no stanges char
	 */
	static public function cw_replace_specials($string)
	{
	        return strtr(utf8_decode( $string ),array('Ä'=>'Ae','Æ'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ß'=>'ss','ä'=>'ae','æ'=>'ae','ö'=>'oe','ü'=>'ue','À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A','Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','×'=>'x','Ø'=>'O','Ù'=>'U','Ú'=>'U','Û'=>'U','Ý'=>'Y','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n', 'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ý' =>'y','ÿ'=>'y','%'=>'perc','?'=>''));
	}
	
	static public function showBankPaymentPage()
	{
		if (!isset($_SESSION['CW_PAYMENTDATA'])||count($_SESSION['CW_PAYMENTDATA']) < 2)
		{
			self::cw_header(site_url()."/".CW_SLUG."/cart");
			exit;
		}
		
		$paymentData = $_SESSION['CW_PAYMENTDATA'];
		CW_Cart::resetCart();
		$paymentDetails = __("orderwillbedeliveredafterconfirmedpayment",'cursuswebshop');
		
		// Show payment data page
		echo "<h3>".__('thankyouforyourpurchase','cursuswebshop')."</h3>
			<div id='cw_comment'>
			<p>".printf($paymentDetails,$paymentData['bankAccountNumber'],'Pay.nl',$paymentData['orderDesc']). 
				"<br />
				 <br />
				 <h3>".__("bankaccountdata",'cursuswebshop')."</h3>
				 <br />".
				__("bankaccountname",'cursuswebshop').": "."Pay.nl<br />". 
				__("bankaccountnumber",'cursuswebshop').": ".$paymentData['bankAccountNumber']."<br />". 
				__("bankiban",'cursuswebshop').": ".$paymentData['bankIban']."<br />". 
				__("bankbic",'cursuswebshop').": ".$paymentData['bankBIC']."<br />
			</p>
			</div>";
	}
	
	static public function showTransactionErrorPage()
	{
		echo "<h3>".__('paymentnotpossibleduetoerror','cursuswebshop')."</h3>
				<div id='cw_comment'>
					<p>".__("pleasetryagainlater",'cursuswebshop')."</p>
				</div>";
	}
}