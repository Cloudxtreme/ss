#!/bin/sh

clean=`date +%Y-%m-* -d "-1 month"`
cd /opt/cdnfilelog/logs
rm -rf $clean
