<?php
require('simple_html_dom.php');

class BikeshareParser
{
	const USER_AGENT = 'Bikeshare parser';
	private $cookieJar = array();
	private $username, $password;
	private $requireHTTPS = true; // Should always be set to true for production use
	
	private function fillCookieJar($http_response_header)
	{		
		$cookieHeader = 'Set-Cookie: ';
		$this->cookieJar = array();
		
		foreach ($http_response_header as $header)
		{
			if (substr($header, 0, strlen($cookieHeader)) == $cookieHeader)
			{
				$this->cookieJar[] = substr($header, strlen($cookieHeader));
			}
		}
	}

	public function getCookiesString()
	{
		$cookiesString = '';
		foreach ($this->cookieJar as $cookie)
		{
			$cookiesString .= $cookie . '; ';
		}
		
		return substr($cookiesString, 0, -2);
	}

	private function isSSL()
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443);
	}
	
	private function validateLogin()
	{
		// Check if using SSL
		if ($this->requireHTTPS && empty($_SERVER['HTTPS']))
		{
			throw new APIException('Please access the API using HTTPS');
		}
		
		// Validate username and password
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
    		header('WWW-Authenticate: Basic realm="Bikeshare API"');
    		header('HTTP/1.0 401 Unauthorized');
    		exit;
    	}

		if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW']))
		{
			throw new APIException('Please specify username and password');
		}

		$this->username = $_SERVER['PHP_AUTH_USER'];
		$this->password = $_SERVER['PHP_AUTH_PW'];
	}
		
	public function authenticate()
	{
		// Validate login data first
		$this->validateLogin();
	
		// Login to Bikeshare
		$data = array(
			'username' => $this->username,
			'password' => $this->password,
		);
		
		// Post and authenticate to Bikesahre
		$data = http_build_query($data); // Convert array to http query string
		$opts = array(
		  'http' => array(
		    'method' => "POST",
		    'header' => "User-Agent: ". self::USER_AGENT ."\r\n" .
		                "Content-Type: application/x-www-form-urlencoded\r\n" .
		                "Content-Length: " . strlen($data) . "\r\n",
		    'content' => $data
		  )
		);
		
		$context = stream_context_create($opts);
		$loginPage = file_get_contents("https://capitalbikeshare.com/login", false, $context);

		// Destroy username, password and data
		unset($this->username);
		unset($this->password);
		unset($this->data);

		// Check for redirect header
		$headers = $this->http_parse_headers($http_response_header);

		// If we are not redirected, login must be invalid
		if (!isset($headers['Location']))
		{
			throw new APIException('Invalid username or password');
		}
		
		$this->fillCookieJar($http_response_header);
	}

	function http_parse_headers($headers)
	{
	    foreach ($headers as $header)
	    {
			$header = explode(": ",$header);
			if ($header[0] && !isset($header[1]))
			{
				$headerdata['status'] = $header[0];
			}
			elseif($header[0] && $header[1])
			{
				$headerdata[$header[0]] = $header[1];
			}
		}
		return $headerdata;
    }

	function request($url)
	{
		// Retreive the page
		$opts = array(
			'http' => array(
			'method' => "GET",
			'header' => "User-Agent: ". self::USER_AGENT ."\r\n" .
			            "Cookie: " . $this->getCookiesString() . "\r\n"
			)
		);

		$context = stream_context_create($opts);
		return file_get_contents($url, false, $context);
	}
}