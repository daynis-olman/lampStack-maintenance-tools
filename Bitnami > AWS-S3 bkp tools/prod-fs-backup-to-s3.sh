#!/bin/bash
#
BAKDATE=$(date +"%Y-%m-%d")
ENV="prod"
PHPCMD="/opt/bitnami/php/bin/php"
BIN="/home/bitnami/apps/bin"
BACKUPDIR="/home/bitnami/apps/logs/backup"
LOCALEXPIRE="7"
FILE_ROOT="/home/bitnami/apps/wordpress/"
WEB_DIR="htdocs"
#
echo "====================================================================================================================="
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- Starting Drupal File System backup process ($ENV)"
echo "- $NOW: ---- 1.Creating local file backup ($BACKUPDIR/$ENV-fs-$BAKDATE.tar.gz)"
#
cd $FILE_ROOT
tar zcf $BACKUPDIR/$ENV-fs-$BAKDATE.tar.gz $WEB_DIR 2>/dev/null
cd $BIN
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- 2.Uploading to S3 ($ENV-fs-$BAKDATE.tar.gz) Bucket"
$PHPCMD $BIN/prod-aws-s3-upload.php $ENV-fs-$BAKDATE.tar.gz
#
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- 3.Deleting old local backups older than $LOCALEXPIRE days"
find $BACKUPDIR/* -mtime +$LOCALEXPIRE -exec rm {} \;
#
NOW=$(date +"%Y-%m-%d %H:%M:%S")
echo "- $NOW: ---- File system backup completed successfully"
echo "====================================================================================================================="
