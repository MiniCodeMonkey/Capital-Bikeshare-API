<?php
require('../vendor/autoload.php');

header('Content-Type: application/json');

try
{
	// Parse URL
	$url = parse_url($_SERVER['REQUEST_URI']);
	$path = explode('/', $url['path']);
	$endpoint = preg_replace("/[^A-Za-z0-9 ]/", '', $path[1]);
	$endpoint = ucfirst(strtolower($endpoint)); // Normalize name

	// Check if we support this endpoint
	$endpointClassName = '\\Scraper\\Endpoints\\' . $endpoint . 'Endpoint';
	if (!class_exists($endpointClassName))
	{
		throw new Scraper\APIException('Endpoint doesn\'t exist');
	}

	// Run parser for the endpoint
	$parser = new $endpointClassName();
	$parser->run();
}
catch (Scraper\APIException $e)
{
	echo json_encode(array(
		'error' => $e->getMessage()
	));
}