import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
const source = (await readFile(new URL('../component/media/js/video.js', import.meta.url), 'utf8'))
  .replaceAll('import.meta.url', JSON.stringify('https://school.example/media/com_jlms/js/video.js'));
const {VideoLesson} = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));
const listeners = new Map();
globalThis.window = {addEventListener: (k,v) => listeners.set(k,v), removeEventListener: k => listeners.delete(k)};
globalThis.document = {hidden:false, addEventListener(){},removeEventListener(){},getElementById(){return null;},createElement:tag=>({tag,options:[],append(option){this.options.push(option);}})};
const elements = Object.fromEntries(['#jlms-player-status','#jlms-player','#jlms-video-language-control']
  .map(k => [k, {textContent:'',addEventListener(){},replaceChildren(){}}]));
elements['#jlms-video-language']={value:'',options:[],addEventListener(){},removeEventListener(){},replaceChildren(){this.options=[];},append(option){this.options.push(option);}};
const root = {querySelector:k=>elements[k]};
let calls = 0;
const config = {policy:'site_consent',provider:'youtube',enrollment:1,lesson:2,token:'csrf',
  urls:{save:'/save',playback:'/playback',close:'/close'},labels:{consent:'consent',offline:'offline',error:'error'}};
const lesson = new VideoLesson(config,root,async () => { calls++; throw Error('unexpected remote load'); });
await lesson.start(); assert.equal(calls,0); assert.equal(elements['#jlms-player-status'].textContent,'consent');
window.JlmsConsent = {isAllowed:()=>false}; await lesson.start();assert.equal(calls,0);
lesson.config.policy='embed'; lesson.session={session_id:'session',source_revision:1,duration_ms:10000};
lesson.duration=10000; lesson.stopped=false;
lesson.queue=[{id:1,type:'play',position_ms:0,client_ms:0,rate_milli:1000,playing:true}];
const bodies=[]; let attempt=0;
lesson.transport=async (_url,options)=>{
  bodies.push(options.body.toString());
  if (++attempt===1) throw Error('network');
  return {ok:true,json:async()=>({success:true,data:{next_sequence:2,lesson_completed:false,course_percent:0}})};
};
await lesson.flush();assert.ok(lesson.pending);assert.equal(lesson.sequence,1);
lesson.queue.push({id:2,type:'pause',position_ms:1000,client_ms:1000,rate_milli:1000,playing:false});
await lesson.flush();assert.equal(bodies[0],bodies[1]);assert.equal(lesson.queue.length,1);assert.equal(lesson.sequence,2);
lesson.transport=async()=>({ok:false,status:403,json:async()=>({success:false})});
let destroyed=0;lesson.player={destroy:async()=>destroyed++};
await lesson.flush();assert.equal(lesson.stopped,true);assert.equal(destroyed,1);
// YouTube can be ready for commands before it knows the duration.
lesson.session = {session_id:'youtube-test', source_revision:1, duration_ms:10000};
lesson.stopped = false;
lesson.queue = [];
let duration = 0;
lesson.player = {sample: async () => ({position_ms:0, duration_ms:duration, rate_milli:1000, playing:false})};
await lesson.record('seeked');
assert.equal(lesson.stopped, false);
assert.equal(lesson.queue.length, 0);
duration = 10000;
await lesson.record('play');
assert.equal(lesson.queue.length, 1);
duration = 20000;
await lesson.record('position');
assert.equal(lesson.stopped, true);
lesson.session = null;
lesson.queue = [];
await lesson.destroy();assert.equal(listeners.size,0);
// The default browser transport keeps the global fetch receiver and loads the adapter after authorization.
const originalFetch = globalThis.fetch;
const startup = [];
globalThis.fetch = async function (url) {
  assert.equal(this, globalThis);
  startup.push(url);
  return {ok:true,json:async()=>({success:true,data:{provider:'youtube', session_id:'auto', duration_ms:10000, options:{}}})};
};
const automatic = new VideoLesson({...config, policy:'site_consent'}, root, undefined, async url => {
  startup.push(url);
  return {create:async()=>({sample:async()=>({position_ms:0,duration_ms:10000,rate_milli:1000,playing:false}),destroy(){}})};
});
await automatic.start();
assert.deepEqual(startup, []);
automatic.config.policy = 'embed';
await automatic.start();
assert.deepEqual(startup, ['/playback','https://school.example/media/plg_jlmsvideo_youtube/js/player.js']);
assert.equal(automatic.stopped, false);
await automatic.stop(false);
const extensionCalls=[];
const extension = new VideoLesson({...config, policy:'embed', provider:'demo_provider'}, root, async url => {
  extensionCalls.push(url);
  return {ok:true,json:async()=>({success:true,data:{provider:'demo_provider',session_id:'extension',duration_ms:10000,options:{}}})};
}, async url => {
  extensionCalls.push(url);
  return {create:async()=>({sample:async()=>({position_ms:0,duration_ms:10000,rate_milli:1000,playing:false}),destroy(){}})};
});
await extension.start();
assert.deepEqual(extensionCalls, ['/playback','https://school.example/media/plg_jlmsvideo_demo_provider/js/player.js']);
assert.equal(extension.stopped, false);
await extension.stop(false);
automatic.session = null;
await automatic.destroy();
globalThis.fetch = originalFetch;
console.log(JSON.stringify({video_player_checks:12,status:'passed',environment:'mock DOM/SDK transport; no live provider playback'}));
// Exercise the local provider adapters against SDK doubles, including privacy parameters and cleanup.
const moduleFromFile = async path => import('data:text/javascript;base64,' + Buffer.from(await readFile(path,'utf8')).toString('base64'));
const youtube = await moduleFromFile(new URL('../plugins/jlmsvideo/youtube/media/js/player.js',import.meta.url));
// A browser can deny the external SDK before issuing a request.
const securityListeners = new Map();
const originalDocument = globalThis.document;
const originalReady = globalThis.onYouTubeIframeAPIReady;
let sdkAttempts = 0;
delete globalThis.YT;
globalThis.document = {
  createElement: () => ({remove(){}}),
  addEventListener: (type, callback) => securityListeners.set(type, callback),
  removeEventListener: type => securityListeners.delete(type),
  head: {append: script => {
    sdkAttempts++;
    queueMicrotask(() => {
      if (sdkAttempts === 1) securityListeners.get('securitypolicyviolation')({
        disposition:'enforce', blockedURI:'https://www.youtube.com',
      });
      else script.onerror();
    });
  }},
};
await assert.rejects(youtube.create({}, {}, () => {}), {message:'youtube_csp'});
await assert.rejects(youtube.create({}, {}, () => {}), {message:'youtube_sdk'});
assert.equal(sdkAttempts, 2);
assert.equal(securityListeners.size, 0);
assert.equal(globalThis.onYouTubeIframeAPIReady, originalReady);
globalThis.document = originalDocument;
let ytFrame, ytDestroyed=0;
globalThis.location={origin:'https://school.example'};
globalThis.YT={Player:class {
  constructor(frame,options){ytFrame=frame;queueMicrotask(()=>options.events.onReady());}
  getCurrentTime(){return 1.5;} getDuration(){return 10;}getPlaybackRate(){return ytRate;}getAvailablePlaybackRates(){return [0.5,1,1.5];}setPlaybackRate(rate){ytRate=rate;}getPlayerState(){return 1;}
  pauseVideo(){}seekTo(){}destroy(){ytDestroyed++;}
}};
const frames=[];
let ytRate=1;
document.createElement=tag=>({tag,dataset:{},remove(){},set src(value){this.url=value;},get src(){return this.url;}});
const box={dataset:{title:'Lesson'},append:frame=>{frames.push(frame);}};
const youtubeBox = {...box, append: frame => {
  box.append(frame);
  assert.equal(ytFrame, undefined, 'SDK waits for iframe navigation');
  queueMicrotask(() => frame.onload?.());
}};
const yt=await youtube.create(youtubeBox,{options:{reference:'dQw4w9WgXcQ',locale:'it-IT'}},()=>{},new AbortController().signal);
const ytUrl = new URL(ytFrame.src);
assert.equal(ytUrl.origin,'https://www.youtube-nocookie.com');
assert.equal(ytUrl.pathname,'/embed/dQw4w9WgXcQ');
assert.equal(ytUrl.searchParams.get('enablejsapi'),'1');
assert.equal(ytUrl.searchParams.get('origin'),location.origin);
assert.equal(ytUrl.searchParams.get('autoplay'),'0');
assert.equal(ytFrame.referrerPolicy,'strict-origin');
assert.equal((await yt.sample()).position_ms,1500);assert.deepEqual(await yt.playbackRates(),[0.5,1,1.5]);assert.equal(await yt.setPlaybackRate(1.5),1.5);yt.destroy();assert.equal(ytDestroyed,1);
const aborted=new AbortController();aborted.abort();await assert.rejects(youtube.create(box,{options:{}},()=>{},aborted.signal));
const vimeo=await moduleFromFile(new URL('../plugins/jlmsvideo/vimeo/media/js/player.js',import.meta.url));
let vmHandlers=0,vmDestroyed=0,vmInstance;
globalThis.Vimeo={Player:class{
  constructor(){vmInstance=this;}
  ready(){return Promise.resolve();}on(){vmHandlers++;}off(){vmHandlers--;}
  getCurrentTime(){return Promise.resolve(2);}getDuration(){return Promise.resolve(10);}
  getPlaybackRate(){return Promise.resolve(1);}setPlaybackRate(rate){return Promise.resolve(rate);}getPaused(){return Promise.resolve(false);}getEnded(){return Promise.resolve(false);}
  getTextTracks(){return Promise.resolve([{language:'en',label:'English',kind:'captions',mode:'disabled'},{language:'fr',label:'Français',kind:'captions',mode:'showing'}]);}
  enableTextTrack(language,kind){this.enabled=[language,kind];return Promise.resolve();}disableTextTrack(){this.enabled=null;return Promise.resolve();}
  destroy(){vmDestroyed++;return Promise.resolve();}
}};
const vm=await vimeo.create(box,{options:{reference:'12345',privacy_hash:'abc123'}},()=>{},new AbortController().signal);
const frameUrl=new URL(frames.at(-1).src);assert.equal(frameUrl.searchParams.get('dnt'),'1');assert.equal(frameUrl.searchParams.get('h'),'abc123');
assert.equal((await vm.sample()).duration_ms,10000);assert.deepEqual(await vm.playbackRates(),[0.5,0.75,1,1.25,1.5,1.75,2]);assert.equal(await vm.setPlaybackRate(1.5),1.5);await vm.destroy();assert.equal(vmHandlers,0);assert.equal(vmDestroyed,1);
const vmTracks=await vm.textTracks();assert.equal(vmTracks.length,2);assert.equal(vmTracks[1].active,true);
await vm.setTextTrack(vmTracks[0].id);assert.deepEqual(vmInstance.enabled,['en','captions']);
await vm.setTextTrack('');
console.log(JSON.stringify({provider_adapter_checks:10,status:'passed',environment:'SDK doubles; no live remote video'}));

