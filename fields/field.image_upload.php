<?php

	if( !defined( '__IN_SYMPHONY__' ) ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT.'/fields/field.upload.php');

	class fieldImage_upload extends fieldUpload
	{
		protected static $svgMimeTypes = array(
			'image/svg+xml',
			'image/svg',
		);

		/*------------------------------------------------------------------------------------------------*/
		/*  Definition  */
		/*------------------------------------------------------------------------------------------------*/

		public function __construct(){
			parent::__construct();
			$this->_name = __( 'Image Upload' );
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
			$jit_status = ExtensionManager::fetchStatus( array('handle' => 'jit_image_manipulation') );

			// process image using JIT mode 1
			if( $jit_status[0] === EXTENSION_ENABLED ){
				require_once(EXTENSIONS.'/jit_image_manipulation/lib/class.image.php');

				/*@var $image Image */

				try{
					$image = Image::load( $file );

					// if not and Image, stick with original version
					if( !$image instanceof Image ){
						return false;
					}
				} // if problems appear, stick with original version
				catch( Exception $e ){
					return false;
				}

				$image->applyFilter( 'resize', array($width, $height) );
				$image->save( $file, 85, null, $mimetype );
			}

			return true;
		}
		
		protected static function removePx($value) {
			return str_replace('px', '', $value);
		}
		
		protected static function isSvg($type) {
			return General::in_iarray($type, self::$svgMimeTypes);
		}
		
		/**
		 * Adds support for svg
		 */
		public static function getMetaInfo($file, $type) {
			$metas = parent::getMetaInfo($file, $type);
			if (self::isSvg($type)) {
				$svg = @simplexml_load_file($file);
				if (is_object($svg)) {
					$svg->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');

					$svgAttr = $svg->xpath('@width');
					if (is_object($svgAttr)) {
						$metas['width'] = floatval(self::removePx($svgAttr[0]->__toString()));
					}

					$svgAttr = $svg->xpath('@height');
					if (is_object($svgAttr)) {
						$metas['height'] = floatval(self::removePx($svgAttr[0]->__toString()));
					}

					if (!isset($metas['width']) || !isset($metas['height'])) {
						$viewBoxes = array('@viewBox', '@viewbox');
						foreach ($viewBoxes as $vb) {
							$svgAttr = $svg->xpath($vb);
							if (is_array($svgAttr) && !empty($svgAttr)) {
								$matches = array();
								$matches_count = preg_match('/^([-]?[\d\.]+)[\s]+([-]?[\d\.]+)[\s]+([\d\.]+)[\s]+([\d\.]+)[\s]?$/i', $svgAttr[0]->__toString(), $matches);
								if ($matches_count == 1 && count($matches) == 5) {
									$metas['width'] = floatval($matches[3]) - floatval($matches[1]);
									$metas['height'] = floatval($matches[4]) - floatval($matches[2]);
									break;
								}
							}
						}
					}
				}
			}
			return $metas;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Settings  */
		/*------------------------------------------------------------------------------------------------*/

		public function findDefaults(array &$settings)
		{
			if (!isset($settings['unique'])) {
				$settings['unique'] = 'yes';
			}

			if (!isset($settings['min_width'])) {
				$settings['min_width'] = 0;
			}

			if (!isset($settings['min_height'])) {
				$settings['min_height'] = 0;
			}

			if (!isset($settings['max_width'])) {
				$settings['max_width'] = 1920;
			}

			if (!isset($settings['max_height'])) {
				$settings['max_height'] = 1080;
			}

			if (!isset($settings['resize'])) {
				$settings['resize'] = 'no';
			}
		}

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
		{
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', null, array('class' => 'two columns'));

			$this->addDimensionInput(
				$div,
				__('Minimum width (px)'),
				'min_width',
				__('If empty or 0, no minimum limit will be set.')
			);

			$this->addDimensionInput(
				$div,
				__('Minimum height (px)'),
				'min_height',
				__('If empty or 0, no minimum limit will be set.')
			);

			$this->addDimensionInput(
				$div,
				__('Maximum width (px)'),
				'max_width',
				__('If empty or 0, no maximum limit will be set. If resize is checked, max values will be used.')
			);

			$this->addDimensionInput(
				$div,
				__('Maximum height (px)'),
				'max_height',
				__('If empty or 0, no maximum limit will be set. If resize is checked, max values will be used.')
			);

			$wrapper->appendChild($div);

			$div = new XMLElement('div', null, array('class' => 'two columns'));

			$this->addUniqueCheckbox($div);
			$this->addResizeCheckbox($div);

			$wrapper->appendChild($div);
		}

		public function buildValidationSelect(XMLElement &$wrapper, $selected = null, $name = 'fields[validator]', $type = 'input', array $errors = null){

			include(TOOLKIT.'/util.validators.php');

			$label = Widget::Label( __( 'Validation Rule' ), new XMLElement('i', __( 'Optional' )) );
			$label->appendChild(
				Widget::Input( $name, $selected != null || $this->get('id') != null ? $selected : $upload['image'] )
			);
			$wrapper->appendChild( $label );

			$ul = new XMLElement('ul', null, array(
				'class' => 'tags singular',
				'data-interactive' => 'data-interactive'
			));
			$ul->appendChild(
				new XMLElement('li', 'image', array('class' => $upload['image']))
			);
			if (isset($upload['image-svg'])) {
				$ul->appendChild(
					new XMLElement('li', 'image+svg', array('class' => $upload['image-svg']))
				);
			}
			$wrapper->appendChild( $ul );

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
		protected function addDimensionInput(XMLElement &$wrapper, $label_value, $setting, $help_message){
			$label = Widget::Label(
				$label_value,
				Widget::Input( "fields[{$this->get('sortorder')}][{$setting}]", (string) $this->get( $setting ) ),
				'column'
			);

			$label->appendChild(
				new XMLElement(
					'p',
					$help_message,
					array('class' => 'help', 'style' => 'margin: 0;')
				)
			);

			$wrapper->appendChild( $label );
		}

		protected function addUniqueCheckbox(XMLElement &$wrapper){
			$label = Widget::Label( null, null, 'column' );
			$input = Widget::Input( "fields[{$this->get('sortorder')}][unique]", 'yes', 'checkbox' );
			if ($this->get( 'unique' ) == 'yes') {
				$input->setAttribute( 'checked', 'checked');
			}
			$label->setValue( __( '%s Create unique filenames', array($input->generate()) ) );

			$wrapper->appendChild( $label );
		}

		protected function addResizeCheckbox(XMLElement &$wrapper){
			$label = Widget::Label( null, null, 'column' );
			$input = Widget::Input( "fields[{$this->get('sortorder')}][resize]", 'yes', 'checkbox' );
			if ($this->get('resize') == 'yes') {
				$input->setAttribute( 'checked', 'checked' );
			}
			$label->setValue(__( '%s Resize image to fit max values', array($input->generate())));
			$wrapper->appendChild($label);
		}

		public function commit(){
			if( !Field::commit() ) return false;

			$id = $this->get( 'id' );

			if( $id === false ) return false;

			$settings = array();

			$settings['field_id']    = $id;
			$settings['destination'] = $this->get( 'destination' );
			$settings['validator']   = ($settings['validator'] == 'custom' ? null : $this->get( 'validator' ));
			$settings['unique']      = $this->get( 'unique' );
			$settings['min_width']   = $this->get( 'min_width' );
			$settings['min_height']  = $this->get( 'min_height' );
			$settings['max_width']   = $this->get( 'max_width' );
			$settings['max_height']  = $this->get( 'max_height' );
			$settings['resize']      = $this->get( 'resize' ) == 'yes' ? 'yes' : 'no';

			return FieldManager::saveSettings( $id, $settings );
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Input  */
		/*------------------------------------------------------------------------------------------------*/

		public function checkPostFieldData($data, &$message, $entry_id = null){
			if( is_array( $data ) && isset($data['name']) && ($this->get( 'unique' ) == 'yes') ){
				$data['name'] = $this->getUniqueFilename( $data['name'] );
			}

			// run basic upload check
			$error = parent::checkPostFieldData( $data, $message, $entry_id );

			// test for minimum dimensions
			if( $error == self::__OK__ ){

				// new file
				if( is_array( $data ) ){
					$tmp_name = $data['tmp_name'];
					$type     = $data['type'];
				}
				// updated file
				else {
					if( is_string( $data ) ){
						$tmp_name = WORKSPACE.$data;
						$type     = 'image/jpg'; // send some dummy data
					}
				}

				$meta = static::getMetaInfo($tmp_name, $type);

				// If we found some dimensions
				if( isset($meta['width']) && isset($meta['height']) ){

					$min_width  = $this->get( 'min_width' );
					$min_height = $this->get( 'min_height' );
					$max_width  = $this->get( 'max_width' );
					$max_height = $this->get( 'max_height' );

					// Min width
					if( !empty($min_width) && ($min_width != 0) && ($meta['width'] < $min_width) ){
						if( strlen( $message ) > 0 ){
							$message .= '<br />';
						}
						$message .= __( 'Image must have a minimum width of %1$spx.', array($min_width) );
						$error = self::__ERROR_CUSTOM__;
					}

					// Min height
					if( !empty($min_height) && ($min_height != 0) && $meta['height'] < $min_height ){
						if( strlen( $message ) > 0 ){
							$message .= '<br />';
						}
						$message .= __( 'Image must have a minimum height of %1$spx.', array($min_height) );
						$error = self::__ERROR_CUSTOM__;
					}

					// Check max only if resize is not active
					if( !$this->isResizeActive() ){
						// Max width
						if( !empty($max_width) && ($max_width != 0) && ($meta['width'] > $max_width) ){
							if( strlen( $message ) > 0 ){
								$message .= '<br />';
							}
							$message .= __( 'Image must have a maximum width of %1$spx.', array($max_width) );
							$error = self::__ERROR_CUSTOM__;
						}

						// Max height
						if( !empty($max_height) && ($max_height != 0) && ($meta['height'] > $max_height) ){
							if( strlen( $message ) > 0 ){
								$message .= '<br />';
							}
							$message .= __( 'Image must have a maximum height of %1$spx.', array($max_height) );
							$error = self::__ERROR_CUSTOM__;
						}
					}
				}
				// No dimension found
				else {
					if( is_array( $data ) && !empty($data['tmp_name']) ){
						$message .= __( 'Uploaded file is not an image.' );
						$error = self::__ERROR_CUSTOM__;
					}
				}
			}

			return $error;
		}

		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
		{
			if (!is_array($data) && !is_string($data)) {
				return parent::processRawFieldData( $data, $status, $message, $simulate, $entry_id );
			}

			if (is_array($data) && isset($data['name']) && ($this->get( 'unique' ) == 'yes')) {
				$data['name'] = $this->getUniqueFilename( $data['name'] );
			}

			$max_width  = $this->get('max_width');
			$max_height = $this->get('max_height');

			// file already exists in Symphony
			if (is_string($data)) {

				// 1. process Upload
				$result = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);

				// Find Mime if it was not submitted
				if ($result['mimetype'] === 'application/octet-stream') {
					if (function_exists( 'finfo_file')) {
						$result['mimetype'] = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $data['name']);
					}
				}

				// 2. resize
				if ($this->isResizeActive() &&
					$result['mimetype'] !== 'application/octet-stream' &&
					!static::isSvg($result['mimetype'])) {

					if ((!empty($max_width) && ($max_width > 0)) || (!empty($max_height) && ($max_height > 0))) {
						if (is_file($file = WORKSPACE.$result['file'])) {
							$dimensions = $this->figureDimensions(static::getMetaInfo($file, $result['mimetype']));
							if ($dimensions['proceed']) {
								if (self::resize( $file, $dimensions['width'], $dimensions['height'], $result['mimetype'])) {
									$result['size'] = filesize($file);
									$result['meta'] = serialize(static::getMetaInfo( $file, $result['mimetype']));
								}
							}
						}
					}
				}
			}

			// new file in Symphony
			else if (is_array($data)) {

				// 1. resize
				if ($this->isResizeActive() && !static::isSvg($result['mimetype'])) {

					if ((!empty($max_width) && ($max_width > 0)) || (!empty($max_height) && ($max_height > 0))) {
						if (is_file($file = $data['tmp_name'])) {
							$dimensions = $this->figureDimensions(static::getMetaInfo($file, $data['type']));
							if ($dimensions['proceed']) {
								if (self::resize($file, $dimensions['width'], $dimensions['height'], $data['type'])) {
									$data['size'] = filesize( $file );
								}
							}
						}
					}
				}

				// 2. process Upload
				$result = parent::processRawFieldData($data, $status, $message, $simulate, $entry_id);
			}

			return $result;
		}

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			// Let the upload field do it's job
			parent::displayPublishPanel( $wrapper, $data, $flagWithError, $fieldnamePrefix, $fieldnamePostfix, $entry_id );

			$label = $this->getChildrenWithClass( $wrapper, 'file', 'label' );
			if( $label != null ){
				// try to find the i element
				$i = $this->getChildrenWithClass( $wrapper, null, 'i' );
				if( $i == null ){
					// create one and prepend it if nothing found
					$i = new XMLElement('i');
					$label->prependChild( $i );
				}

				$i->setValue( ' '.$this->generateHelpMessage() );
			}
		}

		protected function generateHelpMessage(){
			$sizeMessage               = '';
			$sizes                     = array();
			if ($this->get( 'min_width' ) == $this->get( 'max_width' ) && $this->get( 'min_height' ) == $this->get( 'max_height' )) {
				$sizes[__( 'Width' )]  = $this->get( 'min_width' );
				$sizes[__( 'Height' )] = $this->get( 'min_height' );
			} else if ($this->get( 'min_width' ) == $this->get( 'max_width' )) {
				$sizes[__( 'Width' )]  = $this->get( 'min_width' );
				$sizes[__( 'Min height' )] = $this->get( 'min_height' );
				$sizes[__( 'Max height' )] = $this->get( 'max_height' );
			} else if ($this->get( 'min_height' ) == $this->get( 'max_height' )) {
				$sizes[__( 'Min width' )] = $this->get( 'min_width' );
				$sizes[__( 'Max width' )] = $this->get( 'max_width' );
				$sizes[__( 'Height' )]  = $this->get( 'min_height' );
			} else {
				$sizes[__( 'Min width' )]  = $this->get( 'min_width' );
				$sizes[__( 'Min height' )] = $this->get( 'min_height' );
				$sizes[__( 'Max width' )]  = $this->get( 'max_width' );
				$sizes[__( 'Max height' )] = $this->get( 'max_height' );
			}
			
			foreach($sizes as $key => $size){
				if( !empty($size) && $size != 0 ){
					$sizeMessage .= $key.': '.$size.'px, ';
				}
			}
			return trim( $sizeMessage, ', ' );
		}

		/*------------------------------------------------------------------------------------------------*/
		/*  Output  */
		/*------------------------------------------------------------------------------------------------*/

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null){
			if( !$file = $data['file'] ){
				if( $link ){
					return parent::prepareTableValue( null, $link );
				}
				else return parent::prepareTableValue( null );
			}

			if( $data['width'] > $data['height'] ){
				$width  = 40;
				$height = 0;
			}
			else{
				$width  = 0;
				$height = 40;
			}

			$destination = str_replace( '/workspace', '', $this->get( 'destination' ) ).'/';

			$src = '';
			if (isset($data['mimetype']) && self::isSvg($data['mimetype'])) {
				$src = URL . '/workspace' . $destination . $file;
			}
			else {
				$src = URL . '/image/1/' . $width . '/' . $height . $destination . $file;
			}
			$image = '<img style="vertical-align: middle; max-height:40px;" src="' . $src . '" alt="'.$this->get( 'label' ).' of Entry '.$entry_id.'"/>';

			if ($link){
				$link->setValue( $image );
			}
			else{
				$link = Widget::Anchor( $image, URL.$this->get( 'destination' ).'/'.$file );
			}
			$link->setAttribute('data-path', $this->get('destination'));
			return $link->generate();
		}

		public function prepareTextValue($data, $entry_id = null){
			if (!is_array($data)) {
				return null;
			}
			return $data['file'];
		}

		public function allowDatasourceParamOutput() {
			return true;
		}

		public function getParameterPoolValue(array $data, $entry_id = null) {
			return $this->prepareTextValue($data);
		}

		/*------------------------------------------------------------------------------------------------*/
		/*  In-house  */
		/*------------------------------------------------------------------------------------------------*/

		protected function figureDimensions($meta){
			$width  = 0;
			$height = 0;

			$max_width  = $this->get( 'max_width' );
			$max_height = $this->get( 'max_height' );

			$img_width  = $meta['width'];
			$img_height = $meta['height'];

			$ratio = $img_width / $img_height;

			// if width exceeds
			if( ($img_width > $max_width) && ($max_width > 0) ){
				$width  = $max_width;
				$height = 0;

				if( $max_height > 0 ){
					// if resulting height doesn't fit, resize from height
					if( $width / $ratio > $max_height ){
						$width  = 0;
						$height = $max_height;
					}
				}
			}

			// if height exceeds
			elseif( ($img_height > $max_height) && ($max_height > 0) ){
				$width  = 0;
				$height = $max_height;

				if( $max_width > 0 ){
					// if resulting width doesn't fit, resize from width
					if( $height / $ratio > $max_width ){
						$width  = $max_width;
						$height = 0;
					}
				}
			}

			return array(
				'proceed' => ($width != 0 || $height != 0),
				'width'   => $width,
				'height'  => $height
			);
		}

		protected function getUniqueFilename($filename){
			// since unix timestamp is 10 digits, the unique filename will be limited to ($crop+1+10) characters;
			$crop = '150';
			return preg_replace( "/(.*)(\.[^\.]+)/e", "substr('$1', 0, $crop).'-'.time().'$2'", $filename );
		}

		protected function isResizeActive(){
			return $this->get( 'resize' ) == 'yes';
		}

		private function getChildrenWithClass(XMLElement &$rootElement, $className, $tagName = null){
			if( $rootElement == null ){
				return null;
			}

			// contains the right css class and the right node name (if any)
			// TODO: Use word bondaries instead of strpos
			if(
				(!$className || strpos( $rootElement->getAttribute( 'class' ), $className ) > -1)
				&&
				(!$tagName || $rootElement->getName() == $tagName)
			){
				return $rootElement;
			}

			// recursive search in child elements
			foreach($rootElement->getChildren() as $key => $child){

				if (!($child instanceof XMLElement)) {
					continue;
				}

				$res = $this->getChildrenWithClass( $child, $className, $tagName );

				if( $res != null ){
					return $res;
				}
			}

			return null;
		}

	}
