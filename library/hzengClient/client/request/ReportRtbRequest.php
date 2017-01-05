<?php

class ReportRtbRequest {
    private $apiParams = array();
    private $entityName = 'Report';
    public function getApiMethodName()
    {
        return "hzeng.report.rtb";
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