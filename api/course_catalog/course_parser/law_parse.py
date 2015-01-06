import re, os, json

docs = re.compile('<!DOCTYPE html>')
title = re.compile('(?<=<h1 id="ctl00_ctl00_cphContent_ctlMainHead">)[^<]*(?=</h1>)');
course_title = re.compile('(?<=<span id="ctl00_ctl00_cphContent_cphContent1_lblCourseNumber">)[^<]*(?=</span>)');
#description = re.compile('(?<=Description)[^$]*</p>');
#description = re.compile('(?<=Description:).*(?=\<\/\p\>)');
description = re.compile(r"<div>\s*<label>\s*Description:.*</div>");
tagout = re.compile('');
for path, folders, files in os.walk(os.path.join('raw','bulletin_cache')):
  for file in filter(lambda k: k[0] != '.', files):
    print file
    with open(os.path.join(path, file)) as raw:
      raw = raw.read()
      raw = raw.replace('\r\n','')
      raw = re.sub(' +',' ',raw)
      raw = re.sub('\t','',raw)
      raw = re.sub('&amp;','&',raw)
      docs = docs.split(raw)
      
      courses = map(lambda k: {"title": title.findall(k),"description": description.findall(k), "course":course_title.findall(k)},docs)
      courses = filter(lambda k: len(k["description"]) != None, courses)
      courses = filter(lambda k: len(k["title"]) > 0 and len(k["description"]) > 0, courses)
      for k in courses:
        for i in range(len(k["description"])):
          k["description"][i] = re.sub('</div>.*','',k["description"][i])
          k["description"][i] = re.sub('<[^>]*>','',k["description"][i])
          k["description"][i] = re.sub(' Description: ','',k["description"][i])
        for i in range(len(k["course"])):
          k["course"][i] = re.sub('Law ','',k['course'][i])
      lawformat = {"LAW":{}}
      for k in courses:
        lawformat["LAW"][k["course"][0]] = {"title":k["title"][0],"description":k["description"][0]}
      print len(courses)
      with open(os.path.join("json","lawbulletin.json"), "w") as outfile:
          json.dump({"subjects":lawformat}, outfile, sort_keys=True,indent=4)
