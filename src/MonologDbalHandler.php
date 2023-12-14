<?php

declare(strict_types=1);

namespace TheDomeFfm\MonologDbalHandlerBundle;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class MonologDbalHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ?string $logTableName = null,
        protected Level $level = Level::Error,
        protected bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * @throws DbalException
     */
    protected function write(LogRecord $record): void
    {
        $logTableName = empty($this->logTableName) ? $this->getFallbackTableName() : $this->logTableName;

        $this->ensureTableExist($logTableName);

        $row = [
            ...$this->getDefaultData($record),
            ...$this->getAdditionalData($record),
        ];

        $this->connection->insert($logTableName, $row);
    }

    protected function getFallbackTableName(): string
    {
        return 'monolog_log';
    }

    protected function ensureTableExist(string $logTableName): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist($logTableName)) {
            return;
        }

        $schemaManager->createTable($this->getTableSchema($logTableName));
    }

    protected function getTableSchema(string $logTableName): Table
    {
        $table = new Table($logTableName);
        $table->addColumn(
            'id',
            Types::INTEGER,
            ['autoincrement' => true, 'unsigned' => true],
        );
        $table->setPrimaryKey(['id']);

        $table->addColumn(
            'timestamp',
            Types::DATETIMETZ_IMMUTABLE,
        );

        $table->addColumn(
            'level',
            Types::STRING,
            ['length' => 50],
        );

        $table->addColumn(
            'channel',
            Types::STRING,
        );

        $table->addColumn(
            'message',
            Types::JSON,
        );

        $table->addColumn(
            'context',
            Types::JSON,
            ['notnull' => false],
        );

        $table->addColumn(
            'extra',
            Types::JSON,
            ['notnull' => false],
        );

        return $table;

        return new Table(
            name: $logTableName,
            columns: [
                new Column(
                    'id',
                    Type::getType(Types::INTEGER),
                    ['autoincrement' => true]
                ),
                new Column(
                    'timestamp',
                    Type::getType(Types::DATETIMETZ_IMMUTABLE),
                ),
                new Column(
                    'level',
                    Type::getType(Types::STRING),
                    ['length' => 50],
                ),
                new Column(
                    'channel',
                    Type::getType(Types::STRING),
                ),
                new Column(
                    'message',
                    Type::getType(Types::JSON),
                ),
                new Column(
                    'context',
                    Type::getType(Types::JSON),
                    ['notnull' => false],
                ),
                new Column(
                    'extra',
                    Type::getType(Types::JSON),
                    ['notnull' => false],
                ),
            ],
        );
    }

    protected function getDefaultData(LogRecord $record): array
    {
        return [
            'timestamp' => $record->datetime->format('c'),
            'level' => $record->level->getName(),
            'channel' => $record->channel,
            'message' => $record->formatted,
            'context' => $this->serializeToJsonOrNull($record->context),
            'extra' => $this->serializeToJsonOrNull($record->extra),
        ];
    }

    protected function getAdditionalData(LogRecord $record): array
    {
        return [];
    }

    protected function serializeToJsonOrNull(array $data): ?string
    {
        if (empty($data)) {
            return null;
        }

        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $jsonException) {
            return json_encode([
                '_internal' => true,
                'error' => 'JSON serialization failed',
                'message' => $jsonException->getMessage(),
                'in_class' => self::class,
            ]);
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new DbalFormatter();
    }
}
