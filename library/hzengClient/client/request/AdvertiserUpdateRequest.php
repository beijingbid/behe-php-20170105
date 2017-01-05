<?php

class AdvertiserUpdateRequest {
    private $apiParams = array();
    private $entityName = 'Advertiser';
    public function getApiMethodName()
    {
        return "hzeng.advertiser.update";
    }

    public function addEntity($entity)
    {
        array_push($this->apiParams, $entity);
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
            "type"  : "array",
            "items" : {
                "type" : "object",
                "properties" : {
                    "advertiserId" : {
                        "type"     : "integer",
                        "required" : true
                    },
                    "advertiserLiteName" : {
                        "type"      : "string",
                        "maxLength" : 200
                    },
                    "advertiserName" : {
                        "type"      : "string",
                        "minLength" : 3,
                        "maxLength" : 200
                    },
                    "siteName" : {
                        "type"      : "string",
                        "maxLength" : 100
                    },
                    "siteUrl" : {
                        "type"      : "string",
                        "maxLength" : 512,
                        "format"    : "uri"
                    },
                    "telephone" : {
                        "type"      : "string",
                        "maxLength" : 100
                    },
                    "address" : {
                        "type"      : "string",
                        "maxLength" : 100
                    }
                }
            },
            "minItems" : 1,
            "maxItems" : 5
        }');
        $validator = new JsonValidator($schema, $this->entityName);
        $validator->validate($this->apiParams);
    }
} 