# Decisions

Every decision the build made, with what it was measured against. Where the
brief said *decide*, this is the decision. Where it said *measure*, this is the
measurement.

---

## 1. The navy is `#08318B`, and the Bill of Sale is the one that disagrees

The brief flagged a one-digit disagreement: printed documents use `#08328B`, the
website stylesheet `#08318B`.

**Measured**, not chosen. The stylesheet the dealership site actually serves was
read on 6 September 2026: `--brand:#08318B`, and `#08328B` appears nowhere in it.

**So:** `#08318B` is the navy, and the printed documents are what should be
corrected. Tell the owner — a logo navy that differs between the website and the
Bill of Sale is a brand disagreeing with itself.

The whole token set was taken from the same stylesheet rather than retyped:
`--night #103071`, `--night-2 #15418D`, `--night-3 #1A4EA8`,
`--brand-wash #E9EEFA`, `--line #E4E9F4`, shadow tint `rgba(16,48,113,…)`,
body font Barlow, display font Barlow Condensed.

## 2. The accent is `#B45309`

The brief asked for a warm amber in the 25–30° hue range, chosen off a render of
the real components at real size rather than named in prose.

Three candidates were rendered on the actual home page — hero, call to action,
gravel badge, vehicle cards — at 1280px
(`docs/screenshots/accent-sheet.png`):

| | hue | white on it | read |
|---|---|---|---|
| **A `#B45309`** | 26.0° | **5.02:1** | warm, outdoor, holds against the navy |
| B `#A85207` | 28.0° | 5.42:1 | browner; muddier beside the navy, less pop |
| C `#C2410C` | 17.5° | 5.18:1 | reads as a warning colour, not as expedition gear |

**A wins on the render.** It passes 4.5:1 on white for text and comfortably 3:1
for a graphical mark, and it is the natural opposite of the navy without
shouting. `--accent-hover #9A4507` (6.26:1) carries accent-coloured text.

It is used for exactly three things: the primary call to action, the
*gravel approved* badge, and the availability highlight. Nothing else.

Deliberately **not** the vendor gold `#C9A053`: that is TIFF's colour, and
borrowing it for the dealer's rental brand blurs the one line this identity
rests on.

## 3. DAAK does not survive 16px, so the favicon is the monogram

Four letters where there were three is the whole risk in adapting the badge, and
it fails first at the favicon size. It was rendered and looked at
(`docs/screenshots/badge-16px-proof.png` and `favicon-candidates.png`) rather than argued about:

| drawing | 48px | 32px | 16px |
|---|---|---|---|
| Full badge, DAAK in the ring | readable | mush | gone |
| DAAK stacked two-by-two | crisp | readable | mush |
| AK monogram | crisp | crisp | **readable** |

**So**, exactly as the brief's fallback says: the favicon is the monogram and the
wordmark sits beside the badge in the header. The ICO carries the right drawing
for each size rather than one drawing squeezed — 16 and 32 are the AK monogram,
48 through 256 are the stacked DAAK.

The full badge keeps the dealership's silhouette: navy plate, white wing bars,
ringed circle, letters inside. It is what the header, print and the 512/1024 PNGs
use. Letterforms are drawn as geometry, so no font has to be installed anywhere
for the mark to render identically.

Rebuild everything with `node brand/build-brand.js`.

## 4. A block covers both its dates

A vehicle that comes back on the 10th is **not** offered for a pick-up on the
10th, because nobody has looked at it yet. A lot that turns vehicles round the
same day can relax this with the `daak_same_day_turnaround` filter.

A site that books one truck twice on a Saturday has done worse than one that
books nothing, so where the rule could go either way it goes the conservative
way. Availability is checked twice: when the search offers a vehicle, and again
when the request is submitted, because a form can sit open on a phone for an hour.

## 5. A request, not a payment

Version one takes a name, a number and dates. No card, no merchant account, no
PCI scope, no chargebacks. The data model already carries a deposit field, so
version two is a field and not a rewrite.

The request is written to the database **before** anything is sent, so a mail
failure never loses a customer. The requests screen says plainly when a
notification did not go out, and the request is still there.

## 6. Nothing sensitive is collected, and something enforces it

The site collects a name, a telephone number, an email, dates, a vehicle and
extras. It never collects a Social Security number, a date of birth, a driver's
licence number, or bank or card details.

That is a sentence on the page — and a promise with nothing behind it is how
these things drift. So `inc/privacy-boundary.php` refuses the write: any meta key
on a request that looks like one of those is dropped before it can be stored, and
the go-live checklist scans what is stored and reports anything that matches.

(The first version of those patterns used `\b`, which does not match `dr_dob`
because an underscore is a word character. The tests caught it. That is why the
tests exist.)

## 7. Tax is stated, and never guessed

Alaska levies a rental tax on passenger vehicles under 90 days (AS 43.52) and the
Municipality of Anchorage levies its own on top (AMC 12.50). **No rate is written
into this code**, including the ones in the brief. They are entered once against
the published schedule, with the date they were verified, and every page that
shows a price then says the same thing.

Until they are entered the site says *"Alaska and Municipality of Anchorage
vehicle rental tax is added to these rates"* — honest, and vague on purpose. The
go-live checklist counts that as not done.

The same rule governs the studded-tire window: seasonal, latitude-dependent, and
printed only once somebody has verified it against the current statute.

## 8. The daylight table is calculated

Hours of daylight per month are computed from the latitude
(61.2181°N, filterable) using standard solar-declination geometry, and the page
says so — sun centre at the horizon, refraction ignored. A daylight table copied
from somewhere else is a table nobody can check. Mid-June comes out at 18h 53m
and mid-December at 5h 06m, which is the right shape for Anchorage.

## 9. The road table has one source

The verdict for each road is a record in the profile, printed on the drive page,
on every vehicle page and in the policies page. There is no second place to type
it, because the site and the rental agreement disagreeing about gravel is the one
disagreement that ends up in court.

## 10. The theme knows no dealer

Name, address, telephone numbers, email and logo are read from the profile record
and appear nowhere in the theme. `tools/tests/no-dealer-literals.sh` is the proof,
and it runs in seconds.

Seeding is outside the theme too: `deploy/daak-seed.json` is imported from the
uploads directory on first run, and never overwrites a value somebody has typed.

One thing to expect rather than be surprised by: the admin menu suffix is derived
from the business name, so it reads **DAA** and not DAAK — it takes first letters
of words. That is a menu label. The badge is drawn art and is unaffected.

## 11. What was not built

Online payment, a loyalty scheme, multi-location, an app, dynamic pricing, and
any integration with the dealership's desktop application. The same car may one
day be rented and then sold; the **VIN** is the field that will join them.
