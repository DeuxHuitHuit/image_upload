<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	define_safe(IMAGE_UPLOAD_NAME, 'Field: Image Upload');
	define_safe(IMAGE_UPLOAD_GROUP, 'image_upload');
	
	
	
	class extension_image_upload extends Extension {

		public function about() {
			return array(
				'name' => IMAGE_UPLOAD_NAME,
				'version' => '1.1.2',
				'release-date' => '2011-11-17',
				'author' => array(
					array(
						'name' => 'Xander Group',
						'email' => 'symphonycms@xandergroup.ro',
						'website' => 'www.xandergroup.ro'
					),
					array(
						'name' => 'Vlad Ghita',
						'email' => 'vlad.ghita@xandergroup.ro',
					),
				),
				'description'	=> 'Upload images. Optional unique names, set minimum width / height and maximum width / height.'
			);
		}

		
		
		public function install() {
			return Symphony::Database()->query(
				"CREATE TABLE `tbl_fields_image_upload` (
				 `id` int(11) unsigned NOT NULL auto_increment,
				 `field_id` int(11) unsigned NOT NULL,
				 `destination` varchar(255) NOT NULL,
				 `validator` varchar(50),
				 `unique`  varchar(50),
				 `min_width` int(11) unsigned,
				 `min_height` int(11) unsigned,
				 `max_width` int(11) unsigned,
				 `max_height` int(11) unsigned,
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"
			);
		}
		
		public function update($previous_version){
			if( version_compare($previous_version, '1.1', '<') ){
				$query = "ALTER TABLE `tbl_fields_image_upload` 
					ADD `max_width` int(11) unsigned,
					ADD `max_height` int(11) unsigned,
					DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
				
				return (boolean) Symphony::Database()->query($query);
			}
			
			return true;
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_image_upload`");
		}
		
	}