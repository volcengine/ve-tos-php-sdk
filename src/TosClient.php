<?php
/**
 * Copyright (2022) Volcengine
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Tos;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tos\Config\ConfigParser;
use Tos\Exception\TosClientException;
use Tos\Exception\TosServerException;
use Tos\Helper\Helper;
use Tos\Model\AbortMultipartUploadInput;
use Tos\Model\AbortMultipartUploadOutput;
use Tos\Model\AppendObjectInput;
use Tos\Model\AppendObjectOutput;
use Tos\Model\CompleteMultipartUploadInput;
use Tos\Model\CompleteMultipartUploadOutput;
use Tos\Model\Constant;
use Tos\Model\CopyObjectInput;
use Tos\Model\CopyObjectOutput;
use Tos\Model\CreateBucketInput;
use Tos\Model\CreateBucketOutput;
use Tos\Model\CreateMultipartUploadInput;
use Tos\Model\CreateMultipartUploadOutput;
use Tos\Model\DeleteBucketInput;
use Tos\Model\DeleteBucketOutput;
use Tos\Model\DeleteMultiObjectsInput;
use Tos\Model\DeleteMultiObjectsOutput;
use Tos\Model\DeleteObjectInput;
use Tos\Model\DeleteObjectOutput;
use Tos\Model\GetObjectACLInput;
use Tos\Model\GetObjectACLOutput;
use Tos\Model\GetObjectInput;
use Tos\Model\GetObjectOutput;
use Tos\Model\GetObjectToFileInput;
use Tos\Model\GetObjectToFileOutput;
use Tos\Model\HeadBucketInput;
use Tos\Model\HeadBucketOutput;
use Tos\Model\HeadObjectInput;
use Tos\Model\HeadObjectOutput;
use Tos\Model\HttpRequest;
use Tos\Model\ListBucketsInput;
use Tos\Model\ListBucketsOutput;
use Tos\Model\ListMultipartUploadsInput;
use Tos\Model\ListMultipartUploadsOutput;
use Tos\Model\ListObjectsInput;
use Tos\Model\ListObjectsOutput;
use Tos\Model\ListObjectVersionsInput;
use Tos\Model\ListObjectVersionsOutput;
use Tos\Model\ListPartsInput;
use Tos\Model\ListPartsOutput;
use Tos\Model\PreSignedURLInput;
use Tos\Model\PreSignedURLOutput;
use Tos\Model\PutObjectACLInput;
use Tos\Model\PutObjectACLOutput;
use Tos\Model\PutObjectFromFileInput;
use Tos\Model\PutObjectFromFileOutput;
use Tos\Model\PutObjectInput;
use Tos\Model\PutObjectOutput;
use Tos\Model\SetObjectMetaInput;
use Tos\Model\SetObjectMetaOutput;
use Tos\Model\UploadPartCopyInput;
use Tos\Model\UploadPartCopyOutput;
use Tos\Model\UploadPartFromFileInput;
use Tos\Model\UploadPartFromFileOutput;
use Tos\Model\UploadPartInput;
use Tos\Model\UploadPartOutput;


/**
 * @method ListBucketsOutput listBuckets(ListBucketsInput $input);
 * @method CreateBucketOutput createBucket(CreateBucketInput $input);
 * @method HeadBucketOutput headBucket(HeadBucketInput $input);
 * @method DeleteBucketOutput deleteBucket(DeleteBucketInput $input);
 * @method CopyObjectOutput copyObject(CopyObjectInput $input);
 * @method DeleteObjectOutput deleteObject(DeleteObjectInput $input);
 * @method DeleteMultiObjectsOutput deleteMultiObjects(DeleteMultiObjectsInput $input);
 * @method GetObjectOutput getObject(GetObjectInput $input);
 * @method GetObjectToFileOutput getObjectToFile(GetObjectToFileInput $input);
 * @method GetObjectACLOutput getObjectACL(GetObjectACLInput $input);
 * @method HeadObjectOutput headObject(HeadObjectInput $input);
 * @method AppendObjectOutput appendObject(AppendObjectInput $input);
 * @method ListObjectsOutput listObjects(ListObjectsInput $input);
 * @method ListObjectVersionsOutput listObjectVersions(ListObjectVersionsInput $input);
 * @method PutObjectOutput putObject(PutObjectInput $input);
 * @method PutObjectFromFileOutput putObjectFromFile(PutObjectFromFileInput $input);
 * @method PutObjectACLOutput putObjectACL(PutObjectACLInput $input);
 * @method SetObjectMetaOutput setObjectMeta(SetObjectMetaInput $input);
 * @method CreateMultipartUploadOutput createMultipartUpload(CreateMultipartUploadInput $input);
 * @method UploadPartOutput uploadPart(UploadPartInput $input);
 * @method UploadPartFromFileOutput uploadPartFromFile(UploadPartFromFileInput $input);
 * @method CompleteMultipartUploadOutput completeMultipartUpload(CompleteMultipartUploadInput $input);
 * @method AbortMultipartUploadOutput abortMultipartUpload(AbortMultipartUploadInput $input);
 * @method UploadPartCopyOutput uploadPartCopy(UploadPartCopyInput $input);
 * @method ListMultipartUploadsOutput listMultipartUploads(ListMultipartUploadsInput $input);
 * @method ListPartsOutput listParts(ListPartsInput $input);
 *
 */
