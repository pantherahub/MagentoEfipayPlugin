<?php

namespace EfipayPayment\Embebed\Model\Config;


use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'prod', 'label' => __('Produccion')],
            ['value' => 'test', 'label' => __('Pruebas')],
        ];
    }

}
