"""Exercise real public/admin rendering in an isolated Joomla fixture, without a browser dependency."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urlencode,urljoin,urlparse,parse_qs
from http.cookiejar import CookieJar
import subprocess,sys,time,json,os,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.links=[];self.base=None;self.textarea=None;self.select=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base' and a.get('href'):self.base=a['href']
  if tag=='form':self.current={'id':a.get('id',''),'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['checkbox','radio'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='textarea':
   self.textarea=a.get('name') if self.current is not None and 'disabled' not in a else None
   if self.textarea:self.current['fields'][self.textarea]=''
  if tag=='select':self.select=a.get('name') if self.current is not None and 'disabled' not in a else None
  if tag=='option' and self.select and self.current is not None:
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
  if tag=='a' and a.get('href'):self.links.append(a['href'])
 def handle_endtag(self,tag):
  if tag=='form':self.current=None;self.textarea=None;self.select=None
  if tag=='textarea':self.textarea=None
  if tag=='select':self.select=None
 def handle_data(self,data):
  if self.textarea and self.current is not None:self.current['fields'][self.textarea]+=data
base='http://127.0.0.1:8093'
# Joomla treats the cli-server SAPI as CLI for URI discovery. Configure the isolated
# site's known URL so its native router emits the same paths as a web-server deployment.
config=root/'configuration.php'
text,count=re.subn(r"public\s+\$live_site\s*=\s*[^;]+;", "public $live_site = '"+base+"';", config.read_text())
if count!=1:raise RuntimeError('Disposable site URL could not be configured')
config.write_text(text)
log=open(root.parent/'http-smoke.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
http=build_opener(HTTPCookieProcessor(CookieJar()))
def get(path):
 try:
  with http.open(urljoin(base,path),timeout=15) as r:return r.geturl(),r.read().decode('utf-8')
 except HTTPError as error:
  detail=re.sub(r'<[^>]+>',' ',error.read().decode('utf-8',errors='replace'))
  print('Disposable HTTP fixture error: '+re.sub(r'\s+',' ',detail)[-10000:],file=sys.stderr)
  raise
try:
 for i in range(40):
  try:url,html=get('/index.php?option=com_jlms&view=courses');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('Test HTTP server exited')
   time.sleep(.2)
 else:raise RuntimeError('Test HTTP server did not become ready')
 if 'id="jlms-courses"' not in html or 'Publication fixture' not in html:raise RuntimeError('Published course catalog did not render')
 course_links=[x for x in Page(html).links if 'view=course' in x or '/course/' in x]
 if not course_links:raise RuntimeError('Public course link missing')
 public_course_url=course_links[0]
 _,detail=get(public_course_url)
 if 'id="jlms-course-outline"' not in detail or 'Published lesson' not in detail or 'SECRET_LESSON_BODY' in detail:
  raise RuntimeError('Public sheet must show outline without protected lesson body')
 url,html=get('/administrator/index.php')
 page=Page(html)
 form=next((f for f in page.forms if 'username' in f['fields'] and 'passwd' in f['fields']),None)
 if not form:raise RuntimeError('Native Joomla admin login form was not found')
 fields=form['fields'];fields.update(username='cioperator',passwd='Ci-only-Long-Password-2026')
 with http.open(urljoin(urljoin(url,page.base or ''),form['action']),data=urlencode(fields).encode(),timeout=20) as r:r.read()
 url,html=get('/administrator/index.php?option=com_jlms')
 if 'id="jlms-admin-dashboard"' not in html:raise RuntimeError('Authenticated JD Learn dashboard did not render')
 url,html=get('/administrator/index.php?option=com_categories&view=categories&extension=com_jlms.course')
 if 'CI Courses' not in html:raise RuntimeError('Native Joomla category manager did not render the saved category')
 url,html=get('/administrator/index.php?option=com_jlms&view=courses')
 links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('task')==['course.edit']]
 if not links:raise RuntimeError('Saved draft is absent from the native course list')
 url,html=get(links[0]);forms=Page(html).forms
 edit=next((f for f in forms if 'jform[title]' in f['fields']),None)
 if not edit or 'jform[revision]' not in edit['fields'] or 'jform[alias]' not in edit['fields']:
  raise RuntimeError('Native draft editor/form controls did not render')
 url,html=get('/administrator/index.php?option=com_jlms&view=courses')
 curriculum_links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('view')==['curriculum']]
 if not curriculum_links:raise RuntimeError('Curriculum navigation link missing')
 # The curriculum fixture is created last among courses that have a draft structure.
 chosen=None
 for link in curriculum_links:
  u,h=get(link)
  if 'Curriculum fixture' in h:chosen=(u,h);break
 if not chosen:raise RuntimeError('Curriculum fixture screen absent')
 url,html=chosen
 form=next((f for f in Page(html).forms if f['id']=='jlms-module-form'),None)
 if not form:raise RuntimeError('Native module editor missing')
 fields=dict(form['fields']);fields['jform[title]']='HTTP chapter'
 with http.open(urljoin(url,form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  result=response.read().decode('utf-8')
 if 'HTTP chapter' not in result:raise RuntimeError('Module was not saved through the authenticated MVC form')
 if 'id="jlms-lesson-form"' not in result:raise RuntimeError('Native lesson editor missing')
 lesson_form=next(f for f in Page(result).forms if f['id']=='jlms-lesson-form')
 fields=dict(lesson_form['fields']);fields.update({'jform[title]':'HTTP text lesson','jform[kind]':'text','jform[required]':'1',
  'jform[module_id]':re.search(r'data-module-id="([1-9][0-9]*)"',result).group(1),'jform[body]':'<p>Content saved through the native form</p>'})
 with http.open(urljoin(url,lesson_form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  result=response.read().decode('utf-8')
 if 'HTTP text lesson' not in result:raise RuntimeError('Lesson was not saved through the authenticated MVC form')

 # Start from the same native New Course UI as an administrator, not a service-created published fixture.
 checks=0
 def expect(ok,message):
  global checks
  if not ok:raise RuntimeError(message)
  checks+=1
 def submit(url,form,updates=None):
  fields=dict(form['fields']);fields.update(updates or {})
  try:
   with http.open(urljoin(url,form['action']),data=urlencode(fields).encode(),timeout=20) as response:
    return response.geturl(),response.read().decode('utf-8')
  except HTTPError as error:
   detail=re.sub(r'<[^>]+>',' ',error.read().decode('utf-8',errors='replace'))
   print('Disposable form error: '+re.sub(r'\s+',' ',detail)[-10000:],file=sys.stderr)
   raise
 def fixture(operation='snapshot',value=None):
  args=['php',str(Path(__file__).with_name('publication-http-fixture.php')),operation]
  if value is not None:args.append(str(value))
  return json.loads(subprocess.check_output(args,text=True))
 u,h=get('/administrator/index.php?option=com_jlms&task=course.add')
 expect('id="jlms-course-publication-help"' in h and 'id="jlms-course-programme-link"' not in h,'New course explains save-before-publication')
 form=next(f for f in Page(h).forms if 'jform[title]' in f['fields'])
 # Read the category from the native category selector, not a guessed database ID.
 category=re.search(r'<select[^>]*name="jform\[catid\]"[^>]*>(.*?)</select>',h,re.S).group(1)
 catid=re.search(r'<option[^>]*value="([1-9][0-9]*)"[^>]*>[^<]*CI Courses',category).group(1)
 u,h=submit(u,form,{'task':'course.apply','jform[title]':'HTTP first publication','jform[alias]':'http-first-publication',
  'jform[catid]':catid,'jform[language]':'*','jform[catalog_access]':'1','jform[content_access]':'2',
  'jform[enrollment_mode]':'auto','jform[summary]':'Created and first published through native HTTP forms'})
 form=next(f for f in Page(h).forms if 'jform[title]' in f['fields'])
 course_id=int(form['fields']['jform[id]'])
 expect(course_id>0 and 'id="jlms-course-programme-link"' in h,'Saved draft offers curriculum/publication navigation')
 expect(all('id="'+x+'"' in h for x in ['jlms-course-tabs','jlms-course-general','jlms-course-presentation','jlms-course-access-settings','jlms-course-publishing']),'Native course editor exposes general, presentation, access and publication tabs')
 u,h=submit(u,form,{'task':'course.apply','jform[presentation_description]':'<p>HTTP extended <strong>description</strong></p><img src="https://example.invalid/pixel" onerror="alert(1)"><script>alert(2)</script>',
  'jform[presentation_objectives]':'HTTP objectives <script>plain text</script>','jform[presentation_audience]':'HTTP audience',
  'jform[presentation_prerequisite_text]':'HTTP prior knowledge','jform[presentation_difficulty]':'intermediate','jform[presentation_estimated_minutes]':'75'})
 form=next(f for f in Page(h).forms if 'jform[title]' in f['fields'])
 expect(form['fields']['jform[presentation_estimated_minutes]']=='75' and 'HTTP extended' in form['fields']['jform[presentation_description]'],'Presentation saved and reopened in native editor')
 programme=next(x for x in Page(h).links if parse_qs(urlparse(x).query).get('view')==['curriculum'])
 # Close through the native controller before leaving the editor.
 close_url,close_html=submit(u,form,{'task':'course.save'})
 expect(parse_qs(urlparse(close_url).query).get('view')==['courses'],'Native save and close succeeds with all rendered controls')
 u,h=get(programme)
 expect('id="jlms-publication-notice"' in h and 'id="jlms-version-form"' in h,'Course without version explains the next step')
 form=next(f for f in Page(h).forms if f['id']=='jlms-version-form');u,h=submit(u,form)
 expect('id="jlms-publication-form"' in h and re.search(r'id="jlms-publication-submit"[^>]*>\s*Publish\s*</button>',h),'First draft exposes an explicit Publish command')
 before=fixture()
 form=next(f for f in Page(h).forms if f['id']=='jlms-publication-form')
 transition=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
 u,h=submit(u,form,{'jform[transition_id]':transition})
 expect(fixture()==before,'Empty initial programme cannot publish or create an edition')
 form=next(f for f in Page(h).forms if f['id']=='jlms-module-form');u,h=submit(u,form,{'jform[title]':'HTTP first module'})
 form=next(f for f in Page(h).forms if f['id']=='jlms-lesson-form')
 u,h=submit(u,form,{'jform[title]':'HTTP first required lesson','jform[kind]':'text','jform[required]':'1',
  'jform[module_id]':re.search(r'data-module-id="([1-9][0-9]*)"',h).group(1),
  'jform[body]':'<p>FIRST_PUBLICATION_PRIVATE_BODY</p>'})
 expect('HTTP first required lesson' in h,'Module and required text lesson saved through HTTP')
 form=next(f for f in Page(h).forms if f['id']=='jlms-publication-form')
 transition=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
 # A disabled native plugin must be diagnosed without changing course/version data.
 original_plugin=fixture()['plugin']
 try:
  fixture('plugin',0);u,h=get(u)
  expect('id="jlms-publication-notice"' in h and 'id="jlms-publication-form"' not in h,'Disabled Publishing plugin has visible diagnostic and no action')
  before=fixture();u,h=submit(u,form,{'jform[transition_id]':transition})
  expect(fixture()==before,'Stale form cannot publish with disabled plugin')
 finally:fixture('plugin',original_plugin)
 u,h=get(u);form=next(f for f in Page(h).forms if f['id']=='jlms-publication-form')
 stale_form=dict(form);stale_form['fields']=dict(form['fields'])
 transition=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
 u,h=submit(u,form,{'jform[transition_id]':transition})
 published=fixture()['course']
 expect(int(published['state'])==1 and published['status']=='published' and int(published['editions'])==1 and int(published['associations'])==1,'First HTTP publication freezes version and creates one edition and native association')
 expect(bool(published['structure_hash']) and bool(published['published_at']),'Initial publication records immutable hash and timestamp')
 u,h=submit(u,stale_form,{'jform[transition_id]':transition})
 expect(fixture()['course']==published,'Repeated stale publication does not duplicate edition or change history')
 public_http=build_opener(HTTPCookieProcessor(CookieJar()))
 with public_http.open(base+'/index.php?option=com_jlms&view=course&id='+str(course_id),timeout=15) as response:
  sheet=response.read().decode('utf-8')
 expect('HTTP first required lesson' in sheet and 'FIRST_PUBLICATION_PRIVATE_BODY' not in sheet,'First published course is public with no lesson body leak')
 expect(all('id="'+x+'"' in sheet for x in ['jlms-course-description','jlms-course-objectives','jlms-course-audience','jlms-course-prerequisite-text','jlms-course-facts','jlms-course-difficulty','jlms-course-estimated-duration']),'Public presentation exposes documented unique regions')
 expect('<strong>description</strong>' in sheet and 'HTTP audience' in sheet and '75 minutes' in sheet,'Frozen presentation is visible on public course sheet')
 expect('example.invalid/pixel' not in sheet and '<script>plain text</script>' not in sheet and '&lt;script&gt;plain text&lt;/script&gt;' in sheet,'Unsafe HTML removed and informational text escaped')
 expect('id="jlms-publication-status"' in h,'Administrator sees course publication status')
 print(json.dumps({'first_publication_http_checks':checks,'status':'passed'}))

 # Exercise the native publication controller, CSRF token, redirect and current course revision.
 for link in curriculum_links:
  u,h=get(link)
  if 'Publication fixture' in h:break
 else:raise RuntimeError('Publication fixture admin screen absent')
 for operation in ['unpublish','republish']:
  form=next((f for f in Page(h).forms if f['id']=='jlms-publication-form'),None)
  if not form:raise RuntimeError('Native transition form missing')
  fields=dict(form['fields'])
  fields['jform[transition_id]']=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
  with http.open(urljoin(u,form['action']),data=urlencode(fields).encode(),timeout=20) as response:
   h=response.read().decode('utf-8')
  try:
   with http.open(urljoin(base,public_course_url),timeout=15) as response:status=response.status;response.read()
  except HTTPError as error:status=error.code
  if status!=(404 if operation=='unpublish' else 200):raise RuntimeError('HTTP '+operation+' did not update public visibility')

 print(json.dumps({'http_smoke':'passed' ,'pages':['public catalogue','native admin login','dashboard','draft list','draft editor']}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
