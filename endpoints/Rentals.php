<?php
class Rentals extends BikeshareParser
{
	public function run()
	{
		$this->authenticate();
		$page = $this->request('https://capitalbikeshare.com/member/rentals');
		$html = str_get_html($page);
		$keys = array(
			'start_station',
			'start_date',
			'end_station',
			'end_date',
			'duration',
			'cost'
		);

		$trips = array();
		foreach ($html->find('tr') as $row)
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
		    	$trips[] = $trip;
			}
		}

		echo json_encode($trips);
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