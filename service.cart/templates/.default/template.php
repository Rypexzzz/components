<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die(); ?>
<link rel="stylesheet" href="<?= $this->GetFolder() ?>/style.css" />
<script defer src="<?= $this->GetFolder() ?>/script.js"></script>

<div class="service-cart">
  <table class="sc-table">
    <thead>
      <tr><th>Услуга / Роль</th><th>Ставка ₽/ч</th><th>Часы</th><th>Стоимость, ₽</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($arResult['ITEMS'] as $svc): ?>
        <tr class="svc-row" data-id="<?= $svc['ID'] ?>">
          <td colspan="5" class="svc-title">
            <strong><?= htmlspecialcharsbx($svc['NAME']) ?></strong>
            <?php if ($svc['IS_REQUIRED']): ?>
              <span class="svc-required">🔒 обязательная</span>
            <?php else: ?>
              <button class="svc-remove" data-id="<?= $svc['ID'] ?>">Удалить</button>
            <?php endif; ?>
            <span class="svc-sum">Итого: <span class="svc-sum-val"><?= number_format($svc['SUM'],0,'',' ') ?></span> ₽</span>
          </td>
        </tr>
        <?php foreach ($svc['ROLES'] as $rid=>$role): ?>
          <tr class="role-row service-<?= $svc['ID'] ?> role-<?= $rid ?>" data-service="<?= $svc['ID'] ?>" data-role="<?= $rid ?>" data-rate="<?= $role['RATE'] ?>">
            <td class="rl-name">— <?= htmlspecialcharsbx($role['ROLE_NAME']) ?></td>
            <td class="rl-rate"><?= $role['RATE'] ?></td>
            <td>
              <input type="number" min="0" step="1" class="hours-input"
                     data-service="<?= $svc['ID'] ?>" data-role="<?= $rid ?>"
                     value="<?= $role['HOURS'] ?>" />
            </td>
            <td class="cost-cell"><?= number_format($role['COST'],0,'',' ') ?></td>
            <td></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="5" class="cart-total">Общая стоимость: <span id="cart-total-val"></span> ₽</td></tr>
    </tfoot>
  </table>
</div>