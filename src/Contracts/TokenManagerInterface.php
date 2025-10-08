<?php

namespace Tigusigalpa\GigaChat\Contracts;

interface TokenManagerInterface
{
    /**
     * Get access token
     * 
     * @return string
     * @throws \Tigusigalpa\GigaChat\Exceptions\GigaChatException
     */
    public function getAccessToken(): string;
}
