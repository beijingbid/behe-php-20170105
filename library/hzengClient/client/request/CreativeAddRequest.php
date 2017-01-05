<?php

class CreativeAddRequest {
    private $apiParams = array();
    private $entityName = 'Creative';
    public function getApiMethodName()
    {
        return "hzeng.creative.add";
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
                    "creativeId" : {
                        "type"     : "integer",
                        "required" : true
                    },
                    "type" : {
                        "type"      : "integer",
                        "required"  : true
                    },
                    "creativeUrl" : {
                        "type"      : "string",
                        "maxLength" : 2048,
                        "format"    : "uri"
                    },
                    "binaryData" : {
                        "type"      : "string",
                        "maxLength" : 100
                    },
                    "targetUrl" : {
                        "type"      : "string",
                        "required"  : true,
                        "maxLength" : 1024,
                        "format"    : "uri"
                    },
                    "landingPage" : {
                        "type"      : "string",
                        "required"  : true,
                        "maxLength" : 2048
                    },
                    "monitorUrls" : {
                        "type"      : "array",
                        "required"  : true,
                        "items"     : {
                            "type"      : "string",
                            "maxLength" : 1024
                        },
                        "minItems" : 1 ,
                        "maxItems" : 3
                    },
                    "height" : {
                        "type"     : "integer",
                        "required" : true
                    },
                    "width" : {
                        "type"     : "integer",
                        "required" : true
                    },
                    "creativeTradeId" : {
                        "type"     : "integer",
                        "required" : true
                    },
                    "advertiserId" : {
                        "type"     : "integer",
                        "required" : true
                    }
                }
            },
            "minItems" : 1,
            "maxItems" : 10
        }');
        $validator = new JsonValidator($schema, $this->entityName);
        $validator->validate($this->apiParams);
    }
} 