<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	define_safe('IMAGE_UPLOAD_NAME', 'Image Upload');
	define_safe('IMAGE_UPLOAD_GROUP', 'image_upload');

	class extension_image_upload extends Extension
	{
		/*------------------------------------------------------------------------------------------------*/
		/*  Installation  */
		/*------------------------------------------------------------------------------------------------*/

		public function install()
		{
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
				 `resize` enum('yes','no') NOT NULL DEFAULT 'yes',
				  PRIMARY KEY (`id`),
				  KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

		public function update($previousVersion = false) {
			// everything is OK by default
			$ret = true;

			// Before 1.1
			if ($ret && version_compare($previousVersion, '1.1', '<')) {
				$query = "ALTER TABLE `tbl_fields_image_upload`
					ADD `max_width` int(11) unsigned,
					ADD `max_height` int(11) unsigned,
					DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

				try {
					Symphony::Database()->query($query);
				}
				catch (Exception $e) {
				}
			}

			// Before 1.3
			if ($ret && version_compare($previousVersion, '1.3', '<')) {
				$query = "ALTER TABLE `tbl_fields_image_upload`
							ADD COLUMN `resize` enum('yes','no') NOT NULL DEFAULT 'yes'";
				try {
					$ret = Symphony::Database()->query($query);
				}
				catch (Exception $e) {
					// ignore ?
				}
			}

			// Before 1.4
			if ($ret && version_compare($previousVersion, '1.4', '<')) {
				// Remove directory from the upload fields, #1719
				$upload_tables = Symphony::Database()->fetchCol("field_id", "SELECT `field_id` FROM `tbl_fields_image_upload`");

				if (is_array($upload_tables) && !empty($upload_tables)) {
					foreach($upload_tables as $field) {
						Symphony::Database()->query(sprintf(
							"UPDATE tbl_entries_data_%d SET file = substring_index(file, '/', -1)",
							$field
						));
					}
				}
			}

			return $ret;
		}

		public function uninstall() {
			return Symphony::Database()->query("DROP TABLE `tbl_fields_image_upload`");
		}

	}
