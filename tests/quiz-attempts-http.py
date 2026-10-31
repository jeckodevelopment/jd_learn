"""Real Joomla frontend identity, CSRF, ownership and mid-session group revocation."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
fixture=json.loads((root.parent/'quiz-attempts-fixture.json').read_text())
base='http://127.0.0.1:8093'
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
log=open(root.parent/'quiz-attempts-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
def client():return build_opener(HTTPCookieProcessor(CookieJar()))
def request(http,path,fields=None,expected=200):
 try:
  with http.open(urljoin(base,path),data=None if fields is None else urlencode(fields).encode(),timeout=20) as r:status=r.status;url=r.geturl();body=r.read().decode('utf-8')
 except HTTPError as error:status=error.code;url=error.geturl();body=error.read().decode('utf-8',errors='replace')
 if status!=expected:raise RuntimeError('HTTP '+str(status)+' expected '+str(expected)+' for '+path+'; '+re.sub(r'<[^>]+>',' ',body)[-3000:])
 return url,body
checks=0
def expect(ok,message):
 global checks
 if not ok:raise RuntimeError(message)
 checks+=1

def login(http,username):
 url,html=request(http,'/index.php?option=com_users&view=login')
 page=Page(html);form=next((f for f in page.forms if 'username' in f['fields'] and 'password' in f['fields']),None)
 expect(form is not None,'Native frontend login form is available')
 fields=dict(form['fields']);fields.update(username=username,password='Ci-only-Learner-Password-2026')
 return request(http,urljoin(urljoin(url,page.base or ''),form['action']),fields)
import uuid
try:
 guest=client()
 for i in range(40):
  try:request(guest,'/index.php?option=com_jlms&view=courses');break
  except OSError:time.sleep(.2)
 else:raise RuntimeError('Server not ready')
 path='/index.php?option=com_jlms&view=quiz&id='+str(fixture['http_enrollment'])+'&lesson_id='+str(fixture['lesson_id'])
 request(guest,path,expected=401)
 student=client();login(student,'cilearnerhttp')
 _,html=request(student,path)
 expect('id="jlms-quiz"' in html and 'PRIVATE_QUIZ_PROMPT_V1' not in html,'Prestart view has no questions or grading')
 config=json.loads(re.search(r'<script type="application/json" class="joomla-script-options new">(.*?)</script>',html,re.S).group(1))['com_jlms.quiz']
 common={'enrollment_id':fixture['http_enrollment'],'lesson_id':fixture['lesson_id'],config['token']:1}
 endpoint=lambda task:'/index.php?option=com_jlms&task=quiz.'+task+'&format=json'
 start={**common,'mutation_id':str(uuid.uuid4())}
 request(student,endpoint('start'),{k:v for k,v in start.items() if k!=config['token']},expected=403)
 request(student,endpoint('start'),expected=405)
 _,body=request(student,endpoint('start'),start);data=json.loads(body)['data'];attempt=data['attempt']
 expect(len(data['questions'])==2 and 'correct' not in data['questions'][0] and 'PRIVATE_CORRECT_EXPLANATION' not in body,'Start returns public snapshots only')
 _,replay=request(student,endpoint('start'),start);expect(json.loads(replay)['data']['attempt']['id']==attempt['id'],'Native HTTP start replay')
 _,html=request(student,path);expect('id="jlms-quiz-questions"' in html and 'id="jlms-quiz-submit"' in html and 'PRIVATE_CORRECT_EXPLANATION' not in html,'Native MVC active view and CSS hooks')
 payload={**common,'attempt_id':attempt['id'],'revision':attempt['revision'],'mutation_id':str(uuid.uuid4()),'answers_json':json.dumps({q['id']:['first','second'] if q['kind']=='multiple' else ['first'] for q in data['questions']})}
 _,saved=request(student,endpoint('save'),payload);saved=json.loads(saved)['data'];expect(saved['attempt']['revision']==2,'HTTP save increments revision')
 request(student,endpoint('save'),{**payload,'mutation_id':str(uuid.uuid4())},expected=409)
 submit={**payload,'revision':2,'mutation_id':str(uuid.uuid4())}
 _,done=request(student,endpoint('submit'),submit);done=json.loads(done)['data'];expect(done['attempt']['passed'] and done['course_completed'] and done['feedback_available'],'HTTP submit grades and completes')
 _,again=request(student,endpoint('submit'),submit);expect(json.loads(again)['data']['replay'],'HTTP submit replay idempotent')
 _,html=request(student,path);expect('id="jlms-quiz-result"' in html and 'PRIVATE_CORRECT_EXPLANATION' in html,'Closed result releases permitted feedback')
 subprocess.run(['php','tests/joomla-learning.php',str(root),'revoke'],check=True,capture_output=True)
 try:request(student,endpoint('submit'),submit,expected=403)
 finally:subprocess.run(['php','tests/joomla-learning.php',str(root),'restore'],check=True,capture_output=True)
 expect(True,'Native HTTP replay rechecks current Joomla level')
 print(json.dumps({'quiz_http_checks':checks,'status':'passed'}))
finally:
 server.terminate()
 try:server.wait(timeout=10)
 except subprocess.TimeoutExpired:server.kill()
 log.close()
