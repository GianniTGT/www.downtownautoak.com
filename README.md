# www.downtownautoak.com
Build brief — Downtown Auto AK, a car rental site on downtownautoak.com
# Build brief — Downtown Auto AK, a car rental site on downtownautoak.com

You are building a **car rental website** for an existing used-car dealership in
Anchorage, Alaska. This is a **second product, not a second page**: the dealership sells
cars on `downtownautosale.com`; this rents them on `downtownautoak.com`. They share a lot,
a telephone and a mechanic — nothing else. Do not build it inside the existing site.

Read the whole brief before starting. Where it says *decide*, decide and write the decision
down. Where it says *measure*, measure before building.

---

## 0. Three measurements before the plan — two of them can change it

1. **The domain is not empty.** GoDaddy shows `downtownautoak.com` as "Domain, 2 Websites"
   plus a separate **Airo AI Builder** project on the same name. Read what is actually
   published there before touching DNS, and delete nobody's project without asking.
2. **Hosting is already paid for.** The cPanel plan allows 10 hosted domains and 2 are used;
   50 GB storage. So this costs **no new hosting**: an addon domain, its own docroot, **its
   own WordPress**. Not inside the dealership's install — that site has a delicate go-live
   of its own.
3. **AutoSSL** issues the certificate by itself once the name resolves to that server.
   Confirm on the day rather than assuming.

---

## 1. The business — and the one thing that makes it work

Do not build a small copy of Hertz. You lose that fight at the airport.

**The national chains forbid driving on unpaved roads.** The Dalton, the Denali Highway,
the McCarthy Road, the Steese, the Taylor, the Elliott — the roads to everything a visitor
came to Alaska to see. People discover this after they land. Every Alaska specialist lives
in that hole. So the first sentence on the home page is:

> **Gravel-road approved. Unlimited miles. Winter-equipped.**

Everything else on the site supports that sentence.

**A dealership that repairs its own cars has three revenue lines, not one:**

| | why this dealership specifically |
|---|---|
| **Tourists, May–September** | 4×4s and SUVs cleared for gravel. Highest rate, shortest bookings. |
| **Insurance replacement, all year** | They already repair damaged cars. Handing a customer a car while theirs is in the workshop is a year-round floor no tourist season can take away. **Most rental sites forget this half, and it is the one that pays the winter.** |
| **Rent-to-buy** | A dealership that rents is offering a test drive that lasts a week. "Rent it, and the rental credits against the price if you buy" is an offer no rental company can make and no other dealer in town is making. |

Make the third visible without shouting: a line on the vehicle page and one short section.

---

## 2. Identity — the name, the two marks, and the colour

### The name

The GoDaddy account already calls that domain **"Downtown Auto"**, which is the natural
parent for both halves of the business:

```
Downtown Auto · Sales      downtownautosale.com
Downtown Auto · AK         downtownautoak.com
```

Public-facing name: **Downtown Auto AK** (Downtown Auto Alaska). Short mark: **DAAK**.

### The badge: adapt the dealership's, do not draw a new one

The dealership's existing logo is a navy badge reading **DAS**. The rental brand gets the
**same badge with the letters changed to DAAK** — same silhouette, same navy, same weight.
It must read as the same firm at a glance, because the whole point is that a customer who
knows the dealership already trusts this one.

**DAAK rather than DAA, and the reason is not taste:** the domain is `downtownautoak.com`,
so the badge and the address agree letter for letter. DAA agrees with nothing.

Producing it:

- **Four letters where there were three**, so the letterforms narrow. That is the entire
  risk in this job. Draw it, then look at it at **16px** — the favicon size is the one that
  decides whether a badge works, and it is where a four-letter mark fails first. If DAAK
  will not hold at 16px, the favicon becomes the monogram alone and the wordmark sits
  beside it in the header.
- Deliver: an **SVG master**, PNG at 512 and 1024, and an **ICO carrying 16 / 32 / 48 / 64 /
  128 / 256**.
- Keep the navy exactly as the dealership's (below), so the two badges are siblings.

### The two marks, and where each goes

**Two brands live on this site and they are not interchangeable.**

| | Whose mark | Where it appears |
|---|---|---|
| **The dealership** | the **DAAK** badge | Header, favicon, footer contact block, any printed document |
| **The vendor** | **TIFF Software Solutions** — the T-monogram with the road, white T with a gold road | Footer credit only: *Site by **TIFF** Software Solutions* → `https://hoponeurope.com` |

