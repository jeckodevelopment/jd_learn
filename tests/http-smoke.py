"""Exercise real public/admin rendering in an isolated Joomla fixture, without a browser dependency."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.parse import urlencode,urljoin,urlparse,parse_qs
from http.cookiejar import CookieJar
import subprocess,sys,time,json,os
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.links=[];self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='a' and a.get('href'):self.links.append(a['href'])
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
base='http://127.0.0.1:8093'
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
 form=next((f for f in Page(html).forms if 'username' in f['fields'] and 'passwd' in f['fields']),None)
 if not form:raise RuntimeError('Native Joomla admin login form was not found')
 fields=form['fields'];fields.update(username='cioperator',passwd='Ci-only-Long-Password-2026')
 with http.open(urljoin(url,form['action']),data=urlencode(fields).encode(),timeout=20) as r:r.read()
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
 print(json.dumps({'http_smoke':'passed','pages':['public catalogue','native admin login','dashboard','draft list','draft editor']}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
