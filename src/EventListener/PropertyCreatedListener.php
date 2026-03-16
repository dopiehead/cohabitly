<?php
// src/EventListener/PropertyCreatedListener.php
namespace App\EventListener;

use App\Event\PropertyCreatedEvent;
use Psr\Log\LoggerInterface;

class PropertyCreatedListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onPropertyCreated(PropertyCreatedEvent $event)
    {
        $property = $event->getProperty();
        // Example: log
        $this->logger->info('New property created: ' . $property->getTitle());
        
        // You can also send notifications, emails, cache updates, etc.
    }
}