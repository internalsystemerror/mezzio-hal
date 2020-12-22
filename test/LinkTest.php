<?php

/**
 * @see       https://github.com/mezzio/mezzio-hal for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-hal/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Hal;

use ArgumentCountError;
use InvalidArgumentException;
use Mezzio\Hal\Link;
use PHPUnit\Framework\TestCase;
use Psr\Link\EvolvableLinkInterface;

class LinkTest extends TestCase
{
    public function testRequiresRelation()
    {
        $this->expectException(ArgumentCountError::class);
        $link = new Link();
    }

    public function testCanConstructLinkWithRelation()
    {
        $link = new Link('self');
        $this->assertInstanceOf(Link::class, $link);
        $this->assertInstanceOf(EvolvableLinkInterface::class, $link);
        $this->assertEquals(['self'], $link->getRels());
        $this->assertEquals('', $link->getHref());
        $this->assertFalse($link->isTemplated());
        $this->assertEquals([], $link->getAttributes());
    }

    public function testCanConstructLinkWithRelationAndUri()
    {
        $link = new Link('self', 'https://example.com/api/link');
        $this->assertEquals(['self'], $link->getRels());
        $this->assertEquals('https://example.com/api/link', $link->getHref());
    }

    public function testCanConstructLinkWithRelationAndTemplatedFlag()
    {
        $link = new Link('self', '', true);
        $this->assertEquals(['self'], $link->getRels());
        $this->assertTrue($link->isTemplated());
    }

    public function testCanConstructLinkWithRelationAndAttributes()
    {
        $link = new Link('self', '', false, ['foo' => 'bar']);
        $this->assertEquals(['self'], $link->getRels());
        $this->assertEquals(['foo' => 'bar'], $link->getAttributes());
    }

    public function testCanConstructFullyPopulatedLink()
    {
        $link = new Link(
            ['self', 'link'],
            'https://example.com/api/link{/id}',
            true,
            ['foo' => 'bar']
        );
        $this->assertEquals(['self', 'link'], $link->getRels());
        $this->assertEquals('https://example.com/api/link{/id}', $link->getHref());
        $this->assertTrue($link->isTemplated());
        $this->assertEquals(['foo' => 'bar'], $link->getAttributes());
    }

    /**
     * @psalm-return array<string, array{0: mixed}>
     */
    public function invalidRelations(): array
    {
        return [
            'null'         => [null],
            'false'        => [false],
            'true'         => [true],
            'zero'         => [0],
            'int'          => [1],
            'zero-float'   => [0.0],
            'float'        => [1.1],
            'empty-string' => [''],
            'array'        => [['link']],
            'object'       => [(object) ['href' => 'link']],
        ];
    }

    /**
     * @dataProvider invalidRelations
     * @param mixed $rel
     */
    public function testWithRelRaisesExceptionForInvalidRelation($rel)
    {
        $link = new Link('self');
        $this->expectException(InvalidArgumentException::class);
        $link->withRel($rel);
    }

    public function testWithRelReturnsSameInstanceIfRelationIsAlreadyPresent()
    {
        $link = new Link('self');
        $new  = $link->withRel('self');
        $this->assertSame($link, $new);
    }

    public function testWithRelReturnsNewInstanceIfRelationIsNotAlreadyPresent()
    {
        $link = new Link('self');
        $new  = $link->withRel('link');
        $this->assertNotSame($link, $new);
        $this->assertEquals(['self'], $link->getRels());
        $this->assertEquals(['self', 'link'], $new->getRels());
    }

    /**
     * @dataProvider invalidRelations
     * @param mixed $rel
     */
    public function testWithoutRelReturnsSameInstanceIfRelationIsInvalid($rel)
    {
        $link = new Link('self');
        $new  = $link->withoutRel($rel);
        $this->assertSame($link, $new);
    }

    public function testWithoutRelReturnsSameInstanceIfRelationIsNotPresent()
    {
        $link = new Link('self');
        $new  = $link->withoutRel('link');
        $this->assertSame($link, $new);
    }

    public function testWithoutRelReturnsNewInstanceIfRelationCanBeRemoved()
    {
        $link = new Link(['self', 'link']);
        $new  = $link->withoutRel('link');
        $this->assertNotSame($link, $new);
        $this->assertEquals(['self', 'link'], $link->getRels());
        $this->assertEquals(['self'], $new->getRels());
    }

    /**
     * @psalm-return array<string, array{0: mixed}>
     */
    public function invalidUriTypes(): array
    {
        return [
            'null'         => [null],
            'false'        => [false],
            'true'         => [true],
            'zero'         => [0],
            'int'          => [1],
            'zero-float'   => [0.0],
            'float'        => [1.1],
            'array'        => [['link']],
            'plain-object' => [(object) ['href' => 'link']],
        ];
    }

    /**
     * @dataProvider invalidUriTypes
     * @param mixed $uri
     */
    public function testWithHrefRaisesExceptionForInvalidUriType($uri)
    {
        $link = new Link('self');
        $this->expectException(InvalidArgumentException::class);
        $link->withHref($uri);
    }

    /**
     * @psalm-return iterable<string, array{0: string|object}>
     */
    public function validUriTypes(): iterable
    {
        yield 'string' => ['https://example.com/api/link'];
        yield 'castable-object' => [new TestAsset\Uri('https://example.com/api/link')];
    }

    /**
     * @dataProvider validUriTypes
     * @param string|object $uri
     */
    public function testWithHrefReturnsNewInstanceWhenUriIsValid($uri)
    {
        $link = new Link('self', 'https://example.com');
        $new  = $link->withHref($uri);
        $this->assertNotSame($link, $new);
        $this->assertNotEquals((string) $uri, $link->getHref());
        $this->assertEquals((string) $uri, $new->getHref());
    }

    /**
     * @psalm-return array<string, array{0: mixed}>
     */
    public function invalidAttributeNames(): array
    {
        return [
            'null'         => [null],
            'false'        => [false],
            'true'         => [true],
            'zero'         => [0],
            'int'          => [1],
            'zero-float'   => [0.0],
            'float'        => [1.1],
            'empty-string' => [''],
            'array'        => [['attribute']],
            'object'       => [(object) ['name' => 'attribute']],
        ];
    }

    /**
     * @dataProvider invalidAttributeNames
     * @param mixed $name
     */
    public function testWithAttributeRaisesExceptionForInvalidAttributeName($name)
    {
        $link = new Link('self');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$name');
        $link->withAttribute($name, 'foo');
    }

    /**
     * @psalm-return array<string, array{0: mixed}>
     */
    public function invalidAttributeValues(): array
    {
        return [
            'array-with-non-string-values' => [[null, false, true, 0, 0.0, 1, 1.1, 'foo']],
            'object'                       => [(object) ['name' => 'attribute']],
        ];
    }

    /**
     * @dataProvider invalidAttributeValues
     * @param mixed $value
     */
    public function testWithAttributeRaisesExceptionForInvalidAttributeValue($value)
    {
        $link = new Link('self');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$value');
        $link->withAttribute('foo', $value);
    }

    /**
     * @psalm-return array<string, array{0: string, 1: mixed}>
     */
    public function validAttributes(): array
    {
        return [
            'false'      => ['foo', false],
            'true'       => ['foo', true],
            'zero'       => ['foo', 0],
            'int'        => ['foo', 1],
            'zero-float' => ['foo', 0.0],
            'float'      => ['foo', 1.1],
            'string'     => ['foo', 'bar'],
            'string[]'   => ['foo', ['bar', 'baz']],
        ];
    }

    /**
     * @dataProvider validAttributes
     * @param mixed $value
     */
    public function testWithAttributeReturnsNewInstanceForValidAttribute(string $name, $value)
    {
        $link = new Link('self');
        $new  = $link->withAttribute($name, $value);
        $this->assertNotSame($link, $new);
        $this->assertEquals([], $link->getAttributes());
        $this->assertEquals([$name => $value], $new->getAttributes());
    }

    /**
     * @dataProvider invalidAttributeNames
     * @param mixed $name
     */
    public function testWithoutAttributeReturnsSameInstanceWhenAttributeNameIsInvalid($name)
    {
        $link = new Link('self');
        $new  = $link->withoutAttribute($name);
        $this->assertSame($link, $new);
    }

    public function testWithoutAttributeReturnsSameInstanceWhenAttributeIsNotPresent()
    {
        $link = new Link('self', '', false, ['foo' => 'bar']);
        $new  = $link->withoutAttribute('bar');
        $this->assertSame($link, $new);
    }

    public function testWithoutAttributeReturnsNewInstanceWhenAttributeCanBeRemoved()
    {
        $link = new Link('self', '', false, ['foo' => 'bar']);
        $new  = $link->withoutAttribute('foo');
        $this->assertNotSame($link, $new);
        $this->assertEquals(['foo' => 'bar'], $link->getAttributes());
        $this->assertEquals([], $new->getAttributes());
    }
}
