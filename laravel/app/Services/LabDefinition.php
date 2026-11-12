<?php

namespace App\Services;

final readonly class LabDefinition
{
    /**
     * @param  list<string>  $routeNames
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $group,
        public string $question,
        public bool $implemented,
        public bool $defaultEnabled,
        public bool $toggleable,
        public array $routeNames,
    ) {}

    /**
     * @param array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     group: string,
     *     question: string,
     *     implemented: bool,
     *     default_enabled: bool,
     *     toggleable: bool,
     *     route_names: list<string>
     * } $attributes
     */
    public static function fromConfig(array $attributes): self
    {
        return new self(
            key: $attributes['key'],
            label: $attributes['label'],
            description: $attributes['description'],
            group: $attributes['group'],
            question: $attributes['question'],
            implemented: $attributes['implemented'],
            defaultEnabled: $attributes['default_enabled'],
            toggleable: $attributes['toggleable'],
            routeNames: $attributes['route_names'],
        );
    }
}
