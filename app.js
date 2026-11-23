// FoodHub front-end behaviour (no inline scripts, CSP friendly)
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function setCartCount(n) {
    var badge = document.getElementById('cart-count');
    if (badge) { badge.textContent = n; }
  }

  function showMessage(type, text) {
    var area = document.getElementById('flash-area');
    if (!area) { return; }
    var div = document.createElement('div');
    div.className = 'alert alert-' + type;
    div.setAttribute('role', 'status');
    div.textContent = text;          // textContent => safe against XSS
    area.appendChild(div);
    setTimeout(function () { div.remove(); }, 2500);
  }

  /* ---- Add to cart with fetch() (Web API: POST api/cart_add) ---- */
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (!form.classList || !form.classList.contains('add-form')) { return; }
    ev.preventDefault();
    var id = parseInt(form.querySelector('input[name="id"]').value, 10);
    fetch('index.php?r=api/cart_add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ id: id })
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.ok) { setCartCount(data.cart_count); showMessage('success', 'Added to cart.'); }
        else { showMessage('error', data.error || 'Could not add item.'); }
      })
      .catch(function () { form.submit(); }); // graceful fallback: normal form post
  });

  /* ---- Live search on the menu page (Web API: GET api/menu) ---- */
  var grid = document.getElementById('menu-grid');
  var q = document.getElementById('q');
  var cat = document.getElementById('category');
  var counter = document.getElementById('result-count');
  var timer = null;

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) { n.className = cls; }
    if (text !== undefined) { n.textContent = text; }
    return n;
  }

  function buildCard(item) {
    var card = el('article', 'card');
    var emoji = el('div', 'card-emoji', item.emoji); emoji.setAttribute('aria-hidden', 'true');
    card.appendChild(emoji);
    card.appendChild(el('h3', '', item.name));
    card.appendChild(el('p', 'muted', item.category));
    card.appendChild(el('p', '', item.description || ''));
    var foot = el('div', 'card-foot');
    foot.appendChild(el('strong', '', item.price_text));
    var form = el('form', 'add-form'); form.method = 'post'; form.action = 'index.php?r=cart/add';
    // Keep the normal POST fallback valid when the AJAX request fails.
    // The API request uses the same token from the meta tag in the X-CSRF-Token header.
    var csrfField = document.createElement('input');
    csrfField.type = 'hidden'; csrfField.name = 'csrf_token'; csrfField.value = csrf;
    var hidden = document.createElement('input');
    hidden.type = 'hidden'; hidden.name = 'id'; hidden.value = item.id;
    var btn = el('button', 'btn btn-sm', 'Add to cart'); btn.type = 'submit';
    form.appendChild(csrfField); form.appendChild(hidden); form.appendChild(btn);
    foot.appendChild(form); card.appendChild(foot);
    return card;
  }

  function search() {
    var url = 'index.php?r=api/menu&q=' + encodeURIComponent(q.value) + '&category=' + encodeURIComponent(cat.value);
    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (data) {
        grid.textContent = '';
        data.items.forEach(function (item) { grid.appendChild(buildCard(item)); });
        if (!data.items.length) { grid.appendChild(el('p', '', 'No dishes match your search.')); }
        counter.textContent = data.count + ' item(s) found';
      })
      .catch(function () { /* keep server-rendered list */ });
  }

  if (grid && q && cat) {
    q.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 250); });
    cat.addEventListener('change', search);
    document.getElementById('filter-form').addEventListener('submit', function (ev) { ev.preventDefault(); search(); });
  }

  /* ---- Checkout: show only the selected payment box + card formatting ---- */
  var radios = document.querySelectorAll('input[name="method"]');
  var boxes = document.querySelectorAll('.pay-box');
  function togglePay() {
    var chosen = document.querySelector('input[name="method"]:checked');
    boxes.forEach(function (b) { b.hidden = !chosen || b.dataset.method !== chosen.value; });
  }
  if (radios.length) {
    radios.forEach(function (r) { r.addEventListener('change', togglePay); });
    togglePay();
  }
  var cardNumber = document.getElementById('card_number');
  if (cardNumber) {
    cardNumber.addEventListener('input', function () {
      var digits = cardNumber.value.replace(/\D/g, '').slice(0, 19);
      cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });
  }
  var expiry = document.getElementById('card_expiry');
  if (expiry) {
    expiry.addEventListener('input', function () {
      var d = expiry.value.replace(/\D/g, '').slice(0, 4);
      expiry.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
    });
  }

  /* ---- Confirm dialogs for destructive admin actions ---- */
  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (form.classList && form.classList.contains('confirm-form')) {
      if (!window.confirm(form.dataset.confirm || 'Are you sure?')) { ev.preventDefault(); }
    }
  });
})();
