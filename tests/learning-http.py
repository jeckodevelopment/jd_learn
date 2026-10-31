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
fixture=json.loads((root.parent/'learning-fixture.json').read_text())
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
log=open(root.parent/'learning-http.log','w+')
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
 else:raise RuntimeError('HTTP server not ready')
 course='/index.php?option=com_jlms&view=course&id='+str(fixture['course_id'])
 _,html=request(guest,course);expect('LEARNING_PRIVATE_BODY_' not in html,'Public sheet contains no lesson bodies')
 request(guest,'/index.php?option=com_jlms&task=enrollment.start',expected=405)
 student=client();login(student,'cilearnerhttp')
 url,html=request(student,course);page=Page(html);form=next((f for f in page.forms if f['id']=='jlms-start-form'),None)
 expect(form is not None,'Authorised student sees start form')
 fields=dict(form['fields']);bad=dict(fields)
 for key in list(bad):
  if re.fullmatch('[a-f0-9]{32}',key):bad.pop(key)
 request(student,urljoin(url,form['action']),bad,expected=403)
 classroom_url,html=request(student,urljoin(url,form['action']),fields)
 expect('id="jlms-classroom"' in html and 'LEARNING_PRIVATE_BODY_1' in html,'Start opens protected text classroom')
 expect('value="0"' in html and 'noindex, nofollow' in html,'Classroom starts at zero and is not indexable')
 enrollment=int(re.search(r'data-enrollment-id="([1-9][0-9]*)"',html).group(1))
 direct='/index.php?option=com_jlms&view=classroom&id='+str(enrollment)
 request(guest,direct,expected=401)
 again_url,again=request(student,urljoin(url,form['action']),fields)
 expect(int(re.search(r'data-enrollment-id="([1-9][0-9]*)"',again).group(1))==enrollment,'Repeated start uses same enrollment')
 page=Page(html);completion=next(f for f in page.forms if f['id']=='jlms-complete-form')
 done_fields=dict(completion['fields'])
 _,done=request(student,urljoin(classroom_url,completion['action']),done_fields)
 expect('value="33"' in done,'First required lesson persists 33 percent')
 _,done=request(student,urljoin(classroom_url,completion['action']),done_fields)
 expect('value="33"' in done,'Repeated completion does not double count')
 # Change group membership outside the web process, keeping the same authenticated cookie/session.
 subprocess.run(['php',str(Path(__file__).with_name('joomla-learning.php')),str(root),'revoke',str(fixture['http_user_id'])],check=True,capture_output=True)
 request(student,direct,expected=403)
 request(student,urljoin(classroom_url,completion['action']),done_fields,expected=403)
 subprocess.run(['php',str(Path(__file__).with_name('joomla-learning.php')),str(root),'grant',str(fixture['http_user_id'])],check=True,capture_output=True)
 _,resumed=request(student,direct)
 expect('value="33"' in resumed,'Restored group resumes existing progress with same session')
 other=client();login(other,'cilearnerother');request(other,direct,expected=404)
 for lesson in fixture['lessons'][1:3]:
  u,h=request(student,direct+'&lesson_id='+str(lesson));form=next(f for f in Page(h).forms if f['id']=='jlms-complete-form')
  _,done=request(student,urljoin(u,form['action']),form['fields'])
 expect('id="jlms-course-completed"' in done and 'value="100"' in done,'All required lessons complete the course')
 print(json.dumps({'learning_http_checks':checks,'status':'passed','group_revocation_same_session':'passed','ownership':'passed','csrf':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-6000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
