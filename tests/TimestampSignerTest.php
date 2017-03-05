<?php

use Kyslik\TimestampSigner\Exceptions\BadSignatureException;
use Kyslik\TimestampSigner\TimestampSigner;
use Symfony\Component\Yaml\Parser;

/**
 * Class TimestampSignerTest
 * @package Kyslik\TimestampSigner\Tests
 */
class TimestampSignerTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var TimestampSigner
     */
    protected $signer;

    protected $data, $timestamp_data, $object_data;


    public function testSign()
    {
        foreach ($this->data as $data) {
            if ($data['valid']) {
                $this->assertEquals($data['signed'], $this->signer->sign($data['in']));
            } else {
                $this->assertNotEquals($data['signed'], $this->signer->sign($data['in']));
            }
        }
    }


    public function testUnsign()
    {
        foreach ($this->data as $data) {
            $signed = $this->signer->sign($data['in']);
            $this->assertEquals($data['in'], $this->signer->unsign($signed, TimestampSigner::WITH_TIME));
        }
    }


    public function testSignWithTimestamp()
    {
        foreach ($this->timestamp_data as $data) {
            $this->signer->setTimestamp($data['timestamp']);
            if ($data['valid']) {
                $this->assertEquals($data['signed'], $this->signer->sign($data['in'], TimestampSigner::WITH_TIME));
            } else {
                $this->assertNotEquals($data['signed'], $this->signer->sign($data['in'], TimestampSigner::WITH_TIME));
            }
        }
    }


    public function testUnsignWithTimestamp()
    {
        foreach ($this->timestamp_data as $data) {
            $this->signer->setTimestamp($data['timestamp']);
            $signed = $this->signer->sign($data['in'], TimestampSigner::WITH_TIME);
            $this->assertEquals($data['in'], $this->signer->unsign($signed, TimestampSigner::WITH_TIME));
        }
    }


    public function testUnsignFailSeparator()
    {
        $this->expectException(BadSignatureException::class);
        $this->signer->unsign('carthago delenda est');
    }


    public function testUnsignFailSignature()
    {
        $this->expectException(BadSignatureException::class);
        foreach ($this->data as $data) {
            $this->signer->unsign('x'.$this->signer->sign($data['in']));
        }
    }


    public function testDumps()
    {
        $this->signer->setTimestamp(1234567890);
        foreach ($this->object_data as $data) {
            $dumped = $this->signer->dumps($data['in']);
            $this->assertEquals($data['signed'], $dumped);
        }
    }


    public function testLoads()
    {
        foreach ($this->object_data as $data) {
            $loaded = $this->signer->loads($data['signed']);
            $this->assertEquals($data['in'], $loaded);
        }
    }


    public function testLoadsWithMaxAge()
    {
        $array = [ 'user_is' => 1 ];
        $dumped = $this->signer->setTimestamp(time())->dumps($array);
        $this->assertEquals($array, $this->signer->setMaxAge(10)->loads($dumped));
    }


    public function testLoadsWithMaxAgeFails()
    {
        $this->expectException(BadSignatureException::class);
        $array = [ 'user_is' => 1 ];
        $dumped = $this->signer->setTimestamp(time() - 4)->dumps($array);
        $this->signer->setMaxAge(2)->loads($dumped);
    }


    public function testUrlSafety()
    {
        $value = 'https://example.com/site/parameter?query=string';
        $signature = $this->signer->signature($value);

        $this->AssertTrue(false === strpos($signature, '+'));
        $this->AssertTrue(false === strpos($signature, '/'));
    }


    protected function setUp()
    {
        $fixture = dirname(__FILE__).'/fixtures/testData.yaml';
        $yaml = new Parser();
        $data = $yaml->parse(file_get_contents($fixture));

        $this->signer = new TimestampSigner($data['secret']);
        $this->data = $data['data'];
        $this->timestamp_data = $data['timestamp-data'];
        $this->object_data = $data['object-data'];
    }
}
