<?php

use PHPUnit\Framework\TestCase;
use PrestaShopAgenticCommerce\Pricing\PublicPricingResolver;

if (!class_exists('Customer')) {
    class Customer
    {
        public $id;

        public function __construct(int $id = 0)
        {
            $this->id = $id;
        }
    }
}

if (!class_exists('Cart')) {
    class Cart
    {
    }
}

if (!class_exists('Context')) {
    class Context
    {
        public $customer;
        public $cart;
        public $currency;
    }
}

if (!class_exists('Validate')) {
    class Validate
    {
        public static function isLoadedObject($object): bool
        {
            return is_object($object) && isset($object->id) && (int) $object->id > 0;
        }
    }
}

if (!class_exists('Product')) {
    class Product
    {
        public static $_taxCalculationMethod = 7;
        public static array $initCalls = [];
        public static array $priceCall = [];

        public static function initPricesComputation($idCustomer = null): void
        {
            self::$initCalls[] = $idCustomer;
            self::$_taxCalculationMethod = $idCustomer === null ? 0 : 100 + (int) $idCustomer;
        }

        public static function getPriceStatic(...$arguments): float
        {
            self::$priceCall = $arguments;
            return 12.345678;
        }
    }
}

final class PublicPricingResolverTest extends TestCase
{
    protected function setUp(): void
    {
        Product::$_taxCalculationMethod = 7;
        Product::$initCalls = [];
        Product::$priceCall = [];
    }

    public function testAnonymousPricingRestoresOriginalRequestState(): void
    {
        $context = new Context();
        $originalCustomer = new Customer(42);
        $originalCart = new Cart();
        $context->customer = $originalCustomer;
        $context->cart = $originalCart;
        $context->currency = (object) ['iso_code' => 'try'];

        $result = (new PublicPricingResolver())->resolve(123, 456, 5.0, $context);

        self::assertSame(12.345678, $result->price());
        self::assertSame('TRY', $result->currency());
        self::assertSame($originalCustomer, $context->customer);
        self::assertSame($originalCart, $context->cart);
        self::assertSame([null, 42], Product::$initCalls);
        self::assertSame(142, Product::$_taxCalculationMethod);

        // getPriceStatic argument 7 is the quantity parameter.
        self::assertSame(5.0, Product::$priceCall[7]);
        self::assertSame(456, Product::$priceCall[2]);
        self::assertSame(0, Product::$priceCall[9]);
        self::assertSame(0, Product::$priceCall[10]);
    }
}
