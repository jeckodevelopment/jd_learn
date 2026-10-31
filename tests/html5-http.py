"""Native MP4 upload and Joomla access checks; byte delivery tested through real Nginx."""
from pathlib import Path
from html.parser import HTMLParser
from urllib.request import build_opener,HTTPCookieProcessor,Request
from urllib.error import HTTPError
from urllib.parse import urljoin,urlencode
from http.cookiejar import CookieJar
import json,subprocess,sys,os,time,re,uuid,hashlib
root=Path(sys.argv[1]).resolve()
if os.environ.get('JLMS_TEST_DISPOSABLE')!='1':raise RuntimeError('Disposable Joomla site required')
fixture=json.loads((root.parent/'html5-fixture.json').read_text());base='http://127.0.0.1:8093'
script=Path(__file__).with_name('html5-fixture.php')
def command(mode,*args):
 r=subprocess.run(['php',str(script),str(root),mode,*map(str,args)],check=True,capture_output=True,text=True)
 return r.stdout.strip()
def state():return json.loads(command('state'))
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

log=open(root.parent/'html5-http.log','w+')
server=subprocess.Popen(['php','-S','127.0.0.1:8093','-t',str(root)],stdout=log,stderr=log)
nginx=None;scanner=None
admin=client();student=client();guest=client()
source=root.parent/'html5-source.mp4';replacement=root.parent/'html5-replacement.mp4';unsupported=root.parent/'html5-unsupported.mp4'
for path,color,codec in [(source,'black','libx264'),(replacement,'blue','libx264'),(unsupported,'red','mpeg4')]:
 subprocess.run(['ffmpeg','-v','error','-y','-f','lavfi','-i','color=c='+color+':s=160x90:r=10','-t','10','-c:v',codec,'-pix_fmt','yuv420p','-movflags','+faststart',str(path)],check=True)
def admin_page():return request(admin,'/administrator/index.php?option=com_jlms&view=curriculum&id='+str(fixture['course_id'])+'&version_id='+str(fixture['version_id']))
def upload(name,content,expected=200,no_token=False):
 url,body,_=admin_page();form=next(f for f in Page(body.decode()).forms if f['id']=='jlms-video-upload-'+str(fixture['lesson_id']))
 fields=dict(form['fields'])
 if no_token:fields={k:v for k,v in fields.items() if not re.fullmatch('[a-f0-9]{32}',k)}
 boundary='JDVideo'+uuid.uuid4().hex;parts=[]
 for key,value in fields.items():parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="'+key+'"\r\n\r\n'+str(value)+'\r\n').encode())
 parts.append(('--'+boundary+'\r\nContent-Disposition: form-data; name="jform[upload]"; filename="'+name+'"\r\nContent-Type: video/mp4\r\n\r\n').encode()+content+b'\r\n')
 parts.append(('--'+boundary+'--\r\n').encode())
 return request(admin,urljoin(url,form['action']),data=b''.join(parts),headers={'Content-Type':'multipart/form-data; boundary='+boundary},expected=expected)
def login(http,username):
 u,b,_=request(http,'/index.php?option=com_users&view=login');page=Page(b.decode());form=next(f for f in page.forms if 'username'in f['fields'] and 'password'in f['fields'])
 fields=form['fields']|{'username':username,'password':'Ci-only-Learner-Password-2026'}
 return request(http,urljoin(urljoin(u,page.base or ''),form['action']),fields=fields)
