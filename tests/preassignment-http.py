"""Explicit manual preassignment, warning and fresh access after external core grants."""
from pathlib import Path
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import base64,json,os,re,subprocess,sys,time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'preassignment-fixture.json').read_text());base='http://127.0.0.1:8093'
config=root/'configuration.php';original=config.read_text();log=open(root.parent/'preassignment-http.log','w+');server=None;checks=0
class Page(HTMLParser):
 def __init__(self,text):super().__init__(convert_charrefs=True);self.forms=[];self.form=None;self.links=[];self.ids=[];self.inputs=[];self.feed(text)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if 'id'in a:self.ids.append(a['id'])
  if tag=='input':self.inputs.append(a)
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
def fixture(mode):return subprocess.check_output(['php','tests/preassignment-http-fixture.php',str(root),mode],text=True)
def login(http,name,password,admin=True):
 path='/administrator/index.php'if admin else'/index.php?option=com_users&view=login&lang=en'
 url,html,_=request(http,path);field='passwd'if admin else'password';form=next(x for x in Page(html).forms if'username'in x['fields']and field in x['fields'])
 request(http,urljoin(url,form['action']),form['fields']|{'username':name,field:password})
def path(user=None,flag=None):
 url='/administrator/index.php?option=com_jlms&view=manualenrollment&id='+str(f['course'])
 fields={'filter[language]':'en-GB'}
 if user is not None:fields['filter[user_id]']=user
 if flag is not None:fields['filter[preassignment]']=flag
 return url+'&'+urlencode(fields)
def preview(http):
 url,html,headers=request(http,path(f['http_user'],'1'));form=next(x for x in Page(html).forms if x['id']=='jlms-manual-form');return url,html,headers,form
def command(form,changes=None):return form['fields']|{'jform[reason]':'PRIVATE_HTTP_PREASSIGN_REASON','jform[confirmation]':'1'}|(changes or {})
def apply(http,url,form,changes=None,expected=200):return request(http,urljoin(url,form['action']),command(form,changes),expected)
try:
 before=json.loads(fixture('snapshot'))
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 guest=client()
 for _ in range(40):
  try:request(guest,'/administrator/index.php');break
  except OSError:time.sleep(.2)
 manager=client();login(manager,'cirosterreader','Ci-only-Roster-Password-2026')
 _,selection,_=request(manager,path());field=next(x for x in Page(selection).inputs if x.get('name')=='filter[preassignment]')
 expect('checked'not in field,'preassignment is an explicit unchecked default')
 request(manager,path(f['http_user']),expected=403)
 request(manager,path(f['http_user'],'true'),expected=400)
 url,html,headers,form=preview(manager);p=Page(html)
 expect('jlms-manual-access-warning'in p.ids,'missing-access warning is rendered')
 expect(form['fields']['jform[preassignment]']=='1','confirmation binds explicit preassignment')
 expect('no-store'in headers.get('Cache-Control',''),'preassignment page is private')
 owned=[x for x in p.ids if x.startswith(('jlms-','filter_','jform_'))];expect(len(owned)==len(set(owned)),'preassignment component/form IDs are unique')
 expect(json.loads(fixture('snapshot'))==before,'GET preview creates no admission data')
 fields=command(form);tokens=[x for x in fields if re.fullmatch('[a-f0-9]{32}',x)]
 expect(len(tokens)==1,'native CSRF token on explicit command')
 no_token={k:v for k,v in fields.items()if k not in tokens};request(manager,urljoin(url,form['action']),no_token,403)
 apply(manager,url,form,{'jform[confirmation]':'0'},400)
 apply(manager,url,form,{'jform[preassignment]':'0'},403)
 apply(manager,url,form,{'jform[preassignment]':'yes'},400)
 malformed=command(form);del malformed['jform[preassignment]'];malformed['jform[preassignment][]']='1';request(manager,urljoin(url,form['action']),malformed,400)
 fixture('revoke_operator');apply(manager,url,form,expected=403);fixture('grant_operator')
 fixture('grant_learner');apply(manager,url,form,expected=409);fixture('revoke_learner')
 expect(json.loads(fixture('snapshot'))==before,'failed intent/CSRF and changed eligibility create no rows')
 _,saved,_=apply(manager,url,form);after=json.loads(fixture('snapshot'))
 expect(len(after['enrollments'])==1 and after['subjects']==1 and after['receipts']==1 and after['audit']==1,'explicit assignment and audit are created once')
 row=after['enrollments'][0];expect(row['status']=='active'and row['origin']=='manual'and int(row['completed_total'])==0,'academic assignment has no alternative entitlement or progress')
 expect('jlms-manual-access-warning'in Page(saved).ids and'jlms-manual-receipt'in Page(saved).ids,'success still warns that access is unavailable')
 apply(manager,url,form);expect(json.loads(fixture('snapshot'))==after,'explicit retry never duplicates assignment or audit')
 student=client();login(student,'cipreassignhttp','Ci-only-Preassign-Password-2026',False)
 dashboard='/index.php?option=com_jlms&view=dashboard&lang=en';resume='/index.php?option=com_jlms&task=enrollment.resume&id='+str(row['id'])+'&lang=en'
 _,history,_=request(student,dashboard)
 expect('Manual admission fixture'in history and'jlms-my-course-resume-'+str(row['id'])not in Page(history).ids,'assigned title visible with no classroom action')
 expect('MANUAL_PRIVATE_BODY'not in history and'PRIVATE_HTTP_PREASSIGN_REASON'not in history,'assignment metadata never exposes lesson or internal reason')
 request(student,resume,expected=403)
 fixture('grant_learner')
 _,history,_=request(student,dashboard)
 expect('jlms-my-course-resume-'+str(row['id'])in Page(history).ids,'same-session external grant exposes resume')
 _,classroom,_=request(student,resume);expect('MANUAL_PRIVATE_BODY'in classroom,'core grant enables same assigned course')
 apply(manager,url,form)
 apply(manager,url,form,{'jform[preassignment]':'0'},409)
 fixture('revoke_learner');request(student,resume,expected=403)
 _,history,_=request(student,dashboard)
 expect('jlms-my-course-resume-'+str(row['id'])not in Page(history).ids and'Manual admission fixture'in history,'same-session revocation retains history and removes action')
 final=json.loads(fixture('snapshot'));expect(final==after and final['groups']==before['groups'],'grant/revoke and GET navigation preserve assignment, progress and native groups')
 print(json.dumps({'preassignment_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-14000:],file=sys.stderr);raise
finally:
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('restore');log.close()
