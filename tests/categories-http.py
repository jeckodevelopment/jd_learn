"""Real Joomla category views, pagination, language switch and native administration."""
from pathlib import Path
import runpy
verify_dom = runpy.run_path(str(Path(__file__).resolve().parent / 'frontend-dom-runtime.py'))['verify']
from urllib.request import build_opener, HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin, urlencode
from http.cookiejar import CookieJar
from html.parser import HTMLParser
import json, os, re, subprocess, sys, time
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1': raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'categories-fixture.json').read_text())
config=root/'configuration.php'; original=config.read_text(); base='http://127.0.0.1:8093'
log=open(root.parent/'categories-http.log','w+'); server=None; checks=0
class Page(HTMLParser):
 def __init__(self,text):
  self.meta={}
  super().__init__(convert_charrefs=True);self.links=[];self.forms=[];self.form=None;self.anchor=None;self.ids=[];self.head_links=[];self.feed(text)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='link':self.head_links.append(a)
  if tag == 'meta' and 'content' in a:
   self.meta[a.get('property') or a.get('name')] = a['content']
  if 'id' in a:self.ids.append(a['id'])
  if tag=='a' and 'href' in a:self.anchor=a|{'text':''};self.links.append(self.anchor)
  if tag=='form':self.form={'action':a.get('action',''),'fields':{}};self.forms.append(self.form)
  if tag=='input' and self.form is not None and 'name' in a:self.form['fields'][a['name']]=a.get('value','')
 def handle_data(self,data):
  if self.anchor is not None:self.anchor['text']+=data
 def handle_endtag(self,tag):
  if tag=='a':self.anchor=None
  if tag=='form':self.form=None
http=build_opener(HTTPCookieProcessor(CookieJar()))
def get(path):
 with http.open(urljoin(base,path),timeout=20)as r:return r.geturl(),r.read().decode('utf-8')
def fixture(mode):subprocess.run(['php','tests/categories-http-fixture.php',str(root),mode],check=True)
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def denied(path):
 try:get(path)
 except HTTPError as e:return e.code==404
 return False
