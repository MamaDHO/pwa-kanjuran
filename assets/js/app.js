/* Peningkatan progresif: semua tombol tetap jalan tanpa JavaScript (form biasa).
   Dengan JS: aksi +/-/hapus tidak reload halaman, timer & cek status otomatis. */
(function () {
  'use strict';

  var timers = [];
  var searchTimer = null;

  function $(sel, root) { return (root || document).querySelector(sel); }

  function samePath(url) {
    return new URL(url, location.href).pathname === location.pathname;
  }

  function parse(html) {
    return new DOMParser().parseFromString(html, 'text/html');
  }

  /** Ganti seluruh isi layar, pertahankan posisi scroll. */
  function swapScreen(doc) {
    var next = doc.getElementById('screen');
    if (!next) { location.reload(); return; }
    var y = window.scrollY;
    $('#screen').innerHTML = next.innerHTML;
    document.title = doc.title;
    document.body.className = doc.body.className;
    window.scrollTo(0, y);
    init();
  }

  /** Ganti hanya elemen tertentu (agar kolom pencarian tidak kehilangan fokus). */
  function swapParts(doc, ids) {
    ids.forEach(function (id) {
      var cur = document.getElementById(id), nxt = doc.getElementById(id);
      if (cur && nxt) { cur.replaceWith(nxt); }
    });
    // bar keranjang & badge bisa muncul/hilang -> sinkronkan
    var curBar = $('.cartbar'), nxtBar = $('.cartbar', doc);
    if (curBar && nxtBar) { curBar.replaceWith(nxtBar); }
    else if (curBar && !nxtBar) { curBar.remove(); }
    else if (!curBar && nxtBar) { $('#screen').appendChild(nxtBar); }
  }

  function request(url, opts) {
    opts = opts || {};
    opts.headers = Object.assign({ 'X-Requested-With': 'fetch' }, opts.headers || {});
    opts.credentials = 'same-origin';
    return fetch(url, opts).then(function (res) {
      var type = res.headers.get('content-type') || '';
      if (type.indexOf('application/json') !== -1) {
        return res.json().then(function (d) { return { redirect: d.redirect }; });
      }
      return res.text().then(function (t) { return { html: t }; });
    });
  }

  function handle(result, pushUrl) {
    if (result.redirect) {
      if (samePath(result.redirect)) {
        return request(result.redirect).then(function (r) { return handle(r, result.redirect); });
      }
      location.assign(result.redirect);
      return;
    }
    if (pushUrl) { history.replaceState(null, '', pushUrl); }
    swapScreen(parse(result.html));
  }

  /* ---- form aksi ---- */
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form.classList || !form.classList.contains('ajax')) { return; }
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
      ev.preventDefault();
      return;
    }
    ev.preventDefault();
    var btn = form.querySelector('button');
    if (btn) { btn.disabled = true; }
    request(form.action, { method: 'POST', body: new FormData(form) })
      .then(function (r) { handle(r); })
      .catch(function () { HTMLFormElement.prototype.submit.call(form); });
  });

  /* ---- chip kategori ---- */
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest && ev.target.closest('a[data-nav]');
    if (!a) { return; }
    ev.preventDefault();
    request(a.href).then(function (r) { handle(r, a.href); })
      .catch(function () { location.assign(a.href); });
  });

  /* ---- cari produk (sambil mengetik) ---- */
  document.addEventListener('input', function (ev) {
    if (ev.target.id !== 'q') { return; }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      var form = ev.target.form;
      var params = new URLSearchParams(new FormData(form));
      if (!params.get('q')) { params.delete('q'); }
      var url = form.getAttribute('action') + (params.toString() ? '?' + params.toString() : '');
      request(url).then(function (r) {
        if (r.html) {
          history.replaceState(null, '', url);
          swapParts(parse(r.html), ['daftar-produk', 'chips']);
        }
      });
    }, 250);
  });
  document.addEventListener('submit', function (ev) {
    if (ev.target.classList.contains('search')) { ev.preventDefault(); }
  });

  /* ---- timer QRIS + polling status ---- */
  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function initPayment() {
    var el = document.getElementById('timer');
    if (!el) { return; }
    var end = Date.now() + parseInt(el.dataset.sisa, 10) * 1000;

    function tick() {
      var s = Math.max(0, Math.round((end - Date.now()) / 1000));
      el.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60);
      if (s <= 0) { location.href = 'bayar.php'; } // server mengarahkan ke halaman kedaluwarsa
    }
    tick();
    timers.push(setInterval(tick, 1000));

    timers.push(setInterval(function () {
      fetch('status.php', { cache: 'no-store', credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d.status && d.status !== 'pending') { location.href = 'bayar.php'; } })
        .catch(function () {});
    }, 4000));
  }

  /* ---- toast hilang otomatis ---- */
  function initToast() {
    var t = $('.toast');
    if (!t) { return; }
    setTimeout(function () { t.classList.add('hide'); }, 3500);
    setTimeout(function () { t.remove(); }, 4000);
  }

  function init() {
    timers.forEach(clearInterval);
    timers = [];
    initPayment();
    initToast();
  }

  init();
})();
