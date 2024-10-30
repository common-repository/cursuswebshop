<?php
/**
 * @desc 		Cursus Webshop
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.2
 * @since		2011
 */
class CW_Cart
{
	// Returns complete cart including all required HTML to print
	static public function getCart()
	{
		//CWdatabase::fixDB();
		$return='';
		$currentItems = self::getCartItems();
		
		if (count($currentItems) > 0)
		{
			$return.=self::actionCodeDialog($currentItems,true);
			$return.="<div id='cw_cartdisplay'>\n";
			$return.="<form method='post' action='".site_url()."/".CW_SLUG."/cart' id='cw_shopping_cart'>";
			$return.="<table id='cw_cartdisplaytable'>";
			
			$subtotal = 0.00;
			$vat = 0.00;
			
			$return.='<tr>
						<td width="100">
							<strong>'.__('count','cursuswebshop').'</strong>
						</td>
						<td>
							<strong>'.__('desc','cursuswebshop').'</strong>
						</td>
						<td>
							<strong>'.__('1_item_price','cursuswebshop').'</strong>
						</td>
						<td>
							<strong>'.__('total','cursuswebshop').'</strong>
						</td>
					</tr>';
			// Display line
			foreach($currentItems as $item => $itemcount)
			{
				// Get
				$product 			= CWdatabase::getProduct($item);
				$discountPrice 		= CW_shop::getDiscountPrice($product,$itemcount,'int');
				$actioncodeProcent 	= self::actionCodeProcent($product->unique_id);
				
				
				if($discountPrice)
				{
					//Discount
					$singleprice 		= $discountPrice;
					$price 				= $discountPrice * $itemcount;
				}
				else
				{
					//Normal
					$singleprice 		= $product->price;
					$price				= $singleprice * $itemcount;
				}
				//We have price with or without discount
				//check if we used actioncode
				if($actioncodeProcent > 0)
				{
					//New price with actioncode
					$price = $price / 100 * (100 - $actioncodeProcent); 
					$newPrice = $singleprice / 100 * (100 - $actioncodeProcent) ;
				}
			
				// Prepare display
				$prd 			= new stdClass();
				$prd->price 	= $price;
				$prd->vat 		= $product->vat;

				$vat		   	+=(($price/100)*($product->vat));
				$subtotal		+= $price;
				
				$price = CW_shop::getPrice($prd,false,true,true);
				//checking if we can use discount rules
			
				$priceString = CW_shop::getDiscountPrice($product,$itemcount,'text',$actioncodeProcent);
				
				if(!$priceString)
				{
					$priceString = get_option('cw_currency','&euro;').' '.number_format($singleprice,2,',','.'); 
				}
				
				//Display orderline
				if($actioncodeProcent > 0)
				{
					$return.='<tr>
								<td colspan="4">&nbsp;</td>
							</tr>
							 <tr>
								<td rowspan="2">
									<input class="text ui-widget-content ui-corner-all" type="text" size="3" value="'.$itemcount.'" name="item['.$item.']" />&nbsp;<b>x</b>
								</td>
								<td>'.$product->name.'</td>
								<td>'.$priceString.'</td>
								<td>'.$price.'</td>
							</tr>	
							<tr>
							
								<td class="cw_small">'.__('Couponcode used getting extra ','cursuswebshop').$actioncodeProcent.__(' % procent sales.','cursuswebshop').'</td>
								<td>'.get_option('cw_currency','&euro;').' '.number_format($newPrice,2,',','.').'</td>
								<td></td>
							</tr>							
							';
				}
				else 
				{
					$return.='<tr>
								<td colspan="4">&nbsp;</td>
							</tr>
							 <tr>
								<td>
									<input type="text" size="3" value="'.$itemcount.'" name="item['.$item.']" />&nbsp;<b>x</b>
								</td>
								<td>'.$product->name.'</td>
								<td>'.$priceString.'</td>
								<td>'.$price.'</td>
							</tr>';
				}
			}
			
			$cls = new stdClass();
			$cls->price = $subtotal;
			$subtotalShow = CW_shop::getPrice($cls,false,true,true);
			

			//Refresh 
			$return.='<tr><td colspan="4">&nbsp;</td></tr>';
			$return.='<tr>
							<td colspan="4"><a href="#" class="cw_button" id="cw_refresh"><img src="'.CW_URL.'images/refresh-icon.gif" alt="'.__('refresh','cursuswebshop').'" /> '.__('refresh cart','cursuswebshop').'</a></td>
						</tr>';

			// Subtotal
			$return.='<tr><td style="border-bottom:1px solid #2F2F2F;" colspan="4">&nbsp;</td></tr>';
			
			$return.="<tr><td colspan='2'>".__('subtotal','cursuswebshop')."</td><td>&nbsp;</td><td>".$subtotalShow."</td></tr>\n";
			
			
			// VAT
			$cls->price = $vat;
			$vatShow = CW_shop::getPrice($cls,false,true,true);
			$vatProcent = $vat * 100 / $subtotal;
			$return.="<tr><td colspan='2'>".__('vatamount','cursuswebshop')." ".$vatProcent."%</td><td>&nbsp;</td><td>".$vatShow."</td></tr>\n";
			
			// Total price
			$cls->price = $vat + $subtotal;
			$totalShow = CW_shop::getPrice($cls,false,true,true);
			
			$return.='<tr><td colspan="3">&nbsp;</td></tr>';
			$return.="<tr><td colspan='2'>".__('totalamount','cursuswebshop')."</td><td>&nbsp;</td><td><strong>".$totalShow."</strong></td></tr>\n";
			$return.='<tr><td colspan="4">&nbsp;</td></tr>';
			
			//Actioncode
			$return.='<tr><td colspan="4"><a href="#" id="actioncode" class="cw_button" >'.__('use a actioncode','cursuswebshop').'</a></td></tr>';
					$return.='<tr><td colspan="3">&nbsp;</td></tr>';
			// Continue to payment
			$return.="<tr>
						<td colspan='2' style='text-align:left'>
							<input type='button' name='goback' class=\"cw_button\" value='".__('shoppinggoback','cursuswebshop')."' onClick=\"window.location = '".site_url()."/".CW_SLUG."'\" />
						</td>
						<td colspan='2' style='text-align:right'>
							<!-- <input type='submit' name='update'  class=\"cw_button\" value='".__('update','cursuswebshop')."' />&nbsp; -->
							<input type='submit' name='checkout'  class=\"cw_button\" value='".__('gotocheckout','cursuswebshop')."' />&nbsp;
						</td>
					</tr>
					</table>
					</form>
					</div>";
		}
		else
		{
			$return.="<div id='cw_noitemsincart'>".__('noitemsincart','cursuswebshop')."</div>";
		}
		
		return $return;
	}
	
