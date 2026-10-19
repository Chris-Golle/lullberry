import build_base as B
from content import AGING_EN
from playwright.sync_api import sync_playwright
T = {  # day: new text
 3: "Lean toward whole foods linked to slower cellular aging: leafy greens, berries, olive oil, nuts. Drink plenty of water.",
 5: "Replace ‘getting old’ with ‘growing wise’. Seeing aging as a time of expansion, not decline, lifts your well-being.",
 6: "Clear physical and mental clutter to reduce stress. Let go of what no longer serves you, from outdated beliefs to unused possessions.",
 7: "Add two short strength sessions this week: bodyweight, bands, or weights all count. Strength isn't about how you look: it's what keeps you independent as you age.",
 8: "Practice noticing your emotions without being ruled by them. This flexibility supports your mental health and gentler relationships.",
 9: "Talk to yourself as you would to a friend. Be gentle with your inner critic: a harsh inner voice can weigh on you more than the years.",
 11: "Notice resistance to change. Practice softening around it. Acceptance isn't resignation: it's clarity.",
 12: "Journal, pray, chant, or sit in nature. Caring for your soul deepens resilience and joy as you age.",
 13: "Choose one small part of your day to improve: how you eat, move, rest, or connect. Small shifts, big ripples.",
 14: "Reach out and let yourself be vulnerable. Share a story. Be seen and heard. Aging well includes loving well.",
 16: "Write, paint, play, or dance. Creative play is food for the soul and mind.",
 17: "Mentor, volunteer, or simply tell stories. Your legacy takes shape in how you show up, starting today.",
 18: "Curiosity is youthfulness in disguise. Try or learn one new thing today: a language, an instrument, a skill. Your brain keeps its power to learn far longer than we think.",
 19: "Identify one grudge or resentment you're holding. Take a first step toward forgiveness, not for the other person, but for your own freedom and peace.",
 20: "Spend 20 minutes outdoors mindfully: walk in a park, sit by water, or garden. Time in nature reduces stress and supports well-being.",
}
Q = {  # day: (new quote, new source, note)
 7: ("The currency of healthy aging is muscle—lean, high-performance muscle.", None, "Em-dash restored (it had been changed to a colon)."),
 21: ("We need to learn to see our physical form as a river. Our body is not a static thing—it changes all the time.", None, "Em-dash restored (it had been changed to a semicolon)."),
 18: (None, "C. S. Lewis in Mark Hyman, Young Forever", "To confirm: this line is usually credited to C. S. Lewis (sometimes Les Brown). If Hyman quotes it, credit as shown."),
}
rows = ""
for k, (t, d, q, a) in enumerate(AGING_EN, 1):
    if k in T:
        rows += f"<tr><td class='k'>{k} · text</td><td class='o'>{d}</td><td>{T[k]}</td></tr>"
    if k in Q:
        nq, na, note = Q[k]
        rows += f"<tr><td class='k'>{k} · quote</td><td class='o'>{q}<br>{a}</td><td>{nq or q}<br>{na or a}<div class='n'>{note}</div></td></tr>"
css = B.BASE_CSS + """body{padding:2mm 16mm} @page{margin:12mm 0} table{border-collapse:collapse;width:100%;font-size:8.2pt;line-height:1.45}
td{border-top:1px solid #e8d9c4;padding:2mm;vertical-align:top} td.k{white-space:nowrap;color:#c4785a;font-family:'Playfair Display',serif;width:20mm}
td.o{color:#8a7260;width:44%} th{text-align:left;font-family:'Playfair Display',serif;font-weight:400;color:#5c4a35;padding:2mm}
.n{font-size:7.2pt;color:#c4785a;margin-top:1mm} tr{break-inside:avoid} p{font-size:8.6pt;line-height:1.5;margin-bottom:4mm}"""
body = ("<div class='eyebrow'>Lullberry</div><h1 style='font-size:20pt'>Aging Well: text review</h1>"
  "<p>Day texts made lighter and clearer (comma splices, overclaims such as ‘proven’, ‘we’ to ‘you’, US ‘well-being’). "
  "Quotes checked against the sheet: two em-dashes restored, all other quotes unchanged. Days not listed stay as they are. The Lithuanian version is not included.</p>"
  "<table><tr><th></th><th>Now</th><th>Suggested</th></tr>" + rows + "</table>")
with sync_playwright() as p:
    b = p.chromium.launch(); pg = b.new_page(); pg.set_content(B.html(css, body, "en"), wait_until="load")
    pg.pdf(path="/mnt/user-data/outputs/aging-well-text-review.pdf", format="A4", print_background=True, prefer_css_page_size=True); b.close()
