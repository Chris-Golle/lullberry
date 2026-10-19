import json, base64, pathlib
from playwright.sync_api import sync_playwright
from content import *

ROOT = pathlib.Path(__file__).parent
FONTS = ROOT / "node_modules/@fontsource"
icons = json.loads((ROOT / "icons.json").read_text())

R_LATIN = "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD"
R_EXT = "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF"

def face(family, slug, weight, style):
    out = ""
    for sub, rng in (("latin", R_LATIN), ("latin-ext", R_EXT)):
        b = base64.b64encode((FONTS / slug / "files" / f"{slug}-{sub}-{weight}-{style}.woff2").read_bytes()).decode()
        out += f"@font-face{{font-family:'{family}';src:url(data:font/woff2;base64,{b}) format('woff2');font-weight:{weight};font-style:{style};unicode-range:{rng};}}"
    return out

FONT_CSS = (face("Playfair Display", "playfair-display", 400, "normal") + face("Playfair Display", "playfair-display", 400, "italic")
            + face("Raleway", "raleway", 400, "normal") + face("Raleway", "raleway", 400, "italic")
            + face("Raleway", "raleway", 500, "normal") + face("Raleway", "raleway", 600, "normal"))

def svg(id_, color="#5c4a35"):
    vb, paths = icons[id_]
    return f'<svg viewBox="{vb}" fill="{color}" xmlns="http://www.w3.org/2000/svg">' + "".join(f'<path d="{d}"/>' for d in paths.split("|")) + "</svg>"

BASE_CSS = FONT_CSS + """
@page { size: A4; margin: 0; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Raleway, sans-serif; color: #2a1f14; background: #fffcf8; font-variant-numeric: lining-nums; }
.page { width: 210mm; height: 297mm; padding: 18mm 20mm 14mm; position: relative; page-break-after: always;
        display: flex; flex-direction: column; background: #fffcf8; overflow: hidden; }
.page:last-child { page-break-after: auto; }
.eyebrow { font-family: 'Playfair Display', serif; font-size: 10pt; letter-spacing: .03em; color: #8a7260; }
.sub { font-family: 'Playfair Display', serif; color: #c4785a; font-size: 13pt; margin-top: 1mm; }
h1.h1s { font-size: 20pt; margin-bottom: 5mm; }
.sym { display:inline-block; width: 2.3mm; height: 2.3mm; border-radius: 50%; border: .9px solid #c4785a; vertical-align: .1mm; }
.sym.full { background: #c4785a; }
svg.leaf { width: 3.4mm; height: 3.4mm; vertical-align: -.4mm; }
h1 { font-family: 'Playfair Display', serif; font-weight: 400; font-size: 26pt; color: #5c4a35; margin: 2.5mm 0 2mm; font-variant-numeric: lining-nums; }
.rule { width: 14mm; height: 1px; background: #c4785a; margin: 3mm 0 6mm; }
.fields { display: flex; gap: 8mm; font-size: 9pt; color: #8a7260; margin-bottom: 8mm; }
.fields span { flex: 1; border-bottom: 1px solid #e8d9c4; padding-bottom: 1.5mm; }
.fields span.short { flex: .45; }
.foot { margin-top: auto; display: flex; justify-content: space-between; font-family: 'Playfair Display', serif; font-size: 9pt; letter-spacing: .04em; color: #8a7260; padding-top: 5mm; }
.box { width: 5.2mm; height: 5.2mm; border: 1.1px solid #8a7260; border-radius: 1.3mm; flex: none; background: #fffcf8; }
"""

UI = {
 "en": dict(c_eyebrow="Lullberry", c_title="21-day habit tracker", habit="My habit", start="Start date",
            week="Week {n}", c_note="Tick a box each day and watch your habit take flight.", days21="21 days", s_tag="21 ideas from the best books on habits", a_tag="21 practices and ideas from the best books on aging well",
            wk_range="Week {n} · days {a} to {b}", q=("“", "”"),
            s_title="How habits take root", s_eyebrow="Lullberry",
            a_title="Aging Well", a_eyebrow="Lullberry"),
 "lt": dict(c_eyebrow="Lullberry", c_title="21 dienos įpročių kalendorius", habit="Mano įprotis", start="Pradžios data",
            week="{n} savaitė", c_note="Kasdien pažymėk langelį ir žiūrėk, kaip tavo įprotis pakyla skrydžiui.", days21="21 diena", s_tag="21 idėja iš geriausių knygų apie įpročius", a_tag="21 praktika ir idėja iš geriausių knygų apie gražų senėjimą",
            wk_range="{n} savaitė · dienos {a}–{b}", q=("„", "“"),
            s_title="Kaip įpročiai įsišaknija", s_eyebrow="Lullberry",
            a_title="Gražiai senti", a_eyebrow="Lullberry"),
}