class TosClient
{

    /**
     * @var ConfigParser
     */
    private $cp;

    /**
     * @var Client
     */
    private $client;


    use Model\InputTranslator;
    use Model\OutputParser;
    use Auth\Signer;
    use Upload\Uploader;

    /**
     * @param array|ConfigParser|string $configOrRegion
     */
    public function __construct($configOrRegion, $ak = '', $sk = '', $endpoint = '')
    {
        if (is_array($configOrRegion)) {
            $this->cp = new ConfigParser($configOrRegion);
        } else if ($configOrRegion instanceof ConfigParser) {
            $this->cp = $configOrRegion;
        } else if (is_string($configOrRegion)) {
            $this->cp = new ConfigParser([
                'region' => trim($configOrRegion),
                'ak' => $ak,
                'sk' => $sk,
                'endpoint' => $endpoint,
            ]);
        } else {
            throw new TosClientException('invalid config');
        }

        $this->client = new Client([
            'timeout' => 0,
            'read_timeout' => $this->cp->getSocketTimeout() / 1000,
            'connect_timeout' => $this->cp->getConnectionTimeout() / 1000,
            'allow_redirects' => false,
            'verify' => $this->cp->isEnableVerifySSL(),
            'http_errors' => false,
            'decode_content' => false,
        ]);
    }

    public function __call($method, $args)
    {
        $method = ucfirst($method);
        $input = null;
        if (count($args) === 0) {
            if ($method === 'ListBuckets') {
                $input = new ListBucketsInput();
            }
        } else {
            $input = $args[0];
        }
        $transFn = __CLASS__ . '::trans' . $method . 'Input';
        $parseFn = __CLASS__ . '::parse' . $method . 'Output';
        if (is_callable($transFn) && is_callable($parseFn)) {
            $body = null;
            $closeBody = false;
            try {
                if ($method === 'GetObjectToFile') {
                    list($request, $filePath, $doMkdir, $bucket, $key) = $transFn($input);
                    $response = $this->doRequest($request, !$doMkdir && $input->isStreamMode());
                    return $parseFn($response, $filePath, $doMkdir, $bucket, $key);
                }

                $request = $transFn($input);
                if ($method === 'PutObjectFromFile' || $method === 'UploadPartFromFile') {
                    $closeBody = true;
                } else if ($method === 'PutObject' || $method === 'AppendObject' || $method === 'UploadPart') {
                    if (!$request->body) {
                        $request->headers[Constant::HeaderContentLength] = 0;
                    }
                }
                $body = $request->body;
                $response = $this->doRequest($request, $method === 'GetObject' && $input->isStreamMode());
                if ($method === 'UploadPart' || $method === 'UploadPartCopy' || $method == 'UploadPartFromFile') {
                    return $parseFn($response, $input->getPartNumber());
                }
                return $parseFn($response);
            } catch (TosClientException $ex) {
                throw $ex;
            } catch (TosServerException $ex) {
                throw $ex;
            } catch (TransferException $ex) {
                throw new TosClientException(sprintf('do http request for %s error, %s', $this->cp->getEndpoint($request->bucket, $request->key), $ex->getMessage()), $ex);
            } catch (GuzzleException $ex) {
                throw new TosClientException(sprintf('do http request for %s error, %s', $this->cp->getEndpoint($request->bucket, $request->key), $ex->getMessage()), $ex);
            } catch (\Exception $ex) {
                throw new TosClientException(sprintf('unknown error, %s', $ex->getMessage()), $ex);
            } finally {
                if ($closeBody && $body) {
                    if (is_resource($body)) {
                        fclose($body);
                    } else if ($body instanceof StreamInterface) {
                        $body->close();
                    }
                }
            }
        }
        throw new TosClientException(sprintf('unknown method %s error', $method));
    }

    /**
     * @param HttpRequest $request
     * @param bool $stream
     * @return ResponseInterface
     */
    private function &doRequest(HttpRequest &$request, $stream = false)
    {
        $response = $this->doRequestAsync($request, $stream)->wait();
        return $response;
    }

    private function &doRequestAsync(HttpRequest &$request, $stream = false)
    {
        list($method, $requestUri, $headers, $body) = $this->prepareRequest($request);
        $options = [
            'headers' => $headers,
            'stream' => $stream,
            'body' => $body,
        ];

        $promise = $this->client->requestAsync($method, $requestUri, $options);
        return $promise;
    }

