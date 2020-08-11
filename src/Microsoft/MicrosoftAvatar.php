<?php

namespace SocialiteProviders\Microsoft;

use GuzzleHttp\Psr7\Response;

class MicrosoftAvatar
{
    /**
     * The Guzzle Response object.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    public $response;

    /**
     * Set the response of the avatar.
     *
     * @param string $response
     *
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Return the content type of the avatar.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->response->getHeader('content-type')[0];
    }

    /**
     * Return the avatar in binary.
     *
     * @return binary
     */
    public function getContents()
    {
        return $this->response->getBody()->getContents();
    }

    /**
     * Return the data URI formatted image.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'data:'.$this->getContentType().';base64,'.base64_encode($this->getContents());
    }
}
