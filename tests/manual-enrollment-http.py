"""Previewed single-user manual admission through native Joomla forms and fresh ACL."""
from pathlib import Path
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import base64,json,os,re,subprocess,sys,time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'manual-enrollment-fixture.json').read_text());base='http://127.0.0.1:8093'
config=root/'configuration.php';original=config.read_text();log=open(root.parent/'manual-enrollment-http.log','w+');server=None;checks=0
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
def fixture(mode):return subprocess.check_output(['php','tests/manual-enrollment-http-fixture.php',str(root),mode],text=True)
def login(http,name,password,admin=True):
 path='/administrator/index.php'if admin else'/index.php?option=com_users&view=login&lang=en'
 url,html,_=request(http,path);field='passwd'if admin else'password';form=next(x for x in Page(html).forms if'username'in x['fields']and field in x['fields'])
 request(http,urljoin(url,form['action']),form['fields']|{'username':name,field:password})
def preview(http,user=None):
 path='/administrator/index.php?option=com_jlms&view=manualenrollment&id='+str(f['course'])
 if user is not None:path+='&'+urlencode({'filter[user_id]':user,'filter[language]':'en-GB'})
 url,html,headers=request(http,path);form=next((x for x in Page(html).forms if x['id']=='jlms-manual-form'),None)
 return url,html,headers,form
def apply(http,url,form,changes=None,expected=200):
 data=form['fields']|{'jform[reason]':'PRIVATE_HTTP_MANUAL_REASON','jform[confirmation]':'1'}|(changes or {})
 return request(http,urljoin(url,form['action']),data,expected)
try:
 before=json.loads(fixture('snapshot'))
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 guest=client()
 for _ in range(40):
  try:request(guest,'/administrator/index.php');break
  except OSError:time.sleep(.2)
 _,html,_,form=preview(guest)
 expect(form is None and'jlms-manual-selection'not in Page(html).ids,'native backend login protects admission')
 manager=client();login(manager,'cirosterreader','Ci-only-Roster-Password-2026')
 _,selection,_,sf=preview(manager)
 expect('jlms-manual-selection'in Page(selection).ids and sf is None,'native selection form without global user directory')
 url,html,headers,form=preview(manager,f['http_user']);p=Page(html)
 expect(form is not None and'jlms-manual-impact'in p.ids,'authorized preview with confirmation form')
 expect('filter[language]'in html and'en-GB'in html,'native installed site language selector')
 expect('no-store'in headers.get('Cache-Control',''),'private no-store admission response')
 owned=[x for x in p.ids if x.startswith(('jlms-','filter_','jform_'))]
 expect(len(owned)==len(set(owned)),'unique component and form IDs')
 expect(json.loads(fixture('snapshot'))==before,'preview creates no subject enrollment receipt or audit')
 for value in ['MANUAL_PRIVATE_BODY','rules_json','claim_token','request_hash','@example.invalid']:
  expect(value not in html,'minimal preview excludes '+value)
 request(manager,'/administrator/index.php?option=com_jlms&task=manualenrollment.apply',expected=405)
 request(manager,urljoin(url,form['action']),{'task':'manualenrollment.apply'},403)
 apply(manager,url,form,{'jform[confirmation]':'0'},400)
 apply(manager,url,form,{'jform[reason]':''},422)
 apply(manager,url,form,{'jform[impact_hash]':'0'*64},409)
 apply(manager,url,form,{'jform[language]':'zz-ZZ'},400)
 apply(manager,url,form,{'jform[user_id]':str(f['denied_user'])},403)
 apply(manager,url,form,{'jform[course_id]':str(f['other_course'])},403)
 fixture('revoke_learner');apply(manager,url,form,expected=403);fixture('grant_learner')
 fixture('revoke_operator');apply(manager,url,form,expected=403);fixture('grant_operator')
 expect(json.loads(fixture('snapshot'))==before,'denied and malformed commands do not leave receipts or subjects')
 _,saved,_=apply(manager,url,form)
 expect('jlms-manual-receipt'in Page(saved).ids and'PRIVATE_HTTP_MANUAL_REASON'in saved,'success exposes authorized receipt')
 after=json.loads(fixture('snapshot'))
 expect(len(after['enrollments'])==1 and after['subjects']==before['subjects']+1 and after['receipts']==1 and after['audit']==1,'atomic subject assignment receipt and audit')
 row=after['enrollments'][0];expect(row['origin']=='manual'and int(row['completed_total'])==0 and row['completed_at']is None and int(row['created_by'])==f['manager'],'correct provenance and no invented completion')
 apply(manager,url,form);expect(json.loads(fixture('snapshot'))==after,'duplicate POST has no duplicate educational or audit rows')
 apply(manager,url,form,{'jform[reason]':'Changed reason'},409)
 fixture('revoke_learner');apply(manager,url,form,expected=403);fixture('grant_learner')
 fixture('revoke_operator');apply(manager,url,form,expected=403);fixture('grant_operator')
 _,existing,_,ef=preview(manager,f['http_user']);expect(ef is None and'jlms-manual-existing'in Page(existing).ids,'existing admission is displayed without creation action')
 student=client();login(student,'cimanualhttp','Ci-only-Manual-Password-2026',False)
 _,history,_=request(student,'/index.php?option=com_jlms&view=dashboard&lang=en')
 expect('Manual admission fixture'in history and'PRIVATE_HTTP_MANUAL_REASON'not in history,'learner sees own assignment but no internal reason')
 _,classroom,_=request(student,'/index.php?option=com_jlms&task=enrollment.resume&id='+str(row['id'])+'&lang=en')
 expect('MANUAL_PRIVATE_BODY'in classroom,'manual learner can resume current published lesson')
 expect(json.loads(fixture('snapshot'))==after,'read navigation preserves admission and progress')
 expect(after['groups']==before['groups'],'manual operation never grants groups')
 _,roster,_=request(manager,'/administrator/index.php?option=com_jlms&view=enrollments&id='+str(f['course']))
 expect('jlms-roster-manual'in Page(roster).ids,'native roster links to manual admission')
 print(json.dumps({'manual_enrollment_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-14000:],file=sys.stderr);raise
finally:
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('restore');log.close()
