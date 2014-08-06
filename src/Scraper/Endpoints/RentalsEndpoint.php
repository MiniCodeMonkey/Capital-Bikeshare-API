<?php namespace Scraper\Endpoints;

use Scraper\APIException;
use Scraper\BikeshareParser;

class RentalsEndpoint extends BikeshareParser
{
	private $stations = array();

	public function run()
	{
		$this->authenticate();

		$jsonData = $this->loadFromCache();

		if (is_null($jsonData))
		{
			$this->parseStations();

			$keys = array(
				'start_station',
				'start_date',
				'end_station',
				'end_date',
				'duration',
				'cost',
				'distance',
				'calories_burned',
				'co2_offset'
			);

			$trips = array();
			$index = 0;

			$lastPageIndex = null;

			$reauthenticateTries = 0;
			do
			{
				$url = 'https://www.capitalbikeshare.com/member/rentals/' . $index;
				$page = $this->request($url);
				
				$html = \ThauEx\SimpleHtmlDom\SHD::strGetHtml($page);
				$pageTrips = 0;

				if ($lastPageIndex == null) {
					$parts = explode('/', trim($html->find('.pagination a', -1)->href, '/'));
					$lastPageIndex = $parts[count($parts) - 1];
				}

				if (!is_object($html)) {
					throw new APIException('Could not parse ' . $url);
				}

				if (is_object($html->find('input[id=username]', 0)) && $reauthenticateTries < 3) {
					// Try to reauthenticate
					sleep(2);
					$this->authenticate();
					$reauthenticateTries++;
					continue;
				}

				if (!is_object($html->find('table', 1))) {
					throw new APIException('Could not find table ' . $url);
				}

				// Reset counter
				$reauthenticateTries = 0;

				foreach ($html->find('table', 1)->find('tr') as $row)
				{
					$i = 0;
				    $trip = array();
				    foreach($row->find('td') as $cell)
				    {
				    	// Determine key and value
				    	$currentKey = $keys[$i];
				    	$value = trim($cell->innertext);

				    	if ($currentKey == 'duration')
				    	{
				    		$trip['duration_seconds'] = $this->parseDuration($value);
				    	}
				    	elseif ($currentKey == 'cost')
				    	{
				    		$value = floatval(trim(str_replace('$', '', $value)));
				    	}
				    	elseif ($currentKey == 'start_date' || $currentKey == 'end_date')
				    	{
				    		//$value = date(DATE_ISO8601, strtotime($value)); // Consider if this should be converted or not?
				    	}

				        $trip[$currentKey] = $value;

				        $i++;
				    }

				    if (count($trip) > 0)
				    {
				    	if (isset($this->stations[$trip['start_station']]))
				    		$trip['start_station_loc'] = $this->stations[$trip['start_station']];
				    	
				    	if (isset($this->stations[$trip['end_station']]))
				    		$trip['end_station_loc'] = $this->stations[$trip['end_station']];

				    	$trips[] = $trip;
				    	$pageTrips++;
					}
				}

				$index += 20;
			} while ($index <= $lastPageIndex);

			$jsonData = json_encode($trips);
			$this->saveToCache($jsonData);
		}

		echo $jsonData;
	}

	private function saveToCache($jsonData)
	{
		if (!$this->isAuthenticated)
			return NULL;

		$cacheFile = $this->getDataPath() . '/users/' . sha1($this->username) . '.json';

		file_put_contents($cacheFile, $jsonData);
	}

	private function loadFromCache()
	{
		if (!$this->isAuthenticated)
			return NULL;

		$cacheFile = $this->getDataPath() . '/users/' . sha1($this->username) . '.json';
		if (file_exists($cacheFile))
		{
			$maxCacheTime = 3600 * 24 * 14; // 14 days
			$modificationTimeDiff = time() - filemtime($cacheFile);
			if ($modificationTimeDiff > $maxCacheTime)
			{
				return NULL;
			}
			else
			{
				return file_get_contents($cacheFile);
			}			
		}

		return NULL;
	}

	private function parseStations()
	{
		$bikeStationsFilename = $this->getDataPath() . '/bikeStations.xml';

		if (!file_exists($bikeStationsFilename)) {
			file_put_contents($bikeStationsFilename, file_get_contents('https://www.capitalbikeshare.com/data/stations/bikeStations.xml'));
		}

		$xml = new \SimpleXMLElement(file_get_contents($bikeStationsFilename));

		foreach ($xml->station as $station)
		{
			$this->stations[(string)$station->name] = array((float)$station->lat, (float)$station->long);
		}
	}

	private function parseDuration($duration)
	{
		$seconds = 0;

		$parts = explode(',', $duration);

		foreach ($parts as $part)
		{
			$part = trim($part);

			list($value, $unit) = explode(' ', $part);

			$value = intval($value);

			if (substr($unit, 0, 3) == 'day')
			{
				$seconds += $value * (3600 * 24);
			}
			elseif (substr($unit, 0, 4) == 'hour')
			{
				$seconds += $value * 3600;
			}
			elseif (substr($unit, 0, 3) == 'min')
			{
				$seconds += $value * 60;
			}
			elseif (substr($unit, 0, 3) == 'sec')
			{
				$seconds += $value;
			}
			else
			{
				throw new APIException('Internal error: Unknown unit "'. $unit .'"');
			}
		}

		return $seconds;
	}
}