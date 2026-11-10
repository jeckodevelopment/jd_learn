"""Native Joomla question/quiz forms, subform filtering, CSRF and private answer-key boundaries."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
f=json.loads((root.parent/'quiz-authoring-fixture.json').read_text());base='http://127.0.0.1:8093'
class Page(HTMLParser):
 def __init__(self,html):
  super().__init__(convert_charrefs=True);self.forms=[];self.current=None;self.base=None;self.feed(html)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='base':self.base=a.get('href')
  if tag=='form':self.current={'id':a.get('id',''),'action':a.get('action',''),'fields':{}};self.forms.append(self.current)
  if tag=='input' and self.current is not None and 'name' in a:self.current['fields'][a['name']]=a.get('value','')
 def handle_endtag(self,tag):
  if tag=='form':self.current=None
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,fields=None,expected=200,headers=None,method=None,data=None):
 if fields is not None:data=urlencode(fields).encode()
 req=Request(urljoin(base,path),data=data,headers=headers or {},method=method)
 try:
  with http.open(req,timeout=25) as r:status=r.status;url=r.geturl();body=r.read();result_headers=r.headers
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read();result_headers=error.headers
 if status!=expected:raise RuntimeError('HTTP '+str(status)+' expected '+str(expected)+' for '+path+'; '+re.sub(r'<[^>]+>',' ',body.decode('utf-8',errors='replace'))[-2000:])
 return url,body,result_headers

log=open(root.parent/'quiz-authoring-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
admin=client();guest=client()
def form_page(path,form_id):
 u,b,_=request(admin,path);page=Page(b.decode());return u,next(x for x in page.forms if x['id']==form_id)
try:
 for _ in range(40):
  try:u,b,_=request(admin,'/administrator/index.php');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('HTTP server exited')
   time.sleep(.2)
 page=Page(b.decode());form=next(x for x in page.forms if 'username'in x['fields']and'passwd'in x['fields'])
 request(admin,urljoin(urljoin(u,page.base or ''),form['action']),fields=form['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 bank='/administrator/index.php?option=com_jlms&view=questionbank'
 _,b,_=request(admin,bank);bank_html=b.decode()
 expect('id="jlms-question-list"'in bank_html and'PRIVATE_QUIZ_PROMPT_V2'in bank_html and'id="jlms-question-form"'not in bank_html,'native question list is separate from its editor')
 for field in ['filter[search]','filter[published]','filter[catid]','filter[language]']:
  expect(f'name="{field}"' in bank_html,'question bank renders Joomla Search Tools field '+field)
 expect('Actions for: %s' not in bank_html and '<th scope="col" class="text-end">Actions</th>' in bank_html,'question action heading uses a plain translated label')
 filtered=bank+'&'+urlencode({'filter[search]':'id:'+str(f['question_id']),'filter[published]':'1','filter[catid]':str(f['category_id']),'filter[language]':'*'})
 _,filtered_html,_=request(admin,filtered)
 expect('PRIVATE_QUIZ_PROMPT_V2' in filtered_html.decode() and 'PRIVATE_QUIZ_PROMPT_V1' not in filtered_html.decode(),'question bank applies combined search, state, category and language filters')
 request(admin,'/administrator/index.php?option=com_jlms&task=questionbank.save',expected=405)
 new_question='/administrator/index.php?option=com_jlms&view=question'
 u,form=form_page(new_question,'adminForm');fields={k:v for k,v in form['fields'].items()if'options'not in k}
 request(admin,'/administrator/index.php?option=com_jlms&task=questionbank.duplicate',expected=405)
 request(admin,'/administrator/index.php?option=com_jlms&task=questionbank.delete',expected=405)
 row_keys=sorted({m.group(1)for key in form['fields']if(m:=re.fullmatch(r'jform\[options\]\[([^\]]+)\]\[option_id\]',key))and not m.group(1).endswith('X')})
 expect(len(row_keys)>=2,'native repeatable form renders at least two real option rows')
 checkbox_name=f'jform[options][{row_keys[0]}][correct]';checkbox_value=form['fields'].get(checkbox_name)
 expect(checkbox_value=='1','a selected native correct-answer checkbox submits value 1')
 fields|={'jform[id]':'0','jform[revision]':'1','jform[catid]':f['category_id'],'jform[language]':'*','jform[state]':'1','jform[kind]':'true_false','jform[prompt]':'<p>HTTP_PRIVATE_QUESTION</p>','jform[default_points]':'2','jform[explanation]':'HTTP_PRIVATE_ANSWER_KEY'}
 for i,(row_key,label)in enumerate(zip(row_keys[:2],['True','False'])):
  fields|={f'jform[options][{row_key}][option_id]':f'http{i}',f'jform[options][{row_key}][label]':label}
 fields[checkbox_name]=checkbox_value
 bad={k:v for k,v in fields.items()if not re.fullmatch('[a-f0-9]{32}',k)}
 request(admin,urljoin(u,form['action']),fields=bad,expected=403)
 _,invalid_form,_=request(admin,urljoin(u,form['action']),fields=fields|{'jform[prompt]':''},expected=422)
 expect(b'The form contains invalid fields' in invalid_form and b'Question' in invalid_form,'native form validation identifies its invalid field')
 result,b,_=request(admin,urljoin(u,form['action']),fields=fields);expect('HTTP_PRIVATE_QUESTION'in b.decode()and'HTTP_PRIVATE_ANSWER_KEY'in b.decode(),'native subform saves an authorised private answer definition')
 edit_form=next(x for x in Page(b.decode()).forms if x['id']=='adminForm');question_id=int(edit_form['fields']['jform[id]']);expect(question_id>f['question_id'],'HTTP create received a distinct native ID')
 unchecked_name=f'jform[options][{row_keys[1]}][correct]'
 blank_option=fields|{'jform[id]':'0','jform[kind]':'single','jform[prompt]':'<p>HTTP_EMPTY_CHECKBOX_QUESTION</p>',unchecked_name:''}
 _,b,_=request(admin,urljoin(u,form['action']),fields=blank_option);expect('HTTP_EMPTY_CHECKBOX_QUESTION'in b.decode(),'native single-choice form accepts Joomla empty unchecked checkbox value')
 request(admin,urljoin(u,form['action']),fields=fields|{unchecked_name:checkbox_value},expected=422)
 request(guest,'/index.php?option=com_jlms&view=questionbank',expected=404)
 _,public,_=request(guest,'/index.php?option=com_jlms&view=courses');expect(b'HTTP_PRIVATE_ANSWER_KEY'not in public and b'PRIVATE_QUIZ_PROMPT'not in public,'public catalog contains no bank content or grading data')
 _,login,_=request(guest,bank);expect(b'HTTP_PRIVATE_ANSWER_KEY'not in login and b'id="jlms-question-form"'not in login,'native backend sign-in protects question editing')
 quiz='/administrator/index.php?option=com_jlms&view=quiz&id='+str(f['course_id'])+'&version_id='+str(f['version_id'])+'&lesson_id='+str(f['lesson_id'])
 u,form=form_page(quiz,'jlms-quiz-form');fields={k:v for k,v in form['fields'].items()if'questions'not in k}
 fields|={'jform[title]':'Quiz HTTP edit','jform[module_id]':f['module_id'],'jform[required]':'1','jform[assessment]':'graded','jform[threshold]':'70','jform[max_attempts]':'3','jform[time_minutes]':'0','jform[retry_minutes]':'0','jform[selection_mode]':'fixed','jform[feedback_policy]':'pass_or_exhausted','jform[shuffle_questions]':'0','jform[shuffle_options]':'0','jform[questions][item0][revision_id]':f['first_revision'],'jform[questions][item0][points]':'3'}
 request(admin,'/administrator/index.php?option=com_jlms&task=quiz.save',expected=405)
 bad={k:v for k,v in fields.items()if not re.fullmatch('[a-f0-9]{32}',k)};request(admin,urljoin(u,form['action']),fields=bad,expected=403)
 _,b,_=request(admin,urljoin(u,form['action']),fields=fields);expect('Quiz HTTP edit'in b.decode(),'native quiz subform and config saved')
 request(admin,urljoin(u,form['action']),fields=fields,expected=409)
 _,outline,_=request(admin,'/administrator/index.php?option=com_jlms&view=curriculum&id='+str(f['course_id'])+'&version_id='+str(f['version_id']))
 expect('view=quiz'in outline.decode()and'Quiz HTTP edit'in outline.decode(),'curriculum links to dedicated quiz editor')
 request(guest,'/index.php?option=com_jlms&view=quiz',expected=401) # Student view now exists, but requires native identity.
 print(json.dumps({'quiz_authoring_http_checks':checks,'status':'passed','native_forms':'passed','answer_key_boundary':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
