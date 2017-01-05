<?php

class AdvertiserGetAllRequest {
    private $apiParams = array();
    private $entityName = 'Advertiser';
    public function getApiMethodName()
    {
        return "hzeng.advertiser.getAll";
    }

    public function addEntity($entity)
    {
        $this->apiParams = $entity;
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
                "startDate" : {
                    "type"     : "string",
                    "required" : true,
                    "format"   : "date"
                },
                "endDate" : {
                    "type"     : "string",
                    "required" : true,
                    "format"   : "date"
                }
            }
        }');
        $validator = new JsonValidator($schema, $this->entityName);
        $validator->validate($this->apiParams);
    }
} 