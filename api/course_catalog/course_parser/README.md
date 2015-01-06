This is just an explanation for the various scripts in this folder

### course_parse.py
Run this to generate the '201403.json' file from the raw data and the bulletin JSONS. This is the file with all of the course and section info.

### bulletin_parse.py
This generates the bulletin file, from both the raw bulletins and the additional information in the bulletin cache

### feeder.php
This is the php file which uses the files in the json folder to output class information in the proper format. Should be used with a rewrite engine to prettify the API.

### law_parse.py
Will move this later. This generates a draft version of the law school course descriptions, based on the information pulled from HTML on their website.

### regex.json
Used by course_parse.py to parse and format the raw course data. The options indicate whether something will be included in the course or section part of a JSON, whether the text will be capitalized, and so on.
