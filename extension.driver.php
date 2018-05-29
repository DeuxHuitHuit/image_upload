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
			return Symphony::Database()
				->create('tbl_fields_image_upload')
				->ifNotExists()
				->charset('utf8')
				->collate('utf8_unicode_ci')
				->fields([
					'id' => [
						'type' => 'int(11)',
						'auto' => true,
					],
					'field_id' => 'int(11)',
					'destination' => 'varchar(255)',
					'validator' => 'varchar(50)',
					'unique' => 'varchar(50)',
					'min_width' => 'int(11)',
					'min_height' => 'int(11)',
					'max_width' => 'int(11)',
					'max_height' => 'int(11)',
					'resize' => [
						'type' => 'enum',
						'values' => ['yes','no'],
						'default' => 'yes'
					],
				])
				->keys([
					'id' => 'primary',
					'field_id' => 'key',
				])
				->execute()
				->success();
		}

		public function update($previousVersion = false) {
			// everything is OK by default
			$ret = true;

			// Before 1.1
			if ($ret && version_compare($previousVersion, '1.1', '<')) {
				Symphony::Database()
					->alter('tbl_fields_image_upload')
					->add([
						'max_width' => 'int(11)',
						'max_height' => 'int(11)',
					])
					->execute()
					->success();
			}

			// Before 1.3
			if ($ret && version_compare($previousVersion, '1.3', '<')) {
				Symphony::Database()
					->alter('tbl_fields_image_upload')
					->add([
						'resize' => [
							'type' => 'enum',
							'values' => ['yes','no'],
							'default' => 'yes',
						],
					])
					->execute()
					->success();
			}

			// Before 1.4
			if ($ret && version_compare($previousVersion, '1.4', '<')) {
				// Remove directory from the upload fields, #1719
				$upload_tables = Symphony::Database()
					->select('field_id')
					->from('tbl_fields_image_upload')
					->execute()
					->column('field_id');

				if (is_array($upload_tables) && !empty($upload_tables)) {
					foreach($upload_tables as $field) {
						Symphony::Database()
							->update("tbl_entries_data_$field")
							->set([
								'file' => "substring_index(file, '/', -1)",
							])
							->execute()
							->success();
					}
				}
			}

			return $ret;
		}

		public function uninstall() {
			return Symphony::Database()
				->drop('tbl_fields_image_upload')
				->ifExists()
				->execute()
				->success();
		}
	}
