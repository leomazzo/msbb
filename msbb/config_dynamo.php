<?php
// If necessary, reference the sdk.class.php file.
// For example, the following line assumes the sdk.class.php file is
// in an sdk sub-directory relative to this file
require_once dirname(__FILE__) . '/vendor/amazonwebservices/aws-sdk-for-php/sdk.class.php';

// Instantiate the class

putenv("key=AKIAICDJSZAIWHXBWUOQ");
putenv("secret=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");
putenv("region=us-east-1");

$dynamodb = new AmazonDynamoDB(array(
        'key'    => getenv('key'),
        'secret' => getenv('secret')
                ));
$dynamodb->set_region('dynamodb.'.getenv('region').'.amazonaws.com');

####################################################################
# Setup some local variables for dates

$one_day_ago = date('Y-m-d H:i:s', strtotime("-1 days"));
$seven_days_ago = date('Y-m-d H:i:s', strtotime("-7 days"));
$fourteen_days_ago = date('Y-m-d H:i:s', strtotime("-14 days"));
$twenty_one_days_ago = date('Y-m-d H:i:s', strtotime("-21 days"));

####################################################################
?>
