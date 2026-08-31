<?php

declare(strict_types=1);

namespace App\Landing\Domain\Playground;

final readonly class PlaygroundEndpoint
{
    /**
     * @param  array<string, mixed>  $sampleBody
     */
    public function __construct(
        public string $id,
        public string $path,
        public string $title,
        public string $summary,
        public array $sampleBody,
    ) {}

    public function publicPath(): string
    {
        return EndpointCatalog::API_PREFIX.$this->path;
    }

    /**
     * @return array{id: string, path: string, publicPath: string, title: string, summary: string, sampleBody: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'publicPath' => $this->publicPath(),
            'title' => $this->title,
            'summary' => $this->summary,
            'sampleBody' => $this->sampleBody,
        ];
    }
}