def head(eyebrow, title, tag, first):
    if first:
        return f'<div class="eyebrow">{eyebrow}</div><h1>{title}</h1><div class="sub">{tag}</div><div class="rule"></div>'
    return f'<div class="eyebrow">{eyebrow}</div><h1 class="h1s">{title}</h1>'

_ST = '<path d="M6 12V6.5" stroke="#c4785a" stroke-width="1" fill="none"/>'
_LR = '<path d="M6 7C6 3.2 8.4 1 12 1C12 4.8 9.6 7 6 7Z"/>'
_LL = '<path d="M6 7C6 3.2 3.6 1 0 1C0 4.8 2.4 7 6 7Z"/>'
_FL = '<circle cx="6.00" cy="2.10" r="1.55"/><circle cx="8.00" cy="3.55" r="1.55"/><circle cx="7.23" cy="5.90" r="1.55"/><circle cx="4.77" cy="5.90" r="1.55"/><circle cx="4.00" cy="3.55" r="1.55"/><circle cx="6" cy="4.2" r="1.1" fill="#fffcf8"/>'
SYM = (f'<svg class="leaf" viewBox="0 0 12 12" fill="#c4785a">{_ST}{_LR}{_LL}</svg>',
       f'<svg class="leaf" viewBox="0 0 12 12" fill="#c4785a"><path d="M6 12V6" stroke="#c4785a" stroke-width="1" fill="none"/>{_FL}</svg>')

# ---------------- Counter ----------------
COUNTER_CSS = BASE_CSS + """
.week { margin-bottom: 9mm; }
.wk { font-family: 'Playfair Display', serif; color: #8a7260; font-size: 10.5pt; margin-bottom: 3mm; }
.row { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2.6mm; }
.cell { background: #f9f5ef; border: 1px solid #e8d9c4; border-radius: 6px; height: 50mm; display: flex; flex-direction: column; align-items: center; padding: 3mm 1.5mm 3.5mm; }
.num { font-family: 'Playfair Display', serif; font-size: 15pt; color: #5c4a35; }
.art { flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; padding: 0 .5mm; }
.art svg { width: 100%; max-height: 22mm; }
.cell .box { width: 6mm; height: 6mm; }
.quote { text-align: center; font-family: 'Playfair Display', serif; color: #8a7260; font-size: 10pt; margin-top: 2mm; }
"""

def counter(u):
    weeks = ""
    for w in range(3):
        cells = "".join(f'<div class="cell"><div class="num">{w*7+d+1}</div><div class="art">{svg(f"b{w+1}{d+1}")}</div><div class="box"></div></div>' for d in range(7))
        weeks += f'<div class="week"><div class="wk">{u["week"].format(n=w+1)}</div><div class="row">{cells}</div></div>'
    return COUNTER_CSS, f'''<section class="page">{head(u["c_eyebrow"], u["c_title"], u["c_note"], True)}
      <div class="fields"><span>{u["habit"]}</span><span class="short">{u["start"]}</span></div>{weeks}</section>'''

# ---------------- Step series ----------------
STEP_CSS = BASE_CSS + """
.step { display: grid; grid-template-columns: 11mm 1fr 7mm; gap: 4mm; align-items: start; padding: 3.6mm 0; border-top: 1px solid #e8d9c4; }
.step:last-of-type { border-bottom: 1px solid #e8d9c4; }
.n { font-family: 'Playfair Display', serif; font-size: 20pt; line-height: 1; color: #c4785a; }
.t { font-family: 'Playfair Display', serif; font-size: 12pt; color: #5c4a35; margin-bottom: 1mm; }
.d { font-size: 8.8pt; line-height: 1.45; }
.q { font-family: 'Playfair Display', serif; font-size: 8.6pt; line-height: 1.4; color: #8a7260; margin-top: 1.5mm; }
.a { font-style: normal; font-family: Raleway, sans-serif; font-size: 7.6pt; margin-left: 1.5mm; white-space: nowrap; }
.step .box { margin-top: 1mm; width: 6mm; height: 6mm; }
.week-label { font-family: 'Playfair Display', serif; color: #8a7260; font-size: 10.5pt; margin: 0 0 2mm; }
.art-top { position: absolute; top: 14mm; right: 20mm; width: 34mm; opacity: .9; }
"""