// A revoked consent must also settle an SDK instance that never reaches ready.
for (const [name,adapter] of [['YT',youtube],['Vimeo',vimeo]]) {
 const signal=new AbortController(); let made;
 if (name==='YT') globalThis.YT={Player:class{constructor(){made=true;}destroy(){}}};
 else globalThis.Vimeo={Player:class{constructor(){made=true;}ready(){return new Promise(()=>{});}destroy(){return Promise.resolve();}on(){}}};
 const result=adapter.create({...box, append: frame => {box.append(frame); queueMicrotask(() => frame.onload?.());}}, {options:{reference:'12345',locale:'it-IT'}},()=>{},signal.signal);
 await new Promise(resolve=>setTimeout(resolve,0));assert.ok(made);signal.abort();
 await assert.rejects(result,{name:'AbortError'});
}
console.log(JSON.stringify({provider_pending_abort_checks:4,status:'passed'}));
const cf=await moduleFromFile(new URL('../plugins/jlmsvideo/cloudflarestream/media/js/player.js',import.meta.url));
let cfHandlers=0, cfPaused=0;
globalThis.Stream=()=>({currentTime:3,duration:10,playbackRate:1,paused:false,ended:false,
 addEventListener(){cfHandlers++;},removeEventListener(){cfHandlers--;},pause(){cfPaused++;}});
