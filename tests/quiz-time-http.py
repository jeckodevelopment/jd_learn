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
f=json.loads((root.parent/'quiz-time-fixture.json').read_text());base='http://127.0.0.1:8093'
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

log=open(root.parent/'quiz-time-http.log','w+')
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
 path='/administrator/index.php?option=com_jlms&view=quiztime&id='+str(f['course_id'])+'&lesson_id='+str(f['lesson_id'])+'&enrollment_id='+str(f['enrollment_id'])
 u,b,headers=request(admin,path);html=b.decode()
 expect('id="jlms-quiz-time-editor"'in html and 'id="jlms-quiz-time-form"'in html and 'id="jlms-quiz-time-active"'in html,'Native management form and immutable active timer notice render')
 expect('PRIVATE_TIME_REASON_REFERENCE'in html,'Authorized management history shows operational reference')
 expect('no-store'in headers.get('Cache-Control',''),'Sensitive management page cannot be cached')
 form=next(x for x in Page(html).forms if x['id']=='jlms-quiz-time-form');fields=dict(form['fields'])
 fields|={'jform[extra_minutes]':'3','jform[reason]':'HTTP operational authorization'}
 request(admin,'/administrator/index.php?option=com_jlms&task=quiztime.save',expected=405)
 bad={k:v for k,v in fields.items()if not re.fullmatch('[a-f0-9]{32}',k)}
 request(admin,urljoin(u,form['action']),fields=bad,expected=403)
 _,b,_=request(admin,urljoin(u,form['action']),fields=fields)
 expect('HTTP operational authorization'in b.decode(),'Native form authorizes and redirects to recorded history')
 _,b,_=request(admin,urljoin(u,form['action']),fields=fields)
 expect(b.decode().count('HTTP operational authorization')==1,'Identical native POST replay adds no second history row')
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[extra_minutes]':'4'},expected=409)
 u,current=form_page(path,'jlms-quiz-time-form')
 valid=dict(current['fields']);valid['jform[reason]']='HTTP validation'
 request(admin,urljoin(u,current['action']),fields=valid|{'jform[extra_minutes]':'1.5'},expected=400)
 request(admin,urljoin(u,current['action']),fields=valid|{'jform[reason]':'<script>alert(1)</script>'},expected=422)
 request(admin,path.replace('enrollment_id='+str(f['enrollment_id']),'enrollment_id=999999'),expected=404)
 # Operational reasons are confined to the authorized backend.
 _,public,_=request(guest,'/index.php?option=com_jlms&view=courses')
 expect('HTTP operational authorization'not in public.decode()and'PRIVATE_TIME_REASON_REFERENCE'not in public.decode(),'Public catalog has no accommodation reasons')
 f['last_revision']+=1;(root.parent/'quiz-time-fixture.json').write_text(json.dumps(f))
 print(json.dumps({'quiz_time_http_checks':checks,'status':'passed'}))
finally:
 server.terminate()
 try:server.wait(timeout=10)
 except subprocess.TimeoutExpired:server.kill()
 log.close()
