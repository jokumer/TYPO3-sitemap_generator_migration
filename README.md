# Sitemap Generator migration TYPO3 extension

Migration for EXT:sitemap_generator to fetch EXT:dd_googlesitemap calculated change frequency

## What it does

This extension has been written for EXT:sitemap_generator from Markus Sommer
https://github.com/beardcoder/sitemap_generator,
to migrate change frequency of EXT:dd_googlesitemap from Dmitry Dulepov 
https://github.com/dmitryd/typo3-dd_googlesitemap
which was calculated on the fly, given by last modification timestamps.
Change frequencies, which can not calculated are set with default value `monthly`.

## How to

If you want to change from extension dd_googlesitemap to sitemap_generator
and want to use calculated change frequencies as page property for extension sitemap_generator follow these steps:
- Download this extension named as `sitemap_generator_migration`
- Install this extension in your TYPO3 installation
- Execute Updater in extension manager for this extension sitemap_generator_migration - where you will be guided
- Uninstall this extension afterwards and remove it from your installation. 

## Requirements

- TYPO3 version 6.2, 7.6, 8.7
- Extension dd_googlesitemap must not be installed, but its fields must still exist for table pages (pages.tx_ddgooglesitemap_lastmod).
- Table fields pages.tx_ddgooglesitemap_lastmod requires existing values (not for all pages)
- Extension sitemap_generator must not be installed, but its fields must exist for table pages (pages.sitemap_changefreq).
- Table pages.sitemap_changefreq may not be filled with values. In this case you should empty this fields using SQL

    `UPDATE pages SET sitemap_changefreq = '';`

## Hint

Includes copied method 'calculateChangeFrequency()' from EXT:dd_googlesitemap v2.1.4
(c) 2007-2014 Dmitry Dulepov <dmitry.dulepov@gmail.com>
