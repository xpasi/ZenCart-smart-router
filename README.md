ZenCart-smart-router
====================

a smart URL router for ZenCart

## What is it?

This is a URL router for ZenCart which was written because the existing "routers" (called stupidly "SEO URL") were too clumsy and way too complex. It is a drop-in type of plugin which allows normal ZenCart links to work (except for zencarts own "clean url" system), so you don't loose links in search engines when installing it on an existing shop.

## What it does?

It maps clean path URLs to ZenCart query parameters and re-parses links generated by ZenCart to form clean URLs.

**Examples:**

> http://www.example.com/index.php?main_page=products_info&products_id=1

> **becomes:**

> http://www.example.com/product/name-of-the-product

> _Notice the lack of product id in the URL._

It also enables URL translations, so you can have the URL paths in your own language instead of english.

## How it does it?

This is called "smart router", because it doesn't rely on hardcoded URL-rewrite rules per page. Instead the path is feeded into the router, which sets the needed internal variables for a given page "automatically".

It first resolves which "module" (in includes/modules/pages/) should handle the request, by directly seeking the directory (so that example.com/contact_us opens includes/modules/pages/contact_us) or by a routing table in the database. The routing table allows us to rename paths (like product_info -> product).

After the "module" has been selected, control is passed to a sub-routing class, which resides in a file in the modules directory (called routing.php). The sub-router file contains a class that is attached to the main static router class, which then decides what to do with the given path and/or query string. By default, query strings are passed through as-is so normal ZenCart or other add-on operation is not hindered.

## Install notes

Copy the files to your Zen Cart installation.

The module is a drop-in plugin, but because there's no notifier in zen_href_link() (the Zen Cart "API" for link generation), one core file needs to be edited:

**Add a line to the end of zen_href_link() function, just before the return statement at** *includes/functions/html_output.php*

	$link = prr_router::link($link); // PRR: Hook for routing

## Support

For support, please contact me at *p@prr.fi*

Donations are welcome :)
