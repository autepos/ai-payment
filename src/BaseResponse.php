<?php

namespace Autepos\AiPayment;


use JsonSerializable;
use Illuminate\Http\Response;

class BaseResponse implements JsonSerializable
{


    /**
     * Http status code
     *
     * @var int
     */
    public $httpStatusCode = null;


    /**
     * The type of response;
     *
     * @var ResponseType
     */
    protected $type = null;

    /**
     * Is the response successful?
     *
     * @var bool
     */
    public $success = false;

    /**
     * Response message.
     *
     * @var string|null
     */
    public $message = null;

    /**
     * Errors that occurred.
     *
     * @var array
     */
    public $errors = [];


    /**
     * A key-value pair that should be sent to client side for further processing.
     *
     * @var array
     */
    protected $clientSideData = [];




    /**
     * Construct a response
     *
     */
    public function __construct(ResponseType $type, bool $success = false, string $message = null, array $errors = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->errors = $errors;

        $this->type = $type;
    }



    /**
     * New up a response type
     *
     * @throws \InvalidArgumentException When type is unknown type.
     */
    public static function newType(string $type): ResponseType
    {
        try {
            return new ResponseType($type);
        } catch (\InvalidArgumentException $ex) {
            throw $ex;
        }
    }

    /**
     * Set the ResponseType.
     */
    public function setType(ResponseType $type)
    {
        $this->type = $type;
    }

    /**
     * Get the ResponseType.
     *
     */
    public function getType(): ResponseType
    {
        return $this->type;
    }

    /**
     * Set the client side data
     *
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function setClientSideData($key, $val): void
    {
        $this->clientSideData[$key] = $val;
    }

    /**
     * Get client side data
     *
     * @return array
     */
    private function getClientSideData(): array
    {
        return $this->clientSideData;
    }

    /**
     * Get client side errors; i.e errors in a way that client side will understand.
     *
     */
    private function getClientSideErrors(): ?array
    {
        if (count($this->errors)) {
            return ['reason' => $this->errors];
        }
        return null;
    }

    /**
     * Check if instance has error
     *
     * @return boolean
     */
    public function hasError(): bool
    {
        return !!count($this->errors);
    }

    /**
     * Convert the response to Http response.
     * Missing parameters will be replaced with the equivalent in the instance.
     */
    public function toHttp($content = null, $http_status_code = null, array $headers = []): Response
    {
        $data = $this->toArray();

        $data['message'] = $content ?? $data['message'];

        $http_status_code = $http_status_code ?? $data['http_status_code'];
        if (!$http_status_code) {
            $http_status_code = (!$this->success or $this->hasError()) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        }

        return new Response($data, $http_status_code, $headers);
    }

    /**
     * Convert the instance to array
     */
    protected function toArray(): array
    {
        $data = [
            'type' => $this->type->getName(),
            'success' => $this->success,
            'message' => $this->message,
            'transaction' => null,
            'client_side_data' => $this->getClientSideData(),
            'errors' => $this->getClientSideErrors(),
            'http_status_code' => $this->httpStatusCode,
        ];




        return $data;
    }


    /**
     * @inheritDoc
     *
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
