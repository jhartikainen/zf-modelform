<?php
/**
 * A thumbnail image creator with automatic caching
 * @author Jani Hartikainen <firstname at codeutopia.net>
 */
class CU_Image_Thumbnailer
{
	private static $_instance = null;

	private $_thumbPath = 'images/small';

	/**
	 * Implements singleton
	 * @return CU_Image_Thumbnailer
	 */
	public static function getInstance()
	{
		if(self::$_instance == null)
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Set options. Currently the only option is 'path' to the file
	 * @param array $options
	 */
	public static function setOptions(array $options)
	{
		if(isset($options['path']))
			self::getInstance()->_thumbPath = $options['path'];
	}

	/**
	 * Returns a filesystem path to the thumbnail image. Will generate
	 * and cache the thumbnail unless it already exists
	 *
	 * @param string $image fs path to image
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public function getThumbnail($image, $width, $height)
	{
		$fileParts = explode('.', $image);
		$ext = $fileParts[ (count($fileParts) - 1) ];
		
		$fn = $this->_thumbPath . '/' . str_replace('/', '-', $image) . '_' . $width . 'x' . $height . '.' . $ext;
		if(!file_exists($fn))
		{
			$img = new CU_Image($image, $ext);
			$img->resize($width, $height);
			$img->save($fn);
		}

		return $fn;
	}
}
