import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import vm from 'node:vm';
const source=await readFile(new URL('../assets/admin-backup.js',import.meta.url),'utf8');
for(const mode of ['keep','overwrite']) for(const settings of [false,true]) for(const accept of [false,true]) {
  const fields={warning_confirmed:{value:'1'},existing_files:{value:mode},restore_settings:{checked:settings},backup:{files:[{name:'chosen-backup.zip'}]}};
  let submit, message, cancelled=false;
  const form={elements:{namedItem:name=>fields[name]},addEventListener:(event,handler)=>{assert.equal(event,'submit');submit=handler;}};
  vm.runInNewContext(source,{document:{getElementById:()=>form},window:{confirm:text=>{message=text;return accept;}}});
  submit({preventDefault:()=>{cancelled=true;}});
  assert.ok(message.includes('VARNING') && message.includes('chosen-backup.zip'));
  assert.ok(message.includes(mode==='overwrite' ? 'SKRIV ÖVER' : 'BEHÅLL'));
  assert.ok(message.includes(settings ? 'KONTON OCH INSTÄLLNINGAR ERSÄTTS' : 'admininställningar behålls'));
  assert.equal(cancelled,!accept);
  assert.equal(fields.warning_confirmed.value,accept?'1':'');
}
console.log('Backup warning dialog tests passed for both file policies, settings choices and cancel/confirm.');
