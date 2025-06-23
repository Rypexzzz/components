<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

/* ── ID инфоблоков / HL ────────────────────────────────────────── */
$IB_SERVICES = 75;
$HL_DEPTS    = 69;
$HL_ROLES    = 70;
$HL_SOSTAV   = 71;

/* ID свойств элементов */
$PROP_IS_REQUIRED  = 399;  // Обязательная услуга
$PROP_MIN_CRITERIA = 397;  // Мин. критерии
$PROP_COMMENT      = 398;  // Комментарий

/* ── helpers: HL-справочники ───────────────────────────────────── */
function hlAll(int $id, array $select=['*']): array {
  if (!$hl = HLBT::getById($id)->fetch()) return [];
  $ent = HLBT::compileEntity($hl);
  return $ent->getDataClass()::getList(['select'=>$select])->fetchAll();
}

/* Departments: ID → NAME */
$DEPTS = [];
foreach(hlAll($HL_DEPTS, ['ID','UF_NAME']) as $d)
  $DEPTS[$d['ID']] = $d['UF_NAME'];

/* Roles */
$ROLES = [];
foreach(hlAll($HL_ROLES, ['ID','UF_NAME','UF_RATE','UF_DEPARTMENT']) as $r)
  $ROLES[$r['ID']] = [
    'NAME' => $r['UF_NAME'],
    'RATE' => (float)$r['UF_RATE'],
    'DEPT_ID' => (int)$r['UF_DEPARTMENT'],
  ];

/* ── сумма корзины ─────────────────────────────────────────────── */
$cartTotal = static function(): float {
  $s = 0;
  foreach($_SESSION['SERVICE_CART'] ?? [] as $svc)
    foreach($svc['ROLES'] as $r) $s += $r['COST'];
  return $s;
};

/* ── состав услуги (roles + стоимость) ─────────────────────────── */
function buildComp(int $svcId, int $hl, array $roles, array $depts): array {
  static $mem = [];
  if (isset($mem[$svcId])) return $mem[$svcId];
  
  $cls = HLBT::compileEntity(HLBT::getById($hl)->fetch())->getDataClass();
  $rs  = $cls::getList([
    'filter' => ['UF_SERVICE' => $svcId],
    'select' => ['UF_ROLE', 'UF_HOURS']
  ]);

  $out = [];
  $sum = 0;

  while ($row = $rs->fetch()) {
    $rid   = $row['UF_ROLE'];
    $hours = (float)$row['UF_HOURS'];
    $rate  = $roles[$rid]['RATE'] ?? 0;
    $cost  = $hours * $rate;
    
    $out[$rid] = [
      'ROLE_ID' => $rid,
      'ROLE_NAME' => $roles[$rid]['NAME'] ?? '',
      'DEPT_NAME' => $depts[$roles[$rid]['DEPT_ID']] ?? '',
      'HOURS' => $hours,
      'COST' => $cost
    ];
    $sum += $cost;
  }

  return $mem[$svcId] = [$out, $sum];
}

/* ── AJAX add / remove ─────────────────────────────────────────── */
$req = Context::getCurrent()->getRequest();
if ($req->isPost() && $req['ajax'] == 'Y') {
  $id  = (int)$req['serviceId'];
  $act = $req['action'];

  if (!$id) {
    $APPLICATION->RestartBuffer();
    echo '{"success":0}';
    die();
  }

  if ($act === 'addService') {
    if (!isset($_SESSION['SERVICE_CART'][$id])) {
      $el = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $IB_SERVICES, 'ID' => $id],
        false, false, ['ID', 'NAME']
      )->Fetch();

      if ($el) {
        [$c] = buildComp($id, $HL_SOSTAV, $ROLES, $DEPTS);
        $_SESSION['SERVICE_CART'][$id] = [
          'NAME' => $el['NAME'],
          'MANDATORY' => false,
          'ROLES' => $c
        ];
      }
    }

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    echo json_encode(['success' => 1, 'total' => $cartTotal()]);
    die();
  }

  if ($act === 'removeService') {
    unset($_SESSION['SERVICE_CART'][$id]);
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    echo json_encode(['success' => 1, 'total' => $cartTotal()]);
    die();
  }

  $APPLICATION->RestartBuffer();
  echo '{"success":0}';
  die();
}

