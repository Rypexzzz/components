/* Корзина услуг – редактирование, пересчёт, удаление */
(() => {
  const BX = window.BX;

  const fmt = n => Number(n).toLocaleString('ru-RU');

  function updateTotals() {
    let total = 0;
    document.querySelectorAll('.svc-row').forEach(row => {
      let svcSum = 0;
      const svcId = row.dataset.id;
      document.querySelectorAll('.role-row[data-service="'+svcId+'"] .cost-cell').forEach(c => {
        svcSum += parseFloat(c.textContent.replace(/\s+/g,''));
      });
      row.querySelector('.svc-sum-val').textContent = fmt(svcSum);
      total += svcSum;
    });
    document.getElementById('cart-total-val').textContent = fmt(total);
  }
  updateTotals();

  /* изменение часов */
  document.addEventListener('change', e => {
    if (e.target.matches('.hours-input')) {
      const input = e.target;
      const hours = parseFloat(input.value);
      const tr     = input.closest('tr');
      const rate   = parseFloat(tr.dataset.rate);
      const costEl = tr.querySelector('.cost-cell');
      costEl.textContent = fmt(rate * hours);
      updateTotals();
      // ajax save
      BX.ajax.runComponentAction('mycompany:service.cart','updateHours',{
        mode:'class',
        data:{ serviceId:input.dataset.service, roleId:input.dataset.role, hours:hours }
      });
    }
  });

  /* удаление услуги */
  document.addEventListener('click', e => {
    if (e.target.matches('.svc-remove')) {
      const btn = e.target;
      const svcId = btn.dataset.id;
      BX.ajax.runComponentAction('mycompany:service.cart','removeService',{
        mode:'class',data:{ serviceId:svcId }
      }).then(()=>{
        // удаляем из DOM
        document.querySelectorAll('[data-service="'+svcId+'"], .svc-row[data-id="'+svcId+'"]').forEach(el=>el.remove());
        updateTotals();
      });
    }
  });
})();