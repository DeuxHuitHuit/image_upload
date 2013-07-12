<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT.'/fields/field.upload.php');



	class fieldImage_upload extends fieldUpload
	{

		/*------------------------------------------------------------------------------------------------*/
		/*  Definition  */
		/*------------------------------------------------------------------------------------------------*/

		public function __construct(){
			parent::__construct();

			$this->_name = __('Image Upload');
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Goodies  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Resizes an Image to a given maximum width and height.
		 *
		 * @param string  $file     - absolute image path
		 * @param integer $width    - desired width of the image
		 * @param integer $height   - desired height of the image
		 * @param string  $mimetype - image type
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public static function resize($file, $width, $height, $mimetype){
			$jit_status = ExtensionManager::fetchStatus(array('handle' => 'jit_image_manipulation'));

			// process image using JIT mode 1
			if( $jit_status[0] === EXTENSION_ENABLED ){
				require_once(EXTENSIONS.'/jit_image_manipulation/lib/class.image.php');

				/*@var $image Image */

				try{
					$image = Image::load($file);

					// if not and Image, stick with original version
					if( !$image instanceof Image ){
						return false;
					}
				}
					// if problems appear, stick with original version
				catch( Exception $e ){
					return false;
				}

				$image->applyFilter('resize', array($width, $height));
				$image->save($file, 85, null, $mimetype);
			}

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Settings  */
		/*------------------------------------------------------------------------------------------------*/

		public function findDefaults(&$settings){
			if( !isset($settings['unique']) ){
				$settings['unique'] = 'yes';
			}

			if( !isset($settings['min_width']) ){
				$settings['min_width'] = 800;
			}

			if( !isset($settings['min_height']) ){
				$settings['min_height'] = 600;
			}

			if( !isset($settings['max_width']) ){
				$settings['max_width'] = 1600;
			}

			if( !isset($settings['max_height']) ){
				$settings['max_height'] = 1200;
			}
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null){
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', null, array('class' => 'two columns'));

			$this->_addDimensionInput(
				$div,
				__('Minimum width (px)'),
				'min_width',
				__('If empty or 0, no minimum limit will be set.')
			);

			$this->_addDimensionInput(
				$div,
				__('Minimum height (px)'),
				'min_height',
				__('If empty or 0, no minimum limit will be set.')
			);

			$this->_addDimensionInput(
				$div,
				__('Maximum width (px)'),
				'max_width',
				__('If empty or 0, no maximum resize limit will be set.')
			);

			$this->_addDimensionInput(
				$div,
				__('Maximum height (px)'),
				'max_height',
				__('If empty or 0, no maximum resize limit will be set.')
			);

			$wrapper->appendChild($div);

			$this->_addUniqueCheckbox($wrapper);
		}

		public function buildValidationSelect(XMLElement &$wrapper, $selected = null, $name = 'fields[validator]', $type = 'input'){

			include(TOOLKIT.'/util.validators.php');

			$label = Widget::Label(__('Validation Rule'), new XMLElement('i', __('Optional')));
			$label->appendChild(
				Widget::Input($name, $selected != null ? $selected : $upload['image'])
			);
			$wrapper->appendChild($label);

			$ul = new XMLElement('ul', NULL, array('class' => 'tags singular'));
			$ul->appendChild(
				new XMLElement('li', 'image', array('class' => $upload['image']))
			);

			$wrapper->appendChild($ul);

		}

		/**
		 * Append a dimension's Input HTML element.
		 *
		 * @param XMLElement $wrapper      - the wrapper
		 * @param string     $label_value  - value of the label
		 * @param string     $setting      - name of the setting
		 * @param string     $help_message - help message
		 *
		 * @return XMLElement - dimension element
		 */
		private function _addDimensionInput(XMLElement &$wrapper, $label_value, $setting, $help_message){
			$label = Widget::Label(
				$label_value,
				Widget::Input("fields[{$this->get('sortorder')}][{$setting}]", (string) $this->get($setting)),
				'column'
			);

			$label->appendChild(
				new XMLElement(
					'p',
					$help_message,
					array('class' => 'help', 'style' => 'margin: 0;')
				)
			);

			$wrapper->appendChild($label);
		}

		private function _addUniqueCheckbox(XMLElement &$wrapper){
			$label = Widget::Label();
			$input = Widget::Input("fields[{$this->get('sortorder')}][unique]", 'yes', 'checkbox');
			if( $this->get('unique') == 'yes' ) $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Create unique filenames', array($input->generate())));

			$wrapper->appendChild($label);
		}

		public function commit(){
			if( !Field::commit() ) return false;

			$id = $this->get('id');

			if( $id === false ) return false;

			$settings = array();

			$settings['field_id'] = $id;
			$settings['destination'] = $this->get('destination');
			$settings['validator'] = ($settings['validator'] == 'custom' ? NULL : $this->get('validator'));
			$settings['unique'] = $this->get('unique');
			$settings['min_width'] = $this->get('min_width');
			$settings['min_height'] = $this->get('min_height');
			$settings['max_width'] = $this->get('max_width');
			$settings['max_height'] = $this->get('max_height');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($settings, 'tbl_fields_'.$this->handle());
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Input  */
		/*------------------------------------------------------------------------------------------------*/

		public function checkPostFieldData($data, &$message, $entry_id = NULL){
			if( is_array($data) && isset($data['name']) && ($this->get('unique') == 'yes') ){
				$data['name'] = $this->getUniqueFilename($data['name']);
			}

			// run basic upload check
			$error = parent::checkPostFieldData($data, $message, $entry_id);

			// test for minimum dimensions
			if( $error == self::__OK__ ){

				// new file
				if( is_array($data) ){
					$tmp_name = $data['tmp_name'];
					$type = $data['type'];
				}
				// updated file
				else if( is_string($data) ){
					$tmp_name = WORKSPACE.$data;
					$type = 'image/jpg'; // send some dummy data
				}

				$meta = self::getMetaInfo($tmp_name, $type);

				if( isset($meta['width']) && isset($meta['height']) ){

					$min_width = $this->get('min_width');
					$min_height = $this->get('min_height');

					if( !empty($min_width) && ($min_width != 0) && ($meta['width'] < $min_width) ){
						$message .= __('Image must have a minimum width of %1$spx.', array($min_width)).'<br />';
						$error = self::__ERROR_CUSTOM__;
					}

					if( !empty($min_height) && ($min_height != 0) && $meta['height'] < $min_height ){
						$message .= __('Image must have a minimum height of %1$spx.', array($min_height));
						$error = self::__ERROR_CUSTOM__;
					}
				}
				elseif( is_array($data) && !empty($data['tmp_name']) ){
					$message .= __('Uploaded file is not an image.');
					$error = self::__ERROR_CUSTOM__;
				}
			}

			return $error;
		}

		public function processRawFieldData($data, &$status, &$message, $simulate = false, $entry_id = NULL){
			if( !is_array($data) || is_null($data) ) return parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

			if( is_array($data) && isset($data['name']) && ($this->get('unique') == 'yes') ){
				$data['name'] = $this->getUniqueFilename($data['name']);
			}

			// file already exists in Symphony
			if( is_string($data) ){

				// 1. process Upload
				$result = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

				if( $result['mimetype'] === 'application/octet-stream' ){
					if( function_exists('finfo_file') ){
						$result['mimetype'] = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $data['name']);
					}
				}

				// 2. resize
				if( $result['mimetype'] !== 'application/octet-stream' ){
					$max_width = $this->get('max_width');
					$max_height = $this->get('max_height');

					if( (!empty($max_width) && ($max_width > 0)) || (!empty($max_height) && ($max_height > 0)) ){

						if( is_file($file = WORKSPACE.$result['file']) ){

							$dimensions = $this->figureDimensions(self::getMetaInfo($file, $result['mimetype']));

							if( $dimensions['proceed'] ){
								if( self::resize($file, $dimensions['width'], $dimensions['height'], $result['mimetype']) ){
									$result['size'] = filesize($file);
									$result['meta'] = serialize(self::getMetaInfo($file, $result['mimetype']));
								}
							}
						}
					}
				}
			}

			// new file in Symphony
			elseif( is_array($data) ){

				// 1. resize
				$max_width = $this->get('max_width');
				$max_height = $this->get('max_height');

				if( (!empty($max_width) && ($max_width > 0)) || (!empty($max_height) && ($max_height > 0)) ){

					if( is_file($file = $data['tmp_name']) ){

						$dimensions = $this->figureDimensions(self::getMetaInfo($file, $data['type']));

						if( $dimensions['proceed'] ){
							if( self::resize($file, $dimensions['width'], $dimensions['height'], $data['type']) ){
								$data['size'] = filesize($file);
							}
						}
					}
				}

				// 2. process Upload
				$result = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			}

			return $result;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Output  */
		/*------------------------------------------------------------------------------------------------*/

		public function prepareTableValue($data, XMLElement $link = NULL, $entry_id = null){
			if( !$file = $data['file'] ){
				if( $link ) return parent::prepareTableValue(null, $link);
				else return parent::prepareTableValue(null);
			}

			if( $data['width'] > $data['height'] ){
				$width = 40;
				$height = 0;
			}
			else{
				$width = 0;
				$height = 40;
			}
			
			$destination = str_replace('/workspace', '', $this->get('destination')) . '/';
			
			$image = '<img style="vertical-align: middle;" src="'.URL.'/image/1/'.$width.'/'.$height.$destination.$file.'" alt="'.$this->get('label').' of Entry '.$entry_id.'"/>';

			if( $link ){
				$link->setValue($image);
				return $link->generate();
			}

			else{
				$link = Widget::Anchor($image, URL.$this->get('destination').'/'.$file);
				return $link->generate();
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  In-house  */
		/*------------------------------------------------------------------------------------------------*/

		protected function figureDimensions($meta){
			$width = 0;
			$height = 0;

			$max_width = $this->get('max_width');
			$max_height = $this->get('max_height');

			$img_width = $meta['width'];
			$img_height = $meta['height'];

			$ratio = $img_width / $img_height;

			// if width exceeds
			if( ($img_width > $max_width) && ($max_width > 0) ){
				$width = $max_width;
				$height = 0;

				if( $max_height > 0 ){
					// if resulting height doesn't fit, resize from height
					if( $width / $ratio > $max_height ){
						$width = 0;
						$height = $max_height;
					}
				}
			}

			// if height exceeds
			elseif( ($img_height > $max_height) && ($max_height > 0) ){
				$width = 0;
				$height = $max_height;

				if( $max_width > 0 ){
					// if resulting width doesn't fit, resize from width
					if( $height / $ratio > $max_width ){
						$width = $max_width;
						$height = 0;
					}
				}
			}

			return array(
				'proceed' => ($width != 0 || $height != 0),
				'width' => $width,
				'height' => $height
			);
		}

		protected function getUniqueFilename($filename){
			// since unix timestamp is 10 digits, the unique filename will be limited to ($crop+1+10) characters;
			$crop = '150';
			return preg_replace("/(.*)(\.[^\.]+)/e", "substr('$1', 0, $crop).'-'.time().'$2'", $filename);
		}

	}
