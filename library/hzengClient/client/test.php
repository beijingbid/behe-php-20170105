<?php

require 'HzSdk.php';

function AdvertiserAddTest($client) {
    $req = new AdvertiserAddRequest();
    $entity = array(
        "advertiserId" => 105,
        "advertiserLiteName" => "name105",
        "advertiserName" => "name105",
        "siteName" => "sitename105",
        "siteUrl" => "http://url.com",
        "telephone" => "13800138000",
        "address" => "address5",
    );
    $req->addEntity($entity);

    $resp = $client->execute($req);
      
    return $resp;
}
function AdvertiserUpdateTest($client) {
    $req = new AdvertiserUpdateRequest();
    $entity = array(
        "advertiserId" => 105,
        "advertiserLiteName" => "name105",
        "advertiserName" => "name105",
        "siteName" => "sitename105",
        "siteUrl" => "http://url.com",
        "telephone" => "13800138000",
        "address" => "address5",
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function AdvertiserGetAllRequestTest($client) {
    $req = new AdvertiserGetAllRequest();
    $entity = array(
        "startDate" => '2014-04-20',
        "endDate" => '2014-04-22',
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function AdvertiserGetRequestTest($client) {
    $req = new AdvertiserGetRequest();
    $entity = array(105);
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function AdvertiserQueryQualificationRequestTest($client) {
    $req = new AdvertiserQueryQualificationRequest();
    $entity = array(105);
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function CreativeAddRequestTest($client) {
    $req = new CreativeAddRequest();
    $entity = array(
        "targetUrl" => "http://targeturl.com9999/2",
        "landingPage" => "http://landingpage.com9999/2",
        "monitorUrls" => array(
            "http://monitorurl.com99999/2"
        ),
        "creativeId" => 302,
        "creativeTradeId" => 7101,
        "advertiserId" => 9999,
        "binaryData" => "/9j/4AAQSkZJRgABAQAAAQABAAD/",
        "type" => 0,
        "height" => 600,
        "width" => 160,
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function CreativeUpdateRequestTest($client) {
    $req = new CreativeUpdateRequest();
    $entity = array(
        "targetUrl" => "http://targeturl.com9999/2",
        "landingPage" => "http://landingpage.com9999/2",
        "monitorUrls" => array(
            "http://monitorurl.com99999/2"
        ),
        "creativeId" => 302,
        "creativeTradeId" => 7101,
        "advertiserId" => 105,
        "binaryData" => "/9j/4AAQSkZJRgABAQAAAQABAAD/",
        "type" => 0,
        "height" => 600,
        "width" => 160,
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function CreativeGetAllRequestTest($client) {
    $req = new CreativeGetAllRequest();
    $entity = array(
        "startDate" => '2014-04-29',
        "endDate" => '2014-04-29',
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function CreativeGetRequestTest($client) {
    $req = new CreativeGetRequest();
    $entity = array(302);
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function CreativeQueryAuditStateRequestTest($client) {
    $req = new CreativeQueryAuditStateRequest();
    $entity = array(302);
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function ReportRtbRequestTest($client) {
    $req = new ReportRtbRequest();
    $entity = array(
        "startDate" => '2014-04-20',
        "endDate" => '2014-04-29',
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function ReportConsumeRequestTest($client) {
    $req = new ReportConsumeRequest();
    $entity = array(
        "startDate" => '2014-04-20',
        "endDate" => '2014-04-29',
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
function ReportAdvertiserRequestTest($client) {
    $req = new ReportAdvertiserRequest();
    $entity = array(
        "startDate" => '2014-04-20',
        "endDate" => '2014-04-29',
    );
    $req->addEntity($entity);
    $resp = $client->execute($req);
    return $resp;
}
$client = new AdxClient();
$client->dspId = 1;
$client->token = '0a1755bba726bf6ae697efd8d9b6df86';
var_dump(AdvertiserAddTest($client));