/* ── GET-фильтры ──────────────────────────────────────────────── */
$q  = trim((string)($_GET['q'] ?? ''));
$rf = array_filter(array_map('intval', (array)($_GET['roles'] ?? [])));
$idsByRole = null;

if ($rf) {
  $ids = [];
  foreach(hlAll($HL_SOSTAV, ['UF_SERVICE','UF_ROLE']) as $r)
    if (in_array($r['UF_ROLE'], $rf, true)) $ids[$r['UF_SERVICE']] = true;
  $idsByRole = array_keys($ids);
  if (!$idsByRole) {
    $arResult = ['STAGES' => [], 'MAP' => [], 'ROLES' => $ROLES, 'CURRENT_TOTAL' => 0];
    $this->includeComponentTemplate();
    return;
  }
}

/* ── формируем STAGES + MAP ───────────────────────────────────── */
$arResult = ['STAGES' => [], 'MAP' => [], 'ROLES' => $ROLES];
$secRS = CIBlockSection::GetList(
  ['SORT' => 'ASC'],
  ['IBLOCK_ID' => $IB_SERVICES, 'ACTIVE' => 'Y'],
  false,
  ['ID', 'NAME']
);

// Инициализируем корзину перед обработкой элементов
if (!isset($_SESSION['SERVICE_CART'])) {
  $_SESSION['SERVICE_CART'] = [];
}

while ($sec = $secRS->Fetch()) {
  $sid = (int)$sec['ID'];
  $arResult['STAGES'][$sid] = $sec['NAME'];
  $filter = ['IBLOCK_ID' => $IB_SERVICES, 'SECTION_ID' => $sid, 'ACTIVE' => 'Y'];
  if ($q) $filter['%NAME'] = $q;
  if ($idsByRole) $filter['ID'] = $idsByRole;

  $items = [];
  // ЗАПРАШИВАЕМ СВОЙСТВА ПО ИХ ID
  $elRes = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'NAME' => 'ASC'],
    $filter,
    false,
    false,
    [
      'ID',
      'NAME',
      'PROPERTY_'.$PROP_IS_REQUIRED,   // Обязательная услуга
      'PROPERTY_'.$PROP_MIN_CRITERIA,  // Мин. критерии
      'PROPERTY_'.$PROP_COMMENT        // Комментарий
    ]
  );

  while ($el = $elRes->Fetch()) {
    $pid = (int)$el['ID'];
    // ПОЛУЧАЕМ ЗНАЧЕНИЯ СВОЙСТВ ПО ID
    $mandatory = ($el['PROPERTY_'.$PROP_IS_REQUIRED.'_VALUE'] === 'Y');
    $min = is_array($el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE']) 
      ? implode("\n", array_filter($el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE'], 'strlen'))
      : ($el['PROPERTY_'.$PROP_MIN_CRITERIA.'_VALUE'] ?? '');
    $com = is_array($el['PROPERTY_'.$PROP_COMMENT.'_VALUE']) 
      ? implode("\n", array_filter($el['PROPERTY_'.$PROP_COMMENT.'_VALUE'], 'strlen'))
      : ($el['PROPERTY_'.$PROP_COMMENT.'_VALUE'] ?? '');

    /* состав + стоимость */
    [$comp, $std] = buildComp($pid, $HL_SOSTAV, $ROLES, $DEPTS);
    $items[] = [
      'ID' => $pid,
      'NAME' => $el['NAME'],
      'IS_REQUIRED' => $mandatory,
      'MIN_CRITERIA' => $min,
      'COMMENT' => $com,
      'ROLES' => $comp,
      'STD_COST' => $std,
    ];

    /* автоматически кладём обязательные в корзину */
    if ($mandatory && !isset($_SESSION['SERVICE_CART'][$pid])) {
      $_SESSION['SERVICE_CART'][$pid] = [
        'NAME' => $el['NAME'],
        'MANDATORY' => true,
        'ROLES' => $comp
      ];
    }
  }

  $arResult['MAP'][$sid] = ['NAME' => $sec['NAME'], 'ITEMS' => $items];
}

/* ── итого корзины ───────────────────────────────────────────── */
$arResult['CURRENT_TOTAL'] = $cartTotal();
$this->includeComponentTemplate();