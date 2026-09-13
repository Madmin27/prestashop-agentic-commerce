<?php
namespace PrestaShopAgenticCommerce\Repository;
if (!defined('_PS_VERSION_')) { exit; }
final class AiMetaRepository
{
    public function find(int $idShop,int $idProduct,int $idProductAttribute):?array
    {
        $q=new \DbQuery();
        $q->select('*')->from('agentic_product_meta')->where('id_shop='.(int)$idShop)->where('id_product='.(int)$idProduct)->where('id_product_attribute='.(int)$idProductAttribute);
        $row=\Db::getInstance()->getRow($q); if(!$row){return null;}
        foreach(['verified_specs_json'=>'verified_specs','declared_specs_json'=>'declared_specs','suitability_json'=>'suitability','derived_specs_json'=>'derived_specs'] as $from=>$to){$row[$to]=$this->decode($row[$from]??null);}
        return $row;
    }
    private function decode($value):array{if(!is_string($value)||trim($value)===''){return [];} $d=json_decode($value,true); return is_array($d)?$d:[];}
}