    private function &prepareRequest(HttpRequest &$request)
    {
        self::sign($request, $this->cp->getHost($request->bucket), $this->cp->getAk(),
            $this->cp->getSk(), $this->cp->getSecurityToken(), $this->cp->getRegion());

        $headers = $request->headers;
        $headers[Constant::HeaderUserAgent] = $this->cp->getUserAgent();
        $headers[Constant::HeaderConnection] = 'Keep-Alive';
        $requestUri = $this->cp->getEndpoint($request->bucket, $request->key);
        $queries = $request->queries;
        $body = $request->body;

        if ($queries && count($queries) > 0) {
            $requestUri .= '?';
            foreach ($queries as $key => $val) {
                $requestUri .= $key . '=' . $val . '&';
            }
            $requestUri = substr($requestUri, 0, strlen($requestUri) - 1);
        }

        if ($body) {
            if (is_resource($body) && ftell($body) > 0) {
                // fix auto rewind bug in CurlFactory->applyBody
                $body = new NoSeekStream(new Stream($body));
            } else if ($body instanceof StreamInterface && $body->tell() > 0) {
                // fix auto rewind bug in CurlFactory->applyBody
                $body = new NoSeekStream($body);
            }
        }

        $result = [$request->method, $requestUri, $headers, $body];
        return $result;
    }

    /**
     * @param PreSignedURLInput $input
     * @return PreSignedURLOutput
     */
    public function &preSignedURL(PreSignedURLInput $input)
    {
        $request = new HttpRequest();
        if (($method = $input->getHttpMethod()) && self::checkHttpMethod($method)) {
            $request->method = $method;
        }

        if ($bucket = $input->getBucket()) {
            $bucket = self::checkBucket($bucket);
            $request->bucket = $bucket;
        }

        if ($key = $input->getKey()) {
            $key = self::checkKey($key);
            $request->key = $key;
        }

        if (($query = $input->getQuery()) && is_array($query)) {
            $request->queries = $query;
        } else {
            $request->queries = [];
        }

        $schema = '';
        $domain = '';
        if ($alternativeEndpoint = $input->getAlternativeEndpoint()) {
            $result = Helper::splitEndpoint($alternativeEndpoint);
            $schema = $result['schema'];
            $domain = $result['domain'];
        }

        $signedHeader = [];
        if (($ak = $this->cp->getAk()) && ($sk = $this->cp->getSk())) {
            if (($header = $input->getHeader()) && is_array($header)) {
                $request->headers = $header;
            } else {
                $request->headers = [];
            }

            $request->headers[Constant::HeaderHost] = $this->cp->getHost($request->bucket, $domain);
            $signedHeaders = null;
            $canonicalHeaders = self::getCanonicalHeaders($request, $signedHeaders, $signedHeader);

            $longDate = null;
            $shortDate = null;
            $credentialScope = null;
            $region = $this->cp->getRegion();
            self::prepareDateAndCredentialScope($longDate, $shortDate, $credentialScope, $region);

            $request->queries['X-Tos-Algorithm'] = self::$algorithm;
            $request->queries['X-Tos-Credential'] = $ak . '/' . $credentialScope;
            $request->queries['X-Tos-Date'] = $longDate;
            $request->queries['X-Tos-Expires'] = intval($expires = $input->getExpires()) ? $expires : 3600;
            $request->queries['X-Tos-SignedHeaders'] = $signedHeaders;
            if ($securityToken = $this->cp->getSecurityToken()) {
                $request->queries['X-Tos-Security-Token'] = $securityToken;
            }
            $canonicalRequest = self::getCanonicalRequest($request, $canonicalHeaders, $signedHeaders, true);

            $stringToSign = self::getStringToSign($canonicalRequest, $longDate, $credentialScope);
            $signature = self::getSignature($stringToSign, $shortDate, $sk, $region);
            $request->queries['X-Tos-Signature'] = rawurlencode($signature);
        }

        $signedUrl = $this->cp->getEndpoint($request->bucket, $request->key, $schema, $domain, true);
        if (count($request->queries) > 0) {
            $signedUrl .= '?';
            if (count($signedHeader) > 0) {
                foreach ($request->queries as $key => $val) {
                    $signedUrl .= $key . '=' . $val . '&';
                }
            } else {
                foreach ($request->queries as $key => $val) {
                    $signedUrl .= rawurlencode($key) . '=' . rawurlencode($val) . '&';
                }
            }
            $signedUrl = substr($signedUrl, 0, strlen($signedUrl) - 1);
        }
        $output = new PreSignedURLOutput($signedUrl, $signedHeader);
        return $output;
    }
}

