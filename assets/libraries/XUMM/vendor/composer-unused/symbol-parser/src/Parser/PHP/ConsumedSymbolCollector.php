<?php

declare(strict_types=1);

namespace ComposerUnused\SymbolParser\Parser\PHP;

use ComposerUnused\SymbolParser\Parser\PHP\Strategy\StrategyInterface;
use PhpParser\Node;

use function array_merge;
use function array_unique;

/**
 * Collect consumed symbols.
 *
 * Consumed symbols, are symbols used by your code.
 *
 * These might be classes, functions or constants
 */
class ConsumedSymbolCollector extends AbstractCollector
{
    /** @var array<string> */
    private $symbols = [];
    /** @var array<StrategyInterface> */
    private $strategies;

    /**
     * @param array<StrategyInterface> $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function enterNode(Node $node)
    {
        $symbols = [];

        $this->followIncludes($node);

        foreach ($this->strategies as $strategy) {
            if (!$strategy->canHandle($node)) {
                continue;
            }

            $symbols[] = $strategy->extractSymbolNames($node);
        }

        if (count($symbols) > 0) {
            $this->symbols = array_merge($this->symbols, ...$symbols);
        }

        return null;
    }

    public function getSymbolNames(): array
    {
        return array_unique($this->symbols);
    }

    public function reset(): void
    {
        $this->symbols = [];
    }
}
