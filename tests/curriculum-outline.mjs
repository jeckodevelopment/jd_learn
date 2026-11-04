import {readFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const source = await readFile(new URL('../component/media/js/curriculum.js', import.meta.url), 'utf8');
const {dropOrder, persistOrder, initOutline, initUnsavedEditor} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
let checks = 0;
const equal = (a,b) => {assert.deepEqual(a,b);checks++;};
equal(dropOrder(['1','2','3'], '3', '1'), ['3','1','2']);
equal(dropOrder(['1','2','3'], '1', '3', true), ['2','3','1']);
equal(dropOrder(['1','2','3'], '9', '1'), ['1','2','3']);
equal(dropOrder(['1','2','3'], '1', '1'), ['1','2','3']);
const ids=['1','2','3'];dropOrder(ids,'1','3');equal(ids,['1','2','3']);
// Exercise serialized token/CAS and transport success, conflict, malformed and network failures.
globalThis.FormData = class {constructor() {this.values=[['task','module.reorder'],['jform[course_id]','8'],['jform[version_id]','9'],['jform[expected_version_revision]','4'],['nativeToken','1']];} [Symbol.iterator]() {return this.values[Symbol.iterator]();}};
const form={action:'/administrator/index.php'};
const group={dataset:{sortKind:'lesson',moduleId:'10'}};
const transport=async (url, options) => {
 equal(url,form.action);equal(options.credentials,'same-origin');
 equal(options.body.get('nativeToken'),'1');equal(options.body.get('jform[expected_version_revision]'),'4');
 equal(JSON.parse(options.body.get('jform[order_json]')),['2','1']);equal(options.body.get('jform[module_id]'),'10');equal(options.body.get('task'),'curriculum.saveOrder');
 return {ok:true,json:async()=>({success:true,data:{revision:5}})};
};
equal(await persistOrder(form,group,['2','1'],transport),5);
for (const response of [{ok:false,json:async()=>({success:false})},{ok:true,json:async()=>({success:false})},{ok:true,json:async()=>({success:true,data:{revision:'bad'}})},{ok:true,json:async()=>{throw Error('invalid JSON');}}]) {await assert.rejects(persistOrder(form,group,['2','1'],async()=>response));checks++;}
await assert.rejects(persistOrder(form,group,['2','1'],async()=>{throw Error('offline');}));checks++;
// Real drop listener with a minimal DOM: conflict restores order, while success reloads all revisions.
for (const success of [false,true]) {
 const listeners={},status={dataset:{saving:'Saving',error:'Failed',reload:'Reload'},textContent:'',append(){}};
 const list={dataset:{sortKind:'module',moduleId:'0'},children:[],append(item){this.children=this.children.filter(x=>x!==item);this.children.push(item);}};
 const row=id=>({dataset:{itemId:id},parentElement:list,hasAttribute:name=>name==='data-item-id',classList:{add(){},remove(){}},getBoundingClientRect:()=>({top:0,height:100}),closest(){return this;}});
 const a=row('1'),b=row('2');list.children=[a,b];
 const root={dataset:{editorMode:''},classList:{add(){}},querySelector:s=>s==='#jlms-reorder-form'?form:status,addEventListener:(type,callback)=>{listeners[type]=callback;},setAttribute(){},removeAttribute(){}};
 globalThis.document={createElement:()=>({})};globalThis.location={href:'https://example.invalid/programme'};
 let reloaded=0;
 initOutline(root,()=>{reloaded++;},async()=>({ok:success,json:async()=>({success,data:{revision:5}})}));
 const handle={closest:()=>a};
 listeners.dragstart({target:{closest:()=>handle},dataTransfer:{setData(){}},preventDefault(){}});
 await listeners.drop({target:{closest:()=>b},clientY:99,preventDefault(){}});
 equal(list.children.map(x=>x.dataset.itemId),success?['2','1']:['1','2']);equal(reloaded,success?1:0);
 if (!success) equal(status.textContent,'Failed');
 // Cross-module target must not send a request or move the source.
 const outsider=row('3');outsider.parentElement={};
 listeners.dragstart({target:{closest:()=>handle},dataTransfer:{setData(){}},preventDefault(){}});
 const before=list.children.slice();await listeners.drop({target:{closest:()=>outsider},clientY:99,preventDefault(){throw Error('foreign drop intercepted');}});equal(list.children,before);
}
let boundWhileEditing=false;
initOutline({dataset:{editorMode:'lesson'},querySelector:()=>form,addEventListener(){boundWhileEditing=true;}});
equal(boundWhileEditing,false);
console.log(JSON.stringify({curriculum_outline_checks:checks,status:'passed'}));

// Leaving the focused editor must protect input, but not cancel a valid save.
{
 const listeners = {}, events = {}, pending = [];
 const title = {name:'jform[title]', id:'title', type:'text', value:'Original'};
 const body = {name:'jform[body]', id:'body', type:'textarea', value:'<p>Original</p>'};
 const required = {name:'jform[required]', id:'required', type:'checkbox', checked:true};
 const hidden = {name:'token', id:'token', type:'hidden', value:'one'};
 const form = {elements:[title,body,required,hidden],addEventListener:(name,fn)=>{events[name]=fn;}};
 const host = {addEventListener:(name,fn)=>{listeners[name]=fn;},setTimeout:fn=>pending.push(fn)};
 let registry = {};
 const guard = initUnsavedEditor({querySelector:()=>form},host,()=>registry);
 const unload = () => {let warned=false; const event={preventDefault(){warned=true;}};listeners.beforeunload(event);return warned;};
 equal(guard.dirty(),false); equal(unload(),false);
 title.value='Changed'; equal(unload(),true);
 title.value='Original'; equal(unload(),false);
 required.checked=false; equal(unload(),true); required.checked=true;
 hidden.value='renewed'; equal(unload(),false);
 // Rich text can change without a bubbling input event or textarea synchronisation.
 let editorDirty=false;
 registry={body:{getType:()=> 'tinymce',getRawInstance:()=>({isDirty:()=>editorDirty})}};
 equal(unload(),false); editorDirty=true; equal(unload(),true);
 const saved={defaultPrevented:false};events.submit(saved);equal(unload(),false);
 // Late Joomla validation cancellation must leave the warning enabled.
 saved.defaultPrevented=true;equal(unload(),true);
 pending.splice(0).forEach(fn=>fn());equal(unload(),true);
 editorDirty=false; body.value='<p>Saved to textarea by editor, validation rejected</p>';equal(unload(),true);
 body.value='<p>Original</p>';equal(unload(),false);
 // Non-iframe editors expose current text through the Joomla decorator API.
 registry={body:{getType:()=> 'codemirror',getValue:()=>'<p>Edited</p>'}};equal(unload(),true);
 registry={body:{getValue(){throw Error('editor unavailable');}}};equal(unload(),true);
 listeners.pageshow();equal(unload(),true);
 let registered=false;
 equal(initUnsavedEditor({querySelector:()=>null},{addEventListener(){registered=true;}}),undefined);
 equal(registered,false);
 console.log(JSON.stringify({unsaved_editor_checks:18,status:'passed'}));
}
