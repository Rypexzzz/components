/*
 * Каталог услуг – фильтр, поиск, раскрытие, корзина
 * 2025-06-18  sum-fix: считаем sr-required
 */
if (window.__SERVICE_CATALOG_JS__) {
  console.warn('service-catalog: script already loaded, skip duplicate');
} else {
  window.__SERVICE_CATALOG_JS__ = true;

  BX.ready(() => {

    /* ---------- DOM ссылки ---------- */
    const rows       = [...document.querySelectorAll('.service-row')];
    const search     = document.getElementById('service-search');
    const roleFilter = document.getElementById('role-filter');
    const infoBox    = document.getElementById('sc-info');
    const totalBox   = document.getElementById('sc-total');
    const scrollBox  = document.querySelector('.catalog-scroll');

    /* ---------- token helper ---------- */
    const token = (txt, onX) => {
      const s = document.createElement('span');
      s.className = 'token';
      s.innerHTML = BX.util.htmlspecialchars(txt) + ' <button>&times;</button>';
      s.querySelector('button').onclick = onX;
      return s;
    };

    /* ---------- фильтр ---------- */
    function apply() {
      const sv = search.value.toLowerCase().trim();
      const rv = roleFilter.value;

      document.querySelectorAll('.service-details.is-visible').forEach(d => {
        d.classList.remove('is-visible');
        d.previousElementSibling?.classList.remove('sr-open');
        const b = d.previousElementSibling?.querySelector('.sr-toggle');
        if (b) b.textContent = 'Раскрыть ˅';
      });

      rows.forEach(r => {
        const okName = !sv ||
          r.dataset.name.toLowerCase().includes(sv) ||
          r.dataset.result.toLowerCase().includes(sv);
        const okRole = !rv || r.dataset.roles.split(',').includes(rv);
        r.style.display = (okName && okRole) ? '' : 'none';
      });

      document.querySelectorAll('.stage-block').forEach(st => {
        const vis = [...st.querySelectorAll('.service-row')]
          .some(r => r.style.display !== 'none');
        st.style.display = vis ? 'block' : 'none';
      });

      infoBox.innerHTML = '';
      if (sv) infoBox.appendChild(token('Поиск: ' + sv, () => {
        search.value = ''; apply();
      }));
      if (rv) infoBox.appendChild(
        token('Роль: ' + roleFilter.options[roleFilter.selectedIndex].textContent,
          () => { roleFilter.value = ''; apply(); }));
      infoBox.style.display = infoBox.children.length ? 'block' : 'none';
    }
    search.addEventListener('input', apply);
    roleFilter.addEventListener('change', apply);
    apply();

    /* ---------- итоговая сумма ---------- */
    const updSum = sum => {
      if (sum === undefined) {
        sum = [...document.querySelectorAll('.sr-status')]
          .filter(b => b.classList.contains('sr-added') ||
                       b.classList.contains('sr-required'))          // ← учитываем оба
          .reduce((t, b) => {
            const r = b.closest('.service-row');
            return t + (parseFloat(r.dataset.stdcost) || 0);
          }, 0);
      }
      totalBox.textContent = 'Итоговая стоимость: ' +
        sum.toLocaleString('ru-RU') + ' ₽';
    };
    updSum();            // пересчитываем сразу после загрузки

    /* ---------- делегированный клик ---------- */
    document.addEventListener('click', ev => {

      /* раскрыть / скрыть */
      const tog = ev.target.closest('.sr-toggle');
      if (tog) {
        const row = tog.closest('.service-row');
        const det = document.querySelector(
          `.service-details[data-id="${row.dataset.id}"]`);
        if (det) {
          const vis = det.classList.toggle('is-visible');
          row.classList.toggle('sr-open', vis);
          tog.textContent = vis ? 'Скрыть ˄' : 'Раскрыть ˅';
          if (vis) det.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        return;
      }

      /* корзина */
      const btn = ev.target.closest('.sr-status');
      if (btn && !btn.classList.contains('sr-required')) {
        const add = btn.classList.contains('sr-add');
        btn.disabled = true;

        BX.ajax.post(location.href, {
          ajax: 'Y',
          action: add ? 'addService' : 'removeService',
          serviceId: btn.dataset.id
        }, resp => {
          let ok = false, total = 0;
          try { const j = JSON.parse(resp); ok = !!j.success; total = j.total || 0; }
          catch (e) {}
          if (ok) {
            btn.classList.toggle('sr-add', !add);
            btn.classList.toggle('sr-added', add);
            btn.textContent = add ? 'Добавлено' : 'Добавить';
            updSum(total);
          } else {
            alert('Сервер вернул некорректный ответ');
          }
          btn.disabled = false;
        });
      }
    });

    /* ---------- хлебные крошки ---------- */
    const nav = [...document.querySelectorAll('.stage-nav__link')];
    nav.forEach(a => a.addEventListener('click', e => {
      e.preventDefault();
      document.getElementById(a.getAttribute('href').slice(1))
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    new IntersectionObserver(entries => {
      entries.forEach(en => {
        nav.forEach(l => {
          l.classList.toggle('is-active',
            l.dataset.stageLink === en.target.dataset.stage && en.isIntersecting);
        });
      });
    }, {
      root: scrollBox,
      rootMargin: '-35% 0px -65% 0px'
    }).observe(document.querySelectorAll('.stage-block')[0]);

  });
}
