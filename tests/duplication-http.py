"""Native duplication controls, CSRF, immutable source and independently editable results."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'duplication-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['radio','checkbox'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='select':self.select=a.get('name') if self.current is not None else None
  if tag=='option' and self.select and self.current is not None:
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='select':self.select=None
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
log=open(root.parent/'duplication-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['version'])
 url,html=request(path);module=form(html,'module.duplicate');lesson=form(html,'lesson.duplicate')
 expect('jform[title]' in module['fields'] and 'jform[module_id]' in lesson['fields'],'Module and lesson duplication have explicit title and destination controls')
 for task in ['module.duplicate','lesson.duplicate','curriculum.duplicateCourse']:
  request('/administrator/index.php?option=com_jlms&task='+task,expected=405)
  request(urljoin(url,module['action']),{'task':task},expected=403)
 fields=module['fields']|{'jform[title]':'Module duplicated over HTTP'}
 _,html=request(urljoin(url,module['action']),fields)
 expect('Module duplicated over HTTP' in html,'Module command creates its independent copy through MVC')
 # Replaying the stale revision must not add another module.
 _,html=request(urljoin(url,module['action']),fields)
 expect(html.count('<h2>Module duplicated over HTTP</h2>')==1,'Stale duplicate request creates no additional module')
 lesson=form(html,'lesson.duplicate');fields=lesson['fields']|{'jform[title]':'Lesson duplicated over HTTP'}
 _,html=request(urljoin(url,lesson['action']),fields)
 expect('Lesson duplicated over HTTP' in html,'Lesson command creates its independent copy through MVC')
 source='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['source'])+'&version_id='+str(f['source_version'])
 url,html=request(source)
 expect(not any(x['fields'].get('task') in ['module.duplicate','lesson.duplicate'] for x in Page(html).forms),'Published source exposes no in-place module or lesson duplication')
 course=form(html,'curriculum.duplicateCourse');fields=course['fields']|{'jform[title]':'Course duplicated over HTTP'}
 result,html=request(urljoin(url,course['action']),fields)
 copied=int(parse_qs(urlparse(result).query)['id'][0])
 expect(copied not in [f['source'],f['course']] and 'Course duplicated over HTTP' in html,'Course command redirects to a new course identity')
 expect(any(x['fields'].get('task')=='module.duplicate' for x in Page(html).forms),'New copied programme is editable immediately')
 _,public=request('/index.php?option=com_jlms&view=courses')
 expect('Course duplicated over HTTP' not in public,'Duplicated course remains unpublished')
 print(json.dumps({'duplication_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
