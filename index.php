<?php
require('APIException.php');
require('BikeshareParser.php');

header('Content-Type: application/json');

try
{
	// Parse URL
	$url = parse_url($_SERVER['REQUEST_URI']);
	$path = explode('/', $url['path']);
	$endpoint = preg_replace("/[^A-Za-z0-9 ]/", '', $path[1]);
	$endpoint = ucfirst(strtolower($endpoint)); // Normalize name

	// Check if we support this endpoint
	if (!file_exists('endpoints/' . $endpoint . '.php'))
	{
		throw new APIException('Endpoint doesn\'t exist');
	}

	// Run parser for the endpoint
	require('endpoints/' . $endpoint . '.php');
	$parser = new $endpoint();
	$parser->run();
}
catch (APIException $e)
{
	echo json_encode(array(
		'error' => $e->getMessage()
	));
}