def steps(u, series, title, eyebrow, tag, habit_field=False, ranges=False, lang=None):
    o, c = u["q"]; pages = ""; total = 6 if lang else 3
    for w in range(3):
        rows = ""
        for i in range(7):
            k = w*7+i; t, d, q, a = series[k]
            rows += f'<div class="step"><div class="n">{k+1}</div><div><div class="t">{t}</div><div class="d">{d}</div><div class="q">{o}{q}{c}<span class="a">{a}</span></div></div><div class="box"></div></div>'
        hd = head(eyebrow, title, tag, w == 0)
        if w == 0 and habit_field: hd += f'<div class="fields"><span>{u["habit"]}</span></div>'
        wl = u["wk_range"].format(n=w+1, a=w*7+1, b=w*7+7) if ranges else u["week"].format(n=w+1)
        pages += f'''<section class="page"><div class="art-top">{svg(f"b{w+1}7", "#c4785a")}</div>{hd}
          <div class="week-label">{wl}</div>{rows}
          <div class="foot"><span>lullberry.com</span><span>{w+1} / {total}</span></div></section>'''
    css = STEP_CSS
    if lang:
        pages += ex_pages(lang, 4, total); css = STEP_CSS + EX_CSS
    return css, pages

# ---------------- Leaflet (EN) ----------------
LEAF_CSS = BASE_CSS + """
.band { display: grid; grid-template-columns: 12mm 1fr; gap: 4mm; padding: 4.5mm 0; border-top: 1px solid #e8d9c4; }
.band.opt { border-bottom: 1px solid #e8d9c4; }
.band.opt .bt, .band.opt .bn { color: #8a7260; }
.bn { font-family: 'Playfair Display', serif; color: #c4785a; font-size: 24pt; line-height: 1; }
.bt { font-family: 'Playfair Display', serif; color: #5c4a35; font-size: 15pt; line-height: 1.2; }
.bm { font-size: 8pt; color: #8a7260; margin: .8mm 0 2mm; letter-spacing: .02em; }
h3 { font-family: 'Playfair Display', serif; font-weight: 400; font-size: 11.5pt; color: #5c4a35; margin: 2mm 0 1.5mm; }
.phase.opt { background: #fffcf8; border-style: dashed; }
.lead { font-family: 'Playfair Display', serif; font-size: 12pt; line-height: 1.5; color: #5c4a35; max-width: 160mm; margin-bottom: 6mm; }
h2 { font-family: 'Playfair Display', serif; font-weight: 400; font-size: 14pt; color: #5c4a35; margin: 0 0 3mm; }
p { font-size: 9.2pt; line-height: 1.55; margin-bottom: 2.2mm; }
.phases { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4mm; margin-bottom: 6mm; }
.phase { background: #f9f5ef; border: 1px solid #e8d9c4; border-radius: 6px; padding: 4mm 4.5mm; }
.phase .pn { font-family: 'Playfair Display', serif; color: #c4785a; font-size: 18pt; line-height: 1; }
.phase .pt { font-family: 'Playfair Display', serif; color: #5c4a35; font-size: 11.5pt; margin: 2.5mm 0 1mm; }
.phase .pd { font-size: 8.8pt; line-height: 1.5; }
.phase .ps { font-size: 8pt; color: #8a7260; margin-top: 2.5mm; }
.two { display: grid; grid-template-columns: .9fr 1.1fr; gap: 7mm; }
.abc { display: grid; grid-template-columns: 24mm 1fr; gap: 1.5mm 2mm; font-size: 9pt; line-height: 1.5; margin-top: 1mm; }
.abc b { font-family: 'Playfair Display', serif; font-weight: 400; color: #c4785a; font-size: 11pt; line-height: 1.35; }
.sign { font-family: 'Playfair Display', serif; color: #8a7260; font-size: 10.5pt; margin-top: 4mm; }
.art-top { position: absolute; top: 14mm; right: 20mm; width: 38mm; }
.band { padding: 4mm 0; } .lead { margin-bottom: 4.5mm; }
"""
EX_CSS = """
.day { display: grid; grid-template-columns: 30mm 1fr 1fr 1fr; gap: 3.5mm; padding: 2.6mm 0; border-top: 1px solid #e8d9c4; }
.day:last-of-type { border-bottom: 1px solid #e8d9c4; }
.dn { font-family: 'Playfair Display', serif; color: #c4785a; font-size: 17pt; line-height: 1; }
.dt { font-family: 'Playfair Display', serif; color: #5c4a35; font-size: 10pt; line-height: 1.25; margin-top: 1.2mm; }
.ex { font-size: 7.5pt; line-height: 1.38; }
.ex div { margin-bottom: 1.3mm; display: flex; gap: 1.5mm; }
.ex i { flex: none; width: 3mm; padding-top: .3mm; }
.colhead span+span { padding-left: 4.5mm; }
.colhead { display: grid; grid-template-columns: 30mm 1fr 1fr 1fr; gap: 3.5mm; font-family: 'Playfair Display', serif; color: #8a7260; font-size: 10pt; margin-bottom: 1.5mm; }
.new { font-size: 7pt; color: #c4785a; }
.day { padding: 1.8mm 0; } .ex { font-size: 7.3pt; line-height: 1.33; } .ex div { margin-bottom: 1mm; }
.day, .colhead { grid-template-columns: 27mm 1fr 1fr 1fr; gap: 3mm; }
"""

