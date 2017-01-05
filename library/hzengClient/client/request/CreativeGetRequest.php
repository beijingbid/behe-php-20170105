<?php

class CreativeGetRequest {
    private $apiParams = array();
    private $entityName = 'Creative';
    public function getApiMethodName()
    {
        return "hzeng.creative.get";
    }

    public function addEntity($entity)
    {
        $this->apiParams = array('creativeIds' => $entity);
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
                "creativeIds" : {
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