const cfPlayer=await cf.create(box,{options:{domain:'customer-ci.cloudflarestream.com',token:'a.b.c'}},()=>{},new AbortController().signal);
assert.match(frames.at(-1).src,/^https:\/\/customer-ci\.cloudflarestream\.com\/a\.b\.c\/iframe\?/);
assert.equal((await cfPlayer.sample()).position_ms,3000);assert.equal(cfPlayer.playbackRates,undefined);cfPlayer.pause();assert.equal(cfPaused,1);
cfPlayer.destroy();assert.equal(cfHandlers,0);
await assert.rejects(cf.create(box,{options:{domain:'evil.example',token:'a.b.c'}},()=>{},new AbortController().signal));
const cfAbort=new AbortController();
globalThis.Stream=()=>({duration:0,addEventListener(){},removeEventListener(){}});
const pendingCf=cf.create(box,{options:{domain:'customer-ci.cloudflarestream.com',token:'a.b.c'}},()=>{},cfAbort.signal);
await new Promise(resolve=>setTimeout(resolve,0));cfAbort.abort();await assert.rejects(pendingCf,{name:'AbortError'});
console.log(JSON.stringify({cloudflare_adapter_checks:6,status:'passed',environment:'Stream SDK double'}));
// Renewal keeps the telemetry session and monotone event IDs while recreating only the remote iframe.
const renewalConfig={...config,policy:'embed',provider:'cloudflarestream',urls:{save:'/save',renew:'/renew',close:'/close'},labels:{...config.labels,renewed:'renewed'}};
const renewal=new VideoLesson(renewalConfig,root);
renewal.session={session_id:'same-session',source_revision:1,duration_ms:10000,options:{renew_after_seconds:40}};
renewal.stopped=false;renewal.started=performance.now();renewal.aborter=new AbortController();
let oldDestroyed=0, created=0, seekPosition;
const sample=async()=>({position_ms:4000,duration_ms:10000,rate_milli:1000,playing:false});
renewal.player={sample,pause(){},destroy:async()=>oldDestroyed++};
renewal.adapter={create:async()=>{created++;return{sample,pause(){},destroy(){},seek:ms=>{seekPosition=ms;}};}};
renewal.transport=async(url,options)=>{
 const fields=Object.fromEntries(options.body);
 if(url==='/renew'){
  assert.equal(fields.session_id,'same-session');assert.equal(fields.consent,'1');
  return{ok:true,json:async()=>({success:true,data:{provider:'cloudflarestream',source_revision:1,options:{token:'new.token.signature',renew_after_seconds:40}}})};
 }
 return{ok:true,json:async()=>({success:true,data:{next_sequence:renewal.sequence+1,course_percent:0}})};
};
await renewal.renew();assert.equal(oldDestroyed,1);assert.equal(created,1);assert.equal(seekPosition,4000);
assert.equal(renewal.session.session_id,'same-session');assert.equal(renewal.session.options.token,'new.token.signature');
assert.equal(elements['#jlms-player-status'].textContent,'renewed');await renewal.destroy();
console.log(JSON.stringify({cloudflare_client_renew_checks:8,status:'passed'}));
const html5=await moduleFromFile(new URL('../plugins/jlmsvideo/html5/media/js/player.js',import.meta.url));
location.href='https://school.example/index.php?option=com_jlms';
let videoNode, removed=0, pauses=0;
document.createElement=tag=>{
 if(tag==='option')return {value:'',textContent:''};
 assert.equal(tag,'video');const handlers=new Map();
 const element={currentTime:2,duration:10,playbackRate:1,paused:false,ended:false,
  textTracks:[{language:'en',label:'English',kind:'captions',mode:'disabled'},{language:'it',label:'Italiano',kind:'subtitles',mode:'disabled'}],
  setAttribute(){},removeAttribute(){},load(){},pause(){pauses++;},remove(){removed++;},
  addEventListener:(event,handler)=>handlers.set(event,handler),removeEventListener:event=>handlers.delete(event),
  set src(value){this.url=value;queueMicrotask(()=>handlers.get('loadedmetadata')?.());},get src(){return this.url;},handlers};
 videoNode=element;return element;
};
const native=await html5.create(box,{options:{url:'/index.php?option=com_jlms&task=media.stream&enrollment_id=1&lesson_id=2&source_revision=3'}},()=>{},new AbortController().signal);
assert.equal(videoNode.preload,'metadata');assert.equal(videoNode.autoplay,false);assert.equal(videoNode.controls,true);assert.deepEqual(await native.playbackRates(),[0.5,0.75,1,1.25,1.5,1.75,2]);assert.equal(await native.setPlaybackRate(1.5),1.5);assert.equal(videoNode.playbackRate,1.5);
const nativeTracks=await native.textTracks();assert.equal(nativeTracks.length,2);await native.setTextTrack('1');assert.equal(videoNode.textTracks[1].mode,'showing');assert.equal(videoNode.textTracks[0].mode,'disabled');await native.setTextTrack('');assert.equal(videoNode.textTracks[1].mode,'disabled');
assert.equal((await native.sample()).position_ms,2000);native.seek(5000);assert.equal(videoNode.currentTime,5);
native.destroy();native.destroy();assert.equal(removed,1);assert.equal(videoNode.handlers.size,0);
await assert.rejects(html5.create(box,{options:{url:'https://elsewhere.example/private.mp4'}},()=>{},new AbortController().signal));
const localConsent=new VideoLesson({...config,provider:'html5',policy:'blocked'},root);
assert.equal(localConsent.allowed(),true);await localConsent.destroy();
console.log(JSON.stringify({html5_adapter_checks:9,status:'passed',environment:'native media-element double; no browser decoder'}));


const speedOptions=[0.5,0.75,1,1.25,1.5,1.75,2].map(value=>({value:String(value),hidden:false}));
const speedSelect={options:speedOptions,value:'1',addEventListener(){},removeEventListener(){}};
const speedControl={hidden:true};
const speedStatus={textContent:''};
const speedMount={replaceChildren(){}};
const speedRoot={querySelector:selector=>({'#jlms-player-status':speedStatus,'#jlms-player':speedMount,
  '#jlms-video-speed':speedSelect,'#jlms-video-speed-control':speedControl})[selector]};
const previousStorage=globalThis.localStorage;
let savedSpeed=null;
globalThis.localStorage={getItem:key=>key==='jdlearn.video.speed.v1'?savedSpeed:null,setItem:(key,value)=>{if(key==='jdlearn.video.speed.v1')savedSpeed=value;}};
const speedLesson=new VideoLesson({...config,labels:{...config.labels,rate_unavailable:'unsupported'}},speedRoot);
let appliedSpeed=1;
speedLesson.player={playbackRates:async()=>[0.5,1,1.5],setPlaybackRate:async rate=>{appliedSpeed=rate;return rate;},
  sample:async()=>({position_ms:1000,duration_ms:10000,rate_milli:Math.round(appliedSpeed*1000),playing:false}),pause(){},destroy:async()=>{}};
