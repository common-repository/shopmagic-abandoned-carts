<?php

namespace ShopMagicCartVendor\WPDesk\View\Resolver;

use ShopMagicCartVendor\WPDesk\View\Renderer\Renderer;
use ShopMagicCartVendor\WPDesk\View\Resolver\Exception\CanNotResolve;
/**
 * This resolver never finds the file
 *
 * @package WPDesk\View\Resolver
 */
class NullResolver implements \ShopMagicCartVendor\WPDesk\View\Resolver\Resolver
{
    public function resolve($name, \ShopMagicCartVendor\WPDesk\View\Renderer\Renderer $renderer = null)
    {
        throw new \ShopMagicCartVendor\WPDesk\View\Resolver\Exception\CanNotResolve('Null Cannot resolve');
    }
}
