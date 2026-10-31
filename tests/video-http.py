"""Real Joomla frontend identity, CSRF, ownership and mid-session group revocation."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
fixture=json.loads((root.parent/'video-fixture.json').read_text())
base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'id':a.get('id',''),'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a:self.current['fields'][a['name']]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
log=open(root.parent/'video-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,fields=None,expected=200):
 try:
  with http.open(urljoin(base,path),data=None if fields is None else urlencode(fields).encode(),timeout=20) as r:status=r.status;url=r.geturl();body=r.read().decode('utf-8')
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode('utf-8',errors='replace')
 if status!=expected:raise RuntimeError('HTTP '+str(status)+' expected '+str(expected)+' for '+path+'; '+re.sub(r'<[^>]+>',' ',body)[-3000:])
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1

def login(http,username):
 url,html=request(http,'/index.php?option=com_users&view=login')
 page=Page(html);form=next((f for f in page.forms if 'username' in f['fields'] and 'password' in f['fields']),None)
 expect(form is not None,'Native frontend login form is available')
 fields=dict(form['fields']);fields.update(username=username,password='Ci-only-Learner-Password-2026')
 return request(http,urljoin(urljoin(url,page.base or ''),form['action']),fields)
try:
 guest=client()
 for i in range(40):
  try:request(guest,'/index.php?option=com_jlms&view=courses');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('HTTP server exited')
   time.sleep(.2)
 student=client();login(student,'cilearner')
 direct='/index.php?option=com_jlms&view=classroom&id='+str(fixture['enrollment_id'])+'&lesson_id='+str(fixture['lesson_id'])
 _,html=request(student,direct)
 expect('id="jlms-video-region"' in html and 'id="jlms-complete-form"' not in html,'Video classroom has player region and no manual completion')
 expect('dQw4w9WgXcQ' not in html and 'youtube.com/iframe_api' not in html,'Classroom HTML contains no media reference or remote SDK')
 options=None
 for script in re.findall(r'<script[^>]*>(.*?)</script>',html,re.S):
  try:
   data=json.loads(script)
   if isinstance(data,dict) and 'com_jlms.video' in data:options=data['com_jlms.video'];break
  except json.JSONDecodeError:pass
 expect(options is not None,'Native Joomla script options contain safe player settings')
 fields={'enrollment_id':fixture['enrollment_id'],'lesson_id':fixture['lesson_id'],'consent':'1',options['token']:'1'}
 request(student,options['urls']['playback'],expected=405)
 bad=dict(fields);bad.pop(options['token']);request(student,options['urls']['playback'],bad,expected=403)
 bad=dict(fields);bad['consent']='0';request(student,options['urls']['playback'],bad,expected=403)
 _,body=request(student,options['urls']['playback'],fields);grant=json.loads(body)['data']
 expect(grant['provider']=='youtube' and grant['options']['reference']=='dQw4w9WgXcQ','Authorised consent POST returns safe playback descriptor')
 other=client();login(other,'cilearnerother')
 request(other,options['urls']['playback'],fields,expected=403) # CSRF is bound to the authenticated session.
 save=fields|{'session_id':grant['session_id'],'source_revision':grant['source_revision'],'sequence':'1','duration_ms':'10000','events_json':'[]'}
 request(student,options['urls']['save'],save,expected=422)
 save['events_json']=json.dumps([{'id':1,'type':'ended','position_ms':10000,'client_ms':0,'rate_milli':1000,'playing':False}])
 _,body=request(student,options['urls']['save'],save);result=json.loads(body)['data']
 expect(result['covered_ms']==9000,'Ended alone adds no coverage to previous 90 percent')
 _,body=request(student,options['urls']['save'],save);expect(json.loads(body)['data']['replay'],'HTTP replay is idempotent')
 bad=save|{'source_revision':grant['source_revision']+1};request(student,options['urls']['save'],bad,expected=409)
 bad=save|{'events_json':'x'*65537};request(student,options['urls']['save'],bad,expected=413)
 _,body=request(student,options['urls']['close'],fields|{'session_id':grant['session_id']});expect(json.loads(body)['data']['closed'],'Explicit player close expires session')
 request(student,options['urls']['save'],save,expected=409)
 print(json.dumps({'video_http_checks':checks,'status':'passed','real_browser_sdk_playback':False}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-6000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
