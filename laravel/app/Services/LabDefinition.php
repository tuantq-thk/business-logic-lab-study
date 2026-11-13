<?php

namespace App\Services;

final readonly class LabDefinition
{
    /**
     * @param  non-empty-list<string>  $routeNames
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $descriptionKey,
        public string $groupKey,
        public string $questionKey,
        public bool $implemented,
        public bool $defaultEnabled,
        public bool $toggleable,
        public array $routeNames,
    ) {}

    /**
     * @param array{
     *     key: string,
     *     label_key: string,
     *     description_key: string,
     *     group_key: string,
     *     question_key: string,
     *     implemented: bool,
     *     default_enabled: bool,
     *     toggleable: bool,
     *     route_names: non-empty-list<string>
     * } $attributes
     */
    public static function fromConfig(array $attributes): self
    {
        return new self(
            key: $attributes['key'],
            labelKey: $attributes['label_key'],
            descriptionKey: $attributes['description_key'],
            groupKey: $attributes['group_key'],
            questionKey: $attributes['question_key'],
            implemented: $attributes['implemented'],
            defaultEnabled: $attributes['default_enabled'],
            toggleable: $attributes['toggleable'],
            routeNames: $attributes['route_names'],
        );
    }

    public function primaryRouteName(): string
    {
        return $this->routeNames[0];
    }

    /**
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [
            $this->labelKey,
            $this->descriptionKey,
            $this->groupKey,
            $this->questionKey,
        ];
    }
}
