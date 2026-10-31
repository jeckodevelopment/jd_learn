"""Exercise real public/admin rendering in an isolated Joomla fixture, without a browser dependency."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.parse import urlencode,urljoin,urlparse,parse_qs
from http.cookiejar import CookieJar
import subprocess,sys,time,json,os,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.links=[];self.base=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base' and a.get('href'):self.base=a['href']
  if tag=='form':self.current={'id':a.get('id',''),'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='a' and a.get('href'):self.links.append(a['href'])
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
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
 with http.open(urljoin(base,path),timeout=15) as r:return r.geturl(),r.read().decode('utf-8')
try:
 for i in range(40):
  try:url,html=get('/index.php?option=com_jlms&view=courses');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('Test HTTP server exited')
   time.sleep(.2)
 else:raise RuntimeError('Test HTTP server did not become ready')
 if 'id="jlms-courses"' not in html:raise RuntimeError('Public course view did not render')
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

 print(json.dumps({'http_smoke':'passed','pages':['public catalogue','native admin login','dashboard','draft list','draft editor']}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
