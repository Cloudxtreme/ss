#!/bin/sh

path=$1
file=$2
owner=$3
filelist=/tmp/$file.filelist
filelist_temp=/tmp/$file.filelist_temp

echo $path
echo $file

cd $path

/usr/bin/unzip -ou $file
/usr/bin/unzip -l $file > $filelist_temp

awk '{print $4}' $filelist_temp > $filelist

cat $filelist | while read filename
do
	echo $filename
	`/bin/chown $owner:$owner $path/$filename`
done

/usr/bin/php /opt/cdn_hot_file/auto_extract/extract_file_list.php $path $file $filelist

#rm -rf $filelist
#rm -rf $filelist_temp

