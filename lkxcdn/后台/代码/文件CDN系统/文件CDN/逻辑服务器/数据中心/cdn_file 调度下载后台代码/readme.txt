# apache httpd �����ļ� /etc/httpd/conf/httpd.conf

    RewriteEngine On
    RewriteCond %{HTTP_HOST} !^filecdn\.efly\.cc$
    RewriteRule ^(.*)$ cdnwebmgr/cdn_file/index.php

# ʹ���û��Զ�������ת���������ƶ������php�ļ�


# ���ȴ���Ҫ׼���� ����IP���ļ� qqwry.dat


# index.php �ļ�������֧�� �����湦�ܵ�CDN�ļ����ؾ���ο������

if( is_hotfile_hostname($hostname) ) {
	header("Location: http://filecdn.efly.cc/cdnhotfile/index.php?host=$hostname&url=$filename");
	exit;
}

��Ѹ����������¶������Ŀ¼�ļ�

# index.php �Ǽ��þ����غʹ��������ص�
# index.cdnhotfile.php �Ǵ����������ص�
# �����Ҫ���֣��ر����ڲ����ʱ��
# �ǵ����ô������ļ���������Ϊ�ļ��Ƚϴ����Բ��ύ��SVN������
