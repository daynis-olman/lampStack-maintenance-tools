<?php
/**
* @file
* This script will download an archive / file from AWS S3 bucket
*/
error_reporting(0);
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
  'local-env' => 'stage2', //generated backup filename will this label    
  'backup-type' => 'db',
  'backup-file-ext' => 'sql'
);

$options = getopt("d:b:p:t:hf:");
if (array_key_exists('h', $options)) {
  print "Usage: php cjc-aws-s3-download.php -d <yyyy-mm-dd> -b <s3-bucket-name> -p <s3-bucket-folder> -f <s3-bucket-file>\n";
  exit;
}

$backup_file_date = ($options['d']) ? $options['d'] : date("Y-m-d", time()-86400);
$s3_bucket = ($options['b']) ? $options['b'] : $config['s3-bucket'];
$s3_bucket_folder = ($options['p']) ? $options['p'] : $config['s3-bucket-folder'];
$s3_bucket_file = ($options['f']) ? $options['f'] : false;
$backup_type = ($options['t']) ? $options['t'] : $config['backup-type'];

list($y, $m, $d) = explode('-', $backup_file_date);

if ( !checkdate($m, $d, $y) || strtotime($backup_file_date) > time() ) {
  exit("Invalid date : $backup_file_date\n");
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

$local_env = $config['local-env'];
#$backup_type = $config['backup-type'];
$backup_file_ext = ($options['t'] == 'fs')  ? 'tar.gz' : $config['backup-file-ext'];
$keyname = '';

if ($s3_bucket_file) {
  $keyname = $s3_bucket_file;
  $items = explode('/', $keyname);
  $save_file = end($items);
}
else {
  $keyname = $config['s3-bucket-folder'].'/'.$local_env.'-'.$backup_type.'-'.$backup_file_date.'.'.$backup_file_ext;
  $save_file = $local_env.'-'.$backup_type.'-restore-'.$backup_file_date.'.'.$backup_file_ext;
}

$save_file_path = $config['local-folder'].$save_file;


try {
  // Download data.
  $now = microtime(true);
  $result = $s3->getObject(array(
      'Bucket' => $s3_bucket,
      'Key'    => $keyname,
      'SaveAs' => $save_file_path
  ));

  $date_time = date("Y-m-d H:i:s");
  if ($result["ContentLength"]) {
    $diff = round ((microtime(true) - $now), 2);
    echo "- $date_time: ----   File ($save_file) successfully downloaded from S3 Bucket in $diff secs\n";
  }

} catch (S3Exception $e) {
    //echo $e->getMessage() . "\n";
    echo "Error in downloading file ($keyname) : File may not exist in S3 Bucket\n";
}
