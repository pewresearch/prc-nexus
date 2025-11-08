<?php

namespace GNews;

class Article
{
    public string $id;
    public string $title;
    public ?string $description;
    public ?string $content;
    public string $url;
    public ?string $image;
    public string $publishedAt;
    public string $lang;
    public array $source;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->content = $data['content'];
        $this->url = $data['url'];
        $this->image = $data['image'];
        $this->publishedAt = $data['publishedAt'];
        $this->lang = $data['lang'];
        $this->source = $data['source'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getPublishedAt(): string
    {
        return $this->publishedAt;
    }

        public function getLang(): string
    {
        return $this->lang;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function getSourceId(): string
    {
        return $this->source['id'];
    }

    public function getSourceName(): string
    {
        return $this->source['name'];
    }

    public function getSourceUrl(): string
    {
        return $this->source['url'];
    }

    public function getSourceCountry(): string
    {
        return $this->source['country'];
    }
}