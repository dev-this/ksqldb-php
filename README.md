# ksqlDB PHP client

> Currently under development. API stability is not guaranteed until v1.

# Features
- Asynchronous operations (thanks to [amphp/amp](https://github.com/amphp/amp)!)
- Supports [defined client supported features](https://docs.ksqldb.io/en/latest/developer-guide/ksqldb-clients/contributing/#functionality)
  - [Push/pull query support (HTTP/2)](https://docs.ksqldb.io/en/latest/developer-guide/ksqldb-rest-api/streaming-endpoint/)
  - Terminating push queries
  - Inserting new rows of data into existing ksqlDB streams
  - Listing existing streams, tables, topics and queries
  - Creation and deletion of streams and tables
  - Terminating persistent queries
- Compatible with Amp APIs

## Usage

### Create a client
There is a factory available for client creation

`DevThis\KsqlDB\ClientFactory::create(string $hostname): DevThis\KsqlDB\Client`

No connection will be established until a client command has been called.

**Usage:**
```php
$hostname = 'http://localhost:8088';

$client = (new DevThis\KsqlDB\ClientFactory())->create($hostname);
```

### Streaming callbacks
Streaming a query requires a callback class that implements a callback interface.

`DevThis\KsqlDB\ClientFactory::stream(Statement $statement, StreamCallback $callback): Amp\Promise`

Callback class must implement `StreamCallback`
```php
interface StreamCallback {
    // Invoked once, at the start of the stream
    // StreamHeader has getters for the query ID, and column names and their data types.
    public function onHeader(StreamHeader $header): void;
    
    // OnEvent will be invoked on each new event
    // StreamEvent is an \ArrayObject
    public function onEvent(StreamEvent $event): void;
}
````

**Usage:**
```php
use DevThis\KsqlDB\Interfaces\StreamCallback;
use DevThis\KsqlDB\Statement

$transactionStatement = new Statement("SELECT * FROM transactions EMIT CHANGES;");

$transactionHandler = new class implements StreamCallback {
    public function onHeader(StreamHeader $header): void
    {
        echo sprintf(">Query ID: %s\n", $header->getQueryId());
    }

    public function onEvent(StreamEvent $event): void
    {
        echo "Processing new transaction\n";
        // do something with $event...
    }
}

$promise = $client->stream($transactionStatement, $transactionHandler);

// wait indefinitely
\Amp\Promise\wait($promise);
```

### Executing a statement
Executing a statement works similarly to Streaming a statement. The main difference is that executed statements are not continous operations.

`DevThis\KsqlDB\ClientFactory::execute(Statement $statement, ExecutionCallback $callback): Amp\Promise`

Callback class must implement `ExecutionCallback`
```php
interface StreamCallback {
    // OnEvent receives the statement result
    public function onResponse(array $event): void;
}
````

# Functional example
Asynchronous application that will eat its own dogfood. Consuming the very events it created:

```php
use DevThis\KsqlDB\Interfaces\StreamCallback;
use DevThis\KsqlDB\Statement;
use DevThis\KsqlDB\ClientFactory;
use DevThis\KsqlDB\StreamEvent;
use DevThis\KsqlDB\StreamHeader;

$client = (new ClientFactory())->create('http://localhost:8088');

$createStatement = new Statement("CREATE STREAM cool_data (
    id VARCHAR KEY,
    message VARCHAR,
    timestamp VARCHAR,
) WITH (
    kafka_topic = 'cool_data',
    partitions = 1,
    value_format = 'avro',
    timestamp = 'timestamp',
    timestamp_format = 'yyyy-MM-dd''T''HH:mm:ss'
);");
$streamStatement = new Statement("SELECT * FROM cool_data EMIT CHANGES;");
$insertStatement = new Statement("SELECT * FROM cool_data EMIT CHANGES;");

$transactionHandler = new class implements \DevThis\KsqlDB\Interfaces\ExecutionCallback {
    private const SCHEMA_ID = 0;
    private const SCHEMA_MESSAGE = 1;
    private const SCHEMA_TIMESTAMP = 2;

    public function onHeader(StreamHeader $header): void
    {
        echo sprintf(">Query ID: %s\n", $header->getQueryId());
        echo sprintf(">Columns: %s", print_r($header->getColumns(), true));
        echo "--------------------\n";
    }

    public function onEvent(StreamEvent $event): void
    {
        echo "Processing new transaction\n";
        echo sprintf(">ID: %s\n", $event[static::SCHEMA_ID]);
        echo sprintf(">Message: %s\n", $event[static::SCHEMA_MESSAGE]);
        echo sprintf(">Timestamp: %s\n", $event[static::SCHEMA_TIMESTAMP]);
    }
};

$streamingToken = new CancellationTokenSource();
$stream = $client->stream($streamStatement, $transactionHandler, $cancelToken);

// Repeat 100 times
\Amp\Loop::repeat(100, function () use (&$i) {
    // Array of promises
    $insertions = [
        $client->execute($streamStatement),
        $client->execute($streamStatement),
        $client->execute($streamStatement)
    ];
    
    // Wait for all promises to resolve before looping again
    \Amp\Promise\wait(\Amp\Promise\all($insertions));
});

// Kills the streaming query
$streamingCancelToken->cancel();
```

## Alternatives
- https://github.com/ytake/php-ksql
- https://github.com/seanmorris/ksqlc