The dealer's logo is **never a file committed into the theme.** It is uploaded once and read
from the profile, exactly as on the existing site — that is what lets this theme run for the
next dealership without a code edit. The Tiff footer mark **is** in the theme, because the
vendor is the same whoever installs it.

### The dealer's details: read them, never type them

The dealership's **name, address, telephone numbers, email and logo must not be literals
anywhere in the theme.** They live in a profile record and every page reads from there.
Seed it from the existing site, which already stores all of it:

- Official email: **info@downtownautosale.com** — the only one. An old `@outlook.com`
  address was deliberately retired; do not reintroduce it.
- Founded **2016** (Alaska entity **#10045236**). Not 2020 — that was an administrative
  dissolution, and it is exactly the kind of thing that looks like a plausible correction to
  somebody who does not know.
- Google: **4.6 from 92 reviews.** If you show reviews, copy real ones **verbatim** or just
  link to the listing. Never paraphrase and never invent one — a paraphrased review is the
  seller's words in a customer's mouth, over a real person's name.
- Street address, both telephone numbers and opening hours: take them from the existing
  site's contact block. **Do not invent them.**

> One thing to expect rather than be surprised by: any helper that *derives* initials from
> the business name will give **DAA** for "Downtown Auto AK", not DAAK, because it takes
> first letters of words. That only affects admin menu labels. The badge is an uploaded
> image and is unaffected.

### The palette

Take these from the existing site rather than retyping them, and settle the one
disagreement below.

| Token | Value | What it is |
|---|---|---|
| `--brand` | **`#08318B`** | The button and link navy, tied to the logo. Keep it. |
| `--night` | `#103071` | Hero / footer navy |
| `--night-2` | `#15418D` | Hero gradient step |
| `--night-3` | `#1A4EA8` | Hero gradient step |
| shadow tint | `rgba(16, 48, 113, …)` | |
| `--brand-wash` | pale navy tint | Icon discs, quiet panels — read the exact value off the stylesheet |
| Body font | **Barlow** (Google Fonts) | |

**Vendor palette — footer credit only, never page furniture:** forest `#16653C`, gold
`#C9A053`, neutral text `#1C1C1E`, white `#FFFFFF`. The wordmark is **TIFF** in Poppins Bold
over **SOFTWARE SOLUTIONS**.

> **One disagreement to settle by measuring, not by choosing.** The dealership's printed
> documents use `#08328B` and the website stylesheet uses `#08318B` — one digit apart. Read
> the value the site actually serves, use that, and tell the owner which is the real one. A
> logo navy that differs between the website and the Bill of Sale is a brand disagreeing
> with itself.

### What changes between the two sites, and why only this

**Same badge family, same navy, same typography, same components.** A customer who Googles
the dealership and lands here must recognise it in half a second — the 4.6-from-92
reputation only transfers if it looks like the same firm.

**One accent colour separates the two sites**, so a person can tell at a glance which one
they are on. Recommendation: **a warm amber/orange in the 25–30° hue range** — it reads as
expedition and outdoor gear, it is the natural opposite of navy, and it carries the gravel
proposition. Use it for the primary call to action, the *gravel approved* badge and the
availability highlight. Nothing else.

**Deliberately not the Tiff gold `#C9A053`.** That is the vendor's colour, and borrowing it
for the dealer's rental brand blurs the one line this whole identity rests on.

**Do not pick the exact value in prose.** Render two or three candidates on the real
components at real size, check each for **4.5:1 against white** for text and **3:1** for any
graphical mark, and choose from the render. Every colour decision on the dealership site was
made that way, and twice the render overturned what the reasoning had predicted.

**Mobile first, and mean it.** Over 80% of this traffic is a phone, frequently on airport or
hotel wifi.

---

## 3. Alaska — the content that actually sells

No template ships with any of this. Every item is a real question an Alaska renter has.

**Where you may drive.** A plain table on its own page, not fine print:

| Road | The majors | You |
|---|---|---|
| Dalton Hwy (Coldfoot / Prudhoe) | forbidden | **owner decides** — 400 miles of gravel, two spares |
| Denali Highway | forbidden | **owner decides** |
| McCarthy Road | forbidden | **owner decides** |
| Steese / Taylor / Elliott | forbidden | **owner decides** |
| Everything paved | allowed | allowed |

Whatever is decided, **the website and the rental agreement must use the same words.** A
site whose contract disagrees with its marketing about gravel is a site with a court case.

**Winter.** Studded tires are seasonal in Alaska and the window differs by latitude —
Anchorage is north of 60°N, so it is the longer one. **Verify the current statutory dates
before publishing them; never print a date from memory.** Say which vehicles carry studs or
winter tyres, and explain the **engine block heater and the plug-in** — every visitor asks,
and no national site explains it.

**Moose.** The most common serious accident here. One honest paragraph — dusk and dawn, and
what to do — beats a disclaimer.

**Getting out of town.** The Whittier tunnel is one lane, tolled and timed. Alaska Marine
Highway ferries take vehicles but need the owner's permission. Driving the Alcan into Canada
needs a **Canadian non-resident inter-province insurance card** carried in the vehicle. One
line each, and each one is a telephone call saved.

**One-way** to Fairbanks, Seward, Homer, Whittier — offered or not, and at what fee. "Ask
us" is an acceptable answer; silence is not.

**Add-ons are the margin, not the decoration.** In Alaska these genuinely sell: roof box or
rack, tow hitch, camping kit (tent, bags, stove, cooler, chairs), bear spray, child seats,
second driver, satellite messenger. Checkboxes with prices on the booking form.

**Pets.** Alaska renters travel with dogs. Yes with a cleaning fee, or no — but say which.

---

## 4. Legal, tax and insurance

**Tell the owner this before building anything: the blocker is not the website, it is the
insurance policy.** A dealer's garage liability policy almost certainly does not cover
renting vehicles to the public. One call to the agent. If the answer is no, everything below
is premature.

Then, in order of what it costs to miss:

1. **Vehicle rental tax.** Alaska levies a percentage on passenger-vehicle rentals of 90 days
   or less (AS 43.52), and **the Municipality of Anchorage levies its own on top**
   (AMC 12.50). Combined it is a large number, collected from the customer and remitted
   quarterly. **Look the current rates and filing schedule up at the Alaska Department of
   Revenue — do not trust any figure written from memory, including one in this brief.**
   Show the total the customer pays, or state clearly that tax is added. A quoted price that
   grows by a fifth at the counter is this industry's most common complaint.
2. **Recall grounding.** Federal law (the Raechel and Jacqueline Houck Safe Rental Car Act,
   49 U.S.C. §30120) forbids renting a vehicle under an open safety recall, and it bites at
   **fleets of 35 or more**. Below that it is still right practice and worth saying on the
   site: *every vehicle is checked against NHTSA recalls before it goes out.* The VIN is on
   file, so this is checkable rather than a claim.
3. **Business licence.** Alaska state licence (AS 43.70) plus whatever the Municipality
   requires for a rental operation. Confirm the existing dealer licence covers this activity
   — it may not.
4. **Registration.** Vehicles rented to the public may need commercial registration rather
   than dealer plates. Confirm with Alaska DMV.
5. **The damage waiver.** Selling a CDW/LDW is regulated as an insurance-adjacent product in
   several states. Whether Alaska requires a disclosure, a licence or neither is a question
   for an attorney. **Until it is answered, do not sell a waiver on the site** — quote the
   rate and handle protection at the counter.
6. **The rental agreement is the attorney's**, and must settle: gravel permission, mileage
   terms, who may drive, the deposit and the hold, fuel policy, damage, ferry and Canada
   permissions, and recovery costs if the vehicle is retrieved from a road it was not
   permitted on.
7. **Alaska Unfair Trade Practices Act** (AS 45.50.471) — practical effect: the price on the
   site and the price at the counter are the same price.

**And the rule that earns the most trust.** This site collects a name, a telephone number, an
email, dates, a vehicle and extras. **It never collects a Social Security number, a date of
birth, a driver's licence number, or bank or card details.** The licence is examined in
person, at the counter, where it can be looked at. **Say that on the page** — it is the
single most reassuring sentence you can write, and it is also what keeps the sensitive half
off this server.

---

## 5. The pages — nine, and no more in version one

| Page | Contents |
|---|---|
| **Home** | The gravel sentence. Date-range search. Three or four vehicle cards. The three reasons. Reviews. Address and one telephone number. |
| **Fleet** | Cards: photograph, class, seats, drive, transmission, daily rate from, **gravel yes/no**. Filters on class, drive, seats and price — built from the vehicles actually in the fleet; a filter that cannot narrow anything is not drawn. |
| **A vehicle** | Gallery, specs with icons, rates by season and length, what is included, extras, where it may go, and the booking form with the dates already filled in. |
| **Where you can drive** | §3's table in words, with a map. This page is why the site exists. |
| **Rates & what's included** | Daily / weekly / monthly. Mileage. Fuel. Deposit. Extras with prices. Taxes stated plainly. Age rules. |
| **Winter driving in Alaska** | Studs, block heaters, what to carry, daylight hours by month. Genuinely useful, and it is what people search for in October. |
| **Rent to own** | The dealership link, with honest arithmetic. |
| **About / Contact** | Same dealership, same people, same lot. Hours, map, both numbers. |
| **Rental agreement & policies** | The attorney's text plus privacy. Linked from every booking step. |

---

## 6. Booking — take a request, not a payment (v1)

State this to the owner as a recommendation with its reason, not as a limitation:

- Taking a card means a merchant account, PCI scope, refunds, no-shows, chargebacks and a
  deposit-hold policy — none of which exists today.
- A request form plus a telephone call converts perfectly well at this volume, and it ships
  in days rather than months.

Flow: **pick dates → see what is free → choose a vehicle → choose extras → leave a name and
a number → we confirm within the hour.**

**It must still not double-book.** That is the one piece of real machinery version one needs:
an availability calendar per vehicle in the admin, and a search that only offers what is
free. A site that books one truck twice on a Saturday has done worse than one that books
nothing.

Version two, once there is a merchant account: a deposit through Stripe. Design the data
model now so that is a field and not a rewrite.

**Requests go to `info@downtownautosale.com`**, and the site must **save the request to the
database before attempting to send anything** — so a mail failure never loses a customer.

---

## 7. Data model

Keep values machine-readable and labels separate. A value the form never offered is dropped
rather than stored.

**Vehicle:** VIN, year, make, model, class, seats, doors, drive (AWD/4WD/FWD), transmission,
fuel, mileage policy, **gravel-approved**, winter equipment, photographs, daily / weekly /
monthly rate, seasonal rate, minimum days, availability.

**Request:** name, phone, email, pick-up and return date and time, vehicle, extras, message,
source. Nothing else — see §4.

**Deliberately out of scope:** the dealership's desktop application knowing about the rental
fleet. A rental vehicle has a different life and different money; mixing them would put
rental revenue into reports built to measure sale margin. Write the question down instead:
the same car may one day be rented and then sold, and the **VIN** is what will join them.

---

## 8. Before you say it is done

- The nine pages return 200 with no fatal, at phone width and at desktop width.
- A request submitted as a visitor is stored, and the notification arrives.
- A vehicle booked for a date is not offered again for that date.
- No page anywhere collects an SSN, a date of birth, a licence number or card details.
- The dealership's name, address and telephone appear **nowhere in the theme code** — only
  through the profile.
- The DAAK badge is legible at 16px, and the favicon is that badge.
- Tax is stated on every page that shows a price.
- The gravel policy on the site and in the agreement are the same words.
- Verify on the **rendered page**, never from the theme source: fetch it and read the HTML.

---

## 9. Decisions only the owner can make

Put these to him in one conversation, in this order — the first two decide whether there is a
business at all:

1. **Does the insurance cover renting?** One call to the agent. Nothing else matters until it
   is answered.
2. **How many vehicles, and which?** Three is a business; one is a favour. Under 35 keeps the
   federal recall rule off.
3. **Which gravel roads are allowed?** The whole proposition, and a real risk decision — a
   recovery from the Dalton costs thousands.
4. **Airport pick-up, or lot only?** An ANC concession costs a percentage of revenue and a
   contract; a shuttle from the lot costs a driver's time. Off-airport is the sane start.
5. **Rates by season, and the deposit.**
6. **Rent-to-own: how much of the rental credits against the purchase?**
7. **Pets, smoking, additional drivers, minimum age.**
8. **Who answers the requests, and how fast?** A promise on the site is a promise.

---

## 10. Not in version one

Online payment. A loyalty scheme. Multi-location. An app. Dynamic pricing. Integration with
the dealership's desktop application. Every one is a good idea after the first ten rentals
and a distraction before them.

---

**One last thing.** Nothing here starts before the DNS switch for `downtownautosale.com` is
finished. That switch needs a quiet day where nothing else moves, and it has needed one for
three weeks. Finish it, then start this.
