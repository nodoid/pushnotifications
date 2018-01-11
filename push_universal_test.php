<?php
    	require_once('push_universal.php');
	// Message payload
	$msg_payload = array (
		'mtitle' => 'Test push notification title',
		'mdesc' => 'Test push notification body',
	);
	
	// For Android
	$regId = 'APA91bHdOmMHiRo5jJRM1jvxmGqhComcpVFDqBcPfLVvaieHeFI9WVrwoDeVVD1nPZ82rV2DxcyVv-oMMl5CJPhVXnLrzKiacR99eQ_irrYogy7typHQDb5sg4NB8zn6rFpiBuikNuwDQzr-2abV6Gl_VWDZlJOf4w';
	// For iOS
	$deviceToken = 'FE66489F304DC75B8D6E8200DFF8A456E8DAEACEC428B427E9518741C92C6660';
	// For WP8
	$uri = 'http://s.notify.live.net/u/1/sin/HmQAAAD1XJMXfQ8SR0b580NcxIoD6G7hIYP9oHvjjpMC2etA7U_xy_xtSAh8tWx7Dul2AZlHqoYzsSQ8jQRQ-pQLAtKW/d2luZG93c3Bob25lZGVmYXVsdA/EKTs2gmt5BG_GB8lKdN_Rg/WuhpYBv02fAmB7tjUfF7DG9aUL4';
	// For UWP
	$notify_url= "http://s.notify.live.net/u/1/sin/HmQAAADdQiUFgUTAUo6SYiO6u6ROXKjCZad0DTVfHR2Ss13vqir_Ozg2J6Leb5VVgCMT0dX7mikv9uiVwqLgtMxlJHun/d2luZG93c3Bob25lZGVmYXVsdA/Ev4hWfN-R0iEU0LvKG3b5w/vlgx5gRqbmTOjHvuKQwqfd346Ls"; 	
	
	// Replace the above variable values
	
	
    	PushNotifications::android($msg_payload, $regId);
    	
    	PushNotifications::WP8($msg_payload, $uri);
    	
    	PushNotifications::iOS($msg_payload, $deviceToken);
    	
    	// UWP push
 
		$title = "This is sample title.";
		$message= "This is sample description message.";
		$xml_string = $gcm->buildTileXml($title, $message);
		PushNotifications::UWP($xml_string, $notify_url);
?>

 