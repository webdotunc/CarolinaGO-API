#!/usr/bin/python
import re
import sys
import yaml, json
import random
import os

# Copy and paste everything from that UNC courses PDF into a text file. The, input it into this script as the first argument. This will try
# to parse that into some kind of python array by attempting to split it into different courses and using regular expressions to extract details
# from each of those array elements. Still a work in progress.

# To use, call "python course_parse.py <input_fall_courses_text> <output_file>"

delimiter = "_"*156

subparse = re.compile('(?<![A-Za-z0-9])[A-Z]{3,4}\s*[0-9]{2,3}[A-Z]?') #Used to split the raw into course sections

fields = yaml.load(open(os.path.join('json','regex.json')).read())['regex'] #Yaml means that regex strings are read as strings, not unicode


def decode_section(required_fields, raw): #Decodes an array of different sections into course sections, using regular expressions
  section = {}
  for field in fields:
    regex_result = re.search(field['find'],raw)
    if regex_result:
      section[field['field']] = re.sub(field['delete'],field['substitute'], regex_result.group())
    else:
      if field['required']:
        return None
      else:
        section[field['field']] = ''
    if field['capitalize']:
      section[field['field']] = str.title(section[field['field']])
  section['schedule'] = '%s %s'%(section['days'],section['time']) if section['days']!='' and section['time']!='' else ''
  return section  

def course_comp(first,second):
  return first['subject'] == second['subject'] and first['courseNumber'] == second['courseNumber']

def merge_sections(courses, bulletin): # Merges these course sections, taking descriptions and titles from the bulletin JSON where applicable
  newmerge = []
  record = {}
  for course in courses:
    id = '%s%s'%(course['subject'],course['courseNumber'])
    if id not in record:
      record[id]=1
      temp_course = {}
      for field in fields:
        if field['course'] == 1:
          temp_course[field['field']] = course[field['field']]
      temp_course['sections'] = []
      try:
        clean_course_num = re.sub(r'([0-9]+)[A-Z]*',r'\1',course['courseNumber'])
        temp_course['title'] = bulletin['subjects'][course['subject']][clean_course_num]['title']
        temp_course['description'] = bulletin['subjects'][course['subject']][clean_course_num]['description']
      except KeyError:
        pass
      newmerge.append(temp_course)
    for j in newmerge:
      if course_comp(course,j):
        temp_section = {}
        for field in fields:
          if field['section'] == 1:
            temp_section[field['field']] = course[field['field']]
        temp_section['schedule'] = course['schedule']
        temp_section['crn'] = str(random.randrange(999999999));
        temp_section['courseSection'] = course['courseSection'] if course['courseSection'] else "1" if len(j['sections']) == 0 else str(int(j['sections'][-1]['courseSection']) + 1) 
        j['sections'].append(temp_section)
  return newmerge;

def process_raw(raw): #This fixes recurring problems with phrases in the raw text and then splits it into sections
  raw = raw.replace('BuilRoom','Building Room')
  raw = raw.replace(' Jr,',',')
  raw = raw.replace('Fred Brooks Hall at','Fred Brooks Hall')
  raw = re.sub('(?<=\w)Lecture',' Lecture',raw)
  raw = raw.replace(delimiter, '')
  titles = subparse.findall(raw)
  info = subparse.split(raw)
  for i in range(len(titles)):
    titles[i] = titles[i] + info[i+1]
  return titles
  

if __name__ == '__main__':
  for path,folders,files in os.walk(os.path.join('raw','courses')):
    for file in files:
      with open (os.path.join(path,file)) as f:
        raw = process_raw(f.read())
        
        sections = map((lambda k: decode_section(fields, k)),raw)
        sections = filter((lambda k: k != None),sections)

        bulletin = json.loads(open(os.path.join('json','bulletins','bulletin.json')).read())
        courses = merge_sections(sections, bulletin)
        
        with open(os.path.join('..','201403.json'), 'w') as outfile:
          json.dump({'courses':courses}, outfile, sort_keys=True,indent=4,encoding='latin1')
