// Isolated end-to-end checks. PHP_BINARY must point to a PHP CLI with mbstring and zip.
// Optional PHP_EXTENSION_DIR enables those extensions for a portable Windows runtime.
import assert from 'node:assert/strict';
import {spawn} from 'node:child_process';
import {mkdtemp, mkdir, cp, writeFile, readFile, rm} from 'node:fs/promises';
import {createHmac} from 'node:crypto';
import os from 'node:os';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const project=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const fixture=await mkdtemp(path.join(os.tmpdir(),'capmap-backup-http-'));
const port=Number(process.env.CAPMAP_TEST_PORT || 8774);
const base=`http://127.0.0.1:${port}`;
let server;
try {
  for(const folder of ['app','admin']) await cp(path.join(project,folder),path.join(fixture,folder),{recursive:true});
  for(const folder of ['editor','config','content/a','content/b']) await mkdir(path.join(fixture,folder),{recursive:true});
  await cp(path.join(project,'editor/_auth.php'),path.join(fixture,'editor/_auth.php'));
  await writeFile(path.join(fixture,'config/app.php'),`<?php return ['content_root'=>__DIR__.'/../content','content_dirs'=>['a'=>['path'=>__DIR__.'/../content/a','label'=>'A'],'b'=>['path'=>__DIR__.'/../content/b','label'=>'B']]];`);
  await writeFile(path.join(fixture,'config/auth.php'),`<?php return ['session_secret'=>'fixture-only-secret','users'=>['admin'=>['role'=>'admin','password'=>'fixture-only-password'],'reader'=>['role'=>'editor','password'=>'fixture-only-password']]];`);
  await writeFile(path.join(fixture,'content/.capmap-admin.php'),`<?php return ['app'=>['site_name'=>'Fixture site']];`);
  const original='---\nid: original\nname: Original\n---\nOriginal body\n';
  await writeFile(path.join(fixture,'content/a/original.md'),original);
  await writeFile(path.join(fixture,'content/b/reference.md'),'---\nid: ref\nredirect_map: a\nredirect_id: original\n---\n');
  await writeFile(path.join(fixture,'content/root-note.txt'),'Root note');
  const phpArgs=process.env.PHP_EXTENSION_DIR ? ['-d',`extension_dir=${process.env.PHP_EXTENSION_DIR}`,'-d','extension=mbstring','-d','extension=zip'] : [];
  server=spawn(process.env.PHP_BINARY || 'php',[...phpArgs,'-S',`127.0.0.1:${port}`,'-t',fixture],{stdio:['ignore','ignore','pipe']});
  await new Promise((resolve,reject)=>{
    const timer=setTimeout(()=>reject(new Error('PHP server startup timed out')),5000);
    server.once('error',error=>{clearTimeout(timer);reject(error);});
    server.stderr.once('data',data=>{clearTimeout(timer);String(data).includes('started') ? resolve() : reject(new Error(String(data)));});
  });
  function signedCookie(user) {
    const expires=Math.floor(Date.now()/1000)+3600;
    return `capmap_editor=${user}|${expires}|${createHmac('sha256','fixture-only-secret').update(`${user}|${expires}`).digest('hex')}`;
  }
  let response=await fetch(`${base}/admin/backup.php`,{method:'POST',redirect:'manual'});
  assert.equal(response.status,302,'Guests must authenticate');
  response=await fetch(`${base}/admin/backup.php`,{method:'POST',redirect:'manual',headers:{Cookie:signedCookie('reader')}});
  assert.equal(response.status,403,'Only administrators can back up');
  let cookie=signedCookie('admin');
  response=await fetch(`${base}/admin/index.php?tab=backup`,{headers:{Cookie:cookie}});
  assert.equal(response.status,200);
  for(const item of response.headers.getSetCookie()) cookie+='; '+item.split(';')[0];
  const html=await response.text();
  assert.ok(html.includes('Ladda ner ZIP-backup'));
  const csrf=html.match(/name="csrf_token" value="([^"]+)"/)[1];
  response=await fetch(`${base}/admin/backup.php`,{method:'POST',redirect:'manual',headers:{Cookie:cookie},body:new URLSearchParams({action:'export',csrf_token:'wrong'})});
  assert.equal(response.status,303);
  assert.ok(!(response.headers.get('content-type')||'').includes('application/zip'),'CSRF failure cannot download data');
  response=await fetch(`${base}/admin/backup.php`,{method:'POST',headers:{Cookie:cookie},body:new URLSearchParams({action:'export',csrf_token:csrf})});
  assert.equal(response.status,200);
  assert.ok(response.headers.get('content-type').includes('application/zip'));
  const archive=await response.arrayBuffer();
  assert.ok(archive.byteLength>100);
  await writeFile(path.join(fixture,'content/a/original.md'),'Changed after export');
  await writeFile(path.join(fixture,'content/root-note.txt'),'Changed root note');
  const upload=new FormData();
  upload.set('action','import');upload.set('csrf_token',csrf);upload.set('confirmation','IMPORTERA');
  upload.set('existing_files','overwrite');upload.set('restore_settings','1');
  upload.set('backup',new Blob([archive],{type:'application/zip'}),'backup.zip');
  response=await fetch(`${base}/admin/backup.php`,{method:'POST',redirect:'manual',headers:{Cookie:cookie},body:upload});
  assert.equal(response.status,303);
  assert.equal(await readFile(path.join(fixture,'content/a/original.md'),'utf8'),'Changed after export','No import without warning confirmation');
  upload.set('warning_confirmed','1');
  response=await fetch(`${base}/admin/backup.php`,{method:'POST',redirect:'manual',headers:{Cookie:cookie},body:upload});
  assert.equal(response.status,303);
  const config=JSON.parse(await readFile(path.join(fixture,'content/.capmap-config.json'),'utf8'));
  assert.equal(config.content_root,'content');
  assert.equal(await readFile(path.join(fixture,'content',config.content_dirs.a.folder,'original.md'),'utf8'),original);
  assert.equal(await readFile(path.join(fixture,'content/root-note.txt'),'utf8'),'Root note');
  assert.equal(await readFile(path.join(fixture,'content/a/original.md'),'utf8'),'Changed after export','Original files are retained');
  response=await fetch(`${base}/admin/index.php?tab=backup`,{headers:{Cookie:cookie}});
  assert.ok((await response.text()).includes('Importen är klar: 2 kartor och 3 filer'));
  console.log('Backup HTTP tests passed: administrator access, CSRF, ZIP download, multipart import, config activation and round-trip restoration.');
} finally {
  if(server && server.exitCode===null) {const stopped=new Promise(resolve=>server.once('exit',resolve));server.kill();await stopped;}
  const resolved=path.resolve(fixture);
  assert.ok(resolved.startsWith(path.resolve(os.tmpdir())+path.sep) && path.basename(resolved).startsWith('capmap-backup-http-'));
  await rm(resolved,{recursive:true,force:true});
}
