"""Real uploads, immutable attachments, native login, protected ranges and scanner failure policy."""
from pathlib import Path
from html.parser import HTMLParser
from html import unescape
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode,urlparse
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re,uuid,hashlib
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
fixture=json.loads((root.parent/'materials-fixture.json').read_text());base='http://127.0.0.1:8093'
script=Path(__file__).with_name('materials-fixture.php')
def command(mode,*args):
 r=subprocess.run(['php',str(script),str(root),mode,*map(str,args)],check=True,capture_output=True,text=True)
 return r.stdout.strip()
def configure(**changes):command('configure',json.dumps(changes))
def files():return json.loads(command('inspect'))['files']
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
private=root.parent/'private-materials';private.mkdir(mode=0o700,exist_ok=True)
configure(material_storage_root=str(private),material_php_delivery=1,material_scan_required=0,material_max_mib=20,material_orphan_retention_hours=48)
log=open(root.parent/'materials-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
scanner=None
admin=client();student=client();guest=client()
def admin_page(course=None,version=None,replace=0,scope=0):
 course=fixture['course_id'] if course is None else course;version=fixture['version_id'] if version is None else version
 return request(admin,'/administrator/index.php?option=com_jlms&view=materials&id='+str(course)+'&version_id='+str(version)+'&replace_id='+str(replace)+'&material_scope='+str(scope))
def upload(name,content,title='Materiale',lesson=0,replace=0,expected=200,course=None,version=None,invalid_token=False,publish_up='',publish_down='',preview=0):
 url,body,_=admin_page(course,version,replace);html=body.decode();page=Page(html);form=next(f for f in page.forms if f['id']=='jlms-material-upload')
 expect('jform[publish_up]' in html and 'jform[publish_down]' in html and 'datetime-local' in html,'Material form exposes both course-time-zone availability fields')
 fields=dict(form['fields']);fields.update({'jform[title]':title,'jform[description]':'Extra facoltativo','jform[lesson_id]':str(lesson),'jform[replace_id]':str(replace),'jform[publish_up]':publish_up,'jform[publish_down]':publish_down,'jform[preview]':str(preview)})
 if invalid_token:
  fields={k:v for k,v in fields.items() if not re.fullmatch('[a-f0-9]{32}',k)}
 boundary='JDMaterial'+uuid.uuid4().hex;parts=[]
 for key,value in fields.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+str(value)+'\r\n').encode())
 parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="jform[upload]"; filename="'+name+'"\r\nContent-Type: application/octet-stream\r\n\r\n').encode()+content+b'\r\n')
 parts.append(('--'+boundary+'--\r\n').encode())
 return request(admin,urljoin(url,form['action']),data=b''.join(parts),headers={'Content-Type':'multipart/form-data; boundary='+boundary},expected=expected)
def act_material(task,attachment):
 url,body,_=admin_page();forms=Page(body.decode()).forms
 form=next(f for f in forms if f['fields'].get('task')=='material.'+task and f['fields'].get('jform[id]')==str(attachment))
 return request(admin,urljoin(url,form['action']),fields=form['fields'])
def publish(expected=200):
 u,b,_=request(admin,'/administrator/index.php?option=com_jlms&view=curriculum&id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id']))
 h=b.decode();form=next(f for f in Page(h).forms if f['id']=='jlms-publication-form');fields=dict(form['fields'])
 fields['jform[transition_id]']=re.search(r'<option value="([1-9][0-9]*)"',h[h.index('id="jlms-transition"'):]).group(1)
 return request(admin,urljoin(u,form['action']),fields=fields,expected=expected)
