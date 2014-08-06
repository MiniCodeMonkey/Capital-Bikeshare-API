<?php namespace Scraper\Endpoints;

use Scraper\APIException;
use Scraper\BikeshareParser;

class DemoEndpoint extends BikeshareParser
{
	public function run()
	{
		echo file_get_contents($this->getDataPath() . '/demo.json');
	}
}