try:
 for _ in range(40):
  try:u,b,_=request(admin,'/administrator/index.php');break
  except OSError:
   if server.poll() is not None:raise RuntimeError('HTTP server exited')
   time.sleep(.2)
 page=Page(b.decode());form=next(f for f in page.forms if 'username'in f['fields'] and 'passwd'in f['fields'])
 request(admin,urljoin(urljoin(u,page.base or ''),form['action']),fields=form['fields']|{'username':'cioperator','passwd':'Ci-only-Long-Password-2026'})
 request(admin,'/administrator/index.php?option=com_jlms&task=videofile.upload',expected=405)
 upload('video.mp4',source.read_bytes(),expected=403,no_token=True)
 upload('fake.mp4',b'not a video',expected=422)
 upload('bad.php.mp4',source.read_bytes(),expected=422)
 upload('unsupported.mp4',unsupported.read_bytes(),expected=422)
 expect(json.loads(command('deny-student-upload'))['student_upload']=='denied','specific author permission is required before binary processing')
 upload('lesson.mp4',source.read_bytes());first=state();asset=first['asset'];old_key=asset['storage_key']
 expect(first['lesson']['duration_ms']==10000 and asset['mime_type']=='video/mp4','real ffprobe detected the duration and media type')
 expect(asset['status']=='ready' and json.loads(asset['scan_json'])['result']=='not_configured','scanner absence is explicit')
 expect(asset['sha256']==hashlib.sha256(source.read_bytes()).hexdigest(),'stored video hash verified')
 command('edit');edited=state();expect(edited['lesson']['media_asset_id']==first['lesson']['media_asset_id']and edited['lesson']['duration_ms']==10000 and edited['lesson']['source_revision']==first['lesson']['source_revision'],'ordinary edits ignore forged asset/duration fields and preserve the server source')
 upload('lesson-v2.mp4',replacement.read_bytes());current=state();new_key=current['asset']['storage_key'];movie=replacement.read_bytes()
 expect(new_key!=old_key and (Path(fixture['video_root'])/old_key).read_bytes()==source.read_bytes(),'replacement retains the original immutable blob')
 expect(current['lesson']['source_revision']>first['lesson']['source_revision'],'replacement increments the source revision')
 # Mandatory unavailable scanning must block publication; a later clean scan can promote the same immutable file.
 command('configure',json.dumps({'material_scan_required':1,'material_clamd_socket':str(root.parent/'missing-html5-clamd.sock')}))
 _,scan_page,_=admin_page();scan_form=next(f for f in Page(scan_page.decode()).forms if f['fields'].get('task')=='videofile.scan')
 request(admin,urljoin(base,scan_form['action']),fields=scan_form['fields'])
 expect(state()['asset']['status']=='quarantine','required scanner unavailability quarantines local video')
 failed_publish=subprocess.run(['php',str(script),str(root),'publish'],capture_output=True,text=True)
 expect(failed_publish.returncode!=0 and state()['version']['status']=='draft','quarantined video cannot publish')
 socket=root.parent/'html5-clamd.sock'
 scanner=subprocess.Popen(['python3',str(Path(__file__).with_name('clamd-fixture.py')),str(socket)],stdout=log,stderr=log)
 for _ in range(40):
  if socket.exists():break
  if scanner.poll() is not None:raise RuntimeError('Scanner fixture exited')
  time.sleep(.1)
 command('configure',json.dumps({'material_scan_required':1,'material_clamd_socket':str(socket)}))
 _,scan_page,_=admin_page();scan_form=next(f for f in Page(scan_page.decode()).forms if f['fields'].get('task')=='videofile.scan')
 request(admin,urljoin(base,scan_form['action']),fields=scan_form['fields'])
 expect(state()['asset']['status']=='ready' and json.loads(state()['asset']['scan_json'])['result']=='clean','clean protocol scan promotes the private video')
 command('publish');published=state();stamp=published['version']['structure_hash'];version_revision=published['version']['revision']
 # Published replacements must not be accepted even with a real upload and a valid native token.
 _,admin_html,_=admin_page();expect('id="jlms-video-upload-' not in admin_html.decode(),'published lesson exposes no upload form')
 scan=next(f for f in Page(admin_html.decode()).forms if f['fields'].get('task')=='videofile.scan')
 request(admin,'/administrator/index.php?option=com_jlms&task=videofile.upload',fields=scan['fields']|{'task':'videofile.upload'},expected=409)
 request(admin,urljoin(base,scan['action']),fields=scan['fields']);after_scan=state()
 expect(after_scan['version']['structure_hash']==stamp and after_scan['version']['revision']==version_revision,'security rescan leaves teaching version and hash immutable')
 login(student,'cilearnerhttp');u,b,_=request(student,'/index.php?option=com_jlms&view=course&id='+str(fixture['course_id']))
 form=next(f for f in Page(b.decode()).forms if f['id']=='jlms-start-form');classroom,b,_=request(student,urljoin(u,form['action']),fields=form['fields']);html=b.decode()
 expect('id="jlms-video-region"'in html and new_key not in html and 'task=media.stream'not in html,'classroom contains no physical key or media URL before explicit load')
 config=None
 for block in re.findall(r'<script[^>]*>(.*?)</script>',html,re.S):
  try:
   data=json.loads(block)
   if isinstance(data,dict)and 'com_jlms.video'in data:config=data['com_jlms.video'];break
  except json.JSONDecodeError:pass
 expect(config is not None and config['provider']=='html5','native video script options loaded')
 fields={'enrollment_id':config['enrollment'],'lesson_id':config['lesson'],config['token']:'1','consent':'1'}
 _,body,_=request(student,config['urls']['playback'],fields=fields);grant=json.loads(body)['data'];media=grant['options']['url']
 expect(new_key not in media and 'media.stream'in media,'descriptor uses a protected controller rather than a storage URL')
 request(guest,media,expected=401)
 other=client();login(other,'cilearnerother');request(other,media,expected=404)
 _,body,headers=request(student,media,headers={'Range':'bytes=0-31'},expected=206)
 expect(body==movie[:32] and headers['Content-Range']=='bytes 0-31/'+str(len(movie)),'bounded PHP delivery supports exact single ranges')
 expect(headers['Content-Type']=='video/mp4'and headers['Content-Disposition'].startswith('inline')and 'no-store'in headers['Cache-Control'],'protected inline headers')
 _,body,headers=request(student,media,method='HEAD');expect(body==b''and int(headers['Content-Length'])==len(movie),'HEAD never sends video bytes')
 request(student,media,headers={'Range':'bytes=999999999-'},expected=416)
 request(student,media,headers={'Range':'bytes=0-1,4-5'},expected=416)
 request(student,media,fields={},expected=405)
 command('reject');request(student,media,expected=503);command('restore')
 command('delivery','disabled');request(student,media,expected=503);command('delivery','x_accel')
 # Nginx consumes X-Accel-Redirect and serves the bytes from an internal-only directory.
 nginx_config=root.parent/'html5-nginx.conf'
 nginx_config.write_text('worker_processes 1; user root; pid '+str(root.parent/'html5-nginx.pid')+'; error_log stderr; events {worker_connections 64;} http {access_log off; proxy_temp_path '+str(root.parent/'nginx-temp')+'; server { listen 127.0.0.1:8094; location / { proxy_pass http://127.0.0.1:8093; proxy_set_header Host 127.0.0.1:8093; proxy_cache off; } location /jdlearn-private/ { internal; alias '+fixture['video_root']+'/; types {} default_type video/mp4; add_header Cache-Control "private, no-store" always; } } }')
 nginx=subprocess.Popen(['nginx','-c',str(nginx_config),'-g','daemon off;'],stdout=log,stderr=log)
 for _ in range(30):
  try:request(guest,'http://127.0.0.1:8094/jdlearn-private/'+new_key,expected=404);break
  except OSError:
   if nginx.poll() is not None:raise RuntimeError('Nginx exited')
   time.sleep(.1)
 protected=urljoin(base,media).replace(':8093',':8094')
 _,body,headers=request(student,protected,headers={'Range':'bytes=32-63'},expected=206)
 expect(body==movie[32:64]and headers.get('X-Accel-Redirect')is None,'real Nginx offload returns exact bytes and consumes the internal header')
 _,body,_=request(student,protected,method='HEAD');expect(body==b'','Nginx HEAD sends no body')
 subprocess.run(['php',str(Path(__file__).with_name('joomla-learning.php')),str(root),'revoke',str(fixture['http_user_id']),str(fixture['group_id'])],check=True,capture_output=True)
 request(student,protected,headers={'Range':'bytes=64-95'},expected=403)
 subprocess.run(['php',str(Path(__file__).with_name('joomla-learning.php')),str(root),'grant',str(fixture['http_user_id']),str(fixture['group_id'])],check=True,capture_output=True)
 _,body,_=request(student,protected,headers={'Range':'bytes=64-95'},expected=206);expect(body==movie[64:96],'restored entitlement resumes with the same browser session')
 # Loading and ranges do not award progress. Ordered, wall-time-bounded samples still govern completion.
 payload=fields|{'session_id':grant['session_id'],'source_revision':grant['source_revision'],'sequence':1,'duration_ms':10000,
 'events_json':json.dumps([{'id':1,'type':'ended','position_ms':10000,'client_ms':0,'rate_milli':1000,'playing':False}])}
 _,body,_=request(student,config['urls']['save'],fields=payload);expect(json.loads(body)['data']['covered_ms']==0,'load/range/ended event alone award no credit')
 time.sleep(9.1)
 payload|={'sequence':2,'events_json':json.dumps([{'id':2,'type':'play','position_ms':0,'client_ms':1,'rate_milli':1000,'playing':True},{'id':3,'type':'position','position_ms':9000,'client_ms':9001,'rate_milli':1000,'playing':True}])}
 _,body,_=request(student,config['urls']['save'],fields=payload);done=json.loads(body)['data'];expect(done['lesson_completed']and done['course_percent']==100,'HTML5 uses the same server-validated 90 percent completion rule')
 expect(state()['version']['structure_hash']==stamp,'playback never changes frozen teaching content')
 print(json.dumps({'html5_http_checks':checks,'status':'passed','real_nginx_offload':'passed','probe':'real ffprobe','browser_playback':'not exercised'}))
except Exception:
 log.flush();log.seek(0);print(log.read()[-8000:],file=sys.stderr);raise
finally:
 for process in [nginx,server,scanner]:
  if process is None:continue
  process.terminate()
  try:process.wait(timeout=5)
  except subprocess.TimeoutExpired:process.kill();process.wait()
 log.close()
