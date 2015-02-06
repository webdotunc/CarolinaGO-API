CarolinaGO API
==============

This is the API for CarolinaGO (https://m.unc.edu/)

####PHP

The PHP files are calendar makers that use an old version of Eluceo's iCal
maker (https://github.com/eluceo/iCal) to integrate with UNC's dining website
and Wordpress XML calendars to generate iCal files. These use either regular
expressions or DOM to parse the pages.

#### PYTHON

These are various Python scripts used for the Carolina Go mobile application.

### course_parser

Used to output the text from the PDF of UNC's courses
(http://registrar.unc.edu/courses/schedule-of-classes/directory-of-classes-2/)
into a JSON file. Two other JSON files, areas.json and terms.json, are
necessary for the course catalog of the mobile app but are not created from the
Python script. To run course_parse.py, run "python course_parse.py" and it will
look through the given folder structure.

### bulletin_parse

In course_parser, but a sort of different purpose. Creates a JSON of all
courses and their given descriptions from the undergraduate and graduate
bulletins.

### icon_resizer

Used to format the icons used in this app to the various required sizes.
Optionally, it gives a clear border to the output icons as well. It is run
using the terminal command "python icon_resizer.py icon_name
border_percentage", with border_percentage being the percent of the total
resulting icon's width that the border will be — so, 5 is a border that is 5
percent of the total icon width.
