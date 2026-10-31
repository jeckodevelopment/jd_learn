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
