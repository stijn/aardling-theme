// Browser checks for 1.11.0 against the test site.
import puppeteer from 'puppeteer-core';

const BASE = 'http://127.0.0.1:8080';
const OUT = '/tmp/img-out';
let fails = 0;
const ok = (c, m) => { console.log((c ? 'ok   ' : 'FAIL ') + m); if (!c) fails++; };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const b = await puppeteer.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'], headless: true });

// 1. Cover photos load near the screen, not all at once.
{
	const p = await b.newPage();
	await p.setViewport({ width: 412, height: 823 });
	const got = new Set();
	p.on('request', (r) => { const m = r.url().match(/cover-(\d)\.jpg/); if (m) got.add(m[1]); });
	const errors = [];
	p.on('pageerror', (e) => errors.push(String(e)));
	await p.goto(`${BASE}/covers/`, { waitUntil: 'load' });
	await wait(800);
	ok(got.has('1') && got.size === 1, `on load only the first cover's photo is fetched (${[...got].sort().join(',')})`);
	const n = await p.evaluate(() => document.querySelectorAll('.aardling-lazy-bg').length);
	ok(n === 4, `4 covers wait for the script (got ${n}), the old-style cover included`);
	await p.screenshot({ path: `${OUT}/cover-top.png` });
	for (let y = 0; y < 30; y++) {
		await p.evaluate(() => window.scrollBy(0, innerHeight * 0.9));
		await wait(120);
	}
	await wait(800);
	ok(got.size === 4, `after scrolling to the end all four photos are fetched (${[...got].sort().join(',')})`);
	const bgs = await p.evaluate(() => [...document.querySelectorAll('.aardling-lazy-bg')].map((e) => getComputedStyle(e).backgroundImage.slice(0, 60)));
	ok(bgs.every((x) => x.startsWith('url(')), `every cover shows its photo: ${bgs.map((x) => x.split('/').pop()).join(' ')}`);
	const fixed = await p.evaluate(() => getComputedStyle(document.querySelector('.wp-block-cover__image-background.aardling-lazy-bg')).backgroundAttachment);
	ok(true, `background-attachment on the parallax cover: ${fixed}`);
	ok(errors.length === 0, `no script errors ${errors.join(' | ')}`);
	await p.close();
}

// 2. Without script the photos still show.
{
	const p = await b.newPage();
	await p.setJavaScriptEnabled(false);
	await p.setViewport({ width: 1280, height: 900 });
	await p.goto(`${BASE}/covers/`, { waitUntil: 'load' });
	const bgs = await p.evaluate(() => [...document.querySelectorAll('.aardling-lazy-bg')].map((e) => getComputedStyle(e).backgroundImage.slice(0, 60)));
	ok(bgs.length === 4 && bgs.every((x) => x.startsWith('url(')), `without script every cover shows its photo (${bgs.length})`);
	await p.close();
}

// 3. Footnote numbers in light mode and in Deluminator's dark mode (its link rules added after the theme's).
for (const dark of [false, true]) {
	const p = await b.newPage();
	await p.setViewport({ width: 412, height: 823 });
	await p.goto(`${BASE}/notes/`, { waitUntil: 'load' });
	if (dark) {
		await p.evaluate(() => {
			document.documentElement.classList.add('deluminator-palette', 'deluminator-dark');
			const s = document.createElement('style');
			s.textContent = ':root{--dl-accent:#1f6fa8;--dl-accent-text:#fdf6e3;--dl-link:#5aaaeb;--dl-link-hover:#ffd400;--dl-surface:#1b2b34;--dl-line:#34505c;--dl-text:#e8e6e3;--dl-muted:#8b9b9d}'
				+ 'html.deluminator-palette.deluminator-dark a,html.deluminator-palette.deluminator-dark a:visited,html.deluminator-palette.deluminator-dark .entry .entry-content a{color:var(--dl-link)}'
				+ 'html.deluminator-palette.deluminator-dark a:hover,html.deluminator-palette.deluminator-dark .entry .entry-content a:hover{color:var(--dl-link-hover)}';
			document.head.appendChild(s);
		});
	}
	await wait(400); // Twenty Nineteen fades link colours over 110 ms.
	const c = await p.evaluate(() => { const a = document.querySelector('.entry-content sup.fn a'); return { fg: getComputedStyle(a).color, bg: getComputedStyle(a.parentElement).backgroundColor }; });
	await p.hover('.entry-content sup.fn a');
	await wait(400);
	const h = await p.evaluate(() => getComputedStyle(document.querySelector('.entry-content sup.fn a')).color);
	if (dark) {
		ok(c.fg === 'rgb(253, 246, 227)' && c.bg === 'rgb(31, 111, 168)', `dark mode: number in Deluminator's accent pair (${c.fg} on ${c.bg})`);
		ok(h === 'rgb(253, 246, 227)', `dark mode: same colour on hover (${h})`);
	} else {
		ok(c.fg === 'rgb(255, 255, 255)' && c.bg === 'rgb(65, 105, 225)', `light mode: white on royal blue as before (${c.fg} on ${c.bg})`);
	}
	await p.click('.entry-content sup.fn a');
	await wait(300);
	ok(await p.evaluate(() => document.querySelector('.aardling-fn-pop').matches(':popover-open')), `${dark ? 'dark' : 'light'} mode: pop-up still opens`);
	if (dark) await p.screenshot({ path: `${OUT}/notes-dark.png` });
	await p.close();
}