G = {
 "en": dict(eyebrow="Lullberry", h1="How to use your 21 days to Aging Well", sub="Learn from the best books, then practice step by step.",
   lead="A habit is a promise kept every day. This Aging Well series helps you welcome the years ahead in the best possible shape, together with the good practices you will choose to grow.",
   steps="Learn, grow, and dig deeper if you like",
   p1t="Learn your series", p1d="One idea a day for a body and mind that grow older with grace: rest, food, movement, strength, presence, connection and acceptance.", p1s="21 days · Sheet: Aging Well",
   p2t="Grow your habit", p2d="Pick one or a few practices that spoke to you and plant them. Tick a box each day and watch your habit take flight.", p2s="21 days · Sheet: 21-day habit tracker",
   p3t="Go deeper, if you like", p3d="Curious about how habits really work? Discover the best tips from the best books: 21 ideas on why some habits stick and how to make them tiny, visible and joyful.", p3s="Sheet: How habits take root",
   dayh="Each day, learn and try",
   d1="Read the day's idea and quote. Then meet it in your own way. Some days you'll try a tiny practice right away. Other days you'll simply pause and reflect on how the idea fits your life.",
   d2="Both count. There is no wrong way to show up. When you have met the day, tick its box.",
   d3="A missed day is a pause, not an ending. Just pick up where you left off.",
   pathh="Feel, do or reflect", pathp="We don't all learn the same way. At the end of the Aging Well sheet, each day's idea comes with examples of how to apply it, in three ways:",
   L=("Feel","Do","Reflect"), Ld=("notice, sense, appreciate.","try something concrete.","read, ponder, write it down."),
   lvl="Each example comes in a gentle version {g1} and a deeper one {g2}. Choose the one that calls you, or mix them.",
   afterh="After the learning",
   after="Look back over your ticks and notes. Which days lit something up? Choose one habit, or a few that belong together. Write it on your 21-day habit tracker with your start date. Keep it tiny, give it a time and a place, and tick a box each day for 21 days. Then decide: keep it, grow it, or plant the next one.",
   sign="Go gently, and enjoy your 21 days!<br>Christophe",
   ex_eyebrow="Lullberry", ex_h1="Make it your practice", ex_tag="Feel, do or reflect: find the practices that fit you.", lv=("gentle practice", "deeper practice"), week="Week {n}"),
 "lt": dict(eyebrow="Lullberry", h1="Kaip naudotis savo 21 diena gražiam senėjimui", sub="Mokykis iš geriausių knygų, tada praktikuok žingsnis po žingsnio.",
   lead="Įprotis yra kasdien tesimas pažadas. Ši „Gražiai senti“ serija padės pasitikti ateinančius metus geriausios formos, kartu su gerosiomis praktikomis, kurias pasirinksi auginti.",
   steps="Mokykis, augink ir, jei nori, gilinkis",
   p1t="Išmok savo seriją", p1d="Kasdien po vieną idėją kūnui ir protui, kurie sensta su malone: poilsis, maistas, judėjimas, jėga, dėmesingumas, ryšys ir priėmimas.", p1s="21 diena · Lapas: Gražiai senti",
   p2t="Augink savo įprotį", p2d="Pasirink vieną ar kelias praktikas, kurios tave palietė, ir pasodink jas. Kasdien pažymėk langelį ir žiūrėk, kaip tavo įprotis pakyla skrydžiui.", p2s="21 diena · Lapas: 21 dienos įpročių kalendorius",
   p3t="Pasigilink, jei nori", p3d="Įdomu, kaip iš tiesų veikia įpročiai? Atrask geriausius patarimus iš geriausių knygų: 21 idėja apie tai, kodėl kai kurie įpročiai prigyja ir kaip padaryti juos mažyčius, matomus ir džiugius.", p3s="Lapas: Kaip įpročiai įsišaknija",
   dayh="Kasdien mokykis ir bandyk",
   d1="Perskaityk dienos idėją ir citatą. Tada pasitik ją savaip. Kai kuriomis dienomis iškart išbandysi mažytę praktiką. Kitomis tiesiog stabtelsi ir apmąstysi, kaip ši idėja dera su tavo gyvenimu.",
   d2="Abu būdai tinka. Nėra neteisingo būdo dalyvauti. Kai dienos idėją pasitikai, pažymėk jos langelį.",
   d3="",
   pathh="Jausk, daryk ar apmąstyk", pathp="Visi mokomės skirtingai. „Gražiai senti“ lapo pabaigoje prie kiekvienos dienos idėjos rasi pavyzdžių, kaip ją pritaikyti, trimis būdais:",
   L=("Jausk","Daryk","Apmąstyk"), Ld=("pastebėk, pajusk, įvertink.","išbandyk ką nors konkretaus.","skaityk, svarstyk, užsirašyk."),
   lvl="Kiekvienas pavyzdys turi švelnesnį {g1} ir gilesnį {g2} variantą. Rinkis tą, kuris tave šaukia, arba derink juos.",
   afterh="Po mokymosi",
   after="Peržvelk savo varneles ir užrašus. Kurios dienos tave uždegė? Pasirink vieną įprotį arba kelis, kurie dera tarpusavyje. Užrašyk jį savo 21 dienos įpročių kalendoriuje kartu su pradžios data. Išlaikyk jį mažytį, suteik jam laiką ir vietą ir 21 dieną kasdien žymėk po langelį. Tada nuspręsk: išlaikyti jį, auginti ar sodinti kitą.",
   sign="Eik švelniai ir mėgaukis savo 21 diena!<br>Christophe",
   ex_eyebrow="Lullberry", ex_h1="Paversk tai savo praktika", ex_tag="Jausk, daryk ar apmąstyk: rask tau tinkančias praktikas.", lv=("švelni praktika", "gilesnė praktika"), week="{n} savaitė"),
}

