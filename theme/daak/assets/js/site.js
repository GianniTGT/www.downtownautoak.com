/**
 * Downtown Auto AK — the small amount of JavaScript this site needs.
 *
 * Everything here is an improvement on something that already works without it:
 * the forms submit, the filters apply and the pages read with JavaScript off.
 * Over 80% of this traffic is a phone on airport or hotel wifi, so there is no
 * framework, no bundle and one network call — the quote — and only when dates change.
 */
(function () {
	'use strict';

	var cfg = window.DAAK || {};

	/* ---------------------------------------------------------------- menu */
	var hamb = document.querySelector('.hamb');
	var menu = document.getElementById('menu');
	if (hamb && menu) {
		hamb.addEventListener('click', function () {
			var open = menu.classList.toggle('is-open');
			hamb.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		menu.addEventListener('click', function (e) {
			if (e.target.tagName === 'A') {
				menu.classList.remove('is-open');
				hamb.setAttribute('aria-expanded', 'false');
			}
		});
	}

	/* ------------------------------------------------------ date sanity */
	// A return date before the pick-up date is the commonest mis-fill on a phone,
	// so the return box simply cannot hold one.
	function pairDates(root) {
		var from = root.querySelector('input[name="from"], input[name="dr_pickup"]');
		var to = root.querySelector('input[name="to"], input[name="dr_return"]');
		if (!from || !to) { return; }
		function sync() {
			if (!from.value) { return; }
			to.min = from.value;
			if (to.value && to.value < from.value) { to.value = from.value; }
		}
		from.addEventListener('change', sync);
		sync();
	}
	Array.prototype.forEach.call(document.querySelectorAll('form'), pairDates);

	/* --------------------------------------------------- fleet filters */
	var filters = document.querySelector('.filters');
	if (filters) {
		Array.prototype.forEach.call(filters.querySelectorAll('select'), function (sel) {
			sel.addEventListener('change', function () { filters.submit(); });
		});
		var apply = filters.querySelector('button[type="submit"]');
		if (apply) { apply.classList.add('is-fallback'); }
	}

	/* ------------------------------------------------------- live quote */
	var form = document.querySelector('.bookform');
	if (form && cfg.rest) {
		var box = form.querySelector('[data-quote]');
		var lines = form.querySelector('[data-quote-lines]');
		var totalEl = form.querySelector('[data-quote-total]');
		var vehicle = parseInt(form.getAttribute('data-vehicle') || '0', 10);
		var timer = null;

		function money(n) {
			return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		}

		function currentExtras() {
			return Array.prototype.filter.call(form.querySelectorAll('input[name="dr_extras[]"]'), function (i) { return i.checked; })
				.map(function (i) { return i.value; });
		}

		function render(q) {
			if (!q || !q.lines || !q.lines.length) { box.hidden = true; return; }
			lines.innerHTML = '';
			q.lines.forEach(function (l) {
				var li = document.createElement('li');
				var label = document.createElement('span');
				var amount = document.createElement('b');
				label.textContent = l.label;
				amount.textContent = l.amount === null ? 'Ask us' : money(l.amount);
				li.appendChild(label);
				li.appendChild(amount);
				lines.appendChild(li);
			});
			totalEl.textContent = money(q.total);
			box.hidden = false;

			// If the vehicle has since been taken for those dates, say so before
			// the customer types their telephone number rather than after.
			var note = form.querySelector('[data-taken]');
			if (q.free === false) {
				if (!note) {
					note = document.createElement('p');
					note.className = 'notice-warn';
					note.setAttribute('data-taken', '');
					box.parentNode.insertBefore(note, box);
				}
				note.textContent = 'This vehicle is already out on those dates. Send the request anyway and we will call you with what is free.';
			} else if (note) {
				note.remove();
			}
		}

		function refresh() {
			if (!vehicle) { return; }
			var from = form.querySelector('[name="dr_pickup"]').value;
			var to = form.querySelector('[name="dr_return"]').value;
			if (!from || !to) { box.hidden = true; return; }
			var url = cfg.rest + '/quote?vehicle=' + vehicle + '&from=' + encodeURIComponent(from) +
				'&to=' + encodeURIComponent(to) + '&extras=' + encodeURIComponent(currentExtras().join(','));
			fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.json() : null; })
				.then(render)
				.catch(function () { box.hidden = true; });   // a quote is a nicety; the form still works
		}

		function schedule() {
			clearTimeout(timer);
			timer = setTimeout(refresh, 250);
		}

		form.addEventListener('change', function (e) {
			if (e.target.name === 'dr_pickup' || e.target.name === 'dr_return' || e.target.name === 'dr_extras[]') { schedule(); }
		});
		refresh();
	}

	/* --------------------------------------------- rent-to-own arithmetic */
	var rto = document.querySelector('[data-rto]');
	if (rto) {
		var ids = ['rto-rate', 'rto-days', 'rto-credit', 'rto-price'];
		var out = {
			rent: rto.querySelector('[data-rto-rent]'),
			credit: rto.querySelector('[data-rto-credit]'),
			final: rto.querySelector('[data-rto-final]'),
			net: rto.querySelector('[data-rto-net]')
		};
		function dollars(n) {
			return '$' + Math.round(n).toLocaleString('en-US');
		}
		function calc() {
			var rate = parseFloat(document.getElementById('rto-rate').value) || 0;
			var days = parseFloat(document.getElementById('rto-days').value) || 0;
			var pct = parseFloat(document.getElementById('rto-credit').value) || 0;
			var price = parseFloat(document.getElementById('rto-price').value) || 0;
			var rent = rate * days;
			var credit = rent * pct / 100;
			if (!rent) {
				out.rent.textContent = out.credit.textContent = out.final.textContent = out.net.textContent = '—';
				return;
			}
			out.rent.textContent = dollars(rent);
			out.credit.textContent = dollars(credit);
			out.final.textContent = price ? dollars(Math.max(0, price - credit)) : '—';
			out.net.textContent = dollars(rent - credit);
		}
		ids.forEach(function (id) {
			var el = document.getElementById(id);
			if (el) { el.addEventListener('input', calc); }
		});
		calc();
	}

	/* --------------------------------------------------- after a submission */
	// Land on the message rather than at the top of a long page.
	if (location.hash === '#request') {
		var target = document.getElementById('request');
		if (target) { target.scrollIntoView({ block: 'start' }); }
	}
}());
