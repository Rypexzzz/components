<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

$IB_SERVICES = 75;
$HL_ROLES    = 70;
$PROP_MIN_CRITERIA = 397;
$PROP_COMMENT = 398;

/* ---- HL Roles ------------------------------------------------------- */
$rolesById = [];
$hi = HLBT::getById($HL_ROLES)->fetch();
$e  = HLBT::compileEntity($hi);
$res = $e->getDataClass()::getList(['select' => ['ID', 'UF_NAME', 'UF_RATE']]);
while ($r = $res->fetch()) {
    $rate = str_replace([' ', ','], ['', '.'], (string)$r['UF_RATE']);
    $rolesById[$r['ID']] = [
        'NAME' => $r['UF_NAME'],
        'RATE' => (float)$rate
    ];
}

/* ---- AJAX ----------------------------------------------------------- */
$req = Context::getCurrent()->getRequest();
if ($req->isPost() && $req['ajax'] === 'Y') {
    $sid = (int)$req['serviceId'];
    switch ($req['action']) {
        case 'updateHours':
            if (isset($_SESSION['SERVICE_CART'][$sid]['ROLES'][(int)$req['roleId']])) {
                $_SESSION['SERVICE_CART'][$sid]['ROLES'][(int)$req['roleId']]['HOURS'] = max(0, (float)$req['hours']);
            }
            break;
        case 'removeService':
            if (!empty($_SESSION['SERVICE_CART'][$sid]) && empty($_SESSION['SERVICE_CART'][$sid]['MANDATORY'])) {
                unset($_SESSION['SERVICE_CART'][$sid]);
            }
            break;
        case 'clearCart':
            foreach ($_SESSION['SERVICE_CART'] as $id => $svc) {
                if (empty($svc['MANDATORY'])) {
                    unset($_SESSION['SERVICE_CART'][$id]);
                }
            }
            break;
    }
    $APPLICATION->RestartBuffer(); 
    echo '{"success":1}'; 
    die();
}

/* ---- рассчитываем суммы -------------------------------------------- */
$grand = 0;
foreach ($_SESSION['SERVICE_CART'] ?? [] as $sid => &$svc) {
    $svc['SUM'] = 0;
    foreach ($svc['ROLES'] as $rid => &$r) {
        $r['RATE'] = $rolesById[$rid]['RATE'] ?? 0;
        $r['COST'] = $r['RATE'] * ($r['HOURS'] ?? 0);
        $svc['SUM'] += $r['COST'];
    }
    unset($r);
    $grand += $svc['SUM'];
}
unset($svc);

/* ---- этапы ---------------------------------------------------------- */
$stageName = [];
$secRS = CIBlockSection::GetList(
    ['SORT' => 'ASC'], 
    ['IBLOCK_ID' => $IB_SERVICES, 'ACTIVE' => 'Y'],
    false,
    ['ID', 'NAME']
);
while ($s = $secRS->Fetch()) {
    $stageName[$s['ID']] = $s['NAME'];
}

/* ---- группируем по разделам ----------------------------------------- */
$byStage = [];
$elementIds = array_keys($_SESSION['SERVICE_CART'] ?? []);
$elementsProps = [];

if (!empty($elementIds)) {
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $IB_SERVICES, 'ID' => $elementIds],
        false,
        false,
        ['ID', 'PROPERTY_'.$PROP_MIN_CRITERIA, 'PROPERTY_'.$PROP_COMMENT]
    );
    while ($el = $res->Fetch()) {
        $elementsProps[$el['ID']] = [
            'MIN_CRITERIA' => is_array($el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE']) 
                ? implode("\n", $el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE'])
                : ($el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE'] ?? ''),
            'COMMENT' => is_array($el['PROPERTY_'.$PROP_COMMENT.'_VALUE']) 
                ? implode("\n", $el['PROPERTY_'.$PROP_COMMENT.'_VALUE'])
                : ($el['PROPERTY_'.$PROP_COMMENT.'_VALUE'] ?? ''),
        ];
    }
}

foreach ($_SESSION['SERVICE_CART'] ?? [] as $sid => $svc) {
    $sectionRes = CIBlockElement::GetElementGroups($sid, true, ['ID', 'NAME'], ['nTopCount' => 1]);
    $section = $sectionRes->Fetch();
    if (!$section) continue;
    $stID = (int)$section['ID'];
    $stName = $section['NAME'] ?? $stageName[$stID] ?? 'Неизвестный этап';
    if (!isset($byStage[$stID])) {
        $byStage[$stID] = [
            'NAME' => $stName,
            'ITEMS' => []
        ];
    }
    $item = ['ID' => $sid] + $svc;
    if (isset($elementsProps[$sid])) {
        $item['MIN_CRITERIA'] = $elementsProps[$sid]['MIN_CRITERIA'];
        $item['COMMENT'] = $elementsProps[$sid]['COMMENT'];
    }
    $byStage[$stID]['ITEMS'][] = $item;
}

ksort($byStage);
$arResult = [
    'STAGES' => $byStage,
    'GRAND_TOTAL' => $grand
];
$this->includeComponentTemplate();