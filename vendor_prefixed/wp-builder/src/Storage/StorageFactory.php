<?php

namespace ShopMagicCartVendor\WPDesk\PluginBuilder\Storage;

class StorageFactory
{
    /**
     * @return PluginStorage
     */
    public function create_storage()
    {
        return new \ShopMagicCartVendor\WPDesk\PluginBuilder\Storage\WordpressFilterStorage();
    }
}
