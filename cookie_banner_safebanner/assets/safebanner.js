"use strict";(()=>{var E="safebanner_consent";function d(){try{let e=localStorage.getItem(E);return e?JSON.parse(e):null}catch{return null}}function f(e){try{localStorage.setItem(E,JSON.stringify(e))}catch{}}function v(){try{localStorage.removeItem(E)}catch{}}function m(){return d()!==null}var he=[{pattern:/^_ga/,category:"analytics"},{pattern:/^_gid/,category:"analytics"},{pattern:/^_gat/,category:"analytics"},{pattern:/^__utm/,category:"analytics"},{pattern:/^_hjid/,category:"analytics"},{pattern:/^mp_/,category:"analytics"},{pattern:/^amplitude/,category:"analytics"},{pattern:/^plausible/,category:"analytics"},{pattern:/^_fbp/,category:"marketing"},{pattern:/^_fbc/,category:"marketing"},{pattern:/^fr$/,category:"marketing"},{pattern:/^_gcl/,category:"marketing"},{pattern:/^_pinterest/,category:"marketing"},{pattern:/^_tt_/,category:"marketing"},{pattern:/^li_/,category:"marketing"},{pattern:/^IDE$/,category:"marketing"},{pattern:/^ads$/,category:"marketing"}];function T(){let e=document.cookie.split(";").map(n=>n.trim().split("=")[0]),t=[];for(let n of e){if(!n)continue;let a="necessary";for(let{pattern:o,category:i}of he)if(o.test(n)){a=i;break}t.push({name:n,category:a})}return t}function A(e){let n=T().filter(a=>a.category===e);for(let a of n)document.cookie=`${a.name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`,document.cookie=`${a.name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=${window.location.hostname}`}function be(e){let t=e.primaryColor||"#2563eb",n=e.position==="top",a=e.position?.includes("-"),o=e.theme==="auto",i=e.theme==="dark",r=e.layout==="bar",s=e.layout==="card",l=e.offset??16,p=e.maxWidth,h=a||s?16:0,j=e.borderRadius??h,re=e.buttonStyle==="pill"?9999:e.buttonStyle==="square"?0:e.borderRadius!=null?Math.max(4,Math.round(e.borderRadius*.5)):6,se=i?"#1f2937":"#ffffff",ce=i?"#f9fafb":"#111827",k=i?"#d1d5db":"#6b7280",le=i?"#374151":"#f3f4f6",de=i?"#f9fafb":"#374151",q=i?"#6b7280":"#9ca3af",b;if(s)b=`
      left: 50%;
      bottom: ${l}px;
      max-width: ${p??480}px;
      width: calc(100% - ${l*2}px);
      transform: translateX(-50%) translateY(12px);
    `;else if(a){let me=e.position?.includes("right")?`right: ${l}px;`:`left: ${l}px;`,ye=n?`top: ${l}px;`:`bottom: ${l}px;`;b=`${me} ${ye} max-width: 400px;`}else n?b=`top: 0; left: 0; right: 0;
      ${p?`max-width: ${p}px; margin-left: auto; margin-right: auto;`:""}`:b=`bottom: 0; left: 0; right: 0;
      ${p?`max-width: ${p}px; margin-left: auto; margin-right: auto;`:""}`;let pe=s?"transform: translateX(-50%) translateY(12px);":`transform: translateY(${n?"-8px":"8px"});`,ue=s?"transform: translateX(-50%) translateY(0);":"transform: translateY(0);",ge=o?`
    @media (prefers-color-scheme: dark) {
      .cm-banner {
        background: #1f2937 !important;
        color: #f9fafb !important;
      }
      .cm-text { color: #d1d5db !important; }
      .cm-btn-secondary { background: #374151 !important; color: #f9fafb !important; }
      .cm-btn-link { color: #d1d5db !important; }
      .cm-label-required { color: #9ca3af !important; }
      .cm-powered-by, .cm-powered-by a { color: #6b7280 !important; }
      .cm-bar-text { color: #d1d5db !important; }
    }
  `:"",fe=r?`
    .cm-banner {
      padding: 0 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      min-height: 60px;
      flex-wrap: wrap;
    }
    .cm-bar-text {
      font-size: 13px;
      color: ${k};
      flex: 1;
      min-width: 160px;
    }
    .cm-buttons {
      flex-wrap: nowrap;
      gap: 8px;
    }
    .cm-btn {
      padding: 7px 14px;
      font-size: 13px;
      white-space: nowrap;
    }
    .cm-btn-link {
      padding: 7px 8px;
    }
  `:"";return`
    .cm-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
      z-index: 99998;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
    .cm-overlay.cm-visible {
      opacity: 1;
    }
    ${r?".cm-overlay { display: none; }":""}

    .cm-banner {
      position: fixed;
      ${b}
      background: ${se};
      color: ${ce};
      padding: 24px 28px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18), 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 99999;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      line-height: 1.5;
      ${j>0?`border-radius: ${j}px;`:""}
      opacity: 0;
      ${pe}
      transition: opacity 0.25s ease, transform 0.25s ease;
    }
    .cm-banner.cm-visible {
      opacity: 1;
      ${ue}
    }

    .cm-title {
      font-size: 16px;
      font-weight: 600;
      margin: 0 0 8px 0;
    }

    .cm-text {
      margin: 0 0 16px 0;
      color: ${k};
    }

    .cm-link {
      color: ${t};
      text-decoration: none;
    }
    .cm-link:hover {
      text-decoration: underline;
    }

    .cm-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .cm-btn {
      padding: 10px 20px;
      border-radius: ${re}px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      border: none;
      transition: opacity 0.2s ease;
    }
    .cm-btn:hover {
      opacity: 0.9;
    }

    .cm-btn-primary {
      background: ${t};
      color: #ffffff;
    }

    .cm-btn-secondary {
      background: ${le};
      color: ${de};
    }

    .cm-btn-link {
      background: transparent;
      color: ${k};
      padding: 10px 12px;
    }

    .cm-categories {
      margin: 16px 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .cm-category {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cm-checkbox {
      width: 18px;
      height: 18px;
      accent-color: ${t};
    }

    .cm-checkbox:disabled {
      opacity: 0.5;
    }

    .cm-label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .cm-label-text {
      font-weight: 500;
    }

    .cm-label-required {
      font-size: 12px;
      color: #9ca3af;
    }

    .cm-powered-by {
      margin-top: 12px;
      font-size: 11px;
      color: ${q};
    }

    .cm-powered-by a {
      color: ${q};
      text-decoration: none;
    }

    .cm-powered-by a:hover {
      text-decoration: underline;
    }

    .cm-logo {
      display: block;
      max-height: 32px;
      max-width: 120px;
      width: auto;
      margin-bottom: 12px;
      object-fit: contain;
    }

    ${fe}
    ${ge}
  `}function I(e){let t="consent-manager-styles";if(document.getElementById(t))return;let n=document.createElement("style");n.id=t,n.textContent=be(e),document.head.appendChild(n)}var U=["en","fr","de"],O=["es","it","nl","pt","pl","sv","da","fi","cs","no","ro","hu","el","tr","uk","bg","hr","sk","sl","lt","lv","et","mt","ga","ca","eu","gl","is","sq","sr","bs","mk","ru","ar","he","ja","ko","zh","hi","id","ms","th","vi","fa"],F={en:{title:"Cookie Consent",description:"We use cookies to improve your experience and analyze site traffic.",preferencesTitle:"Cookie Preferences",preferencesDescription:"Choose which cookies you want to accept.",acceptAll:"Accept All",rejectAll:"Reject All",customize:"Customize",save:"Save Preferences",necessary:"Necessary",analytics:"Analytics",marketing:"Marketing",required:"(Required)",privacyPolicy:"Privacy Policy"},fr:{title:"Consentement aux cookies",description:"Nous utilisons des cookies pour am\xE9liorer votre exp\xE9rience et analyser le trafic.",preferencesTitle:"Pr\xE9f\xE9rences de cookies",preferencesDescription:"Choisissez les cookies que vous souhaitez accepter.",acceptAll:"Tout accepter",rejectAll:"Tout refuser",customize:"Personnaliser",save:"Enregistrer",necessary:"N\xE9cessaires",analytics:"Analytiques",marketing:"Marketing",required:"(Requis)",privacyPolicy:"Politique de confidentialit\xE9"},de:{title:"Cookie-Einwilligung",description:"Wir verwenden Cookies, um Ihre Erfahrung zu verbessern und den Datenverkehr zu analysieren.",preferencesTitle:"Cookie-Einstellungen",preferencesDescription:"W\xE4hlen Sie, welche Cookies Sie akzeptieren m\xF6chten.",acceptAll:"Alle akzeptieren",rejectAll:"Alle ablehnen",customize:"Anpassen",save:"Speichern",necessary:"Notwendig",analytics:"Analyse",marketing:"Marketing",required:"(Erforderlich)",privacyPolicy:"Datenschutzrichtlinie"}},P={...F};function L(e){if(!e)return null;let t=e.toLowerCase().slice(0,2);return Ce(t)?t:null}function Ce(e){return U.includes(e)||O.includes(e)}function H(e){Object.assign(P,e)}function K(e){let t=L(e);return t?!!P[t]:!1}function W(e){let t=L(e);return t&&P[t]||F.en}function B(e){let t=L(e);return t?O.includes(t):!1}function Y(e,t){let n=L(e);return n&&(U.includes(n)||t)?n:"en"}var c=null,g=null,C=null;function J(e){try{let t=new URL(e,window.location.origin);return t.protocol==="http:"||t.protocol==="https:"?t.toString():null}catch{return null}}function V(e){return document.createTextNode(e)}function xe(e){if(!e)return null;let t=J(e);if(!t)return null;let n=document.createElement("img");return n.src=t,n.className="cm-logo",n.alt="",n.setAttribute("aria-hidden","true"),n}function _(){let e=document.createElement("div");e.className="cm-powered-by";let t=document.createElement("a");return t.href="https://www.safebanner.com/?ref=badge",t.target="_blank",t.rel="noopener noreferrer",t.textContent="Powered by SafeBanner",e.appendChild(t),e}function X(e,t){if(!e)return null;let n=J(e);if(!n)return null;let a=document.createElement("a");return a.href=n,a.className="cm-link",a.target="_blank",a.rel="noopener noreferrer",a.textContent=t,a}function u(e,t,n){let a=document.createElement("button");return a.className=n,a.dataset.action=t,a.textContent=e,a}function D(e,t,n,a,o,i){let r=document.createElement("label");r.className="cm-category";let s=document.createElement("input");s.type="checkbox",s.className="cm-checkbox",s.dataset.category=e,s.checked=n,s.disabled=a;let l=document.createElement("span");l.className="cm-label";let p=document.createElement("span");if(p.className="cm-label-text",p.textContent=t,l.appendChild(p),o&&i){let h=document.createElement("span");h.className="cm-label-required",h.textContent=i,l.appendChild(h)}return r.appendChild(s),r.appendChild(l),r}function ve(e,t){let n=document.createDocumentFragment(),a=xe(e.logoUrl);a&&n.appendChild(a);let o=document.createElement("div");o.className="cm-title",o.textContent=e.bannerTitle||t.title,n.appendChild(o);let i=document.createElement("p");i.className="cm-text",i.appendChild(V((e.bannerDescription||t.description)+" "));let r=X(e.privacyPolicyUrl,t.privacyPolicy);r&&i.appendChild(r),n.appendChild(i);let s=document.createElement("div");return s.className="cm-buttons",s.appendChild(u(e.acceptLabel||t.acceptAll,"accept-all","cm-btn cm-btn-primary")),s.appendChild(u(e.rejectLabel||t.rejectAll,"reject-all","cm-btn cm-btn-secondary")),s.appendChild(u(e.customizeLabel||t.customize,"customize","cm-btn cm-btn-link")),n.appendChild(s),e.showBranding!==!1&&n.appendChild(_()),n}function Le(e,t){let n=document.createDocumentFragment(),a=d(),o=document.createElement("div");o.className="cm-title",o.textContent=t.preferencesTitle,n.appendChild(o);let i=document.createElement("p");i.className="cm-text",i.appendChild(V(t.preferencesDescription+" "));let r=X(e.privacyPolicyUrl,t.privacyPolicy);r&&i.appendChild(r),n.appendChild(i);let s=document.createElement("div");s.className="cm-categories",s.appendChild(D("necessary",t.necessary,!0,!0,!0,t.required)),s.appendChild(D("analytics",t.analytics,a?.analytics??!1,!1,!1)),s.appendChild(D("marketing",t.marketing,a?.marketing??!1,!1,!1)),n.appendChild(s);let l=document.createElement("div");return l.className="cm-buttons",l.appendChild(u(e.saveLabel||t.save,"save","cm-btn cm-btn-primary")),l.appendChild(u(e.acceptLabel||t.acceptAll,"accept-all","cm-btn cm-btn-secondary")),n.appendChild(l),e.showBranding!==!1&&n.appendChild(_()),n}function Se(){let e=c?.querySelector('[data-category="analytics"]')?.checked??!1,t=c?.querySelector('[data-category="marketing"]')?.checked??!1;return{necessary:!0,analytics:e,marketing:t,timestamp:Date.now()}}function we(e,t){let n=document.createDocumentFragment(),a=document.createElement("span");a.className="cm-bar-text",a.textContent=e.bannerDescription||t.description,n.appendChild(a);let o=document.createElement("div");return o.className="cm-buttons",o.appendChild(u(e.acceptLabel||t.acceptAll,"accept-all","cm-btn cm-btn-primary")),o.appendChild(u(e.rejectLabel||t.rejectAll,"reject-all","cm-btn cm-btn-secondary")),o.appendChild(u(e.customizeLabel||t.customize,"customize","cm-btn cm-btn-link")),n.appendChild(o),e.showBranding!==!1&&n.appendChild(_()),n}function x(e){I(e);let t=W(e.lang),n=e.layout==="bar";C=document.activeElement,g=document.createElement("div"),g.className="cm-overlay",document.body.appendChild(g),c=document.createElement("div"),c.className="cm-banner",c.setAttribute("role","dialog"),c.setAttribute("aria-modal","true"),c.setAttribute("aria-label",e.bannerTitle||t.title),c.setAttribute("tabindex","-1"),c.appendChild(n?we(e,t):ve(e,t)),document.body.appendChild(c),requestAnimationFrame(()=>{g?.classList.add("cm-visible"),c?.classList.add("cm-visible"),c?.focus()}),c.addEventListener("keydown",a=>{if(a.key!=="Tab"||!c)return;let o=c.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');if(o.length===0)return;let i=o[0],r=o[o.length-1];a.shiftKey&&document.activeElement===i?(a.preventDefault(),r.focus()):!a.shiftKey&&document.activeElement===r&&(a.preventDefault(),i.focus())}),c.addEventListener("click",a=>{let i=a.target.dataset.action;if(i)switch(i){case"accept-all":{let r={necessary:!0,analytics:!0,marketing:!0,timestamp:Date.now()};f(r),y(),e.onAccept?.(r);break}case"reject-all":{let r={necessary:!0,analytics:!1,marketing:!1,timestamp:Date.now()};f(r),y(),e.onDecline?.();break}case"customize":{c&&(c.textContent="",c.appendChild(Le(e,t)));break}case"save":{let r=Se();f(r),y(),e.onUpdate?.(r);break}}})}function y(){g?.classList.remove("cm-visible"),c?.classList.remove("cm-visible"),setTimeout(()=>{g?.remove(),c?.remove(),g=null,c=null,C&&typeof C.focus=="function"&&(C.focus(),C=null)},300)}function S(){return c!==null}var Q=!1,z="advanced";function $(){return typeof window<"u"&&typeof window.gtag=="function"}function Z(e,t){if($())try{window.gtag("consent",e,t)}catch(n){console.warn("[SafeBanner] Failed to send Google consent signal:",n)}}function ee(e,t){let n=e?.analytics??!1,a=e?.marketing??!1,o={analytics_storage:n?"granted":"denied",ad_storage:a?"granted":"denied",ad_user_data:a?"granted":"denied",ad_personalization:a?"granted":"denied"};return t==="advanced"&&!a&&(o.ads_data_redaction=!0),o}function N(e="advanced"){if(z=e,$()){let n=window.dataLayer;Array.isArray(n)&&n.some(o=>Array.isArray(o)&&o[0]==="consent")&&console.warn("[SafeBanner] Google consent signals detected before SafeBanner initialized. Ensure SafeBanner script loads BEFORE Google tags for proper consent handling.")}window.dataLayer=window.dataLayer||[],$()||(window.gtag=function(){window.dataLayer.push(arguments)});let t=ee(null,e);t.wait_for_update=500,Z("default",t),Q=!0}function R(e){Q||N(z);let t=ee(e,z);Z("update",t)}var te='script[type="text/safebanner"]',ne="data-safebanner-activated",ke=["id","nonce","integrity","crossorigin","referrerpolicy"];function Ee(){let e=[],t=document.querySelectorAll(te);for(let n of t){if(n.hasAttribute(ne))continue;let a=n.dataset.consent;if(a!=="analytics"&&a!=="marketing")continue;let o=n.dataset.src||null,i=!o&&n.textContent?n.textContent.trim():null;!o&&!i||e.push({element:n,category:a,src:o,inline:i})}return e}function Te(e){try{let t=new URL(e,window.location.origin);return t.protocol==="http:"||t.protocol==="https:"?t.toString():null}catch{return null}}function Ae(e){let t=document.createElement("script"),n=e.element.dataset.type;n&&(t.type=n);for(let a of ke){let o=e.element.getAttribute(a)||e.element.dataset[a];o&&t.setAttribute(a,o)}if(e.src){let a=Te(e.src);if(!a)return;t.src=a,e.element.hasAttribute("data-async")&&(t.async=!0),e.element.hasAttribute("data-defer")&&(t.defer=!0)}else e.inline&&(t.textContent=e.inline);e.element.setAttribute(ne,"true"),e.element.parentNode?.insertBefore(t,e.element.nextSibling)}function ae(e){let t=Ee();for(let n of t)e[n.category]&&Ae(n)}function oe(){return document.querySelectorAll(te).length>0}var G="safebanner_license:",Pe=24*60*60*1e3,Be=320;function De(){let e=document.currentScript;if(!e)return{};let t="advanced",n=e.dataset.googleConsent;return(n==="basic"||n==="off")&&(t=n),{position:e.dataset.position||"bottom",theme:e.dataset.theme||"light",primaryColor:e.dataset.color,companyName:e.dataset.company,privacyPolicyUrl:e.dataset.privacy,lang:e.dataset.lang,googleConsentMode:t,projectKey:e.dataset.projectKey,consentExpiryDays:e.dataset.consentExpiryDays!=null?parseInt(e.dataset.consentExpiryDays,10):void 0,layout:e.dataset.layout,maxWidth:e.dataset.maxWidth!=null?parseInt(e.dataset.maxWidth,10):void 0,offset:e.dataset.offset!=null?parseInt(e.dataset.offset,10):void 0,logoUrl:e.dataset.logo,borderRadius:e.dataset.radius!=null?parseInt(e.dataset.radius,10):void 0,buttonStyle:e.dataset.buttonStyle,bannerTitle:e.dataset.bannerTitle,bannerDescription:e.dataset.bannerDescription,acceptLabel:e.dataset.acceptLabel,rejectLabel:e.dataset.rejectLabel,customizeLabel:e.dataset.customizeLabel,saveLabel:e.dataset.saveLabel}}var ie="https://www.safebanner.com";function _e(){return`${ie}/api/validate-key`}function ze(){return`${ie}/safebanner-pro-translations.json`}function $e(e){try{let t=localStorage.getItem(`${G}${e}`);if(!t)return null;let n=JSON.parse(t);return n.expiresAt<Date.now()?(localStorage.removeItem(`${G}${e}`),null):n.valid}catch{return null}}function Ne(e,t){try{localStorage.setItem(`${G}${e}`,JSON.stringify({valid:t,expiresAt:Date.now()+Pe}))}catch{}}var M=class{constructor(t={}){this.initialized=!1;this.googleConsentInitialized=!1;this.hasProLicense=!1;this.validationStarted=!1;this.proTranslationsPromise=null;this.consentChangeListeners=[];this.config={...De(),...t},this.requestedLanguage=this.config.lang,this.applyLicenseState(this.getCachedLicenseState()),this.initGoogleConsentMode(),this.validateProjectKey()}initGoogleConsentMode(){if(this.googleConsentInitialized||this.config.googleConsentMode==="off")return;let t=this.config.googleConsentMode==="basic"?"basic":"advanced";N(t),this.googleConsentInitialized=!0;let n=d();n&&R(n)}async init(){if(!this.initialized){if(this.initialized=!0,this.hasProLicense&&this.config.consentExpiryDays&&m()){let t=d();if(t){let n=this.config.consentExpiryDays*24*60*60*1e3;Date.now()-t.timestamp>n&&v()}}if(m()){this.enforceConsent();return}await this.ensureRequestedLanguageLoaded(),x(this.getBannerConfig())}}sendGoogleConsentUpdate(t){this.config.googleConsentMode!=="off"&&R(t)}getCachedLicenseState(){return this.config.projectKey?$e(this.config.projectKey)===!0:!1}applyLicenseState(t){this.hasProLicense=t,this.config.lang=Y(this.requestedLanguage,t),this.config.showBranding=!t}async ensureRequestedLanguageLoaded(){if(!this.hasProLicense||!B(this.requestedLanguage)||K(this.requestedLanguage))return;if(this.proTranslationsPromise)return this.proTranslationsPromise;let t=ze();return this.proTranslationsPromise=(async()=>{try{let n=await fetch(t,{mode:"cors"});if(!n.ok)return;let a=await n.json();H(a)}catch{}finally{this.proTranslationsPromise=null}})(),this.proTranslationsPromise}async validateProjectKey(){if(this.validationStarted||!this.config.projectKey)return;this.validationStarted=!0;let t=_e();try{let n=await fetch(t,{method:"POST",mode:"cors",headers:{"Content-Type":"application/json"},body:JSON.stringify({projectKey:this.config.projectKey,hostname:window.location.hostname})});if(!n.ok)return;let o=(await n.json()).valid===!0;Ne(this.config.projectKey,o),await this.upgradeLicenseState(o)}catch{}}async upgradeLicenseState(t){let n=this.config.lang,a=this.config.showBranding;if(this.applyLicenseState(t),await this.ensureRequestedLanguageLoaded(),t){if(this.config.consentExpiryDays&&m()){let r=d();if(r){let s=this.config.consentExpiryDays*24*60*60*1e3;Date.now()-r.timestamp>s&&v()}}this.enforceConsent()}if(!S())return;let o=a!==this.config.showBranding,i=n!==this.config.lang&&B(this.requestedLanguage);o&&!this.config.showBranding&&document.querySelector(".cm-powered-by")?.remove(),(o||i)&&(y(),window.setTimeout(()=>{m()||x(this.getBannerConfig())},Be))}getBannerConfig(){if(!this.hasProLicense){let n=[this.config.layout&&"data-layout",this.config.logoUrl&&"data-logo",this.config.bannerTitle&&"data-banner-title",this.config.bannerDescription&&"data-banner-description",this.config.buttonStyle&&"data-button-style",this.config.theme==="auto"&&'data-theme="auto"',this.config.acceptLabel&&"data-accept-label",this.config.rejectLabel&&"data-reject-label",this.config.consentExpiryDays&&"data-consent-expiry-days"].filter(Boolean);n.length>0&&console.info(`[SafeBanner] ${n.join(", ")} require${n.length===1?"s":""} a Pro license and will be ignored. Upgrade at https://www.safebanner.com/upgrade`)}let t=this.hasProLicense?{layout:this.config.layout,theme:this.config.theme,maxWidth:this.config.maxWidth,offset:this.config.offset,logoUrl:this.config.logoUrl,borderRadius:this.config.borderRadius,buttonStyle:this.config.buttonStyle,bannerTitle:this.config.bannerTitle,bannerDescription:this.config.bannerDescription,acceptLabel:this.config.acceptLabel,rejectLabel:this.config.rejectLabel,customizeLabel:this.config.customizeLabel,saveLabel:this.config.saveLabel}:{layout:void 0,theme:this.config.theme==="auto"?"light":this.config.theme,maxWidth:void 0,offset:void 0,logoUrl:void 0,borderRadius:void 0,buttonStyle:void 0,bannerTitle:void 0,bannerDescription:void 0,acceptLabel:void 0,rejectLabel:void 0,customizeLabel:void 0,saveLabel:void 0};return{...this.config,...t,onAccept:n=>{this.sendGoogleConsentUpdate(n),this.enforceConsent(),this.config.onAccept?.(n)},onDecline:()=>{let n=d();n&&this.sendGoogleConsentUpdate(n),this.enforceConsent(),this.config.onDecline?.()},onUpdate:n=>{this.sendGoogleConsentUpdate(n),this.enforceConsent(),this.config.onUpdate?.(n)}}}enforceConsent(){let t=d();if(t){this.hasProLicense?(t.analytics||A("analytics"),t.marketing||A("marketing"),ae(t)):oe()&&console.info('[SafeBanner] Script blocking (type="text/safebanner") requires a Pro license. Upgrade at https://www.safebanner.com/upgrade');for(let n of this.consentChangeListeners)try{n(t)}catch{}}}getConsent(){return d()}hasConsented(){return m()}hasConsentFor(t){let n=d();return n?n[t]:t==="necessary"}updateConsent(t){let a={...d()||{necessary:!0,analytics:!1,marketing:!1,timestamp:Date.now()},...t,necessary:!0,timestamp:Date.now()};f(a),this.sendGoogleConsentUpdate(a),this.enforceConsent(),this.config.onUpdate?.(a)}reset(){v(),this.sendGoogleConsentUpdate({necessary:!0,analytics:!1,marketing:!1,timestamp:Date.now()}),S()||x(this.getBannerConfig())}show(){S()||x(this.getBannerConfig())}hide(){y()}onConsentChange(t){return this.consentChangeListeners.push(t),()=>{let n=this.consentChangeListeners.indexOf(t);n!==-1&&this.consentChangeListeners.splice(n,1)}}detectCookies(){return T()}},w=new M;document.readyState==="loading"?document.addEventListener("DOMContentLoaded",()=>w.init()):w.init();window.safeBanner=w;var Qe=w;})();
