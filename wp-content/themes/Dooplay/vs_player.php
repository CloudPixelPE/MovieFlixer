<?php
error_reporting(E_ALL);

// VIDEOSPIDER START

$videospider_api_key = "646381410"; // YOUR VIDEOSPIDER API KEY
$videospider_secret_key = "xxjsk6dx7doeobex9o05"; // YOUR VIDEOSPIDER SECRET KEY

$wp_postmeta_name = "wp_postmeta"; // NAME OF YOUR POSTMETA TABLE, DEFAULT IS WP_POSTMETA BUT IF YOU USE CUSTOM TABLE PREFIX IT COULD BE DIFFERENT

// GET CORRECT VISITOR IP ADDRESS IF YOU ARE USING CLOUDFLARE AND DON'T HAVE MOD_CLOUDFLARE INSTALLED ON YOUR SERVER
// OTHERWISE YOU CAN REMOVE THE FOLLOWING CODE

if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

// VIDEOSPIDER END


define('SHORTINIT', true);

function get_wp_load_path()
{
    $base = dirname(__FILE__);
    $path = false;

    if (@file_exists(dirname(dirname($base))."/wp-load.php"))
    {
        $path = dirname(dirname($base))."/wp-load.php";
    }
    else
    if (@file_exists(dirname(dirname(dirname($base)))."/wp-load.php"))
    {
        $path = dirname(dirname(dirname($base)))."/wp-load.php";
    }
    else
        $path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

    if ($path != false)
    {
        $path = str_replace("\\", "/", $path);
		$path = str_replace("//", "/", $path);
    }
    return $path;
}

function get_wp_postmeta_name() {
	
	global $wpdb;
	$tables = $wpdb->get_results("SELECT table_name FROM information_schema.tables"); // get list of tables
	$return = "ERROR: Postmeta table not found";
		
	if(!empty($tables)) {
			
		foreach($tables as $table) {
			
			if (strpos($table->table_name, 'postmeta') !== false) {
					
				$return = $table->table_name;
					
			}			
		}
		
	} else {
			
		$return = "ERROR: Can't connect to db";
			
	}
	
	return $return;
	
}


if(isset($_GET['id']) && is_numeric($_GET['id'])) { // if set and numeric id
	
	require_once(get_wp_load_path()); // require wordpress load
	
	$id = $_GET['id'];

	$posts = $wpdb->get_results("SELECT meta_value FROM $wp_postmeta_name WHERE post_id = '$id' AND meta_key = 'ids'"); // get video id from database

	$video_id = $posts[0]->meta_value;
	
	if(!empty($video_id)) { // if not empty video id, create ticket and redirect to videospider
	
		$season = $_GET['s'];
		$episode = $_GET['e'];
		
		if(!empty($_GET['tv'])) {
			$tv = 1;
		} else {
			$tv = 0;	
		}	
		
		if(function_exists('curl_version')) { // use cURL if enabled, otherwise use file_get_contents
			
			$url = 'https://videospider.in/getticket.php?key='.$videospider_api_key.'&secret_key='.$videospider_secret_key.'&video_id='.$video_id.'&s='.$season.'&ip='.$_SERVER["REMOTE_ADDR"];
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, 5);
			curl_setopt($curl, CURLOPT_HEADER, false);
			$videospider_ticket = curl_exec($curl);
			curl_close($curl);	
			
		} else {
			
			$videospider_ticket = file_get_contents('https://videospider.in/getticket.php?key='.$videospider_api_key.'&secret_key='.$videospider_secret_key.'&video_id='.$video_id.'&s='.$season.'&ip='.$_SERVER["REMOTE_ADDR"]);
			
		}
		
		$videospider_url = 'https://videospider.stream/getvideo?key='.$videospider_api_key.'&tv='.$tv.'&tmdb='.$tv.'&video_id='.$video_id.'&s='.$season.'&e='.$episode.'&ticket='.$videospider_ticket;

		if(!empty($videospider_ticket)) {
			
			header("Location: $videospider_url");
			
		} else {
			
			echo "Error getting ticket";
			
		}
		
	} else {
		
		echo 'Database problem, probably wrong wp_postmeta_name.<br />Change $wp_postmeta_name to <b>'. get_wp_postmeta_name() .'</b> in this file.';
			
	}

	
} else if (isset($_GET['debug'])) {
	
	echo date('m/d/Y H:i:s', time());

	echo "<br /><br />Wordpress load path is: ". get_wp_load_path();

	require_once(get_wp_load_path());

	echo "<br /><br />wp_postmeta_name is: <b>". get_wp_postmeta_name() ."</b>";
	
}

?>