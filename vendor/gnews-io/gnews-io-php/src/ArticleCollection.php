<?php

namespace GNews;

use ArrayAccess;
use Countable;
use Iterator;

class ArticleCollection implements ArrayAccess, Iterator, Countable
{
    private array $articles = [];
    private int $totalArticles;
    private int $position = 0;

    public function __construct(array $response)
    {
        $this->totalArticles = $response['totalArticles'] ?? 0;

        if (isset($response['articles']) && is_array($response['articles'])) {
            foreach ($response['articles'] as $articleData) {
                $this->articles[] = new Article($articleData);
            }
        }
    }

    public function getTotalArticles(): int
    {
        return $this->totalArticles;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->articles[$offset]);
    }

    public function offsetGet($offset): ?Article
    {
        return $this->articles[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->articles[] = $value;
        } else {
            $this->articles[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->articles[$offset]);
    }

    // Iterator implementation
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): ?Article
    {
        return $this->articles[$this->position] ?? null;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->articles[$this->position]);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->articles);
    }
}