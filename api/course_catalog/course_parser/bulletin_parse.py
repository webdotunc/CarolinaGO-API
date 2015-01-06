#!/usr/bin/python
import re, sys, os, json

subj_find = r"(?<=\n)[A-Z]{3,4}(?=\s\(.+\)\n)|(?<=\n)[A-Z]{3,4}(?=\n)"
rimbles = {}

for path,folders, files in os.walk(os.path.join('json','bulletins','custom_bulletins')):
  for file in files:
    bulletin = json.loads(open(os.path.join(path,file)).read())
    for i in bulletin['subjects']:
      rimbles[i] = bulletin['subjects'][i]
      

for path,folders,files in os.walk(os.path.join('raw','bulletins')):
  for file in files:
    raw = open(os.path.join(path,file)).read();
    raw = re.sub('(?<!\s)\-\s','',raw);
    raw = re.sub('(?<=[a-z])\n+(?=[a-z])|','',raw);
    raw = re.sub('\[.*\]','',raw)
    subjs = re.findall(subj_find, raw)
    areas = re.split(subj_find, raw)
    areas = map(lambda k: re.sub('((?<=See )[A-Z]{3,4})\n([0-9]{2,3}[IHLR]?)','\1 \2',k), areas)
    areas = map(lambda k: re.sub('\.\n(?=[A-Z][a-z ])','\. ',k), areas)
    areas = map(lambda k: re.findall('(?<=\n)[0-9]{2,3}[IHLR]?(?:\s|\n).*\.(?=\s*\n)(?!\s*\n[A-Z][a-z ])|^[0-9]{2,3}[IHLR]?(?:\s|\n).*\.(?=\s*\n)(?!\s*\n[A-Z][a-z])',k), areas)

    fark = True
    for i in range(len(areas)):
      for j in range(len(areas[i])):
        courseNumber = re.search("[0-9]{2,3}[IHLR]?",areas[i][j]).group()
        courseTitle = re.search("(?<=\s)[^\.]*",areas[i][j]).group()
        courseDescription = "";
        try:
          courseDescription = re.search("(?<=\. ).+",areas[i][j]).group()
        except AttributeError:
          courseDescription = ""
        areas[i][j] = {str(courseNumber) : {"title":courseTitle,"description":courseDescription}}
    aller = []
    for i in range(len(subjs)):
      aller.append({subjs[i] : areas[i+1]})

    for i in aller:
      for key in i.keys():
        if key not in rimbles:
          rimbles[key] = {}
        for j in i[key]:
          try:
            for k in j.keys():
              rimbles[key][k] = j[k]
          except AttributeError:
            print "Problem: " + j;

#subjects = re.findall()
#areas = re.findall('(?<=\n)[0-9]?[0-9]{2}[IHLR]?\s.*\.\n|(?<=\n)[A-Z]{3}[A-Z]?(?=\n)',data)
with open(os.path.join('json','bulletins','bulletin.json'), "w") as outfile:
  json.dump({"subjects":rimbles}, outfile, sort_keys=True,indent=4)
