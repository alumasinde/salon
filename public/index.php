<?php
require __DIR__ . '/db.php';
$s = settings();
$services = [];
foreach (q('SELECT category, name, duration, price FROM services WHERE active = 1 ORDER BY sort, id') as $r)
    $services[$r['category']][] = [$r['name'], $r['duration'], (int)$r['price']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s['name']) ?> | Hair, Nails & Beauty in Nairobi</title>
<meta name="description" content="Braids, silk press, locs, nails, makeup and spa treatments in Nairobi. Book online in under a minute and pay by M-Pesa.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --plum:#3a1233; --plum-2:#5a1d4e; --rose:#d6336c; --gold:#e0a82e;
  --bg:#fbf7f9; --card:#ffffff; --ink:#2a1626; --muted:#6f5d6b; --line:#eadfe6;
  --r:18px; --maxw:1120px;
  --head:'Bricolage Grotesque',system-ui,sans-serif; --body:'Figtree',system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0}
html{scroll-behavior:smooth;scroll-padding-top:72px}
body{font-family:var(--body);color:var(--ink);background:var(--bg);line-height:1.6;font-size:17px}
h1,h2,h3{font-family:var(--head);line-height:1.1;letter-spacing:-.01em}
a{color:inherit}
.wrap{max-width:var(--maxw);margin:0 auto;padding:0 20px}
section{padding:72px 0}
:focus-visible{outline:3px solid var(--gold);outline-offset:3px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 26px;border-radius:999px;border:0;font:600 1rem var(--body);cursor:pointer;text-decoration:none;transition:transform .15s,background .2s}
.btn:active{transform:scale(.97)}
.btn-primary{background:var(--rose);color:#fff}
.btn-primary:hover{background:#b92a5d}
.btn-ghost{border:2px solid rgba(255,255,255,.5);color:#fff}
.btn-ghost:hover{background:rgba(255,255,255,.12)}

/* header */
header{position:sticky;top:0;z-index:50;background:rgba(251,247,249,.92);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.nav{display:flex;align-items:center;justify-content:space-between;height:64px}
.logo{font:800 1.35rem var(--head);text-decoration:none;color:var(--plum)}
.logo span{color:var(--rose)}
.nav ul{display:flex;gap:26px;list-style:none;padding:0}
.nav ul a{text-decoration:none;font-weight:500;color:var(--muted)}
.nav ul a:hover{color:var(--rose)}
.nav .btn{min-height:42px;padding:0 20px}

/* hero */
.hero{background:radial-gradient(900px 500px at 85% 10%,#7a2a67 0,transparent 60%),var(--plum);color:#fff;padding:84px 0 96px}
.hero-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:48px;align-items:center}
.hero h1{font-size:clamp(2.5rem,6vw,4.4rem);font-weight:800}
.hero p{margin:20px 0 32px;font-size:1.15rem;max-width:34em;color:#ecdbe8}
.hero .cta{display:flex;flex-wrap:wrap;gap:12px}
.trust{display:flex;flex-wrap:wrap;gap:22px;margin-top:36px;font-size:.95rem;color:#ecdbe8}
.trust b{color:var(--gold);font-weight:600}
.today{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);border-radius:var(--r);padding:26px}
.today h2{font-size:1.2rem;margin-bottom:14px}
.today ul{list-style:none;padding:0}
.today li{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.12)}
.today li:last-child{border:0}
.today .open{display:inline-block;margin-bottom:12px;padding:3px 12px;border-radius:99px;font-size:.85rem;font-weight:600;background:#1c7a4b}
.today .open.closed{background:#8a2c3b}
.today .on{color:var(--gold);font-weight:600}

/* services */
.head{max-width:620px;margin-bottom:32px}
.head h2{font-size:clamp(1.9rem,4vw,2.7rem)}
.head p{color:var(--muted);margin-top:10px}
.tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:22px;scrollbar-width:none}
.tab{flex:none;min-height:44px;padding:0 20px;border-radius:99px;border:1.5px solid var(--line);background:var(--card);font:500 1rem var(--body);color:var(--ink);cursor:pointer}
.tab[aria-selected=true]{background:var(--plum);border-color:var(--plum);color:#fff}
.list{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
.item{display:flex;justify-content:space-between;align-items:center;gap:14px;background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:18px 20px}
.item h3{font-size:1.08rem;font-weight:700}
.item small{color:var(--muted)}
.price{font:700 1.05rem var(--head);color:var(--plum);white-space:nowrap}
.item button{margin-top:6px;background:none;border:0;padding:0;color:var(--rose);font:600 .92rem var(--body);cursor:pointer}
.note{margin-top:16px;color:var(--muted);font-size:.93rem}

/* why + reviews */
.why{background:var(--plum);color:#fff}
.why-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px}
.why h3{font-size:1.25rem;margin-bottom:8px;color:var(--gold)}
.why p{color:#ecdbe8}
.reviews{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.review{background:var(--card);border:1px solid var(--line);border-radius:var(--r);padding:24px}
.stars{color:var(--gold);letter-spacing:2px}
.review p{margin:10px 0 14px}
.review cite{font-style:normal;font-weight:600;color:var(--muted);font-size:.93rem}

/* booking */
.book{background:#f3e7ee}
.book-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:44px;align-items:start}
.book ul{list-style:none;padding:0;margin-top:22px;display:grid;gap:12px;color:var(--muted)}
.book ul b{color:var(--ink)}
form{background:var(--card);border-radius:var(--r);padding:28px;display:grid;gap:16px;box-shadow:0 14px 40px rgba(58,18,51,.12)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
label{display:grid;gap:6px;font-weight:600;font-size:.95rem}
input,select,textarea{width:100%;min-height:48px;padding:10px 14px;border:1.5px solid var(--line);border-radius:12px;background:#fff;font:400 1rem var(--body);color:var(--ink)}
textarea{min-height:76px;resize:vertical}
input:focus,select:focus,textarea:focus{outline:3px solid rgba(214,51,108,.3);border-color:var(--rose)}
.hint{font-weight:400;color:var(--muted);font-size:.86rem}
.err{color:#b3261e;font-weight:500;font-size:.88rem;min-height:1em}

/* visit */
.visit-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.visit dl{display:grid;grid-template-columns:auto 1fr;gap:10px 22px}
.visit dt{font-weight:600}
.visit dd{color:var(--muted)}
.map{min-height:260px;border-radius:var(--r);background:linear-gradient(135deg,#e9d7e3,#f6ecf2);display:grid;place-items:center;text-align:center;padding:20px;border:1px solid var(--line)}

footer{background:var(--ink);color:#cdbdc9;padding:34px 0 90px;font-size:.95rem}
footer .wrap{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px}
.wa{position:fixed;right:16px;bottom:16px;z-index:60;background:#1faa59;color:#fff;border-radius:99px;min-height:52px;padding:0 22px;display:flex;align-items:center;font-weight:600;text-decoration:none;box-shadow:0 8px 24px rgba(0,0,0,.25)}

#toast{position:fixed;left:50%;bottom:84px;transform:translateX(-50%);background:var(--plum);color:#fff;padding:12px 20px;border-radius:12px;opacity:0;pointer-events:none;transition:opacity .25s;z-index:70}
#toast.show{opacity:1}

@media(max-width:860px){
  .nav ul{display:none}
  .hero-grid,.book-grid,.visit-grid,.why-grid,.reviews{grid-template-columns:1fr}
  .list{grid-template-columns:1fr}
  section{padding:56px 0}
  .hero{padding:56px 0 64px}
}
@media(max-width:480px){.row{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body>

<header>
  <div class="wrap nav">
    <a class="logo" href="#top">Zuri<span>.</span></a>
    <ul>
      <li><a href="#services">Services</a></li>
      <li><a href="#why">Why Zuri</a></li>
      <li><a href="#reviews">Reviews</a></li>
      <li><a href="#visit">Find us</a></li>
    </ul>
    <a class="btn btn-primary" href="#book">Book now</a>
  </div>
</header>

<main id="top">
  <div class="hero">
    <div class="wrap hero-grid">
      <div>
        <h1>Hair and beauty that fits your day, not the other way round.</h1>
        <p>Braids, silk press, locs, nails and makeup in Westlands, Nairobi. Pick a service, choose a time, and we will confirm on WhatsApp.</p>
        <div class="cta">
          <a class="btn btn-primary" href="#book">Book an appointment</a>
          <a class="btn btn-ghost" href="#services">See prices</a>
        </div>
        <div class="trust">
          <span><b>4.9</b> from 600+ reviews</span>
          <span>Pay by <b>M-Pesa</b> or cash</span>
          <span>Walk-ins welcome</span>
        </div>
      </div>
      <aside class="today" aria-label="Opening hours today">
        <span class="open" id="openState">Open today</span>
        <h2>Opening hours</h2>
        <ul id="hours"></ul>
      </aside>
    </div>
  </div>

  <section id="services">
    <div class="wrap">
      <div class="head">
        <h2>Services and prices</h2>
        <p>Prices are in Kenya shillings. Final price depends on hair length and thickness; we confirm before we start.</p>
      </div>
      <div class="tabs" role="tablist" id="tabs"></div>
      <div class="list" id="list" role="tabpanel"></div>
      <p class="note">Bringing your own hair extensions? Tell us when you book and we will adjust the price.</p>
    </div>
  </section>

  <section class="why" id="why">
    <div class="wrap">
      <div class="head"><h2>Why clients come back</h2></div>
      <div class="why-grid">
        <div><h3>On time, every time</h3><p>Book a slot and we keep it. No sitting around for hours waiting your turn.</p></div>
        <div><h3>Skilled with every texture</h3><p>Our stylists are trained in natural hair care, from 4C twist-outs to sleek silk presses.</p></div>
        <div><h3>Clean and safe</h3><p>Sterilised tools, fresh towels for every client, and products we are happy to name.</p></div>
      </div>
    </div>
  </section>

  <section id="reviews">
    <div class="wrap">
      <div class="head"><h2>What our clients say</h2></div>
      <div class="reviews">
        <div class="review"><span class="stars" aria-label="5 stars">★★★★★</span><p>Knotless braids done in four hours and they were neat and light. I was in and out before my lunch meeting.</p><cite>Wanjiru, Kilimani</cite></div>
        <div class="review"><span class="stars" aria-label="5 stars">★★★★★</span><p>Booked through WhatsApp on Friday night and got a Saturday morning slot. Paid by M-Pesa, no stress.</p><cite>Achieng, Lavington</cite></div>
        <div class="review"><span class="stars" aria-label="5 stars">★★★★★</span><p>My wedding makeup lasted from the church to the late reception. The whole bridal party was happy.</p><cite>Faith, Westlands</cite></div>
      </div>
    </div>
  </section>

  <section class="book" id="book">
    <div class="wrap book-grid">
      <div>
        <div class="head" style="margin:0"><h2>Book your appointment</h2><p>Send your request and we will confirm by WhatsApp or phone within 30 minutes during opening hours.</p></div>
        <ul>
          <li><b>Deposit:</b> KES 500 by M-Pesa for braids and bridal bookings.</li>
          <li><b>Running late?</b> Message us. We hold your slot for 15 minutes.</li>
          <li><b>Cancelling?</b> Free up to 12 hours before your appointment.</li>
        </ul>
      </div>
      <form id="form" novalidate>
        <input id="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;min-height:0;height:1px">
        <label>Full name
          <input id="name" autocomplete="name" required>
          <span class="err" data-for="name"></span>
        </label>
        <label>Phone number <span class="hint">Used only to confirm your booking</span>
          <input id="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0712 345 678" required>
          <span class="err" data-for="phone"></span>
        </label>
        <label>Service
          <select id="service" required></select>
          <span class="err" data-for="service"></span>
        </label>
        <div class="row">
          <label>Date
            <input id="date" type="date" required>
            <span class="err" data-for="date"></span>
          </label>
          <label>Preferred time
            <select id="time" required></select>
            <span class="err" data-for="time"></span>
          </label>
        </div>
        <label>Anything we should know? <span class="hint">Optional</span>
          <textarea id="notes" placeholder="Hair length, style inspiration, allergies"></textarea>
        </label>
        <button class="btn btn-primary" type="submit">Request booking</button>
      </form>
    </div>
  </section>

  <section class="visit" id="visit">
    <div class="wrap visit-grid">
      <div>
        <div class="head" style="margin-bottom:20px"><h2>Find us</h2></div>
        <dl>
          <dt>Address</dt><dd><?= e($s['address']) ?></dd>
          <dt>Phone</dt><dd><a href="tel:+<?= e($s['whatsapp']) ?>"><?= e($s['phone']) ?></a></dd>
          <dt>WhatsApp</dt><dd><a href="https://wa.me/<?= e($s['whatsapp']) ?>"><?= e($s['phone']) ?></a></dd>
          <dt>Email</dt><dd><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></dd>
          <dt>Parking</dt><dd>Free for clients in the basement</dd>
        </dl>
        <p style="margin-top:22px"><a class="btn btn-primary" href="https://www.google.com/maps/search/?api=1&query=Sarit+Centre+Westlands+Nairobi" target="_blank" rel="noopener">Get directions</a></p>
      </div>
      <div class="map"><p><b>Westlands, Nairobi</b><br><span style="color:var(--muted)">Next to Sarit Centre, lift to the 2nd floor</span></p></div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>© <span id="yr"></span> <?= e($s['name']) ?>. All rights reserved.</span>
    <span>Instagram @zuribeautylounge · TikTok @zuribeautylounge</span>
  </div>
</footer>

<a class="wa" href="https://wa.me/<?= e($s['whatsapp']) ?>?text=Hi%20Zuri%2C%20I%20would%20like%20to%20book" aria-label="Chat on WhatsApp">Chat on WhatsApp</a>
<div id="toast" role="status" aria-live="polite"></div>

<script>
const SERVICES = <?= json_encode($services, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const HOURS = [["Mon – Fri","8:00 am – 8:00 pm"],["Saturday","8:00 am – 9:00 pm"],["Sunday","10:00 am – 6:00 pm"]];
const OPEN = {0:[10,18],6:[8,21]}; // other days 8-20
const $ = id => document.getElementById(id);
const kes = n => "KES " + n.toLocaleString("en-KE");

// hours + open state
(function(){
  const d = new Date(), day = d.getDay(), h = d.getHours() + d.getMinutes()/60;
  const [o,c] = OPEN[day] || [8,20];
  const idx = day===0?2:day===6?1:0;
  $("hours").innerHTML = HOURS.map((r,i)=>`<li><span class="${i===idx?'on':''}">${r[0]}</span><span class="${i===idx?'on':''}">${r[1]}</span></li>`).join("");
  const isOpen = h>=o && h<c, s = $("openState");
  s.textContent = isOpen ? "Open now" : "Closed now";
  s.classList.toggle("closed", !isOpen);
  $("yr").textContent = d.getFullYear();
})();

// services tabs
let active = Object.keys(SERVICES)[0];
function renderTabs(){
  $("tabs").innerHTML = Object.keys(SERVICES).map(k=>`<button class="tab" role="tab" aria-selected="${k===active}" data-k="${k}">${k}</button>`).join("");
  $("list").innerHTML = SERVICES[active].map(s=>`<div class="item"><div><h3>${s[0]}</h3><small>${s[1]}</small><br><button data-s="${s[0]}">Book this</button></div><span class="price">${kes(s[2])}</span></div>`).join("");
}
renderTabs();
$("tabs").addEventListener("click",e=>{const b=e.target.closest(".tab");if(b){active=b.dataset.k;renderTabs();}});
$("list").addEventListener("click",e=>{
  const b=e.target.closest("button[data-s]"); if(!b) return;
  $("service").value=b.dataset.s; $("book").scrollIntoView(); setTimeout(()=>$("date").focus({preventScroll:true}),500);
});

// form options
$("service").innerHTML = '<option value="">Choose a service</option>' + Object.entries(SERVICES).map(([g,a])=>`<optgroup label="${g}">${a.map(s=>`<option>${s[0]}</option>`).join("")}</optgroup>`).join("");
const times=[]; for(let h=8;h<=19;h++){["00","30"].forEach(m=>times.push(`${h>12?h-12:h}:${m} ${h>=12?"pm":"am"}`));}
$("time").innerHTML = '<option value="">Choose a time</option>' + times.map(t=>`<option>${t}</option>`).join("");
const today = new Date(); today.setMinutes(today.getMinutes()-today.getTimezoneOffset());
$("date").min = today.toISOString().slice(0,10);

// validate + send
function setErr(id,msg){document.querySelector(`[data-for=${id}]`).textContent=msg;return !msg;}
$("form").addEventListener("submit",e=>{
  e.preventDefault();
  const phone = $("phone").value.replace(/[\s-]/g,"");
  let ok = true;
  ok = setErr("name",$("name").value.trim().length<2?"Enter your name.":"") && ok;
  ok = setErr("phone",/^(\+?254|0)[17]\d{8}$/.test(phone)?"":"Enter a Kenyan number, like 0712 345 678.") && ok;
  ok = setErr("service",$("service").value?"":"Choose a service.") && ok;
  ok = setErr("date",$("date").value?"":"Choose a date.") && ok;
  ok = setErr("time",$("time").value?"":"Choose a time.") && ok;
  if(!ok){ const f=document.querySelector(".err:not(:empty)"); f&&f.previousElementSibling.focus(); return; }
  const fd = new FormData();
  [["name","name"],["phone","phone"],["service","service"],["date","date"],["time","time"],["notes","notes"],["website","website"]].forEach(([k,id])=>fd.set(k,$(id).value));
  const btn = e.submitter; btn.disabled = true;
  const toast = m => { const t=$("toast"); t.textContent=m; t.classList.add("show"); setTimeout(()=>t.classList.remove("show"),4500); };
  fetch("book.php",{method:"POST",body:fd}).then(r=>r.json()).then(j=>{
    toast(j.msg);
    if(j.ok) $("form").reset();
  }).catch(()=>toast("Could not send. Please call or WhatsApp us.")).finally(()=>btn.disabled=false);
});
document.querySelectorAll("#form input,#form select").forEach(el=>el.addEventListener("input",()=>{const s=document.querySelector(`[data-for=${el.id}]`);s&&(s.textContent="");}));
</script>
</body>
</html>
