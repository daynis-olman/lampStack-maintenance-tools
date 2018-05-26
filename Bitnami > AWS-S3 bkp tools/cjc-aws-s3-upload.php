<?php
/**
* @file
* Upload a backup archive / file to AWS S3 bucket
*/
require dirname(__FILE__).'/aws-autoloader.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

$config = array (
  's3-bucket' => 'ENTER-AWS-S3-BUCKET-NAME-HERE',
  's3-bucket-folder' => 'master',
  'key'    => 'ENTER-AWS-KEY-HERE',
  'secret' => 'ENTER-AWS-SECRET-HERE', 
  'region' => "ap-southeast-2", 
  'local-folder' => '/home/bitnami/apps/logs/backup/',
  'backup-age' => '7 days' //defining generated file purge timeframe (delete in local env. only, not S3)
);



if ($argc < 2 )
{
    exit( "Usage: cjc_aws_s3 <filename>\n" );
}

$file = $argv[1];
$file_name = $config['local-folder'].$file;

if (!is_file($file_name)) {
  exit("Invalid file : $file_name\n");
}
						

$s3 = S3Client::factory(
  array(
    'credentials' => [
        'key'    => $config['key'],
        'secret' => $config['secret'],
    ],
    'region' => $config['region'],
    'http' => [ 'verify' => false ],
    'version' => '2006-03-01',
  )
);

$keyname = $config['s3-bucket-folder'].'/'.$file;

try {
  // Upload data.
  $now = microtime(true);
  $result = $s3->putObject(
    array(
      'Bucket' => $config['s3-bucket'],
      'Key'    => $keyname,
      'SourceFile'    => $file_name,
      //'ContentType' => 'application/zip',
      'CacheControl'  => 'max-age=86400',
      "Expires"       => gmdate("D, d M Y H:i:s T", strtotime("+5 years")),
      'MetadataDirective' => 'REPLACE'
    )
  );

  $date_time = date("Y-m-d H:i:s");
  if ($result['ObjectURL']) {
    $diff = round ((microtime(true) - $now), 2);
    echo "- $date_time: ----   File ($file) successfully uploaded to S3 Bucket in $diff secs\n";
  }

} catch (S3Exception $e) {
    echo "Error in uploading the file: \n";
    echo $e->getMessage() . "\n";
}
