# apache httpd �����ļ� /etc/httpd/conf/httpd.conf

    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^cdnhotfile\.efly\.cc$
    RewriteRule ^(.*)$ cdnhotfile/index.php

# ʹ���û��Զ�������ת���������ƶ������php�ļ�


# ���ȴ���Ҫ׼���� ����IP���ļ� qqwry.dat


