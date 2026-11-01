"""Previewed enrollment suspension/reactivation through native Joomla forms and ACL."""
from pathlib import Path
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import base64,json,os,re,subprocess,sys,time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'enrollment-state-fixture.json').read_text());base='http://127.0.0.1:8093'
config=root/'configuration.php';original=config.read_text();log=open(root.parent/'enrollment-state-http.log','w+');server=None;checks=0
class Page(HTMLParser):
 def __init__(self,text):super().__init__(convert_charrefs=True);self.forms=[];self.form=None;self.links=[];self.ids=[];self.feed(text)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if 'id'in a:self.ids.append(a['id'])
  if tag=='a'and'href'in a:self.links.append(a)
  if tag=='form':self.form={'id':a.get('id',''),'action':a.get('action',''),'fields':{}};self.forms.append(self.form)
  if tag=='input'and self.form is not None and'name'in a:self.form['fields'][a['name']]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.form=None
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,data=None,expected=200):
 try:
  with http.open(urljoin(base,path),data=None if data is None else urlencode(data).encode(),timeout=20)as r:status=r.status;url=r.geturl();headers=r.headers;text=r.read().decode('utf-8')
 except HTTPError as e:status=e.code;url=e.geturl();headers=e.headers;text=e.read().decode('utf-8',errors='replace')
 if status!=expected:raise RuntimeError('HTTP '+str(status)+' expected '+str(expected)+'; '+re.sub('<[^>]+>',' ',text)[-4000:])
 return url,text,headers
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def fixture(mode):return subprocess.check_output(['php','tests/enrollment-state-http-fixture.php',str(root),mode],text=True)
def login(http,name,password,admin=True):
 path='/administrator/index.php'if admin else'/index.php?option=com_users&view=login&lang=en'
 url,html,_=request(http,path);field='passwd'if admin else'password';form=next(x for x in Page(html).forms if'username'in x['fields']and field in x['fields'])
 request(http,urljoin(url,form['action']),form['fields']|{'username':name,field:password})
def preview(http,enrollment=None,course=None,target=None):
 path='/administrator/index.php?option=com_jlms&view=enrollmentstate&id='+str(course or f['course'])+'&enrollment_id='+str(enrollment or f['http_enrollment'])
 if target:path+='&target_status='+target
 url,html,headers=request(http,path);p=Page(html);form=next((x for x in p.forms if x['id']=='jlms-enrollment-state-form'),None)
 return url,html,headers,form
def apply(http,url,form,changes=None,expected=200):
 fields=form['fields']|{'jform[reason]':'PRIVATE_HTTP_STATE_REASON','jform[confirmation]':'1'}|(changes or {})
 return request(http,urljoin(url,form['action']),fields,expected)
try:
 before=json.loads(fixture('snapshot'))
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 guest=client()
 for _ in range(40):
  try:request(guest,'/administrator/index.php');break
  except OSError:time.sleep(.2)
 _,html,_,form=preview(guest);expect(form is None and'jlms-enrollment-state-impact'not in Page(html).ids,'native login protects state preview')
 admin=client();login(admin,'cioperator','Ci-only-Long-Password-2026')
 url,html,headers,form=preview(admin)
 expect(form is not None and'jlms-enrollment-state-impact'in Page(html).ids,'preview and native confirmation form')
 expect('no-store'in headers.get('Cache-Control',''),'private state response')
 expect(form['fields']['jform[target_status]']=='suspended','command has explicit target status')
 expect(json.loads(fixture('snapshot'))==before,'GET preview is read-only')
 fields=form['fields'];tokens=[k for k in fields if re.fullmatch('[a-f0-9]{32}',k)]
 expect(len(tokens)==1,'native Joomla CSRF token')
 bad={'task':'enrollmentstate.apply','jform[course_id]':str(f['course'])}
 request(admin,urljoin(url,form['action']),bad,403)
 request(admin,'/administrator/index.php?option=com_jlms&task=enrollmentstate.apply',expected=405)
 apply(admin,url,form,{'jform[confirmation]':'0'},400)
 apply(admin,url,form,{'jform[reason]':''},422)
 apply(admin,url,form,{'jform[target_status]':'cancelled'},400)
 apply(admin,url,form,{'jform[impact_hash]':'0'*64},409)
 apply(admin,url,form,{'jform[enrollment_id]':str(f['foreign_enrollment'])},404)
 student=client();login(student,'cidashboardother','Ci-only-Dashboard-Password-2026',False)
 resume='/index.php?option=com_jlms&task=enrollment.resume&id='+str(f['http_enrollment'])+'&lang=en'
 request(student,resume)
 _,saved,_=apply(admin,url,form)
 expect('PRIVATE_HTTP_STATE_REASON'in saved and'jlms-enrollment-state-history'in Page(saved).ids,'success shows protected history')
 request(student,resume,expected=403)
 _,history,_=request(student,'/index.php?option=com_jlms&view=dashboard&lang=en')
 expect('PRIVATE_HTTP_STATE_REASON'not in history,'internal reason never reaches learner dashboard')
 suspended=json.loads(fixture('snapshot'));expect(suspended['state']['status']=='suspended'and suspended['history']==before['history']+1,'suspension persisted once')
 apply(admin,url,form)
 expect(json.loads(fixture('snapshot'))==suspended,'duplicate POST does not duplicate event revision or audit')
 apply(admin,url,form,{'jform[reason]':'Changed reason'},409)
 # Preview denial while the backend identity remains logged in and authorized to enter the component.
 manager=client();login(manager,'cirosterreader','Ci-only-Roster-Password-2026')
 mu,mh,_,mf=preview(manager);expect(mf is not None,'course manager can prepare reactivation')
 fixture('revoke')
 request(manager,mu,expected=403);apply(manager,mu,mf,expected=403);fixture('grant')
 # Fresh current preview is required; prior suspension command is not a reactivation toggle.
 ru,rh,_,rf=preview(admin,target='active');expect(rf['fields']['jform[target_status]']=='active','reactivation is explicit')
 apply(admin,ru,rf)
 request(student,resume)
 apply(admin,url,form)
 after=json.loads(fixture('snapshot'))
 expect(after['state']['status']=='active'and after['history']==before['history']+2 and after['audit']==before['audit']+2,'late retry leaves current state and audit intact')
 expect(after['stable']==before['stable']and after['groups']==before['groups'],'state change preserves all learning evidence and native groups')
 expect(int(after['state']['revision'])==int(before['state']['revision'])+2,'exactly two state revisions')
 _,blocked,_,bf=preview(admin,enrollment=f['cancelled'],target='active')
 expect(bf is None and'jlms-enrollment-state-blocked'in Page(blocked).ids,'cancelled history cannot be reactivated by this flow')
 _,roster,_=request(admin,'/administrator/index.php?option=com_jlms&view=enrollments&id='+str(f['course']))
 expect('view=enrollmentstate'in roster,'roster links to scoped management')
 print(json.dumps({'enrollment_state_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-14000:],file=sys.stderr);raise
finally:
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('grant');log.close()
