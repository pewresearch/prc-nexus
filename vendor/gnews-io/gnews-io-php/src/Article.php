<?php

namespace GNews;

class Article
{
    public string $title;
    public ?string $description;
    public ?string $content;
    public ?string $url;
    public ?string $image;
    public ?string $publishedAt;
    public array $source;

    public function __construct(array $data)
    {
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->content = $data['content'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->image = $data['image'] ?? null;
        $this->publishedAt = $data['publishedAt'] ?? null;
        $this->source = $data['source'] ?? [];
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPublishedAt(): ?string
    {
        return $this->publishedAt;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function getSourceName(): ?string
    {
        return $this->source['name'] ?? null;
    }

    public function getSourceUrl(): ?string
    {
        return $this->source['url'] ?? null;
    }
}