# -*- coding: utf-8 -*-
"""
Генератор собственных SVG-иллюстраций сайта «Карамель».
Запуск (из папки site):  python tools/gen_svg.py
Все рисунки нарисованы кодом, чужих изображений нет.
"""
import os, random

OUT = os.path.join(os.path.dirname(__file__), '..', 'assets', 'img')

CORAL, CORAL_D = '#FF6F61', '#D9473A'
CREAM, CREAM_D = '#FFF6E9', '#FFE6C7'
MINT, MINT_D = '#7ED9C3', '#1F8F7A'
BUTTER, SKY, LILAC, PINK = '#FFD36B', '#8EC9F2', '#B9A6F2', '#FFB3C1'
INK = '#3A2E39'


def svg(w, h, body, extra=''):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}" role="img" {extra}>'
            f'{body}</svg>\n')


def balloon(cx, cy, r, color, string=True, rot=0):
    rx, ry = r, r * 1.2
    s = f'<g transform="rotate({rot} {cx} {cy})">'
    if string:
        s += (f'<path d="M{cx} {cy+ry+8} q -10 22 4 40 t -2 44" fill="none" stroke="{INK}" stroke-width="2" opacity=".55"/>')
    s += f'<path d="M{cx} {cy+ry} l -7 11 h14z" fill="{color}"/>'
    s += f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="{color}"/>'
    s += f'<ellipse cx="{cx-rx*0.38}" cy="{cy-ry*0.4}" rx="{rx*0.16}" ry="{ry*0.28}" fill="#fff" opacity=".5" transform="rotate(25 {cx-rx*0.38} {cy-ry*0.4})"/>'
    return s + '</g>'


def confetti(w, h, n, seed, area=None):
    rnd = random.Random(seed)
    cols = [CORAL, MINT, BUTTER, SKY, LILAC, PINK]
    x0, y0, x1, y1 = area or (0, 0, w, h)
    s = ''
    for _ in range(n):
        x, y = rnd.uniform(x0, x1), rnd.uniform(y0, y1)
        c = rnd.choice(cols)
        k = rnd.random()
        a = rnd.randint(0, 180)
        if k < 0.4:
            s += f'<rect x="{x:.0f}" y="{y:.0f}" width="9" height="5" rx="1.5" fill="{c}" transform="rotate({a} {x:.0f} {y:.0f})"/>'
        elif k < 0.75:
            s += f'<circle cx="{x:.0f}" cy="{y:.0f}" r="{rnd.uniform(2.5,4.5):.1f}" fill="{c}"/>'
        else:
            s += f'<path d="M{x:.0f} {y:.0f} l6 -3 l-2 7z" fill="{c}" transform="rotate({a} {x:.0f} {y:.0f})"/>'
    return s


def star(cx, cy, r, color):
    import math
    pts = []
    for i in range(10):
        ang = -math.pi / 2 + i * math.pi / 5
        rr = r if i % 2 == 0 else r * 0.45
        pts.append(f'{cx+rr*math.cos(ang):.1f},{cy+rr*math.sin(ang):.1f}')
    return f'<polygon points="{" ".join(pts)}" fill="{color}"/>'


def cake(cx, cy, scale=1.0):
    s = f'<g transform="translate({cx} {cy}) scale({scale})">'
    s += f'<ellipse cx="0" cy="62" rx="108" ry="14" fill="{INK}" opacity=".12"/>'
    s += f'<rect x="-90" y="8" width="180" height="56" rx="14" fill="{CREAM_D}"/>'
    s += f'<rect x="-90" y="8" width="180" height="20" rx="10" fill="{CORAL}"/>'
    s += f'<path d="M-90 20 q 15 22 30 0 q 15 22 30 0 q 15 22 30 0 q 15 22 30 0 q 15 22 30 0 q 15 22 30 0" fill="{CORAL}"/>'
    s += f'<rect x="-60" y="-34" width="120" height="44" rx="12" fill="{MINT}"/>'
    s += f'<path d="M-60 -22 q 15 20 30 0 q 15 20 30 0 q 15 20 30 0 q 15 20 30 0" fill="#fff" opacity=".85"/>'
    for i, x in enumerate((-36, -12, 12, 36)):
        s += f'<rect x="{x-3}" y="-62" width="6" height="26" rx="2" fill="{[BUTTER, SKY, PINK, LILAC][i]}"/>'
        s += f'<path d="M{x} -80 q -7 9 0 16 q 7 -7 0 -16z" fill="{BUTTER}"/><path d="M{x} -76 q -3 5 0 9 q 3 -4 0 -9z" fill="{CORAL}"/>'
    for x in (-70, -35, 0, 35, 70):
        s += f'<circle cx="{x}" cy="46" r="5" fill="{BUTTER}"/>'
    return s + '</g>'


