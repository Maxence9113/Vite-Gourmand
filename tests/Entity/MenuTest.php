<?php

namespace App\Tests\Entity;

use App\Entity\Menu;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    private Menu $menu;

    protected function setUp(): void
    {
        $this->menu = new Menu();
    }

    public function testStockCanBeSet(): void
    {
        $this->menu->setStock(10);
        $this->assertSame(10, $this->menu->getStock());
    }

    public function testStockCanBeNull(): void
    {
        $this->menu->setStock(null);
        $this->assertNull($this->menu->getStock());
    }

    public function testIsAvailableWithNullStock(): void
    {
        $this->menu->setStock(null);
        $this->assertTrue($this->menu->isAvailable());
    }

    public function testIsAvailableWithStockGreaterThanMinimum(): void
    {
        $this->menu->setNbPersonMin(10);
        $this->menu->setStock(15);
        $this->assertTrue($this->menu->isAvailable());
    }

    public function testIsAvailableWithStockEqualToMinimum(): void
    {
        $this->menu->setNbPersonMin(10);
        $this->menu->setStock(10);
        $this->assertTrue($this->menu->isAvailable());
    }

    public function testIsAvailableWithStockLessThanMinimum(): void
    {
        $this->menu->setNbPersonMin(10);
        $this->menu->setStock(5);
        $this->assertFalse($this->menu->isAvailable());
    }

    public function testIsAvailableWithZeroStock(): void
    {
        $this->menu->setNbPersonMin(10);
        $this->menu->setStock(0);
        $this->assertFalse($this->menu->isAvailable());
    }

    public function testDecrementStockWithNullStock(): void
    {
        $this->menu->setStock(null);
        $this->menu->decrementStock();
        $this->assertNull($this->menu->getStock());
    }

    public function testDecrementStockWithPositiveStock(): void
    {
        $this->menu->setStock(10);
        $this->menu->decrementStock();
        $this->assertSame(9, $this->menu->getStock());
    }

    public function testDecrementStockByCustomQuantity(): void
    {
        $this->menu->setStock(10);
        $this->menu->decrementStock(3);
        $this->assertSame(7, $this->menu->getStock());
    }

    public function testDecrementStockCannotGoNegative(): void
    {
        $this->menu->setStock(2);
        $this->menu->decrementStock(5);
        $this->assertSame(0, $this->menu->getStock());
    }

    public function testDecrementStockFromOne(): void
    {
        $this->menu->setNbPersonMin(1);
        $this->menu->setStock(1);
        $this->menu->decrementStock();
        $this->assertSame(0, $this->menu->getStock());
        $this->assertFalse($this->menu->isAvailable());
    }
}
