define([
	'Magento_Payment/js/view/payment/cc-form',
	'jquery',
	'Magento_Payment/js/model/credit-card-validation/validator'
], function(Component, $) {
	'use strict';
	
	return Component.extend({
		defaults:{
			template: 'EfipayPayment_Embebed/payment/cc-form',
			code: 'efipaypayment_embebed'
		},

		getCode(){
			return this.code
		},

		isActive(){
			return this.getCode() === this.isChecked()
		},
		
		getSelector(field){
			return '#' + this.getCode() + '_' + field
		},

		validate(){
			const form = $(this.getSelector('payment-form'))
			form.validation()
			return form.valid()
		}
	})
});