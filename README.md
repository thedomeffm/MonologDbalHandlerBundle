# MonologDbalHandlerBundle

Adds a handler to monolog which can persist your logs into your database.
The default table name is `monolog_log` and it uses the monolog json formatter to format the logs.

## Installation

`composer require thedomeffm/monolog-dbal-handler-bundle`

## Configuration

You *can* override the table name if you want.
I've not added a recipe (or what ever I need to create :shrug:), so you need to create the config by yourself.

```yaml
# thedomeffm_monolog_dbal_handler.yaml

thedomeffm_monolog_dbal_handler:
    dbal:
        log_table_name: "the_table_name_you_prefer"

```

Edit your monolog configuration
```yaml
# monolog.yaml

monolog:
    handlers:
        # your other handler...
        dbal:
            type: service
            id: thedomeffm_monolog_dbal_handler
```

Here is an example how a production config could look like:

```yaml
# monolog.yaml

when@prod:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: main_group
                excluded_http_codes: [404, 405]
                buffer_size: 50

            main_group:
                type: group
                members: ['error_stream', 'dbal']

            error_stream:
                type: stream
                path: php://stderr
                level: debug
                formatter: monolog.formatter.json

            dbal:
                type: service
                id: thedomeffm_monolog_dbal_handler

            # your other handler...
```
