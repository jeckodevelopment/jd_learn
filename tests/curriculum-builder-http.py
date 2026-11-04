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
f=json.loads((root.parent/'curriculum-builder-fixture.json').read_text());base='http://127.0.0.1:8095'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.select=None;self.links=[];self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='a' and a.get('href'):self.links.append(a['href'])
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a and 'disabled' not in a:
   if a.get('type') not in ['radio','checkbox'] or 'checked' in a:self.current['fields'][a['name']]=a.get('value','')
  if tag=='select':self.select=a.get('name') if self.current is not None else None
  if tag=='option' and self.select and self.current is not None:
   if self.select not in self.current['fields'] or 'selected' in a:self.current['fields'][self.select]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
  if tag=='select':self.select=None
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
log=open(root.parent/'curriculum-builder-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8095','-t',str(root)],stdout=log,stderr=log)
def state():return json.loads(subprocess.check_output(['php',str(Path(__file__).with_name('joomla-curriculum-builder.php')),str(root),'state'],text=True))
def link(html,key,value):return next(x for x in Page(html).links if parse_qs(urlparse(x).query).get(key)==[str(value)])
def modules(html):return re.findall(r'<section id="jlms-module-([0-9]+)"',html)
def lesson_ids(html,module):
 outline=re.search(r'<ol[^>]+data-sort-kind="lesson"[^>]+data-module-id="'+str(module)+r'">(.*?)</ol>',html,re.S)
 return re.findall(r'<li id="jlms-lesson-([0-9]+)"',outline[1])