// 4. "Older posts" keeps a name on phones and its words on wide screens.
for (const width of [412, 1280]) {
	const p = await b.newPage();
	await p.setViewport({ width, height: 900 });
	await p.goto(`${BASE}/`, { waitUntil: 'load' });
	const link = await p.$('a.next.page-numbers');
	const snap = link ? await p.accessibility.snapshot({ root: link }) : null;
	const span = await p.evaluate(() => { const s = document.querySelector('a.next.page-numbers .nav-next-text'); if (!s) return null; const r = s.getBoundingClientRect(); return { d: getComputedStyle(s).display, w: Math.round(r.width) }; });
	ok(!!snap && !!snap.name && snap.name.trim().length > 3, `${width}px: "Older posts" link has a name for screen readers ("${snap && snap.name}")`);
	ok(span && (width < 768 ? span.w <= 1 : span.w > 20), `${width}px: its words are ${width < 768 ? 'hidden from view' : 'visible'} (${span && span.d}, ${span && span.w}px)`);
	await p.close();
}

// 5. Google Analytics: the tag only after the page has loaded; nothing extra next to Cloudflare's loader.
for (const cf of [false, true]) {
	const p = await b.newPage();
	await p.setRequestInterception(true);
	const gtag = [];
	const views = [];
	let loaded = false;
	p.on('request', (r) => {
		const u = r.url();
		if (/googletagmanager\.com\/gtag\/js/.test(u)) gtag.push({ u, afterLoad: loaded });
		if (/google-analytics\.com\/g\/collect/.test(u) || /\/g\/collect\?/.test(u)) {
			if (/en=page_view/.test(u) || /en=page_view/.test(r.postData() || '')) views.push(u);
			return r.abort(); // don't send test hits to the real property
		}
		if (/cover-1\.jpg/.test(u)) return setTimeout(() => r.continue(), 2500); // keep the load event waiting
		r.continue();
	});
	p.on('load', () => { loaded = true; });
	await p.goto(`${BASE}/ga/${cf ? '?cfloader=1' : ''}`, { waitUntil: 'domcontentloaded' });
	await wait(1000);
	const early = await p.evaluate(() => ({ ready: document.readyState, tags: [...document.querySelectorAll('script[src]')].map((s) => s.getAttribute('src')).filter((x) => /gtag|ptp3/.test(x)) }));
	await p.waitForFunction(() => document.readyState === 'complete', { timeout: 20000 });
	await wait(4000);
	const late = await p.evaluate(() => [...document.querySelectorAll('script[src]')].map((s) => s.getAttribute('src')).filter((x) => /gtag|ptp3/.test(x)));
	if (cf) {
		ok(gtag.length === 0 && late.length === 1 && /ptp3/.test(late[0]), `with Cloudflare's loader the theme adds no tag of its own (${late.join(', ')})`);
	} else {
		ok(early.ready !== 'complete' && early.tags.length === 0, `before the load event no Google tag is on the page (state ${early.ready}, ${early.tags.length} tags)`);
		ok(gtag.length === 1 && gtag[0].afterLoad, `one Google tag, requested after the load event (${gtag.length})`);
		ok(views.length === 1, `one page view sent (${views.length})`);
	}
	await p.close();
}

await b.close();
if (fails) { console.log(`${fails} browser check(s) failed`); process.exit(1); }
console.log('all browser checks passed');
