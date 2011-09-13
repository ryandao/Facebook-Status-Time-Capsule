<?php 
// Status Time Capsule FB Application
// 8/16/2011 

// Include the main Facebook PHP library
require_once 'php-sdk/facebook.php';
// Include the component files
require_once 'includes/status.php';
require_once 'includes/graph.php';
require_once 'includes/feed.php';
//require_once 'includes/pics.php';

// FB App details	
$app_id = "233868763323548";
$app_secret = "9fa776021a4760d8946d74a9dbeac900"; 
$canvas_page = "http://apps.facebook.com/status-timecapsule/";//"http://ec2-75-101-236-189.compute-1.amazonaws.com/";	

// Create the facebook API object
$facebook = new Facebook(array(
	'appId' => $app_id,
	'secret' => $app_secret,
));
  	

// Check if the user id is present
// If it is present, the user has used the application before
// If it isn't, this is the user's first time on the application


$user = $facebook->getUser();

if ($user) {
  try {
    $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    $user = null;
  }
}

if (!$user) {//empty($data["user_id"])) {

	// If the user id is not present, redirect the user to the 
	// page to allow the application access to his data
	
	// Permissions that the application requires
	$fb_perms_required = "user_status, read_stream, publish_stream"; 

	$auth_url2 = "http://www.facebook.com/dialog/oauth?client_id=" 
		. $app_id
 . "&redirect_uri=" . urlencode($canvas_page)
		. "&scope=" . $fb_perms_required;
	$fb_root = 'http://apps.facebook.com/the_time_capsule';
	$auth_url = $facebook->getLoginUrl(array('scope'=>$fb_perms_required,'redirect_uri'=>$canvas_page));//'canvas'=>1,'fbconnect'=>0,'next'=>$fb_root));//,'display'=>'iframe'));//'grant_type'=>'client_credentials'));//'type'=>'user_agent'));
	// send a script tag to
	echo "<script>top.location.href='" . $auth_url . "';
	// Make Google Analytics happy
	var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-803292-17']);
  _gaq.push(['_setDomainName', '.compute-1.amazonaws.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
	
	</script>";
	// type='text/javascript'
	// Stop script execution. Any code below exit() will not be executed.
	exit(); 
	
}

//authentication with user_status, read_stream permission
if(isset($_POST["signed_request"])) {
	$signed_request = $_POST["signed_request"];
  // The portion of signed request before the first period contains the signature
  // The portion after contains the encoded data
  // See: http://developers.facebook.com/docs/authentication/signed_request/
  list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

  // The data is encoded by base 64. We have to do some decoding to get the 
  // actual piece of data. For explanation on the details, see the signed_request link above
  $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
  
  try {
    // Get the user information and store it in a global variable
    $user = $facebook->api('me/');
  }
  catch (OAuthException $e) {
    // This means that the deauthorize callback is being called, and the app is no longer authorized to access the user's data.
  }
}




/***************************************************************************
 ******************************** Main Page ********************************
 ***************************************************************************/


function index_page() {
  global $user;
  print theme_header(TRUE);
  // Print a welcome message 
  // $user['name']
  print <<<EOS
<h1>Status Time Capsule</h1>
    <div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#appId=162720610474682&amp;xfbml=1"></script>
    <input type="button" value="Invite friends" class="uibutton" id="button-invite" width="110" style="width:110px;vertical-align:top;" title="Invite your friends!" />
    <fb:like href="http://www.facebook.com/apps/application.php?id=233868763323548" send="true" width="605" show_faces="false" font=""></fb:like>
    
EOS;
  print theme_footer();
}


function ajax_content_page() {
  global $user;  
  $statuses = statuses_retrieve();
  
  // Display graph
  print_graph();
  // FB API placeholder
  echo "<div id='fb-root'></div>";
  // Display karma index
  print_karma($statuses);
  
  print <<<EOS
<div id="nav-buttons">  
<input type="button" value="Your most popular statuses" class="uibutton tab-main confirm" id="tab-pop" />
<input type="button" value="Your oldest statuses" class="uibutton tab-main" id="tab-oldest" />
<input type="button" value="All your statuses" class="uibutton tab-main" id="tab-allstatus" />
<input type="button" value="Most popular friends" class="uibutton tab-main" id="tab-topuser" />
</div>  
EOS;
  print '<div class="main-tab-member" id="tab-pop-content">';
  // Display the most popular status
  print_most_popular($statuses);  
  print '</div><div class="main-tab-member" id="tab-oldest-content">';
  // Display the oldest status
  print_oldest($statuses);
  print '</div><div class="main-tab-member" id="tab-allstatus-content">';
  // Display all statuses
  print_statuses($statuses);
  print '</div><div class="main-tab-member" id="tab-topuser-content">';
  
  // I know this is ugly, but data for new users needs to be inserted to the cache before printing out the leaderboard...
  if (!empty($user)) {
    log_user($user);
  }
  
  print_leaderboard(TRUE, 5);
  print_leaderboard(FALSE, 10);
  print '</div>';
  
  print theme_links();
  //$pics = pics_retrieve();
  //print_pics($pics);
  
}


/***************************************************************************
 ***************************** Page dispatch *******************************
 ***************************************************************************/

function theme_header($really_load=FALSE) {
  global $user;
  global $app_id;
  	
  // $really_load is TRUE only for the main loading page, FALSE on other pages (eg privacy policy).
	$body_class = $really_load ? '' : ' class="standalone-page"';
  
  return <<<EOS
	<!DOCTYPE html>
	<html lang="en" xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
	  <title>Status Time Capsule</title>
	  <meta charset="utf-8">
	  <link rel="stylesheet" type="text/css" href="styles/style.css" />
	  <link href='http://fonts.googleapis.com/css?family=Yanone+Kaffeesatz|Droid+Sans:400,700' rel='stylesheet' type='text/css'>
      <script language="javascript" type="text/javascript">
  
EOS
        . 'var _really_load = ' . ($really_load ? 'true' : 'false') . ';'
        . 'var _POST = ' . json_encode($_POST) . ';'
. <<<EOS
  	  </script>
  	  <script src="http://connect.facebook.net/en_US/all.js"></script>
	  <script language="javascript" type="text/javascript" src="scripts/jquery-1.6.2.min.js"></script>
	  <script language="javascript" type="text/javascript" src="scripts/spin.min.js"></script>
      <script language="javascript" type="text/javascript" src="scripts/loading.js"></script>
      <script language="javascript" type="text/javascript" src="scripts/graph.js"></script>
      <script language="javascript" type="text/javascript" src="scripts/bump.js"></script>
      <script language="javascript" type="text/javascript" src="scripts/share.js"></script>  
      <script language="javascript" type="text/javascript" src="js/flot/jquery.flot.js"></script>
      <script type="text/javascript">

        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-803292-17']);
        _gaq.push(['_setDomainName', '.compute-1.amazonaws.com']);
        _gaq.push(['_trackPageview']);

        (function() {
           var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
           ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
           var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
        
      </script>
      <script type="text/javascript">
EOS
      . 'var user_name = "' . $user['name'] . '";'
      . 'var user_id = "' . $user['id'] . '";'
. <<<EOS
  	    $(document).bind('loaded', function() {    	      
  	      // Initialize the JS SDK
		  FB.init({
		    appId  : {$app_id},
		    status : true,  // check login status
		    cookie : true,  // enable cookies to allow the server to access the session
		    xfbml  : true,  // parse XFBML
		    oauth  : false, // we're not ready for OAuth yet
		  });
		});
	  </script>
</head>

<body$body_class>
<div id="wrapper">

EOS;
}


function theme_links() {
  $footer_links = array(
    'ajax_content' => '<a href="http://apps.facebook.com/status-timecapsule/" target="_top">Back to app</a>',
    'popularity' => '<a href="/?q=popularity" target="blank">How we calculate popularity</a>',
    'privacy' => '<a href="/?q=privacy" target="blank">Privacy policy</a>',
    '_link_to_app_page' => '<a href="http://www.facebook.com/apps/application.php?id=233868763323548" target="blank" title="Post your reviews or contact the developers">App page and wall</a>',
  );
  
  // Do not show link to current page
  global $module;
  unset($footer_links[$module]);
  
  $return = '<div id="footer">' . implode(' &bull; ', $footer_links) . '</div>';
  return $return;
}

function theme_footer() {
  return <<<EOS
  <div id="spinner" style="margin-top:3em;"></div>
<div id="main"></div>
</div>
</body>
</html>
EOS;
}


function page_dispatch() {
  global $module;
  $module_whitelist = array('index', 'ajax_content', 'privacy', 'deauthorize', 'popularity');
  
  $module = 'index'; // default module
  if (isset($_GET['q']) && in_array($_GET['q'], $module_whitelist)) {
    $module = $_GET['q'];
  }

  // Optionally you may put the callbacks inside the following file, see json.php for an example
  $page_fn = dirname(__FILE__) . "/includes/$module.php";
  if (file_exists($page_fn)) {
    require_once $page_fn;
  }
  $page_callback = "{$module}_page";
  $page_callback();
}


page_dispatch();


?>
