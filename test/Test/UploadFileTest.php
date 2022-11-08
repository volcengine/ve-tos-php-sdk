<?php

namespace Tos\Test;

use Tos\Model\GetObjectInput;
use Tos\Model\UploadFileInput;

require_once 'TestCommon.php';

class UploadFileTest extends TestCommon
{
    public function testNormal()
    {
        $client = self::getClient();
        $bucket = self::$fixedBucket;
        $sampleFilePath = './temp/' . self::genRandomString(10) . '.txt';
        $key = self::genRandomString(10);
        $key2 = self::genRandomString(10);

        if (!file_exists($sampleFilePath)) {
            self::createSampleFile($sampleFilePath);
            self::$bigSampleFilePath = $sampleFilePath;
        }

        $sourceFileMd5 = base64_encode(md5_file($sampleFilePath, true));

        $input = new UploadFileInput($bucket, $key, $sampleFilePath);
        $input->setPartSize(5 * 1024 * 1024);
        $input->setTaskNum(3);
        $output = $client->uploadFile($input);
        $this->assertTrue(strlen($output->getRequestId()) > 0);

        $output = $client->getObject(new GetObjectInput($bucket, $key));
        $this->assertTrue(strlen($output->getRequestId()) > 0);
        $this->assertEquals(base64_encode(md5($output->getContent()->getContents(), true)), $sourceFileMd5);
        $output->getContent()->close();

        $input->setEnableCheckpoint(true);
        $input->setTaskNum(1);
        $input->setKey($key2);
        $output = $client->uploadFile($input);
        $this->assertTrue(strlen($output->getRequestId()) > 0);
        $output = $client->getObject(new GetObjectInput($bucket, $key2));
        $this->assertTrue(strlen($output->getRequestId()) > 0);
        $this->assertEquals(base64_encode(md5($output->getContent()->getContents(), true)), $sourceFileMd5);
        $output->getContent()->close();

    }

}