(()=>{
 const form=document.getElementById('payment-form'); if(!form)return;
 const ops=[...form.querySelectorAll('input[name="tipoOperacion"]')], methodSection=form.querySelector('[data-payment-method-section]'), method=document.getElementById('medioPago');
 const panels=[...form.querySelectorAll('[data-method-panel]')], base=document.getElementById('importeCobro'), adjType=document.getElementById('adjustmentType'), adjPct=document.getElementById('adjustmentPercent'), final=document.getElementById('importeFinalEstimado'), received=document.getElementById('importeRecibido'), change=document.getElementById('vueltoEstimado');
 const money=n=>new Intl.NumberFormat('es-AR',{style:'currency',currency:'ARS'}).format(Number.isFinite(n)?n:0);
 const parseAmount=value=>{
  let raw=String(value??'').trim().replace(/[^0-9,.\-]/g,'');if(!raw||raw==='-')return 0;
  const negative=raw.startsWith('-');raw=raw.replace(/^-/,'');
  const comma=raw.lastIndexOf(','),dot=raw.lastIndexOf('.');
  if(comma>=0&&dot>=0){raw=comma>dot?raw.replace(/\./g,'').replace(',','.'):raw.replace(/,/g,'');}
  else if(comma>=0){const decimals=raw.length-comma-1;raw=decimals<=2?raw.replace(/\./g,'').replace(',','.'):raw.replace(/,/g,'');}
  else if(dot>=0){const parts=raw.split('.');if(parts.length>2||(parts.length===2&&parts[1].length===3))raw=raw.replace(/\./g,'');}
  const parsed=Number(raw);return Number.isFinite(parsed)?(negative?-parsed:parsed):0;
 };
 const finalAmount=()=>{const b=parseAmount(base.value),p=parseAmount(adjPct.value);return adjType.value==='DESCUENTO'?Math.max(0,b-b*p/100):adjType.value==='RECARGO'?b+b*p/100:b};
 function recalc(){const f=finalAmount();final.value=money(f);if(received){const receivedAmount=parseAmount(received.value);change.value=money(Math.max(0,receivedAmount-f));received.setCustomValidity((ops.find(x=>x.checked)?.value||'PAGO')==='PAGO'&&method.value==='EFECTIVO'&&received.value.trim()!==''&&receivedAmount+0.004<f?'El importe recibido no alcanza para cubrir el cobro.':'');}}
 function setRequired(panel,on){panel.querySelectorAll('input,select,textarea').forEach(el=>{if(on && el.dataset.optional!=='1')el.setAttribute('required','required');else el.removeAttribute('required');});}
 function refresh(){const op=ops.find(x=>x.checked)?.value||'PAGO';methodSection.hidden=op!=='PAGO';method.required=op==='PAGO';panels.forEach(p=>{const active=op==='PAGO'&&p.dataset.methodPanel===method.value;p.hidden=!active;setRequired(p,active && p.dataset.methodPanel!=='TARJETA' && p.dataset.methodPanel!=='TRANSFERENCIA');});if(op==='PAGO'&&method.value==='EFECTIVO'&&received&&!received.value.trim())received.value=finalAmount().toFixed(2);recalc();}
 ops.forEach(x=>x.addEventListener('change',refresh));method.addEventListener('change',refresh);[base,adjType,adjPct,received].forEach(x=>x&&x.addEventListener('input',recalc));adjType.addEventListener('change',recalc);refresh();
})();
