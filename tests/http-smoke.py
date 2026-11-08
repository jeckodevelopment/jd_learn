"""Exercise real public/admin rendering in an isolated Joomla fixture, without a browser dependency."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urlencode,urljoin,urlparse,parse_qs
from http.cookiejar import CookieJar
from html import unescape
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
 course_links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('view')==['course'] or '/course/' in x]
 if not course_links:raise RuntimeError('Public course link missing')
 for public_course_url in course_links:
  _,detail=get(public_course_url)
  if 'id=\"jlms-course-outline\"' in detail and 'Publication fixture' in detail:break
 else:raise RuntimeError('Publication fixture sheet missing')
 if 'id="jlms-course-outline"' not in detail or 'Published lesson' not in detail or 'SECRET_LESSON_BODY' in detail:
  raise RuntimeError('Public sheet must show outline without protected lesson body')
 url,html=get('/administrator/index.php')
 page=Page(html)
 form=next((f for f in page.forms if 'username' in f['fields'] and 'passwd' in f['fields']),None)
 if not form:raise RuntimeError('Native Joomla admin login form was not found')
 fields=form['fields'];fields.update(username='cioperator',passwd='Ci-only-Long-Password-2026')
 with http.open(urljoin(urljoin(url,page.base or ''),form['action']),data=urlencode(fields).encode(),timeout=20) as r:r.read()
 url,html=get('/administrator/index.php?option=com_jlms')
 if 'id="jlms-admin-courses"' not in html:raise RuntimeError('JD Learn must open the course list directly')
 menu_links=[parse_qs(urlparse(x).query) for x in Page(html).links]
 for view in ['courses','teachers','questionbank']:
  if not any(q.get('option')==['com_jlms'] and q.get('view')==[view] for q in menu_links):raise RuntimeError('Native JD Learn sidebar link missing: '+view)
 if not any(q.get('option')==['com_categories'] and q.get('extension')==['com_jlms.course'] for q in menu_links):raise RuntimeError('Native course category sidebar missing')
 custom_fields_link=next((x for x in Page(html).links if parse_qs(urlparse(x).query).get('option')==['com_fields'] and parse_qs(urlparse(x).query).get('view')==['fields'] and parse_qs(urlparse(x).query).get('context')==['com_jlms.course']),None)
 if not custom_fields_link:raise RuntimeError('Native custom fields sidebar link missing')
 _,custom_fields_html=get(urljoin(url,custom_fields_link))
 context_filter=re.search(r'<select[^>]+name="context"[^>]*>(.*?)</select>',custom_fields_html,re.S)
 if not context_filter or any('value="com_jlms.'+context+'"' not in context_filter.group(1) for context in ['course','teacher','lesson']):raise RuntimeError('Native custom fields context filter must offer course, teacher and lesson')
 plugin_link=next((x for x in Page(html).links if parse_qs(urlparse(x).query).get('option')==['com_plugins'] and parse_qs(urlparse(x).query).get('filter[folder]')==['jlmsvideo']),None)
 if not plugin_link:raise RuntimeError('Native video plugin manager sidebar link missing')
 # A prior plugin-manager search/status must not hide providers on entry from JD Learn.
 get('/administrator/index.php?option=com_plugins&view=plugins&filter[folder]=content&filter[search]=JDLEARN_NO_MATCH&filter[enabled]=0&filter[element]=missing&limitstart=100')
 _,plugin_html=get(urljoin(urljoin(url,Page(html).base or ""),plugin_link))
 plugin_page=Page(plugin_html)
 plugin_form=next(f for f in plugin_page.forms if f['id']=='adminForm')
 if plugin_form['fields'].get('filter[folder]')!='jlmsvideo' or plugin_form['fields'].get('filter[search]')!='' or plugin_form['fields'].get('filter[enabled]')!='':raise RuntimeError('Video plugin filter or cleared search/status is incorrect')
 plugin_ids={parse_qs(urlparse(x).query).get('extension_id',[''])[0] for x in plugin_page.links if parse_qs(urlparse(x).query).get('task')==['plugin.edit']}
 if len(plugin_ids)!=4 or '' in plugin_ids:raise RuntimeError('Filtered manager must show exactly the four installed video plugins')
 print(json.dumps({'admin_navigation_http_checks':8,'status':'passed'}))
 url,html=get('/administrator/index.php?option=com_categories&view=categories&extension=com_jlms.course')
 if 'CI Courses' not in html:raise RuntimeError('Native Joomla category manager did not render the saved category')
 url,html=get('/administrator/index.php?option=com_jlms&view=courses')
 if 'COM_JLMS_COURSE_FIELDS_MANAGE' in html or 'option=com_fields&amp;view=fields&amp;context=com_jlms.course' in html:raise RuntimeError('Course custom fields access must be in the sidebar, not the list view')
 links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('task')==['course.edit']]
 if not links:raise RuntimeError('Saved draft is absent from the native course list')
 # The latest course may already be published: find an actually editable draft.
 # Disabled published controls are not browser-successful fields.
 edit=None
 for link in links:
  url,html=get(link)
  edit=next((f for f in Page(html).forms if 'jform[title]' in f['fields']),None)
  if edit:break
 if not edit or 'jform[revision]' not in edit['fields'] or 'jform[alias]' not in edit['fields']:
  raise RuntimeError('Native draft editor/form controls did not render')
 fields=dict(edit['fields']);fields.update({'task':'course.apply','jform[summary]':'è'*501})
 with http.open(urljoin(url,edit['action']),data=urlencode(fields).encode(),timeout=20) as response:
  invalid_html=response.read().decode('utf-8')
 invalid=next(f for f in Page(invalid_html).forms if 'jform[summary]' in f['fields'])
 if ('The short description must contain no more than 500 characters.' not in invalid_html
     or invalid['fields']['jform[summary]']!='è'*501
     or invalid['fields']['jform[revision]']!=edit['fields']['jform[revision]']):
  raise RuntimeError('The editor must reject an overlong description and preserve the unsaved input')
 print(json.dumps({'editor_summary_http_checks':1,'status':'passed'}))
 url,html=get('/administrator/index.php?option=com_jlms&view=courses')
 curriculum_links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('view')==['curriculum']]
 if not curriculum_links:raise RuntimeError('Curriculum navigation link missing')
 # The curriculum fixture is created last among courses that have a draft structure.
 chosen=None
 for link in curriculum_links:
  u,h=get(link)
  if 'Curriculum fixture' in h:chosen=(u,h);break
 if not chosen:raise RuntimeError('Curriculum fixture screen absent')
 module_link=next(x for x in Page(chosen[1]).links if parse_qs(urlparse(x).query).get('new_module')==['1'])
 url,html=get(module_link)
 form=next((f for f in Page(html).forms if f['id']=='jlms-module-form'),None)
 if not form:raise RuntimeError('Native module editor missing')
 fields=dict(form['fields']);fields['jform[title]']='HTTP chapter'
 with http.open(urljoin(url,form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  result=response.read().decode('utf-8')
 if 'HTTP chapter' not in result:raise RuntimeError('Module was not saved through the authenticated MVC form')
 lesson_link=next(x for x in Page(result).links if parse_qs(urlparse(x).query).get('new_lesson')==['1'])
 url,result=get(lesson_link)
 if 'id="jlms-lesson-form"' not in result:raise RuntimeError('Contextual native lesson editor missing')
 lesson_form=next(f for f in Page(result).forms if f['id']=='jlms-lesson-form')
 fields=dict(lesson_form['fields']);fields.update({'jform[title]':'HTTP text lesson','jform[kind]':'text','jform[required]':'1',
  'jform[module_id]':re.search(r'data-module-id="([1-9][0-9]*)"',result).group(1),'jform[body]':'<p>Content saved through the native form</p>'})
 with http.open(urljoin(url,lesson_form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  result=response.read().decode('utf-8')
 if 'HTTP text lesson' not in result:raise RuntimeError('Lesson was not saved through the authenticated MVC form')
 preview_link=next(link for link in Page(result).links if parse_qs(urlparse(link).query).get('view')==['coursepreview'])
 preview_url,preview=get(preview_link)
 if 'id="jlms-course-preview"' not in preview or 'HTTP text lesson' not in preview:
  raise RuntimeError('Program link must open the correct editorial preview')
 for link in Page(preview).links:
  if parse_qs(urlparse(link).query).get('lesson_id'):
   _,preview_lesson=get(link)
   if 'Content saved through the native form' in preview_lesson:
    break
 else:raise RuntimeError('Preview must render the saved lesson content')
 if 'id="jlms-course-preview-lesson"' not in preview_lesson or 'id="jlms-complete-form"' in preview_lesson or 'com_jlms.video' in preview_lesson:
  raise RuntimeError('Editorial preview must not expose student actions or a player session')
 try:
  http.open(preview_url+'&lesson_id=2147483647',timeout=15)
 except HTTPError as error:
  if error.code!=404:raise
 else:raise RuntimeError('An unknown lesson must not render a preview')
 setup=re.search(r'<details id="jlms-video-setup">(.*?)</details>',result,re.S)
 if not setup or not all(f'<strong>{name}</strong>' in setup[1] for name in ['YouTube','Vimeo','Cloudflare Stream','MP4']):
  raise RuntimeError('Draft editor must show configuration status for all shipped providers')
 print(json.dumps({'video_editor_setup_http_checks':1,'status':'passed'}))

 # Editor diagnostics and delegated-category navigation over real HTTP.
 editor_fixture=json.loads((root.parent/'editor-access-fixture.json').read_text())
 u,h=get('/administrator/index.php?option=com_jlms&view=course&layout=edit&id='+str(editor_fixture['locked']))
 locked_form=next(f for f in Page(h).forms if f['id']=='adminForm')
 if 'jform[title]' in locked_form['fields'] or 'id="jlms-course-effective-rights"' not in h or 'id="jlms-course-rules"' in h:
  raise RuntimeError('Other-operator checkout must render a read-only editor and permission diagnostic')
 if 'id="jlms-course-options-link"' not in h:raise RuntimeError('Authorised native options link missing')
 delegated_http=build_opener(HTTPCookieProcessor(CookieJar()))
 def delegated_get(path):
  with delegated_http.open(urljoin(base,path),timeout=20) as response:return response.geturl(),response.read().decode('utf-8')
 du,dh=delegated_get('/administrator/index.php')
 login_form=next(f for f in Page(dh).forms if 'username' in f['fields'] and 'passwd' in f['fields'])
 fields=dict(login_form['fields']);fields.update(username='cieditor',passwd='Ci-only-Editor-Password-2026')
 with delegated_http.open(urljoin(du,login_form['action']),data=urlencode(fields).encode(),timeout=20) as response:response.read()
 try:
  delegated_get(preview_url)
 except HTTPError as error:
  if error.code!=403:raise
 else:raise RuntimeError('An editor delegated elsewhere cannot preview the course')
 print(json.dumps({'editorial_preview_http_checks':4,'status':'passed'}))
 du,dh=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor allowed1' not in dh or 'Editor denied' in dh or 'Editor foreign' in dh or 'Editor other_owner' in dh:
  raise RuntimeError('Administrative course list leaked or omitted native delegated scope')
 # The native Rules field persists through Joomla com_config, with its own ACL and CSRF guards.
 rules_url,rules_html=get('/administrator/index.php?option=com_jlms&task=course.edit&id='+str(editor_fixture['denied']))
 rules_region=re.search(r'<section id="jlms-course-rules">(.*?)</section>',rules_html,re.S)
 if not rules_region or '<joomla-field-permissions' not in rules_region[1]:
  raise RuntimeError('Authorised saved-course editor must render the native Rules field')
 rules_form=next(f for f in Page(rules_html).forms if f['id']=='adminForm')
 rules_endpoint=unescape(re.search(r'data-uri="([^"]+)"',rules_region[1]).group(1))
 permission={'comp':'com_jlms.course.'+str(editor_fixture['denied']),'action':'core.edit',
  'rule':str(editor_fixture['group']),'value':'1','title':'Editor denied'}
 def update_native_rule(client,endpoint,data):
  req=Request(urljoin(rules_url,endpoint),data=json.dumps(data).encode(),headers={'Content-Type':'application/json','Accept':'application/json'})
  try:
   with client.open(req,timeout=20) as response:return json.loads(response.read().decode('utf-8'))
  except HTTPError as error:
   if error.code==403:return {'data':False}
   raise
 # Missing CSRF cannot turn a deny into a grant.
 no_token=urlparse(rules_endpoint)
 clean_endpoint=no_token._replace(query=urlencode({k:v[0] for k,v in parse_qs(no_token.query).items() if not re.fullmatch(r'[a-f0-9]{32}',k)})).geturl()
 if (update_native_rule(http,clean_endpoint,permission).get('data') or {}).get('result'):
  raise RuntimeError('Native permission endpoint accepted missing CSRF')
 if not (update_native_rule(http,rules_endpoint,permission).get('data') or {}).get('result'):
  raise RuntimeError('Native course permission grant failed')
 _,visible=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor denied' not in visible:raise RuntimeError('Record grant must take effect in the next delegated request')
 # Restore inheritance, which grants edit through the category.
 inherited=dict(permission,value='')
 if not (update_native_rule(http,rules_endpoint,inherited).get('data') or {}).get('result'):
  raise RuntimeError('Native inherited rule failed')
 _,visible=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor denied' not in visible:raise RuntimeError('Clearing a record rule must restore category inheritance')
 denied=dict(permission,value='0')
 if not (update_native_rule(http,rules_endpoint,denied).get('data') or {}).get('result'):
  raise RuntimeError('Native record deny failed')
 _,visible=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor denied' in visible:raise RuntimeError('Record deny must take effect in the next delegated request')
 # A user with edit rights but no core.admin cannot grant permission via the same native endpoint.
 _,delegate_edit=delegated_get('/administrator/index.php?option=com_jlms&view=course&layout=edit&id='+str(editor_fixture['allowed']))
 if 'id="jlms-course-rules"' in delegate_edit or '<joomla-field-permissions' in delegate_edit:
  raise RuntimeError('The native Rules editor must be hidden from ordinary course editors')
 delegate_edit_form=next(f for f in Page(delegate_edit).forms if f['id']=='adminForm')
 delegate_token=next(k for k in delegate_edit_form['fields'] if re.fullmatch(r'[a-f0-9]{32}',k))
 delegate_endpoint=no_token._replace(query=urlencode({'option':'com_config','task':'application.store','format':'json',delegate_token:'1'})).geturl()
 if (update_native_rule(delegated_http,delegate_endpoint,permission).get('data') or {}).get('result'):
  raise RuntimeError('Course editor escalated record permissions without core.admin')
 _,visible=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor denied' in visible:raise RuntimeError('Unauthorised permission request changed the rule')
 # A standard metadata save must preserve rules already saved by native AJAX.
 fields=dict(rules_form['fields']);fields['task']='course.save'
 with http.open(urljoin(rules_url,rules_form['action']),data=urlencode(fields).encode(),timeout=20) as response:response.read()
 _,visible=delegated_get('/administrator/index.php?option=com_jlms&view=courses')
 if 'Editor denied' in visible:raise RuntimeError('Course metadata save overwrote the native deny')
 print(json.dumps({'course_rules_http_checks':9,'status':'passed'}))
 du,dh=delegated_get('/administrator/index.php?option=com_jlms&task=course.add')
 delegate_form=next(f for f in Page(dh).forms if 'jform[title]' in f['fields'])
 selector=re.search(r'<select[^>]*name="jform\[catid\]"[^>]*>(.*?)</select>',dh,re.S).group(1)
 if 'Editor allowed' not in selector or 'Editor other' in selector or 'Editor trash' in selector or 'id="jlms-course-options-link"' in dh:
  raise RuntimeError('Category-only creation selector or options permissions incorrect')
 fields=dict(delegate_form['fields']);fields.update({'task':'course.apply','jform[title]':'Editor HTTP delegated','jform[alias]':'editor-http-delegated',
  'jform[catid]':str(editor_fixture['allowed_category']),'jform[language]':'*','jform[catalog_access]':'1','jform[content_access]':'2','jform[enrollment_mode]':'auto'})
 with delegated_http.open(urljoin(du,delegate_form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  du=response.geturl();dh=response.read().decode('utf-8')
 delegate_form=next(f for f in Page(dh).forms if 'jform[title]' in f['fields'])
 if int(delegate_form['fields']['jform[id]'])<1 or 'id="jlms-course-effective-rights"' not in dh:
  raise RuntimeError('Delegate could not create through the native controller/form')
 # Submit a forged destination not offered by the native category selector.
 fields=dict(delegate_form['fields']);fields.update({'task':'course.apply','jform[catid]':str(editor_fixture['other_category'])})
 with delegated_http.open(urljoin(du,delegate_form['action']),data=urlencode(fields).encode(),timeout=20) as response:response.read()
 stored=json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('editor-http-fixture.php'))],text=True))
 if int(stored['catid'])!=editor_fixture['allowed_category'] or int(stored['revision'])!=1:
  raise RuntimeError('Forged category mutation changed the saved course')
 # Publish through Joomla using the category grant, with no component-wide state permission.
 du,dh=delegated_get('/administrator/index.php?option=com_jlms&view=curriculum&id='+str(editor_fixture['delegated']))
 publication_form=next(f for f in Page(dh).forms if f['id']=='jlms-publication-form')
 with delegated_http.open(urljoin(du,publication_form['action']),data=urlencode(publication_form['fields']).encode(),timeout=20) as response:
  published_html=response.read().decode('utf-8')
 if 'Before publishing, enter the full description' not in published_html or re.search(r'<strong>Published</strong>',published_html):
  raise RuntimeError('An otherwise valid course without a description must show the specific publication error')
 du,dh=delegated_get('/administrator/index.php?option=com_jlms&task=course.edit&id='+str(editor_fixture['delegated']))
 description_form=next(f for f in Page(dh).forms if 'jform[presentation_description]' in f['fields'])
 fields=dict(description_form['fields']);fields.update({'task':'course.save','jform[presentation_description]':'<p>Delegated course presentation.</p>'})
 with delegated_http.open(urljoin(du,description_form['action']),data=urlencode(fields).encode(),timeout=20) as response:response.read()
 du,dh=delegated_get('/administrator/index.php?option=com_jlms&view=curriculum&id='+str(editor_fixture['delegated']))
 publication_form=next(f for f in Page(dh).forms if f['id']=='jlms-publication-form')
 with delegated_http.open(urljoin(du,publication_form['action']),data=urlencode(publication_form['fields']).encode(),timeout=20) as response:
  published_html=response.read().decode('utf-8')
 print(json.dumps({'publication_description_http_checks':2,'status':'passed'}))
 if not re.search(r'<strong>Published</strong>',published_html):
  raise RuntimeError('First publication failed for the category-scoped publisher')
 print(json.dumps({'scoped_publication_http_checks':1,'status':'passed'}))
 print(json.dumps({'editor_access_http_checks':6,'status':'passed'}))

 # Exercise teacher creation through the native administrator form.
 u,h=get('/administrator/index.php?option=com_jlms&view=teachers')
 if 'id="jlms-admin-teachers"' not in h:raise RuntimeError('Native teacher list missing')
 if 'COM_JLMS_TEACHER_FIELDS' in h or 'option=com_fields&amp;view=fields&amp;context=com_jlms.teacher' in h:raise RuntimeError('Teacher custom fields access must be in the sidebar, not the list view')
 u,h=get('/administrator/index.php?option=com_jlms&task=teacher.add')
 teacher_form=next(f for f in Page(h).forms if 'jform[bio]' in f['fields'])
 if 'jform[catid]' in teacher_form['fields']:raise RuntimeError('Teacher creation must not require choosing a category')
 teacher_fields=dict(teacher_form['fields']);teacher_fields.update({'task':'teacher.apply','jform[title]':'HTTP teacher','jform[alias]':'http-teacher',
  'jform[state]':'1','jform[access]':'1','jform[language]':'*','jform[bio]':'HTTP teacher biography <script>plain text</script>',
  'jform[image]':'','jform[image_alt]':'','jform[image_decorative]':'0','jform[website]':'https://example.invalid/http-teacher','jform[user_id]':'0'})
 with http.open(urljoin(u,teacher_form['action']),data=urlencode(teacher_fields).encode(),timeout=20) as response:
  u=response.geturl();h=response.read().decode('utf-8')
 teacher_form=next(f for f in Page(h).forms if 'jform[bio]' in f['fields'])
 http_teacher_id=int(teacher_form['fields']['jform[id]'])
 if http_teacher_id<1 or teacher_form['fields']['jform[title]']!='HTTP teacher':raise RuntimeError('Native teacher save/reopen failed')
 teacher_fields=dict(teacher_form['fields']);teacher_fields['task']='teacher.save'
 with http.open(urljoin(u,teacher_form['action']),data=urlencode(teacher_fields).encode(),timeout=20) as response:
  h=response.read().decode('utf-8')
 if 'HTTP teacher' not in h or 'id="jlms-admin-teachers"' not in h:raise RuntimeError('Native teacher save/close failed')

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
 expect('joomla-field-media' in h and 'jform[presentation_image]' in h,'Native Media Manager field is available in course editor')
 expect(all('id="'+x+'"' in h for x in ['jlms-course-tabs','jlms-course-general','jlms-course-presentation','jlms-course-access-settings','jlms-course-metadata','jlms-course-calendar','jlms-course-publishing','jlms-course-permissions']),'Native course editor exposes general, presentation, access and publication tabs')
 before=fixture()
 u,h=submit(u,form,{'task':'course.apply','jform[calendar_timezone]':'Europe/Rome','jform[calendar_starts_at]':'2027-03-28 02:30:00'})
 expect(fixture()==before,'Native form rejects nonexistent DST time without saving')
 form=next(f for f in Page(h).forms if 'jform[title]' in f['fields'])
 u,h=submit(u,form,{'task':'course.apply','jform[calendar_timezone]':'Europe/Rome',
  'jform[calendar_publish_up]':'2000-01-01 01:00:00','jform[calendar_publish_down]':'2099-01-01 01:00:00',
  'jform[calendar_starts_at]':'2000-01-01 01:00:00','jform[calendar_ends_at]':'2098-01-01 01:00:00','jform[presentation_description]':'<p>HTTP extended <strong>description</strong></p><img src="https://example.invalid/pixel" onerror="alert(1)"><script>alert(2)</script>',
  'jform[presentation_objectives]':'HTTP objectives <script>plain text</script>','jform[presentation_audience]':'HTTP audience',
  'jform[presentation_image]':'images/jlms-ci-cover.png#joomlaImage://local-images/jlms-ci-cover.png?width=1&height=1','jform[presentation_image_alt]':'HTTP cover "quoted" <plain text>','jform[presentation_image_decorative]':'0',
  'jform[teacher_ids][]':str(http_teacher_id),'jform[presentation_seo_title]':'HTTP published SEO title','jform[presentation_seo_description]':'HTTP SEO description & public sheet',
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
 expect('id="jlms-publication-readiness"' in h and 'data-readiness-code="COM_JLMS_READINESS_MODULES_REQUIRED"' in h
  and 'data-readiness-code="COM_JLMS_READINESS_REQUIRED_LESSON"' in h,'Draft publication page shows basic blockers before the user submits')
 before=fixture()
 form=next(f for f in Page(h).forms if f['id']=='jlms-publication-form')
 transition=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
 u,h=submit(u,form,{'jform[transition_id]':transition})
 expect(fixture()==before,'Empty initial programme cannot publish or create an edition')
 form=next(f for f in Page(h).forms if f['id']=='jlms-module-form');u,h=submit(u,form,{'jform[title]':'HTTP first module'})
 lesson_link=next(x for x in Page(h).links if parse_qs(urlparse(x).query).get('new_lesson')==['1'])
 u,h=get(lesson_link)
 expect('data-readiness-code="COM_JLMS_READINESS_MODULE_EMPTY"' in h,'An empty module remains a publication blocker while editing its first lesson')
 form=next(f for f in Page(h).forms if f['id']=='jlms-lesson-form')
 u,h=submit(u,form,{'jform[title]':'HTTP first required lesson','jform[kind]':'text','jform[required]':'1',
  'jform[module_id]':re.search(r'data-module-id="([1-9][0-9]*)"',h).group(1),
  'jform[body]':'<p>FIRST_PUBLICATION_PRIVATE_BODY</p>'})
 expect('HTTP first required lesson' in h,'Module and required text lesson saved through HTTP')
 expect('id="jlms-publication-blockers"' in h and 'COM_JLMS_READINESS_MODULE_EMPTY' not in h
  and 'COM_JLMS_READINESS_REQUIRED_LESSON' not in h,'Adding the required lesson clears structural blockers')
 material=fixture('material-add')
 u,h=get(u)
 expect('data-readiness-code="COM_JLMS_MATERIAL_NOT_READY"' in h,'Draft readiness identifies an attachment that the publication service would reject')
 fixture('material-remove',material['attachment_id'])
 u,h=get(u)
 expect('data-readiness-code="COM_JLMS_MATERIAL_NOT_READY"' not in h,'Removing the unready fixture material clears its readiness blocker')
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
 # Current access rules remain native even when the teaching version is immutable.
 published_editor_url,published_editor=get('/administrator/index.php?option=com_jlms&view=course&layout=edit&id='+str(course_id))
 if 'id="jlms-course-rules"' not in published_editor:
  raise RuntimeError('Authorised administrators must be able to manage current permissions of a published course')
 published_editor_form=next(f for f in Page(published_editor).forms if f['id']=='adminForm')
 if 'jform[title]' not in published_editor_form['fields']:
  raise RuntimeError('Published course editorial fields must be editable')
 print(json.dumps({'published_course_rules_http_checks':1,'status':'passed'}))
 published=fixture()['course']
 expect(int(published['state'])==1 and published['status']=='published' and int(published['editions'])==1 and int(published['associations'])==1,'First HTTP publication freezes version and creates one edition and native association')
 expect(bool(published['structure_hash']) and bool(published['published_at']),'Initial publication records immutable hash and timestamp')
 u,h=submit(u,stale_form,{'jform[transition_id]':transition})
 expect(fixture()['course']==published,'Repeated stale publication does not duplicate edition or change history')
 public_http=build_opener(HTTPCookieProcessor(CookieJar()))
 pathways=json.loads((root.parent/'pathways-fixture.json').read_text())
 _,pathway_admin=get('/administrator/index.php?option=com_jlms&view=pathways')
 expect('id="jlms-admin-pathways"' in pathway_admin and 'CI learning pathway' in pathway_admin,
        'Authenticated administrator sees the native pathway list')
 _,pathway_editor=get('/administrator/index.php?option=com_jlms&task=pathway.edit&id='+str(pathways['pathway']))
 expect('id="jlms-pathway-editor"' in pathway_editor and 'jform[title]' in pathway_editor and 'jform[courses]' in pathway_editor,
        'Authenticated administrator can edit pathway details and course associations')
 with public_http.open(base+'/index.php?option=com_jlms&view=pathways',timeout=15) as response:
  pathway_catalog=response.read().decode('utf-8')
 expect('id="jlms-pathways"' in pathway_catalog and 'CI learning pathway' in pathway_catalog,
        'Public pathway catalog renders published pathways')
 with public_http.open(base+'/index.php?option=com_jlms&view=pathway&id='+str(pathways['pathway']),timeout=15) as response:
  pathway_page=response.read().decode('utf-8')
 expect('id="jlms-pathway"' in pathway_page and 'CI learning pathway' in pathway_page and 'Publication fixture' in pathway_page,
        'Public pathway sheet renders ordered course association')
 expect('id="jlms-pathway-progress-label"' not in pathway_page and 'id="jlms-pathway-course-1"' in pathway_page,
        'Guest pathway page hides personal progress and keeps the unavailable course position')
 expect('Test course' not in pathway_page and 'This course is not currently available.' in pathway_page,
        'Guest pathway page labels the hidden course without revealing its identity')
 with public_http.open(base+'/index.php?option=com_jlms&view=course&id='+str(course_id),timeout=15) as response:
  sheet=response.read().decode('utf-8')
 expect('HTTP first required lesson' in sheet and 'FIRST_PUBLICATION_PRIVATE_BODY' not in sheet,'First published course is public with no lesson body leak')
 expect(all('id="'+x+'"' in sheet for x in ['jlms-course-description','jlms-course-objectives','jlms-course-audience','jlms-course-prerequisite-text','jlms-course-facts','jlms-course-difficulty','jlms-course-estimated-duration']),'Public presentation exposes documented unique regions')
 expect('<strong>description</strong>' in sheet and 'HTTP audience' in sheet and '75 minutes' in sheet,'Frozen presentation is visible on public course sheet')
 expect('example.invalid/pixel' not in sheet and '<script>plain text</script>' not in sheet and '&lt;script&gt;plain text&lt;/script&gt;' in sheet,'Unsafe HTML removed and informational text escaped')
 cover=re.search(r'<div id="jlms-course-cover".*?</div>',sheet,re.S)
 expect(bool(cover) and 'images/jlms-ci-cover.png' in cover.group(0) and 'alt="HTTP cover &quot;quoted&quot; &lt;plain text&gt;"' in cover.group(0),'Public cover uses safe URL and escaped alternative text')
 expect('<title>HTTP published SEO title</title>' in sheet and 'HTTP SEO description &amp; public sheet' in sheet,'Native document head uses the published SEO values')
 expect('#joomlaImage' not in cover.group(0),'Native adapter metadata not emitted in public image URL')
 expect('id="jlms-course-teachers"' in sheet and 'HTTP teacher biography &lt;script&gt;plain text&lt;/script&gt;' in sheet,'Assigned native teacher appears as escaped frozen public profile')
 expect('id="jlms-course-calendar"' in sheet and 'Europe/Rome' in sheet and '2000-01-01 01:00:00 +01:00' in sheet,'Public frozen calendar shows its explicit zone and offset')
 expect(published['calendar_timezone']=='Europe/Rome' and published['calendar_start'].startswith('2000-01-01 00:00:00') and published['edition_end'].startswith('2098-01-01 00:00:00'),'Native calendar form persists UTC and initial edition dates')
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
