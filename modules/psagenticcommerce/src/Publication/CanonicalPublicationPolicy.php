<?php
namespace PrestaShopAgenticCommerce\Publication;
use PrestaShopAgenticCommerce\Domain\CanonicalProductDTO;
if (!defined('_PS_VERSION_')) { exit; }
final class CanonicalPublicationPolicy
{
    public function prepare(CanonicalProductDTO $dto): array
    {
        $data=$dto->toArray();
        if (($data['context']['pricing_context']??null)!=='public_catalog_tax_included') { throw new \DomainException('Canonical product is not built with a public catalog pricing context.'); }
        $evidence=is_array($data['evidence']??null)?$data['evidence']:[];
        $this->assertEvidenceForSpecs($data['verified_specs']??[],$evidence,'verified');
        $this->assertEvidenceForSpecs($data['declared_specs']??[],$evidence,'declared');
        $this->assertEvidenceForSpecs($data['derived_properties']??[],$evidence,'derived');
        $data['evidence']=$this->publicEvidence($evidence);
        return $data;
    }
    private function assertEvidenceForSpecs($specs,array $evidence,string $class):void
    {
        if(!is_array($specs)){throw new \DomainException('Canonical spec block must be an object.');}
        foreach(array_keys($specs) as $propertyKey){$records=is_array($evidence[$propertyKey]??null)?$evidence[$propertyKey]:[];$valid=false;foreach($records as $record){if(is_array($record)&&($record['evidence_class']??null)===$class&&($record['status']??null)==='active'&&($record['is_public']??false)===true){$valid=true;break;}}if(!$valid){throw new \DomainException(sprintf('Property %s has no active public %s evidence.',$propertyKey,$class));}}
    }
    private function publicEvidence(array $evidence):array
    {
        $result=[];foreach($evidence as $key=>$records){if(!is_array($records)){continue;}foreach($records as $record){if(!is_array($record)||($record['status']??null)!=='active'||($record['is_public']??false)!==true){continue;}unset($record['notes']);$result[$key][]=$record;}}return $result;
    }
}
