var tm = angular.module('tm', ['ngRoute']);



function tmController($scope, $http, $timeout, $sce,$window)
{	

  	$scope.status = "Loading...";

    $scope.loading = true;
    $scope.loadmessage = "loading";
    $scope.loadpercent = 100;
    $scope.extraloadingclass = "";


  	$scope.colorAlreadySet = 0;
  	$scope.backgroundAlreadySet = 0;

  	$scope.checkWait = 100;
	$scope.checkFacebook = function(){
		console.log("facebookid:"+facebookid);
		if(facebookid)
		{
			console.log("Logged in to facebook as "+facebookid+"...");
			$scope.facebookpic  = "http://graph.facebook.com/"+facebookid+"/picture";
			$scope.facebooklink = "https://www.facebook.com/"+facebookid;
		}
		else
		{
			$scope.checkWait*=1.6;
			$timeout(function(){$scope.checkFacebook()},$scope.checkWait);
		}
	}
	$timeout(function(){$scope.checkFacebook()},250);


  	$scope.login =function(item){
  		console.log("login");
  		$window.location.href = "https://m.facebook.com/dialog/oauth?client_id=227668560771596&redirect_uri=http://"+phphost+"";
  	}

  	$scope.logout =function(item){
  		console.log("logout");
  		FB.logout();
  		$window.location.href = "/";
  	}

  	$scope.upload = function(item){
  		console.log("upload");
  		$scope.performClick(document.getElementById('file'));
  	}

  	$scope.performClick = function(node) {
	   var evt = document.createEvent("MouseEvents");
	   evt.initEvent("click", true, false);
	   node.dispatchEvent(evt);
	}
  	

  	$scope.view =function(item){
  		//input = '<iframe src="https://embed.spotify.com/?uri='+input+'" width="300" height="80" frameborder="1" allowtransparency="true"></iframe>';
		var output = "";
  		if(item.text.startsWith("spotify:"))
  			output = '<iframe src="https://embed.spotify.com/?uri='+item.text+'" width="300" height="80" frameborder="0" allowtransparency="true"></iframe>'
  		else if(item.text.startsWith("background:"))
  		{
  			var setTo = item.text.replace("background:","");
  			if(!$scope.backgroundAlreadySet)
  			{
  				$scope.backgroundAlreadySet=1;
  				console.log("CHANGING BACKGROUND");
  				document.body.style.backgroundImage="url('"+setTo+"')";
  				console.log("BACKGROUND IS NOW:"+document.body.style.backgroundImage);
  			}
  			output="<i>changed background image to <a target='_blank' href='"+setTo+"'><img src='"+setTo+"' class='backgroundpreview'></a></i>";
  			
  		}
  		else if(item.text.startsWith("color:"))
  		{
  			var setTo = item.text.replace("color:","");
  			if(!$scope.colorAlreadySet)
  			{
  				$scope.colorAlreadySet=1;
	  			document.body.style.color=setTo;
	  		}
  			output="<i>changed color to "+setTo+"</i>";
  		}
  		else if(item.contenttype)
  			output = '<a target="_blank" href='+item.text+'><img src="'+item.text+'" class="postedimages"></a>';
  		else if(item.text.startsWith("http"))
  			output = '<a target="_blank" href='+item.text+'>'+item.text+'</a>';
  		else
  			output = item.text.replace(/\+/g, ' ');
		return $sce.trustAsHtml(output);
  	}

  	$scope.viewHearts =function(item){
  		//input = '<iframe src="https://embed.spotify.com/?uri='+input+'" width="300" height="80" frameborder="1" allowtransparency="true"></iframe>';
		var output = "";



		var foundme = 0;
  		if(item.likes)
  		{
	  		for(var i=0;i<item.likes.length;i++)
	  		{
	  			output="<A href='http://facebook.com/"+item.likes[i]+"' target='_blank'><img src='http://graph.facebook.com/"+item.likes[i]+"/picture' class='heartpics'></a> "+output;	
	  		}  
	  	}

		return $sce.trustAsHtml(output);
  	}

  	$scope.viewButton =function(item){
  		//input = '<iframe src="https://embed.spotify.com/?uri='+input+'" width="300" height="80" frameborder="1" allowtransparency="true"></iframe>';
		var output = "";

		var foundme = 0;
  		if(item.likes)
  		{
	  		for(var i=0;i<item.likes.length;i++)
	  		{	
	  			if(item.likes[i]==facebookid) {foundme=1;break;}
	  		}
	  	}

	  	if(item.user==facebookid)
		{
			//this is mine, don't show like button
  				output = output+"<span class='glyphicon glyphicon-trash heart'></span>";
		}
		else
		{
			if(foundme)
	  			output = output+"<span class='glyphicon glyphicon-heart heart heartfull'></span>";
	  		else
  				output = output+"<span class='glyphicon glyphicon-heart-empty heart'></span>";
		}
		return $sce.trustAsHtml(output);
  	}

  	$scope.heart = function(item){
  		console.log("HEART!"+item.id);
  		console.log(item);

  		//check if we are logged in yet, if not, you can't send stuff
  		if(!facebookid) $scope.login();

  		//make sure my id isn't already there
  		if(item.likes)
  		{
	  		for(var i=0;i<item.likes.length;i++)
	  		{
	  			if(item.likes[i]==facebookid) {console.log('already liked!');return;}
	  		}
	  	}
  		senddata = {'heart':item.id, 'user':facebookid};

  		$http.post('/api/',senddata)
    	.success(function(data, status, headers, config) {
    		console.log("POSTED");
    		console.log(data);
        }).error(function(data, status) { 
        	console.log("ERROR");
        	console.log(status);
        });

  	}


  	$scope.go = function(){
  		$scope.backgroundAlreadySet=0;
  		$scope.colorAlreadySet=0;
  		console.log($scope.inputtext);

  		//check if we are logged in yet, if not, you can't send stuff
  		if(!facebookid) $scope.login();

  		if($scope.inputtext!="")
  		{
	  		$scope.callStart = Date.now();
	  		var senddata = {'text': $scope.inputtext, 'user':facebookid};
			$scope.inputtext="";
			console.log("SENDING:");
			console.log(senddata);
	    	$http.post('/api/',senddata)
	    	.success(function(data, status, headers, config) {
	    		console.log("POSTED");
	    		console.log(data);
	        }).error(function(data, status) { 
	        	console.log("ERROR");
	        	console.log(status);
	        });
	    }
  	}

  	$scope.currentHash = 0;

    $scope.init = function() {
    	$scope.backgroundAlreadySet=0;
    	$scope.colorAlreadySet=0;
    	$scope.callStart = Date.now();
    	console.log("GET hash="+$scope.currentHash);
    	$http({method: 'GET', url: '/api/?hash='+$scope.currentHash}).
	    success(function(data, status, headers, config) {

        //hide the loading screen...
        $scope.extraloadingclass="fadeOut";
        $timeout(function(){$scope.loading=false;$scope.extraloadingclass="";},1000);
        

	    	//parse data and call again
	      	console.log("2md5 of:"+JSON.stringify(data));
	      	$scope.currentHash = md5(JSON.stringify(data));
	      	console.log("NEW HASH:"+$scope.currentHash);
	      	$scope.callTime = Date.now()-$scope.callStart;
	      	for(var i=0;i<data.length;i++)
	      	{
	      		data[i] = JSON.parse(data[i]);
	      		data[i].text = decodeURIComponent(data[i].text);
	      	}
	      	if($scope.currentData!=data) $scope.currentData=data;
	      	console.log("CURRENT DATA:");
	      	console.log($scope.currentData);
	      	console.log($scope.callTime);
	      	var nextCallIn = 1;
	      	var minDelay = 1000;
	      	if($scope.callTime<minDelay)
	      	{
	      		nextCallIn=minDelay-$scope.callTime;
	      	}
	     	$timeout($scope.init,nextCallIn);
	    }).
	    error(function(data, status, headers, config) {
	      console.log("ERROR");
	      console.log(status);
	      console.log(headers);
	      console.log(data);
	    });

	    document.getElementById("inputline").focus();
    }
}

if (typeof String.prototype.startsWith != 'function') {
  // see below for better implementation!
  String.prototype.startsWith = function (str){
    return this.indexOf(str) == 0;
  };
}