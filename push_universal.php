<?php 
//UWP bits
class WPNTypesEnum{       
    const Toast = 'wns/toast';
    const Badge = 'wns/badge';
    const Tile  = 'wns/tile';
    const Raw   = 'wns/raw';
}    
 
//Define WPN Response Class
class WPNResponse{
    public $message = '';
    public $error = false;
    public $httpCode = '';
    
    function __construct($message, $httpCode, $error = false){
        $this->message = $message;
        $this->httpCode = $httpCode;
        $this->error = $error;
    }
}

// Server file
class PushNotifications {
	// (Android)API access key from Google API's Console.
	private static $API_ACCESS_KEY = '';
	// (iOS) Private key's passphrase.
	private static $passphrase = '';
	// (Windows) The name of our push channel.
        private static $channelName = "";	
	
	// Change the above three vriables as per your app.
	public function __construct() {
		exit('Init function is not allowed');
	}
	
        // Sends Push notification for Android users
	public function android($data, $reg_id) {
	         $url = 'https://fcm.googleapis.com/fcm/send';
     $message = array
     (
         'title' => $data['mtitle'],
         'body' => $data['mdesc'],
         'subtitle' => '',
         'tickerText' => '',
         'msgcnt' => 1,
         'vibrate' => 1
     );

     $headers = array(
         'Content-Type:application/json',
         'Authorization:key='.self::$API_ACCESS_KEY
     );
     $fields = array(
         'notification' => $message,
         'to' => $reg_id,
     );
     return self::useCurl($url, $headers, $fields);
    	}
	
	// Sends Push's toast notification for Windows
	public function WP($data, $uri) {
		$delay = 2;
		$msg =  "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
		        "<wp:Notification xmlns:wp=\"WPNotification\">" .
		            "<wp:Toast>" .
		                "<wp:Text1>".htmlspecialchars($data['mtitle'])."</wp:Text1>" .
		                "<wp:Text2>".htmlspecialchars($data['mdesc'])."</wp:Text2>" .
		            "</wp:Toast>" .
		        "</wp:Notification>";
		
		$sendedheaders =  array(
		    'Content-Type: text/xml',
		    'Accept: application/*',
		    'X-WindowsPhone-Target: toast',
		    "X-NotificationClass: $delay"
		);
		
		$response = $this->useCurl($uri, $sendedheaders, $msg);
		
		$result = array();
		foreach(explode("\n", $response) as $line) {
		    $tab = explode(":", $line, 2);
		    if (count($tab) == 2)
		        $result[$tab[0]] = trim($tab[1]);
		}
		
		return $result;
	}
	
        // Sends Push notification for iOS users
	public function iOS($data, $devicetoken) {
		$deviceToken = $devicetoken;
		$ctx = stream_context_create();
		// ck.pem is your certificate file
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', self::$passphrase);
		// Open a connection to the APNS server
		$fp = stream_socket_client(
			'ssl://gateway.sandbox.push.apple.com:2195', $err,
			$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		// Create the payload body
		$body['aps'] = array(
			'alert' => array(
			    'title' => $data['mtitle'],
                'body' => $data['mdesc'],
			 ),
			'sound' => 'default'
		);
		// Encode the payload as JSON
		$payload = json_encode($body);
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		
		// Close the connection to the server
		fclose($fp);
		if (!$result)
			return 'Message not delivered' . PHP_EOL;
		else
			return 'Message successfully delivered' . PHP_EOL;
	}
	
public function buildTileXml($title, $subtitle){
        $toastMessage = "<?xml version=\"1.0\" encoding=\"utf-8\"?>".
                "<wp:Notification xmlns:wp=\"WPNotification\">".
                   "<wp:Toast>".
                        "<wp:Text1>".$title."</wp:Text1>".
                        "<wp:Text2>".$subtitle."</wp:Text2>".
                        "<wp:Param>/MainPage.xaml</wp:Param>".
                   "</wp:Toast> ".
                "</wp:Notification>";
        
        return $toastMessage;
    }
    
    public function UWP($uri, $xml_data, $type = 'wns/toast', $tileTag = ''){
        if($this->access_token == ''){
            $this->get_access_token();
        }
    
        $headers = array('Content-Type: text/xml',"Content-Type: text/xml", "X-WNS-Type: wns/toast","Content-Length: " . strlen($xml_data),"X-NotificationClass:2" ,"X-WindowsPhone-Target: toast","Authorization: Bearer $this->access_token");
        if($tileTag != ''){
            array_push($headers, "X-WNS-Tag: $tileTag");
        }
        $ch = curl_init($uri);
        # Tiles: http://msdn.microsoft.com/en-us/library/windows/apps/xaml/hh868263.aspx
        # http://msdn.microsoft.com/en-us/library/windows/apps/hh465435.aspx
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $response = curl_getinfo( $ch );
        curl_close($ch);
    
        $code = $response['http_code'];
        
        if($code == 200){
            return new WPNResponse('Successfully sent message', $code);
        }
        else if($code == 401){
            $this->access_token = '';
            return $this->post_tile($uri, $xml_data, $type, $tileTag);
        }
        else if($code == 410 || $code == 404){
            return new WPNResponse('Expired or invalid URI', $code, true);
        }
        else{
            return new WPNResponse('Unknown error while sending message', $code, true);
        }
    }
    
    private function get_access_token(){
        if($this->access_token != ''){
            return;
        }
        $str = "grant_type=client_credentials&client_id=$this->sid&client_secret=$this->secret&scope=notify.windows.com";
        $url = "https://login.live.com/accesstoken.srf";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$str");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);                       
        $output = json_decode($output);
        if(isset($output->error)){
            throw new Exception($output->error_description);
        }
        $this->access_token = $output->access_token;
    }	
	
	// Curl 
	private function useCurl($url, $headers , $fields) {
        $ch = curl_init();
        if ($url) {
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            if ($fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            }
            // Execute post
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
            // Close connection
            curl_close($ch);
       //checking the response code we get from fcm for debugging purposes
            echo "http response " . $httpcode;
       //checking the status/result of the push notif for debugging purposes
            echo $result;
            return $result;
        }

    }
}
}
?>