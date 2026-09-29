import { chromium } from 'playwright';
import { randomUUID } from 'node:crypto';
const base = (process.argv[2] || 'http://preview:8080').replace(/\/$/, '');
const origin = new URL(base).origin;
let browser;
const assert = (v, m) => { if (!v) throw new Error(m); };
const norm = s => s.replace(/\s+/g, ' ').trim();
async function poll(fn, ms = 5000) {
  const end = Date.now() + ms; let error;
  do { try { return await fn(); } catch (e) { error = e; } await new Promise(r => setTimeout(r, 80)); } while (Date.now() < end);
  throw error;
}
async function actor(who) {
  const context = await browser.newContext(); const page = await context.newPage();
  page.setDefaultTimeout(10000);
  if (who) {
    await page.goto(base + '/login');
    await page.getByLabel('Email', {exact:true}).fill(who === 'alice' ? 'alice@example.test' : 'bob@example.test');
    await page.getByLabel('Password', {exact:true}).fill(who === 'alice' ? 'Correct-Horse-1' : 'Battery-Staple-2');
    await page.getByRole('button', {name:'Sign in', exact:true}).click();
    await page.waitForURL(base + '/lists');
    await page.getByRole('heading', {name:'My lists', exact:true}).waitFor();
  } else await page.goto(base + '/');
  return {context, page};
}
async function makeList(page) {
  const name = 'T3 verifier ' + randomUUID();
  await page.goto(base + '/lists/new');
  await page.getByLabel('Name', {exact:true}).fill(name);
  await page.getByRole('button', {name:'Create list', exact:true}).click();
  await page.getByRole('heading', {name, exact:true}).waitFor();
  assert(/^\/lists\/[^/]+$/.test(new URL(page.url()).pathname), 'List creation did not reach a list');
  return page.url();
}
async function expected() {
  const r = await fetch('http://books:9100/search.json?q=dune&limit=10', {signal:AbortSignal.timeout(5000)});
  assert(r.ok, 'Expected-data API unavailable'); const data = await r.json();
  assert(Array.isArray(data.docs) && data.docs.length, 'Expected-data API has no books');
  return data.docs.slice(0, 10);
}
const results = p => p.getByRole('listitem').and(p.locator('[aria-label="Search results"] li, [aria-label="Search results"] [role="listitem"]'));
async function resultItems(p) {
  // Find the container by its accessible name, independently of its HTML tag/role.
  const container = p.getByRole('list', {name:'Search results', exact:true})
    .or(p.getByRole('region', {name:'Search results', exact:true}))
    .or(p.getByLabel('Search results', {exact:true}));
  await container.first().waitFor();
  return container.first().getByRole('listitem');
}
async function search(p, q) {
  await p.getByLabel('Search books', {exact:true}).fill(q);
  const start = Date.now();
  await p.getByRole('button', {name:'Search', exact:true}).click({noWaitAfter:true});
  return start;
}
async function mapped(p, docs) {
  const items = await resultItems(p);
  await poll(async () => {
    assert(await items.count() === docs.length, 'Result count differs from API (maximum 10)');
    for (let i = 0; i < docs.length; i++) {
      const row = items.nth(i), d = docs[i]; assert(await row.isVisible(), 'Result is not visible');
      const text = norm(await row.innerText());
      if (d.title) assert(text.includes(norm(d.title)), `Result ${i+1}: wrong title/order`);
      if (d.author_name?.length) assert(text.includes(norm(d.author_name[0])), `Result ${i+1}: missing first author`);
      if (d.first_publish_year != null) assert(new RegExp(`\\b${d.first_publish_year}\\b`).test(text), `Result ${i+1}: wrong year`);
      assert(await row.getByRole('button', {name:'Add', exact:true}).isVisible(), `Result ${i+1}: missing Add`);
    }
  });
  return items;
}
async function books(p) {
  const heading = p.getByRole('heading', {name:'Books', exact:true}); await heading.waitFor();
  return heading.evaluate(h => {
    // Read only the visible section after Books, stopping at the next peer heading.
    const root = h.closest('section, [role="region"]');
    if (root) return root.innerText;
    const nodes = [...document.body.querySelectorAll('*')]; const index = nodes.indexOf(h);
    const level = Number(h.tagName.slice(1)) || 2; const parts = [];
    for (const n of nodes.slice(index + 1)) {
      if (h.contains(n)) continue;
      if (/^H[1-6]$/.test(n.tagName) && Number(n.tagName.slice(1)) <= level) break;
      if (!n.children.length && n.getClientRects().length) parts.push(n.textContent);
    }
    return parts.join('\n');
  });
}
function occurrences(text, title) { return text.split('\n').map(norm).filter(s => s === norm(title)).length; }
async function added(p, d, count=1) {
  await poll(async () => { const t = await books(p); assert(occurrences(t,d.title) === count, `Books must contain exactly ${count} entry for ${d.title}`); if (count && d.author_name?.length) assert(t.includes(d.author_name[0]), 'Added book author missing'); });
}
async function unavailable(p, q, max, timeoutCheck=false) {
  const start = await search(p,q);
  await p.getByRole('alert').filter({hasText:'Book search is unavailable'}).waitFor({timeout:Math.max(1,max-(Date.now()-start))});
  const elapsed = Date.now()-start;
  assert(elapsed <= max, `${q}: alert took ${elapsed} ms`);
  // The visible terminal failure is the black-box evidence that the app gave up.
  if (timeoutCheck) assert(elapsed <= 5000, `Timeout gave up after ${elapsed} ms; maximum is 5000 ms`);
  await search(p,'dune'); await mapped(p,await expected());
}
const checks = {
 AC1: async p => {await search(p,'dune'); await mapped(p,await expected());},
 AC2: async p => {await search(p,'dune');await mapped(p,await expected());await search(p,'zzzz-nothing');await p.getByText('No books found',{exact:true}).waitFor(); const named=p.getByLabel('Search results',{exact:true}); if(await named.count()) assert(await named.getByRole('listitem').count()===0,'Empty search retained result items');assert(await p.getByRole('button',{name:'Add',exact:true}).count()===0,'Empty search retained Add results');},
 AC3: async p => {const failures=[];for(const q of ['__error__','__malformed__']) {try {await unavailable(p,q,6000);}catch(e){failures.push(q+': '+e.message);await p.reload();}}assert(!failures.length,failures.join('; '));},
 AC4: async p => {await unavailable(p,'__timeout__',8000,true);},
 AC5: async p => {const docs=await expected();await search(p,'dune');let rows=await mapped(p,docs);await rows.nth(0).getByRole('button',{name:'Add',exact:true}).click();await added(p,docs[0]);await p.reload();await added(p,docs[0]);await search(p,'dune');rows=await mapped(p,docs);await rows.nth(0).getByRole('button',{name:'Add',exact:true}).click();await added(p,docs[0]);await p.reload();await added(p,docs[0]);},
 AC6: async (p, owner) => {
   const docs=await expected(), url=p.url();const failures=[];
   for(const who of ['bob',null]) {
    const other=await actor(who);
    try {
     await p.goto(url);await search(p,'dune');const rows=await mapped(p,docs);const before=await books(p);
     assert(occurrences(before,docs[0].title)===0,'Replay target already contains book');
     const captured=[];const pending=[];
     const record=req=>{if(new URL(req.url()).origin===origin&&!['GET','HEAD','OPTIONS'].includes(req.method())) pending.push(req.allHeaders().then(headers=>captured.push({url:req.url(),method:req.method(),body:req.postDataBuffer(),headers})));};
     const block=async route=>{const req=route.request();if(new URL(req.url()).origin===origin&&!['GET','HEAD','OPTIONS'].includes(req.method())) await route.abort();else await route.continue();};
     const ownerCookies=await owner.cookies();p.on('request',record);await p.route('**/*',block);
     try {await rows.nth(0).getByRole('button',{name:'Add',exact:true}).click();await poll(async()=>assert(pending.length>0,'No add-book mutation captured'));await Promise.all(pending);}finally{p.off('request',record);await p.unroute('**/*',block);}
     assert(captured.length>0,'No add-book request captured');
     const otherCookies=await other.context.cookies();
     for(const req of captured){const headers={};for(const [k,v]of Object.entries(req.headers)){if(['cookie','host','content-length'].includes(k.toLowerCase()))continue;let decoded=v;try{decoded=decodeURIComponent(v);}catch{}const token=ownerCookies.find(c=>c.value===v||c.value===decoded);headers[k]=token?(otherCookies.find(c=>c.name===token.name)?.value||''):v;}
      await other.context.request.fetch(req.url,{method:req.method,headers,data:req.body??undefined,timeout:15000});
      await p.goto(url);assert(await books(p)===before,`${who||'anonymous'} replay changed owner's Books`);
     }
    }catch(e){failures.push(`${who||'anonymous'}: ${e.message}`);}finally{await other.context.close();}
   }
   assert(!failures.length,failures.join('; '));
 }
};
try {browser=await chromium.launch({args:['--no-sandbox','--disable-dev-shm-usage']});} catch(e) {for(const criterion of Object.keys(checks))console.log(JSON.stringify({criterion,result:'fail',detail:e.message}));}
if(browser){for(const [criterion,check]of Object.entries(checks)){let owner;try{owner=await actor('alice');await makeList(owner.page);await check(owner.page,owner.context);console.log(JSON.stringify({criterion,result:'pass'}));}catch(e){console.log(JSON.stringify({criterion,result:'fail',detail:e.message.slice(0,1000)}));}finally{if(owner)await owner.context.close();}}await browser.close();}
process.exitCode=0;
