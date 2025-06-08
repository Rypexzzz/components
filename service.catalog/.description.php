<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

return [
    'NAME'        => 'Каталог услуг ЦК ПР',
    'DESCRIPTION' => 'Выводит список услуг по этапам с фильтром и поиском, AJAX‑добавление в корзину',
    'PATH'        => [
        'ID'   => 'mycompany',
        'NAME' => 'MyCompany',
    ],
    'FRAME_MODE'       => true,
    'CACHE_PATH'       => 'Y',
    'COMPLEX'          => 'N',
];