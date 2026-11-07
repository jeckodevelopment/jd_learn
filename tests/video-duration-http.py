"""Automatic metadata receipts and lesson saves through native Joomla HTTP forms."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'video-duration-fixture.json').read_text());base='http://127.0.0.1:8093'
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
log=open(root.parent/'video-duration-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def state():return json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('joomla-video-duration.php')),str(root),'state'],text=True))

try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['version'])+'&edit_lesson='+str(f['lesson'])
 def editor():
  _,html=request(path);return html,form(html,'lesson.save')
 before=state();html,command=editor();fields=command['fields']
 expect('id="jlms-video-metadata"' in html and fields['jform[duration_mode]']=='auto','Native lesson defaults to automatic duration with preview region')
 expect('type="checkbox" name="jform[duration_confirmed]"' not in html,'No routine manual duration confirmation checkbox')
 request('/administrator/index.php?option=com_jlms&task=lesson.metadata&format=json',expected=405)
 request('/administrator/index.php?option=com_jlms&format=json',{'task':'lesson.metadata'},expected=403)
 def metadata(values,expected=200):
  _,body=request('/administrator/index.php?option=com_jlms&format=json',values|{'task':'lesson.metadata'},expected);return json.loads(body)
 metadata(fields|{'jform[source_reference]':'https://evil.example/private'},422)
 metadata(fields|{'jform[expected_version_revision]':'999999'},409)
 metadata(fields|{'jform[version_id]':str(f['foreign_version'])},404)
 proof=metadata(fields)['data'];expect(proof['duration_ms'] is None and len(proof['ticket'])==64,'Authoring endpoint issues source-bound SDK receipt without a student session')
 expect(state()==before,'Metadata reads do not mutate programme or learning')
 values=fields|{'jform[duration_ticket]':proof['ticket'],'jform[sdk_duration_ms]':'13333'}
 request(command['action'],values);after=state();row=next(l for l in after['lessons'] if int(l['id'])==f['lesson'])
 expect(int(row['duration_ms'])==13333 and json.loads(row['params_json'])['video_duration_origin']=='author_sdk','Automatic SDK measurement saves without manual seconds or checkbox')
 expect(after['enrollments']==before['enrollments'] and after['progress']==before['progress'],'Measured draft does not change assigned learning')
 request(command['action'],values);expect(state()==after,'Consumed or stale receipt cannot be reused')
 _,command=editor();fields=command['fields'];proof=metadata(fields)['data']
 request(command['action'],fields|{'jform[duration_ticket]':proof['ticket'],'jform[sdk_duration_ms]':'10000','jform[source_reference]':'aaaaaaaaaaa'})
 expect(state()==after,'Measurement of one source cannot be applied to another')
 _,command=editor();fields=command['fields']
 request(command['action'],fields|{'jform[source_reference]':'aaaaaaaaaaa','jform[duration_ticket]':'','jform[sdk_duration_ms]':''});incomplete=state();row=next(l for l in incomplete['lessons'] if int(l['id'])==f['lesson'])
 expect(row['duration_ms'] is None,'Changed source can be saved as incomplete draft without invented duration')
 _,command=editor();fields=command['fields'];request(command['action'],fields|{'jform[duration_mode]':'manual','jform[duration_seconds]':'14.5','jform[duration_confirmed]':'0'})
 final=state();row=next(l for l in final['lessons'] if int(l['id'])==f['lesson'])
 expect(int(row['duration_ms'])==14500 and json.loads(row['params_json'])['video_duration_origin']=='declared','Explicit manual fallback works without the removed confirmation step')
 expect(final['enrollments']==before['enrollments'] and final['progress']==before['progress'],'Fallback also preserves learning history')
 subprocess.check_call(['php',str(Path(__file__).with_name('joomla-video-duration.php')),str(root),'checkpoint'])
 print(json.dumps({'video_duration_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
