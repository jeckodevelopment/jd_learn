"""Native Joomla question/quiz forms, subform filtering, CSRF and private answer-key boundaries."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'quiz-deadline-fixture.json').read_text());base='http://127.0.0.1:8093'
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
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,fields=None,expected=200,headers=None,method=None,data=None):
 if fields is not None:data=urlencode(fields).encode()
 req=Request(urljoin(base,path),data=data,headers=headers or {},method=method)
 try:
  with http.open(req,timeout=25) as r:status=r.status;url=r.geturl();body=r.read();result_headers=r.headers
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read();result_headers=error.headers
 if status!=expected:raise RuntimeError('HTTP '+str(status)+' expected '+str(expected)+' for '+path+'; '+re.sub(r'<[^>]+>',' ',body.decode('utf-8',errors='replace'))[-2000:])
 return url,body,result_headers

log=open(root.parent/'quiz-deadline-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
admin=client();guest=client()
def form_page(path,form_id):
 u,b,_=request(admin,path);page=Page(b.decode());return u,next(x for x in page.forms if x['id']==form_id)
try:
 for _ in range(40):
  try:u,b,_=request(admin,'/administrator/index.php');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('HTTP server exited')
   time.sleep(.2)
 page=Page(b.decode());form=next(x for x in page.forms if 'username'in x['fields']and'passwd'in x['fields'])
 request(admin,urljoin(urljoin(u,page.base or ''),form['action']),fields=form['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=quizextend&id='+str(f['course_id'])+'&lesson_id='+str(f['http_lesson'])+'&enrollment_id='+str(f['http_enrollment'])+'&attempt_id='+str(f['http_attempt'])+'&minutes=1'
 u,b,headers=request(admin,path);html=b.decode();form=next(x for x in Page(html).forms if x['id']=='jlms-quiz-extend-form');fields=dict(form['fields'])
 expect('id="jlms-quiz-extend-impact"'in html and fields['jform[minutes]']=='1','Native preview binds the chosen duration')
 expect('no-store'in headers.get('Cache-Control',''),'Private extension preview is not cached')
 fields|={'jform[reason]':'HTTP_PRIVATE_EXTENSION_REASON','jform[confirmation]':'1'}
 request(admin,'/administrator/index.php?option=com_jlms&task=quizextend.apply',expected=405)
 bad={k:v for k,v in fields.items()if not re.fullmatch('[a-f0-9]{32}',k)}
 request(admin,urljoin(u,form['action']),fields=bad,expected=403)
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[confirmation]':'0'},expected=400)
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[minutes]':'2'},expected=409)
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[minutes]':'1.5'},expected=400)
 _,b,_=request(admin,urljoin(u,form['action']),fields=fields)
 expect('HTTP_PRIVATE_EXTENSION_REASON'in b.decode(),'Native form writes authorized extension history')
 _,b,_=request(admin,urljoin(u,form['action']),fields=fields)
 expect(b.decode().count('HTTP_PRIVATE_EXTENSION_REASON')==1,'Identical POST does not extend twice')
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[reason]':'Different reason'},expected=409)
 student=client();u,b,_=request(student,'/index.php?option=com_users&view=login');page=Page(b.decode());login=next(x for x in page.forms if 'username'in x['fields']and'password'in x['fields'])
 request(student,urljoin(urljoin(u,page.base or ''),login['action']),fields=login['fields']|{'username':'cilearnerhttp','password':'Ci-only-Learner-Password-2026'})
 public='/index.php?option=com_jlms&view=quiz&id='+str(f['http_enrollment'])+'&lesson_id='+str(f['http_lesson'])
 _,b,_=request(student,public);html=b.decode();expect('id="jlms-quiz-extension"'in html and 'HTTP_PRIVATE_EXTENSION_REASON'not in html,'Learner sees timing extension without internal reason')
 state='/index.php?option=com_jlms&task=quiz.state&format=json&enrollment_id='+str(f['http_enrollment'])+'&lesson_id='+str(f['http_lesson'])+'&compact=1'
 _,body,_=request(student,state);data=json.loads(body)['data'];expect(data['questions']==[] and data['extension_seconds']==60 and data['time_seconds']==120 and data['attempt']['revision']==1,'Compact authenticated state reflects extended deadline and unchanged answer revision')
 _,guest_body,_=request(guest,state,expected=401)
 request(student,'/index.php?option=com_jlms&task=quizextend.apply&format=json',fields={},expected=404)
 print(json.dumps({'quiz_deadline_http_checks':checks,'status':'passed'}))
finally:
 server.terminate()
 try:server.wait(timeout=10)
 except subprocess.TimeoutExpired:server.kill()
 log.close()
