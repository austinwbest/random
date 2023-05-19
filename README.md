# Random stuff

A place to dump random quickly thrown together scripts and such. Typically something is needed to test or make some one off changes, these things where created to do that. Most of this is thrown together in a couple hours and never really optimized since they are `throw away` scripts that i made for someone or to solve a time consuming task. With that said, none of this stuff is used in a production environment so they may require some adjusting to do so if you want.

Most of them have some form of output or simple UI to know it is working.

## Radarr branch comparing

> branches.php

Simple script that currently depends on a Notifiarr session to work but remove the require dependancies and it should work fine stand alone. It will compatre the amount of migrations and the language ids across the 3 brances that Radarr uses (master, develop, nightly) and show you if you can swap between them. A yes in both columns means you can hop between those branches without an issue.

## *arr language localization

> language.php

This is a quick script to convert \*arr app hard coded strings to a localization file. It expects the \*arr app to already have the localization framework in place.

## *arr language key issues

> languageCleanup.php

This script will look for any defined language keys in the source and display which are missing a translation in the english file and it will look in the english file to see which defined keys are not in the source

## *arr os change database path tool

> sqlite/*

This script will allow you to pick a radarr.db file and adjust all the paths from one os to another

## *arr cache compare tool

> cache.php

This one actually relies on functions from Notifiarr so it would take some tweaking to get working stand alone but is not hard at all to do. Replacing the functions that fetch the meta would be about all it takes

## *arr endpoint testing

> endpointTest.php

Nothing special, just a simple way to test out endpoint responses. Was made/used when adding the jackett importer to radarr (scraped later) and then when setting up the sync code for prowlarr

## prowlarr indexer conversion

> indexers/*

This will take a folder full of Jackett c# indexers and convert them to the base usage for Prowlarr

## jackett + qbt cross seed

> xseed/*

This will connect to your qbt and jackett to try and reach out to the configured sites and grab .torrent files to try and cross seed them. Was made in an hour or two as a poc for the code. Super simple, seems to work during basic testing. Ya know, to easily help seed those linux iso's

## torrent creator

> torrentCreator/*

This was something thrown together for someone who asked if they could pick a directory on their network and have it create a .torrent file, upload thumbnail images, create a copy/paste template, etc for later usage. Ya know, to easily upload those linux iso's

## qbt skip rechecking

> qbt-skip-rechecking/*

This was something thrown together for when a drive is remounted/remapped and everything is trying to re-check files when it is not necessary. It will grab everything with `checkingUP` state and remove it from qbt then re-add it with skip_checking set to true. This **will** reset seed time, ratio, etc in qbt but the tradeoff is fine to avoid rechecking hundreds/thousands of torrents for no reason

## qbt find orphans

> qbt-orphaned-data/*

Quick script to check a data directory against qbt and find where data exists but nothing in qbt does, it will rename them so they can be easily found and deleted. It will also run the qbt list against the data directories and see what is in qbt but has no data and add a tag to them.

## qbt notify for errors

> trackerErrors.py

Send a notification using the passthrough integration on Notifiarr when items in qbt are in an error state

## passthrough notification script for Notifiarr

> notifiarr.py

Send json data to the script and it will push them to Notifiarr

## autobrr updater script

> autobrr-updater.py

Quick throw together script so autobrr will auto update on windows
