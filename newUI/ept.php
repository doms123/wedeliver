<?php 

    ini_set('max_execution_time', 0);
    ini_set('display_errors', 1);
    date_default_timezone_set('Europe/London');

    require_once '../vendor/autoload.php';
    require_once '../meekrodb.2.3.class.php';
    require_once '../src/Facebook/autoload.php';
    require_once("../includes/ActiveCampaign.class.php");

    include 'eptDataFunctions.php';
    include 'eptIncludes.php';
    callIncludeEptFiles();
    
    includeFiles();
    use \DrewM\MailChimp\MailChimp;
    include('./MailChimp.php'); 

    DB::$user = 'eptdb';
    DB::$password = '5WB5Y6ZPi!@6vY';
    DB::$dbName = 'eptdb';    
    $infusionsoft=NULL;
    
    
    $client="ept";
    $web=true;  
    if (PHP_SAPI === 'cli') {
        $web=false;
        //maintAddColumn("Contact", "alter table %l add Phone1 varchar(255) after EmailAddress3");
        $args=array_slice($argv, 1);
        if (count($args) > 0) {
            while (count($args) > 0) {
                $arg=array_shift($args);
                switch ($arg) {
                    case "-u":
                        $userId=array_shift($args);
                        $userDetails=DB::queryFirstRow("select userId, fbId, fbIntId, fbFirstName, fbLastName, fbEmail, isContactId, isEmail, platform, appName, appDomainName, accessToken, refreshToken, expiresAt, tokenType, scope, authhash from tblEptUsers u where userId=%i", $userId);
                        break;
                        
                    case "-uc":
                        $op="updateContactData";
                        break;
                    case "-p":
                        $startPage=array_shift($args);
                        break;
                    default:
                        printf("ERROR: Unknown argument %s\n", $arg);
                        die();
                        break;
                } 
            }
        }
        
        //exit;
    } else {
        $op=readRequest("op", "");
        $reportId=readRequest("reportId", 0);
        $oauthDebug=readRequest("oad", "true");
        $debug=readRequest("debug", "false");
        $reset=readRequest("reset", "false");
        $fbId=readRequest("fbId", 0);
        $isContactId=readRequest("isContactId", 0);
        $membEmail=readRequest("membEmail", "");
        $fbFirstName=readRequest("fbFirstName", "");
        $fbLastName=readRequest("fbLastName", "");
    
        $debug=$debug == "true" ? true:false;

        
    	if ($op == "zapUserAccess") {
	
            $log=fopen("/var/www/html/upd-wdem/ept.log", "a");
            fprintf($log, "%s: Op %s - FbId %d - Name %s %s\n", date("Y-m-d H:i:s"), $op, $fbId, $fbFirstName, $fbLastName);
        
            // Create one time hash
            $oth = md5(sprintf('%s_ept_%d_%s_auth_hash_23492309usdINOWIF', date("Y-m-d-H:i:s"), $fbId, $fbFirstName));
            // Need last 8 characters of hash
            $oth = substr($oth, -8, 8);
        
            DB::insertUpdate("tblEptUsers", array(
                'fbId' => $fbId,
                'fbFirstName' => $fbFirstName,
                'fbLastName' => $fbLastName,
                'OneTimeHash' => $oth,
                'client' => 'ept',
                'oneTimeHashExpires' => time()+1800)
            );	
        
            header('Content-Type: application/json');
        
            printf('{"oth": "%s"}', $oth);
            print "\n";
            
            fclose($log);
            exit;
        }else if ($op == "wdeUserAccess") {
            $log=fopen("/var/www/html/upd-wdem/ept.log", "a");
            fprintf($log, "%s: Op %s - isContact %d - Email %s - Name %s %s\n", date("Y-m-d H:i:s"), $op, $isContactId, $membEmail, $fbFirstName, $fbLastName);
        
            // Create one time hash
            $oth = md5(sprintf('%s_ept_%d_%s_auth_hash_23492309usdINOWIF', date("Y-m-d-H:i:s"), $fbId, $fbFirstName));
            // Need last 8 characters of hash
            $oth = substr($oth, -8, 8);
        
            DB::insertUpdate("tblEptUsers", array(
                'isContactId' => $isContactId,
                'fbFirstName' => $fbFirstName,
                'fbLastName' => $fbLastName,
                'membEmail' => $membEmail,
                'OneTimeHash' => $oth,
                'client' => 'ept',
                'oneTimeHashExpires' => time()+1800)
            );	
        
            header('Content-Type: application/json');
        
            printf('{"oth": "%s"}', $oth);
            print "\n";
            
            fclose($log);
            exit;		
        }

    } 
    if(empty(session_id())) {
		session_start();
    }  
    

	// Turn off output buffering
	ini_set('output_buffering', 'off');
	// Turn off PHP output compression
	ini_set('zlib.output_compression', false);
	// Implicitly flush the buffer(s)
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	// Clear, and turn off output buffering
	while (ob_get_level() > 0) {
		// Get the curent level
		$level = ob_get_level();
		// End the buffering
		ob_end_clean();
		// If the current level has not changed, abort
		if (ob_get_level() == $level) break;
	}   

	$sessionId=session_id();
    $fbAuth=false;
    //print_r($_SESSION);
	if (isset($_SESSION['login_status']) && $_SESSION['login_status'] == 'login') {
		$fbAuth=true;
	}else{
		require_once '../'.'login.php';
	}    
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php echo initializedCSS(true);   //print css  ?>
    <?php echo initializedJavascript();   //print js script     ?>
