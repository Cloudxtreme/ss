#!/bin/sh
./uwsgi -s 127.0.0.1:3031 -M -p 4 --pythonpath /opt/uwsgi --chdir /opt/uwsgi --wsgi-file main.py --daemonize /var/log/uwsgi.log 
