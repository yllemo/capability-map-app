// Read-only integration checks against an explicitly provided running endpoint.
// MCP_TEST_URL=http://127.0.0.1:8769/mcp/index.php MCP_TEST_TOKEN=... node tests/mcp_http.mjs
import assert from 'node:assert/strict';
const url = process.env.MCP_TEST_URL;
const token = process.env.MCP_TEST_TOKEN;
if (!url || !token) throw new Error('Set MCP_TEST_URL and MCP_TEST_TOKEN');
const meta = {'io.modelcontextprotocol/protocolVersion':'2026-07-28','io.modelcontextprotocol/clientCapabilities':{}};
const base = {'Content-Type':'application/json',Accept:'application/json, text/event-stream',Authorization:`Bearer ${token}`,'MCP-Protocol-Version':'2026-07-28'};
async function rpc(method, params = {}, headers = {}) {
  return fetch(url,{method:'POST',headers:{...base,'Mcp-Method':method,...(params.name ? {'Mcp-Name':params.name}:{}),...headers},body:JSON.stringify({jsonrpc:'2.0',id:42,method,params:{_meta:meta,...params}})});
}
let response = await rpc('server/discover');
assert.equal(response.status,200);
assert.ok((await response.json()).result.supportedVersions.includes('2026-07-28'));
response = await rpc('tools/list');
assert.equal((await response.json()).result.tools.length,5);
response = await rpc('tools/call',{name:'maps_list',arguments:{}});
const maps = (await response.json()).result.structuredContent.maps;
if (maps.length) {
  response = await rpc('tools/call',{name:'capabilities_list',arguments:{map:maps[0].map,limit:1}});
  const caps = (await response.json()).result.structuredContent.capabilities;
  if (caps.length) {
    response = await rpc('tools/call',{name:'capabilities_read',arguments:{map:maps[0].map,id:caps[0].id}});
    const result = (await response.json()).result;
    assert.equal(result.isError,false);
    assert.equal(typeof result.structuredContent.markdown,'string');
  }
}
assert.equal((await fetch(url)).status,405);
assert.equal((await rpc('tools/list',{}, {Origin:'https://untrusted.invalid'})).status,403);
assert.equal((await rpc('tools/list',{}, {Authorization:'Bearer wrong-token'})).status,401);
assert.equal((await rpc('tools/list',{}, {'Content-Type':'text/plain'})).status,415);
assert.equal((await rpc('tools/list',{}, {Accept:'text/html'})).status,406);
response = await rpc('tools/list',{}, {'Mcp-Method':'tools/call'});
assert.equal(response.status,400);
assert.equal((await response.json()).error.code,-32020);
response = await rpc('tools/call',{name:'maps_list'}, {'Mcp-Name':'=?base64?bWFwc19saXN0?='});
assert.equal(response.status,200);
response = await rpc('tools/call',{name:'capabilities_read',arguments:{map:'__missing__',id:'none'}});
assert.equal((await response.json()).result.isError,true);
console.log('MCP HTTP integration tests passed');
