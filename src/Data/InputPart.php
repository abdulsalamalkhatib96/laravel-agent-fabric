<?php

namespace Evolvex\AgentFabric\Data;

use Evolvex\AgentFabric\Enums\InputModality;

final readonly class InputPart
{
    public function __construct(
        public InputModality $modality,
        public string $value,
        public ?string $mimeType = null,
        public array $metadata = [],
    ) {}

    public static function text(string $value): self { return new self(InputModality::Text, $value, 'text/plain'); }
    public static function image(string $value, ?string $mimeType = null): self { return new self(InputModality::Image, $value, $mimeType); }
    public static function audio(string $value, ?string $mimeType = null): self { return new self(InputModality::Audio, $value, $mimeType); }
    public static function file(string $value, ?string $mimeType = null): self { return new self(InputModality::File, $value, $mimeType); }
}
