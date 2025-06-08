<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$link = $this->GetFolder();
?>
<link rel="stylesheet" href="<?= $link ?>/style.css">
<script defer src="<?= $link ?>/script.js"></script>

<?php $inCart = $_SESSION['SERVICE_CART'] ?? []; ?>

<div class="service-catalog">

  <!-- ───── фильтр / поиск ───── -->
  <div class="sc-filter">
    <select id="role-filter" class="sc-select">
      <option value="">Все роли</option>
      <?php foreach ($component->getRoles() as $rid => $role): ?>
        <option value="<?= $rid ?>"><?= htmlspecialcharsbx($role['NAME']) ?></option>
      <?php endforeach; ?>
    </select>

    <input id="service-search" class="sc-search" placeholder="Поиск…">
    <div id="sc-total" class="sc-total">Итоговая стоимость: 0 ₽</div>
  </div>
  <div id="sc-info" class="sc-info" style="display:none;"></div>

  <!-- ───── вкладки этапов ───── -->
  <div class="sc-tabs">
    <?php $first = true;
    foreach ($arResult['MAP'] as $sid => $sec): ?>
      <button type="button"
              class="sc-tab<?= $first ? ' is-active' : '' ?>"
              data-section="<?= $sid ?>">
        <?= htmlspecialcharsbx($sec['NAME'] ?: 'Без названия') ?>
      </button>
    <?php $first = false; endforeach; ?>
  </div>

  <!-- ───── списки услуг ───── -->
  <?php $firstStage = array_key_first($arResult['MAP']);
  foreach ($arResult['MAP'] as $sid => $sec): ?>
    <div class="sc-section"
         data-section="<?= $sid ?>"
         style="display:<?= $sid === $firstStage ? 'block' : 'none' ?>;">

      <?php foreach ($sec['ITEMS'] as $svc):
        $rolesCsv  = implode(',', array_keys($svc['ROLES']));
        $resultTxt = implode(' ', array_column($svc['ROLES'], 'ROLE_NAME'));
        $inCartFlg = isset($inCart[$svc['ID']]);
        $roleCnt   = count($svc['ROLES']);
      ?>
        <div class="service-row"
             data-id="<?= $svc['ID'] ?>"
             data-name="<?= htmlspecialcharsbx($svc['NAME']) ?>"
             data-roles="<?= $rolesCsv ?>"
             data-result="<?= htmlspecialcharsbx($resultTxt) ?>"
             data-stdcost="<?= $svc['STD_COST'] ?>">

          <!-- кнопки -->
          <div class="sr-btns">
            <?php if ($svc['IS_REQUIRED']): ?>
              <button type="button" class="sr-status sr-required" disabled>
                Обязательно
              </button>
            <?php else: ?>
              <button type="button"
                      class="sr-status <?= $inCartFlg ? 'sr-added' : 'sr-add' ?>"
                      data-id="<?= $svc['ID'] ?>">
                <?= $inCartFlg ? 'Добавлено' : 'Добавить' ?>
              </button>
            <?php endif; ?>
            <button type="button" class="sr-toggle">Раскрыть ▼</button>
          </div>

          <!-- название / тег / цена -->
          <span class="sr-name"><?= htmlspecialcharsbx($svc['NAME']) ?></span>
          <span class="sr-tag"><?= htmlspecialcharsbx($component->getStageName($svc['STAGE_ID'])) ?></span>
          <div class="sr-cost"><?= number_format($svc['STD_COST'], 0, '', ' ') ?> ₽</div>
        </div>

        <!-- раскрытие -->
        <div class="service-details" data-id="<?= $svc['ID'] ?>" style="display:none;">
          <table class="sd-table">
            <thead>
              <tr>
                <th>Отдел</th>
                <th>Роль</th>
                <th class="sd-hours">Часы</th>
                <th class="sd-crit">Мин.&nbsp;критерии</th>
                <th class="sd-comm">Комментарий</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 0;
              foreach ($svc['ROLES'] as $r): ?>
                <tr>
                  <td><?= htmlspecialcharsbx($r['DEPT_NAME']) ?></td>
                  <td><?= htmlspecialcharsbx($r['ROLE_NAME']) ?></td>
                  <td class="sd-hours"><?= $r['HOURS'] ?></td>

                  <?php if ($i === 0): ?>
                    <td class="sd-crit" rowspan="<?= $roleCnt ?>">
                      <?= $svc['MIN_CRITERIA']
                           ? nl2br(htmlspecialcharsbx($svc['MIN_CRITERIA']))
                           : '—' ?>
                    </td>
                    <td class="sd-comm" rowspan="<?= $roleCnt ?>">
                      <?= $svc['COMMENT']
                           ? nl2br(htmlspecialcharsbx($svc['COMMENT']))
                           : '—' ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php $i++; endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
