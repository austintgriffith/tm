<?php
date_default_timezone_set("America/Denver");
set_time_limit(600);
require_once("amazon.php");
require_once("cache.php");
$cache = new Cache();
$today = date("Ymd");
$creds = json_decode(file_get_contents("../awscreds.txt"));
$db = new AwsDbClient($creds->key,$creds->secret,$creds->region,"chat");
$postdata = file_get_contents("php://input");

$doredirect = false; //if they are directed here not api called then you want to redirect back

if($_FILES)//if a file was uploaded // move it to s3 and provide a link
{
	echo "FILE(S) UPLOADED!";
	foreach($_FILES as $file)
	{
		//echo $file['name'].",".$file['type'].",".$file['tmp_name'].",".$file['error'].",".$file['size'].",";
		$filepath = $file['tmp_name'];
		if($file['error'])
		{
			echo "ERROR UPLOADING ".$file['name'].": ".$file['error']."\n\n".print_r($_FILES);exit(0);
		}
		else
		{
			if(!file_exists($filepath))
			{
				echo "ERROR: $filepath doesn't exist!\n\n".print_r($_FILES);exit(0);
			}
			else
			{
				//upload $filepath to s3...
				$s3 = new AwsS3Client($creds->key,$creds->secret,$creds->region);
				$url = $s3->putPublicObject("trustymusket",date("Ymd")."/".microtime_float().$file['name'],$filepath);
				$postdata = array();
				$postdata['user']=$_REQUEST['user'];
				$postdata['text']=$url;
				$postdata=json_encode($postdata);
				$doredirect=true;

				echo "FILE UPLOADED TO S3:".$url." SETTING POST...";
			}

		}
	}
}


if($postdata)
{
	$postdata = json_decode($postdata,true);
	$postdata['text'] = urlencode($postdata['text']);
	$currentData = json_decode($cache->get($today));
	if(!$currentData) $currentData=array();
	//echo "CURRENT:";
	print_r($currentData);

	if($postdata['heart'])
	{
		//heart 
		print_r($postdata);
		$founditemat=0;
    	foreach($currentData as $item)
    	{
    		$founditemat++;
    		$item=json_decode($item);
    		//echo "\npost:".$postdata['id']." ITEM:".$item->id."\n";
    		//print_r($item);
    		file_put_contents("post.log","--------:".$_SERVER['REMOTE_ADDR']." HEARTS\n".$item->id.",".$postdata['heart']."..\n", FILE_APPEND | LOCK_EX);
    		if($item->id==$postdata['heart'])
    		{
    			echo "\nFOUND ITEM TO HEART:".$founditemat;
    			break;
    		}

    	}


    	if($founditemat!=-1)
		{
			$item=json_decode($currentData[$founditemat-1]);
			echo "\n\nLOADED ITEM:\n";
			print_r($item);

			if($item->user==$postdata['user'])
			{
				unset($currentData[$founditemat-1]); // remove item
				$currentData = array_values($currentData); 
			}
			else
			{
				if(!isset($item->likes))
					$item->likes = array();
				if(!in_array($postdata['user'], $item->likes))
					$item->likes[] = $postdata['user'];
				echo "\nADDED heart ".$postdata['user']."!\n";
				echo "\nITEM:\n";
				print_r($item);
				$currentData[$founditemat-1]=json_encode($item);
			}
		}
	}
	else
	{
		//set id to millis and push it out
		$postdata['id'] = microtime_float();
		echo "POSTINGTHIS:";
		print_r($postdata);
		array_unshift($currentData,json_encode($postdata));
	}
	echo "PUSHING:";
	print_r($currentData);
	$encodedData = json_encode($currentData);
	echo "1md5 of:$encodedData\n";
	$newHash = md5($encodedData);
	$cache->set($today,$encodedData);
	$cache->set($today."_hash",$newHash);
	//echo "HASH:".$newHash;
	flush();

	if(strpos($postdata['text'],"http")===0)
	{
		file_put_contents("post.log","--------:".$_SERVER['REMOTE_ADDR']."\nCurling ".$postdata['text']."..\n", FILE_APPEND | LOCK_EX);

		$curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,urldecode($postdata['text']));
        curl_setopt($curl, CURLOPT_FILETIME, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $header = curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        file_put_contents("post.log","--------:".$_SERVER['REMOTE_ADDR']."\n".$postdata['text']."\n".$contentType."\n", FILE_APPEND | LOCK_EX);

       // echo "CONTENT-TYPE:$contentType";

        if(strpos($contentType,"image")===0)
        {
        	//update to be a preview/link to the image
        	//echo "UPDATING AS IMG SRC";
        	$founditemat = -1;
        	$currentData = json_decode($cache->get($today));

        	foreach($currentData as $item)
        	{
        		$founditemat++;
        		$item=json_decode($item);
        		//echo "\npost:".$postdata['id']." ITEM:".$item->id."\n";
        		//print_r($item);
        		if($item->id==$postdata['id'])
        		{
        			echo "\nFOUND ITEM:";
        			print_r($item);
        			break;
        			
        		}

        	}
        	if($founditemat!=-1)
        	{
        		///echo "EDITING:";
        		//print_r($currentData[$founditemat]);
        		$item=json_decode($currentData[$founditemat]);
        		$item->contenttype=urlencode($contentType);//"<a href='".$item->text."'><img src='".$item->text."' class='postedimages'></a>";
        		$currentData[$founditemat]=json_encode($item);
        		//echo "NOW:";
        		//print_r($currentData[$founditemat]);
        		$encodedData = json_encode($currentData);
				$newHash = md5($encodedData);
				$cache->set($today,$encodedData);
				$cache->set($today."_hash",$newHash);
				echo "NEWHASHer:".$newHash."\nFROM:".$encodedData."\n";


        	}
        	
        }

		
		
	}




	$db->put(array('day'=>$today,'json'=>$encodedData));

}
else
{
	//list all items
	$theirHash = $_GET['hash'];
	$currentHash  = $cache->get($today."_hash");

	$delayCount=0;

	file_put_contents("get.log","--------:".$_SERVER['REMOTE_ADDR']."\ntheirHash:$theirHash\ncurrentHash:$currentHash\n", FILE_APPEND | LOCK_EX);

	//echo "theirHash:$theirHash currentHash:$currentHash ";exit(0);
	if($theirHash)
	{
		while($delayCount<200&&$theirHash==$currentHash)
		{
			usleep(250000);//0.25 second wait (250ms)
			$delayCount++;
			$currentHash = $cache->get($today."_hash");
		}
	}

	if(@$_GET['clear'])
	{
		$cache->expire($today."_hash",0);
		$cache->expire($today,0);	
	}

	echo $cache->get($today);
}





if($doredirect)
{
	?>
		<script>
			window.location = "/";
		</script>
	<?php
}



/*
require_once("amazon.php");
$creds = json_decode(file_get_contents("../awscreds.txt"));
$db = new AwsDbClient($creds->key,$creds->secret,$creds->region,"chat");
$stuff = $db->get(array('day'=>2043));
echo $stuff['json'];*/


/*


$cache->set("somekey","hello cache");
$cache->expire("somekey",5);

$data = new stdClass();
$data->status = "hello world!";
echo json_encode($data);
*/

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return round(1000*(((float)$usec + (float)$sec)));
}
?>