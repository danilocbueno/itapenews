<?php

namespace App\Storage;

use App\Data\Item;

/**
 * Notícias guardadas em data/items/AAAA-MM.json (um arquivo por mês).
 */
final class ItemRepository
{
    /** @var array<string, Item> */
    private array $items = [];

    /** @var array<string, true> meses alterados desde o load */
    private array $dirty = [];

    public function __construct(private string $directory)
    {
        foreach (glob($this->directory.'/*.json') ?: [] as $file) {
            foreach (JsonFile::read($file) ?? [] as $data) {
                $item = Item::fromArray($data);
                $this->items[$item->id()] = $item;
            }
        }
    }

    public function has(string $id): bool
    {
        return isset($this->items[$id]);
    }

    public function add(Item $item): void
    {
        $this->items[$item->id()] = $item;
        $this->dirty[$this->month($item)] = true;
    }

    /**
     * Mais recentes primeiro.
     *
     * @return list<Item>
     */
    public function all(): array
    {
        $items = array_values($this->items);
        usort($items, fn (Item $a, Item $b) => $b->date() <=> $a->date());

        return $items;
    }

    /**
     * @return list<Item>
     */
    public function since(\DateTimeInterface $from): array
    {
        return array_values(array_filter($this->all(), fn (Item $item) => $item->date() >= $from));
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return bool se algum arquivo mudou
     */
    public function save(): bool
    {
        $changed = false;

        foreach (array_keys($this->dirty) as $month) {
            $items = array_filter($this->all(), fn (Item $item) => $this->month($item) === $month);
            $changed = JsonFile::write(
                $this->directory."/{$month}.json",
                array_values(array_map(fn (Item $item) => $item->toArray(), $items)),
            ) || $changed;
        }

        $this->dirty = [];

        return $changed;
    }

    private function month(Item $item): string
    {
        return $item->date()->format('Y-m');
    }
}
