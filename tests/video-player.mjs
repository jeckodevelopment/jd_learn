import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
const source = await readFile(new URL('../component/media/js/video.js', import.meta.url), 'utf8');
const {VideoLesson} = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));
const listeners = new Map();
globalThis.window = {addEventListener: (k,v) => listeners.set(k,v), removeEventListener: k => listeners.delete(k)};
globalThis.document = {hidden:false, addEventListener(){},removeEventListener(){},getElementById(){return null;}};
const elements = Object.fromEntries(['#jlms-player-status','#jlms-video-load','#jlms-video-stop','#jlms-player']
  .map(k => [k, {textContent:'',addEventListener(){},replaceChildren(){}}]));
const root = {querySelector:k=>elements[k]};
let calls = 0;
const config = {policy:'site_consent',provider:'youtube',enrollment:1,lesson:2,token:'csrf',
  urls:{save:'/save',playback:'/playback',close:'/close'},labels:{consent:'consent',offline:'offline',error:'error'}};
const lesson = new VideoLesson(config,root,async () => { calls++; throw Error('unexpected remote load'); });
await lesson.start(); assert.equal(calls,0); assert.equal(elements['#jlms-player-status'].textContent,'consent');
window.JlmsConsent = {isAllowed:()=>false}; await lesson.start();assert.equal(calls,0);
lesson.config.policy='click'; lesson.session={session_id:'session',source_revision:1,duration_ms:10000};
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
await lesson.destroy();assert.equal(listeners.size,0);
console.log(JSON.stringify({video_player_checks:12,status:'passed',environment:'mock DOM/SDK transport; no live provider playback'}));
