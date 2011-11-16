<?php

	class extension_image_upload extends Extension {

		public function about() {
			return array(
				'name'			=> 'Field: Image Upload',
				'version'		=> '1.0.1',
				'release-date'	=> '2011-11-15',
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
				'description'	=> 'Upload images. Optionally unique names and minimum width / height.'
			);
		}

		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_image_upload`");
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
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

	}