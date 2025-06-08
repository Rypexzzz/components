<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

return [
    'NAME'        => 'Корзина услуг ЦК ПР',
    'DESCRIPTION' => 'Отображает выбранные услуги, позволяет менять часы и рассчитывает итоговую стоимость',
    'PATH'        => [
        'ID'   => 'mycompany',
        'NAME' => 'MyCompany',
    ],
    'FRAME_MODE'       => true,
    'CACHE_PATH'       => 'N',
    'COMPLEX'          => 'N',
];