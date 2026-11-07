"""Native article-style course list, Search Tools, selected actions and compact editor."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'courses-ui-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.textarea=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['radio','checkbox'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='textarea':
   self.textarea=a.get('name') if self.current is not None and 'disabled' not in a else None
   if self.textarea:self.current['fields'][self.textarea]=''
  if tag=='select':self.select=a.get('name') if self.current is not None and 'disabled' not in a else None
  if tag=='option' and self.select and self.current is not None:
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='select':self.select=None
  if tag=='textarea':self.textarea=None
 def handle_data(self,data):
  if self.textarea and self.current is not None:self.current['fields'][self.textarea]+=data
client=build_opener(HTTPCookieProcessor(CookieJar()))
def request(path,fields=None,expected=200):
 try:
  with client.open(Request(urljoin(base,path),data=None if fields is None else urlencode(fields).encode()),timeout=25) as response:status=response.status;url=response.geturl();body=response.read().decode()
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode()
 if status!=expected:raise RuntimeError(f'HTTP {status}, expected {expected}: '+re.sub('<[^>]+>',' ',body)[-3000:])
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def form(html,task):return next(x for x in Page(html).forms if x['fields'].get('task')==task)
log=open(root.parent/'courses-ui-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=courses'
 def listing(filters=None,order='a.title ASC',limit='20',start='0'):
  fields={'filter[search]':'List UI','filter[published]':'','filter[category_id]':'','filter[access]':'','filter[author_id]':'','filter[language]':'','filter[checked_out]':'','list[fullordering]':order,'list[limit]':limit,'limitstart':start}
  fields.update(filters or {})
  return request(path,fields)
 def visible(html):return [int(x) for x in re.findall(r'data-course-id="([0-9]+)"',html)]
 url,html=listing()
 expect(visible(html)==[f['courses']['Alpha'],f['courses']['Bravo'],f['courses']['Zebra']],'Search Tools title ordering renders only matching courses')
 page=Page(html);admin_form=next(x for x in page.forms if 'boxchecked' in x['fields'])
 expect(all(name in admin_form['fields'] for name in ['filter[search]','filter[published]','filter[category_id]','filter[access]','filter[author_id]','filter[language]','list[fullordering]','list[limit]']),'Native search, filters, sort and limit fields are present')
 expect('joomla-toolbar-button' in html and 'courses.programme' in html and 'course.edit' in html,'Native selection toolbar exposes edit and programme')
 expect('LIST_UI_PRIVATE_LESSON_BODY' not in html and 'COM_JLMS_FOUNDATION_NOTICE' not in html,'List excludes lesson content and development warning')
 _,filtered=listing({'filter[published]':'1'});expect(visible(filtered)==[f['courses']['Alpha']],'Published filter over HTTP')
 _,persisted=request(path);expect(visible(persisted)==[f['courses']['Alpha']],'Native list filters persist on return')
 _,empty=listing({'filter[search]':'No course matches this text'});expect('id="jlms-courses-empty"' in empty and not visible(empty),'Empty list keeps native Search Tools available')
 _,paged=listing(limit='1');first=visible(paged);expect(len(first)==1,'Native pagination applies requested page size')
 _,page2=listing(limit='1',start='1');expect(len(visible(page2))==1 and visible(page2)!=first,'Pagination reaches the next matching course')
 _,reset=listing({'filter[published]':'1'},limit='1',start='50');expect(visible(reset)==[f['courses']['Alpha']],'Changing filters resets pagination to the first page')
 url,html=listing();admin_form=next(x for x in Page(html).forms if 'boxchecked' in x['fields']);token=next(k for k in admin_form['fields'] if re.fullmatch('[a-f0-9]{32}',k))
 request('/administrator/index.php?option=com_jlms&task=courses.programme',expected=405)
 request(path,{'task':'courses.programme','cid[]':str(f['courses']['Alpha'])},expected=403)
 request(path,{'task':'courses.programme',token:'1'},expected=422)
 request(path,{'task':'courses.programme',token:'1','cid[]':'malformed'},expected=422)
 dest,programme=request(path,{'task':'courses.programme',token:'1','cid[]':str(f['courses']['Alpha'])})
 expect(parse_qs(urlparse(dest).query).get('view')==['curriculum'] and 'List UI module' in programme,'Selected Programme action opens the authorized course')
 edit='/administrator/index.php?option=com_jlms&task=course.edit&id='+str(f['courses']['Alpha'])
 _,editor=request(edit)
 expect(all('id="'+x+'"' in editor for x in ['jlms-course-editor-layout','jlms-course-editor-sidebar','jlms-course-general','jlms-course-programme','jlms-course-access-publication','jlms-course-access-settings','jlms-course-calendar','jlms-course-image-fields','jlms-course-teachers','jlms-course-teaching-rules','jlms-course-options']),'Core course fields are grouped into five focused editor tabs')
 expect('jlms-course-images' not in editor and 'jlms-course-teachers-settings' not in editor,'Separate image and teacher tabs are removed')
 expect(all(editor.count('name="jform['+x+']"')==1 for x in ['title','content_access','catalog_access','enrollment_mode','presentation_description','presentation_image','teacher_ids[]']),'Core fields are rendered once across the grouped form')
 expect('id="jlms-course-programme-summary"' in editor and 'List UI module' in editor,'Editor Programme tab summarizes the saved outline')
 expect('COM_JLMS_COURSE_PROGRAMME_HELP' not in editor and 'COM_JLMS_COURSES_SEARCH' not in html,'New UI labels are translated')
 # One native save creates a course and opens its programme without losing the submitted description.
 new_url,new_html=request('/administrator/index.php?option=com_jlms&task=course.add')
 new_form=next(x for x in Page(new_html).forms if 'jform[title]' in x['fields'])
 new_fields=new_form['fields']|{'task':'course.programme','jform[title]':'Saved directly into programme','jform[alias]':'saved-into-programme','jform[catid]':str(f['categories']['parent']),'jform[language]':'*','jform[presentation_description]':'<p>Preserved on opening programme</p>','jform[catalog_access]':'1','jform[content_access]':'2','jform[enrollment_mode]':'auto'}
 saved_url,saved_html=request(urljoin(new_url,new_form['action']),new_fields)
 expect(parse_qs(urlparse(saved_url).query).get('view')==['curriculum'] and 'Saved directly into programme' in saved_html and 'id="jlms-module-form"' in saved_html and 'id="jlms-version-form"' not in saved_html,'Native save-and-open creates the course and reaches its programme')
 new_id=int(parse_qs(urlparse(saved_url).query)['id'][0])
 _,reopened=request('/administrator/index.php?option=com_jlms&task=course.edit&id='+str(new_id))
 expect('Preserved on opening programme' in reopened,'Save-and-open preserves submitted course content')
 # Restricted editor: native backend login in a fresh session.
 client=build_opener(HTTPCookieProcessor(CookieJar()))
 url,login_html=request('/administrator/index.php');page=Page(login_html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cieditor','passwd':'Ci-only-Editor-Password-2026'})
 _,limited=request(path);expect('List UI Alpha' not in limited,'Restricted list never exposes the uneditable course')
 limited_form=next(x for x in Page(limited).forms if 'boxchecked' in x['fields']);limited_token=next(k for k in limited_form['fields'] if re.fullmatch('[a-f0-9]{32}',k))
 request(path,{'task':'courses.programme',limited_token:'1','cid[]':str(f['courses']['Alpha'])},expected=403)
 print(json.dumps({'courses_ui_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
