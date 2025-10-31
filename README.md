# Workerman Connection Pipe

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-connection-pipe.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-connection-pipe)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-connection-pipe.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-connection-pipe)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg)](https://php.net/)
[![Coverage Status](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/workerman-connection-pipe)

A high-performance, highly customizable connection forwarding framework based on Workerman, supporting data transmission and protocol conversion between TCP and UDP connections.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [Configuration](#configuration)
  - [TCP to TCP Proxy Example](#tcp-to-tcp-proxy-example)
  - [UDP to UDP Forwarding Example](#udp-to-udp-forwarding-example)
- [Listening to Events](#listening-to-events)
- [Custom Data Processing](#custom-data-processing)
- [Detailed Workflows](#detailed-workflows)
- [Advanced Configuration](#advanced-configuration)
  - [Setting Buffer Size](#setting-buffer-size)
  - [Enabling Encrypted Transport](#enabling-encrypted-transport)
- [Performance Optimization Tips](#performance-optimization-tips)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

## Features

- **Multi-protocol Support**
  - TCP → TCP: Transparent TCP proxying, load balancing, and reverse proxying
  - TCP → UDP: Access UDP services from TCP clients, such as DNS proxying
  - UDP → TCP: Access TCP services from UDP clients, such as game server frontend
  - UDP → UDP: UDP relay, stream forwarding, with full-cone NAT support

- **Advanced Connection Management**
  - Automatic connection lifecycle and resource release management
  - Smart buffer control to prevent memory overflow
  - Timeout cleanup mechanism that automatically releases inactive connections

- **Powerful Extension Functions**
  - Complete event system for monitoring all forwarding events
  - Flexible logging interface for recording detailed connection and transmission information
  - Custom data processing logic for protocol conversion and content modification

## Installation

```bash
composer require tourze/workerman-connection-pipe
```

## Quick Start

### Configuration

First, configure the dependency container, setting up the logger and event dispatcher:

```php
use Tourze\Workerman\ConnectionPipe\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Configure logging system
$logger = new Logger('pipe');
$logger->pushHandler(new StreamHandler('path/to/your/app.log', Logger::DEBUG));
Container::$logger = $logger;

// Configure event dispatcher
$eventDispatcher = new EventDispatcher();
Container::$eventDispatcher = $eventDispatcher;
```

### TCP to TCP Proxy Example

Create a simple TCP proxy that forwards traffic from local port 8443 to a remote server:

```php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Tourze\Workerman\ConnectionPipe\PipeFactory;

// Create local listening service
$worker = new Worker('tcp://0.0.0.0:8443');

$worker->onConnect = function(TcpConnection $connection) {
    // Create connection to target server
    $targetConnection = new AsyncTcpConnection('tcp://example.com:443');
    $targetConnection->connect();

    // Create and start pipe from source to target
    $forwardPipe = PipeFactory::createTcpToTcp($connection, $targetConnection);
    $forwardPipe->pipe();

    // Create and start pipe from target to source
    $backwardPipe = PipeFactory::createTcpToTcp($targetConnection, $connection);
    $backwardPipe->pipe();

    // Save pipe references for later cleanup
    $connection->pipeTo = $forwardPipe;
    $connection->pipeFrom = $backwardPipe;

    // Set cleanup logic when source connection closes
    $connection->onClose = function() use ($connection) {
        if (isset($connection->pipeTo)) {
            $connection->pipeTo->unpipe();
        }
        if (isset($connection->pipeFrom)) {
            $connection->pipeFrom->unpipe();
        }
    };
};

Worker::runAll();
```

### UDP to UDP Forwarding Example

Create a UDP forwarding service that forwards UDP traffic from local port 8053 to Google's DNS server:

```php
use Workerman\Worker;
use Workerman\Connection\AsyncUdpConnection;
use Tourze\Workerman\ConnectionPipe\PipeFactory;

// Create UDP source server
$sourceWorker = new Worker('udp://0.0.0.0:8053');

$sourceWorker->onWorkerStart = function($worker) {
    // Create UDP connection to target DNS server
    $targetConnection = new AsyncUdpConnection('udp://8.8.8.8:53');

    $targetConnection->onConnect = function($connection) use ($worker) {
        echo "Connected to target UDP server\n";
        // Store the target connection
        $worker->targetConnection = $connection;

        // Get UDP connection objects
        $sourceConnection = $worker->getMainSocket();

        // Create UDP to UDP pipe
        $pipe = PipeFactory::createUdpToUdp($sourceConnection, $connection);

        // Store pipe reference
        $worker->pipe = $pipe;

        // Start the pipe
        $pipe->pipe();
    };

    $targetConnection->connect();
};

$sourceWorker->onMessage = function($connection, $data, $sourceAddress, $sourcePort) {
    $worker = $connection->worker;

    // Check if target connection is available
    if (!isset($worker->pipe)) {
        return;
    }

    // Forward the data with source address information
    $worker->pipe->forward($data, $sourceAddress, $sourcePort);
};

Worker::runAll();
```

## Listening to Events

You can listen to data forwarding related events for custom processing:

```php
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\Container;

// Listen for successful data forwarding events
Container::$eventDispatcher->addListener(
    DataForwardedEvent::class, 
    function(DataForwardedEvent $event) {
        $pipe = $event->getPipe();
        $data = $event->getData();
        $sourceProtocol = $event->getSourceProtocol();
        $targetProtocol = $event->getTargetProtocol();
        $context = $event->getContext();

        // Process successfully forwarded data, e.g., statistics or logging
        echo "Forwarded " . strlen($data) . " bytes from {$sourceProtocol} to {$targetProtocol}\n";
    }
);

// Listen for data forwarding failure events
Container::$eventDispatcher->addListener(
    ForwardFailedEvent::class,
    function(ForwardFailedEvent $event) {
        $errorMessage = $event->getErrorMessage();

        // Handle forwarding failure, e.g., warning or retry
        echo "Forwarding failed: {$errorMessage}\n";
    }
);
```

## Custom Data Processing

If you need to modify or process data during forwarding, you can extend existing pipe classes and override relevant methods:

```php
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;

class EncryptedTcpPipe extends TcpToTcpPipe
{
    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        // Encrypt or process data before forwarding
        $processedData = $this->encryptData($data);

        // Call parent method to complete actual forwarding
        return parent::forward($processedData, $sourceAddress, $sourcePort);
    }

    protected function encryptData(string $data): string
    {
        // Implement your data encryption or processing logic
        return $data; // No actual processing in this example
    }
}

// Use custom pipe class
$pipe = new EncryptedTcpPipe();
$pipe->setSource($sourceConnection);
$pipe->setTarget($targetConnection);
$pipe->pipe();
```

## Detailed Workflows

For detailed workflows of each connection combination, see the [workflows](./workflows) directory:

- [TCP to TCP Forwarding Workflow](./workflows/tcp_to_tcp_workflow.md)
- [TCP to UDP Forwarding Workflow](./workflows/tcp_to_udp_workflow.md)
- [UDP to TCP Forwarding Workflow](./workflows/udp_to_tcp_workflow.md)
- [UDP to UDP Forwarding Workflow](./workflows/udp_to_udp_workflow.md)

## Advanced Configuration

### Setting Buffer Size

For high-traffic applications, you can adjust the buffer size of TCP connections:

```php
use Workerman\Connection\TcpConnection;

// Set global maximum send buffer size to 10MB
TcpConnection::$defaultMaxSendBufferSize = 10 * 1024 * 1024;

// Set buffer size for a single connection
$targetConnection->maxSendBufferSize = 5 * 1024 * 1024;
```

### Enabling Encrypted Transport

You can enable SSL/TLS encryption on TCP connections:

```php
$targetConnection = new AsyncTcpConnection('tcp://example.com:443');

// Enable SSL
$targetConnection->transport = 'ssl';

$targetConnection->connect();
```

## Performance Optimization Tips

1. **Connection Reuse**: For frequently established connections, consider implementing a connection pool to reduce connection establishment overhead.

2. **Multi-process Mode**: On multi-core servers, allocate an appropriate number of Worker processes:

   ```php
   $worker->count = 4; // Adjust based on CPU cores and load
   ```

3. **Memory Optimization**: For long-running applications, periodically check memory usage to prevent memory leaks.

4. **Timeout Settings**: Set appropriate timeout values for different application scenarios to ensure resources are released in a timely manner.

## Troubleshooting

1. **Connection Cannot Be Established**: Check network connectivity, firewall settings, and whether the target server is reachable.

2. **Data Forwarding Failure**: Ensure pipes are correctly created and started, check error messages in event listeners.

3. **High Memory Usage**: Check if connections are properly closed and NAT mappings are periodically cleaned up.

4. **Unstable UDP Communication**: UDP does not have connection state, ensure proper handling of packet loss and out-of-order situations.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for details on what has changed in each version.

## License

This project is licensed under the [MIT License](LICENSE).
