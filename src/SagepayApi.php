<?php

declare(strict_types=1);

namespace Sbarbat\SyliusSagepayPlugin;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;

abstract class SagepayApi
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->options['sandbox'] ? 'https://sandbox.opayo.eu.elavon.com/api/v1/' : 'https://live.opayo.eu.elavon.com/api/v1/';
    }

    /**
     * @return string
     */
    public function getOffsiteEndpoint()
    {
        return $this->options['sandbox'] ? 'https://sandbox.opayo.eu.elavon.com/gateway/service/vspform-register.vsp' : 'https://live.opayo.eu.elavon.com/gateway/service/vspform-register.vsp';
    }

    public function getOption(string $option)
    {
        return $this->options[$option];
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getTransactionCode(OrderInterface $order, PaymentInterface $payment)
    {
        return $order->getNumber().'_'.$payment->getId().'_'.time();
    }

    /**
     * @param mixed $method
     * @param mixed $path
     *
     * @return ResponseInterface
     */
    protected function doRequest($method, $path, array $fields = [])
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $request = $this->messageFactory->createRequest(
            $method,
            $this->getApiEndpoint(),
            $headers,
            http_build_query($fields)
        );

        $response = $this->client->send($request);

        $statusCode = $response->getStatusCode();
        if (! ($statusCode >= 200 && $statusCode < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }
}
