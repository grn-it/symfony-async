<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ServiceDeserializer
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }
    
    public function deserialize(ResponseInterface $response, string $className): Object|array|null
    {
        $result = null;
        try {
            if ($response->getStatusCode() === Response::HTTP_OK) {
                $result = $this->serializer->deserialize(
                    $response->getContent(),
                    $className,
                    'json'
                );
            }
        } catch (\Exception) {
            return null;
        }
        
        return $result;
    }
}
