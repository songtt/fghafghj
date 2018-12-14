#!/usr/bin/env bash
#------------------------
# clean yesterday's log
#------------------------
# get base dir
set -x
tmp=$(dirname $0)
cd $tmp
cd /home/www/lezun/public/test/lezunlog/
basedir=$(pwd)

function clean_cache() {
    local basedir=$1
    yesday=$(date -d '-1 day' +%F | sed 's/-//g')

    find $basedir -type d -name "$yesday" | while read folder;do 
        echo 'Clean the folder: '$folder
        /usr/bin/vmtouch -e $folder
    done
    echo 'Clean successfull' "($yesday)"
}

clean_cache $basedir
# clean_cache /home/lezunlog/