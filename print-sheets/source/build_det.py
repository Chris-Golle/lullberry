import build_base as B, pathlib, json
from det_content import DET_EN, NEW
from playwright.sync_api import sync_playwright
B.AGING_EN = DET_EN; B.ABC_NEW = NEW
g = dict(B.G["en"])
g.update(h1="How to use your 21 days to Digital Detox",
  lead="A habit is a promise kept every day. This Digital Detox series helps you take back your attention from your screens, together with the good practices you will choose to grow.",
  p1d="One idea a day to loosen the pull of your screens: noticing, clearing, protecting your time and spaces, and filling them with real life.",
  p1s="21 days · Sheet: Digital Detox",
  pathp="We don't all learn the same way. At the end of the Digital Detox sheet, each day's idea comes with examples of how to apply it, in three ways:")
B.G["en"] = g
u = B.UI["en"]
docs = {
 "lullberry-21-day-digital-detox": B.html(*B.steps(u, DET_EN, "Digital Detox", "Lullberry", "21 practices and ideas from the best books on digital detox", lang="en"), "en"),
 "lullberry-21-day-digital-detox-guide": B.html(*B.leaflet("en"), "en"),
}
CHECK = """() => [...document.querySelectorAll('.page')].map((p,i) => {
  const r = p.getBoundingClientRect(); const pad = parseFloat(getComputedStyle(p).paddingBottom);
  let maxB = 0, over = [];
  p.querySelectorAll('*').forEach(e => { const b = e.getBoundingClientRect();
    if (b.height && !e.closest('.foot')) maxB = Math.max(maxB, b.bottom);
    if (e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).overflow !== 'visible') over.push(e.className); });
  const f = p.querySelector('.foot').getBoundingClientRect();
  return {page:i+1, gap_to_foot_px: Math.round(f.top - maxB), foot_inside: f.bottom <= r.bottom - pad + 1, over};
})"""
out = pathlib.Path("/root/ps/out"); out.mkdir(exist_ok=True)
with sync_playwright() as p:
    b = p.chromium.launch(); pg = b.new_page(viewport={"width": 794, "height": 1123})
    for name, h in docs.items():
        pg.set_content(h, wait_until="load"); pg.evaluate("document.fonts.ready")
        print(name, json.dumps(pg.evaluate(CHECK)))
        pg.pdf(path=str(out/f"{name}.pdf"), format="A4", print_background=True, prefer_css_page_size=True)
    b.close()
