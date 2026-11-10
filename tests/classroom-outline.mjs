import {readFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const source = await readFile(new URL('../component/media/js/classroom-outline.js', import.meta.url), 'utf8');
const {initClassroomOutline} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
let checks = 0;
const equal = (a,b) => {assert.equal(a,b);checks++;};

function fixture(isMobile) {
  const handlers = {};
  const listeners = {};
  const toggle = {hidden:true,dataset:{showLabel:'Show curriculum',hideLabel:'Hide curriculum'},textContent:'Show curriculum',attributes:{'aria-expanded':'true'},
    setAttribute(name,value){this.attributes[name]=value;},getAttribute(name){return this.attributes[name];},
    addEventListener(name,callback){handlers[name]=callback;}};
  const outline = {hidden:false};
  const documentRef = {querySelector(selector){return selector==='#jlms-classroom-outline-toggle'?toggle:outline;}};
  const media = {matches:isMobile,addEventListener(name,callback){listeners[name]=callback;}};
  initClassroomOutline({documentRef,matchMedia:()=>media});
  return {toggle,outline,handlers,listeners,media};
}

const mobile=fixture(true);
equal(mobile.toggle.hidden,false); equal(mobile.toggle.getAttribute('aria-expanded'),'false');
equal(mobile.outline.hidden,true); equal(mobile.toggle.textContent,'Show curriculum');
mobile.handlers.click(); equal(mobile.toggle.getAttribute('aria-expanded'),'true');
equal(mobile.outline.hidden,false); equal(mobile.toggle.textContent,'Hide curriculum');
mobile.handlers.click(); equal(mobile.outline.hidden,true);
mobile.media.matches=false; mobile.listeners.change();
equal(mobile.toggle.hidden,true); equal(mobile.outline.hidden,false);

const desktop=fixture(false);
equal(desktop.toggle.hidden,true); equal(desktop.toggle.getAttribute('aria-expanded'),'true');
equal(desktop.outline.hidden,false);
let queries=0; initClassroomOutline({documentRef:{querySelector(){queries++;return null;}},matchMedia:()=>({matches:true})});
equal(queries,2);
console.log(JSON.stringify({classroom_outline_checks:checks,status:'passed'}));
