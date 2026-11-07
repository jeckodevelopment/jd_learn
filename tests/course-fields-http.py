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
f=json.loads((root.parent/'course-fields-fixture.json').read_text());base='http://127.0.0.1:8093'
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
log=open(root.parent/'course-fields-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&task=course.edit&id='+str(f['course'])
 url,html=request(path);editor=next(x for x in Page(html).forms if 'jform[title]' in x['fields'])
 field='jform[com_fields][course-editorial-reference]'
 expect('id="jlms-course-custom-fields"' in html and field in editor['fields'],'Native custom field is visible in its dedicated course tab')
 expect(editor['fields'][field]=='Updated native course value','Existing custom value loaded into native course editor')
 url,html=request(urljoin(url,editor['action']),editor['fields']|{'task':'course.apply',field:'Value saved over HTTP'})
 editor=next(x for x in Page(html).forms if 'jform[title]' in x['fields'])
 expect(editor['fields'][field]=='Value saved over HTTP','Native form submits, saves and reloads custom course value')
 url,html=request(urljoin(url,editor['action']),editor['fields']|{'task':'course.apply','jform[summary]':'x'*501,field:'Unsaved native field correction'})
 editor=next(x for x in Page(html).forms if 'jform[title]' in x['fields'])
 expect(editor['fields'][field]=='Unsaved native field correction','Form validation failure preserves custom field input')
 request(urljoin(url,editor['action']),editor['fields']|{'task':'course.cancel'})
 _,html=request(path);editor=next(x for x in Page(html).forms if 'jform[title]' in x['fields'])
 expect(editor['fields'][field]=='Value saved over HTTP','Cancel leaves the last saved custom value intact')
 request(urljoin(url,editor['action']),editor['fields']|{'task':'course.cancel'})
 print(json.dumps({'course_fields_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-6000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
