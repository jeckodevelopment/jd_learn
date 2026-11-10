import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';

const source = await readFile(new URL('../component/media/js/audio-progress.js', import.meta.url), 'utf8');
const listeners = new Map();
const playerListeners = new Map();
const player = {currentTime:12.345,duration:60,dataset:{},addEventListener:(name,fn)=>playerListeners.set(name,fn)};
const status = {textContent:''};
globalThis.Joomla = {getOptions:()=>({url:'/audio-save',enrollment:4,lesson:9,token:'csrf',labels:{saved:'saved',error:'error'}})};
globalThis.document = {querySelector:selector=>selector==='#jlms-audio-player'?player:status};
globalThis.window = {setTimeout,clearTimeout,addEventListener:(name,fn)=>listeners.set(name,fn)};
let calls = [];
globalThis.fetch = async (_url,options) => {
  const body = new URLSearchParams(options.body);
  calls.push({position:body.get('position_ms'),token:body.get('csrf')});
  if(calls.length===1) return {ok:false,status:503,json:async()=>({success:false})};
  return {ok:true,status:200,json:async()=>({success:true})};
};
await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'));
playerListeners.get('pause')();
await new Promise(resolve=>setTimeout(resolve,650));
assert.deepEqual(calls,[{position:'12345',token:'1'},{position:'12345',token:'1'}],
  'transient failure retries the same saved audio position once');
assert.equal(status.textContent,'saved','successful retry reports the saved state');

globalThis.fetch = async (_url,options) => {
  const body = new URLSearchParams(options.body);
  calls.push({position:body.get('position_ms'),token:body.get('csrf')});
  return {ok:false,status:403,json:async()=>({success:false})};
};
player.currentTime=18;
playerListeners.get('seeked')();
await new Promise(resolve=>setTimeout(resolve,50));
assert.equal(calls.length,3,'permanent authorization failure is not retried');
assert.equal(calls[2].position,'18000','new position replaces a failed older queued point');
assert.equal(status.textContent,'error','permanent failure is visible to the learner');
console.log(JSON.stringify({audio_progress_retry_checks:5,status:'passed'}));