try:
 for _ in range(40):
  try:url,html=request('/administrator/index.php');break
  except OSError:time.sleep(.2)
 page=Page(html);login=next(x for x in page.forms if 'username' in x['fields'] and 'passwd' in x['fields'])
 request(urljoin(urljoin(url,page.base or ''),login['action']),login['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 path='/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course'])+'&version_id='+str(f['version'])
 url,html=request(path)
 expect('id="jlms-lesson-form"' not in html and 'id="jlms-module-form"' not in html,'Default outline has no unsolicited editing forms')
 expect('data-drag-handle="module"' in html and 'data-drag-handle="lesson"' in html,'Draft exposes scoped drag handles')
 expect('curriculum.js' in html and 'curriculum.css' in html,'Native WAM loads curriculum assets')
 _,editor=request(link(html,'edit_lesson',f['l1']));edit=form(editor,'lesson.save')
 expect(edit['fields']['jform[id]']==str(f['l1']) and edit['fields']['jform[module_id]']==str(f['m2']),'Lesson title opens its native scoped editor')
 materials=next(x for x in Page(editor).links if parse_qs(urlparse(x).query).get('view')==['materials'] and parse_qs(urlparse(x).query).get('lesson_id')==[str(f['l1'])])
 _,materials_html=request(materials)
 expect(form(materials_html,'material.upload')['fields']['jform[lesson_id]']==str(f['l1']),'Materials form selects the clicked lesson')
 _,failed=request(edit['action'],edit['fields']|{'jform[title]':'','jform[body]':'<p>UNSAVED_BUILDER_BODY</p>'})
 expect('UNSAVED_BUILDER_BODY' in failed and form(failed,'lesson.save')['fields']['jform[title]']=='','Validation failure preserves editor input')
 saved=form(failed,'lesson.save');_,html=request(saved['action'],saved['fields']|{'jform[title]':'Builder lesson updated via HTTP','jform[body]':'<p>Saved programme content</p>'})
 expect('Builder lesson updated via HTTP' in html and 'id="jlms-lesson-form"' not in html,'Save returns to a compact outline')
 # Moving uses a separate action, preserving the lesson identity and body.
 _,movehtml=request(path+'&item_action=move_lesson&item_id='+str(f['l1']));move=form(movehtml,'lesson.move')
 before=state();_,html=request(move['action'],move['fields']|{'jform[module_id]':str(f['m1'])});after=state()
 moved=next(x for x in after[3] if int(x['id'])==f['l1'])
 expect(int(moved['module_id'])==f['m1'] and moved['body']=='<p>Saved programme content</p>','Contextual move persists destination without losing content')
 expect(f['l1'] in [int(x) for x in lesson_ids(html,f['m1'])],'Outline immediately shows moved lesson in its destination')
 # Every mutation requires POST and a native token.
 for task in ['curriculum.saveOrder','lesson.move']:
  _,body=request('/administrator/index.php?option=com_jlms&task='+task+('&format=json' if task=='curriculum.saveOrder' else ''),expected=405)
  request('/administrator/index.php?option=com_jlms',{'task':task,'format':'json'},expected=403)
 # Exact full permutation uses CAS; replay and forged membership cannot change data.
 orderform=form(html,'module.reorder');ids=modules(html);fields=orderform['fields']|{'task':'curriculum.saveOrder','format':'json','jform[kind]':'module'}
 fields.update({'jform[order]['+str(i)+']':id for i,id in enumerate(reversed(ids))})
 _,body=request(orderform['action'],fields);result=json.loads(body)
 expect(result['success'] is True and result['data']['revision']>int(fields['jform[expected_version_revision]']),'JSON order saves with a fresh version revision')
 _,html=request(path);expect(modules(html)==list(reversed(ids)),'Reordered outline is persisted in server output')
 stable=state();_,body=request(orderform['action'],fields,expected=409)
 expect(json.loads(body)['success'] is False and state()==stable,'Stale ordering is rejected without partial writes')
 current=form(html,'module.reorder');bad=current['fields']|{'task':'curriculum.saveOrder','format':'json','jform[kind]':'module','jform[order][0]':ids[0]}
 request(current['action'],bad,expected=409);expect(state()==stable,'Partial or foreign sibling lists cannot replace an outline')
 # Keyboard/non-JS route exposes an ordinary protected confirmation form, no GET writes.
 up=next(x for x in Page(html).links if parse_qs(urlparse(x).query).get('item_action')==['order_module'] and parse_qs(urlparse(x).query).get('direction')==['up'])
 before=state();_,confirm=request(up);expect(state()==before,'Opening a keyboard order link never mutates data')
 command=form(confirm,'module.reorder');_,html=request(command['action'],command['fields'])
 expect(modules(html)!=modules(confirm),'Native fallback command reorders without JavaScript')
 # Add video is scoped to the clicked module; quiz also selects the clicked module.
 _,video=request(path+'&new_lesson=1&lesson_kind=video&module_id='+str(f['m2']));videoform=form(video,'lesson.save')
 expect(videoform['fields']['jform[module_id]']==str(f['m2']) and videoform['fields']['jform[kind]']=='video','Add video selects both the clicked module and activity kind')
 _,quiz=request('/administrator/index.php?option=com_jlms&view=quiz&id='+str(f['course'])+'&version_id='+str(f['version'])+'&module_id='+str(f['m2']))
 expect(form(quiz,'quiz.save')['fields']['jform[module_id]']==str(f['m2']),'Add quiz selects the clicked module')
 request(path+'&new_lesson=1&module_id=2147483647',expected=404)
 request(path+'&edit_lesson=2147483647',expected=404)
 request(path+'&item_action=copy_lesson&item_id=2147483647',expected=404)
 # A published source has no draft controls, even with an edit query.
 _,published=request('/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['source'])+'&version_id='+str(f['source_version']))
 expect('data-drag-handle' not in published and 'data-order-kind' not in published and 'id="jlms-lesson-form"' not in published,'Published programme remains read only')
 subprocess.run(['php',str(Path(__file__).with_name('joomla-curriculum-builder.php')),str(root),'checkpoint'],check=True)
 print(json.dumps({'curriculum_builder_http_checks':checks,'status':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:server.terminate();server.wait(timeout=5);log.close()
