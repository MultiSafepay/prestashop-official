<?php declare(strict_types=1);

namespace MultiSafepay\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

abstract class BaseMultiSafepayTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $locator = new FileLocator(_PS_MODULE_DIR_ . 'multisafepayofficial/config');
        $loader = new YamlFileLoader($this->container, $locator);
        $loader->load('services.yml');
        $this->container->compile();
    }
}
