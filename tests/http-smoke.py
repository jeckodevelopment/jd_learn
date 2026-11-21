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
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.links=[];self.anchors=[];self.base=None;self.textarea=None;self.select=None;self.feed(html)
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
  if tag=='a' and a.get('href'):
   self.links.append(a['href']);self.anchors.append(a)
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
 if 'id="jlms-catalog-filters"' not in html or 'name="filter[search]"' not in html or 'name="filter[order]"' not in html or 'name="filter[tag_id]"' not in html or 'name="filter[teacher_id]"' not in html or 'name="filter[pathway_id]"' not in html:
  raise RuntimeError('Public catalog search, order and tag controls did not render')
 _,all_tag_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[tag_id]':'0'}))
 if 'Publication fixture' not in all_tag_html:
  raise RuntimeError('The all-tags selection must preserve the unfiltered public catalog')
 _,invalid_tag_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[tag_id]':'2147483647'}))
 if 'Publication fixture' in invalid_tag_html:
  raise RuntimeError('An unknown tag must not broaden the public catalog result')
 if 'id="jlms-catalog-filters"' not in html or 'name="filter[search]"' not in html or 'name="filter[order]"' not in html or 'name="filter[difficulty]"' not in html or 'name="filter[duration]"' not in html or 'name="filter[layout]"' not in html or 'data-layout="grid"' not in html:
  raise RuntimeError('Public catalog search, order and core filters did not render')
 _,list_layout=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[layout]':'list'}))
 if 'data-layout="list"' not in list_layout or 'jlms-course-grid--list' not in list_layout:
  raise RuntimeError('Public catalog list layout did not render')
 _,invalid_layout=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[layout]':'unexpected'}))
 if 'data-layout="grid"' not in invalid_layout:
  raise RuntimeError('Unsupported catalog layouts must fall back to the grid')
 search_url,search_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[search]':'Publication fixture','filter[order]':'title'}))
 if 'Publication fixture' not in search_html or 'value="title" selected' not in search_html:
  raise RuntimeError('Public catalog title/summary search and title ordering did not apply')
 _,filtered_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[difficulty]':'beginner','filter[duration]':'30-60'}))
 if 'Publication fixture' not in filtered_html or 'value="beginner" selected' not in filtered_html or 'value="30-60" selected' not in filtered_html:
  raise RuntimeError('Public catalog difficulty and estimated-duration filters did not apply')
 _,excluded_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[difficulty]':'advanced'}))
 if 'Publication fixture' in excluded_html:
  raise RuntimeError('Public catalog difficulty filter included a non-matching course')
 _,duration_excluded_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[duration]':'over-120'}))
 if 'Publication fixture' in duration_excluded_html:
  raise RuntimeError('Public catalog duration filter included a non-matching course')
 _,private_term_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[search]':'SECRET_LESSON_BODY'}))
 if 'Publication fixture' in private_term_html:
  raise RuntimeError('Catalog search must not search protected lesson bodies')
 category_url=next((x for x in Page(html).links if parse_qs(urlparse(x).query).get('view')==['category']),None)
 if category_url:
  category_query=urlencode({'filter[search]':'Publication fixture','filter[order]':'title','filter[difficulty]':'beginner','filter[duration]':'30-60'})
  _,category_search=get(category_url+('&' if '?' in category_url else '?')+category_query)
  if 'Publication fixture' not in category_search or 'id="jlms-category-catalog-filters"' not in category_search or 'name="filter[tag_id]"' not in category_search:
   raise RuntimeError('Category-scoped search and tag controls did not preserve their category')
  if 'Publication fixture' not in category_search or 'id="jlms-category-catalog-filters"' not in category_search or 'value="beginner" selected' not in category_search or 'value="30-60" selected' not in category_search:
   raise RuntimeError('Category-scoped catalog search did not preserve its category')
  _,category_list=get(category_url+('&' if '?' in category_url else '?')+urlencode({'filter[layout]':'list'}))
  if 'data-layout="list"' not in category_list or 'jlms-course-grid--list' not in category_list:
   raise RuntimeError('Category course list layout did not render')
 # SCR-01: HTTP-level URL persistence and filter/pagination isolation.
 # The one-course fixture verifies Joomla's native out-of-range offset clamp;
 # a fresh GET filter submission must still start from the first valid page.
 course_path='/index.php?option=com_jlms&view=courses'
 selected_filters={'filter[search]':'Publication fixture',
                   'filter[order]':'title',
                   'filter[difficulty]':'beginner',
                   'filter[duration]':'30-60',
                   'filter[layout]':'list'}
 active_url=course_path+'&'+urlencode(selected_filters)
 active_final,active_html=get(active_url)
 active_form=next((x for x in Page(active_html).forms if x['id']=='jlms-catalog-filters'),None)
 if not active_form or any(active_form['fields'].get(k)!=v for k,v in selected_filters.items()):
  raise RuntimeError('SCR-01: a sharable catalogue URL did not hydrate every selected filter')
 if 'Publication fixture' not in active_html or 'data-layout="list"' not in active_html:
  raise RuntimeError('SCR-01: combined URL filters lost a visible matching course')
 # Simulate history back, forward and reload as separate GETs to the exact URL.
 for revisit in (course_path, active_final, course_path, active_final):
  _,revisited=get(revisit)
  selected=revisit==active_final
  if selected and ('value="title" selected' not in revisited or 'data-layout="list"' not in revisited):
   raise RuntimeError('SCR-01: revisiting filter URL did not restore selected filters')
  if not selected and ('data-layout="grid"' not in revisited or 'value="title" selected' in revisited):
   raise RuntimeError('SCR-01: catalogue filter state leaked into the unfiltered URL')
 # Changing search after requesting the second page must not replay the old offset.
 _,later_page=get(active_url+'&limitstart=12')
 if 'id="jlms-course-card-' not in later_page:
  raise RuntimeError('SCR-01: Joomla did not clamp an out-of-range catalogue offset to the only page')
 submitted=dict(active_form['fields'])
 submitted['filter[search]']='Publication fixture'
 submitted.pop('limitstart',None)
 _,reset_page=get(course_path+'&'+urlencode(submitted))
 if 'Publication fixture' not in reset_page or 'id="jlms-course-card-' not in reset_page:
  raise RuntimeError('SCR-01: GET filter submit failed to reset page offset to zero')
 if 'limitstart' in dict(parse_qs(urlparse(active_final).query)):
  raise RuntimeError('SCR-01: first-page URL unexpectedly carries a pagination offset')
 # Category filter forms must preserve their own scope, expose every supported
 # sort and restore state through URL navigation independently of the catalog.
 _,categories_index=get('/index.php?option=com_jlms&view=categories')
 # Joomla may rewrite category anchors to SEF paths; use the public
 # card's category ID to build an explicit component route for this fixture.
 category_card=re.search(r'id="jlms-category-card-([0-9]+)"',categories_index)
 if not category_card:
  raise RuntimeError('SCR-01: public fixture category card is missing')
 cat_url='/index.php?option=com_jlms&view=category&id='+category_card.group(1)
 cat_params={'filter[search]':'Publication fixture','filter[order]':'published',
             'filter[difficulty]':'beginner','filter[duration]':'30-60',
             'filter[layout]':'list'}
 cat_request=cat_url+('&' if '?' in cat_url else '?')+urlencode(cat_params)
 cat_final,cat_filtered=get(cat_request)
 cat_form=next((x for x in Page(cat_filtered).forms if x['id']=='jlms-category-catalog-filters'),None)
 if not cat_form or any(cat_form['fields'].get(k)!=v for k,v in cat_params.items()):
  raise RuntimeError('SCR-01: category URL did not hydrate the supported filters')
 if 'Publication fixture' not in cat_filtered or 'data-layout="list"' not in cat_filtered:
  raise RuntimeError('SCR-01: filtered public category did not include its matching course')
 if 'name="filter[available]"' not in cat_filtered:
  raise RuntimeError('SCR-01: category availability filter was not rendered')
 for value in ('editorial','recent','title','duration','published'):
  if 'value="'+value+'"' not in cat_filtered.split('id="jlms-category-catalog-order"',1)[1].split('</select>',1)[0]:
   raise RuntimeError('SCR-01: category ordering option missing: '+value)
 _,cat_second=get(cat_request+'&limitstart=12')
 if 'id="jlms-course-card-' not in cat_second:
  raise RuntimeError('SCR-01: Joomla did not clamp an out-of-range category offset to the only page')
 _,cat_reset=get(cat_url+('&' if '?' in cat_url else '?')+urlencode(cat_form['fields']))
 if 'id="jlms-course-card-' not in cat_reset:
  raise RuntimeError('SCR-01: category GET filter submit did not reset course paging')
 _,cat_revisited=get(cat_final)
 if 'value="published" selected' not in cat_revisited or 'data-layout="list"' not in cat_revisited:
  raise RuntimeError('SCR-01: category URL navigation lost its selected state')
 # No-result clear action must preserve category identity but discard all filters.
 _,cat_empty=get(cat_url+('&' if '?' in cat_url else '?')+urlencode({'filter[search]':'___unmatched_ci_term___'}))
 clear_tag=re.search(r'id="jlms-category-clear-filters"[^>]*>\s*<a href="([^"]+)"',cat_empty)
 if not clear_tag:
  raise RuntimeError('SCR-01: category empty-state reset link missing')
 clear_url=unescape(clear_tag.group(1))
 _,cat_cleared=get(clear_url)
 if 'id="jlms-course-card-' not in cat_cleared or '___unmatched_ci_term___' in cat_cleared:
  raise RuntimeError('SCR-01: category clear-filters link lost context or retained stale search')
 print(json.dumps({'scr01_http_catalog_filter_roundtrip':4,
                   'scr01_http_category_filter_roundtrip':5,
                   'scr01_pagination_reset':2,
                   'scope':'disposable Joomla HTTP requests; not browser history automation or manual acceptance',
                   'status':'passed'}))
 _,invalid_filter_html=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[difficulty]':'beginner\' OR 1=1 --','filter[duration]':'0 OR 1=1'}))
 if 'Publication fixture' not in invalid_filter_html:
  raise RuntimeError('Invalid catalog filter values must be ignored safely')
 course_links=[x for x in Page(html).links if parse_qs(urlparse(x).query).get('view')==['course'] or '/course/' in x]
 if not course_links:raise RuntimeError('Public course link missing')
 for public_course_url in course_links:
  _,detail=get(public_course_url)
  if 'id=\"jlms-course-outline\"' in detail and 'Publication fixture' in detail:break
 else:raise RuntimeError('Publication fixture sheet missing')
 if 'id="jlms-course-outline"' not in detail or 'Published lesson' not in detail or 'SECRET_LESSON_BODY' in detail:
  raise RuntimeError('Public sheet must show outline without protected lesson body')
 if 'Undefined property: stdClass::$catalog_access' in detail or 'Warning:' in detail:
  raise RuntimeError('Public course detail must render without PHP warnings about catalog access metadata')
 # FE-12: request the real installed Joomla public frontend rather than a static DOM fixture.
 # These tests intentionally do not certify manual responsive/WCAG acceptance.
 for view, root_id, element in (
     ('categories', 'jlms-categories', 'jlms-categories-list'),
     ('pathways', 'jlms-pathways', 'jlms-pathway-grid'),
     ('teachers', 'jlms-teachers', 'jlms-teacher-grid'),
 ):
  public_url,public_html=get('/index.php?option=com_jlms&view='+view)
  if 'id="'+root_id+'"' not in public_html or 'id="'+element+'"' not in public_html:
   raise RuntimeError('FE-12 public '+view+' view did not render its native DOM')
  if '<h1' not in public_html:
   raise RuntimeError('FE-12 public '+view+' view lacks a page heading')
  if not re.search(r'(?:/media/com_jlms/css/site(?:[.]min)?[.]css|com_jlms/site(?:[.]min)?[.]css)', public_html):
   raise RuntimeError('FE-12 public '+view+' view did not load the Joomla WAM stylesheet')
  if 'SECRET_LESSON_BODY' in public_html:
   raise RuntimeError('FE-12 public '+view+' leaked protected lesson content')
 print(json.dumps({'fe12_real_joomla_public_views':3,
                   'checks':['DOM','heading','WAM stylesheet','protected lesson exclusion'],
                   'status':'passed','scope':'real disposable Joomla HTTP, not visual or manual acceptance'}))
 url,html=get('/administrator/index.php')
 page=Page(html)
 form=next((f for f in page.forms if 'username' in f['fields'] and 'passwd' in f['fields']),None)
 if not form:raise RuntimeError('Native Joomla admin login form was not found')
 fields=form['fields'];fields.update(username='cioperator',passwd='Ci-only-Long-Password-2026')
 with http.open(urljoin(urljoin(url,page.base or ''),form['action']),data=urlencode(fields).encode(),timeout=20) as r:r.read()
 options_url,options_html=get('/administrator/index.php?option=com_config&view=component&component=com_jlms')
 options_title=re.search(r'<title>(.*?)</title>',options_html,re.S)
 if not options_title or 'JD Learn: Options' not in unescape(options_title.group(1)) or 'com_jlms_configuration' in options_html:
  actual_title=unescape(options_title.group(1)) if options_title else '(missing <title>)'
  raise RuntimeError('JD Learn component options must have the translated browser title; title='+actual_title)
 if 'JGLOBAL_HISTORY_OPTIONS' in options_html or options_html.count('Version History Options')<2:
  raise RuntimeError('Component history tab and group must use the translated label')
 print(json.dumps({'component_options_translation_http_checks':2,'status':'passed'}))
 url,html=get('/administrator/index.php?option=com_jlms')
 if 'id="jlms-admin-dashboard"' not in html:raise RuntimeError('JD Learn must open the administrator dashboard directly')
 if len(re.findall(r'<h1\b',html))!=1:raise RuntimeError('Dashboard title appears once in the Joomla toolbar')
 menu_links=[parse_qs(urlparse(x).query) for x in Page(html).links]
 for view in ['courses','teachers','questionbank']:
  if not any(q.get('option')==['com_jlms'] and q.get('view')==[view] for q in menu_links):raise RuntimeError('Native JD Learn sidebar link missing: '+view)
 if not any(q.get('option')==['com_categories'] and q.get('extension')==['com_jlms.course'] for q in menu_links):raise RuntimeError('Native course category sidebar missing')
 custom_fields_link=next((x for x in Page(html).links if parse_qs(urlparse(x).query).get('option')==['com_fields'] and parse_qs(urlparse(x).query).get('view')==['fields'] and parse_qs(urlparse(x).query).get('context')==['com_jlms.course']),None)
 if not custom_fields_link:raise RuntimeError('Native custom fields sidebar link missing')
 fields_url,fields_html=get(urljoin(url,custom_fields_link))
 contexts={'course':('Courses','Course context fixture'),
           'teacher':('Teachers','Teacher context fixture'),
           'lesson':('Lessons','Lesson reference')}
 for selected in ('course','teacher','lesson','course','lesson','teacher'):
  context_filter=re.search(r'<select[^>]+name="context"[^>]*>(.*?)</select>',fields_html,re.S)
  if not context_filter:raise RuntimeError('Native custom fields context selector missing')
  options={value:unescape(label).strip() for value,label in re.findall(
   r'<option\b[^>]*value="([^"]+)"[^>]*>([^<]*)</option>',context_filter.group(1))}
  if options!={'com_jlms.'+key:labels[0] for key,labels in contexts.items()}:
   raise RuntimeError('Custom fields label/context mismatch: '+str(options))
  form=next(f for f in Page(fields_html).forms if f['id']=='adminForm')
  expected='com_jlms.'+selected
  with http.open(urljoin(fields_url,form['action']),data=urlencode(form['fields']|{'context':expected}).encode(),timeout=20) as response:
   fields_url=response.geturl()
   fields_html=response.read().decode('utf-8')
  if parse_qs(urlparse(fields_url).query).get('context')!=[expected]:
   raise RuntimeError('Context switch did not navigate to '+expected)
  selected_form=next(f for f in Page(fields_html).forms if f['id']=='adminForm')
  if selected_form['fields'].get('context')!=expected:
   raise RuntimeError('Context selector did not retain '+expected)
  if any((marker in fields_html)!=(key==selected) for key,(_,marker) in contexts.items()):
   raise RuntimeError('Native field rows do not match '+expected)
 print(json.dumps({'custom_fields_context_switches':6,'status':'passed'}))
 plugin_link=next((x for x in Page(html).links if parse_qs(urlparse(x).query).get('option')==['com_plugins'] and parse_qs(urlparse(x).query).get('filter[folder]')==['jlmsvideo']),None)
 if not plugin_link:raise RuntimeError('Native video plugin manager dashboard link missing')
 dashboard_page=Page(html)
 dashboard_shortcuts={a.get('id'):parse_qs(urlparse(a.get('href','')).query) for a in dashboard_page.anchors if a.get('id','').startswith('jlms-admin-shortcut-')}
 if any('&amp;' in a.get('href','') for a in dashboard_page.anchors if a.get('id','').startswith('jlms-admin-shortcut-')):raise RuntimeError('Dashboard shortcut href must not contain a literal HTML entity')
 required_shortcuts={
  'jlms-admin-shortcut-certificates':('com_jlms','certificates'),
  'jlms-admin-shortcut-courses':('com_jlms','courses'),
  'jlms-admin-shortcut-mail_queue':('com_jlms','mailqueue'),
  'jlms-admin-shortcut-dashboard_mail_variables':('com_jlms','mailvariables'),
  'jlms-admin-shortcut-mail_templates':('com_mails','templates'),
  'jlms-admin-shortcut-pathways':('com_jlms','pathways'),
  'jlms-admin-shortcut-course_categories':('com_categories','categories'),
  'jlms-admin-shortcut-pathway_categories':('com_categories','categories'),
  'jlms-admin-shortcut-question_bank':('com_jlms','questionbank'),
  'jlms-admin-shortcut-teachers':('com_jlms','teachers'),
  'jlms-admin-shortcut-custom_fields':('com_fields','fields'),
  'jlms-admin-shortcut-video_plugins':('com_plugins','plugins'),
  'jlms-admin-shortcut-dashboard_category_dependencies':('com_jlms','categoryreassignment'),
 }
 expected_shortcut_order=['courses','pathways','course_categories','pathway_categories','teachers','question_bank','certificates','mail_queue','mail_templates','dashboard_mail_variables','custom_fields','video_plugins','dashboard_category_dependencies']
 actual_shortcut_order=[identifier.removeprefix('jlms-admin-shortcut-') for identifier in dashboard_shortcuts]
 if actual_shortcut_order!=expected_shortcut_order:raise RuntimeError('Dashboard shortcuts must follow the agreed operational order: '+json.dumps(actual_shortcut_order))
 # The icon for category dependencies must use a Joomla icon class, not a
 # non-existent icon-folder-tree selector (#394).
 if not re.search(r'id="jlms-admin-shortcut-dashboard_category_dependencies"[^>]*>\s*<span class="jlms-admin-dashboard__shortcut-icon icon-folder"', html):
  raise RuntimeError('Category dependency shortcut must render Joomla icon-folder (#394)')
 for shortcut,(option,view) in required_shortcuts.items():
  query=dashboard_shortcuts.get(shortcut)
  if not query or query.get('option')!=[option] or query.get('view')!=[view]:
   found={a.get('id'):a.get('href') for a in dashboard_page.anchors if a.get('id','').startswith('jlms-admin-shortcut-')}
   raise RuntimeError('Dashboard shortcut missing or misrouted: '+shortcut+'; found='+json.dumps(found,sort_keys=True))
 # The standalone guide is available by navigation and does not clutter mail queue monitoring.
 guide_url=next(a.get('href') for a in dashboard_page.anchors if a.get('id')=='jlms-admin-shortcut-dashboard_mail_variables')
 _,guide_html=get(urljoin(url,guide_url))
 if 'jlms-mail-variable-guide' not in guide_html or 'COURSE_TITLE' not in guide_html:
  raise RuntimeError('JD Learn standalone variable guide did not render')
 _,queue_html=get('/administrator/index.php?option=com_jlms&view=mailqueue')
 if 'jlms-mail-variable-guide' in queue_html:raise RuntimeError('Operational mail queue must not contain variable guide')
 # The native Mail Templates filter must keep JD Learn in focus without changing com_mails.
 mail_query=dashboard_shortcuts['jlms-admin-shortcut-mail_templates']
 if mail_query.get('filter[extension]')!=['com_jlms']:raise RuntimeError('Mail Templates shortcut must select the JD Learn extension')
 mail_template_url=next(a.get('href') for a in dashboard_page.anchors if a.get('id')=='jlms-admin-shortcut-mail_templates')
 _,mail_template_html=get(urljoin(url,mail_template_url))
 if '<form' not in mail_template_html or 'com_mails' not in mail_template_html:raise RuntimeError('Native Joomla Mail Templates did not render from JD Learn dashboard')
 if 'Manage courses, the learning programme and teaching resources from the administrator panel.' not in html or 'Your learning history and current classroom availability are shown separately.' in html:
  raise RuntimeError('Administrator dashboard introduction must use its current, relevant translation')
 icon_count=len(re.findall(r'class="[^"]*jlms-admin-dashboard__shortcut-icon[^"]*(?:icon-[a-z0-9-]+|fa-[a-z0-9-]+)[^"]+"',html))
 if icon_count!=len(dashboard_shortcuts):raise RuntimeError('Every visible dashboard shortcut must include a Joomla icon')
 provider_rows=re.findall(r'<li data-jlms-provider="([^"]+)">',html)
 if provider_rows!=['youtube','vimeo','cloudflarestream','html5']:raise RuntimeError('Dashboard must show all bundled providers in a stable order: '+json.dumps(provider_rows))
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
 if 'jform[com_fields][lesson-reference]' not in lesson_form['fields']:
  raise RuntimeError('The Joomla lesson field must render in the curriculum form')
 fields=dict(lesson_form['fields']);fields.update({'jform[title]':'HTTP text lesson','jform[kind]':'text','jform[required]':'1',
  'jform[module_id]':re.search(r'data-module-id="([1-9][0-9]*)"',result).group(1),'jform[body]':'<p>Content saved through the native form</p>'})
 with http.open(urljoin(url,lesson_form['action']),data=urlencode(fields).encode(),timeout=20) as response:
  result=response.read().decode('utf-8')
 if 'HTTP text lesson' not in result:raise RuntimeError('Lesson was not saved through the authenticated MVC form')
 preview_link=next(link for link in Page(result).links if parse_qs(urlparse(link).query).get('view')==['coursepreview'])
 preview_url,preview=get(preview_link)
 if 'id="jlms-course-preview"' not in preview or 'HTTP text lesson' not in preview:
  raise RuntimeError('Program link must open the correct editorial preview')
 if 'id="jlms-course-access-preview"' not in preview or 'name="access_levels[]"' not in preview or 'satisfy course access requirements' not in preview:
  raise RuntimeError('Editorial preview must provide a read-only simulation of catalogue and content access levels')
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
 # Render the editor toolbar over authenticated Joomla HTTP; source-level wiring alone missed #384.
 def assert_course_save_toolbar(path, label):
  _,editor_html=get(path)
  visible=unescape(editor_html)
  expected_tasks=['task="course.apply"','task="course.save"','task="course.programme"']
  if not all(task in editor_html for task in expected_tasks):
   raise RuntimeError(label+' course toolbar is missing a save task: '+str([task for task in expected_tasks if task not in editor_html]))
  task_positions=[editor_html.index(task) for task in expected_tasks]
  if task_positions!=sorted(task_positions):
   raise RuntimeError(label+' course save actions are not ordered as standalone Save, primary Save & Close, then programme menu')
  expected_labels=['Save','Save & Close','Save and open curriculum']
  if not all(text in visible for text in expected_labels):
   raise RuntimeError(label+' course toolbar is missing a visible save action: '+str([text for text in expected_labels if text not in visible]))
  if 'JTOOLBAR_SAVE_AND_CLOSE' in visible or 'COM_JLMS_COURSE_SAVE_AND_CLOSE' in visible:
   raise RuntimeError(label+' course toolbar exposes an untranslated save-close key')
 assert_course_save_toolbar('/administrator/index.php?option=com_jlms&view=course&layout=edit&id=0','New')
 assert_course_save_toolbar('/administrator/index.php?option=com_jlms&view=course&layout=edit&id='+str(editor_fixture['allowed']),'Existing')
 print(json.dumps({'course_save_toolbar_http_checks':2,'status':'passed'}))
 # Announcement preview was already implemented, but the main display controller must route this view.
 announcement_id=int(editor_fixture['denied'])
 _,announcement_html=get('/administrator/index.php?option=com_jlms&view=courseannouncement&id='+str(announcement_id))
 if 'Editor denied' not in announcement_html or 'JERROR_LAYOUT_PAGE_NOT_FOUND' in announcement_html:
  raise RuntimeError('Course announcement preview must render for an editable course')
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
 if any(q.get('option')==['com_fields'] and q.get('context',[''])[0].startswith('com_jlms.')
        for q in (parse_qs(urlparse(link).query) for link in Page(dh).links)):
  raise RuntimeError('A delegated editor without Fields management must not see the sidebar link')
 guest_http=build_opener(HTTPCookieProcessor(CookieJar()))
 for section in contexts:
  with guest_http.open(base+'/administrator/index.php?option=com_fields&view=fields&context=com_jlms.'+section,timeout=20) as response:
   if not any('passwd' in form['fields'] for form in Page(response.read().decode('utf-8')).forms):
    raise RuntimeError('Guest access to the native field manager must require Joomla login')
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
 expect(course_id>0 and 'id="jlms-course-programme-link"' not in h and 'task="course.programme"' in h,'Saved draft keeps the curriculum action without a duplicate link')
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
 expect('task="course.programme"' in h,'Saved course retains the curriculum action in its Programme tab')
 programme='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(course_id)
 # Close through the native controller before leaving the editor.
 close_url,close_html=submit(u,form,{'task':'course.save'})
 expect(parse_qs(urlparse(close_url).query).get('view')==['courses'],'Native save and close succeeds with all rendered controls')
 u,h=get(programme)
 expect('id="jlms-publication-notice"' in h and 'id="jlms-version-form"' in h,'Course without version explains the next step')
 form=next(f for f in Page(h).forms if f['id']=='jlms-version-form');u,h=submit(u,form)
 expect('id="jlms-publication-form"' in h and re.search(r'id="jlms-publication-submit"[^>]*>\s*Execute transition\s*</button>',h)
  and 'Send for review' in h and 'Publish' in h,'First draft exposes explicit review and authorized publish transitions')
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
 expect(len(re.findall(r'<h1\b',pathway_admin))==1,'Pathways title appears once in the Joomla toolbar')
 expect('id="jlms-admin-pathways"' in pathway_admin and 'CI learning pathway' in pathway_admin,
        'Authenticated administrator sees the native pathway list')
 # LCM-05: exercise the actual Joomla administrator HTTP controller guards.
 # Invalid requests must fail before any state-changing model call.
 pathway_id=int(pathways['pathway'])
 lifecycle_tasks=('trash','restore','archive','publish','unpublish','purge')
 for lifecycle_task in lifecycle_tasks:
  task_url=base+'/administrator/index.php?'+urlencode({'option':'com_jlms','task':'pathway.'+lifecycle_task})
  try:
   with http.open(Request(task_url,method='GET'),timeout=15) as response:
    response.read()
  except HTTPError as error:
   expect(error.code==405,'Pathway '+lifecycle_task+' GET must be rejected with HTTP 405')
  else:
   raise RuntimeError('Pathway '+lifecycle_task+' accepted a GET request')
  try:
   with http.open(Request(task_url,data=urlencode({'cid[]':str(pathway_id)}).encode(),method='POST'),timeout=15) as response:
    response.read()
  except HTTPError as error:
   expect(error.code==403,'Pathway '+lifecycle_task+' POST without CSRF token must return HTTP 403')
  else:
   raise RuntimeError('Pathway '+lifecycle_task+' accepted POST without CSRF token')
 expect('CI learning pathway' in get('/administrator/index.php?option=com_jlms&view=pathways')[1],
        'Rejected lifecycle requests preserve the pathway admin listing')
 print(json.dumps({'pathway_lifecycle_http_guards':12,'status':'passed'}))
 # LCM-05: with the native form token, the administrator must be able to
 # archive, conservatively restore and explicitly republish the same pathway.
 admin_forms=Page(pathway_admin).forms
 pathway_form=next((form for form in admin_forms if form['id']=='adminForm'),None)
 expect(pathway_form is not None,'Native pathway list supplies an administration form')
 csrf_field=next((key for key,value in pathway_form['fields'].items()
                  if re.fullmatch(r'[0-9a-f]{32}',key) and value=='1'),None)
 expect(csrf_field is not None,'Native pathway form exposes a session CSRF token')
 def pathway_transition(task):
  endpoint=base+'/administrator/index.php?'+urlencode({'option':'com_jlms','task':'pathway.'+task})
  body=urlencode([(csrf_field,'1'),('cid[]',str(pathway_id))]).encode()
  with http.open(Request(endpoint,data=body,method='POST'),timeout=20) as response:
   response.read()
 def pathway_public_visible():
  with public_http.open(base+'/index.php?option=com_jlms&view=pathways',timeout=15) as response:
   return 'CI learning pathway' in response.read().decode('utf-8')
 pathway_transition('archive')
 expect(not pathway_public_visible(),'Authenticated HTTP archive hides pathway from public catalogue')
 pathway_transition('restore')
 expect(not pathway_public_visible(),'Authenticated HTTP restore does not automatically republish pathway')
 pathway_transition('publish')
 expect(pathway_public_visible(),'Explicit authenticated HTTP publish restores pathway visibility')
 print(json.dumps({'pathway_lifecycle_authorized_http_checks':5,'status':'passed'}))
 _,pathway_editor=get('/administrator/index.php?option=com_jlms&task=pathway.edit&id='+str(pathways['pathway']))
 expect('id="jlms-pathway-editor"' in pathway_editor and 'jform[title]' in pathway_editor and 'jform[courses]' in pathway_editor,
        'Authenticated administrator can edit pathway details and course associations')
 with public_http.open(base+'/index.php?option=com_jlms&view=pathways',timeout=15) as response:
  pathway_catalog=response.read().decode('utf-8')
 expect('id="jlms-pathways"' in pathway_catalog and 'CI learning pathway' in pathway_catalog,
        'Public pathway catalog renders published pathways')
 _,pathway_filtered=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[pathway_id]':str(pathways['pathway'])}))
 if 'Publication fixture' not in pathway_filtered or 'value="'+str(pathways['pathway'])+'" selected' not in pathway_filtered:
  raise RuntimeError('Public catalog pathway filter did not preserve visible courses and selection')
 _,teacher_filtered=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[teacher_id]':str(http_teacher_id)}))
 if 'HTTP first publication' not in teacher_filtered or 'value="'+str(http_teacher_id)+'" selected' not in teacher_filtered:
  raise RuntimeError('Public catalog teacher filter did not include the assigned visible course')
 _,invalid_teacher=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[teacher_id]':'2147483647'}))
 if 'HTTP first publication' in invalid_teacher:
  raise RuntimeError('Unknown teacher filter must not broaden the public catalog')
 _,invalid_pathway=get('/index.php?option=com_jlms&view=courses&'+urlencode({'filter[pathway_id]':'2147483647'}))
 if 'Publication fixture' in invalid_pathway:
  raise RuntimeError('Unknown pathway filter must not broaden the public catalog')
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
 expect('id="jlms-course-teachers"' in sheet and 'HTTP teacher biography' in sheet
        and 'HTTP teacher biography &lt;script&gt;plain text&lt;/script&gt;' not in sheet,
        'Assigned native teacher is visible after safe HTML filtering without retaining forbidden script tags')
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

