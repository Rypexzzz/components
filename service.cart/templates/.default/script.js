if (window.__SERVICE_CART_JS__) {
  console.warn('service-cart: script already loaded');
} else {
  window.__SERVICE_CART_JS__ = true;
  BX.ready(() => {
    const fmt = n => Number(n).toLocaleString('ru-RU');
    let currentOpenCard = null;

    const debug = (...args) => {
        console.log('[DEBUG]', ...args);
    };

    document.addEventListener('click', e => {
      const t = e.target.closest('.svc-toggle');
      if (t) {
        const card = t.closest('.svc-card');

        if (currentOpenCard && currentOpenCard !== card) {
          currentOpenCard.classList.remove('open');
          const prevBtn = currentOpenCard.querySelector('.svc-toggle');
          if (prevBtn) prevBtn.textContent = 'Читать подробнее';
        }

        const body = card.querySelector('.svc-body');
        const open = card.classList.toggle('open');
        t.textContent = open ? 'Скрыть ▲' : 'Читать подробнее';
        currentOpenCard = open ? card : null;

        const detailBox = document.getElementById('detail-box');

        if (open) {
          const serviceName = card.querySelector('.svc-name').textContent;
          const stageName = card.querySelector('.svc-tag').textContent;
          const bodyClone = body.cloneNode(true);

          detailBox.innerHTML = '';
          const title = document.createElement('h4');
          title.textContent = `${stageName}: ${serviceName}`;
          detailBox.appendChild(title);

          const criteria = bodyClone.querySelector('.svc-criteria');
          const comment = bodyClone.querySelector('.svc-comment');
          if (criteria) detailBox.appendChild(criteria);
          if (comment) detailBox.appendChild(comment);

          const table = bodyClone.querySelector('.role-tbl');
          if (table) {
            const tableTitle = document.createElement('p');
            tableTitle.innerHTML = '<strong>Состав услуги:</strong>';
            detailBox.appendChild(tableTitle);
            detailBox.appendChild(table);
          }
        } else {
          detailBox.innerHTML = '<p class="hint">Выберите «Читать подробнее» слева</p>';
        }
      }

      const del = e.target.closest('.svc-remove');
      if (del) {
        const serviceId = del.dataset.id;
        const card = del.closest('.svc-card');
        BX.ajax.post(
          location.href,
          {ajax: 'Y', action: 'removeService', serviceId: serviceId},
          () => {
            card.style.opacity = '0';
            card.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
              card.remove();
              recalculateGrandTotal();
            }, 300);
          }
        );
      }

      if (e.target.closest('#btn-clear')) {
        if (confirm('Удалить все необязательные услуги из корзины?')) {
          BX.ajax.post(
            location.href,
            {ajax: 'Y', action: 'clearCart'},
            () => {
              document.querySelectorAll('.svc-card:not(.svc-lock)').forEach(card => {
                card.style.opacity = '0';
                card.style.transition = 'opacity 0.3s ease';
                setTimeout(() => card.remove(), 300);
              });
              setTimeout(recalculateGrandTotal, 350);
            }
          );
        }
      }
    });

    document.addEventListener('change', e => {
      if (e.target.matches('.rl-hours')) {
        const inp = e.target;
        const h   = Math.max(0, +inp.value || 0); 
        inp.value = h;
        const tr   = inp.closest('.role-row');
        const rate = +tr.dataset.rate;
        tr.querySelector('.rl-cost').textContent = fmt(h * rate);
        const card = tr.closest('.svc-card');
        let s = 0; 
        card.querySelectorAll('.role-row').forEach(r => {
          const hours = r.querySelector('.rl-hours').value;
          const rate = r.dataset.rate;
          s += hours * rate;
        });
        card.querySelector('.svc-sum-val').textContent = fmt(s);
        recalculateGrandTotal();
        BX.ajax.post(location.href, {
          ajax: 'Y',
          action: 'updateHours',
          serviceId: tr.dataset.service,
          roleId: tr.dataset.role,
          hours: h
        });
      }
    });

    function recalculateGrandTotal() {
      let g = 0; 
      document.querySelectorAll('.svc-sum-val').forEach(v => {
        g += +v.textContent.replace(/\s+/g, '');
      });
      document.getElementById('grand-val').textContent = fmt(g);
    }
  });
}