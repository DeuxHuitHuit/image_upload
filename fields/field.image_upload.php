<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/fields/field.upload.php');
	
	
	
	final class fieldImage_upload extends fieldUpload {
		
		public function __construct(&$parent){
			parent::__construct($parent);
			
			$this->_name = __('Image Upload');
		}
		
		
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/	
		
		public function findDefaults(&$settings){
			if( !isset($settings['unique']) ){
				$settings['unique'] = 'yes';
			}
			
			if( !isset($settings['mid_width']) ){
				$settings['min_width'] = 800;
			}
			
			if( !isset($settings['min_height']) ){
				$settings['min_height'] = 600;
			}
		}
		
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			
			// set validator default expression
			foreach( $wrapper->getChildrenByName('label') as $label ){
				if( $label->getValue() == __('Validation Rule') ){
					
					foreach( $label->getChildrenByName('input') as $input ){
						$input->setAttribute('value', '/\.(?:bmp|gif|jpe?g|png)$/i');
						break;
					}
				}
			}
			
			// remove `document` from validators
			foreach( $wrapper->getChildrenByName('ul') as $ul ){
				if( in_array('tags', explode(' ', $ul->getAttribute('class'))) ){
					
					foreach( $ul->getChildren() as $index => $li ){
						
						if( ($li->getName() == 'li') && ($li->getValue() == 'document') ){
							$ul->removeChildAt($index);
							break;
						}
					}
				}
			}
			
			// append MinWidth, MinHeight and Unique
			foreach( $wrapper->getChildrenByName('div') as $div ){
				
				if( $div->getAttribute('class') == 'compact' ){
					$this->_appendMinWidthInput($div);
					$this->_appendMinHeightInput($div);
					$this->_appendUniqueCheckbox($div);
					break;
				}
			}
		}
		
		private function _appendMinWidthInput(XMLElement &$wrapper) {
			$order = $this->get('sortorder');

			$label = Widget::Label(
				__('Mimimum width (px)'),
				Widget::Input("fields[{$order}][min_width]", $this->get('min_width'))
			);
			
			$label->appendChild(
				new XMLElement(
					'p', 
					__('If empty or 0, no limit will be set.'),
					array('class' => 'help', 'style' => 'margin: 5px 0 0 0;')
				)
			);

			$wrapper->appendChild($label);
		}
		
		private function _appendMinHeightInput(XMLElement &$wrapper) {
			$order = $this->get('sortorder');

			$label = Widget::Label(
				__('Mimimum height (px)'),
				Widget::Input("fields[{$order}][min_height]", $this->get('min_height'))
			);
			
			$label->appendChild(
				new XMLElement(
					'p', 
					__('If empty or 0, no limit will be set.'),
					array('class' => 'help', 'style' => 'margin: 5px 0 0 0;')
				)
			);

			$wrapper->appendChild($label);
		}
		
		private function _appendUniqueCheckbox(XMLElement &$wrapper) {
			$order = $this->get('sortorder');

			$label = Widget::Label();
			$input = Widget::Input("fields[{$order}][unique]", 'yes', 'checkbox');

			if ($this->get('unique') == 'yes') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Create unique filenames', array($input->generate())));

			$wrapper->appendChild($label);
		}
		
		public function commit(){
			if(!Field::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$settings = array();

			$settings['field_id'] = $id;
			$settings['destination'] = $this->get('destination');
			$settings['validator'] = ($settings['validator'] == 'custom' ? NULL : $this->get('validator'));
			$settings['unique'] = $this->get('unique');
			$settings['min_width'] = $this->get('min_width');
			$settings['min_height'] = $this->get('min_height');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return Symphony::Database()->insert($settings, 'tbl_fields_' . $this->handle());
		}
		
		
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		
		public function checkPostFieldData($data, &$message, $entry_id = NULL) {
			if( is_array($data) && isset($data['name']) ){
				$data['name'] = $this->_getUniqueFilename($data['name']);
			}
			
			$error = parent::checkPostFieldData($data, $message, $entry_id);
			
			if( $error == self::__OK__ ){
				$meta = self::getMetaInfo($data['tmp_name'], $data['type']);
				
				$min_width = $this->get('min_width');
				$min_height = $this->get('min_height');
				
				if( isset($meta['width']) && isset($meta['height']) ){
					if( !empty($min_width) && ($min_width != 0) && ($meta['width'] < $min_width) ){
						$message .= __('Image must have a minimum width of %1$spx.', array($min_width) ).'<br />';
						$error = self::__ERROR_CUSTOM__;
					}
					
					if( !empty($min_height) && ($min_height != 0) && $meta['height'] < $min_height ){
						$message .= __('Image must have a minimum height of %1$spx.', array($min_height) );
						$error = self::__ERROR_CUSTOM__;
					}
				}
				elseif( !empty($data['tmp_name']) ){
					$message .= __('Uploaded file is not an image.');
					$error = self::__ERROR_CUSTOM__;
				}
			}
			
			return $error;
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = NULL) {
			if( isset($data['name']) ){
				$data['name'] = $this->_getUniqueFilename($data['name']);
			}
			
			return parent::processRawFieldData($data, $status, $simulate, $entry_id);
		}
		
		
		
	/*-------------------------------------------------------------------------
		In-house utilities:
	-------------------------------------------------------------------------*/
		
		private function _getUniqueFilename($filename) {
			// since unix timestamp is 10 digits, the unique filename will be limited to ($crop+1+10) characters;
			$crop  = '150';
			return preg_replace("/(.*)(\.[^\.]+)/e", "substr('$1', 0, $crop).'-'.time().'$2'", $filename);
		}
		
	}
	