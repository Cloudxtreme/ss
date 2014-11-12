# apache httpd 配置文件 /etc/httpd/conf/httpd.conf

    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^filecdn\.efly\.cc$
    RewriteRule ^(.*)$ cdnwebmgr/cdn_file/index.php

# 使得用户自定义域名转发到我们制定的入口php文件


# 调度代码要准备好 纯真IP库文件 qqwry.dat


# index.php 文件包含了支持 带缓存功能的CDN文件下载具体参考里面的

if( is_hotfile_hostname($hostname) ) {
	header("Location: http://filecdn.efly.cc/cdnhotfile/index.php?host=$hostname&url=$filename");
	exit;
}

会把该请求再重新定向到相关目录文件

# index.php 是兼用旧下载和带缓存下载的
# index.cdnhotfile.php 是纯带缓存下载的
# 这个需要区分，特别是在部署的时候
# 记得配置纯真库的文件，这里因为文件比较大所以不提交到SVN上面了
