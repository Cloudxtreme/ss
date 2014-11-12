<?php
class host{
		const             HOSTNAME = 'ibss.efly.cc';
		const     CDNINFO_HOSTNAME = 'cdninfo.efly.cc';
		const   FILESTATS_HOSTNAME = 'filestats.cdn.efly.cc';
		const    WEBSTATS_HOSTNAME = 'webstats.cdn.efly.cc';
		const      NEWCDN_HOSTNAME = '183.61.80.177';
		const NEWCDN_FILE_HOSTNAME = '183.61.80.176';
		const    SQUIDDNS_HOSTNAME = 'squiddns.data.efly.cc';
		const      CDNMGR_HOSTNAME = 'cdnmgr.efly.cc';
		const             USERNAME = 'root';
		const    SQUIDDNS_USERNAME = 'dnsadmin';
		const             PASSWORD = 'rjkj@2009#8';
    const         CDN_PASSWORD = 'rjkj@rjkj';
    const    SQUIDDNS_PASSWORD = 'dnsadmin';
		
		public function get_hostname(){
				return host::HOSTNAME;
		}
		
		public function get_cdninfo_hostname(){
				return host::CDNINFO_HOSTNAME;
		}
		
		public function get_filestats_hostname(){
				return host::FILESTATS_HOSTNAME;
		}
		
		public function get_webstats_hostname(){
				return host::WEBSTATS_HOSTNAME;
		}
		
		public function get_newcdn_hostname(){
				return host::NEWCDN_HOSTNAME;
		}
		
		public function get_newcdn_file_hostname(){
				return host::NEWCDN_FILE_HOSTNAME;
		}
		
		public function get_squiddns_hostname(){
				return host::SQUIDDNS_HOSTNAME;
		}
		
		public function get_cdnmgr_hostname(){
				return host::CDNMGR_HOSTNAME;
		}
		
		public function get_username(){
				return host::USERNAME;
		}
		
		public function get_squiddns_username(){
				return host::SQUIDDNS_USERNAME;
		}
		
		public function get_password(){
				return host::PASSWORD;
		}
		
		public function get_cdn_password(){
				return host::CDN_PASSWORD;
		}
		
		public function get_squiddns_password(){
				return host::SQUIDDNS_PASSWORD;
		}
}
?>