try:
 for _ in range(40):
  try:url,body,_=request(admin,'/administrator/index.php');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('Server exited')
   time.sleep(.2)
 page=Page(body.decode());form=next(f for f in page.forms if 'username' in f['fields'] and 'passwd' in f['fields']);fields=dict(form['fields']);fields.update(username='cioperator',passwd='Ci-only-Long-Password-2026')
 request(admin,urljoin(urljoin(url,page.base or ''),form['action']),fields=fields)
 # No public fallback and no uploads with executable disguises.
 upload('notes.txt',b'CSRF must fail\n',expected=403,invalid_token=True)
 upload('evil.php.pdf',b'%PDF-1.4\n%%EOF\n',expected=422)
 upload('fake.pdf',b'not a PDF document',expected=422)
 configure(material_storage_root=str(root/'images'))
 upload('notes.txt',b'Course notes\n',expected=503)
 configure(material_storage_root=str(private),material_max_mib=1)
 upload('too-large.txt',b'x'*(1048576+1),expected=413)
 configure(material_max_mib=20)
 upload('notes.txt',b'OLD_PRIVATE_NOTES\n','Course handout')
 first=files()[0];old_key=first['storage_key'];old_hash=first['sha256']
 upload('notes-v2.txt',b'NEW_PRIVATE_NOTES\n','Course handout',replace=first['id'])
 first=files()[0]
 expect(first['storage_key']!=old_key and (private/old_key).exists() and hashlib.sha256((private/old_key).read_bytes()).hexdigest()==old_hash,'Replacement preserves immutable old blob')
 upload('lesson.txt',b'LESSON_PRIVATE_NOTES\n','Lesson handout',lesson=fixture['lesson_id'])
 upload('lesson-preview.txt',b'LESSON_PREVIEW_MUST_STAY_PRIVATE\n','Lesson public preview forbidden',lesson=fixture['lesson_id'],preview=1,expected=422)
 lesson_file=next(row for row in files() if row['title']=='Lesson handout')
 reuse_url,reuse_page,_=admin_page();reuse_form=next(f for f in Page(reuse_page.decode()).forms if f['fields'].get('task')=='material.reuse' and f['fields'].get('jform[id]')==str(lesson_file['id']))
 reuse_fields=dict(reuse_form['fields']);reuse_fields['jform[target_lesson_id]']='0'
 request(admin,urljoin(reuse_url,reuse_form['action']),fields=reuse_fields)
 reused=next(row for row in files() if row['id']!=lesson_file['id'] and row['title']=='Lesson handout')
 expect(reused['lesson_id'] is None and reused['asset_id']==lesson_file['asset_id'],'Reuse links the same private immutable asset into course scope')
 reuse_url,reuse_page,_=admin_page();reuse_form=next(f for f in Page(reuse_page.decode()).forms if f['fields'].get('task')=='material.reuse' and f['fields'].get('jform[id]')==str(lesson_file['id']))
 reuse_fields=dict(reuse_form['fields']);reuse_fields['jform[target_lesson_id]']='0'
 request(admin,urljoin(reuse_url,reuse_form['action']),fields=reuse_fields,expected=409)
 objects=[b'<</Type/Catalog/Pages 2 0 R>>',b'<</Type/Pages/Kids[3 0 R]/Count 1>>',b'<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]/Contents 4 0 R>>',b'<</Length 0>>stream\n\nendstream']
 pdf=b'%PDF-1.4\n';offsets=[0]
 for i,obj in enumerate(objects,1):offsets.append(len(pdf));pdf+=str(i).encode()+b' 0 obj\n'+obj+b'\nendobj\n'
 xref=len(pdf);pdf+=b'xref\n0 5\n0000000000 65535 f \n'+b''.join(('%010d 00000 n \n'%o).encode() for o in offsets[1:])+b'trailer<</Size 5/Root 1 0 R>>\nstartxref\n'+str(xref).encode()+b'\n%%EOF\n'
 upload('guida-à.pdf',pdf,'PDF handout',lesson=fixture['lesson_id'])
 upload('foreign.txt',b'FOREIGN_PRIVATE_NOTES\n','Foreign handout',course=fixture['foreign_course_id'],version=fixture['foreign_version_id'])
 expect(len(files())==4,'Course and lesson attachment scopes and explicit reuse persisted')
 _,all_scope,_=admin_page(); all_html=all_scope.decode()
 expect('id="jlms-material-scope-filter"' in all_html and 'value="0" selected' in all_html,'Material administration exposes a scope filter with all scopes selected by default')
 _,course_scope,_=admin_page(scope=-1); course_html=course_scope.decode()
 expect('id="jlms-material-scope-filter"' in course_html and 'value="-1" selected' in course_html and 'data-attachment-id="'+str(lesson_file['id'])+'"' not in course_html,'Course scope filter excludes lesson-only attachments')
 _,lesson_scope,_=admin_page(scope=fixture['lesson_id']); lesson_html=lesson_scope.decode()
 expect('value="'+str(fixture['lesson_id'])+'" selected' in lesson_html and 'data-attachment-id="'+str(lesson_file['id'])+'"' in lesson_html and 'data-attachment-id="'+str(reused['id'])+'"' not in lesson_html,'Lesson scope filter shows only attachments linked to that lesson')
 expect(command('denied')=='material ACL passed','Material authoring requires its specific permission')
 expect(json.loads(first['scan_json'])['result']=='not_configured','Absent scanner is explicit, never reported clean')
 # Mandatory scanner unavailable: quarantine, admin download blocked and publication refused.
 configure(material_scan_required=1,material_clamd_socket=str(root.parent/'missing-clamd.sock'))
 upload('pending.txt',b'PENDING_PRIVATE_NOTES\n','Pending handout')
 pending=files()[-1];expect(pending['status']=='quarantine','Mandatory unavailable scanner keeps quarantine')
 raw='/administrator/index.php?option=com_jlms&task=material.download&format=raw&course_id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id'])+'&id='+str(pending['id'])
 request(admin,raw,expected=404)
 _,failed,_=publish();expect('jlms-material-upload' not in failed.decode() and 'material' in failed.decode().lower(),'Publication validation returns curriculum response')
 # The curriculum controller uses an error message + redirect, so check the draft upload form remains available.
 _,draft,_=admin_page();expect('id="jlms-material-upload"' in draft.decode(),'Quarantined files prevent version publication')
 real_socket=os.environ.get('JLMS_REAL_CLAMD_SOCKET')
 scanner=None
 if real_socket:
  socket=real_socket
  if not Path(socket).is_socket():raise RuntimeError('Real ClamAV socket unavailable')
 else:
  socket=str(root.parent/'clamd.sock')
  scanner=subprocess.Popen([sys.executable,str(Path(__file__).with_name('clamd-fixture.py')),socket],stdout=subprocess.PIPE,text=True)
  if scanner.stdout.readline().strip()!='ready':raise RuntimeError('Scanner protocol fixture failed')
 configure(material_clamd_socket=socket)
 for row in files():act_material('scan',row['id'])
 expect(all(row['status']=='ready' for row in files()),'Clean scanner result promotes assets')
 expect(all(json.loads(row['scan_json']).get('engine')=='clamd' and json.loads(row['scan_json']).get('result')=='clean' for row in files()),'Clean files report real scanner result')
 if real_socket:
  eicar=b'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'
  upload('eicar.txt',eicar,'Blocked handout')
  infected=files()[-1]
  expect(infected['status']=='rejected' and json.loads(infected['scan_json']).get('result')=='infected','Real ClamAV EICAR signature blocks asset')
 else:
  upload('infected.txt',b'FIXTURE_INFECTED\\n','Blocked handout')
  infected=files()[-1];expect(infected['status']=='rejected','Positive scanner fixture blocks asset')
 act_material('delete',infected['id'])
 before_invalid=set(private.iterdir());before_invalid_links=len(files())
 upload('bad-window.txt',b'BAD_WINDOW\n','Invalid window',publish_up='2099-01-02T00:00:00',publish_down='2099-01-01T00:00:00',expected=400)
 expect(set(private.iterdir())==before_invalid and len(files())==before_invalid_links,'An invalid date range is rejected before a blob or link is created')
 upload('scheduled.txt',b'SCHEDULED_PRIVATE\n','Scheduled handout',publish_up='2099-01-01T00:00:00',publish_down='2099-01-02T00:00:00')
 upload('public-sample.txt',b'PUBLIC_SAMPLE_CONTENT\n','Public sample',preview=1)
 upload('scheduled-public-sample.txt',b'FUTURE_PUBLIC_SAMPLE\n','Future public sample',preview=1,publish_up='2099-01-01T00:00:00',publish_down='2099-01-02T00:00:00')
 u,b,_=request(admin,'/administrator/index.php?option=com_jlms&view=curriculum&id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id']))
 readiness=b.decode();expect('data-readiness-code="COM_JLMS_READINESS_MATERIAL_SCHEDULED"' in readiness and 'data-readiness-code="COM_JLMS_MATERIAL_NOT_READY"' not in readiness,'A valid material schedule is a readiness notice, not a publication blocker')
 publish()
 _,published,_=admin_page();expect('id="jlms-material-upload"' not in published.decode(),'Published material associations are immutable')
 preview_url='/administrator/index.php?option=com_jlms&view=coursepreview&id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id'])+'&lesson_id='+str(fixture['lesson_id'])
 _,preview_page,_=request(admin,preview_url)
 expect(b'id="jlms-preview-course-materials"' in preview_page and b'Course handout' in preview_page and b'Public sample' not in preview_page and b'Future public sample' not in preview_page,
  'Student-style editorial preview shows available course materials for a text lesson, excluding public-only and future assets')
 public_files={row['title']:row for row in files()}
 preview_link=re.search(rb'href="([^"]*task=material\.preview[^"]*)"',preview_page)
 expect(preview_link is not None,'Text lesson preview includes a protected link to its available course material')
 preview_path=urlparse(unescape(preview_link.group(1).decode())).path+'?'+urlparse(unescape(preview_link.group(1).decode())).query
 _,preview_body,_=request(admin,preview_path)
 expect(preview_body==b'NEW_PRIVATE_NOTES\n','Course-scope material is delivered through the protected read-only preview endpoint')
 request(admin,'/administrator/index.php?option=com_jlms&task=material.preview&format=raw&course_id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id'])+'&lesson_id='+str(fixture['lesson_id'])+'&id='+str(lesson_file['id']),expected=404)
 sample=public_files['Public sample'];future_sample=public_files['Future public sample']
 course_path='/index.php?option=com_jlms&view=course&id='+str(fixture['course_id'])
 _,public_page,_=request(guest,course_path)
 expect(b'id="jlms-course-materials"' in public_page and b'Public sample' in public_page and b'Course handout' not in public_page and b'Lesson handout' not in public_page and b'Future public sample' not in public_page,'Guest course page shows only explicitly public, currently available course material')
 public_url='/index.php?option=com_jlms&task=attachment.publicpreview&format=raw&course_id='+str(fixture['course_id'])+'&id='
 _,public_body,public_headers=request(guest,public_url+str(sample['id']))
 expect(public_body==b'PUBLIC_SAMPLE_CONTENT\n' and 'no-store' in public_headers.get('Cache-Control','') and 'noindex, nofollow' in public_headers.get('X-Robots-Tag',''),'Explicit course preview is downloadable without exposing the private asset path')
 request(guest,public_url+str(future_sample['id']),expected=404)
 private_id=public_files['Course handout']['id'];lesson_id=public_files['Lesson handout']['id'];foreign_id=json.loads(command('inspect'))['foreign_files'][0]['id']
 for forbidden in [private_id,lesson_id,foreign_id]:request(guest,public_url+str(forbidden),expected=404)
 request(guest,public_url+str(sample['id']),method='POST',expected=405)
 _,head_body,head_headers=request(guest,public_url+str(sample['id']),method='HEAD')
 expect(head_body==b'' and head_headers.get('Content-Length')==str(len(b'PUBLIC_SAMPLE_CONTENT\n')),'Public preview HEAD returns metadata without a response body')
 frozen=json.loads(command('inspect'))['version']
 act_material('scan',first['id'])
 expect(json.loads(command('inspect'))['version']==frozen,'Published security rescan preserves teaching hash and revision')
 u,b,_=admin_page();scan_form=next(f for f in Page(b.decode()).forms if f['fields'].get('task')=='material.scan')
 forged=dict(scan_form['fields']);forged.update({'task':'material.save','jform[title]':'Forbidden edit','jform[state]':'1','jform[ordering]':'0'})
 request(admin,urljoin(u,scan_form['action']),fields=forged,expected=409)
 forged=dict(scan_form['fields']);forged.update({'task':'material.reuse','jform[target_lesson_id]':'0'})
 request(admin,urljoin(u,scan_form['action']),fields=forged,expected=409)
 # Student access uses native frontend login.
 url,body,_=request(student,'/index.php?option=com_users&view=login');page=Page(body.decode());form=next(f for f in page.forms if 'username' in f['fields'] and 'password' in f['fields'])
 fields=dict(form['fields']);fields.update(username='cilearnerhttp',password='Ci-only-Learner-Password-2026');request(student,urljoin(urljoin(url,page.base or ''),form['action']),fields=fields)
 course='/index.php?option=com_jlms&view=course&id='+str(fixture['course_id'])
 url,body,_=request(student,course);expect(b'NEW_PRIVATE_NOTES' not in body and b'Course handout' not in body,'Promotional sheet exposes no protected file content or titles')
 form=next(f for f in Page(body.decode()).forms if f['id']=='jlms-start-form');classroom,body,_=request(student,urljoin(url,form['action']),fields=form['fields'])
 enrollment=int(re.search(rb'data-enrollment-id="([1-9][0-9]*)"',body).group(1))
 expect(b'id="jlms-classroom-course-materials"' in body and b'id="jlms-lesson-resources"' in body and b'Course handout' in body and b'Lesson handout' in body and b'Scheduled handout' not in body,'Classroom separates resources and hides materials before their start date')
 download='/index.php?option=com_jlms&task=attachment.download&format=raw&enrollment_id='+str(enrollment)+'&id='+str(first['id'])
 _,binary,headers=request(student,download);expect(binary==b'NEW_PRIVATE_NOTES\n','Authorised download is exact')
 pdf_id=next(f['id'] for f in files() if f['title']=='PDF handout')
 _,pdf_download,pdf_headers=request(student,download.rsplit('&id=',1)[0]+'&id='+str(pdf_id))
 expect(pdf_download==pdf and pdf_headers.get('Content-Type')=='application/pdf' and "filename*=UTF-8''" in pdf_headers.get('Content-Disposition',''),'PDF and UTF-8 filename delivered exactly')
 expect(headers.get('X-Content-Type-Options')=='nosniff' and 'attachment;' in headers.get('Content-Disposition','') and 'no-store' in headers.get('Cache-Control',''),'Protected response headers applied')
 _,part,headers=request(student,download,headers={'Range':'bytes=0-2'},expected=206);expect(part==b'NEW' and headers.get('Content-Range','').startswith('bytes 0-2/'),'Partial download is correct')
 _,suffix,_=request(student,download,headers={'Range':'bytes=-6'},expected=206);expect(suffix==b'NOTES\n','Suffix range is correct')
 request(student,download,headers={'Range':'bytes=999999-1000000'},expected=416)
 _,head,headers=request(student,download,method='HEAD');expect(head==b'' and int(headers.get('Content-Length'))==len(binary),'HEAD carries correct metadata and no body')
 foreign_id=json.loads(command('inspect'))['foreign_files'][0]['id']
 request(student,download.rsplit('&id=',1)[0]+'&id='+str(foreign_id),expected=404)
 other=client();u,b,_=request(other,'/index.php?option=com_users&view=login');pg=Page(b.decode());login_form=next(f for f in pg.forms if 'username' in f['fields'] and 'password' in f['fields'])
 credentials=dict(login_form['fields']);credentials.update(username='cilearnerother',password='Ci-only-Learner-Password-2026')
 request(other,urljoin(urljoin(u,pg.base or ''),login_form['action']),fields=credentials)
 request(other,download,expected=404)
 request(guest,download,expected=401)
 request(guest,'/private-materials/'+first['storage_key'],expected=404)
 # Revoke the dedicated entitlement outside the web process, preserving the same session.
 learning_script=Path(__file__).with_name('joomla-learning.php')
 subprocess.run(['php',str(learning_script),str(root),'revoke',str(fixture['http_user_id']),str(fixture['group_id'])],check=True,capture_output=True)
 request(student,download,headers={'Range':'bytes=0-2'},expected=403)
 subprocess.run(['php',str(learning_script),str(root),'grant',str(fixture['http_user_id']),str(fixture['group_id'])],check=True,capture_output=True)
 request(student,download)
 _,unchanged,_=request(student,classroom);expect(b'value="0"' in unchanged,'Downloading optional files gives no completion credit')
 # Same-size corruption is detected before returning bytes.
 key=first['storage_key'];original=(private/key).read_bytes();(private/key).write_bytes(b'X'*len(original))
 request(student,download,expected=503);(private/key).write_bytes(original)
 expect(command('cleanup')=='material cleanup passed','Native scheduler applies configured retention and preserves newer unreferenced assets')
 command('configure',json.dumps({'material_scan_required':0}))
 command('hidden',str(fixture['course_id']))
 request(guest,public_url+str(sample['id']),expected=404)
 print(json.dumps({'materials_http_checks':checks,'status':'passed','ranges':'passed','quarantine':'passed','revocation_same_session':'passed'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-6000:],file=sys.stderr);raise
finally:
 if scanner:
  scanner.terminate();scanner.wait(timeout=5)
 server.terminate()
 try:server.wait(timeout=5)
 except subprocess.TimeoutExpired:server.kill();server.wait()
 log.close()
