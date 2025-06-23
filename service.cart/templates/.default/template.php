<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$dir = $this->GetFolder();
$v   = '?v='.time();
?>
<link rel="stylesheet" href="<?= $dir ?>/style.css<?= $v ?>">
<script defer src="<?= $dir ?>/script.js<?= $v ?>"></script>
<div class="basket-layout">
    <section class="basket-left">
        <div class="basket-scroll">
            <?php foreach ($arResult['STAGES'] as $st): ?>
                <h3 class="stage-h"><?= htmlspecialcharsbx($st['NAME']) ?></h3>
                <?php foreach ($st['ITEMS'] as $svc): ?>
                    <article class="svc-card <?= $svc['MANDATORY'] ? 'svc-lock' : '' ?>" data-id="<?= $svc['ID'] ?>">
                        <header class="svc-head">
                            <span class="svc-tag"><?= htmlspecialcharsbx($st['NAME']) ?></span>
                            <span class="svc-name"><?= htmlspecialcharsbx($svc['NAME']) ?></span>
                            <?php if ($svc['MANDATORY']): ?>
                                <span class="svc-badge svc-blue">Обязательно</span>
                            <?php else: ?>
                                <button class="svc-badge svc-red svc-remove" data-id="<?= $svc['ID'] ?>">Удалить</button>
                            <?php endif; ?>
                            <span class="svc-sum">
                                <span class="svc-sum-val"><?= number_format($svc['SUM'], 0, '', ' ') ?></span> ₽
                            </span>
                            <button class="svc-toggle">Читать подробнее</button>
                        </header>
                        <div class="svc-body">
                            <?php if (!empty($svc['MIN_CRITERIA'])): ?>
                                <div class="svc-criteria">
                                    <strong>Минимальные критерии:</strong>
                                    <p><?= nl2br(htmlspecialcharsbx($svc['MIN_CRITERIA'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($svc['COMMENT'])): ?>
                                <div class="svc-comment">
                                    <strong>Комментарий:</strong>
                                    <p><?= nl2br(htmlspecialcharsbx($svc['COMMENT'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <table class="role-tbl">
                                <thead>
                                    <tr>
                                        <th>Роль</th>
                                        <th>Ставка ₽/ч</th>
                                        <th>Часы</th>
                                        <th>Стоимость, ₽</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($svc['ROLES'] as $rid => $r): ?>
                                        <tr class="role-row" 
                                            data-service="<?= $svc['ID'] ?>" 
                                            data-role="<?= $rid ?>" 
                                            data-rate="<?= $r['RATE'] ?>">
                                            <td><?= htmlspecialcharsbx($r['ROLE_NAME']) ?></td>
                                            <td><?= number_format($r['RATE'], 2, '.', '') ?></td>
                                            <td><input class="rl-hours" type="number" min="0" step="1" value="<?= $r['HOURS'] ?>"></td>
                                            <td class="rl-cost"><?= number_format($r['COST'], 0, '', ' ') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <footer class="basket-footer">
            <button class="btn grey" disabled>Применить черновик</button>
            <button class="btn green" disabled>Сохранить как черновик</button>
            <button class="btn blue" disabled>Выгрузить отчёт</button>
            <button class="btn-clear" id="btn-clear">🗑 Очистить корзину</button>
            <div class="basket-total">
                Общая стоимость: <span id="grand-val"><?= number_format($arResult['GRAND_TOTAL'], 0, '', ' ') ?></span> ₽
            </div>
        </footer>
    </section>
    <aside class="basket-right">
        <h3>Описание&nbsp;услуги</h3>
        <div id="detail-box" class="detail-box">
            <p class="hint">Выберите «Читать подробнее» слева</p>
        </div>
    </aside>
</div>