def leaflet(lang):
    g = G[lang]
    abc = "".join(f'<b>{a}</b><span>{b}</span>' for a, b in zip(g["L"], g["Ld"]))
    lvl = g["lvl"].format(g1=SYM[0], g2=SYM[1])
    pages = f'''<section class="page"><div class="art-top">{svg("b37", "#c4785a")}</div>
    <div class="eyebrow">{g["eyebrow"]}</div>
    <h1 style="max-width:125mm">{g["h1"]}</h1><div class="sub">{g["sub"]}</div><div class="rule"></div>
    <div class="lead">{g["lead"]}</div>
    <div class="band"><div class="bn">1</div><div>
      <div class="bt">{g["p1t"]}</div><div class="bm">{g["p1s"]}</div><p>{g["p1d"]}</p>
      <div class="two">
        <div><h3>{g["dayh"]}</h3><p>{g["d1"]}</p><p>{g["d2"]}</p></div>
        <div><h3>{g["pathh"]}</h3><p>{g["pathp"]}</p><div class="abc">{abc}</div><p style="margin-top:2mm">{lvl}</p></div>
      </div></div></div>
    <div class="band"><div class="bn">2</div><div>
      <div class="bt">{g["p2t"]}</div><div class="bm">{g["p2s"]}</div><p>{g["after"]}</p></div></div>
    <div class="band opt"><div class="bn">+</div><div>
      <div class="bt">{g["p3t"]}</div><div class="bm">{g["p3s"]}</div><p>{g["p3d"]}</p></div></div>
    <div class="sign">{g["sign"]}</div>
    <div class="foot"><span>lullberry.com</span><span></span></div></section>'''
    return LEAF_CSS, pages

