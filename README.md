# LudumDareRescueChicken
A web page which spotlights games with too few votes to get a rating in Ludum Dare

# Installation
- To run this web page, download, install and run a php server (For example Apache)
- Copy the contents of this repository onto your server.
- Open the web page in your browser.

# How it works
This page scrapes a particular page on the Ludum Dare website, which contains information about how many votes participants received. It orders these entries by number of received votes and outputs them. The page caches scrape results into a json file, so as not to scrape the page every time it's loaded. By default it scrapes the page on the Ludum Dare website once every 60 seconds, at most.

The page is set up to default to scraping the LD35 event, however a GET parameter can be set to change this. Adding "?event=36" at the end of the URL will perform the scrape for Ludum Dare 36.

# Live demo

http://liamlime.com/content/ldtools/rescuechicken/
