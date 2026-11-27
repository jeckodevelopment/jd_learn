"""Private owner history, Joomla forms, native menus and read-only resume via real HTTP."""
from pathlib import Path
import runpy
verify_dom = runpy.run_path(str(Path(__file__).resolve().parent / 'frontend-dom-runtime.py'))['verify']
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import json,os,re,subprocess,sys,time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'dashboard-fixture.json').read_text());base='http://127.0.0.1:8093'
config=root/'configuration.php';original=config.read_text();log=open(root.parent/'dashboard-http.log','w+');server=None;checks=0;navActivated=False
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
def fixture(mode):return subprocess.check_output(['php','tests/dashboard-http-fixture.php',str(root),mode],text=True)
def login(http,name):
 url,html,_=request(http,'/index.php?option=com_users&view=login&lang=en');form=next(x for x in Page(html).forms if'username'in x['fields']and'password'in x['fields'])
 request(http,urljoin(url,form['action']),form['fields']|{'username':name,'password':'Ci-only-Dashboard-Password-2026'})
def enrollments(html):return set(map(int,re.findall(r'data-enrollment-id="([1-9][0-9]*)"',html)))
def card(html,enrollment):
 match=re.search(r'<article\b[^>]*id="jlms-my-course-'+str(enrollment)+r'"[^>]*>.*?</article>',html,re.S)
 if not match:
  raise RuntimeError('Missing rendered learner card '+str(enrollment))
 return match.group(0)
def blocked_card(html,enrollment,message):
 markup=card(html,enrollment)
 expect('jlms-my-course__next-action' in markup and message in markup,
  'blocked card shows its specific next action: '+str(enrollment))
 expect('jlms-my-course-resume-'+str(enrollment) not in markup,
  'blocked card has no resume/review action: '+str(enrollment))
