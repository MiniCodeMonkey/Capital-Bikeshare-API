# Capital Bikeshare API

Scrapes your member information from http://capitalbikeshare.com and outputs it nicely formatted as JSON

## Usage

A local environment to interact with the data can be set up straight away if you're running PHP 5.4+

Install composer dependencies

`$ composer install`

> Read more about Composer [here](https://getcomposer.org)

Start local webserver

`$ php -S localhost:8080 -t public/`

Perform API request

`$ curl --user bikeshareusername:bikesharepassword http://localhost:8080/rentals`

> Note: Depending on your rental history size, this may take a little while (It takes 2 1/2 minute to scrape my 300+ rentals).

If you're running this on a regular webserver, make sure that the `data` folder is writable by the webserver user. E.g. `chmod -R 777 data/`

### Example output
```json
[
  {
    "start_station": "14th & Rhode Island Ave NW",
    "start_date": "01-05-2013 12:56 pm",
    "end_station": "25th St & Pennsylvania Ave NW",
    "end_date": "01-05-2013 1:05 pm",
    "duration_seconds": 524,
    "duration": "8 minutes, 44 seconds",
    "cost": 0
  }
]
```

## Features
- Rental history
- Caches results for 14 days using simple file-based cache
