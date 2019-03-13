<?php

namespace Ziffity\Procurement\Model;

/**
 * Description of PriceUpdater
 *
 * @author Daiva
 */
class PriceUpdater
{
    protected $resource;

    public function __construct(
    \Magento\Framework\App\ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    protected function getAttributeId($attribute_code = 'price')
    {
        $connection     = $this->getConnection();
        $sql            = "SELECT attribute_id
                FROM ".$connection->getTableName('eav_attribute')."
            WHERE
                entity_type_id = ?
                AND attribute_code = ?";
        $entity_type_id = $this->getEntityTypeId();
        return $connection->fetchOne($sql,
                array($entity_type_id, $attribute_code));
    }

    protected function getEntityTypeId($entity_type_code = 'catalog_product')
    {
        $connection = $this->getConnection();
        $sql        = "SELECT entity_type_id FROM ".$connection->getTableName('eav_entity_type')." WHERE entity_type_code = ?";
        return $connection->fetchOne($sql, array($entity_type_code));
    }

    protected function getConnection()
    {
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        return $connection;
    }

    protected function getSpecialPrice($productId, $attributeId)
    {
        $connection = $this->getConnection();
        $sql        = "SELECT * FROM ".$connection->getTableName('catalog_product_entity_decimal')." cped
            WHERE  cped.attribute_id = ?
            AND cped.entity_id = ?";
        return $connection->fetchAll($sql, array($attributeId, $productId));

    }

    protected function getSkuFromid($productId)
    {
        $connection = $this->getConnection();
        $sql        = "SELECT sku FROM ".$connection->getTableName('catalog_product_entity')." WHERE entity_id = ?";
        return $connection->fetchOne($sql, array($productId));
    }

    public function updatePrice($productId, $offerSlab)
    {
        $priceAttributeId               = $this->getAttributeId();
        $specialPriceAttributeId        = $this->getAttributeId('special_price');
        $tolerancePercentageAttributeId = $this->getAttributeId('tolerance_percentage');
        $connection                     = $this->getConnection();

        $sql           = "SELECT cped.value FROM ".$connection->getTableName('catalog_product_entity_decimal')." cped
            WHERE  cped.attribute_id = ?
            AND cped.entity_id = ?";
        $price         = $connection->fetchAll($sql,
            array($priceAttributeId, $productId));
        $originalPrice = $price[0]['value'];

        $calculatedPrice = $originalPrice - ($originalPrice * $offerSlab->getListingPricePercnetage()
            / 100);

        $tolerancePercentage = $offerSlab->getOfferTolarancePercentage();

        $sql = "UPDATE ".$connection->getTableName('catalog_product_entity_varchar')." cped
                SET  cped.value = ?
            WHERE  cped.attribute_id = ?
            AND cped.entity_id = ?";
        $connection->query($sql,
            array($tolerancePercentage, $tolerancePercentageAttributeId, $productId));
        $sku = $this->getSkuFromid($productId);
        $valuesSpecial=$this->getSpecialPrice($productId, $specialPriceAttributeId);
        $sepicalCount=count($valuesSpecial);
        
            if($sepicalCount>0){
                $sepicalPrice=$valuesSpecial[0]['value'];
        if ($calculatedPrice != $sepicalPrice) {
          
                $sql = "UPDATE ".$connection->getTableName('catalog_product_entity_decimal')." cped
                SET  cped.value = ?
            WHERE  cped.attribute_id = ?
            AND cped.entity_id = ?";
                $connection->query($sql,
                    array($calculatedPrice, $specialPriceAttributeId, $productId));

                if($sepicalPrice)
                 return [$sku, $sepicalPrice, $calculatedPrice];
                else
                   return [$sku, $originalPrice, $calculatedPrice];
            }
            
        }else {
            if($calculatedPrice!=$originalPrice){
                $sql = "INSERT INTO ".$connection->getTableName('catalog_product_entity_decimal')."
              (value,attribute_id,entity_id,store_id) VALUES (?,?,?,?)";
                $connection->query($sql,
                    array($calculatedPrice, $specialPriceAttributeId, $productId,
                    0));
                return [$sku, $originalPrice, $calculatedPrice];
                }
        }
            
        

       
    }
      public function checkPriceChange($productId, $offerSlab)
    {
        $priceAttributeId               = $this->getAttributeId();
        $specialPriceAttributeId        = $this->getAttributeId('special_price');
        $tolerancePercentageAttributeId = $this->getAttributeId('tolerance_percentage');
        $connection                     = $this->getConnection();

        $sql           = "SELECT cped.value FROM ".$connection->getTableName('catalog_product_entity_decimal')." cped
            WHERE  cped.attribute_id = ?
            AND cped.entity_id = ?";
        $price         = $connection->fetchAll($sql,
            array($priceAttributeId, $productId));
        $originalPrice = $price[0]['value'];

        $calculatedPrice = $originalPrice - ($originalPrice * $offerSlab->getListingPricePercnetage()
            / 100);

      
        $sku = $this->getSkuFromid($productId);
        $valuesSpecial=$this->getSpecialPrice($productId, $specialPriceAttributeId);
        $sepicalCount=count($valuesSpecial);
            if($sepicalCount>0){
                $sepicalPrice=$valuesSpecial[0]['value'];
        if ($calculatedPrice != $sepicalPrice) {
                if($sepicalPrice)
                 return [$sku, $sepicalPrice, $calculatedPrice];
                else
                   return [$sku, $originalPrice, $calculatedPrice];
            } }else {
            if($calculatedPrice!=$originalPrice){
      
                return [$sku, $originalPrice, $calculatedPrice];
                }
        }
            



    }
}
