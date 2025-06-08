<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;

class ServiceCartComponent extends CBitrixComponent implements Controllerable
{
    private const IBLOCK_ID = 75;
    private const HL_ROLES_ID = 70;
    private const HL_SOSTAV_ID = 71;

    private array $roles = [];

    public function configureActions(): array
    {
        return [
            'addService'    => ['prefilters' => [new ActionFilter\Csrf()]],
            'removeService' => ['prefilters' => [new ActionFilter\Csrf()]],
            'updateHours'   => ['prefilters' => [new ActionFilter\Csrf()]],
        ];
    }

    public function addServiceAction(int $serviceId): array
    {
        $_SESSION['SERVICE_CART'][$serviceId] ??= [];
        return ['status'=>'ok'];
    }
    public function removeServiceAction(int $serviceId): array
    {
        unset($_SESSION['SERVICE_CART'][$serviceId]);
        return ['status'=>'ok'];
    }
    public function updateHoursAction(int $serviceId,int $roleId,float $hours): array
    {
        if ($hours < 0) $hours = 0;
        $_SESSION['SERVICE_CART'][$serviceId]['HOURS'][$roleId] = $hours;
        return ['status'=>'ok'];
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('highloadblock')) return;
        $this->ensureRequiredServices();
        $this->roles = $this->loadRoles();
        $this->arResult['ITEMS'] = $this->prepareCartItems();
        $this->includeComponentTemplate();
    }

    private function ensureRequiredServices(): void
    {
        $rs = CIBlockElement::GetList([], ['IBLOCK_ID'=>self::IBLOCK_ID,'ACTIVE'=>'Y'], false, false, ['ID','PROPERTY_IS_REQUIRED']);
        while($e = $rs->Fetch())
            if($e['PROPERTY_IS_REQUIRED_VALUE']=='Y')
                $_SESSION['SERVICE_CART'][$e['ID']] ??= [];
    }

    private function loadRoles(): array
    {
        $hl=HighloadBlockTable::getById(self::HL_ROLES_ID)->fetch();
        $dc=HighloadBlockTable::compileEntity($hl)->getDataClass();
        $out=[];$rs=$dc::getList(['select'=>['ID','UF_NAME','UF_RATE']]);
        while($r=$rs->fetch()) $out[$r['ID']]=['NAME'=>$r['UF_NAME'],'RATE'=>(float)$r['UF_RATE']];
        return $out;
    }

    private function prepareCartItems(): array
    {
        $serviceIds = array_map('intval', array_keys($_SESSION['SERVICE_CART']??[]));
        if(!$serviceIds) return [];
        $services=[];
        $rsEl=CIBlockElement::GetList([],['IBLOCK_ID'=>self::IBLOCK_ID,'ID'=>$serviceIds],false,false,['ID','NAME','PROPERTY_IS_REQUIRED']);
        while($e=$rsEl->GetNext()){
            $sid=(int)$e['ID'];
            $services[$sid]=['ID'=>$sid,'NAME'=>$e['NAME'],'IS_REQUIRED'=>$e['PROPERTY_IS_REQUIRED_VALUE']=='Y','ROLES'=>[],'SUM'=>0];
        }
        if(!$services) return [];
        $hl=HighloadBlockTable::getById(self::HL_SOSTAV_ID)->fetch();
        $dc=HighloadBlockTable::compileEntity($hl)->getDataClass();
        $rs=$dc::getList(['filter'=>['UF_SERVICE'=>$serviceIds],'select'=>['UF_SERVICE','UF_ROLE','UF_HOURS']]);
        while($row=$rs->fetch()){
            $sid=$row['UF_SERVICE'];$rid=$row['UF_ROLE'];
            if(!isset($services[$sid])) continue;
            $role=$this->roles[$rid]??null;if(!$role) continue;
            $hours=$_SESSION['SERVICE_CART'][$sid]['HOURS'][$rid]??(float)$row['UF_HOURS'];
            $cost=$hours*$role['RATE'];
            $services[$sid]['ROLES'][$rid]=['ROLE_NAME'=>$role['NAME'],'RATE'=>$role['RATE'],'HOURS'=>$hours,'COST'=>$cost];
            $services[$sid]['SUM']+=$cost;
        }
        return $services;
    }
}