	static public function actionCodeProcent($productid)
	{
		if(isset($_SESSION['CW_ACTIONCODE'][$productid]))
		{
			return $_SESSION['CW_ACTIONCODE'][$productid];
		}
		else 
		{
			return 0;
		}
	}
	
	static public function actionCodeDialog($currentItems,$show=false)
	{
		//Get the product from the session
		$select = '<select id="cw_code_product" name="product">';
		foreach($currentItems as $item => $itemcount)
		{
			$product = CWdatabase::getProduct($item);
			$select .= '<option value="'.htmlspecialchars($product->unique_id).'">'.htmlspecialchars($product->name).'</option>';
		}
		$select .= '</select>';
		
		$return =  '<div id="cw_dialog" title="'.__('Enter your couponcode','cursuswebshop').'">
						<p class="validateTips"></p>
						<form action="'. site_url().'/'.CW_SLUG.'/actioncodecheck/" method="post" name="cw_code_form" id="cw_code_form">
							<p>
								'.__('Please enter your couponcode','cursuswebshop').'
							</p>
							<p>
								<input type="text" name="cw_code" id="cw_code" />
							</p>
							<p>
								'.__('Select your product from the list where your couponcode is for.','cursuswebshop').'
							</p>
							<p>	
								'.$select.'
							</p>
						</form>
			  		</div>';
		return $return;
	}
	
	
	static public function getCartForCw()
	{
		global $webshopName;
		$return='';
		$currentItems = self::getCartItems();
		
		if (count($currentItems) > 0)
		{	
			$subtotal = 0.00;
			$vat = 0.00;
			$return .= '<ul>';
			$count_products = 0;
			foreach($currentItems as $item => $itemcount)
			{
				// Get
				$product = CWdatabase::getProduct($item);
				
				$actioncodeProcent 	= self::actionCodeProcent($product->unique_id);
				$discountPrice 		= CW_shop::getDiscountPrice($product,$itemcount,'int');
				// Calc
				
				if($discountPrice)
				{
					$singleprice 	= $discountPrice;
					$price 			= $discountPrice * $itemcount;
				}
				else
				{
					$singleprice 	= $product->price;
					$price 			= $singleprice * $itemcount;
					
				}
				//check if we used actioncode
				if($actioncodeProcent > 0)
				{
					//New price with actioncode
					$price = $price / 100 * (100 - $actioncodeProcent); 
					
					$newPrice =  $singleprice / 100 * (100 - $actioncodeProcent); 
				}
				// Prepare display
				$prd 			= new stdClass();
				$prd->price 	= $price;
				$prd->vat 		= $product->vat;
				$vat			+=($price/100)*($product->vat);
				$subtotal		+=$price;
				// Display line
				$count_products += $itemcount;
			}
			
			$cls = new stdClass();
			$cls->price = $subtotal;
			$subtotalShow = CW_shop::getPrice($cls,false,true,true);
			
			// Subtotal
			$return.="<li>".$count_products.' '.__('products','cursuswebshop')."</li>\n";
			
			// Total price
			$cls->price = $vat + $subtotal;
			$totalShow = CW_shop::getPrice($cls,false,true,true);
			$return.="<li>".__('totalamount','cursuswebshop')." ".$totalShow."</li>\n";
			$return.='<li><a href="'.site_url().'/'.CW_SLUG.'/cart">'.__('cartcontent','cursuswebshop').'</a></li>';
			$return .= '</ul>';
			
		}
		else
		{
			$return.="<ul><li>".__('noitemsincartsmal','cursuswebshop')."</li></ul>";
		}
		
		return $return;
	}
	
