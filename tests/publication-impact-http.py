"""Contextual curriculum navigation, native editors, moves, ordering and denial paths."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse,parse_qs
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable site required')
f=json.loads((root.parent/'publication-impact-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.option=None;self.links=[];self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='a' and a.get('href'):self.links.append(a['href'])
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['radio','checkbox'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='select' and self.current is not None:
   self.select=a.get('name');self.current.setdefault('options',{}).setdefault(self.select,[])
  if tag=='option' and self.select and self.current is not None:
   self.option={'value':a.get('value',''),'text':''}
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='option' and self.option is not None and self.current is not None:
   self.current['options'][self.select].append(self.option);self.option=None
  if tag=='select':self.select=None
 def handle_data(self,data):
  if self.option is not None:self.option['text']+=data
client=build_opener(HTTPCookieProcessor(CookieJar()))
def request(path,fields=None,expected=200):
 try:
  with client.open(Request(urljoin(base,path),data=None if fields is None else urlencode(fields).encode()),timeout=25) as response:status=response.status;url=response.geturl();body=response.read().decode()
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode()
 if status!=expected:raise RuntimeError(f'HTTP {status}, expected {expected}: '+re.sub('<[^>]+>',' ',body)[-3000:])
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def action(html,name):return next(link for link in Page(html).links if parse_qs(urlparse(link).query).get('item_action')==[name])
def form(html,task):return next(x for x in Page(html).forms if x['fields'].get('task')==task)
log=open(root.parent/'publication-impact-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def state():return json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('joomla-publication-impact.php')),str(root),'state'],text=True))
def publication(html,label=None):
 p=Page(html);current=next(x for x in p.forms if x['fields'].get('task')=='curriculum.transition')
 if label is not None:
  choices=current.get('options',{}).get('jform[transition_id]',[])
  choice=next(x for x in choices if label in x['text'])
  current['fields']['jform[transition_id]']=choice['value']
 return current
try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['version'])
 before=state();url,html=request(path);command=publication(html,'Send for review')
 url,html=request(command['action'],command['fields'])
 expect(state()==before,'Submitting an update for review preserves current course and learning data')
 _,html=request(path);command=publication(html,'Approve and publish')
 url,html=request(command['action'],command['fields'])
 expect('id="jlms-publication-review"' in html and 'id="jlms-publication-confirm-form"' in html,'Approval opens a separate native impact review')
 expect(state()==before,'Opening and reading the review never publishes or changes learning data')
 expect('HTTP impact &lt;script&gt;safe&lt;/script&gt;' in html and '<script>safe</script>' not in html,'Review escapes private lesson titles')
 expect('New enrollments will use the updated version' in html and 'Existing enrollments keep their assigned version' in html,'Review explains the effect on new and existing assignments')
 confirm=publication(html);request('/administrator/index.php?option=com_jlms&task=curriculum.transition',expected=405)
 request('/administrator/index.php?option=com_jlms',{'task':'curriculum.transition'},expected=403)
 _,rejected=request(confirm['action'],confirm['fields'])
 expect(state()==before,'Confirmation checkbox is required by the server')
 _,rejected=request(confirm['action'],confirm['fields']|{'jform[publication_confirmation]':'1','jform[review_ticket]':'0'*64})
 expect(state()==before,'Forged session-bound confirmation ticket cannot publish')
 # Return to the author, edit while in draft, and verify the old impact ticket is stale.
 _,html=request(path);_,html=request(publication(html,'Return to draft')['action'],publication(html,'Return to draft')['fields'])
 _,edit=request(path+'&edit_module='+str(f['module']));module=next(x for x in Page(edit).forms if x['fields'].get('task')=='module.save')
 request(module['action'],module['fields']|{'jform[title]':'Changed after reviewing'})
 changed=state();_,rejected=request(confirm['action'],confirm['fields']|{'jform[publication_confirmation]':'1'})
 expect(state()==changed,'Returning and changing a draft prevents stale publication')
 # Resubmit the fresh draft and explicitly approve the current impact.
 _,html=request(path);_,html=request(publication(html,'Send for review')['action'],publication(html,'Send for review')['fields'])
 _,html=request(path);_,html=request(publication(html,'Approve and publish')['action'],publication(html,'Approve and publish')['fields'])
 current=publication(html);fields=current['fields']|{'jform[publication_confirmation]':'1'}
 _,html=request(current['action'],fields);after=state()
 expect(after['version']['status']=='published' and int(after['course']['current_version_id'])==f['version'],'Current explicit confirmation publishes the new programme')
 expect(after['learning']==before['learning'],'Publication preserves all existing enrollment, attempt and progress rows')
 _,html=request(current['action'],fields);expect(state()==after,'Replaying a consumed confirmation cannot publish twice')
 _,html=request('/administrator/index.php?option=com_jlms&view=publicationreview&id='+str(f['course'])+'&version_id='+str(f['version']),expected=409)
 print(json.dumps({'publication_impact_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