try:
 fixture('enable')
 server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
 for _ in range(40):
  try:get('/administrator/index.php');break
  except OSError:time.sleep(.2)
 for sef in [0,1]:
  changed,count=re.subn(r'public\s+\$sef\s*=\s*[^;]+;',f'public $sef = {sef};',original)
  if count!=1:raise RuntimeError('SEF fixture missing')
  config.write_text(changed)
  _,catalog_page=get('/index.php?option=com_jlms&view=courses&lang=en')
  expect(verify_dom(catalog_page, root='jlms-courses', scoped_class='jlms-course-card') > 0, 'UI-07: catalog CSS customization and unique IDs verified in real Joomla HTML')
  _,root_page=get('/index.php?option=com_jlms&view=categories&lang=en')
  expect('id="jlms-categories"' in root_page and 'Taxonomy English' in root_page and 'PRIVATE_QUESTION_CATEGORY' not in root_page,'root list scoped and visible')
  expect(verify_dom(root_page, root='jlms-categories', scoped_class='jlms-category-card') > 0, 'UI-07: public category index preserves scoped cards and unique IDs')
  category_link=next(a['href'] for a in Page(root_page).links if a['text'].strip()=='Taxonomy English')
  if sef:expect('taxonomy-menu-category-en' in category_link,'native category menu selected by router')
  _,menu_page=get(category_link)
  expect('id="jlms-category"' in menu_page and 'Taxonomy English' in menu_page,'native category menu opens correct view')
  # CORE-07: both the menu-selected and the legacy numeric category URL
  # must resolve the same accessible content in either Joomla SEF mode.
  en_menu=f['menus']['taxonomy-menu-category-en']
  routed='/index.php?option=com_jlms&view=category&id='+str(f['en'])+'&lang=en&Itemid='+str(en_menu)
  _,routed_page=get(routed)
  expect('id="jlms-category"' in routed_page and 'Taxonomy English' in routed_page,
         'CORE-07: explicit menu Itemid selects the authorised English category')
  url,page=get('/index.php?option=com_jlms&view=category&id='+str(f['en'])+'&lang=en')
  parsed=Page(page)
  canonical_links=[link.get('href','') for link in parsed.head_links if link.get('rel')=='canonical']
  expect(len(canonical_links)==1 and canonical_links[0].startswith(base), 'CAT-04 public category canonical must be unique and absolute')
  expect(parsed.meta.get('og:url')==canonical_links[0], f'CAT-04 public OG mismatch: canonical={canonical_links!r}, og={parsed.meta.get("og:url")!r}, sef={sef}')
  expect(parsed.meta.get('og:title') and parsed.meta.get('twitter:title'), 'CAT-04 public category social titles missing')
  expect(verify_dom(page, root='jlms-category', scoped_class='jlms-course-card') > 0, 'UI-07: category course cards preserve scoped CSS contract and unique IDs')
  if sef:
   canonical=next((link.get('href','') for link in parsed.head_links if link.get('rel')=='canonical'),'')
   expect('taxonomy-en' in canonical or 'taxonomy-menu-category-en' in canonical,'legacy numeric category URL publishes its category or dedicated menu alias as canonical')
  expect(all(x in parsed.ids for x in ['jlms-category','jlms-category-description','jlms-subcategories','jlms-category-courses']),'category CSS regions present')
  expect(len(parsed.ids)==len(set(parsed.ids)),'public view has no duplicate IDs')
  expect('taxonomy-visible-course' in page and 'PRIVATE_CATEGORY_LESSON' not in page and 'HIDDEN_TAXONOMY_' not in page and 'WRONG_LANGUAGE_TAXONOMY_CHILD' not in page,'course cards and children obey access/language without protected bodies')
  if sef:
   course_link=next((a['href'] for a in parsed.links if 'taxonomy-visible-course' in a['text'].lower().replace(' ','-')), '')
   expect('taxonomy-visible-course' in course_link,'public course link uses its alias segment')
  expect('<script>UNSAFE_CATEGORY_SCRIPT' not in page,'native description rendered as escaped text')
  next_link=next((a['href'] for a in parsed.links if 'subcategories_limitstart=20' in a['href']),None)
  expect(next_link is not None,'child pagination uses independent native prefix')
  _,second=get(next_link)
  expect('id="jlms-subcategories"' in second and 'taxonomy-visible-course' in second,'second child page preserves courses')
  italian=next(a['href']for a in parsed.links if a['text'].strip().startswith('Italiano'))
  _,translated=get(italian)
  expect('id="jlms-category"' in translated and 'Tassonomia italiana' in translated and 'taxonomy-corso-italiano' in translated,'core language switch resolves translated category')
  translated_page=Page(translated)
  translated_canonical=[link.get('href','') for link in translated_page.head_links if link.get('rel')=='canonical']
  expect(translated_page.meta.get('og:url', '').startswith(base), 'CAT-04 translated public category must have an absolute OG URL')
  if translated_canonical:
   expect(len(translated_canonical)==1 and translated_canonical[0]==translated_page.meta['og:url'], 'CAT-04 translated canonical disagrees with Open Graph URL')
  else:
   # Joomla's native multilingual system can replace canonical with hreflang alternate links.
   expect(any(link.get('rel')=='alternate' and link.get('hreflang')=='it-IT' and link.get('href')==translated_page.meta['og:url'] for link in translated_page.head_links), 'CAT-04 translated Joomla alternate must identify the Open Graph URL')
  expect(translated_page.meta.get('og:title') and translated_page.meta.get('twitter:title'), 'CAT-04 translated category social titles absent')
  if sef:expect('taxonomy-menu-category-it' in italian,'native target-language category menu selected')
  fixture('hide_target')
  _,hidden=get('/index.php?option=com_jlms&view=category&id='+str(f['en'])+'&lang=en')
  fallback=next(a['href']for a in Page(hidden).links if a['text'].strip().startswith('Italiano'))
  _,fallback_page=get(fallback)
  expect('id="jlms-courses"' in fallback_page and 'id="jlms-category"' not in fallback_page,'hidden translation uses native language home fallback')
  fixture('restore_target')
  fixture('hide_parent')
  expect(denied('/index.php?option=com_jlms&view=category&id='+str(f['child'])+'&lang=en'),'restricted ancestor denies direct category URL')
  expect(denied(routed), 'CORE-03/07: revoked ancestor denies menu-selected category URL')
  expect(denied('/index.php?option=com_jlms&view=course&id='+str(f['course'])+'&lang=en'),
         'CORE-03/07: ancestor ACL revocation denies direct course URL despite public alias')
  expect(denied('/index.php?option=com_jlms&view=categories&parent_id='+str(f['child'])+'&lang=en'),'restricted ancestor denies child-index URL')
  fixture('restore_parent')
  _,restored_menu=get(routed)
  expect('id="jlms-category"' in restored_menu and 'Taxonomy English' in restored_menu,
         'CORE-03/07: menu route reopens immediately on ancestor ACL restoration')
  expect(denied('/index.php?option=com_jlms&view=category&id='+str(f['otherSection'])+'&lang=en'),'question category cannot be read via course view')
 url,html=get('/administrator/index.php')
 login=next(x for x in Page(html).forms if 'username'in x['fields'])
 fields=login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'}
 with http.open(urljoin(url,login['action']),data=urlencode(fields).encode(),timeout=20)as r:r.read()
 _,html=get('/administrator/index.php?option=com_associations&view=associations&itemtype=com_jlms.course.category&language=en-GB')
 expect('Taxonomy English' in html and 'PRIVATE_QUESTION_CATEGORY' not in html,'native association manager scopes category section')
 _,menu_html=get('/administrator/index.php?option=com_menus&task=item.edit&id='+str(f['menus']['taxonomy-menu-category-en']))
 expect('jform[request][id]' in menu_html,'native menu manager loads category request field from extension metadata')
 _,html=get('/administrator/index.php?option=com_categories&task=category.edit&id='+str(f['en'])+'&extension=com_jlms.course')
 expect('jform[associations][it-IT]' in html,'native category editor owns translation controls')
 print(json.dumps({'category_http_checks':checks,'sef_modes':[0,1],'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-12000:],file=sys.stderr);raise
finally:
 config.write_text(original)
 if server is not None:
  server.terminate()
  try:server.wait(timeout=5)
  except subprocess.TimeoutExpired:server.kill();server.wait()
 fixture('disable');log.close()
