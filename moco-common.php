<?php
/*
 * moco-common.phphp
 *
 * General Purpose Library
 * It contains static functions.
 */
 
 class Moco_Helper
 {

	/**
	 * Wrapper for print_r() that formats the array for HTML output
	 *
	 * @return void
	 */
     function pre_print_r($txt)
	{
		print("<pre>\n"); 
		print_r($txt); 
		print("</pre>\n");
	}
	
	/**
	 * Checks whether the supplied $number is even
	 *
	 * @return boolean
	 */
     function is_even($number)
	{
		if ($number % 2 == 0 )
		{
			// The number is even
			return true;
		}
    		else
		{
			// The number is odd
			return false;
		}
	}

     function redirect($location = '')
	{
		if(empty($location))
		{
			$location = $_SERVER['PHP_SELF'];
			
			if($_GET)
			{
				$args = '?';
				foreach($_GET as $var => $value)
				{
					$args .= "$var=$value&";
				}
			}
		}

		header("location: $location$args");
		exit();
	}

	/**
	 * Determines the application host platform
	 *
	 * @return array
	 */
     function get_host_platform()
	{
		$host = array();
		
		// Check for WordPress
		if(function_exists(get_bloginfo))
		{
			$host = array('type' => 'WP',
						  'version' => substr(get_bloginfo('version'), 0, 3));
		}
		
		return $host;
	}
	
	/**
	 * Retrieve the application URL
	 *
	 * @return string
	 */
     function get_current_url()
 	{
		$current_dir = '';
		
 		// Get Host
		$host = social_common::get_host_platform();
		
 		if($host['type'] == 'WP')
 		{
			$app_directory = end(explode('/', dirname(__FILE__)));
			$current_dir = social_db::get_user_option('siteurl').'/wp-content/plugins/'.$app_directory.'/';
 		}

		return $current_dir;
 	}
}
?>
