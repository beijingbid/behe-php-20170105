<?php

class AdvertiserQueryQualificationRequest {
    private $apiParams = array();
    private $entityName = 'Advertiser';
    public function getApiMethodName()
    {
        return "hzeng.advertiser.queryQualification";
    }

    public function addEntity($entity)
    {
        $this->apiParams = array('advertiserIds' => $entity);
        return $this->apiParams;
    }

    public function getApiParams()
    {
        return $this->apiParams;
    }

    public function check()
    {
        $schema = json_decode('
        {
            "type" : "object",
            "properties" : {
                "advertiserIds" : {
                    "type"     : "array",
                    "required" : true,
                    "items"    : {
                        "type"     : "integer",
                        "minItems" : 1,
                        "maxItems" : 100
                    }
                }
            }
        }');
        $validator = new JsonValidator($schema, $this->entityName);
        $validator->validate($this->apiParams);
    }
} 