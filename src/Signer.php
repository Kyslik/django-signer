<?php
namespace Kyslik\Django\Signing;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Kyslik\Django\Signing\Exceptions\BadSignatureException;
use Tuupola\Base62\Encoder as Base62;

/**
 * Class Signer
 * @package Kyslik\Django\Signing
 */
class Signer
{

    CONST MINUTE = 60;
    CONST HOUR = 60 * 60;
    CONST DAY = 12 * self::HOUR;
    CONST WEEK = 7 * self::DAY;

    CONST WITH_TIME = true;

    protected $secret, $salt, $separator, $serializer, $base62;

    /**
     * @var integer
     */
    private $max_age;

    /**
     * @var integer
     */
    private $timestamp;


    /**
     * Create a signer with a key
     *
     * @param string $secret Signing key to use
     * @param string $separator
     * @param string $salt
     * @param int    $max_age
     */
    public function __construct(string $secret, string $separator = ':', string $salt = null, int $max_age = null)
    {
        $this->secret = $secret;
        $this->salt = (null !== $salt ? $salt : 'django.core.signing');
        $this->separator = $separator;
        $this->base62 = new Base62;
        $this->max_age = $max_age;
        $this->timestamp = null;
    }


    /**
     * Sign a serializable object
     *
     * @param Data       $object
     * @param Serializer $serializer
     * @param string     $encoder
     * @param bool       $compress Compress data, defaults to false
     *
     * @return string Object signature
     * @internal param Serializer $Serializer to use, defaults to JSONSerializer with GetSetMethodNormalizer
     * @internal param Data $object object to sign
     */
    public function dumps(
        $object,
        Serializer $serializer = null,
        string $encoder = 'json',
        bool $compress = false
    ): string {
        $s = ($serializer ? $serializer : $this->getSerializer());
        $data = $s->serialize($object, $encoder);

        $is_compressed = false;

        if ($compress) {
            $compressed = gzcompress($data, 9);
            if (strlen($compressed) < (strlen($data) - 1)) {
                $data = $compressed;
                $is_compressed = true;
            }
        }

        $base64d = rtrim(base64_encode($data), '=');

        if ($is_compressed) {
            $base64d = '.'.$base64d;
        }

        return $this->sign($base64d, self::WITH_TIME);
    }


    /**
     * @return Serializer
     */
    protected function getSerializer(): Serializer
    {
        if ( ! $this->serializer) {
            $this->serializer = SerializerBuilder::create()->build();
        }

        return $this->serializer;
    }


    /**
     * Create a signed string (including the input value)
     *
     * @param string $value
     *
     * @param bool   $with_time
     *
     *
     * @return string $signature
     * @internal param string $string String to be signed
     */
    public function sign(string $value, bool $with_time = false): string
    {
        if ($with_time) {
            $value .= $this->separator.$this->base62->encode($this->timestamp ?? time());
        }

        return sprintf('%s%s%s', $value, $this->separator, $this->signature($value));
    }


    /**
     * Create a string signature
     *
     * @param   string $value String to be signed
     *
     * @return  string  $signature
     */
    public function signature(string $value): string
    {
        $salt = $this->salt.'signer';
        $key = sha1($salt.$this->secret, true);

        $salted_hmac = hash_hmac('sha1', $value, $key, true);

        $base64 = base64_encode($salted_hmac);
        $base64_url_safe = str_replace([ '+', '/' ], [ '-', '_' ], $base64);

        return rtrim($base64_url_safe, '=');
    }


    /**
     * Alias for sign($value, true)
     *
     * @param string $value
     *
     * @return string
     */
    public function signWithTimestamp(string $value): string
    {
        return $this->sign($value, Signer::WITH_TIME);
    }


    /**
     * @param string          $signature
     * @param string          $class
     * @param Serializer|null $serializer
     * @param string          $encoder
     *
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function loads(
        string $signature,
        string $class = 'array',
        Serializer $serializer = null,
        string $encoder = 'json'
    ) {
        $base64d = $this->unsign($signature, self::WITH_TIME);

        $decompress = false;
        if ($base64d[0] == '.') {
            $base64d = substr($base64d, 1);
            $decompress = true;
        }

        $data = base64_decode($base64d);

        if ($decompress) {
            $data = gzuncompress($data);
        }

        $s = $serializer ? $serializer : $this->getSerializer();

        return $s->deserialize($data, $class, $encoder);
    }


    /**
     * Test value signature. Raised BadSignatureException o fail.
     *
     * @param string $signed_value Signature to check
     *
     * @param bool   $with_time
     *
     * @return string $value         Unsigned original value
     * @throws BadSignatureException
     * @internal param int $max_age
     *
     */
    public function unsign(string $signed_value, bool $with_time = false): string
    {
        if (strpos($signed_value, $this->separator) === false) {
            throw new BadSignatureException('Invalid sign value.');
        }

        if ($with_time === false) {
            list($signature, $value) = array_map('strrev', explode($this->separator, strrev($signed_value), 2));
            if (hash_equals($signature, $this->signature($value))) {
                return $value;
            }

            throw new BadSignatureException('Signature "'.$signature.'" does not match value "'.$value.'"');
        }

        $base64d = $this->unsign($signed_value);

        if (strpos($base64d, $this->separator) !== false) {
            list($base62t, $base64d) = array_map('strrev', explode($this->separator, strrev($base64d), 2));

            if ($this->max_age !== null) {
                $timestamp = $this->base62->decode($base62t, 10);
                if ( ! is_numeric($timestamp) || ! is_numeric($this->max_age)) {
                    throw new BadSignatureException('Timestamp or max age is not numeric.');
                }

                $age = time() - $timestamp;
                if ($age > $this->max_age) {
                    throw new BadSignatureException('Signature expired.');
                }
            }
        }

        return $base64d;
    }


    /**
     * Alias for unsign($string, true)
     *
     * @param string $signed_value
     *
     * @return string
     */
    public function unsignWithTimestamp(string $signed_value): string
    {
        return $this->unsign($signed_value, Signer::WITH_TIME);
    }


    /**
     * @param mixed $timestamp
     *
     * @return Signer
     */
    public function setTimestamp($timestamp): Signer
    {
        $this->timestamp = $timestamp;

        return $this;
    }


    /**
     * @param int $max_age
     *
     * @return Signer
     */
    public function setMaxAge(int $max_age): Signer
    {
        $this->max_age = ($max_age <= 0) ? null : $max_age;

        return $this;
    }
}
