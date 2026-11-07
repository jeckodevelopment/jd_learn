"""Current course fields through native field plugins/layouts, access, language and editorial updates."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'course-fields-public-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.textarea=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['radio','checkbox'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='textarea':
   self.textarea=a.get('name') if self.current is not None and 'disabled' not in a else None
   if self.textarea:self.current['fields'][self.textarea]=''
  if tag=='select':self.select=a.get('name') if self.current is not None and 'disabled' not in a else None
  if tag=='option' and self.select and self.current is not None:
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='select':self.select=None
  if tag=='textarea':self.textarea=None
 def handle_data(self,data):
  if self.textarea and self.current is not None:self.current['fields'][self.textarea]+=data

def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,fields=None,expected=200):
 try:
  with http.open(Request(urljoin(base,path),data=None if fields is None else urlencode(fields).encode()),timeout=25) as response:status=response.status;url=response.geturl();body=response.read().decode()
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode()
 if status!=expected:raise RuntimeError(f'HTTP {status}, expected {expected}: '+re.sub('<[^>]+>',' ',body)[-2000:])
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def fixture(mode):
 result=subprocess.run(['php','tests/joomla-course-fields-public.php',str(root),mode],capture_output=True,text=True,check=True)
 return json.loads(result.stdout) if result.stdout else None
def login(http):
 url,html=request(http,'/index.php?option=com_users&view=login')
 page=Page(html);form=next(x for x in page.forms if 'username' in x['fields'] and 'password' in x['fields'])
 request(http,urljoin(urljoin(url,page.base or ''),form['action']),form['fields']|{'username':'publicfieldsreader','password':'Ci-only-Fields-Password-2026'})
log=open(root.parent/'course-fields-public-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
try:
 guest=client();reader=client();path='/index.php?option=com_jlms&view=course&id='+str(f['course'])+'&lang=en'
 for _ in range(40):
  try:_,html=request(guest,path);break
  except OSError:time.sleep(.2)
 else:raise RuntimeError('Disposable HTTP server unavailable')
 before=fixture('state');_,html=request(guest,path)
 expect('PUB_AFTER_TITLE' in html and 'PUB_BEFORE_CONTENT' in html and 'PUB_NATIVE_CHOICE_LABEL' in html,'Native text, textarea and list field plugins render values/choice label')
 expect(html.index('id="jlms-course-title"')<html.index('PUB_AFTER_TITLE')<html.index('id="jlms-course-summary"'),'After-title position is after title and before presentation')
 expect(html.index('id="jlms-course-fields-before-content"')<html.index('PUB_BEFORE_CONTENT')<html.index('id="jlms-course-summary"'),'Before-content native position is respected')
 expect(html.index('id="jlms-course-description"')<html.index('PUB_NATIVE_CHOICE_LABEL')<html.index('id="jlms-course-access"'),'After-content native position is after presentation and before enrollment')
 expect(all(x not in html for x in ['FIELDS_MANUAL_SECRET','FIELDS_PRIVATE_VALUE','FIELDS_GROUP_VALUE','FIELDS_UNPUBLISHED_SECRET','FIELDS_FOREIGN_SECRET','FIELDS_OTHER_CATEGORY_SECRET','FIELDS_PRIVATE_LESSON_BODY']),'Manual, private/group, unpublished, foreign/category and lesson content stay absent')
 expect('PUB_NATIVE_DEFAULT' in html and 'Public field empty' not in html,'Native defaults render, empty values/labels stay absent')
 expect('&lt;script&gt;FIELD_XSS()&lt;/script&gt;' in html and '<script>FIELD_XSS()' not in html,'Text plugin escapes stored active markup')
 ids=re.findall(r'\bid="(jlms-[^"]+)"',html)
 expect(len(ids)==len(set(ids)),'Component field wrappers keep document component IDs unique')
 expect(fixture('state')==before,'Reading native fields has no course/field/teaching writes')
 login(reader);_,registered=request(reader,path)
 expect('FIELDS_PRIVATE_VALUE' in registered and 'FIELDS_GROUP_VALUE' in registered,'Current registered field and group access permit values')
 fixture('revoke');_,revoked=request(reader,path)
 expect('PUB_AFTER_TITLE' in revoked and 'FIELDS_PRIVATE_VALUE' not in revoked and 'FIELDS_GROUP_VALUE' not in revoked,'Same browser session loses field/group access after native group revocation')
 fixture('group-public');_,public_group=request(guest,path)
 expect('FIELDS_GROUP_VALUE' in public_group,'Current field group access is applied on the next request')
 fixture('group-hidden');_,hidden_group=request(guest,path)
 expect('FIELDS_GROUP_VALUE' not in hidden_group,'Unpublished group hides fields on direct course URL')
 fixture('field-hidden');_,hidden_field=request(guest,path)
 expect('PUB_BEFORE_CONTENT' not in hidden_field and 'id="jlms-course-fields-before-content"' in hidden_field,'Field withdrawal removes its value while other before-content fields remain')
 fixture('edit');_,updated=request(guest,path)
 expect('PUB_UPDATED_VALUE' in updated and 'PUB_AFTER_TITLE' not in updated,'Native editorial update appears publicly without republishing programme')
 expect(fixture('state')['teaching']==before['teaching'],'Editorial changes and reads preserve assigned version, enrollment and progress')
 fixture('override');_,overridden=request(guest,path)
 expect('native-fields-override-proof' in overridden and 'PUB_UPDATED_VALUE' in overridden,'Native component fields layout override is used')
 fixture('remove-override');_,normal=request(guest,path)
 expect('native-fields-override-proof' not in normal and 'fields-container' in normal,'Removing override restores core fields layout')
 fixture('withdraw');_,denied=request(guest,path,expected=404)
 expect('PUB_UPDATED_VALUE' not in denied and 'FIELDS_PRIVATE_VALUE' not in denied,'Course withdrawal blocks fields through the same direct URL')
 fixture('restore');expect(fixture('state')['teaching']==before['teaching'],'Course visibility changes never rewrite teaching evidence')
 fixture('checkpoint')
 print(json.dumps({'course_fields_public_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-6000:],file=sys.stderr);raise
finally:
 server.terminate();server.wait(timeout=5);log.close()
