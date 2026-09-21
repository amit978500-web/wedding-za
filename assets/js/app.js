(() => {
  'use strict';
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  const storage={get(k,f=[]){try{return JSON.parse(localStorage.getItem(k))??f}catch{return f}},set(k,v){localStorage.setItem(k,JSON.stringify(v))}};
  const toast=(msg)=>{const el=$('#toast');if(!el)return;el.textContent=msg;el.classList.add('show');clearTimeout(window.__wzToast);window.__wzToast=setTimeout(()=>el.classList.remove('show'),2600)};

  window.addEventListener('DOMContentLoaded',()=>{
    document.body.classList.add('is-loading');
    setTimeout(()=>{document.body.classList.remove('is-loading');$('#preloader')?.classList.add('hide')},550);
    initHeader(); initReveal(); initParallax(); initTransitions(); initShortlist(); initIdeaSaves(); initForms(); initVendorFilters(); initPlanner(); initBudget(); initInviteBuilder(); initShare();
  });

  function initHeader(){
    const header=$('#siteHeader'), progress=$('#pageProgress'), btn=$('#menuToggle'), menu=$('#mobileMenu');
    const onScroll=()=>{const y=window.scrollY, max=document.documentElement.scrollHeight-innerHeight; header?.classList.toggle('scrolled',y>12); if(progress)progress.style.width=`${max>0?(y/max)*100:0}%`;};
    addEventListener('scroll',onScroll,{passive:true});onScroll();
    const toggle=(open)=>{document.body.classList.toggle('menu-open',open);btn?.classList.toggle('active',open);btn?.setAttribute('aria-expanded',String(open));menu?.classList.toggle('open',open);menu?.setAttribute('aria-hidden',String(!open));};
    btn?.addEventListener('click',()=>toggle(!menu?.classList.contains('open')));
    menu?.addEventListener('click',e=>{if(e.target.closest('a'))toggle(false)});
    addEventListener('keydown',e=>{if(e.key==='Escape')toggle(false)});
  }

  function initReveal(){
    const els=$$('.reveal'); if(!els.length)return;
    if(!('IntersectionObserver'in window)){els.forEach(e=>e.classList.add('in-view'));return}
    const io=new IntersectionObserver(entries=>entries.forEach(x=>{if(x.isIntersecting){x.target.classList.add('in-view');io.unobserve(x.target)}}),{threshold:.12,rootMargin:'0px 0px -30px'});els.forEach(e=>io.observe(e));
  }

  function initCursor(){
    if(matchMedia('(pointer:coarse)').matches)return; const dot=$('.cursor-dot'),ring=$('.cursor-ring'); if(!dot||!ring)return;
    let x=0,y=0,rx=0,ry=0; const move=e=>{x=e.clientX;y=e.clientY;dot.style.opacity=ring.style.opacity='1';dot.style.transform=`translate(${x-2.5}px,${y-2.5}px)`};addEventListener('mousemove',move);
    const loop=()=>{rx+=(x-rx)*.14;ry+=(y-ry)*.14;ring.style.transform=`translate(${rx-17}px,${ry-17}px)`;requestAnimationFrame(loop)};loop();
    $$('a,button,input,select,textarea').forEach(el=>{el.addEventListener('mouseenter',()=>ring.classList.add('is-link'));el.addEventListener('mouseleave',()=>ring.classList.remove('is-link'))});
  }

  function initParallax(){
    if(matchMedia('(prefers-reduced-motion: reduce)').matches)return; const els=$$('[data-parallax]');if(!els.length)return;
    let ticking=false;const paint=()=>{const vh=innerHeight;els.forEach(el=>{const r=el.getBoundingClientRect(), speed=parseFloat(el.dataset.parallax||'.08'),mid=r.top+r.height/2-vh/2;el.style.transform=`translate3d(0,${mid*-speed}px,0)`});ticking=false};addEventListener('scroll',()=>{if(!ticking){requestAnimationFrame(paint);ticking=true}},{passive:true});paint();
  }

  function initMagnetic(){
    if(matchMedia('(pointer:coarse)').matches||matchMedia('(prefers-reduced-motion: reduce)').matches)return;
    $$('.magnetic').forEach(el=>{el.addEventListener('mousemove',e=>{const r=el.getBoundingClientRect(),x=e.clientX-r.left-r.width/2,y=e.clientY-r.top-r.height/2;el.style.transform=`translate(${x*.12}px,${y*.18}px)`});el.addEventListener('mouseleave',()=>el.style.transform='')});
  }

  function initTilt(){
    if(matchMedia('(pointer:coarse)').matches||matchMedia('(prefers-reduced-motion: reduce)').matches)return;
    $$('.tilt-card').forEach(card=>{
      let frame=0;
      const move=e=>{
        cancelAnimationFrame(frame);
        frame=requestAnimationFrame(()=>{
          const r=card.getBoundingClientRect();
          const px=(e.clientX-r.left)/r.width-.5, py=(e.clientY-r.top)/r.height-.5;
          card.style.setProperty('--mx',`${(px+.5)*100}%`);
          card.style.setProperty('--my',`${(py+.5)*100}%`);
          card.style.transform=`perspective(1100px) rotateX(${py*-3.2}deg) rotateY(${px*4.2}deg) translateY(-6px)`;
        });
      };
      card.addEventListener('mousemove',move);
      card.addEventListener('mouseleave',()=>{cancelAnimationFrame(frame);card.style.transform='';card.style.setProperty('--mx','50%');card.style.setProperty('--my','50%')});
    });
  }

  function initSpotlights(){
    if(matchMedia('(pointer:coarse)').matches)return;
    $$('.city-tile,.category-luxe,.form-card,.planner-group,.fact-card,.budget-card,.enquiry-card,.auth-card').forEach(el=>{
      el.dataset.spotlight='';
      el.addEventListener('mousemove',e=>{
        const r=el.getBoundingClientRect();
        el.style.setProperty('--mx',`${e.clientX-r.left}px`);
        el.style.setProperty('--my',`${e.clientY-r.top}px`);
      });
    });
    $$('.vendor-card').forEach(el=>el.addEventListener('mousemove',e=>{
      const r=el.getBoundingClientRect();
      el.style.setProperty('--mx',`${e.clientX-r.left}px`);
      el.style.setProperty('--my',`${e.clientY-r.top}px`);
    }));
  }

  function initHeroMotion(){
    if(matchMedia('(pointer:coarse)').matches||matchMedia('(prefers-reduced-motion: reduce)').matches)return;
    const hero=$('.hero-premium'), orb=$('.hero-orb');if(!hero||!orb)return;
    hero.addEventListener('mousemove',e=>{
      const r=hero.getBoundingClientRect(), x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
      orb.style.marginLeft=`${x*12}px`;
      orb.style.marginTop=`${y*10}px`;
    });
    hero.addEventListener('mouseleave',()=>{orb.style.marginLeft='';orb.style.marginTop=''});
  }

  function initTransitions(){
    const wipe=$('#pageWipe'); if(!wipe)return;
    $$('a[href]').forEach(a=>a.addEventListener('click',e=>{const href=a.getAttribute('href')||'';if(href.startsWith('#')||href.startsWith('mailto:')||href.startsWith('tel:')||a.target==='_blank'||e.ctrlKey||e.metaKey||e.shiftKey)return;let u;try{u=new URL(a.href,location.href)}catch{return}if(u.origin!==location.origin)return;e.preventDefault();wipe.classList.add('active');setTimeout(()=>location.href=a.href,470)}));
  }

  function shortlistIds(){return storage.get('wz_shortlist',[])}
  function initShortlist(){
    const sync=()=>{const ids=shortlistIds();$('#shortlistCount')&&( $('#shortlistCount').textContent=ids.length );$$('[data-shortlist]').forEach(b=>{const on=ids.includes(b.dataset.shortlist);b.classList.toggle('active',on);if(b.classList.contains('heart-btn'))b.textContent=on?'♥':'♡';else b.textContent=on?'♥ Saved to shortlist':'♡ Save to shortlist';b.setAttribute('aria-pressed',String(on))});renderShortlist(ids)};
    document.addEventListener('click',e=>{const b=e.target.closest('[data-shortlist]');if(!b)return;e.preventDefault();const id=b.dataset.shortlist,ids=shortlistIds(),next=ids.includes(id)?ids.filter(x=>x!==id):[...ids,id];storage.set('wz_shortlist',next);toast(next.includes(id)?'Saved to your shortlist ♡':'Removed from shortlist');sync()});sync();
  }
  function renderShortlist(ids){
    const root=$('#shortlistGrid');if(!root)return; const cards=$$('.vendor-card[data-vendor-id]',root);cards.forEach(c=>c.classList.toggle('hidden',!ids.includes(c.dataset.vendorId))); const empty=$('#shortlistEmpty');if(empty)empty.hidden=ids.length>0;
  }

  function initIdeaSaves(){
    const key='wz_ideas', sync=()=>{const ids=storage.get(key,[]);$$('[data-save-idea]').forEach(b=>{const on=ids.includes(b.dataset.saveIdea);b.classList.toggle('saved',on);b.textContent=on?'♥':'♡'})};
    document.addEventListener('click',e=>{const b=e.target.closest('[data-save-idea]');if(!b)return;e.preventDefault();const id=b.dataset.saveIdea,ids=storage.get(key,[]),next=ids.includes(id)?ids.filter(x=>x!==id):[...ids,id];storage.set(key,next);toast(next.includes(id)?'Idea saved to your board':'Idea removed');sync()});sync();
  }

  function initForms(){
    $$('form[data-async]').forEach(form=>form.addEventListener('submit',async e=>{e.preventDefault();const btn=form.querySelector('[type=submit]'),box=form.querySelector('.success-box');const label=btn?.textContent; if(btn){btn.disabled=true;btn.textContent='Sending…'};
      try{const res=await fetch(form.action||'api/lead.php',{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'}});const out=await res.json();if(!res.ok||!out.ok)throw new Error(out.message||'Could not submit');form.reset();if(box){box.textContent=out.message||'Thanks — we received it.';box.classList.add('show')}toast(out.message||'Thanks — we received it.');}
      catch(err){toast(err.message||'Something went wrong. Please try again.')}finally{if(btn){btn.disabled=false;btn.textContent=label}}
    }));
  }

  function initVendorFilters(){
    const grid=$('#vendorListing');if(!grid)return;
    const cards=$('.vendor-card',grid),q=$('#filterSearch'),event=$('#filterEvent'),city=$('#filterCity'),cat=$('#filterCategory'),sort=$('#filterSort'),count=$('#filterCount'),reset=$('#filterReset'),bar=$('#activeFilterBar');
    const controls={event,city,category:cat};
    const syncUrl=()=>{
      const url=new URL(location.href);
      ['event','city','category'].forEach(k=>{const el=controls[k];if(el?.value)url.searchParams.set(k,el.value);else url.searchParams.delete(k)});
      history.replaceState({},'',url);
    };
    const renderChips=()=>{
      if(!bar)return;
      const chips=[];
      Object.entries(controls).forEach(([key,el])=>{if(el?.value)chips.push(`<button type="button" data-clear-filter="${key}">${el.value} ×</button>`)});
      bar.innerHTML=chips.join('');
      bar.classList.toggle('has-filters',chips.length>0);
    };
    const run=()=>{
      const term=(q?.value||'').toLowerCase().trim(),ev=event?.value||'',cv=city?.value||'',cc=cat?.value||'';
      let shown=cards.filter(c=>{const events=(c.dataset.events||'').split('|');const ok=(!term||c.dataset.search.toLowerCase().includes(term))&&(!ev||events.includes(ev))&&(!cv||c.dataset.city===cv)&&(!cc||c.dataset.category===cc);c.classList.toggle('hidden',!ok);return ok});
      if(sort){const mode=sort.value;shown.sort((a,b)=>mode==='rating'?+b.dataset.rating-+a.dataset.rating:mode==='price'?+a.dataset.price-+b.dataset.price:0).forEach(c=>grid.appendChild(c))}
      if(count)count.textContent=`${shown.length} vendor${shown.length===1?'':'s'} found`;
      $('#vendorEmpty')&&($('#vendorEmpty').hidden=shown.length>0);
      renderChips();syncUrl();
    };
    [q,event,city,cat,sort].forEach(el=>el?.addEventListener(el===q?'input':'change',run));
    reset?.addEventListener('click',()=>{if(q)q.value='';[event,city,cat].forEach(el=>{if(el)el.value=''});if(sort)sort.value='featured';run()});
    bar?.addEventListener('click',e=>{const b=e.target.closest('[data-clear-filter]');if(!b)return;const el=controls[b.dataset.clearFilter];if(el)el.value='';run()});
    run();
  }

  function initPlanner(){
    const root=$('#plannerChecklist');if(!root)return;const boxes=$$('input[type=checkbox][data-task]',root),key='wz_tasks';const saved=storage.get(key,[]);boxes.forEach(b=>b.checked=saved.includes(b.dataset.task));
    const sync=()=>{const done=boxes.filter(b=>b.checked).map(b=>b.dataset.task);storage.set(key,done);const pct=boxes.length?Math.round(done.length/boxes.length*100):0;const ring=$('#progressRing');if(ring)ring.style.setProperty('--p',pct);$('#progressPct')&&($('#progressPct').textContent=`${pct}%`);$('#progressDone')&&($('#progressDone').textContent=`${done.length}/${boxes.length}`)};boxes.forEach(b=>b.addEventListener('change',sync));sync();
  }

  function initBudget(){
    const table=$('#budgetTable');if(!table)return;const key='wz_budget';const inputs=$$('input[data-budget]',table),saved=storage.get(key,{});inputs.forEach(i=>{if(saved[i.dataset.budget]!=null)i.value=saved[i.dataset.budget]});
    const money=n=>new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR',maximumFractionDigits:0}).format(n||0);
    const sync=()=>{const obj={};inputs.forEach(i=>obj[i.dataset.budget]=Number(i.value||0));storage.set(key,obj);let planned=0,spent=0;Object.entries(obj).forEach(([k,v])=>k.endsWith(':planned')?planned+=v:spent+=v);$('#budgetPlanned')&&($('#budgetPlanned').textContent=money(planned));$('#budgetSpent')&&($('#budgetSpent').textContent=money(spent));$('#budgetLeft')&&($('#budgetLeft').textContent=money(planned-spent))};inputs.forEach(i=>i.addEventListener('input',sync));sync();
  }

  function initInviteBuilder(){
    const live=$('#liveInvite');if(!live)return;const names=$('#inviteNames'),date=$('#inviteDate'),venue=$('#inviteVenue'),theme=$('#inviteTheme');
    const themes={gulab:['#f4dedf','#7b2942'],mehr:['#f0ddc2','#7c3f26'],ivory:['#f5f1e9','#34302d'],noor:['#e8e3ec','#4b315f'],bagh:['#e7efe2','#36523b'],saanjh:['#2e2432','#e4bd82']};
    const sync=()=>{const t=themes[theme?.value]||themes.gulab;live.style.background=t[0];live.style.color=t[1];live.querySelector('[data-live-names]').textContent=names?.value||'Your Celebration';live.querySelector('[data-live-date]').textContent=date?.value?new Date(date.value+'T12:00:00').toLocaleDateString('en-IN',{day:'numeric',month:'long',year:'numeric'}):'12 December 2026';live.querySelector('[data-live-venue]').textContent=venue?.value||'Jaipur, Rajasthan'};[names,date,venue,theme].forEach(x=>x?.addEventListener('input',sync));sync();
    $('#downloadInvite')?.addEventListener('click',()=>toast('Preview ready. PDF export can be connected in production.'));
  }

  function initShare(){
    $$('[data-share]').forEach(b=>b.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.href);toast('Link copied')}catch{toast('Copy the page URL to share')}}));
  }
})();
