#!/bin/bash
#
BACKUP_DATE=`date -d "yesterday" '+%Y-%m-%d'`
S3_BUCKET="cjcbackup"
S3_BUCKET_FOLDER="master"
S3_BUCKET_FILE=""

usage() { 
  echo "usage: $0"; 
  echo "-d <$BACKUP_DATE> S3 Backup Date (Optional, default to yesterday)"
  echo "-b <$S3_BUCKET> S3 Bucket Name (Optional, default to cjcbackup)"
  echo "-p <$S3_BUCKET_FOLDER> S3 Bucket Folder (Optional, default to master)"
  echo "-f <none> Full path to S3 file (Optional, default to empty)"
}

while getopts ":d:b:p:f:h" opt; do
  case "$opt" in
    d) BACKUP_DATE=$OPTARG ;;
    b) S3_BUCKET=$OPTARG ;;
    p) S3_BUCKET_FOLDER=$OPTARG ;;
    f) S3_BUCKET_FILE=$OPTARG ;;
    h) usage
  esac
done
shift $(( OPTIND - 1 ))

BAKDATE=$(date +"%Y-%m-%d")
ENV="stage1"
PHPCMD="/usr/local/bin/php"
MYSQLBIN="/usr/bin/mysql"
MYSQLUSER="ENTER-MYSQL-DB-USERNAME-HERE"
MYSQLPASS="ENTER-MYSQL-DB-PASSWORD-HERE"
MYSQLDB="aujennyc_com_au_stage"
BIN="/home/aujennycraig/bin"
DOWNLOADDIR="/home/aujennycraig/aws-s3-temp-local-backup"
RESTOREFILE="$DOWNLOADDIR/$ENV-db-restore-$BACKUP_DATE.sql"
if [ -n "$S3_BUCKET_FILE" ]; then
    RESTOREFILE=$DOWNLOADDIR/"${S3_BUCKET_FILE##*\/}"
fi

#
echo "========================================================================================================================="
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- Starting database restore process ($ENV)"
echo "- $NOW: ---- 1.Downloading database backup ($BACKUP_DATE) from S3 Bucket"
$PHPCMD $BIN/cjc-aws-s3-download.php -d $BACKUP_DATE -b $S3_BUCKET -p $S3_BUCKET_FOLDER -f $S3_BUCKET_FILE
NOW=$(date +"%Y-%m-%d %H:%M:%S")
#
echo "- $NOW: ---- Restoring database from ($RESTOREFILE)"
$MYSQLBIN -u$MYSQLUSER -p$MYSQLPASS -D $MYSQLDB < $RESTOREFILE 2>/dev/null 
#
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- DB Restore completed successfully"
echo "========================================================================================================================="
