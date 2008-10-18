<?php
/**
 * A class for handling some image operations with GD
 *
 * @author Jani Hartikainen <firstname at codeutopia.net>
 */
class CU_Image
{
	protected $_resource = null;
	protected $_type = '';
	
	/**
	 * @param string $path Filesystem path to the image file
	 * @param string $ext jpg/jpeg/png/gif or null to guess from the filename
	 */
	public function __construct($path, $ext = null)
	{
		if($ext == null)
		{
			$fileParts = explode('.', $path);
			$ext = $fileParts[ (count($fileParts) - 1) ];
		}

		if($ext == 'jpg')
			$ext = 'jpeg';

		$this->_type = strtolower($ext);
		$this->_file = $path;
		$this->_open();
	}

	protected function _open()
	{
		$func = 'imagecreatefrom' . $this->_type;
		$this->_resource = $func($this->_file);
	}

	/**
	 * Resize image
	 * @param int $width
	 * @param int $height
	 * @return CU_Image implements fluent interface
	 */
	public function resize($width, $height)
	{
		$newRes = imagecreatetruecolor($width, $height);
		$origWidth = imagesx($this->_resource);
		$origHeight = imagesy($this->_resource);
		imagecopyresampled($newRes, $this->_resource, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

		imagedestroy($this->_resource);
		$this->_resource = $newRes;
		return $this;
	}

	/**
	 * Saves the image
	 * @param string $name Filesystem path to file
	 */ 
	public function save($name)
	{
		$func = 'image' . $this->_type;
		$func($this->_resource, $name);
	}
}
