// Wrap-around carousel (no cloning) — stable index, no cutting
(() => {
  const strip = document.getElementById('ucStrip');
  const prev  = document.getElementById('ucPrev');
  const next  = document.getElementById('ucNext');
  if (!strip || !prev || !next) return;

  const CARD_SEL = '.uc-card, .collection-card';

  // wait for images so widths are correct
  const whenImagesReady = (el) => {
    const imgs = [...el.querySelectorAll('img')];
    if (!imgs.length) return Promise.resolve();
    return Promise.all(imgs.map(img =>
      img.complete ? 1 : new Promise(r => img.addEventListener('load', r, { once:true }))
    ));
  };

  let M = null;          // metrics
  let index = 0;         // current index we control

  function readMetrics() {
    const cs   = getComputedStyle(strip);
    const gap  = parseFloat(cs.columnGap || cs.gap || '0') || 0;
    const padL = parseFloat(cs.paddingLeft  || '0') || 0;
    const padR = parseFloat(cs.paddingRight || '0') || 0;

    const cards = [...strip.querySelectorAll(CARD_SEL)];
    if (!cards.length) return null;

    const cardW = cards[0].offsetWidth;     // includes borders
    const step  = cardW + gap;

    // visible width inside paddings
    const viewW = strip.clientWidth - padL - padR;

    // how many whole cards fit in view
    let n = 1;
    while (n * cardW + (n - 1) * gap <= viewW) n++;
    const visible = Math.max(1, n - 1);

    const lastIndex = Math.max(0, cards.length - visible);
    const maxLeft   = strip.scrollWidth - strip.clientWidth;
    const lastLeft  = Math.min(padL + lastIndex * step, maxLeft);

    return { step, padL, lastIndex, maxLeft, lastLeft };
  }

  function setIndex(i, smooth = true) {
    index = Math.max(0, Math.min(M.lastIndex, i));
    const left = Math.min(M.padL + index * M.step, M.maxLeft);
    strip.scrollTo({ left, behavior: smooth ? 'smooth' : 'auto' });
  }

  function init() {
    M = readMetrics();
    if (!M) return;
    // derive starting index once, then we control it
    const raw = Math.round((strip.scrollLeft - M.padL) / M.step);
    index = Math.max(0, Math.min(M.lastIndex, raw));
    setIndex(index, false);
  }

  function go(dir) {
    if (!M) return;
    const count = M.lastIndex + 1;                  // number of valid positions
    const nextIdx = (index + dir + count) % count;  // wrap both ways
    // if landing on last, use clamped lastLeft so browser max doesn't block
    if (nextIdx === M.lastIndex) {
      index = nextIdx;
      strip.scrollTo({ left: M.lastLeft, behavior: 'smooth' });
    } else {
      setIndex(nextIdx);
    }
  }

  prev.addEventListener('click', () => go(-1));
  next.addEventListener('click', () => go(1));

  whenImagesReady(strip).then(init);
  addEventListener('resize', () => whenImagesReady(strip).then(init));
})();
// Mini-calendar: convert <time datetime="YYYY-MM-DD"> to badge (Mon / Day)
(function () {
  const items = document.querySelectorAll('.events-list .event time[datetime]');
  if (!items.length) return;

  const monthShort = (d) =>
    d.toLocaleString('en', { month: 'short' }); // "Nov" (change 'en' to 'pt-PT' if you prefer)

  items.forEach(t => {
    const iso = t.getAttribute('datetime');
    const d = new Date(iso);
    if (isNaN(d)) return; // ignore bad dates

    const mon = monthShort(d);
    const day = String(d.getDate()).padStart(2, '0');

    // Inject simple structure
    t.innerHTML = `<span class="cal-mon">${mon}</span><span class="cal-day">${day}</span>`;
  });
})();