def gift(x, y, w, h, color, ribbon):
    return (f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="{color}"/>'
            f'<rect x="{x+w/2-6}" y="{y}" width="12" height="{h}" fill="{ribbon}"/>'
            f'<rect x="{x-4}" y="{y-12}" width="{w+8}" height="16" rx="5" fill="{color}"/>'
            f'<rect x="{x+w/2-6}" y="{y-12}" width="12" height="16" fill="{ribbon}"/>'
            f'<path d="M{x+w/2} {y-12} q -26 -26 -26 -4 q 8 8 26 4z M{x+w/2} {y-12} q 26 -26 26 -4 q -8 8 -26 4z" fill="{ribbon}"/>')


def bunting(x0, x1, y, n, seed=3, sag=24):
    rnd = random.Random(seed)
    cols = [CORAL, MINT, BUTTER, SKY, LILAC, PINK]
    s = f'<path d="M{x0} {y} Q {(x0+x1)/2} {y+sag*2} {x1} {y}" fill="none" stroke="{INK}" stroke-width="2" opacity=".5"/>'
    for i in range(n):
        t = (i + 0.5) / n
        x = x0 + (x1 - x0) * t
        yy = y + 2 * sag * t * (1 - t) * 2
        s += f'<path d="M{x-14:.0f} {yy-1:.0f} h28 l-14 30z" fill="{cols[i % len(cols)]}"/>'
    return s


def hat(cx, cy, scale=1.0, color=CORAL):
    return (f'<g transform="translate({cx} {cy}) scale({scale})"><path d="M0 -70 L-40 40 Q0 56 40 40z" fill="{color}"/>'
            f'<path d="M-22 -2 L22 -2 M-31 22 L31 22" stroke="#fff" stroke-width="7" opacity=".7" stroke-linecap="round"/>'
            f'<circle cx="0" cy="-72" r="9" fill="{BUTTER}"/></g>')


def blob(cx, cy, r, color, opacity=1):
    return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{color}" opacity="{opacity}"/>'


