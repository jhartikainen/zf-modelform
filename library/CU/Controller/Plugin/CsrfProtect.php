<?php
/**
 * A controller plugin for protecting forms from CSRF
 * 
 * Works by looking at the response and adding a hidden element to every
 * form, which contains an automatically generated key that is checked
 * on the next request against a key stored in the session
 * 
 * @author Jani Hartikainen <firstname at codeutopia net>
 */
class CU_Controller_Plugin_CsrfProtect extends Zend_Controller_Plugin_Abstract 
{
	/**
	 * Session storage
	 * @var Zend_Session_Namespace
	 */
	protected $_session = null;
	
	/**
	 * The name of the form element which contains the key
	 * @var string
	 */
	protected $_keyName = 'csrf';
	
	/**
	 * How long until the csrf key expires (in seconds)
	 * @var int
	 */
	protected $_expiryTime = 300;
	
	public function __construct(array $params = array())
	{
		if(isset($params['expiryTime']))
			$this->setExpiryTime($params['expiryTime']);
		
		if(isset($params['keyName']))
			$this->setKeyName($params['keyName']);

		$this->_session = new Zend_Session_Namespace('CsrfProtect');
	}
	
	/**
	 * Set the expiry time of the csrf key
	 * @param int $seconds expiry time in seconds
	 * @return CU_Controller_Plugin_CsrfProtect implements fluent interface
	 */
	public function setExpiryTime($seconds)
	{
		$this->_expiryTime = $seconds;
		return $this;
	}

	/**
	 * Set the name of the csrf form element
	 * @param string $name
	 * @return CU_Controller_Plugin_CsrfProtect implements fluent interface
	 */
	public function setKeyName($name)
	{
		$this->_keyName = $name;
		return $this;
	}
	
	/**
	 * Performs CSRF protection checks before dispatching occurs
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{		
		if($request->isPost() === false)
			return;
			
		$value = $request->getPost($this->_keyName);
		if(!isset($this->_session->key) || $value != $this->_session->key)
			throw new RuntimeException('A possible CSRF attack detected - keys do not match');
	}
	
	/**
	 * Generates a new key and adds protection to forms
	 */
	public function dispatchLoopShutdown()
	{
		$response = $this->getResponse();
		$newKey = sha1(microtime() . mt_rand());
		
		$this->_session->key = $newKey;
		$this->_session->setExpirationSeconds($this->_expiryTime);
		
		$headers = $response->getHeaders();
		foreach($headers as $header)
		{
			//Do not proceed if content-type is not html/xhtml or such
			if($header['name'] == 'Content-Type' && strpos($header['value'], 'html') === false)
				return;			
		}
		
		$element = sprintf('<input type="hidden" name="%s" value="%s" />',
			$this->_keyName,
			$newKey
		);
		
		$body = $response->getBody();
		
		//Find all forms and add the csrf protection element to them
		$body = preg_replace('/<form[^>]*>/i', '$0' . $element, $body);
		
		$response->setBody($body);
	}
}