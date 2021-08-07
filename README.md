# ksqlDB PHP client

> Currently under development. API stability is not guaranteed until v1.

Requires PHP 8
```bash
composer require dev-this/ksqldb-php
```


# Features
- Asynchronous operations (thanks to [amphp/amp](https://github.com/amphp/amp)!)
- All the Confluent [desired client features](https://docs.ksqldb.io/en/latest/developer-guide/ksqldb-clients/contributing/#functionality)
  - [x] Push/pull query support (HTTP/2)(https://docs.ksqldb.io/en/latest/developer-guide/ksqldb-rest-api/streaming-endpoint/)
  - [x] Terminating push queries
  - [ ] Inserting new rows of data into existing ksqlDB streams
  - [x] Listing existing streams, tables, topics and queries
  - [x] Creation and deletion of streams and tables
  - [x] Terminating persistent queries
- Native `Amp\Promise` functionality

## Usage

### Create a client
There is a factory available for client creation

`DevThis\KsqlDB\ClientFactory::create(string $hostname): DevThis\KsqlDB\Client`

No HTTP connection will be established until a client command has been called.

**Usage:**
```php
$hostname = 'http://localhost:8088';

$client = (new DevThis\KsqlDB\Factory\ClientFactory())->create($hostname);
```

### Streaming callbacks
Streaming a query requires a callback class that implements a callback interface.
Establishing a stream is purposefully blocking until the header has been received (along with query ID).

`DevThis\KsqlDB\Factory\ClientFactory::stream(Statement $statement, StreamCallback $callback): Amp\Promise`

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

$stream = $client->stream($transactionStatement, $transactionHandler);
// Query ID
echo $stream->getQueryId();

// Terminate the query
$client->terminate($stream);

// wait indefinitely
\Amp\Promise\wait($promise);
```

### Executing a statement
Executing a statement works similarly to Streaming a statement. The main difference is that executed statements are not continous operations.

`DevThis\KsqlDB\Client::execute(Statement $statement): ArrayObject`

`ArrayObject` will contain the response.

# Functional example
Asynchronous application that will eat its own dogfood. Consuming the very events it created:

```php
use DevThis\KsqlDB\Interfaces\StreamCallback;
use DevThis\KsqlDB\Statement;
use DevThis\KsqlDB\Factory\ClientFactory;
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
$coolDataCallback = new class implements \DevThis\KsqlDB\Interfaces\StreamCallback {
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

$stream = $client->execute($createStatement);

// Run event loop
// https://amphp.org/amp/event-loop/
\Amp\Loop::run(function () use ($client) {
    $stream = $client->streamAsync($streamStatement, $coolDataCallback);

    Loop::repeat(1000, static function() {
        // insert into stream example.
    });
    
    // Terminate stream after 100 seconds.
    Loop::delay(1000 * 100, static function () use ($client, $stream) {
        $client->terminateStream($stream->getQueryId());
    });
});
```

## Alternatives
- https://github.com/ytake/php-ksql
- https://github.com/seanmorris/ksqlc
