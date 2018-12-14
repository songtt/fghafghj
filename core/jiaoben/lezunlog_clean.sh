#!/usr/bin/env bash
#------------------------
# clean yesterday's log
#------------------------
basedir=/home/www/lezun/public/test/lezunlog/
yesday=$(date -d '-7 day' +%F | sed 's/-//g')

find $basedir -type d -name "$yesday" | while read folder;do 
     echo 'Clean the folder: '$folder
     rm -rf $folder
done
echo 'Clean successfull' "($yesday)"
