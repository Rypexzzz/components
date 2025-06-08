/*
 * Каталог услуг – вкладки, фильтр, раскрытие, add/remove
 * «Длинный» вариант (по образцу v4) + удаление услуги
 * © 2025
 */
(() => {
  const BX = window.BX;

  /* ───────────────────────── вкладки ───────────────────────── */
  document.querySelectorAll('.sc-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('is-active'));
      document.querySelectorAll('.sc-section').forEach(s => s.style.display = 'none');

      tab.classList.add('is-active');
      const target = document.querySelector('.sc-section[data-section="' + tab.dataset.section + '"]');
      if (target) target.style.display = 'block';
    });
  });

  /* ──────────────────────── фильтр / поиск ─────────────────── */
  const search   = document.getElementById('service-search');
  const roleSel  = document.getElementById('role-filter');
  const infoBox  = document.getElementById('sc-info');
  const rows     = Array.from(document.querySelectorAll('.service-row'));

  function applyFilters() {
    const q    = (search.value || '').toLowerCase();
    const role = roleSel.value;

    rows.forEach(row => {
      const matchText = !q || row.dataset.name.toLowerCase().includes(q) ||
                               row.dataset.result.toLowerCase().includes(q);
      const matchRole = !role || row.dataset.roles.split(',').includes(role);

      const visible = matchText && matchRole;
      row.style.display = visible ? '' : 'none';

      /* синхронно скрываем раскрытые детали */
      const det = document.querySelector('.service-details[data-id="' + row.dataset.id + '"]');
      if (det && !visible) det.style.display = 'none';
    });

    /* инфострока */
    let msg = '';
    if (q)    msg += `Поиск «${search.value}»`;
    if (role) msg += (msg ? ' · ' : '') +
                     `Роль: ${roleSel.options[roleSel.selectedIndex].textContent}`;
    infoBox.textContent   = msg;
    infoBox.style.display = msg ? 'block' : 'none';
  }
  search.addEventListener('input',  applyFilters);
  roleSel.addEventListener('change', applyFilters);

  /* ───────────────────── итоговая стоимость ───────────────── */
  const totalBox = document.getElementById('sc-total');
  function recalcTotal() {
    let sum = 0;
    document.querySelectorAll('.sr-status.sr-added')
      .forEach(btn => {
        const row  = btn.closest('.service-row');
        sum += parseFloat(row.dataset.stdcost) || 0;
      });
    totalBox.textContent = `Итоговая стоимость: ${sum.toLocaleString('ru-RU')} ₽`;
  }

  /* ───────────────────────── раскрытие ─────────────────────── */
  document.addEventListener('click', e => {
    if (e.target.matches('.sr-toggle')) {
      const toggleBtn = e.target;
      const rowId     = toggleBtn.closest('.service-row').dataset.id;
      const details   = document.querySelector('.service-details[data-id="' + rowId + '"]');
      if (!details) return;

      const opened = details.style.display !== 'none';
      details.style.display = opened ? 'none' : 'block';
      toggleBtn.textContent = opened ? 'Раскрыть ▼' : 'Скрыть ▲';
    }
  });

  /* ─────────────── добавление / удаление услуги ────────────── */
  document.addEventListener('click', e => {
    const addBtn = e.target.closest('.sr-status');
    if (!addBtn || addBtn.classList.contains('sr-required')) return;

    const isAdd = addBtn.classList.contains('sr-add');
    const action = isAdd ? 'addService' : 'removeService';

    addBtn.disabled = true;
    BX.ajax.runComponentAction('mycompany:service.cart', action, {
      mode: 'class',
      data: { serviceId: addBtn.dataset.id }
    }).then(() => {
      /* меняем классы и текст */
      addBtn.classList.toggle('sr-add', !isAdd);
      addBtn.classList.toggle('sr-added', isAdd);
      addBtn.textContent = isAdd ? 'Добавлено' : 'Добавить';
      addBtn.disabled = false;

      recalcTotal();
    }).catch(() => {
      addBtn.disabled = false;
      alert('Не удалось выполнить действие. Попробуйте ещё раз.');
    });
  });

  /* ───────────────────── инициализация ─────────────────────── */
  document.querySelectorAll('.service-details').forEach(d => d.style.display = 'none');
  recalcTotal();
})();