def ex_pages(lang, start, total):
    g = G[lang]; ex_data = ABC_NEW if lang == "en" else ABC_LT; titles = AGING_EN if lang == "en" else AGING_LT
    ex = lambda x, y: f'<div class="ex"><div><i>{SYM[0]}</i><span>{x}</span></div><div><i>{SYM[1]}</i><span>{y}</span></div></div>'
    pages = ""
    for w in range(3):
        rows = ""
        for i in range(7):
            k = w*7+i; a1, a2, b1, b2, c1, c2 = ex_data[k]
            rows += f'<div class="day"><div><div class="dn">{k+1}</div><div class="dt">{titles[k][0]}</div></div>{ex(a1,a2)}{ex(b1,b2)}{ex(c1,c2)}</div>'
        pages += f'''<section class="page">{head(g["ex_eyebrow"], g["ex_h1"], g["ex_tag"], w == 0)}
          <div class="colhead"><span>{g["week"].format(n=w+1)}</span><span>{g["L"][0]}</span><span>{g["L"][1]}</span><span>{g["L"][2]}</span></div>{rows}
          <div class="foot"><span>lullberry.com</span><span>{SYM[0]} {g["lv"][0]} &nbsp; {SYM[1]} {g["lv"][1]} &nbsp;&nbsp;&nbsp; {start+w} / {total}</span></div></section>'''
    return pages

THINK = "Reflect"

def html(css, body, lang):
    return f"<!doctype html><html lang='{lang}'><head><meta charset='utf-8'><style>{css}</style></head><body>{body}</body></html>"

docs = {}
for lang, sci, age in (("en", SCIENCE_EN, AGING_EN), ("lt", SCIENCE_LT, AGING_LT)):
    u = UI[lang]; sfx = "" if lang == "en" else "-lt"
    docs[f"lullberry-21-day-counter{sfx}"] = html(*counter(u), lang)
    docs[f"lullberry-21-day-science-of-habits{sfx}"] = html(*steps(u, sci, u["s_title"], u["s_eyebrow"], u["s_tag"], habit_field=True, ranges=True), lang)
    docs[f"lullberry-21-day-aging-well{sfx}"] = html(*steps(u, age, u["a_title"], u["a_eyebrow"], u["a_tag"], lang=lang), lang)
docs["lullberry-21-day-aging-well-guide"] = html(*leaflet("en"), "en")
docs["lullberry-21-day-aging-well-guide-lt"] = html(*leaflet("lt"), "lt")

out = ROOT / "out"; out.mkdir(exist_ok=True)
with sync_playwright() as p:
    b = p.chromium.launch(); pg = b.new_page(viewport={"width": 794, "height": 1123})
    for name, h in docs.items():
        pg.set_content(h, wait_until="load")
        pg.pdf(path=str(out / f"{name}.pdf"), format="A4", print_background=True, prefer_css_page_size=True)
        pg.screenshot(path=str(out / f"{name}.png"), full_page=True)
    b.close()
print("ok")

# review sheet: original vs suggested
labels = ["Feel 1","Feel 2","Do 1","Do 2",THINK+" 1",THINK+" 2"]
rows = ""
for k in range(21):
    for j in range(6):
        o = ABC[k][j] if k < 20 else "(missing)"; n = ABC_NEW[k][j]
        if o != n:
            rows += f"<tr><td class='k'>{k+1} · {labels[j]}</td><td class='o'>{o}</td><td>{n}</td></tr>"
rev = html(BASE_CSS + """body{padding:14mm 16mm} table{border-collapse:collapse;width:100%;font-size:8pt;line-height:1.4}
td{border-top:1px solid #e8d9c4;padding:1.6mm 2mm;vertical-align:top} td.k{white-space:nowrap;color:#c4785a;font-family:'Playfair Display',serif}
td.o{color:#8a7260;width:45%} th{text-align:left;font-family:'Playfair Display',serif;font-weight:400;color:#5c4a35;padding:2mm}""",
  "<h1 style='font-size:18pt'>Examples: before and after</h1><table><tr><th></th><th>Before</th><th>Suggested</th></tr>"+rows+"</table>", "en")
with sync_playwright() as p:
    b = p.chromium.launch(); pg = b.new_page(); pg.set_content(rev, wait_until="load")
    pg.pdf(path=str(out / "examples-review.pdf"), format="A4", print_background=True); b.close()
print("review ok")