dashboard='/index.php?option=com_jlms&view=mycourses&lang=en'
overview='/index.php?option=com_jlms&view=dashboard&lang=en'
resume='/index.php?option=com_jlms&task=enrollment.resume&id='
try:
 fixture('enable');before=json.loads(fixture('snapshot'))
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 guest=client()
 for _ in range(40):
  try:request(guest,'/index.php?option=com_jlms&view=courses&lang=en');break
  except OSError:time.sleep(.2)
 request(guest,dashboard,expected=401)
 request(guest,overview,expected=401)
 request(guest,resume+str(f['active'])+'&lang=en',expected=401)
 student=client();login(student,'cidashboard')
 # CORE-21: two independent authenticated sessions + guest, exhaustive
 # task allowlist and private-view inventory derived from a fail-closed manifest.
 from urllib.request import Request, HTTPRedirectHandler
 matrix=runpy.run_path(str(Path(__file__).resolve().parent/'secdev02-core21-task-matrix.py'))
 expect(not matrix['validate'](Path(__file__).resolve().parents[1]),
  'CORE-21 manifest differs from actual protected Joomla controllers')
 class NoRedirect(HTTPRedirectHandler):
  def redirect_request(self,req,fp,code,msg,headers,newurl):return None
 def direct(http,path,method='GET'):
  # Preserve the original authenticated cookie jar but inspect a 303 itself,
  # not the ultimately reached public page, which may use different headers.
  handler=next(h for h in http.handlers if isinstance(h,HTTPCookieProcessor))
  raw=build_opener(HTTPCookieProcessor(handler.cookiejar),NoRedirect())
  probe=Request(urljoin(base,path),data=b'' if method=='POST' else None,method=method)
  try:
   with raw.open(probe,timeout=20) as result:
    code=result.status
    headers=result.headers
    body=result.read().decode('utf-8',errors='replace')
  except HTTPError as error:
   code=error.code
   headers=error.headers
   body=error.read().decode('utf-8',errors='replace')
  return code,headers,body
 empty=client()
 login(empty,'cidashboardempty')
 private_cases=0
 matrix_failures=[]
 def matrix_check(ok,message):
  if not ok:
   matrix_failures.append(message)
 for name,http in [('owner',student),('empty',empty),('guest',guest)]:
  for controller,actions in matrix['TASKS'].items():
   for action,method in actions.items():
    task=controller+'.'+action
    url='/index.php?option=com_jlms&task='+task+'&format=raw&lang=en'
    if task=='mailconsent.confirm':
     url+='&token=invalid-core21-fixture'
    code,headers,body=direct(http,url,method)
    # A native Joomla FormController edit GET without a record may redirect.
    # Every mutation POST must be rejected without valid CSRF.
    if task=='mailconsent.confirm':
     matrix_check(code in [302,303,400,403,404],
      'CORE-21 invalid consent link not rejected/redirected: '+name+' '+str(code))
    elif task.startswith('teacher.'):
     # Native FormController redirects on absent editor ID or failed CSRF.
     # Inspect the 303 itself, and never accept success for an invalid POST.
     matrix_check(code in [303,400,401,403,404,405],
      'CORE-21 teacher action unexpectedly accepted: '+name+' '+task+' '+str(code))
     if code==303:
      location=headers.get('Location','')
      matrix_check(bool(location) and urljoin(base+'/',location).startswith(base+'/'),
       'CORE-21 teacher redirect leaves Joomla origin: '+task+' '+location)
    else:
     matrix_check(400<=code<500,
      'CORE-21 unsafe task response '+name+' '+task+' HTTP '+str(code))
    matrix_check('no-store' in headers.get('Cache-Control','').lower(),
     'CORE-21 no-store missing '+name+' '+task+' HTTP '+str(code))
    matrix_check('LEARNING_PRIVATE_BODY_2' not in body and 'PRIVATE_CATEGORY_LESSON' not in body,
     'CORE-21 task exposed private lesson body '+name+' '+task)
    private_cases+=1
  for view in matrix['PRIVATE_VIEWS']:
   url='/index.php?option=com_jlms&view='+view+'&lang=en'
   code,headers,body=direct(http,url)
   matrix_check(200<=code<500,
    'CORE-21 private view '+view+' returned server error HTTP '+str(code))
   matrix_check('no-store' in headers.get('Cache-Control','').lower(),
    'CORE-21 private view '+view+' missing no-store for '+name)
   matrix_check('noindex' in headers.get('X-Robots-Tag','').lower(),
    'CORE-21 private view '+view+' missing noindex for '+name)
   if name!='owner':
    matrix_check('LEARNING_PRIVATE_BODY_2' not in body,
     'CORE-21 private view leaked owner content into '+name)
   private_cases+=1
 expect(private_cases == 3*(sum(len(a) for a in matrix['TASKS'].values())+
  len(matrix['PRIVATE_VIEWS'])),'CORE-21 complete HTTP case count diverged')
 print(json.dumps({'core21_private_http_cases':private_cases,
  'task_routes':sum(len(a) for a in matrix['TASKS'].values()),
  'private_views':len(matrix['PRIVATE_VIEWS']),'sessions':3,
  'unmet_expectations':matrix_failures},ensure_ascii=False))
 if matrix_failures:
  raise RuntimeError('CORE-21 exhaustive HTTP failures: '+'; '.join(matrix_failures))
 checks+=private_cases
 # FE-06: menu opt-in defaults off; toggling the active Joomla menu must be effective.
 menu_overview=overview+'&Itemid='+str(f['menus']['en-GB'])
 _,hidden_nav,_=request(student,menu_overview)
 expect('jlms-personal-navigation' not in Page(hidden_nav).ids,'personal navigation defaults off')
 fixture('navigation_on');navActivated=True
 _,direct_nav,_=request(student,overview)
 expect('jlms-personal-navigation' not in Page(direct_nav).ids,'direct links with unrelated active menu cannot enable local navigation')
 _,shown_nav,_=request(student,menu_overview)
 shown_page=Page(shown_nav)
 expect(verify_dom(shown_nav, root='jlms-dashboard') > 0, 'UI-07: no duplicate IDs with personal navigation enabled')
 expect('jlms-personal-navigation' in shown_page.ids,'navigation enabled on matching active Joomla menu')
 expect('aria-current="page"' in shown_nav and 'COM_JLMS_PERSONAL_NAV_LABEL' not in shown_nav,'current view and label localized')
 nav_section=shown_nav.split('<nav id="jlms-personal-navigation"',1)[1].split('</nav>',1)[0]
 expect(nav_section.count('<a ') == 3 and nav_section.count('aria-current="page"') == 1,'local nav links exactly the three other learner views')
 expect(len(shown_page.ids)==len(set(shown_page.ids)),'local nav does not duplicate IDs')
 fixture('navigation_off');navActivated=False
 for sef in [0,1]:
  text,count=re.subn(r'public\s+\$sef\s*=\s*[^;]+;',f'public $sef = {sef};',original)
  if count!=1:raise RuntimeError('SEF fixture missing')
  config.write_text(text)
  url,html,headers=request(student,dashboard+'&limit=50&limitstart=0')
  page=Page(html)
  _,summary_html,summary_headers=request(student,overview)
  summary_page=Page(summary_html)
  expect(verify_dom(summary_html, root='jlms-dashboard') > 0, 'UI-07: summary DOM does not duplicate component IDs')
  expect('jlms-dashboard-summary' in summary_page.ids and 'jlms-dashboard-my-certificates-link' in summary_page.ids and 'jlms-dashboard-preferences-link' in summary_page.ids,'summary links to autonomous views')
  expect('jlms-my-courses' not in summary_page.ids and 'jlms-dashboard-filters' not in summary_page.ids and not enrollments(summary_html),'summary does not repeat full history')
  expect('no-store' in summary_headers.get('Cache-Control','') and 'noindex' in summary_headers.get('X-Robots-Tag',''),'summary is private and not indexed')
  expect('jlms-dashboard-my-courses-link' in summary_page.ids and 'jlms-dashboard-catalog' in summary_page.ids,'summary keeps existing navigation')
  expect('jlms-dashboard'in page.ids and'jlms-my-courses'in page.ids,'private dashboard CSS regions')
  expect(verify_dom(html, root='jlms-dashboard', scoped_class='jlms-my-course') > 0, 'UI-07: student CSS customization selector supported by rendered DOM')
  expect(len(page.ids)==len(set(page.ids)),'no duplicate public IDs')
  expect('no-store'in headers.get('Cache-Control','')and'noindex'in headers.get('X-Robots-Tag','')and'noindex, nofollow'in html,'private non-indexable response')
  expect(f['active']in enrollments(html)and f['other_enrollment']not in enrollments(html),'current owner data only')
  expect('LEARNING_PRIVATE_BODY'not in html and'PRIVATE_CATEGORY_LESSON'not in html and'rules_json'not in html,'dashboard has no teaching bodies or rules')
  _,forged,_=request(student,dashboard+'&user_id='+str(f['other'])+'&id='+str(f['other_enrollment']))
  expect(f['active']in enrollments(forged)and f['other_enrollment']not in enrollments(forged),'query parameters cannot select another account')
  _,filtered,_=request(student,dashboard+'&'+urlencode({'filter[progress]':'completed'}))
  expect(enrollments(filtered)=={f['completed']}and'jlms-dashboard-completed'in Page(filtered).ids,'native progress filter keeps expired completion history')
  expect('jlms-my-course-resume-'+str(f['completed'])not in Page(filtered).ids,'expired completed history does not expose review action')
  _,second,_=request(student,dashboard+'&limit=20&limitstart=20')
  expect(len(enrollments(second))==7,'pagination counts owner history across course languages')
  # Native ListModel retains limitstart in the session; return to the page containing the active enrollment.
  _,first_again,_=request(student,dashboard+'&limitstart=0')
  expect(f['active']in enrollments(first_again),'return from retained pagination to active enrollment')
  form=next(x for x in page.forms if x['id']=='jlms-dashboard-filters')
  expect('filter[progress]'in html and'completed'in html,'native filter form rendered')
  # STU-01: every normal access diagnosis is shown in the rendered learner
  # card, with a distinct suggestion and no unavailable CTA.
  for enrollment,message in [
   (f['completed'],'Check the catalogue for a new edition'),
   (f['suspended'],'Contact the course manager to review the suspended enrollment'),
   (f['cancelled'],'Check the catalogue to see whether you can submit a new enrollment request'),
   (f['pending'],'Wait for a decision on your request'),
   (f['future'],'Try again after the course access window opens')]:
   blocked_card(html,enrollment,message)
  expect('jlms-my-course-resume-'+str(f['active']) in Page(html).ids,
   'available card keeps its resume action')
  target=next(a['href']for a in page.links if a.get('id')=='jlms-my-course-resume-'+str(f['active']))
  target_url,classroom,_=request(student,target)
  expect('id="jlms-classroom"'in classroom and'LEARNING_PRIVATE_BODY_2'in classroom,'resume opens last valid owned activity')
  expect('jlms-classroom-dashboard'in Page(classroom).ids,'classroom links back to private area')
  request(student,resume+str(f['other_enrollment'])+'&lang=en',expected=404)
  request(student,resume+str(f['active'])+'&lang=en',data={},expected=405)
  request(student,'/index.php?option=com_jlms&task=enrollment.resume&id[]=1&lang=en',expected=400)
  request(student,resume+str(f['suspended'])+'&lang=en',expected=403)
  _,italian,_=request(student,resume+str(f['italian'])+'&lang=en')
  expect('id="jlms-classroom"'in italian and'PRIVATE_CATEGORY_LESSON'in italian,'resume uses original course language without transferring enrollment')
  fixture('revoke')
  _,revoked,_=request(student,dashboard)
  expect(f['active']in enrollments(revoked)and'jlms-my-course-resume-'+str(f['active'])not in Page(revoked).ids,'mid-session revocation retains history and removes resume action')
  expect('Ask the administrator to check the Joomla viewing access required for the course content.' in revoked,'blocked history offers a current-access next step')
  blocked_card(revoked,f['active'],'Ask the administrator to check the Joomla viewing access required')
  request(student,resume+str(f['active'])+'&lang=en',expected=403)
  request(student,'/index.php?option=com_jlms&view=classroom&id='+str(f['active'])+'&lang=en',expected=403)
  _,revoked_overview,_=request(student,overview)
  expect('jlms-dashboard-summary'in Page(revoked_overview).ids and 'jlms-dashboard-my-courses-link'in Page(revoked_overview).ids,'role revocation preserves owner-scoped personal navigation and history')
  _,revoked_course,_=request(student,'/index.php?option=com_jlms&view=course&id='+str(f['course'])+'&lang=en')
  expect('LEARNING_PRIVATE_BODY' not in revoked_course and 'PRIVATE_CATEGORY_LESSON' not in revoked_course,'revoked course page does not disclose lesson bodies')
  _,revoked_catalog,_=request(student,'/index.php?option=com_jlms&view=courses&lang=en')
  expect('LEARNING_PRIVATE_BODY' not in revoked_catalog and 'PRIVATE_CATEGORY_LESSON' not in revoked_catalog,'revoked learner retains only public catalog metadata')
  fixture('grant')
  _,restored,_=request(student,dashboard)
  expect('jlms-my-course-resume-'+str(f['active'])in Page(restored).ids,'same-session restored access reflected')
  fixture('course_off')
  _,unpublished,_=request(student,dashboard)
  blocked_card(unpublished,f['active'],'Contact the site administrator to check the course publication and availability')
  fixture('course_on')
  fixture('language_missing')
  _,missing_language,_=request(student,dashboard)
  blocked_card(missing_language,f['active'],'Change the site language and check whether the course is available')
  fixture('language_restore')
  fixture('block');request(student,dashboard,expected=403);fixture('unblock')
 _,html,_=request(empty,dashboard)
 expect('jlms-dashboard-empty'in Page(html).ids and not enrollments(html),'empty state without new enrollment')
 admin=client();url,html,_=request(admin,'/administrator/index.php');form=next(x for x in Page(html).forms if'username'in x['fields']and'passwd'in x['fields'])
 request(admin,urljoin(url,form['action']),form['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 _,html,_=request(admin,'/administrator/index.php?option=com_menus&task=item.edit&id='+str(f['menus']['en-GB']))
 expect('My courses'in html or'My Courses'in html,'native menu manager recognizes dashboard layout metadata')
 expect(json.loads(fixture('snapshot'))==before,'dashboard and resume GETs do not alter enrollment progress or consume attempts')
 print(json.dumps({'dashboard_http_checks':checks,'sef_modes':[0,1],'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-12000:],file=sys.stderr);raise
finally:
 try:
  fixture('language_restore')
  fixture('course_on')
  fixture('grant')
  fixture('unblock')
 except Exception:
  pass
 if navActivated: fixture('navigation_off')
 config.write_text(original)
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('disable');log.close()
