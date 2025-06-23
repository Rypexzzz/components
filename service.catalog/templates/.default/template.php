<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$dir   = $this->GetFolder();
$ver   = '?v='.time();
$inCart = $_SESSION['SERVICE_CART'] ?? [];
?>
<link  rel="stylesheet" href="<?= $dir ?>/style.css<?= $ver ?>">
<script defer src="<?= $dir ?>/script.js<?= $ver ?>"></script>
<div class="service-catalog">
  <!-- ── неподвижная «шапка» (фильтр + хлебные крошки) ───────────── -->
  <header class="catalog-head">
    <div class="sc-filter">
      <select id="role-filter" class="sc-select">
        <option value="">Все роли</option>
        <?php foreach ($arResult['ROLES'] as $rid => $role): ?>
          <option value="<?= $rid ?>"
            <?= in_array($rid, (array)($_GET['roles'] ?? []), true) ? 'selected' : '' ?>>
            <?= htmlspecialcharsbx($role['NAME']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input id="service-search" class="sc-search"
             value="<?= htmlspecialcharsbx($_GET['q'] ?? '') ?>"
             placeholder="Поиск…">
      <div id="sc-total" class="sc-total">
        Итоговая стоимость:
        <?= number_format($arResult['CURRENT_TOTAL'], 0, '', ' ') ?> ₽
      </div>
    </div>
    <div id="sc-info" class="sc-info" style="display:none;"></div>
    <nav class="stage-nav">
      <?php foreach ($arResult['STAGES'] as $sid => $name): ?>
        <a href="#stage-<?= $sid ?>" data-stage-link="<?= $sid ?>"
           class="stage-nav__link"><?= htmlspecialcharsbx($name) ?></a>
      <?php endforeach; ?>
    </nav>
  </header>
  <!-- ── прокручиваемый список этапов / услуг ─────────────────────── -->
  <div class="catalog-scroll">
    <?php foreach ($arResult['MAP'] as $sid => $sec): ?>
      <section class="stage-block" data-stage="<?= $sid ?>">
        <h2 id="stage-<?= $sid ?>" class="stage-title">
          <?= htmlspecialcharsbx($sec['NAME']) ?>
        </h2>
        <?php foreach ($sec['ITEMS'] as $svc):
          $id        = $svc['ID'];
          $inCartFlg = isset($inCart[$id]);
          $rolesCsv  = implode(',', array_keys($svc['ROLES']));
          $roleNames = implode(' ', array_column($svc['ROLES'], 'ROLE_NAME'));
          $roleCnt   = count($svc['ROLES']);
        ?>
          <!-- карточка -->
          <div class="service-row"
               data-id="<?= $id ?>"
               data-name="<?= htmlspecialcharsbx($svc['NAME']) ?>"
               data-roles="<?= $rolesCsv ?>"
               data-result="<?= htmlspecialcharsbx($roleNames) ?>"
               data-stdcost="<?= $svc['STD_COST'] ?>">
            <div class="sr-btns">
              <?php if ($svc['IS_REQUIRED']): ?>
                <button class="sr-status sr-required" disabled>
                  🔒 Обязательно
                </button>
              <?php else: ?>
                <button class="sr-status <?= $inCartFlg ? 'sr-added' : 'sr-add' ?>"
                        data-id="<?= $id ?>">
                  <?= $inCartFlg ? '✓ Добавлено' : 'Добавить' ?>
                </button>
              <?php endif; ?>
              <button class="sr-toggle">Раскрыть ˅</button>
            </div>
            <span class="sr-name"><?= htmlspecialcharsbx($svc['NAME']) ?></span>
            <span class="sr-tag"><?= htmlspecialcharsbx($sec['NAME']) ?></span>
            <div class="sr-cost">
              <?= number_format($svc['STD_COST'], 0, '', ' ') ?> ₽
            </div>
          </div>
          <!-- детали -->
          <div class="service-details" data-id="<?= $id ?>">
            <table class="sd-table">
              <thead><tr>
                <th>Отдел</th><th>Роль</th><th class="sd-hours">Часы</th>
                <th class="sd-crit">Мин.&nbsp;критерии</th>
                <th class="sd-comm">Комментарий</th>
              </tr></thead>
              <tbody>
                <?php $i=0; foreach ($svc['ROLES'] as $r): ?>
                <tr>
                  <td><?= htmlspecialcharsbx($r['DEPT_NAME']) ?></td>
                  <td><?= htmlspecialcharsbx($r['ROLE_NAME']) ?></td>
                  <td class="sd-hours"><?= $r['HOURS'] ?></td>
                  <?php if (!$i++): ?>
                    <td class="sd-crit" rowspan="<?= $roleCnt ?>">
                      <?= $svc['MIN_CRITERIA'] !== '' ?
                            nl2br(htmlspecialcharsbx($svc['MIN_CRITERIA'])) : '—' ?>
                    </td>
                    <td class="sd-comm" rowspan="<?= $roleCnt ?>">
                      <?= $svc['COMMENT'] !== '' ?
                            nl2br(htmlspecialcharsbx($svc['COMMENT'])) : '—' ?>
                    </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
</div>