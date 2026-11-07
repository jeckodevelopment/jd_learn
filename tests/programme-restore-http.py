"""Historical programme recovery through native Joomla forms and protected commands."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'programme-restore-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.links=[];self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='a' and a.get('href'):self.links.append(a['href'])
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
def action(html,name):return next(link for link in Page(html).links if parse_qs(urlparse(link).query).get('item_action')==[name])
def form(html,task):return next(x for x in Page(html).forms if x['fields'].get('task')==task)
log=open(root.parent/'programme-restore-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def state():return json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('joomla-programme-restore.php')),str(root),'state'],text=True))

try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['historical'])
 before=state();_,html=request(path);command=form(html,'curriculum.restoreVersion')
 expect('id="jlms-restore-programme"' in html,'Historical programme exposes native recovery action')
 expect(not any(x['fields'].get('task')=='curriculum.createVersion' for x in Page(html).forms),'Historical view does not offer misleading current-version copy')
 expect(state()==before,'Viewing historical programme performs no recovery')
 request('/administrator/index.php?option=com_jlms&task=curriculum.restoreVersion',expected=405)
 request('/administrator/index.php?option=com_jlms',{'task':'curriculum.restoreVersion'},expected=403)
 request(command['action'],command['fields']);expect(state()==before,'Server requires explicit recovery confirmation')
 fields=command['fields']|{'jform[confirmation]':'1'}
 request(command['action'],fields|{'jform[expected_version_revision]':'999999'})
 expect(state()==before,'Stale historical source cannot be copied')
 request(command['action'],fields|{'jform[version_id]':str(f['current'])})
 expect(state()==before,'Forged active version cannot bypass source guard')
 url,html=request(command['action'],fields);after=state()
 expect(len(after['versions'])==len(before['versions'])+1,'Recovery creates exactly one independent draft')
 expect(after['versions'][:-1]==before['versions'],'All previously existing versions and drafts are preserved')
 expect(after['versions'][-1]['version']['status']=='draft','Recovered version opens as an editable draft')
 expect(after['course']['current_version_id']==before['course']['current_version_id'] and after['course']['state']==before['course']['state'],'Recovery leaves current publication unchanged')
 expect(after['learning']==before['learning'],'Recovery never changes enrollments, attempts or progress')
 expect(len(after['audit'])==len(before['audit'])+1,'Native audit records recovery')
 expect(len([l for l in after['versions'][-1]['lessons'] if l['kind']=='video'])==3,'Recovered draft retains all three external provider lessons')
 request(command['action'],fields);expect(state()==after,'Replaying stale recovery form creates no additional draft')
 subprocess.check_call(['php',str(Path(__file__).with_name('joomla-programme-restore.php')),str(root),'checkpoint'])
 print(json.dumps({'programme_restore_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
