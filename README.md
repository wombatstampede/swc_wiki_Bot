# SWC Wiki Bot

## PURPOSE:

	Write/Update Wiki Entries of game items for the game:
		SWC
	
	All scripts are PHP CLI.

## INSTALLATION:
* Edit values in: config/swc_wiki_config.php
*Note:*
Without Wiki Bot account, you still can work file-based with the input/output directories.
Perhaps that is a good idea to start with.

* Memory Limit (PHP.INI):
This script reads the json-files into memory. So it may need the memory limit to be lifted.
64MB may be adequate. (tested with 128MB)
	
* edit the most current Manifest Version in tools/get_json.php
retrieve json-data with get_json.php (CLI)

* run swc_wiki_Bot (CLI) to update wiki unit data
I propose that you work with text files first and enable the wiki update
config entries when everything works as expected.

## DIRECTORIES:
* "json"
contains json-data retrieved by the tool "get_json.php"
* "input"
The Bot usually reads contents directly from wiki
but it can read also from text files in the "input" directory.
(these are *.txt files which contain the page source)
* "output"
The Bot can write data as *.txt to this directory.
Useful for testing/debugging.
* "tool"
contains "get_json.php" to retrieve game json databases
contains "swc_wiki_upload.php" to auto-upload png files to the wiki
* "extlib"
external php libraries
* "lib"
own classes/libs
* "png_upload"
png-files to auto-upload with "swc_wiki_upload.php"

## FUNCTION:
* Bot creates/update wiki unit pages with database contents from the game:
Units (Troops, Vehicles, Spaceships)
Buildings
		
* Bot will try to import existing page sources from either the input-dir (text-files) or
directly from the wiki. It will overwrite all template parameters it knows but it'll try to
retain all unknown parameters and other edits. Also pages with unknown template names might be
left unchanged.
	
* For auto-edit please follow these formatting conventions for page content:
```
	Any content (will be left unchanged)
	...
	{{ TemplateName |
	  | parameter1 =
	  ...
	  | parametern =
	  {{ NestedTemplate |  (parameters in nested templates will also be filled in, if any exist)
		| parameter n+1
		...
		| parameter n+m
	  }}
	}}
	Any content
	...
```
Each parameter will only be updated on 1st occurence. (Multiple are prob. invalid anyway)
Missing parameters will be appended inside the main Template
	

