<?php declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;
ComponentRegistrar::register(
	ComponentRegistrar::MODULE,
	'EfipayPayment_Embebed',
	__DIR__
);