</head>

<body>
    <div id="wrapper">       
        <?php 
            $showSession=false;
            if($showSession)
            {
                echo '<pre>';
                echo session_id();
                print_r($_SESSION);
                echo '<br/>';
                print_r(getUserDetailsByUID('233'));
                echo 'sessionId: ' . $sessionId;
                echo '</pre>';
            }
            /*
            echo '<pre>';
            print_r(getUserDetailsAll());
            echo '</pre>';
            echo '<script>alert('.$fbAuth.');</script>';
            */
            if(!$fbAuth)
            {
                
                echo fbAuthentication();
                //echo navigationMenu();    //print navigation and side panel 
            }        
           else
            {
                $fbIntId=$_SESSION['user_id']; //facebook id
                $userDetails=getUserDetails($sessionId);
                //$userDetails=getUserDetailsByUID('319'); // for testing, jeremy flanagan
                //$userDetails=getUserDetailsByUID('327'); // for testing, jeremy flanagan
                $platform=$userDetails["platform"];
                if ($platform == "ISFT") {
                    $infusionsoft = new \Infusionsoft\Infusionsoft(array(
                        'clientId'     => 'ukkty8jzhv523ernkvbzam6u',
                        'clientSecret' => 'qbpxUvVwH4',
                        'redirectUri'  => 'https://wdem.wedeliver.email/eptauth.php',
                    ));	
                }
                if ($reset == "true") {
                    if ($oauthDebug == "true") {
                        echo '<p>Resetting OAuth Tokens</p>';
                        unset($userDetails['refreshToken']);
                    }	
                }   
                if ($reset == "true") {
                    if ($oauthDebug == "true") {
                       // echo '<p>Resetting OAuth Tokens</p>';
                        debugOut("<p>Resetting OAuth Tokens</p>");
                        unset($userDetails['refreshToken']);
                    }	
                } 

                if (isset($_GET['code'])) {
                   // $hideDashboard=true;
                    try {
                        $token=$infusionsoft->requestAccessToken($_GET['code']);
                    } catch(Infusionsoft\Http\HttpException $e) { 
                        print("<h2>Invalid Authorisation Code</h2>\n");
                        print('<a href="#" onclick="javascript:ShowAuth("xx' . $infusionsoft->getAuthorizationUrl() . '")">Click here to authorise Infusionsoft</a>');
                        //htmlTerminate();
                    }
                } else if (isset($userDetails['refreshToken'])) {
                    $token=new \Infusionsoft\Token(array(
                        'access_token' => $userDetails['accessToken'],
                        'refresh_token' => $userDetails['refreshToken'],
                        'expires_in'    => $userDetails['expiresAt']-time(),
                        'token_type' => $userDetails['tokenType'],
                        'scope' => sprintf("%s|%s", $userDetails['scope'], $userDetails['appDomainName'])
                    ));
                    $infusionsoft->setToken($token);
                    $token=$infusionsoft->getToken();
                    if ($oauthDebug == "true") {
                        debugOut("<hr>");
                        debugOut("<p>Using Token From Database</p>");
                        debugPrintR($userDetails);
                        debugPrintR($token);                   
                    }	
                
                    $authHash=$userDetails['authhash'];
                } else {
                   // htmlMinimalPage();
            //		htmlPageTemplate("--", "--", "--", "--", "--");
                    print "<div class='w3-container'>\n";
                    print "<h2>Welcome to Email Power Tools</h2>\n";
                    print '<h2>You need to connect to an Infusionsoft application</h2><a href="#" onclick="javascript:ShowAuth(\'' . $infusionsoft->getAuthorizationUrl() . '\')">Click here to authorise Infusionsoft and select your app</a>';
                    print "</div>\n";
                   // htmlTerminate();
                }                
                
                /* 
               // $token=$infusionsoft->getToken(); 
                $token=$infusionsoft->refreshAccessToken();
                echo '<pre>';
                print_r( $token);
                echo '</pre>';    
                */         
                             
                if($userDetails)
                {
                    $appName=$userDetails["appName"];
                    echo navigationMenu($userDetails, $infusionsoft);    //print navigation and side panel 
                    echo '<div id="page-wrapper">';
                    //echo printDashboardPage();   //print navigation and side panel
                    //echo $infusionsoft->getAuthorizationUrl();
                    
                    $cacheInfo=getAllMySqlLastUpdate($appName);
                    foreach ($cacheInfo as $tableCache) {
                        $daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
                    }
                    $days=$daysSince["EmailAddStatus"];

                    if(strpos($op, 'opportunityActivityReport') !== false) {
                        echo printOpportunityActivityReport($appName, $sessionId, $userDetails, $op);
                    }else {
                        switch ($op) {
                            case "lostCLV":
                                $dataToString=postActionLostCLV($appName);
                                echo printLostCLVPage($days, $dataToString);                 
                                break;
                                
                            case "lostCustomersReport":
                                //echo 'days :>> ' . $days. $appName;
                                /*
                                echo '<pre>';
                                print_r( $optTypeTranslate);
                                echo '</pre>';
                                */
                                $dataToString=Report_postActionLostCLV($appName, $optTypeTranslate);  
                                //echo 'days :>> ' . $days . $dataToString;          
                                echo Report_printLostCLVPage($days,  $dataToString); 
                                break;
                            case "showstatus":       
                                $cacheInfo=getAllMySqlLastUpdate($appName);    
                                //print_r($cacheInfo);                                         
                                echo printShowStatus($cacheInfo); 
                                break;   
                            case "emailOpenReport":                         
                                echo printOpenEmailReport($appName); 
                                break;    
                            case "updateContactData":                         
                                echo printUpdateContractData($appName); 
                                break;          
                                //emailOpenReport   
                            case "updateOpportunityData": 
                                echo printUpdateOpportunityData($appName, $sessionId, $userDetails, $op); 
                                break; 
                            case "kleanApiConfig": 
                                echo printKleanApiConfig($appName, $sessionId, $userDetails, $op); 
                                break; 
                            case "kleanOnDemandScrub": 
                                echo printKleanOnDemandScrub($appName, $sessionId, $userDetails, $op); 
                                break;
                            default:
                                echo '<br/>';
                                echo printDashboardPage();
                                break;
                        }  
                    }                        //               
                  // print_r($userDetails);
                    //echo $op;
                    /*
                    if($op=="lostCLV")
                    {
                        $appName=$userDetails["appName"];
                        $cacheInfo=getAllMySqlLastUpdate($appName);
                        foreach ($cacheInfo as $tableCache) {
                            $daysSince[$tableCache["tableName"]]=$tableCache["daysAgo"];
                        }
                        $days=$daysSince["EmailAddStatus"];
                        //echo 'una>> ' . $appName;
                        $dataToString=postActionLostCLV($appName);
                       // echo 'dalawa>> ' . $appName;
                        echo printLostCLVPage($days, $dataToString);  
                        //echo testUI();                      
                    }
                    else
                    {
                        echo printDashboardPage();
                    }
                    */
                    echo ' </div>';

                }
                else
                {
                    echo callMannyChat();
                   // echo '<div id="page-wrapper">';
                    echo $_SESSION['user_id'];
                    echo PHP_SAPI;
                   // echo ' </div>';
                }
                
                //print_r(getUserDetails($_SESSION['user_id']));
            } 
                //printDebug();   //print navigation and side panel            
                 
        ?>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->   
</body>

</html>