def write(path, content):
    full = os.path.join(OUT, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    with open(full, 'w', encoding='utf-8') as f:
        f.write(content)


# ---------- логотип ----------
logo = svg(48, 48, balloon(24, 18, 13, CORAL, False) + f'<path d="M24 41 q -6 3 0 6" fill="none" stroke="{INK}" stroke-width="2"/>'
           + f'<circle cx="36" cy="38" r="6" fill="{MINT}"/><circle cx="12" cy="38" r="4" fill="{BUTTER}"/>', 'aria-hidden="true"')
write('logo-mark.svg', logo)
write('favicon.svg', logo)

# ---------- главная иллюстрация ----------
hero = (blob(300, 230, 200, MINT, .35) + blob(440, 120, 70, BUTTER, .55) + blob(110, 360, 60, PINK, .6)
        + bunting(20, 540, 40, 12, 1, 18)
        + balloon(120, 150, 42, CORAL, rot=-8) + balloon(190, 110, 36, MINT_D, rot=5) + balloon(430, 150, 44, LILAC, rot=8)
        + balloon(495, 220, 34, BUTTER, rot=-5) + balloon(75, 250, 32, SKY, rot=-4)
        + cake(300, 330, 1.15)
        + gift(60, 380, 80, 60, CORAL_D, BUTTER) + gift(445, 390, 70, 50, MINT_D, '#fff')
        + hat(260, 120, .9, MINT_D)
        + confetti(560, 460, 55, 7, (10, 70, 550, 440)) + star(330, 90, 14, BUTTER) + star(520, 330, 10, CORAL))
write('ill/hero.svg', svg(560, 460, hero))

# ---------- иллюстрации разделов 600x400 ----------
def sec_frame(inner, bg):
    return svg(600, 400, f'<rect width="600" height="400" rx="28" fill="{bg}"/>' + inner)

write('ill/sec-birthdays.svg', sec_frame(
    blob(300, 230, 150, '#fff', .55) + bunting(30, 570, 36, 10, 5, 16) + cake(300, 260, 1.35)
    + balloon(90, 150, 36, CORAL) + balloon(510, 150, 36, MINT_D) + balloon(150, 230, 28, BUTTER) + balloon(455, 230, 28, SKY)
    + confetti(600, 400, 40, 11), CREAM_D))

cap = (f'<g transform="translate(300 170)"><path d="M0 -50 L130 0 L0 50 L-130 0z" fill="{INK}"/>'
       f'<path d="M-70 22 v52 q 70 36 140 0 v-52 L0 48z" fill="{CORAL_D}"/>'
       f'<line x1="118" y1="4" x2="118" y2="80" stroke="{BUTTER}" stroke-width="5"/><circle cx="118" cy="86" r="9" fill="{BUTTER}"/></g>')
scroll = (f'<g transform="translate(300 330)"><rect x="-110" y="-24" width="220" height="48" rx="24" fill="#fff"/>'
          f'<rect x="-110" y="-24" width="30" height="48" rx="15" fill="{CREAM_D}"/><rect x="80" y="-24" width="30" height="48" rx="15" fill="{CREAM_D}"/>'
          f'<rect x="-52" y="-4" width="104" height="8" rx="4" fill="{CORAL}" opacity=".6"/><rect x="-6" y="-24" width="12" height="48" fill="{CORAL}"/></g>')
write('ill/sec-graduations.svg', sec_frame(
    blob(300, 200, 160, '#fff', .5) + cap + scroll + star(90, 100, 22, BUTTER) + star(520, 90, 18, CORAL) + star(480, 300, 14, SKY)
    + star(110, 290, 16, LILAC) + confetti(600, 400, 45, 21), '#D8F3EC'))

arch = ''
cols = [CORAL, MINT_D, BUTTER, LILAC, SKY, PINK]
import math
for i in range(13):
    ang = math.pi + i * math.pi / 12
    x, y = 300 + 190 * math.cos(ang), 330 + 220 * math.sin(ang)
    arch += f'<circle cx="{x:.0f}" cy="{y:.0f}" r="{34 if i % 2 == 0 else 26}" fill="{cols[i % 6]}"/><circle cx="{x-8:.0f}" cy="{y-9:.0f}" r="7" fill="#fff" opacity=".45"/>'
write('ill/sec-decor.svg', sec_frame(
    blob(300, 330, 120, BUTTER, .35) + arch + gift(240, 300, 70, 62, CORAL_D, '#fff') + gift(330, 320, 56, 42, MINT_D, BUTTER)
    + confetti(600, 400, 35, 31, (30, 120, 570, 380)), '#FFE3E6'))

write('ill/sec-quests.svg', sec_frame(
    f'<g transform="translate(300 210) rotate(-6)"><rect x="-170" y="-110" width="340" height="220" rx="14" fill="#F7E3B5"/>'
    f'<path d="M-140 60 q 50 -90 100 -30 t 90 -20 t 90 -50" fill="none" stroke="{CORAL_D}" stroke-width="5" stroke-dasharray="3 12" stroke-linecap="round"/>'
    f'<path d="M-142 -70 l22 22 m0 -22 l-22 22" stroke="{CORAL_D}" stroke-width="7" stroke-linecap="round"/>'
    f'<circle cx="130" cy="-60" r="16" fill="{MINT_D}"/><circle cx="130" cy="-60" r="6" fill="#fff"/>'
    f'<rect x="-30" y="-80" width="60" height="38" rx="8" fill="{SKY}"/><path d="M-30 -80 l30 22 l30 -22" fill="none" stroke="#fff" stroke-width="3"/></g>'
    f'<g transform="translate(430 290)"><circle cx="0" cy="0" r="46" fill="none" stroke="{INK}" stroke-width="12"/><circle cx="0" cy="0" r="40" fill="#fff" opacity=".45"/>'
    f'<line x1="32" y1="32" x2="76" y2="76" stroke="{INK}" stroke-width="14" stroke-linecap="round"/></g>'
    f'<g transform="translate(140 300)"><circle cx="0" cy="0" r="22" fill="none" stroke="{BUTTER}" stroke-width="10"/><rect x="18" y="-5" width="60" height="10" rx="4" fill="{BUTTER}"/>'
    f'<rect x="56" y="5" width="8" height="16" fill="{BUTTER}"/><rect x="70" y="5" width="8" height="12" fill="{BUTTER}"/></g>'
    + confetti(600, 400, 30, 41), '#E2F0FB'))

# ---------- иконки услуг 120x120 ----------
def icon(inner, bg):
    return svg(120, 120, f'<circle cx="60" cy="60" r="58" fill="{bg}"/>' + inner)

write('ill/svc-birthday.svg', icon(cake(60, 74, .5), CREAM_D))
write('ill/svc-graduation.svg', icon(
    f'<path d="M60 28 L100 48 L60 68 L20 48z" fill="{INK}"/><path d="M36 58 v22 q 24 14 48 0 v-22 L60 70z" fill="{CORAL_D}"/>'
    f'<line x1="96" y1="50" x2="96" y2="78" stroke="{BUTTER}" stroke-width="4"/><circle cx="96" cy="82" r="5" fill="{BUTTER}"/>', '#D8F3EC'))
write('ill/svc-decor.svg', icon(balloon(42, 46, 17, CORAL, False) + balloon(72, 40, 17, MINT_D, False) + balloon(58, 66, 17, BUTTER, False)
                                + f'<path d="M42 70 q 10 14 16 26 M72 64 q -8 14 -14 32 M58 90 l0 8" fill="none" stroke="{INK}" stroke-width="2" opacity=".5"/>', '#FFE3E6'))
write('ill/svc-quest.svg', icon(
    f'<rect x="22" y="30" width="76" height="56" rx="8" fill="#F7E3B5"/><path d="M30 76 q 14 -30 28 -8 t 28 -22" fill="none" stroke="{CORAL_D}" stroke-width="3" stroke-dasharray="2 7" stroke-linecap="round"/>'
    f'<path d="M32 42 l9 9 m0 -9 l-9 9" stroke="{CORAL_D}" stroke-width="4" stroke-linecap="round"/><circle cx="82" cy="46" r="7" fill="{MINT_D}"/>', '#E2F0FB'))
write('ill/svc-family.svg', icon(
    f'<circle cx="38" cy="44" r="11" fill="{CORAL}"/><rect x="26" y="56" width="24" height="34" rx="11" fill="{CORAL}"/>'
    f'<circle cx="82" cy="44" r="11" fill="{MINT_D}"/><rect x="70" y="56" width="24" height="34" rx="11" fill="{MINT_D}"/>'
    f'<circle cx="60" cy="64" r="8" fill="{BUTTER}"/><rect x="52" y="73" width="16" height="22" rx="8" fill="{BUTTER}"/>'
    + star(60, 28, 9, CORAL), '#F0E9FF'))
write('ill/svc-animator.svg', icon(
    hat(60, 66, .55, MINT_D) + f'<circle cx="60" cy="80" r="14" fill="#FFD9B3"/><circle cx="55" cy="78" r="2" fill="{INK}"/><circle cx="65" cy="78" r="2" fill="{INK}"/>'
    f'<path d="M53 85 q 7 6 14 0" fill="none" stroke="{CORAL_D}" stroke-width="2.5" stroke-linecap="round"/>'
    f'<circle cx="38" cy="84" r="6" fill="{CORAL}"/><circle cx="84" cy="88" r="5" fill="{BUTTER}"/>', '#FFF1C9'))

# ---------- прочие иллюстрации ----------
write('ill/about.svg', svg(240, 170, f'<rect width="240" height="170" rx="24" fill="{CREAM_D}"/>' + bunting(10, 230, 20, 7, 2, 10)
      + balloon(60, 80, 24, CORAL, False) + balloon(180, 80, 24, MINT_D, False) + cake(120, 118, .55) + confetti(240, 170, 22, 5)))
write('ill/cta.svg', svg(220, 160, balloon(60, 60, 34, CORAL, rot=-8) + balloon(130, 50, 30, MINT_D, rot=6) + balloon(180, 90, 24, BUTTER, rot=10)
      + confetti(220, 160, 22, 9) + star(110, 130, 12, LILAC)))
write('ill/404.svg', svg(360, 260,
      blob(180, 140, 110, MINT, .3)
      + f'<g transform="rotate(14 180 120)"><ellipse cx="180" cy="120" rx="52" ry="40" fill="{CORAL}"/><path d="M178 158 l-8 12 h16z" fill="{CORAL}"/>'
        f'<ellipse cx="160" cy="105" rx="9" ry="6" fill="#fff" opacity=".45"/>'
        f'<circle cx="162" cy="118" r="4" fill="{INK}"/><circle cx="198" cy="118" r="4" fill="{INK}"/><path d="M168 136 q 12 -9 24 0" fill="none" stroke="{INK}" stroke-width="3" stroke-linecap="round"/></g>'
      + f'<path d="M180 170 q -20 22 6 40 t -8 40" fill="none" stroke="{INK}" stroke-width="2.5" opacity=".6"/>'
      + confetti(360, 260, 30, 13) + star(300, 60, 12, BUTTER)))
for i, (c, hair) in enumerate([(CORAL, '#6B3F2A'), (MINT_D, INK), (LILAC, '#B5542B')], 1):
    write(f'ill/person-{i}.svg', svg(96, 96, f'<circle cx="48" cy="48" r="46" fill="{CREAM_D}"/>'
          f'<path d="M14 92 q 4 -30 34 -30 t 34 30z" fill="{c}"/><circle cx="48" cy="40" r="18" fill="#FFD9B3"/>'
          f'<path d="M30 38 q 2 -22 18 -22 t 18 22 q -8 -10 -18 -10 t -18 10z" fill="{hair}"/>'
          f'<circle cx="42" cy="42" r="2" fill="{INK}"/><circle cx="54" cy="42" r="2" fill="{INK}"/><path d="M42 50 q 6 5 12 0" fill="none" stroke="{CORAL_D}" stroke-width="2" stroke-linecap="round"/>'))

# схема проезда
road = '#fff'
write('ill/map.svg', svg(520, 340,
      f'<rect width="520" height="340" rx="22" fill="#E9F5EF"/>'
      f'<rect x="0" y="140" width="520" height="52" fill="{road}"/><line x1="0" y1="166" x2="520" y2="166" stroke="#C9CFD3" stroke-width="3" stroke-dasharray="14 10"/>'
      f'<rect x="250" y="0" width="44" height="340" fill="{road}"/>'
      f'<rect x="30" y="30" width="90" height="70" rx="10" fill="#CBE8DA"/><rect x="140" y="40" width="80" height="60" rx="10" fill="#CBE8DA"/>'
      f'<rect x="320" y="30" width="150" height="80" rx="10" fill="{CREAM_D}"/><rect x="40" y="226" width="150" height="80" rx="10" fill="{CREAM_D}"/>'
      f'<circle cx="95" cy="262" r="0" fill="none"/>'
      f'<rect x="330" y="226" width="62" height="70" rx="8" fill="#D5DADD"/><text x="361" y="266" font-size="30" text-anchor="middle" font-family="Verdana, sans-serif" font-weight="700" fill="#5C6B73">P</text>'
      f'<text x="130" y="158" font-size="15" font-family="Verdana, sans-serif" fill="#5C6B73">ул. Учебная</text>'
      f'<text x="300" y="320" font-size="13" font-family="Verdana, sans-serif" fill="#5C6B73" transform="rotate(-90 304 330)">пер. Весёлый</text>'
      f'<g transform="translate(420 130)"><rect x="-10" y="-44" width="6" height="44" fill="{INK}"/><rect x="-26" y="-60" width="52" height="22" rx="6" fill="{MINT_D}"/>'
      f'<text x="0" y="-45" font-size="11" text-anchor="middle" font-family="Verdana, sans-serif" fill="#fff" font-weight="700">Остановка</text></g>'
      f'<g transform="translate(345 70)"><path d="M0 40 C-34 4 -30 -34 0 -34 C30 -34 34 4 0 40z" fill="{CORAL}"/><circle cx="0" cy="-8" r="10" fill="#fff"/></g>'
      f'<text x="345" y="128" font-size="14" text-anchor="middle" font-family="Verdana, sans-serif" font-weight="700" fill="{INK}">Офис «Карамель»</text>'
      f'<path d="M420 190 L420 214 L360 214 L360 150" fill="none" stroke="{CORAL_D}" stroke-width="4" stroke-dasharray="6 8" stroke-linecap="round"/>'))

# ---------- баннеры 1200x400 ----------
def banner(bg1, bg2, art):
    grad = (f'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{bg1}"/><stop offset="1" stop-color="{bg2}"/></linearGradient></defs>')
    return svg(1200, 400, grad + '<rect width="1200" height="400" fill="url(#g)"/>' + art, 'preserveAspectRatio="xMidYMid slice"')

write('banners/b1.svg', banner('#FFD9D2', '#FFF0D6',
      blob(930, 200, 190, '#fff', .45) + cake(940, 250, 1.5) + balloon(790, 130, 44, CORAL) + balloon(1090, 120, 44, MINT_D) + balloon(1130, 260, 32, BUTTER)
      + confetti(1200, 400, 60, 51, (600, 0, 1200, 400))))
write('banners/b2.svg', banner('#D6F2EA', '#E8F7FF',
      blob(950, 190, 170, '#fff', .5) + f'<g transform="translate(560 -10) scale(1.2)">{cap}</g>'.replace('translate(300 170)', 'translate(300 170)')
      + star(840, 90, 26, BUTTER) + star(1120, 90, 22, CORAL) + star(1100, 330, 20, LILAC) + confetti(1200, 400, 60, 52, (600, 0, 1200, 400))))
write('banners/b3.svg', banner('#FFE0E6', '#FFF1CC',
      ''.join(f'<circle cx="{x}" cy="{y}" r="{r}" fill="{c}"/><circle cx="{x-r*.3:.0f}" cy="{y-r*.3:.0f}" r="{r*.2:.0f}" fill="#fff" opacity=".5"/>'
              for x, y, r, c in [(760, 120, 70, CORAL), (860, 230, 60, MINT_D), (960, 110, 66, BUTTER), (1060, 220, 72, LILAC), (1150, 100, 50, SKY),
                                 (850, 340, 44, PINK), (1000, 340, 48, CORAL)]) + confetti(1200, 400, 50, 53, (600, 0, 1200, 400))))
write('banners/b4.svg', banner('#DCEBFA', '#F1E8FF',
      f'<g transform="translate(640 20) scale(.95)">' + '' + '</g>'
      + f'<g transform="translate(900 200) rotate(-6)"><rect x="-220" y="-150" width="440" height="290" rx="18" fill="#F7E3B5"/>'
        f'<path d="M-180 80 q 70 -120 130 -40 t 120 -30 t 110 -70" fill="none" stroke="{CORAL_D}" stroke-width="7" stroke-dasharray="4 16" stroke-linecap="round"/>'
        f'<path d="M-182 -90 l28 28 m0 -28 l-28 28" stroke="{CORAL_D}" stroke-width="9" stroke-linecap="round"/><circle cx="160" cy="-80" r="22" fill="{MINT_D}"/><circle cx="160" cy="-80" r="8" fill="#fff"/></g>'
      + f'<g transform="translate(1110 310)"><circle r="46" fill="none" stroke="{INK}" stroke-width="12"/><line x1="32" y1="32" x2="78" y2="78" stroke="{INK}" stroke-width="14" stroke-linecap="round"/></g>'
      + confetti(1200, 400, 40, 54, (600, 0, 1200, 400))))
write('banners/b5.svg', banner('#FFF0C9', '#FFE3E6',
      f'<g transform="translate(860 150)">' + gift(-100, 40, 130, 100, CORAL_D, BUTTER) + gift(40, 70, 100, 70, MINT_D, '#fff') + '</g>'
      + balloon(1080, 120, 50, SKY) + balloon(760, 110, 42, PINK) + hat(1000, 170, 1.2, LILAC) + bunting(560, 1180, 30, 11, 6, 16)
      + confetti(1200, 400, 55, 55, (600, 0, 1200, 400))))
print('SVG готовы:', sum(len(f) for _, _, f in os.walk(OUT)))
