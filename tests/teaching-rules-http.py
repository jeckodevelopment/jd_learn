"""Versioned teaching rules through native forms and protected save commands."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'teaching-rules-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.textarea=None;self.links=[];self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='a' and a.get('href'):self.links.append(a['href'])
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
def action(html,name):return next(link for link in Page(html).links if parse_qs(urlparse(link).query).get('item_action')==[name])
def form(html,task):return next(x for x in Page(html).forms if x['fields'].get('task')==task)
log=open(root.parent/'teaching-rules-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def state():return json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('joomla-teaching-rules.php')),str(root),'state'],text=True))

try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['version'])+'&edit_rules=1'
 before=state();_,html=request(path);command=form(html,'curriculum.saveTeachingRules')
 expect('id="jlms-teaching-rules-form"' in html and command['fields']['jform[video_threshold]']=='50.25','Native form shows exact persisted threshold')
 expect(html.count('class="jlms-focused-editor"')==1 and 'id="jlms-module-form"' not in html and 'id="jlms-lesson-form"' not in html,'Rules use one focused editor')
 expect(state()==before,'Opening rules never changes the programme or learners')
 request('/administrator/index.php?option=com_jlms&task=curriculum.saveTeachingRules',expected=405)
 request('/administrator/index.php?option=com_jlms',{'task':'curriculum.saveTeachingRules'},expected=403)
 fields=command['fields']|{'jform[video_threshold]':'82.75'}
 request(command['action'],fields|{'jform[expected_version_revision]':'999999'});expect(state()==before,'Stale rules save is rejected')
 _,html=request(command['action'],fields|{'jform[video_threshold]':'101'})
 expect(state()==before and form(html,'curriculum.saveTeachingRules')['fields']['jform[video_threshold]']=='101','Invalid input remains recoverable without writes')
 _,html=request(command['action'],fields);after=state()
 expect(json.loads(after['version']['rules_json'])['video_threshold_basis_points']==8275,'POST saves exact threshold')
 expect(after['original']==before['original'] and after['learning']==before['learning'] and after['course']==before['course'],'Rule save preserves frozen source, learner history and course state')
 request(command['action'],fields);expect(state()==after,'Stale repeated save cannot mutate newer rules')
 _,html=request(path.replace('version_id='+str(f['version']),'version_id='+str(f['original'])))
 expect('id="jlms-teaching-rules-form"' not in html and '90%' in html,'Historical rules display read-only assigned threshold')
 request(command['action'],fields|{'jform[version_id]':str(f['original']),'jform[expected_version_revision]':before['original']['revision']});expect(state()==after,'Forged historical save is rejected')
 _,overview=request(path.replace('&edit_rules=1',''));transition=form(overview,'curriculum.transition')
 _,review=request(transition['action'],transition['fields'])
 expect('id="jlms-publication-threshold-change"' in review and '50.25%' in review and '82.75%' in review,'Publication review shows exact before/after threshold')
 expect(state()==after,'Review does not publish or change learning')
 # Native course task saves editorial input before opening the working rules editor.
 url,html=request('/administrator/index.php?option=com_jlms&task=course.edit&id='+str(f['course']))
 courseForm=next(x for x in Page(html).forms if 'jform[title]' in x['fields'])
 expect('task="course.programmeRules"' in html and 'jlms-course-teaching-rules' in html,'Course editor offers native save-and-open rules tab')
 url,html=request(urljoin(url,courseForm['action']),courseForm['fields']|{'task':'course.programmeRules','jform[title]':'Saved before rules editor'})
 expect('id="jlms-teaching-rules-form"' in html and 'Saved before rules editor' in html,'Native editorial save opens existing draft rules with saved context')
 expect(state()['learning']==before['learning'],'Save-and-open never migrates learning')
 subprocess.check_call(['php',str(Path(__file__).with_name('joomla-teaching-rules.php')),str(root),'checkpoint'])
 print(json.dumps({'teaching_rules_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
