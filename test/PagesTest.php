<?php declare(strict_types=1);

namespace Pages\Tests;

use ArrayObject;
use DateTimeImmutable;
use Pages\Exception\ItemException;
use Pages\Page;
use Pages\Pages;
use PHPUnit\Framework\TestCase;

class PagesTest extends TestCase
{
    // Fixtures
    public function validItemsProvider(): array
    {
        return [
            [["hello world"]],
            [array_fill(0, 5, "hello world")],
            [[new DateTimeImmutable()]],
            [["hello", "world", 1]],
            [[[], [], []]],
            [[(object) ["foo" => "bar"]]],
        ];
    }

    public function validItemCollectionProvider(): array
    {
        // Starting with the examples used for calls to append
        $collections = array_map(
            function ($argsArray) {
                return array_merge($argsArray, $argsArray);
            },
            $this->validItemsProvider()
        );
        $collections = $this->addGeneratorBasedIterable($collections);

        // ArrayObject item collection
        $fooBarArray = ["foo", "bar", "baz"];
        $collections[] = [new ArrayObject($fooBarArray), $fooBarArray];

        return $collections;
    }

    /**
     * @param iterable[] $collections
     * @return iterable[]
     */
    private function addGeneratorBasedIterable(array $collections): array
    {
        // Adding a collection that's an iterable created with a generator
        $lambdaGenerator = function ($somePhrase) {
            foreach (explode(" ", $somePhrase) as $word) {
                yield $word;
            }
        };

        $phrase = "This is a silly test fixture";
        $collections[] = [$lambdaGenerator($phrase), explode(" ", $phrase)];

        return $collections;
    }

    public function invalidItemsProvider(): array
    {
        return [
            [null],
        ];
    }

    public function invalidItemCollectionProvider(): array
    {
        return [
            [[null]]
        ];
    }

    // Test Cases
    public function testEmpty()
    {
        $pages = Pages::empty();

        $this->assertEquals(new Page([]), $pages->current());
        $this->assertEquals([], $pages->getItems());
    }

    /** @dataProvider validItemsProvider */
    public function testAppend(array $items)
    {
        $pages = Pages::empty();
        foreach ($items as $item) {
            $pages->append($item);
        }

        $this->assertEquals(
            $items,
            $pages->getItems(),
            "The items returned from Pages::getItems did not match the values passed to Pages::append."
        );
    }

    /** @dataProvider invalidItemsProvider */
    public function testAppendThrowsException($item)
    {
        $pages = Pages::empty();

        $this->expectException(ItemException::class);
        $this->expectExceptionMessage(ItemException::itemWasNull()->getMessage());
        $pages->append($item);
    }

    /** @dataProvider validItemCollectionProvider */
    public function testAppendCollection(iterable $collection, array $expectedItems)
    {
        $pages = Pages::empty();
        $pages->appendCollection($collection);

        $this->assertEquals($expectedItems, $pages->getItems());
    }

    /** @dataProvider invalidItemCollectionProvider */
    public function testAppendCollectionThrowsException(array $collection)
    {
        $pages = Pages::empty();

        $this->expectException(ItemException::class);
        $this->expectExceptionMessage(ItemException::itemCollectionContainsNulls(array_keys($collection))->getMessage());
        $pages->appendCollection($collection);
    }
}
