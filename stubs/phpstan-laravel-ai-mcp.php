<?php

declare(strict_types=1);

namespace Laravel\Ai\Contracts {
    use Laravel\Ai\Tools\JsonSchema;
    use Laravel\Ai\Tools\Request;
    use Stringable;

    if (! interface_exists(Tool::class)) {
        interface Tool
        {
            public function name(): string;

            public function description(): string;

            /**
             * @return array<string, mixed>
             */
            public function schema(JsonSchema $schema): array;

            public function handle(Request $request): Stringable|string;
        }
    }
}

namespace Laravel\Ai\Tools {
    if (! class_exists(JsonSchema::class)) {
        class JsonSchema
        {
            /**
             * @return array<string, mixed>
             */
            public function string(string $description = ''): array
            {
                return ['type' => 'string', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function integer(string $description = ''): array
            {
                return ['type' => 'integer', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function boolean(string $description = ''): array
            {
                return ['type' => 'boolean', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function number(string $description = ''): array
            {
                return ['type' => 'number', 'description' => $description];
            }

            /**
             * @param  array<string, array<string, mixed>>  $properties
             * @param  list<string>  $required
             * @return array<string, mixed>
             */
            public function object(array $properties = [], array $required = []): array
            {
                return [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ];
            }
        }
    }

    if (! class_exists(Request::class)) {
        class Request
        {
            /**
             * @param  array<string, mixed>  $payload
             */
            public function __construct(
                private array $payload = [],
            ) {}

            /**
             * @return array<string, mixed>
             */
            public function toArray(): array
            {
                return $this->payload;
            }

            /**
             * @return array<string, mixed>
             */
            public function all(): array
            {
                return $this->payload;
            }

            /**
             * @return array<string, mixed>
             */
            public function input(): array
            {
                return $this->payload;
            }
        }
    }
}

namespace Laravel\Mcp\Server {
    use Laravel\Mcp\Request;
    use Laravel\Mcp\Response;
    use Laravel\Mcp\Schema;

    if (! class_exists(Tool::class)) {
        abstract class Tool
        {
            abstract public function description(): string;

            /**
             * @return array<string, mixed>
             */
            abstract public function schema(Schema $schema): array;

            abstract public function handle(Request $request): Response;
        }
    }
}

namespace Laravel\Mcp {
    if (! class_exists(Schema::class)) {
        class Schema
        {
            /**
             * @return array<string, mixed>
             */
            public function string(string $description = ''): array
            {
                return ['type' => 'string', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function integer(string $description = ''): array
            {
                return ['type' => 'integer', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function boolean(string $description = ''): array
            {
                return ['type' => 'boolean', 'description' => $description];
            }

            /**
             * @return array<string, mixed>
             */
            public function number(string $description = ''): array
            {
                return ['type' => 'number', 'description' => $description];
            }

            /**
             * @param  array<string, array<string, mixed>>  $properties
             * @param  list<string>  $required
             * @return array<string, mixed>
             */
            public function object(array $properties = [], array $required = []): array
            {
                return [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ];
            }
        }
    }

    if (! class_exists(Request::class)) {
        class Request
        {
            /**
             * @param  array<string, mixed>  $payload
             */
            public function __construct(
                private array $payload = [],
            ) {}

            /**
             * @return array<string, mixed>
             */
            public function toArray(): array
            {
                return $this->payload;
            }

            /**
             * @return array<string, mixed>
             */
            public function all(): array
            {
                return $this->payload;
            }

            /**
             * @return array<string, mixed>
             */
            public function input(): array
            {
                return $this->payload;
            }
        }
    }

    if (! class_exists(Response::class)) {
        class Response
        {
            private function __construct(
                public string $content,
                public bool $error = false,
            ) {}

            public static function text(string $content): self
            {
                return new self($content);
            }

            public static function error(string $message): self
            {
                return new self($message, true);
            }
        }
    }
}

namespace Laravel\Mcp\Facades {
    if (! class_exists(Mcp::class)) {
        class Mcp
        {
            /**
             * @param  class-string  $serverClass
             */
            public static function local(string $handle, string $serverClass): void
            {
                unset($handle, $serverClass);
            }

            public static function getLocalServer(string $handle): ?callable
            {
                unset($handle);

                return null;
            }
        }
    }
}
