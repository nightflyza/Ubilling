jQuery(document).ready(function(){
	$('input:radio[name="amount"]').change(function(){
		var amount=this.value;
		if(amount=='0'){
			$('[name=LMI_PAYMENT_AMOUNT]').val($('[name=amount_val]').val());
			$('[name=LMI_PAYMENT_DESC]').val('Internet');
		}else{
			$('[name=LMI_PAYMENT_AMOUNT]').val(amount);
			$('[name=LMI_PAYMENT_DESC]').val($('#tariff_'+this.value).html());
		}
	});
	
	$('[name=amount_val]').change(function(){
		$('[name=LMI_PAYMENT_AMOUNT]').val(this.value);
	});

	$('[name=LMI_PAYMENT_DESC]').val('Internet');
});