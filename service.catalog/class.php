<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

class ServiceCatalogComponent extends CBitrixComponent
{
    private const IBLOCK_ID    = 75;
    private const HL_DEPT_ID   = 69;
    private const HL_ROLES_ID  = 70;
    private const HL_SOSTAV_ID = 71;
    private const STAGE_IDS    = [2203,2204,2205,2206,2207,2208,2209,2210];

    private array $departments = [];
    private array $roles       = [];
    private array $stages      = [];

    public function executeComponent()
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('highloadblock'))
            return;

        $this->departments = $this->loadDepartments();
        $this->roles       = $this->loadRoles();
        $this->loadStages();

        $this->arResult = [
            'STAGES' => $this->stages,
            'MAP'    => $this->loadServices(),
        ];
        $this->includeComponentTemplate();
    }

    /* ───── справочники ───── */
    private function loadDepartments(): array
    {
        $hl = HighloadBlockTable::getById(self::HL_DEPT_ID)->fetch();
        $dc = HighloadBlockTable::compileEntity($hl)->getDataClass();
        $out=[];
        $rs=$dc::getList(['select'=>['ID','UF_NAME']]);
        while($r=$rs->fetch()) $out[$r['ID']]=$r['UF_NAME'];
        return $out;
    }

    private function loadRoles(): array
    {
        $hl = HighloadBlockTable::getById(self::HL_ROLES_ID)->fetch();
        $dc = HighloadBlockTable::compileEntity($hl)->getDataClass();
        $out=[];
        $rs=$dc::getList(['select'=>['ID','UF_NAME','UF_RATE','UF_DEPARTMENT']]);
        while($r=$rs->fetch()){
            $dept=$this->departments[(int)$r['UF_DEPARTMENT']]??'—';
            $out[$r['ID']]=[
                'NAME'=>$r['UF_NAME'],
                'RATE'=>(float)$r['UF_RATE'],
                'DEPT_NAME'=>$dept,
            ];
        }
        return $out;
    }

    private function loadStages(): void
    {
        $rs=CIBlockSection::GetList([],[
            'IBLOCK_ID'=>self::IBLOCK_ID,
            'ID'=>self::STAGE_IDS,
            'ACTIVE'=>'Y'
        ],false,['ID','NAME']);
        while($row=$rs->Fetch()) $this->stages[$row['ID']]=$row['NAME'];
    }

    /* ───── helper: берём строку из свойства при любом формате ───── */
    private function valToString($val): string
    {
        if (is_array($val)){
            if(isset($val['TEXT']))          return trim($val['TEXT']);
            if(isset($val['VALUE']))         return trim($val['VALUE']);
            return trim(implode(', ', array_filter($val,'strlen')));
        }
        return trim((string)$val);
    }

    /* ───── услуги ───── */
    private function loadServices(): array
    {
        $map=[]; foreach(self::STAGE_IDS as $sid) $map[$sid]=['NAME'=>$this->stages[$sid]??'', 'ITEMS'=>[]];

        $filter=[
            'IBLOCK_ID'=>self::IBLOCK_ID,
            'ACTIVE'=>'Y',
            'PROPERTY_IBLOCK_SECTION_ID'=>self::STAGE_IDS,
        ];
        $select=['ID','NAME','PROPERTY_IBLOCK_SECTION_ID'];
        $services=[]; $svcIds=[];

        $rs=CIBlockElement::GetList([], $filter, false, false, $select);
        while($ob=$rs->GetNextElement()){
            $f=$ob->GetFields();
            $p=$ob->GetProperties();

            $id=(int)$f['ID']; $stage=(int)$f['PROPERTY_IBLOCK_SECTION_ID_VALUE'];
            if(!$stage||!isset($map[$stage])) continue;

            $services[$id]=[
                'ID'=>$id,
                'NAME'=>$f['NAME'],
                'STAGE_ID'=>$stage,
                'IS_REQUIRED'=>($p['IS_REQUIRED']['VALUE']==='Y'),
                'MIN_CRITERIA'=>$this->valToString($p['MIN_CRITERIA']['VALUE']),
                'COMMENT'     =>$this->valToString($p['COMMENT']['VALUE']),
                'ROLES'=>[],
                'STD_COST'=>0,
            ];
            $svcIds[]=$id;
        }
        if(!$svcIds) return $map;

        $hl=HighloadBlockTable::getById(self::HL_SOSTAV_ID)->fetch();
        $dc=HighloadBlockTable::compileEntity($hl)->getDataClass();
        $rs=$dc::getList([
            'filter'=>['UF_SERVICE'=>$svcIds],
            'select'=>['UF_SERVICE','UF_ROLE','UF_HOURS']
        ]);
        while($r=$rs->fetch()){
            $sid=$r['UF_SERVICE']; $rid=$r['UF_ROLE'];
            if(!isset($services[$sid],$this->roles[$rid])) continue;
            $h=(float)$r['UF_HOURS']; $rate=$this->roles[$rid]['RATE'];
            $services[$sid]['ROLES'][$rid]=[
                'DEPT_NAME'=>$this->roles[$rid]['DEPT_NAME'],
                'ROLE_NAME'=>$this->roles[$rid]['NAME'],
                'HOURS'=>$h,
            ];
            $services[$sid]['STD_COST'] += $h*$rate;
        }

        foreach($services as $svc) $map[$svc['STAGE_ID']]['ITEMS'][]=$svc;
        return $map;
    }

    public function getRoles(): array             { return $this->roles; }
    public function getStageName(int $id): string { return $this->stages[$id]??''; }
}
