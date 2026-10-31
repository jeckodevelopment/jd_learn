import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {webcrypto} from 'node:crypto';
const source=await readFile(new URL('../component/media/js/quiz.js',import.meta.url),'utf8');
const {initialiseQuiz}=await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'));
const keys=['fetch','setInterval','clearInterval','addEventListener','crypto','confirm','performance'];
const originals=Object.fromEntries(keys.map(k=>[k,Object.getOwnPropertyDescriptor(globalThis,k)]));
try{
 Object.defineProperty(globalThis,'crypto',{value:webcrypto,configurable:true});let ms=0;
 Object.defineProperty(globalThis,'performance',{value:{now:()=>ms},configurable:true});
 const intervals=[],events=new Map(),requests=[],moves=[];globalThis.setInterval=(callback,delay)=>{intervals.push({callback,delay});return intervals.length;};
 globalThis.clearInterval=()=>{};globalThis.addEventListener=()=>{};globalThis.confirm=()=>true;
 const input={value:'first',checked:false};const field={dataset:{questionId:'10'},querySelectorAll:s=>s==='input:checked'?(input.checked?[input]:[]):[input]};
 const form={querySelectorAll:s=>s==='[data-question-id]'?[field]:[],addEventListener:(e,cb)=>events.set(e,cb)};
 const nodes={'jlms-quiz':{},'jlms-quiz-form':form,'jlms-quiz-status':{textContent:''},'jlms-quiz-timer':{textContent:''},'jlms-quiz-warning':{textContent:'Old warning'},'jlms-quiz-extension':{textContent:''},'jlms-quiz-duration':{textContent:'60'},'jlms-quiz-save':{addEventListener:(e,cb)=>events.set('save',cb)}};
 let phase='extend';globalThis.fetch=async(url,options)=>{
  requests.push({url:String(url),options});const post=options.method==='POST';
  const attempt={id:1,state:'open',started_at:1000,deadline:1180,revision:post?2:(phase==='conflict'?3:1)};
  return {status:200,ok:true,json:async()=>({data:{server_now:post?1061:1060,time_seconds:180,extension_seconds:120,attempt,questions:[]}})};
 };
 initialiseQuiz({enrollment:2,lesson:3,token:'csrf',server_now:1060,attempt:{id:1,revision:1,state:'open',started_at:1000,deadline:1060},urls:{save:'/save',submit:'/submit',state:'/state'},labels:{saved:'Saved',saving:'Saving',error:'Error',conflict:'Conflict',extended:'Deadline extended'}},{getElementById:id=>nodes[id]??null},{href:'https://example.invalid/quiz',assign:u=>moves.push(u)});
 input.checked=true; // An unsaved answer must survive learning that the manager extended the deadline.
 await intervals.find(x=>x.delay===1000).callback();
 assert.equal(requests.length,1);assert.equal(new URL(requests[0].url).searchParams.get('compact'),'1');
 assert.equal(requests[0].options.method,undefined);assert.equal(input.checked,true);
 assert.equal(nodes['jlms-quiz-duration'].textContent,'180');assert.equal(nodes['jlms-quiz-warning'].textContent,'');
 assert.match(nodes['jlms-quiz-extension'].textContent,/120/);assert.equal(moves.length,0);
 await events.get('save')();const body=new URLSearchParams(requests[1].options.body);
 assert.equal(body.get('revision'),'1');assert.equal(body.get('answers_json'),'{"10":["first"]}');
 assert.equal(input.checked,true); // Timing synchronization never replaces the answer form.
 ms=3000;phase='conflict';intervals.find(x=>x.delay===20000).callback();await new Promise(r=>setImmediate(r));
 assert.equal(nodes['jlms-quiz-status'].textContent,'Conflict');
 input.checked=false;await events.get('save')();assert.equal(requests.length,3); // Another tab's answer revision stops this tab.
 console.log(JSON.stringify({quiz_deadline_player_checks:13,status:'passed'}));
}finally{for(const[key,descriptor]of Object.entries(originals)){if(descriptor)Object.defineProperty(globalThis,key,descriptor);else delete globalThis[key];}}
