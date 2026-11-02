import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const installSource=fs.readFileSync(new URL('../public/assets/pwa.js',import.meta.url),'utf8');
const workerSource=fs.readFileSync(new URL('../sw.js',import.meta.url),'utf8');
let checks=0;
const check=(condition,message)=>{assert.ok(condition,message);checks++;};
const element=()=>({
  hidden:false,attributes:{},listeners:{},focused:false,
  addEventListener(type,callback){(this.listeners[type]??=[]).push(callback);},
  setAttribute(name,value){this.attributes[name]=value;},
  removeAttribute(name){delete this.attributes[name];},
  focus(){this.focused=true;},
  async fire(type){let prevented=false;for(const listener of this.listeners[type]??[])await listener({preventDefault(){prevented=true;}});return prevented;},
});

function installHarness(options={}) {
  const button=element(),closeButton=element(),secureNote=element();
  const dialog={...element(),open:false,shows:0,
    showModal(){this.open=true;this.shows++;},close(){this.open=false;},
    querySelector(selector){return selector==='[data-pwa-insecure]'?secureNote:closeButton;},
    querySelectorAll(){return [closeButton];},
  };
  const handlers={},registrations=[];
  const displayMode={matches:options.standalone??false,addEventListener(type,listener){this.listener=listener;}};
  const navigator={standalone:options.iosStandalone??false,serviceWorker:{register(url,config){registrations.push({url,config});return Promise.resolve({});}}};
  const window={isSecureContext:options.secure??true,location:new URL('https://school.invalid/HW/index.php'),matchMedia(){return displayMode;},addEventListener(type,listener){(handlers[type]??=[]).push(listener);}};
  const document={readyState:'complete',currentScript:{dataset:{workerUrl:options.workerUrl??'/HW/sw.js',workerScope:'/HW/'}},
    querySelector(selector){return selector==='#install-dialog'?dialog:null;},
    querySelectorAll(){return [button];},
  };
  vm.runInNewContext(installSource,{window,document,navigator,URL});
  return {button,closeButton,dialog,secureNote,registrations,displayMode,
    fire(type,event={}){for(const listener of handlers[type]??[])listener(event);},
  };
}

const native=installHarness();
check(native.dialog.shows===0,'Instructions must not open unsolicited.');
check(native.registrations.length===1 && native.registrations[0].config.scope==='https://school.invalid/HW/','Worker scope must preserve the installation directory.');
let prompts=0,prevented=false;
native.fire('beforeinstallprompt',{preventDefault(){prevented=true;},async prompt(){prompts++;},userChoice:Promise.resolve({outcome:'accepted'})});
check(prevented && prompts===0,'Receiving an install event must not trigger a prompt.');
await native.button.fire('click');
check(prompts===1 && native.button.hidden,'Only a click may prompt; accepting hides the install action.');

const dismissed=installHarness();
dismissed.fire('beforeinstallprompt',{preventDefault(){},async prompt(){},userChoice:Promise.resolve({outcome:'dismissed'})});
await dismissed.button.fire('click');
check(!dismissed.button.hidden && dismissed.dialog.shows===0,'Dismissing must not trigger another modal.');
await dismissed.button.fire('click');
check(dismissed.dialog.open && dismissed.dialog.shows===1,'A later deliberate click can show manual instructions.');
await dismissed.closeButton.fire('click');
check(!dismissed.dialog.open && dismissed.button.focused,'Closing instructions restores focus.');

const manual=installHarness({secure:false});
await manual.button.fire('click');
check(manual.dialog.open && !manual.secureNote.hidden,'An unsupported/insecure browser must receive honest manual instructions.');
check(manual.registrations.length===0,'A worker must not be registered in an insecure context.');
check(installHarness({standalone:true}).button.hidden && installHarness({iosStandalone:true}).button.hidden,'Already installed apps must not show the install action.');
const spoof=installHarness({workerUrl:'https://attacker.invalid/sw.js'});
check(spoof.registrations.length===0,'Worker configuration must not send registration to another origin.');
const later=installHarness();
later.fire('appinstalled');
check(later.button.hidden,'External browser installation must also hide the action.');

let fetchListener;
let fetchMode='success';
const online=new Response('Only the server response',{status:200});
vm.runInNewContext(workerSource,{
  self:{location:{origin:'https://school.invalid'},addEventListener(type,listener){if(type==='fetch')fetchListener=listener;}},
  URL,Response,
  async fetch(){if(fetchMode==='offline')throw new Error('Offline');return online;},
});
const request=async (changes={})=>{
  let response;
  fetchListener({request:{url:'https://school.invalid/HW/index.php?r=dashboard',method:'GET',mode:'navigate',...changes},respondWith(value){response=value;}});
  return response?await response:null;
};
check(await request()===online,'Online account pages must pass through the server response unchanged.');
fetchMode='offline';
const offline=await request();
check(offline.status===503 && offline.headers.get('Cache-Control')==='no-store','Offline fallback must not cache private navigation.');
const offlineText=await offline.text();
check(offlineText.includes('Потрібне з’єднання') && !offlineText.includes('dashboard'),'Offline output must be generic and contain no account route/data.');
check(await request({method:'POST'})===null && await request({mode:'no-cors'})===null && await request({url:'https://attacker.invalid/'})===null,'Form submissions, resources and other origins must not be intercepted.');
console.log(`OK: ${checks} перевірок встановлення та безпечної роботи без мережі.`);
