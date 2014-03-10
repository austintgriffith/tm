<?php
	$page = trim($_SERVER['REQUEST_URI'],'/');
	$qat = strpos($page,"?");
	if($qat) $page = substr($page,0,$qat);
	if(!$page) header('Location:demo');
?><!DOCTYPE html>
<?php date_default_timezone_set("America/Denver"); ?>
<html ng-app="tm">
<head>
    <title><?php echo($page) ?></title>
    <meta name = "viewport" content = "width=device-width,user-scalable=no">
    <link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <script type="text/javascript"
        src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.3/angular.min.js"></script>
    <script type="text/javascript"
        src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.3/angular-route.js"></script>
        
    <script type="text/javascript" src="tm.js"></script>
    <script type="text/javascript" src="md5.js"></script>
</head>
<body>
	<div id='cont' ng-app="tm" ng-controller="tmController" ng-init="init()">
	<script>
		var phphost = "<?php echo($_SERVER['HTTP_HOST']); ?>";
		var loading = false;
		var page = "<?php echo($page); ?>";
	</script>
    	<form ng-submit="go()" style='padding-bottom:50px;'>

			<input id="inputline" type="text" ng-model="inputtext" class="text-line"/>
			<div class='uploadbutton' ng-click='upload()'>
				<div class="glyphicon glyphicon-file" style='margin-top:-55px;margin-right:10px;float:right;font-size:40px;opacity:0.4'></div>
			</div>
			<div class='loginbutton' ng-click='login()' ng-hide='facebookpic'>
				<img src='fb.png' style='width:50px;height:50px;opacity:0.6'>
			</div>
			
			<div class='loginbutton' ng-click='logout()' ng-show='facebookpic'>
				<img src='{{facebookpic}}'>
			</div>
			<input type='submit' id='submit' value='+' style='position:absolute;left:-1000px;'/>
		</form>
		<form id='form' action='/api/' method='POST' enctype="multipart/form-data">
			<input type='hidden' id='fileuserid' name='user'>
			<input type='hidden' id='page' name='page' value='<?php echo($page); ?>'>
			<input type="file" name="file" id="file" style='position:absolute;left:-1000px'>
		</form>
		<script>
			document.getElementById("file").onchange = function() {
				console.log("UPLOADING!");

				document.getElementById("uploader").style.display = "block";
				setTimeout(function(){
					document.getElementById("uploadprogress").style.width = "50%"; 
				},1000);
				setTimeout(function(){
					document.getElementById("uploadprogress").style.width = "70%"; 
				},3000);
				setTimeout(function(){
					document.getElementById("uploadprogress").style.width = "90%"; 
				},7000);

				document.getElementById("fileuserid").value=facebookid;
		    	document.getElementById("form").submit();
			};
		</script>


		<div class='floattitle' style='{{daytitlestyle}}'><?php echo date("Y.m.d"); ?></div>
    	<div class='floatlefttitle' style='{{pagetitlestyle}}'><?php echo $page; ?></div>

    	<div ng-repeat="item in currentData" class='contentbox'>
    		<div class='face'>
    			<a target='_blank' href='http://facebook.com/{{item.user}}'><img class='facebookpics' src='http://graph.facebook.com/{{item.user}}/picture'></a>
    		</div>
    		
    		<div ng-bind-html="viewButton(item)" ng-click='heart(item)' class='likebar'>
    		</div>
    		<div ng-bind-html="viewHearts(item)" class='likebar'>
    		</div>

    		<div ng-bind-html="view(item)" class='itemview'>
    		</div>
    		
    	</div>
    

    <script>
		var facebookid = 0;
		var allfacebookdata = 0;

		window.fbAsyncInit = function() {
		  FB.init({
		    appId      : '227668560771596',
		    status     : true, // check login status
		    cookie     : true, // enable cookies to allow the server to access the session
		    xfbml      : true,  // parse XFBML
		    oauth      : true,  
		    channelUrl: 'http://'+phphost+'/channel.html' //custom channel
		  });

		  FB.getLoginStatus(function(response) {
		    console.log(response);
		  if (response.status === 'connected') {
		    var accessToken = response.authResponse.accessToken;
		  } else if (response.status === 'not_authorized') {
		    // the user is logged in to Facebook, 
		    // but has not authenticated your app
		  } else {
		    // the user isn't logged in to Facebook.
		  }
		 });

		  FB.Event.subscribe('auth.authResponseChange', function(response) {

		    if (response.status === 'connected') {
		      testAPI();
		    } else if (response.status === 'not_authorized') {
		      FB.login();
		    } else {
		      FB.login();
		    }
		  });
		  };

		  // Load the SDK asynchronously
		  (function(d){
		   var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
		   if (d.getElementById(id)) {return;}
		   js = d.createElement('script'); js.id = id; js.async = true;
		   js.src = "//connect.facebook.net/en_US/all.js";
		   ref.parentNode.insertBefore(js, ref);
		  }(document));

		  // Here we run a very simple test of the Graph API after login is successful. 
		  // This testAPI() function is only called in those cases. 
		  function testAPI() {
		    console.log('Welcome!  Fetching your information.... ');
		    FB.api('/me', function(response) {
		      console.log('Good to see you, ' + response.name + '.');
		      console.log(response);
		      allfacebookdata=encodeURIComponent(response);
		      facebookid=response.id;
		    });
		  }
	</script>
	<div id='loader' class='loading {{extraloadingclass}}' ng-show='loading'><span class='innerloading'>{{loadmessage}}</span><div class="progress progress-striped active innerbar">
  <div class="progress-bar"  role="progressbar" aria-valuenow="{{loadpercent}}" aria-valuemin="0" aria-valuemax="100" style="width: {{loadpercent}}%">
    <span class="sr-only">{{loadpercent}}% {{loadmessage}}</span>
  </div>
</div></div>


<div id='uploader' class='loading' style='display:none;'><span class='innerloading'>uploading</span><div class="progress progress-striped active innerbar">
  <div id='uploadprogress' class="progress-bar"  role="progressbar" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100" style="width: 33%">
    <span class="sr-only">33% uploading</span>
				</div></div></div>

</div>
</body>
</html>
