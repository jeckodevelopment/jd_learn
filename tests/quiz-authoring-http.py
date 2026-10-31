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
 _,b,_=request(admin,bank);expect('id="jlms-question-form"'in b.decode()and'PRIVATE_QUIZ_PROMPT_V2'in b.decode(),'native bank list and question form rendered')
 request(admin,'/administrator/index.php?option=com_jlms&task=questionbank.save',expected=405)
 u,form=form_page(bank,'jlms-question-form');fields={k:v for k,v in form['fields'].items()if'options'not in k}
 fields|={'jform[id]':'0','jform[revision]':'1','jform[catid]':f['category_id'],'jform[language]':'*','jform[state]':'1','jform[kind]':'true_false','jform[prompt]':'<p>HTTP_PRIVATE_QUESTION</p>','jform[default_points]':'2','jform[explanation]':'HTTP_PRIVATE_ANSWER_KEY'}
 for i,(label,correct)in enumerate([('True','1'),('False','0')]):
  fields|={f'jform[options][option{i}][option_id]':f'http{i}',f'jform[options][option{i}][label]':label,f'jform[options][option{i}][correct]':correct}
 bad={k:v for k,v in fields.items()if not re.fullmatch('[a-f0-9]{32}',k)}
 request(admin,urljoin(u,form['action']),fields=bad,expected=403)
 result,b,_=request(admin,urljoin(u,form['action']),fields=fields);expect('HTTP_PRIVATE_QUESTION'in b.decode()and'HTTP_PRIVATE_ANSWER_KEY'in b.decode(),'native subform saves an authorised private answer definition')
 edit_form=next(x for x in Page(b.decode()).forms if x['id']=='jlms-question-form');question_id=int(edit_form['fields']['jform[id]']);expect(question_id>f['question_id'],'HTTP create received a distinct native ID')
 request(admin,urljoin(u,form['action']),fields=fields|{'jform[options][option1][correct]':'1'},expected=422)
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
 request(guest,'/index.php?option=com_jlms&view=quiz',expected=404)
 print(json.dumps({'quiz_authoring_http_checks':checks,'status':'passed','native_forms':'passed','answer_key_boundary':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
