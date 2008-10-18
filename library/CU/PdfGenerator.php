<?php
/**
 * A class for generating PDF reports
 *
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_PdfGenerator
{
	/**
	 * Template pdf filename
	 * @var string
	 */
	protected $_template = '';
	
	/**
	 * Main font
	 * @var Zend_Pdf_Resource_Font
	 */
	protected $_font = null;
	
	/**
	 * Main font size
	 * @var integer
	 */
	protected $_fontSize = 0;

	/**
	 * fonts for specific keys
	 * @var array Zend_Pdf_Resource_Font array
	 */
	protected $_fontsForKeys = array();
	
	/**
	 * Font sizes for specific keys
	 * @var array integer array
	 */
	protected $_fontSizesForKeys = array();
	
	/**
	 * Report data
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Positions of keys
	 * @var array
	 */
	protected $_positions = array();
	
	public function __construct()
	{
	}
	
	/**
	 * Convenience method for allowing fluent construction
	 *
	 * @return CU_PdfGenerator
	 */
	public static function create()
	{
		return new CU_PdfGenerator();
	}
	

	/**
	 * Render the PDF
	 * @return Zend_Pdf
	 */
	public function getPdf()
	{
		$pdf = Zend_Pdf::load($this->_template);
		
		for($i = 0, $dataCount = count($this->_data); $i < $dataCount; $i++)
		{
			if($i > 0)
				$pdf->pages[] = new Zend_Pdf_Page($pdf->pages[0]);
			
			$page = $pdf->pages[$i];
			
			$values = $this->_data[$i];
			foreach($values as $key => $value)
			{
				$font = $this->getFontForKey($key);
				$size = $this->getFontSizeForKey($key);
				if($font != null)
					$page->setFont($font, $size);
				else 
					$page->setFont($this->_font, $this->_fontSize);
					
				$position = $this->getPosition($key);
				if($position == null)
					continue;
					
				list($x, $y) = $position;
				$yFromTop = $page->getHeight() - $y;
				
				$page->drawText($value, $x, $yFromTop);
			}
		}
		
			
		return $pdf;
	}
	
	/**
	 * Set position of a key
	 *
	 * @param string $key
	 * @param integer $x
	 * @param integer $y
	 * @return CU_PdfGenerator
	 */
	public function setPosition($key, $x, $y)
	{
		$this->_positions[$key] = array($x, $y);
		return $this;
	}
	
	/**
	 * Set positions for keys
	 *
	 * @param array $positions key => key, value => array(x, y)
	 * @return CU_PdfGenerator
	 */
	public function setPositions(array $positions)
	{
		$this->_positions = array_merge($this->_positions, $positions);
		
		return $this;
	}
	
	/**
	 * Set template PDF file
	 *
	 * @param string $filename
	 * @return CU_PdfGenerator
	 */
	public function setTemplate($filename)
	{
		$this->_template = $filename;
		return $this;
	}
	
	/**
	 * Set base font size for the PDF
	 *
	 * @param Zend_Pdf_Resource_Font $font
	 * @param unknown_type $size
	 * @return CU_PdfGenerator
	 */
	public function setFont(Zend_Pdf_Resource_Font $font, $size)
	{
		$this->_font = $font;
		$this->_fontSize = $size;
		return $this;
	}
	
	/**
	 * Set font for specific data key
	 *
	 * @param string $key key name
	 * @param Zend_Pdf_Resource_Font $font
	 * @param integer $size
	 * @return CU_PdfGenerator
	 */
	public function setFontForKey($key, Zend_Pdf_Resource_Font $font, $size)
	{
		$this->_fontsForKeys[$key] = $font;
		$this->_fontSizesForKeys[$key] = $size;
		return $this;
	}
	
	/**
	 * Set data for generator
	 *
	 * @param array $data
	 * @return CU_PdfGenerator
	 */
	public function setData(array $data)
	{
		$this->_data = $data;
		return $this;
	}
	
	/**
	 * Return template filename
	 *
	 * @return string
	 */
	public function getTemplate()
	{		
		return $this->_template;
	}
	
	/**
	 * Return main font
	 *
	 * @return Zend_Pdf_Resource_Font|null
	 */
	public function getFont()
	{
		return $this->_font;
	}
	
	/**
	 * Return main font size
	 *
	 * @return integer
	 */
	public function getFontSize()
	{
		return $this->_fontSize;
	}
	
	/**
	 * Return font for specific key
	 *
	 * @param string $key
	 * @return Zend_Pdf_Resource_Font|null
	 */
	public function getFontForKey($key)
	{
		if(isset($this->_fontsForKeys[$key]))
			return $this->_fontsForKeys[$key];
			
		return null;
	}
	
	/**
	 * Return font size for specific key. 
	 * If no value is specified, returns the main font size
	 *
	 * @param string $key
	 * @return integer
	 */
	public function getFontSizeForKey($key)
	{
		if(isset($this->_fontSizesForKeys[$key]))
			return $this->_fontSizesForKeys[$key];
			
		return $this->_fontSize;
	}
	
	/**
	 * Get data
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->_data;
	}	
	
	/**
	 * Get key position
	 *
	 * @param string $key
	 * @return array|null
	 */
	public function getPosition($key)
	{
		if(isset($this->_positions[$key]))
			return $this->_positions[$key];

		return null;
	}
}