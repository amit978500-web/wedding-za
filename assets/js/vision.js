(() => {
  'use strict';
  document.documentElement.classList.add('js-vision');
  const $=(s,c=document)=>c.querySelector(s), $$=(s,c=document)=>[...c.querySelectorAll(s)];
  const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;

  function discovery(){
    const panel=$('#discoveryPanel'); if(!panel)return;
    const set=open=>{panel.classList.toggle('open',open);panel.setAttribute('aria-hidden',String(!open));document.body.classList.toggle('discovery-open',open);if(open)setTimeout(()=>panel.querySelector('select')?.focus(),350)};
    $$('[data-discovery-open]').forEach(b=>b.addEventListener('click',()=>set(true)));
    $$('[data-discovery-close]').forEach(b=>b.addEventListener('click',()=>set(false)));
    addEventListener('keydown',e=>{if(e.key==='Escape')set(false)});
  }

  function headerTone(){
    const header=$('#siteHeader'); if(!header)return;
    const tick=()=>header.classList.toggle('scrolled',scrollY>48);
    addEventListener('scroll',tick,{passive:true}); tick();
  }

  function heroPlanner(){
    const form=$('#heroPlanDock'), eventSelect=$('#heroEvent'), preview=$('#heroPlanPreview'), label=$('#heroPlanLabel');
    if(!form||!eventSelect)return;
    const updateFieldState=()=>$('label',form).forEach(el=>{
      const select=el.querySelector('select');
      if(select)el.classList.toggle('is-filled',Boolean(select.value));
    });
    const updatePreview=()=>{
      const opt=eventSelect.options[eventSelect.selectedIndex];
      const src=opt?.dataset?.image;
      if(label)label.textContent=eventSelect.value ? `Plan a ${eventSelect.value.toLowerCase()}` : 'Build your event team';
      if(preview&&src&&preview.src!==src){
        preview.classList.add('is-changing');
        const img=new Image();
        img.onload=()=>{preview.src=src;requestAnimationFrame(()=>preview.classList.remove('is-changing'))};
        img.src=src;
      }
      updateFieldState();
    };
    eventSelect.addEventListener('change',updatePreview);
    $('select',form).forEach(s=>s.addEventListener('change',updateFieldState));
    updatePreview();
  }

  function smoothScroll(){
    if(reduce || typeof Lenis==='undefined')return;
    const lenis=new Lenis({duration:.92,smoothWheel:true,wheelMultiplier:.9,touchMultiplier:1.05});
    if(typeof gsap!=='undefined'&&typeof ScrollTrigger!=='undefined'){
      // Keep Lenis on ONE animation clock. Driving it from both RAF and GSAP
      // causes uneven velocity and can make pinned/horizontal scenes feel stuck.
      lenis.on('scroll',ScrollTrigger.update);
      gsap.ticker.add(t=>lenis.raf(t*1000));
      gsap.ticker.lagSmoothing(0);
    }else{
      const raf=t=>{lenis.raf(t);requestAnimationFrame(raf)};
      requestAnimationFrame(raf);
    }
    window.WZ_LENIS=lenis;
  }

  function animate(){
    if(reduce || typeof gsap==='undefined')return;
    if(typeof ScrollTrigger!=='undefined')gsap.registerPlugin(ScrollTrigger);

    // Opening sequence — image first, typography second.
    const hero=$('.vision-hero');
    if(hero){
      const tl=gsap.timeline({delay:.48,defaults:{ease:'power4.out'}});
      tl.from('.vision-hero-bg img',{scale:1.16,duration:1.8})
        .from('.vision-hero-eyebrow',{y:20,opacity:0,duration:.7},'-=1.15')
        .from('.hero-line',{yPercent:115,clipPath:'inset(0 0 100% 0)',duration:1.15,stagger:.12},'-=.95')
        .from('.vision-hero-copy',{y:28,opacity:0,duration:.75},'-=.72')
        .from('.vision-hero-actions',{y:20,opacity:0,duration:.6},'-=.58')
        .from('.celebration-types a',{y:16,opacity:0,duration:.45,stagger:.045},'-=.48')
        .from('.hero-plan-dock',{y:28,opacity:0,duration:.8},'-=.52')
        .from('.vhero-float',{y:55,opacity:0,scale:.94,duration:1,stagger:.12},'-=.72');
      gsap.to('.vision-hero-bg img',{yPercent:9,ease:'none',scrollTrigger:{trigger:hero,start:'top top',end:'bottom top',scrub:true}});
      gsap.to('.vhero-float-a',{yPercent:-18,ease:'none',scrollTrigger:{trigger:hero,start:'top top',end:'bottom top',scrub:true}});
      gsap.to('.vhero-float-b',{yPercent:-33,ease:'none',scrollTrigger:{trigger:hero,start:'top top',end:'bottom top',scrub:true}});
    }

    // Editorial title reveals on every page.
    $$('[data-split-title]').forEach(el=>{
      gsap.from(el,{y:70,opacity:0,duration:1,ease:'power4.out',scrollTrigger:{trigger:el,start:'top 86%',once:true}});
    });

    // Reveal imagery as a photographic print, not a generic fade-in.
    $$('.vision-category-image,.vision-city-card figure,.vision-vendor-card .vendor-media,.vision-journal-card .journal-media,.vision-idea,.story-media,.vendor-gallery figure,.story-gallery figure').forEach(el=>{
      gsap.fromTo(el,{clipPath:'inset(0 0 100% 0)'},{clipPath:'inset(0 0 0% 0)',duration:1.15,ease:'power4.out',scrollTrigger:{trigger:el,start:'top 88%',once:true}});
    });

    // Horizontal category chapter.
    const exp=$('#visionExperience'), track=$('#visionCategoryTrack');
    if(exp&&track&&innerWidth>900){
      const distance=()=>Math.max(0,track.scrollWidth-innerWidth+innerWidth*.12);
      gsap.to(track,{x:()=>-distance(),ease:'none',scrollTrigger:{trigger:exp,start:'top top',end:()=>'+='+(distance()+innerHeight*.35),scrub:.65,pin:$('.vision-experience-sticky'),invalidateOnRefresh:true,anticipatePin:1}});
    }

    // Destination rail: horizontal motion WITHOUT pinning the whole section.
    // It follows normal vertical scrolling, so the page never feels trapped.
    const cities=$('.vision-cities'), rail=$('#visionCityRail');
    if(cities&&rail&&innerWidth>720){
      const distance=()=>Math.max(0,rail.scrollWidth-innerWidth+innerWidth*.08);
      gsap.set(rail,{force3D:true});
      gsap.to(rail,{x:()=>-distance(),ease:'none',scrollTrigger:{
        trigger:cities,
        start:'top 76%',
        end:'bottom 18%',
        scrub:.45,
        invalidateOnRefresh:true,
        fastScrollEnd:true
      }});
    }

    // Stacked real-wedding scenes receive depth as the next story arrives.
    $$('.vision-real-panel').forEach((panel,i,arr)=>{
      if(i===arr.length-1)return;
      gsap.to(panel,{scale:.92,opacity:.45,filter:'brightness(.65)',ease:'none',scrollTrigger:{trigger:arr[i+1],start:'top bottom',end:'top top',scrub:true}});
    });

    // Concierge image breathes instead of tilting.
    const concierge=$('.vision-concierge');
    if(concierge)gsap.fromTo('.vision-concierge-image img',{scale:1.12,yPercent:-3},{scale:1.02,yPercent:3,ease:'none',scrollTrigger:{trigger:concierge,start:'top bottom',end:'bottom top',scrub:true}});

    // Small content groups stagger only once.
    $$('.vision-featured-stack,.vision-journal-grid,.story-grid-premium,.vendor-grid').forEach(group=>{
      const kids=[...group.children];
      gsap.from(kids,{y:38,opacity:0,duration:.75,stagger:.08,ease:'power3.out',scrollTrigger:{trigger:group,start:'top 82%',once:true}});
    });
  }

  function nativeFallback(){
    if(!reduce && typeof gsap!=='undefined')return;
    const els=$$('[data-split-title],.vision-category-panel,.vision-city-card,.vision-vendor-card,.vision-journal-card');
    if(!('IntersectionObserver'in window)){els.forEach(e=>e.classList.add('vision-in'));return}
    const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('vision-in');io.unobserve(e.target)}}),{threshold:.08});els.forEach(e=>io.observe(e));
  }

  addEventListener('DOMContentLoaded',()=>{
    discovery();headerTone();heroPlanner();smoothScroll();animate();nativeFallback();
  });

  // Re-measure scroll scenes after fonts and high-resolution imagery settle.
  // This prevents horizontal distances from being calculated against incomplete layouts.
  addEventListener('load',()=>{
    if(typeof ScrollTrigger!=='undefined')requestAnimationFrame(()=>ScrollTrigger.refresh());
  },{once:true});
  if(document.fonts?.ready)document.fonts.ready.then(()=>{
    if(typeof ScrollTrigger!=='undefined')ScrollTrigger.refresh();
  });
})();
