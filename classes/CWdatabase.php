<?php
class CWdatabase
{
	public static function cw_uninstall()
	{
		global $wpdb;
		
		// first remove all tables
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_category");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_orders");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_rating");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_accreditation");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_products");
		
		// then remove all options
		delete_option( 'moodlesitekey' );
		delete_option( 'paynlusername' );
		delete_option( 'paynlpassword' );
		delete_option( 'paynlcompanyid' );
		delete_option( 'paynlwebsiteid' );
		delete_option( 'paynlwebsitelocationid' );
		delete_option( 'merlincode' );
		delete_option( 'moodlesitelocation' );
		delete_option( 'companyname' );
	}
	//SETTING default values
	public static function cw_default_options($do='add')
	{
		//TODO check if this works
		if($do=='add')
		{
			//DEFAULT not in global config yet
			add_option( 'cw_is_importing', 			0);
			add_option( 'cw_last_import_date', 		0);
			
			//
			add_option( 'moodlesitekey', 			'SFAJKLALS');
			add_option( 'paynlusername', 			'username');
			add_option( 'paynlpassword', 			'password');
			add_option( 'paynlcompanyid', 			2000);
			add_option( 'paynlwebsiteid', 			1);
			add_option( 'paynlwebsitelocationid',	1 );
			add_option( 'merlincode', 				1);
			add_option( 'moodlesitelocation', 		'http://waarmoodlestaat.nl');
			add_option( 'companyname', 				'bedrijf_test');
		} 
		elseif($do=='update')
		{
			update_option( 'moodlesitekey', 			'SFAJKLALS');
			update_option( 'paynlusername', 			'username');
			update_option( 'paynlpassword', 			'password');
			update_option( 'paynlcompanyid', 			2000);
			update_option( 'paynlwebsiteid', 			1);
			update_option( 'paynlwebsitelocationid',	1 );
			update_option( 'merlincode', 				1);
			update_option( 'moodlesitelocation', 		'http://waarmoodlestaat.nl');
			update_option( 'companyname', 				'bedrijf_test');
		}
	}
	//BACKUP
	public static function cw_backup($email=SEBSOFT_MAIL)
	{
		global $cw_setting;
		
		if(!isset($_SESSION['cw_backup']))
		{
			mail($email,'Backup Wordpress CursusWebshop: '.site_url(),print_r($cw_setting,true));
			echo 'Sending a email to ['.$email.'] with the setting';
			$_SESSION['cw_backup'] = true;
		}
		else 
		{
			echo 'Already send you a email';
		}
		
	}
	
	public static function cw_install()
	{	
		global $wpdb;	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		//TODO dit even netjes maken
		//TODO Records toevoegen
		//TODO Record Discount toevoegen
		/*
		 * CREATE TABLE `test_wp`.`testwp_cw_discount` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`procent` DECIMAL( 10, 2 ) NOT NULL ,
			`count` SMALLINT NOT NULL ,
			`unique_id` INT NOT NULL ,
			`discount_id` INT NOT NULL ,
			`active` TINYINT NOT NULL
			) ENGINE = MYISAM 
		 */
		//TODO Record Actioncode
		// Check version
		$dbVersion = get_option("cw_db_version",0);
		
		if ($dbVersion < CW_VERSION && $dbVersion != 0)
		{
			// do upgrades
			echo 'We are now doing some update`s';
			//Adding a table for cat
			$table_name = $wpdb->prefix . "cw_category";
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE  ".$table_name." (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`moodle_cat_id` INT UNSIGNED NOT NULL ,
					`name` VARCHAR( 200 ) NOT NULL,
					`active` TINYINT UNSIGNED NOT NULL
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				dbDelta($sql);
			}
			//TODO geen update gedeelte 
			//ALTER TABLE `testwp_cw_accreditation` ADD `alt` VARCHAR( 100 ) NOT NULL 
			//add_option("cw_db_version", CW_VERSION);
		}
		elseif($dbVersion == 0)
		{
			// Fresh install
			//TODO adding new tables
			// Order table
			$table_name = $wpdb->prefix . "cw_orders";
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE " . $table_name . " (
						`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
						`status` TINYINT NOT NULL DEFAULT '0',
						`payment_session` INT NOT NULL ,
						`order_id` VARCHAR( 36 ) NOT NULL ,
						`paymentmethod` INT NOT NULL ,
						`price` DECIMAL(10,5) NOT NULL COMMENT 'price in cents',
						`vatprice` DECIMAL(10,5) NOT NULL COMMENT 'price in cents',
						`transferdata` TEXT NOT NULL ,
						`userid` INT NOT NULL COMMENT 'wordpress userid',
						`email` VARCHAR( 128 ) NOT NULL ,
						`organisation` VARCHAR( 128 ) NOT NULL ,
						`firstname` VARCHAR( 64 ) NOT NULL ,
						`lastname` VARCHAR( 64 ) NOT NULL ,
						`address` VARCHAR( 255 ) NOT NULL ,
						`pc` VARCHAR( 8 ) NOT NULL ,
						`city` VARCHAR( 64 ) NOT NULL ,
						`country` VARCHAR( 64 ) NOT NULL DEFAULT '',
						`buyorder` VARCHAR( 64 ) NOT NULL DEFAULT '',
						PRIMARY KEY ( `id` ) ,
						UNIQUE (
						`payment_session`
						)
						) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";

				dbDelta($sql);
			}
			
			$table_name = $wpdb->prefix . "cw_rating";
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE " . $table_name . " (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `courseid` int(11) NOT NULL,
						  `stars` int(11) NOT NULL,
						  `comment` text,
						  `addedon` int(11) NOT NULL,
						  `userid` int(11) NOT NULL,
						  `active` int(1) NOT NULL DEFAULT '0',
						  PRIMARY KEY (`id`)
						
						) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				dbDelta($sql);
			}
			//Accreditation
			$table_name = $wpdb->prefix . "cw_accreditation";
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE " . $table_name . " (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `pic` varchar(200) NOT NULL,
						  `courseid` int(11) NOT NULL,
						  `active` int(1) DEFAULT '0',
						  `alt` varchar(200) NOT NULL,
						  PRIMARY KEY (`id`)
						
						) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				dbDelta($sql);
			}
			//CATEGORY
			$table_name = $wpdb->prefix . "cw_category";
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE  ".$table_name." (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`moodle_cat_id` INT UNSIGNED NOT NULL ,
					`name` VARCHAR( 200 ) NOT NULL,
					`active` TINYINT UNSIGNED NOT NULL
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				dbDelta($sql);
			}
			//PRODUCTEN / COURSES
			$table_name = $wpdb->prefix . "cw_products";
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name)
			{
				$sql = "CREATE TABLE " . $table_name . " (
					  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					  `unique_id` varchar(128) NOT NULL COMMENT 'CW_UNIQUE',
					  `name` varchar(128) NOT NULL,
					  `description` text NOT NULL,
					  `price` decimal(10,5) NOT NULL,
					  `vat` int(11) NOT NULL COMMENT 'percentage',
					  `category` int(11) NOT NULL,
					  `education` varchar(100) DEFAULT NULL,
					  `avgrating` int(11) DEFAULT NULL,
					  `courseid` int(11) DEFAULT NULL,
					  `active` int(1) DEFAULT '0',
					  `deleted` int(1) DEFAULT '0',
					  PRIMARY KEY (`id`)
					) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				dbDelta($sql);
				
			}
			add_option("cw_db_version", CW_VERSION);
			
			//FIRST install default setting
			self::cw_default_options();
		}
	}
	
	public static function updateCourse($arrays)
	{
		global $wpdb;
		$return ='<br/><b>Products</b><br/>';
		$table_name = $wpdb->prefix . "cw_products";
		//Als even op 0 zetten (alles wat 0 is gebleven verwijderen)
		$wpdb->update($table_name, array('active'=>0),array('active'=>1));
		
		foreach($arrays as $array)
		{
			if(!empty($array['courseid'])&&is_numeric($array['courseid']))
			{
				$sql = $wpdb->prepare('SELECT id FROM '.$table_name.' WHERE courseid=%d LIMIT 1',$array['courseid']);
				//kijken of course al bestaat
				$course = $wpdb->get_results($sql);
				if(!empty($course))
				{
					//UPDATE
					$return .= 'Update: Course<br/>';
					$wpdb->update($table_name, $array, array('id'=>$course[0]->id));
				}
				else
				{
					//INSERT
					$return .= 'Insert: Course<br/>';
					$rows_affected = $wpdb->insert( $table_name, $array );
				}
			}
		}
		
		$wpdb->update($table_name, array('deleted'=>1),array('active'=>0));
		return $return;
	}

	public static function importPrepare()
	{
		//prepare the db for a new import setting some items to not active 
		$return = 'Todo: setting some items to inactive <br/>';
		
		return $return;
	}
	
	public static function importEnd()
	{
		//cleaning some extra db information
		$return = 'Todo: cleaning item they stay inactive<br/>';
		
		return $return;
	}
	
	public static function updateRating($array)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_rating";
		
		//Kijken of er rating is anders een maken of update
		$sql = $wpdb->prepare('SELECT id FROM '.$table_name.' WHERE courseid=%d AND userid=%d LIMIT 1',$array['courseid'],$array['userid']);
		$rating = $wpdb->get_results($sql);
		if(!empty($rating))
		{
			$wpdb->update($table_name, $array, array('id'=>$rating[0]->id));
			return '- Update: '.$rating[0]->id.'<br/>';
		}
		else 
		{
			$rows_affected = $wpdb->insert( $table_name, $array );
			return 'Insert'.'<br/>';
		}
	}
	public static function updateCategory($array)
	{
		global $wpdb;
		//TODO maybe better way but for now oke
		$return ='<br/><b>Category</b><br/>';
		$table_name = $wpdb->prefix . "cw_category";
		
		//Verwijder alles 
		$wpdb->query("DELETE FROM  $table_name");
		
		foreach($array as $nr=>$item)
		{
			 $wpdb->insert( $table_name, $item);
			 $return .='Insert:'.$item['name'].'<br/>';
		}
		return $return.'<br/>';
	}
	public function calcAvgRating()
	{
		/*
		 * Haal alle course op
		 * Haal alle rating op voor die course
		 * alles bij elkaar tellen en delen door het aantal
		 */
		global $wpdb;
		
		$courses = $wpdb->get_results('SELECT DISTINCT courseid FROM '.$wpdb->prefix.'cw_products');
		
		foreach ($courses as $course)
		{
			$counter = 0;
			$total = 0;
			
			$ratings = $wpdb->get_results('SELECT stars FROM '.$wpdb->prefix.'cw_rating WHERE courseid='.$course->courseid);
			
			foreach($ratings as $rating)
			{
				//show($rating->stars);
				$total = $total + $rating->stars;
				$counter++;
			}
			
			if($total!=0)
			{
				$avg = $total / $counter;
				
				unset($counter);
				unset($total);
			
				$table_name = $wpdb->prefix . "cw_products";
				$wpdb->update($table_name, array('avgrating'=>$avg), array('courseid'=>$course->courseid));
			}
		}
	}
	
	public static function getActionCode($code, $productid)
	{
		global $wpdb;
		//
		$table_name = $wpdb->prefix . "cw_actioncode";	
		
		$sql = $wpdb->prepare('SELECT * FROM '.$table_name.' WHERE courseid=%d AND code=%s LIMIT 1',$productid,$code);
		$result = $wpdb->get_results($sql);
		
		if($result)
		{
			return $result[0];		
		}
		else 
		{
			return false;
		}
	}
	
	public static function getStarsDB($courseid)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_products";	
		
		$sql = $wpdb->prepare('SELECT avgrating FROM '.$table_name.' WHERE courseid=%d LIMIT 1',$courseid);
		$avg = $wpdb->get_results($sql);
		
		if(!empty($avg))
		{
			return $avg[0]->avgrating;
		}
		else
		{
			return false;
		}
		
	}
	
	public static function getCommentCount($courseid)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_rating";	
		
		$sql = $wpdb->prepare('SELECT COUNT(*) AS count FROM '.$table_name.' WHERE courseid=%d',$courseid);
		$rating = $wpdb->get_results($sql);
		
		if(!empty($rating))
		{
			return $rating[0]->count;
		}
		else
		{
			return false;
		}
	}
	
	public static function updateDiscount($array)
	{
		global $wpdb;
		$return ='<br/><b>Discount</b><br/>';
		$table_name = $wpdb->prefix . "cw_discount";
		
		//TODO Delete first not the best way but it works
		$wpdb->query("DELETE FROM  $table_name");
		
		foreach($array as $nr=>$item)
		{
			 $wpdb->insert( $table_name, $item);
			 $return .= 'Insert:'.$item['unique_id'].'/ '.$item['procent'].'<br/>';
		}

		return $return.'<br/>';
	}
	
	public static function updateActionCode($array)
	{
		global $wpdb;
		$return ='<br/><b>Actioncode</b><br/>';
		$table_name = $wpdb->prefix . "cw_actioncode";
		
		//TODO Delete first not the best way but it works
		$wpdb->query("DELETE FROM  $table_name");
		
		foreach($array as $nr=>$item)
		{
			 $wpdb->insert( $table_name, $item);
			 $return .= 'Insert:'.$item['code'].'<br/>';
		}
		return $return.'<br/>';
	}
	
	public static function getComment($courseid)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_rating";	
		
		$sql = $wpdb->prepare('SELECT * FROM '.$table_name.' WHERE courseid=%d',$courseid);
		$comments = $wpdb->get_results($sql);
		
		if(!empty($comments))
		{
			return $comments;
		}
		else
		{
			return false;
		}
	}
	
	public static function saveCustomer($transferData,$totalPrice,$payData)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_orders";
		
		$array = array();
		
		$array['status'] 			= 'Send to pay';
		$array['payment_session'] 	= $payData['paymentSessionId'];
		$array['order_id'] 			= $payData['orderId'];
		$array['paymentmethod'] 	= $transferData['clientData']['paymentProfile'];
		$array['transferdata']		= serialize($transferData);
		$array['vatprice'] 			= 'Send to pay';
		$array['price'] 			= $totalPrice;
		$array['email'] 			= $transferData['clientData']['email'];
		$array['organisation'] 		= $transferData['clientData']['organisation'];
		$array['firstname'] 		= $transferData['clientData']['firstname'];
		$array['lastname'] 			= $transferData['clientData']['lastname'];
		$array['address'] 			= $transferData['clientData']['address'];
		$array['pc'] 				= '';
		$array['city'] 				= $transferData['clientData']['city'];
		$array['country'] 			= 'Netherlands';

		$s = $wpdb->insert( $table_name, $array );
		//TODO Mag uit !!!
		mail(SEBSOFT_MAIL,'cw_customer',print_r($transferData,true).print_r($payData,true));
	}
	
	public static function getAc($courseid)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_accreditation";	
		
		$sql = $wpdb->prepare('SELECT pic,alt FROM '.$table_name.' WHERE courseid=%d AND active=1',$courseid);
		$ac = $wpdb->get_results($sql);
		
		if(!empty($ac))
		{
			return $ac;
		}
		else
		{
			return false;
		}
	}
	
	
	public static function getDiscount($productid)
	{
		global $wpdb;
		$discount = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cw_discount WHERE unique_id='".intval($productid)."' ORDER BY count ASC");

		if($discount)
		{
			return $discount;	
		}
		else
		{
			return false;
		}
	}	
		
	public static function updateAc($array)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . "cw_accreditation";
		
		/*
		 * Zet alle ac pic op inactive
		 * Kijk of record bestaat zoja actief zetten
		 * Anders het record toevoegen 
		 */
		
		$wpdb->update($table_name, array('active'=>0),array('active'=>1));
				
		foreach($array as $item)
		{
			$sql = $wpdb->prepare('SELECT id FROM '.$table_name.' WHERE courseid=%d AND pic=%s LIMIT 1',$item['courseid'],$item['pic']);
			$ac = $wpdb->get_results($sql);
			
			if(!empty($ac))
			{
				$wpdb->update($table_name, array('pic'=>$item['pic'],'alt'=>$item['alt'],'courseid'=>$item['courseid'],'active'=>1), array('id'=>$ac[0]->id));
			}
			else 
			{
				$array = array('pic'=>$item['pic'],'alt'=>$item['alt'],'courseid'=>$item['courseid'],'active'=>1);
				$rows_affected = $wpdb->insert( $table_name, $array );
			}
		}
		return '<b>Accreditation: </b>'.'<br/>Updated<br/>';
	}
	public static function getProductsByCategory($category_id=0,$product=0)
	{
		global $wpdb;
		
		$fields = 'p.id,p.name,p.description,p.price,p.vat,p.courseid,p.education,p.unique_id,c.name AS category';
		$join = 'LEFT JOIN '.$wpdb->prefix.'cw_category AS c ON c.moodle_cat_id=p.category';
		if($category > 0)
		{
			$products=$wpdb->get_results("SELECT ".$fields." FROM ".$wpdb->prefix ."cw_products AS p ".$join." WHERE p.category=$category_id AND p.active=1 ORDER BY p.name");
		}
		elseif($product > 0)
		{
			//Unique_id moodle course id
			$products=$wpdb->get_results("SELECT ".$fields." FROM ".$wpdb->prefix."cw_products AS p ".$join."  WHERE p.unique_id=$product AND p.active=1 ORDER BY p.name");
		}
		else
		{
			$products=$wpdb->get_results("SELECT ".$fields." FROM ".$wpdb->prefix."cw_products AS p ".$join." WHERE p.active=1 ORDER BY p.name");
		}
		$wpdb->print_error();
		return $products;
	}
	
	
	public static function getProduct($id,$getInCents=false)
	{
		//Payment information
		global $wpdb;
		$urlmoodle = get_option('moodlesitelocation');
		$product = $wpdb->get_results("SELECT id,name,description,price,vat,unique_id,courseid FROM ".$wpdb->prefix."cw_products WHERE id='".$id."' LIMIT 1");
		
		if (count($product) <> 1)
		{
			return new stdClass();
		}
		if($getInCents==true)
		{
			$product[0]->price = $product[0]->price * 100;
		}
		return $product[0];
	}
	
}