define([
	'uiComponent',
	'Magento_Checkout/js/model/payment/renderer-list'
], function(Component, rendererList){
	'use_strict';

	rendererList.push({
		type: 'efipaypayment_embebed',
		component: 'EfipayPayment_Embebed/js/view/payment/method-renderer/cc-form'
	});

	return Component.extend({});
});