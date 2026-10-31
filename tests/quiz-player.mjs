import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
const source = await readFile(new URL('../component/media/js/quiz.js',import.meta.url),'utf8');
const {QuizTransport} = await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'));
const bodies = []; let calls=0;
const transport = new QuizTransport({save:'/save'},{token:1},async (url,opts)=>{
 bodies.push(opts.body);calls++; if(calls===1)throw new Error('offline');
 if(calls===2)return {status:503};
 return {status:200,ok:true,json:async()=>({data:{attempt:{revision:2}}})};
},async()=>{});
const payload={attempt_id:1,revision:1,mutation_id:'fixed-retry-uuid',answers_json:'{"10":["a"]}'};
assert.equal((await transport.send('save',payload)).attempt.revision,2);
assert.equal(calls,3);assert.equal(new Set(bodies).size,1);
let rejected=0;
const conflict=new QuizTransport({save:'/save'},{},async()=>{rejected++;return {status:409,ok:false,json:async()=>({error:true,message:'Conflict'})};},async()=>{});
await assert.rejects(()=>conflict.send('save',payload),e=>e.status===409);assert.equal(rejected,1);
console.log(JSON.stringify({quiz_transport_checks:5,status:'passed'}));

// Exercise the actual view adapter with a small DOM and transport double, without claiming browser certification.
const {initialiseQuiz} = await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'));
const {webcrypto} = await import('node:crypto');
const originals = Object.fromEntries(['fetch','setInterval','clearInterval','addEventListener','crypto','confirm'].map(k=>[k,Object.getOwnPropertyDescriptor(globalThis,k)]));
try {
 Object.defineProperty(globalThis,'crypto',{value:webcrypto,configurable:true});
 const intervals=[];globalThis.setInterval=(callback,ms)=>{intervals.push({callback,ms});return intervals.length;};
 globalThis.clearInterval=()=>{};globalThis.addEventListener=()=>{};globalThis.confirm=()=>true;
 const input={value:'first',checked:false};const events=new Map(),saved=[];
 const field={dataset:{questionId:'10'},querySelectorAll:selector=>selector==='input:checked'?(input.checked?[input]:[]):[input]};
 const form={querySelectorAll:selector=>selector==='[data-question-id]'?[field]:[],addEventListener:(event,callback)=>events.set(event,callback)};
 const status={textContent:''};
 const elements={'jlms-quiz':{},'jlms-quiz-form':form,'jlms-quiz-status':status,'jlms-quiz-timer':{},'jlms-quiz-save':{addEventListener:(event,callback)=>events.set('save',callback)}};
 const document={getElementById:id=>elements[id]??null};
 const moves=[];const location={href:'https://example.invalid/index.php?option=com_jlms&view=quiz&id=2&lesson_id=3&attempt_id=1',assign:url=>moves.push(url)};
 const options={urls:{save:'/save',submit:'/submit',state:'/state'},enrollment:2,lesson:3,token:'csrf',server_now:1000,attempt:{id:1,revision:1,state:'open',deadline:null},labels:{saved:'Saved',saving:'Saving',error:'Error',conflict:'Conflict',confirm:'Submit?'}};
 let conflictMode=false;
 globalThis.fetch=async(url,request)=>{
  saved.push({url,body:new URLSearchParams(request.body)});
  if(conflictMode)return {status:409,ok:false,json:async()=>({error:true,message:'Conflict'})};
  return {status:200,ok:true,json:async()=>({data:{server_now:1010,attempt:{id:1,revision:2,state:'open',deadline:null}}})};
 };
 initialiseQuiz(options,document,location);
 const autosave=intervals.find(x=>x.ms===20000).callback;
 const flush=()=>new Promise(resolve=>setImmediate(resolve));
 autosave();await flush();assert.equal(saved.length,0); // Unchanged answers do not cause a write.
 input.checked=true;autosave();await flush();
 assert.equal(saved.length,1);assert.equal(saved[0].body.get('revision'),'1');assert.equal(saved[0].body.get('answers_json'),'{"10":["first"]}');
 const confirmed=status.textContent;autosave();await flush();assert.equal(status.textContent,confirmed); // No false newer save timestamp.
 input.checked=false;conflictMode=true;events.get('save')();await flush();
 assert.equal(saved[1].body.get('revision'),'2');assert.equal(saved[1].body.get('answers_json'),'{"10":[]}');assert.equal(status.textContent,'Conflict');
 input.checked=true;autosave();await flush();assert.equal(saved.length,2);assert.equal(moves.length,0); // Stale tab stops without overwriting.
 console.log(JSON.stringify({quiz_view_adapter_checks:10,status:'passed'}));
} finally {
 for(const [key,descriptor] of Object.entries(originals)){if(descriptor)Object.defineProperty(globalThis,key,descriptor);else delete globalThis[key];}
}
