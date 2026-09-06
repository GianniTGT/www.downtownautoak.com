# Installing

The site is a WordPress theme. It carries its own vehicle and request post types,
its own availability engine and its own settings, so it has **no plugin
dependencies**.

## 0. Before anything

Nothing here starts before the DNS switch for the sales domain is finished. That
switch needs a quiet day where nothing else moves.

And two questions decide whether there is a business at all — put them to the
owner first, because everything below is premature until they are answered:

1. **Does the insurance policy cover renting vehicles to the public?** A dealer's
   garage liability policy almost certainly does not. One call to the agent.
2. **Do the state and municipal licences cover a rental operation?** The existing
   dealer licence may not.

See `docs/OWNER-QUESTIONS.md` for the rest.

## 1. Hosting

The cPanel plan allows 10 hosted domains and 2 are used, so this costs no new
hosting:

1. Add `downtownautoak.com` as an **addon domain** with **its own document root**
   (e.g. `/home/<user>/downtownautoak.com`). Not inside the dealership's install —
   that site has a delicate go-live of its own.
2. Check what is already published on that domain before touching DNS. GoDaddy
   shows it as "Domain, 2 Websites" plus a separate Airo AI Builder project.
   Read them first, and delete nobody's project without asking.
3. Point the DNS A record at the cPanel server.
4. **AutoSSL** issues the certificate by itself once the name resolves there.
   Confirm it on the day rather than assuming.

## 2. WordPress

A fresh WordPress in that document root. Its own database, its own admin user —
nothing shared with the dealership install.

## 3. The theme

```
wp-content/themes/daak/     <-  the contents of theme/daak/ in this repository
```

Upload, then **before activating** put the seed where the theme will find it:

```
wp-content/uploads/daak-seed.json   <-  deploy/daak-seed.json
```

Activate the theme. On activation it will:

- import the seed into the profile record (never overwriting anything already set)
- create the nine pages and set the front page and the privacy page
- build a primary menu
- flush permalinks

## 4. Fill in what only the owner knows

**Rentals → Setup**, six tabs:

| Tab | What it decides |
|---|---|
| Business profile | name, address, telephone, email, hours, logo, Google rating |
| Policies | the gravel sentence, deposit, fuel, mileage, age, pets, one-way, the response promise |
| Roads | the verdict for each road — this is the page the site exists for |
| Add-ons | roof box, camping kit, bear spray, child seat, second driver, satellite messenger, with prices |
| Seasons | the summer and winter windows and their rate multipliers |
| Tax | the AS 43.52 and AMC 12.50 rates, looked up, with the date they were checked |

Upload the badge to the media library and put its attachment ID in the profile.
Never commit it into the theme.

## 5. Put vehicles in

**Fleet → Add vehicle.** Everything the cards and filters draw comes from here:
VIN, year, make, model, class, seats, doors, drive, transmission, fuel, mileage
policy, gravel approval, winter equipment, rates, minimum days, photographs.

Blocked dates live on the vehicle. Confirming a request writes them there
automatically.

## 6. Check it

**Rentals → Checklist** computes the brief's closing list against what is
actually in the database: profile filled, tax verified, gravel sentence written,
every road answered, every vehicle priced, photographed, VIN on file and recall
checked, the pages published, and nothing sensitive stored.

Two things it cannot check for you are the two above: the insurance and the
licences.

## 7. Mail

Requests go to the profile email address. WP Mail SMTP or the host's mailer
should be configured before go-live, but note the order the site works in: the
request is **saved first**, then sent. A mail failure never loses a customer, and
the requests screen says when one happened.

## Developing on it

```bash
php tools/tests/run.php                 # 52 behaviour checks, no WordPress needed
tools/tests/no-dealer-literals.sh       # the theme must know no dealer
php tools/preview/render.php            # render every template to static HTML
node tools/preview/audit.js 390         # no sideways scroll, no JS errors, tap targets
tools/preview/shoot.sh 390 phone        # screenshots
node brand/build-brand.js               # rebuild the marks and the favicon
```

`tools/preview/` renders the templates outside WordPress against fixture data.
It is not a test of WordPress — it is how you look at a page before it is on a
server, which is the only way to catch a layout that breaks at 390px.