speedLesson.session={session_id:'speed',source_revision:1,duration_ms:10000};speedLesson.started=performance.now();speedLesson.stopped=false;
await speedLesson.configurePlaybackSpeed(speedLesson.player);
assert.equal(speedControl.hidden,false);assert.equal(speedOptions.find(option=>option.value==='0.75').hidden,true);
speedSelect.value='1.5';await speedLesson.changePlaybackRate();
assert.equal(appliedSpeed,1.5);assert.equal(savedSpeed,'1.5');assert.equal(speedLesson.queue.at(-1).rate_milli,1500);
appliedSpeed=1;await speedLesson.record('ratechange');assert.equal(speedSelect.value,'1');
speedLesson.session=null;speedLesson.queue=[];await speedLesson.destroy();
const unsupportedLesson=new VideoLesson(config,speedRoot);
await unsupportedLesson.configurePlaybackSpeed({playbackRates:async()=>[1],setPlaybackRate:async rate=>rate});
assert.equal(speedControl.hidden,true);await unsupportedLesson.destroy();

// The saved caption language is applied only when a matching exposed track exists.
let captionTrack=''; let captionPreference='fr-CA';
Object.defineProperty(globalThis,'localStorage',{configurable:true,value:{getItem:key=>key==='jdlearn.video.language.v1'?captionPreference:null,
  setItem:(key,value)=>{if(key==='jdlearn.video.language.v1')captionPreference=value;},
  removeItem:key=>{if(key==='jdlearn.video.language.v1')captionPreference=null;}}});
const languageLesson=new VideoLesson(config,root);
languageLesson.player={textTracks:async()=>[
  {id:'en',language:'en',label:'English'},{id:'fr',language:'fr',label:'Français'}],
  setTextTrack:async id=>{captionTrack=id;}};
await languageLesson.configureCaptionLanguage(languageLesson.player);
assert.equal(elements['#jlms-video-language-control'].hidden,false);
assert.equal(captionTrack,'fr');assert.equal(elements['#jlms-video-language'].value,'fr');
assert.deepEqual(elements['#jlms-video-language'].options.map(option=>option.value),['','en','fr']);
elements['#jlms-video-language'].value='en';await languageLesson.changeCaptionLanguage();
assert.equal(captionTrack,'en');assert.equal(captionPreference,'en');
elements['#jlms-video-language'].value='';await languageLesson.changeCaptionLanguage();
assert.equal(captionTrack,'');assert.equal(captionPreference,null);
await languageLesson.destroy();
if(previousStorage===undefined) delete globalThis.localStorage; else Object.defineProperty(globalThis,'localStorage',{configurable:true,value:previousStorage});
console.log(JSON.stringify({player_preference_checks:12,status:'passed',coverage:'dynamic playback speed and caption tracks, local preferences, unsupported player, preview-safe presentation'}));
