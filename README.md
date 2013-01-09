Capital Bikeshare API
============================

Scrapes your member information from http://capitalbikeshare.com and outputs it nicely formatted as JSON

Example
-------------------------
Online version available at https://bikeshare.dotsquare.dk/rentals

Features
-------------------------
- Rental history

Example output
-------------------------
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

Mathias Hansen - <me@codemonkey.io>
