/** Real browser navigation on a controlled fixture, not Joomla visual acceptance. */
import {createServer} from 'node:http';
import {readFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const {chromium} = await import(process.env.JLMS_PLAYWRIGHT_MODULE || 'playwright');
const script = await readFile(new URL('../component/media/js/curriculum.js', import.meta.url), 'utf8');
const html = `<!doctype html><html><body><section id="jlms-admin-curriculum" data-editor-mode="lesson">
<div class="jlms-focused-editor"><form action="/saved" method="get"><input id="title" name="title" value="Original" required>
<textarea id="body" name="body">Original text</textarea><button>Save</button></form></div>
<a href="/other">Leave</a><script type="module" src="/curriculum.js"></script></section></body></html>`;
const server = createServer((req,res) => {
 res.setHeader('Content-Type', req.url === '/curriculum.js' ? 'text/javascript' : 'text/html');
 res.end(req.url === '/curriculum.js' ? script : req.url === '/' ? html : '<p>Arrived</p>');
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
const base=`http://127.0.0.1:${server.address().port}`;
let browser;
let checks=0;
try {
 browser=await chromium.launch();
 for (const viewport of [{width:1280,height:800},{width:390,height:844}]) {
  const page=await browser.newPage({viewport});
  await page.goto(base); await page.locator('#title').click();
  let dialogs=0;
  page.on('dialog',async dialog=>{dialogs++;assert.equal(dialog.type(),'beforeunload');await dialog.dismiss();});
  await page.locator('a').click(); await page.waitForURL(base+'/other'); assert.equal(dialogs,0);checks++;
  await page.goto(base);await page.locator('#title').fill('Changed');
  await page.locator('a').click({noWaitAfter:true});
  await page.waitForTimeout(100);assert.equal(dialogs,1);assert.equal(await page.locator('#title').inputValue(),'Changed');checks++;
  // Joomla-style late cancellation must not bypass protection.
  await page.evaluate(()=>document.querySelector('form').addEventListener('submit',event=>event.preventDefault()));
  await page.locator('button').click();await page.locator('a').click({noWaitAfter:true});
  await page.waitForTimeout(100);assert.equal(dialogs,2);checks++;
  page.removeAllListeners('dialog');page.on('dialog',dialog=>dialog.accept());await page.goto(base);
  page.removeAllListeners('dialog');dialogs=0;page.on('dialog',async dialog=>{dialogs++;await dialog.dismiss();});
  await page.locator('#title').fill('Saved');await page.locator('button').click();
  await page.waitForURL('**/saved?**');assert.equal(dialogs,0);checks++;
  await page.goto(base);await page.locator('#title').fill('');await page.locator('button').click();
  await page.locator('a').click({noWaitAfter:true});await page.waitForTimeout(100);assert.equal(dialogs,1);checks++;
  // Model iframe edits without textarea input; native warning still must fire.
  page.removeAllListeners('dialog');page.on('dialog',dialog=>dialog.accept());await page.goto(base);
  page.removeAllListeners('dialog');dialogs=0;page.on('dialog',async dialog=>{dialogs++;await dialog.dismiss();});
  await page.locator('#title').click();
  await page.evaluate(()=>{window.Joomla={editors:{instances:{body:{getType:()=> 'tinymce',getRawInstance:()=>({isDirty:()=>true})}}}};});
  await page.locator('a').click({noWaitAfter:true});await page.waitForTimeout(100);assert.equal(dialogs,1);checks++;
  await page.close({runBeforeUnload:false});
 }
 console.log(JSON.stringify({curriculum_browser_checks:checks,status:'passed',scope:'controlled desktop/mobile viewport fixture; not Joomla visual acceptance'}));
} finally {await browser?.close();await new Promise(resolve=>server.close(resolve));}