	// Returns list of items in the cart
	static private function getCartItems()
	{
		$cart = array();
		
		if (isset($_SESSION['CW_CART']))
		{
			if (is_array($_SESSION['CW_CART']))
			{
				foreach($_SESSION['CW_CART'] as $key => $val)
				{
					$cart[$key] = $val;
				}
			}
		}
		
		return $cart;
	}

	
	
	// Adds item to the cart
	static public function addItemToCart($item,$amount)
	{
		$amount = intval($amount);
		if ($amount == 0 || $amount < 0)
		{
			$amount = 1;
		}
		$currentItems = self::getCartItems();
		
		if(isset($currentItems[$item]))
		{
			$_SESSION['CW_CART'][$item]+=intval($amount);
		}
		else
		{
			$_SESSION['CW_CART'][$item]=intval($amount);
		}
		
	}
	// Updates or removes items from the cart
	static public function updateItemInCart($item,$amount)
	{
		if (isset($_SESSION['CW_CART'][$item]))
		{
			if ($amount == 0)
			{
				unset($_SESSION['CW_CART'][$item]);
			}
			else
			{
				$_SESSION['CW_CART'][$item] = $amount;
			}
		}
	}
	// Generate the transferdata required for Pay
	static public function getTransferDataForPay(&$totalPrice,$getInCents=false)
	{
		$cart = array();
		
		$cartItems = self::getCartItems();
		$totalPrice = 0.00;
		
		foreach($cartItems as $item => $count)
		{
			$product = CWdatabase::getProduct($item,$getInCents);
			
			// Calc
			$discountPrice 		= CW_shop::getDiscountPrice($product,$count,'int');
			$actioncodeProcent 	= self::actionCodeProcent($product->unique_id);
			
			if($discountPrice)
			{
				$discountUsed	= true;
				$singleprice 	= $discountPrice;
				$priceTotalExcl = $discountPrice * $count;
			}
			else
			{
				$discountUsed	= false;
				$priceTotalExcl = $singleprice * $count;
				$singleprice 	= $product->price;
			}
			
			if($actioncodeProcent > 0)
			{
				$singleprice 	= $singleprice / 100 * (100 - $actioncodeProcent);
				$priceTotalExcl = $singleprice * $count;
				
				$actioncodeUsed = true;
			}
			else 
			{
				$actioncodeUsed = false;
			}
			
			//Array 1
			$productExtra					= array();
			$productExtra['id']				= $product->id;
			$productExtra['name']			= $product->name;
			$productExtra['description']	= $product->description;
			$productExtra['price']			= $singleprice;
			$productExtra['vat']			= $product->vat;
			$productExtra['courseid']		= $product->courseid;
			$productExtra['unique_id']		= $product->unique_id;
			
			if($discountUsed)
			{
				$productExtra['discountProcent']	=   100 - ($singleprice * 100 / $product->price);
			}
			if($actioncodeUsed)
			{
				$productExtra['actioncodeProcent'] 	= $actioncodeProcent;
			}
			
			$productExtra['actioncodeUsed']		= $actioncodeUsed;
			$productExtra['discountUsed']		= $discountUsed;

			$totalPrice	+= ($singleprice * $count) + ((($singleprice * $count) / 100) * ($product->vat));
			
			//Array 3
			$pricedata 	= array('price_ex_vat'=>($singleprice * $count),'vat'=>((($singleprice * $count) / 100) * ($product->vat)));
			
			$cart[] 	= array('product'=>$productExtra,'itemcount'=>$count,'pricedata'=>$pricedata);
		}
		return $cart;
	}
	static public function jsonCodeValidator()
	{
		$array = array();
		if(isset($_POST))
		{
			if(!empty($_POST['code']) && !empty($_POST['product']))
			{
				//first check if we can find the code for this product
				$productid 	= intval($_POST['product']);
				$code 		= $_POST['code'];
				$res		= CWdatabase::getActionCode($code,$productid);
				
				if($res)
				{
					$nowTime 	= time();
					//
					$endTime 	= $res->enddate;
					$startTime 	= $res->startdate;
					
					if($nowTime <= $endTime)
					{
						if($nowTime >= $startTime)
						{
							$array['ok'] = __('Your code gives you ','cursuswebshop').$res->procent. __(' % procent discount.','cursuswebshop');
							//korting van product af halen laatste regel na staffel
							
							if(!isset($_SESSION['CW_ACTIONCODE'][$res->courseid]))
							{
								$_SESSION['CW_ACTIONCODE'][$res->courseid] = $res->procent;
							}
						}
						else 
						{
							//verlopen
							$array['error'] = __('Code exists but not valid','cursuswebshop');
						}
					}
					else
					{
						//verlopen
						$array['error'] = __('Code exists but not valid','cursuswebshop');
					}
				}
				else 
				{
					$array['error'] = __('Wrong code','cursuswebshop');
				}
			}
			else
			{
				$array['error'] = __('some fields where empty','cursuswebshop');
			}
		}
		else
		{
			$array['error'] = __('No access','cursuswebshop');
		}
		return json_encode($array);
	}
	// Reset the cart afer succesfull payment
	static public function resetCart()
	{
		if (isset($_SESSION['CW_CART']))
		{
			$_SESSION['CW_CART'] = array();
		}
	}
	
}