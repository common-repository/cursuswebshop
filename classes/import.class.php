<?php 
/**
 * @desc 		Cursus Webshop
 * @author  	Luuk Verhoeven
 * @copyright 	Sebsoft.nl 
 * @link 		http://www.sebsoft.nl
 * @version 	1.0.2
 * @since		2011
 */
class import_courses{
	
	private $output;
	private $baseurl;
	private $export_location = 'blocks/webshop/export_webshop.php';
	private $outputshow =true;
	
	function __construct($baseurl='')
	{
		if($baseurl!='')
		{
			$this->baseurl = $baseurl;
		}
	}
	
	public function saveToDB()
	{
		$this->output .= '<h1>Last update: '.date("Y-m-d H:i:s",get_option('cw_last_import_date')).'</h1>';
		//Let the system know we are importing
		update_option('cw_is_importing',true);
		update_option('cw_last_import_date',time());
		//TODO Maybe we want to check on some places if we are importing
		
		$this->output .= CWdatabase::importPrepare();
		
		$array = $this->getXml();	
		
		if($this->outputshow == true)
		{
			echo '<pre>';
			print_r($array);
			echo '</pre>';
		}
		$tmpArray = array();
		$tmpArrayCourses = array();
		
		if($array)
		{
			$this->output .= '<br/>';
			//TODO setting al rating inactive
			
			foreach($array['courses']['course'] as $course)
			{
				//Prepare array
				$tmpArrayCourses[] = array(	'courseid'=>$course['id'],
											'name'=> $course['fullname'],
											'unique_id'=>$course['unique_id'],
											'description'=>htmlspecialchars($course['summary']),
											'price'=>$course['price'],
											'vat'=>$course['vat'],
											'education'=>$course['education'],
											'category'=>$course['catid'],
											'active'=>1,
											'deleted'=>0);

				if(isset($course['ratings']))
				{
					foreach($course['ratings'] as $courser)
					{
						$this->output .= '<b>Rating</b><br/>';
						foreach($courser['rating'] as $item)
						{
						
							$array_rating = array('courseid'=>$course['id'],
											 	'stars'=>$item['stars'],
											 	'comment'=>$item['comment'],
											 	'addedon'=>$item['addedon'],
												'userid'=>$item['userid']);
							//show($array_rating);
							$this->output .= CWdatabase::updateRating($array_rating);
						}
					}
				}
				
				//accreditation
				if(isset($course['accreditation'][0]['ac']))
				{
					foreach($course['accreditation'][0]['ac'] as $ac)
					{
						$tmpArray[] = array('pic'=>$ac['pic'],'courseid'=>$course['id'],'alt'=>$ac['alt']);
					}
				}
			}//close foreach
			
			//discount
			if(!empty($array['courses']['discount']))
			{
				$this->output .= CWdatabase::updateDiscount($array['courses']['discount']);
			}
			//category
			if(!empty($array['courses']['category']))
			{
				$this->output .= CWdatabase::updateCategory($array['courses']['category']);
			}
			//Actioncode
			if(!empty($array['courses']['actioncode']))
			{
				$this->output .= CWdatabase::updateActionCode($array['courses']['actioncode']);
			}
		}
		else 
		{
			$this->output .= __('error converting to array make sure the base url is correct:').'<br/>';
		}
		$this->output .= CWdatabase::updateAc($tmpArray);
		$this->output .= CWdatabase::updateCourse($tmpArrayCourses).'<br/>';
		$this->output .= CWdatabase::calcAvgRating();
		//import ready
		$this->output .= CWdatabase::importEnd();
		update_option('cw_is_importing',false);
	}
	
	
	public function output()
	{
		if($this->outputshow == true)
		{
			return $this->output;
		}
	}
	
	
	private function getXml()
	{
		$url = $this->baseurl.$this->export_location;
		$this->output .= '<b><a href="'.$url.'">XML import</a></b><br/>';
		
		$content_xml = wp_remote_get($url);
		
		$array	=	$this->xml2array($content_xml['body']);

		if(is_array($array))
		{
			return $array;
		}
		else
		{
			return false;
		}
	}
	
	private function xml2array($xml) 
	{
		//Creating Instance of the Class
		$xmlObj    = new XmlToArray($xml);
		//Creating Array
		$arrayData = $xmlObj->createArray();
	    return $arrayData;
	} 
	
}
?>