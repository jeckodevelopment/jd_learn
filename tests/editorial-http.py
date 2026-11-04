"""Native profile fields, teacher menu layouts and linked-account editing in a disposable site."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
fixture=json.loads((root.parent/'editorial-fixture.json').read_text())
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.links=[];self.current=None;self.textarea=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['checkbox','radio'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='textarea':
   self.textarea=a.get('name') if self.current is not None else None
   if self.textarea:self.current['fields'][self.textarea]=''
  if tag=='a' and a.get('href'):self.links.append(a['href'])
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='textarea':self.textarea=None
 def handle_data(self,data):
  if self.current is not None and self.textarea:self.current['fields'][self.textarea]+=data
base='http://127.0.0.1:8093'
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
client=build_opener(HTTPCookieProcessor(CookieJar()))
def request(path,fields=None,expected=200):
 try:
  with client.open(urljoin(base,path),data=None if fields is None else urlencode(fields).encode(),timeout=20) as response:
   status=response.status;url=response.geturl();body=response.read().decode('utf-8')
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode('utf-8')
 if status!=expected:raise RuntimeError(f'Unexpected HTTP {status}, expected {expected}: {body[-1500:]}')
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
try:
 for attempt in range(40):
  try:request('/index.php?option=com_jlms&view=courses');break
  except OSError:time.sleep(.2)
 path='/index.php?option=com_jlms&view=teacher&id='+str(fixture['teacher'])
 _,html=request(path)
 expect('id="jlms-teacher-profile"' in html and 'own@example.invalid' in html,'Public profile renders permitted native fields')
 expect('Editorially updated course' in html,'Teacher page lists its published courses')
 _,courses=request(path+'&layout=courses')
 expect('id="jlms-teacher-presentation"' not in courses and 'Editorially updated course' in courses,'Courses-by-teacher menu layout renders the filtered list')
 request('/index.php?option=com_jlms&view=teacheredit&id='+str(fixture['teacher']),expected=403)
 request('/index.php?option=com_jlms&view=teacher&id=2147483647',expected=404)
 url,html=request('/index.php?option=com_users&view=login')
 form=next(f for f in Page(html).forms if 'username' in f['fields'])
 fields=dict(form['fields']);fields.update(username='cieditor',password='Ci-only-Editor-Password-2026')
 request(urljoin(url,form['action']),fields)
 _,html=request(path)
 link=next(x for x in Page(html).links if parse_qs(urlparse(x).query).get('task')==['teacher.edit'])
 url,html=request(link)
 form=next(f for f in Page(html).forms if 'jform[bio]' in f['fields'])
 expect('jform[catid]' not in form['fields'] and 'jform[user_id]' not in form['fields'],'Own profile editor hides category and protected account binding')
 fields=dict(form['fields']);fields.update({'task':'teacher.save','jform[bio]':'Profile edited from the public page','jform[com_fields][teaching-contact]':'frontend@example.invalid'})
 _,html=request(urljoin(url,form['action']),fields)
 expect('Profile edited from the public page' in html and 'frontend@example.invalid' in html,'Linked teacher saves their profile and native field from the frontend: '+re.sub(r'\s+',' ',re.sub(r'<[^>]*>',' ',html))[:10000])
 print(json.dumps({'editorial_http_checks':checks,'status':'passed'}))
finally:server.terminate();server.wait(timeout=5)
