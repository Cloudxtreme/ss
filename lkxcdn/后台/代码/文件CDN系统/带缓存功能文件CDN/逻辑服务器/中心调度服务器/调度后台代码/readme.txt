# apache httpd 配置文件 /etc/httpd/conf/httpd.conf

    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^cdnhotfile\.efly\.cc$
    RewriteRule ^(.*)$ cdnhotfile/index.php

# 使得用户自定义域名转发到我们制定的入口php文件


# 调度代码要准备好 纯真IP库文件 qqwry.dat


