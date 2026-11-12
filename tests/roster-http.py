"""Course-scoped administrative roster, native filters and fresh ACL over real HTTP."""
from pathlib import Path
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import base64,json,os,re,subprocess,sys,time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'roster-fixture.json').read_text());base='http://127.0.0.1:8093'
config=root/'configuration.php';original=config.read_text();log=open(root.parent/'roster-http.log','w+');server=None;checks=0
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
def fixture(mode):return subprocess.check_output(['php','tests/roster-http-fixture.php',str(root),mode],text=True)
def login(http,name,password):
 url,html,_=request(http,'/administrator/index.php');form=next(x for x in Page(html).forms if'username'in x['fields']and'passwd'in x['fields'])
 fields=form['fields']|{'username':name,'passwd':password,'return':base64.b64encode(('/administrator/index.php?option=com_jlms&view=enrollments&id='+str(f['course'])).encode()).decode()}
 request(http,urljoin(url,form['action']),fields)
def enrollments(html):return set(map(int,re.findall(r'data-enrollment-id="([1-9][0-9]*)"',html)))
roster='/administrator/index.php?option=com_jlms&view=enrollments&id='+str(f['course'])
try:
 before=json.loads(fixture('snapshot'))
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 guest=client()
 for _ in range(40):
  try:request(guest,'/administrator/index.php');break
  except OSError:time.sleep(.2)
 _,html,_=request(guest,roster)
 expect('jlms-roster-table'not in Page(html).ids,'anonymous cannot view enrollment history')
 admin=client();login(admin,'cioperator','Ci-only-Long-Password-2026')
 _,html,headers=request(admin,roster+'&limitstart=0')
 p=Page(html)
 expect('jlms-roster-table'in p.ids and'jlms-roster-filters'in p.ids,'native roster rendered')
 expect('name="filter[search]"'in html,'native learner search control rendered')
 expect('data-roster-option-search="filter_version"'in html and 'id="filter_version"' in html,'course version filter has a linked searchable control')
 expect('data-roster-option-search="filter_user"'in html and 'id="filter_user"' in html,'enrolled user filter has a linked searchable control')
 expect('name="filter[version]"' in html and 'name="filter[user]"' in html and str(f['owner']) in html,'authorized filter choices are native selects populated from this course')
 expect('no-store'in headers.get('Cache-Control',''),'private no-store administrative response')
 expect(len(enrollments(html))==20 and f['foreign_enrollment']not in enrollments(html),'bounded course-only first page')
 # Audit the IDs owned by this component, including the native filter controls.
 owned=[value for value in p.ids if value.startswith(('jlms-','filter_'))]
 duplicates=sorted({value for value in p.ids if p.ids.count(value)>1})
 print(json.dumps({'roster_page_duplicate_ids':duplicates}))
 expect(len(owned)==len(set(owned)),'unique JD Learn roster and filter IDs')
 _,second,_=request(admin,roster+'&limit=20&limitstart=20')
 expect(len(enrollments(second))==f['total']-20,'native second page')
 _,owner,_=request(admin,roster+'&limitstart=0&'+urlencode({'filter[user]':f['owner']}))
 expect('&lt;img src=x onerror=ROSTER_XSS&gt;'in owner and'<img src=x onerror=ROSTER_XSS>'not in owner,'native account label is escaped')
 for private in ['LEARNING_PRIVATE_BODY','grading_json','rules_json','subject_key','Ci-only-']:
  expect(private not in owner,'private data omitted: '+private)
 _,completed,_=request(admin,roster+'&limitstart=0&'+urlencode({'filter[user]':f['owner'],'filter[progress]':'completed'}))
 expect(enrollments(completed)=={f['completed']}and'100%'in completed,'native completion filter independent of expiry')
 _,suspended,_=request(admin,roster+'&limitstart=0&'+urlencode({'filter[user]':f['owner'],'filter[status]':'suspended'}))
 expect(enrollments(suspended)=={f['suspended']},'native status filter')
 _,empty,_=request(admin,roster+'&limitstart=0&'+urlencode({'filter[user]':'2147483647'}))
 expect('jlms-roster-empty'in Page(empty).ids and not enrollments(empty),'empty filtered roster')
 _,by_name,_=request(admin,roster+'&limit=50&limitstart=0&'+urlencode({'filter[user]':f['owner'],'filter[search]':'cidash'}))
 expect(len(enrollments(by_name))==25,'learner search combines with the user filter')
 _,literal,_=request(admin,roster+'&limitstart=0&'+urlencode({'filter[search]':'%'}))
 expect('jlms-roster-empty'in Page(literal).ids,'LIKE wildcard is treated literally')
 for query in [{'filter[status]':"active' OR 1=1 --"},{'filter[user]':'1 OR 1=1'},{'filter[version]':-1},{'filter[progress][]':'completed'},{'filter[search]':'x'*101},{'filter[search][]':'cidash'}]:
  request(admin,roster+'&'+urlencode(query),expected=400)
 _,courses,_=request(admin,'/administrator/index.php?option=com_jlms&view=courses')
 expect('view=enrollments'in courses,'authorized course list links to roster')
 manager=client();login(manager,'cirosterreader','Ci-only-Roster-Password-2026')
 _,html,_=request(manager,roster+'&limitstart=0')
 expect('jlms-roster-table'in Page(html).ids,'dedicated native course manager can read roster')
 request(manager,'/administrator/index.php?option=com_jlms&view=enrollments&id='+str(f['other_course']),expected=403)
 fixture('revoke');request(manager,roster,expected=403);fixture('grant')
 _,html,_=request(manager,roster)
 expect('jlms-roster-table'in Page(html).ids,'restored same-session rights are fresh')
 expect(json.loads(fixture('snapshot'))==before,'all roster GETs preserve enrollment history')
 print(json.dumps({'roster_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-12000:],file=sys.stderr);raise
finally:
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('restore');log.close()
