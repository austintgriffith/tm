<?php
//AMAZON
require_once("../aws/aws-autoloader.php");
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Enum\AttributeAction;
use Aws\S3\S3Client;
use Aws\DynamoDb\Iterator\ItemIterator;

class AwsDbClient
{
	public $dbclient;
	public $tableName;

	function __construct($key,$secret,$region,$tableName)
	{
		$this->dbclient = DynamoDbClient::factory(array(
	    	'key'    => $key,
	    	'secret' => $secret,
	    	'region' => $region
		));
		$this->tableName = $tableName;
	}

	function put($values)
	{
		$result = $this->dbclient->putItem(array(
            // TableName is required
            'TableName' => $this->tableName,
            // Item is required
            'Item' => $this->prepareKeys($values)
        ));
        return $result['Attributes'];
	}

	function get($keys)
	{
		$result = $this->dbclient->getItem(array(
            'ConsistentRead' => true,
            'TableName' => $this->tableName,
            'Key'       => $this->prepareKeys($keys)
        ));
        return $this->cleanResult($result['Item']);
	}

    function delete($keys){
		$this->dbclient->deleteItem(array(
	        'TableName' => $this->tableName,
	        'Key' =>  $this->prepareKeys($keys)
	    ));
    }

	function update($keys,$values){
		$result = $this->dbclient->updateItem(array(
		    "TableName" => $this->tableName, 
		        "Key" => $this->prepareKeys($keys),
		        "AttributeUpdates" => $this->prepareUpdateValues($values)
		    )
		);            
	}

	function prepareKeys($keys){
		$keyarray = array();
		foreach($keys as $key => $value)
		{
			$foundinkeys = false;
			foreach($this->keystuct as $akeyhash => $akeytype)
			{
				if($akeyhash==$key)
				{
					$newarray = array($akeytype=>$value);
					$keyarray[$akeyhash] = $newarray;
					$foundinkeys=true;
					break;
				}
			}
			if(!$foundinkeys)
			{
				if(is_numeric($value))
					$newarray = array('N'=>"".$value);	
				else
					$newarray = array('S'=>"".$value);
				$keyarray[$key] = $newarray;
			}
		}
		return $keyarray;
	}

	function cleanResult($result){
		$finalresult = array();
		if(!$result) return 0;
		foreach($result as $key => $value)
		{
			//echo "key:$key value:$value\n";
			if(@$value['S'])
				$finalresult[$key] = @$value['S'];
			else
				$finalresult[$key] = $value['N'];
		}

		return $finalresult;
	}
}

/////////////////////////////////////////////////////////////////////////////////////////////////////
//
//		S3 
//
//
/////////////////////////////////////////////////////////////////////////////////////////////////////
class AwsS3Client
{
	public $s3client;
	function __construct($key,$secret,$region)
	{
		$this->s3client = S3Client::factory(array(
	    	'key'    => $key,
	    	'secret' => $secret,
	    	'region' => $region
		));
	}

	function putPublicObject($bucket,$key,$pathToFile)
	{
		$result = $this->s3client->putObject(array(
		    'ACL' => 'public-read',
		    'Bucket' => $bucket,
		    'Key' => $key,
		    'SourceFile' => $pathToFile,
		));

		return $result['ObjectURL'];
	}
}

?>
