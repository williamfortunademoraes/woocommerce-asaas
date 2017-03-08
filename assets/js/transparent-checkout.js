/*global wc_pagseguro_params, PagSeguroDirectPayment, wc_checkout_params */
(function( $ ) {
	'use strict';

	$( function() {

		var pagseguro_submit = false;

		/**
		 * Set credit card brand.
		 *
		 * @param {string} brand
		 */
		function pagSeguroSetCreditCardBrand( brand ) {
			$( '#asaas-credit-card-form' ).attr( 'data-credit-card-brand', brand );
		}

		/**
		 * Format price.
		 *
		 * @param  {int|float} price
		 *
		 * @return {string}
		 */
		function pagSeguroGetPriceText( price ) {
			return 'R$ ' + parseFloat( price, 10 ).toFixed( 2 ).replace( '.', ',' ).toString();
		}

		/**
		 * Get installment option.
		 *
		 * @param  {object} installment
		 *
		 * @return {string}
		 */
		function pagSeguroGetInstallmentOption( installment ) {
			var interestFree = ( true === installment.interestFree ) ? ' ' + wc_pagseguro_params.interest_free : '';

			return '<option value="' + installment.quantity + '" data-installment-value="' + installment.installmentAmount + '">' + installment.quantity + 'x ' + pagSeguroGetPriceText( installment.installmentAmount ) + interestFree + '</option>';
		}

		/**
		 * Add error message
		 *
		 * @param {string} error
		 */
		function pagSeguroAddErrorMessage( error ) {
			var wrapper = $( '#asaas-credit-card-form' );

			$( '.woocommerce-error', wrapper ).remove();
			wrapper.prepend( '<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">' + error + '</div>' );
		}

		/**
		 * Hide payment methods if have only one.
		 */
		function pagSeguroHidePaymentMethods() {
			var paymentMethods = $( '#asaas-payment-methods' );

			if ( 1 === $( 'input[type=radio]', paymentMethods ).length ) {
				paymentMethods.hide();
			}
		}

		/**
		 * Show/hide the method form.
		 *
		 * @param {string} method
		 */
		function pagSeguroShowHideMethodForm( method ) {
			// window.alert( method );
			$( '.asaas-method-form' ).hide();
			$( '#asaas-payment-methods li' ).removeClass( 'active' );
			$( '#asaas-' + method + '-form' ).show();
			$( '#asaas-payment-method-' + method ).parent( 'label' ).parent( 'li' ).addClass( 'active' );
		}

		/**
		 * Initialize the payment form.
		 */
		function pagSeguroInitPaymentForm() {
			pagSeguroHidePaymentMethods();

			$( '#asaas-payment-form' ).show();

			pagSeguroShowHideMethodForm( $( '#asaas-payment-methods input[type=radio]:checked' ).val() );

			// CPF.
			$( '#asaas-card-holder-cpf' ).mask( '999.999.999-99', { placeholder: ' ' } );

			// Birth Date.
			$( '#asaas-card-holder-birth-date' ).mask( '99 / 99 / 9999', { placeholder: ' ' } );

			// Phone.
			$( '#asaas-card-holder-phone' ).focusout( function() {
				var phone, element;
				element = $( this );
				element.unmask();
				phone = element.val().replace( /\D/g, '' );

				if ( phone.length > 10 ) {
					element.mask( '(99) 99999-999?9', { placeholder: ' ' } );
				} else {
					element.mask( '(99) 9999-9999?9', { placeholder: ' ' } );
				}
			}).trigger( 'focusout' );

			$( '#asaas-bank-transfer-form input[type=radio]:checked' ).parent( 'label' ).parent( 'li' ).addClass( 'active' );
		}

		/**
		 * Form Handler.
		 *
		 * @return {bool}
		 */
		function pagSeguroformHandler() {
			if ( pagseguro_submit ) {
				pagseguro_submit = false;

				return true;
			}

			if ( ! $( '#payment_method_pagseguro' ).is( ':checked' ) ) {
				return true;
			}

			if ( 'credit-card' !== $( 'body li.payment_method_pagseguro input[name=pagseguro_payment_method]:checked' ).val() ) {
				$( 'form.checkout, form#order_review' ).append( $( '<input name="pagseguro_sender_hash" type="hidden" />' ).val( PagSeguroDirectPayment.getSenderHash() ) );

				return true;
			}

			var form = $( 'form.checkout, form#order_review' ),
				creditCardForm  = $( '#asaas-credit-card-form', form ),
				error           = false,
				errorHtml       = '',
				brand           = creditCardForm.attr( 'data-credit-card-brand' ),
				cardNumber      = $( '#asaas-card-number', form ).val().replace( /[^\d]/g, '' ),
				cvv             = $( '#asaas-card-cvc', form ).val(),
				expirationMonth = $( '#asaas-card-expiry', form ).val().replace( /[^\d]/g, '' ).substr( 0, 2 ),
				expirationYear  = $( '#asaas-card-expiry', form ).val().replace( /[^\d]/g, '' ).substr( 2 ),
				installments    = $( '#asaas-card-installments', form ),
				today           = new Date();

			// Validate the credit card data.
			errorHtml += '<ul>';

			// Validate the card brand.
			if ( typeof brand === 'undefined' || 'error' === brand ) {
				errorHtml += '<li>' + wc_pagseguro_params.invalid_card + '</li>';
				error = true;
			}

			// Validate the expiry date.
			if ( 2 !== expirationMonth.length || 4 !== expirationYear.length ) {
				errorHtml += '<li>' + wc_pagseguro_params.invalid_expiry + '</li>';
				error = true;
			}

			if ( ( 2 === expirationMonth.length && 4 === expirationYear.length ) && ( expirationMonth > 12 || expirationYear <= ( today.getFullYear() - 1 ) || expirationYear >= ( today.getFullYear() + 20 ) || ( expirationMonth < ( today.getMonth() + 2 ) && expirationYear.toString() === today.getFullYear().toString() ) ) ) {
				errorHtml += '<li>' + wc_pagseguro_params.expired_date + '</li>';
				error = true;
			}

			// Installments.
			if ( '0' === installments.val() ) {
				errorHtml += '<li>' + wc_pagseguro_params.empty_installments + '</li>';
				error = true;
			}

			errorHtml += '</ul>';

			// Create the card token.
			if ( ! error ) {
				PagSeguroDirectPayment.createCardToken({
					brand:           brand,
					cardNumber:      cardNumber,
					cvv:             cvv,
					expirationMonth: expirationMonth,
					expirationYear:  expirationYear,
					success: function( data ) {
						// Remove any old hash input.
						$( 'input[name=pagseguro_credit_card_hash], input[name=pagseguro_credit_card_hash], input[name=pagseguro_installment_value]', form ).remove();

						// Add the hash input.
						form.append( $( '<input name="pagseguro_credit_card_hash" type="hidden" />' ).val( data.card.token ) );
						form.append( $( '<input name="pagseguro_sender_hash" type="hidden" />' ).val( PagSeguroDirectPayment.getSenderHash() ) );
						form.append( $( '<input name="pagseguro_installment_value" type="hidden" />' ).val( $( 'option:selected', installments ).attr( 'data-installment-value' ) ) );

						// Submit the form.
						pagseguro_submit = true;
						form.submit();
					},
					error: function() {
						pagSeguroAddErrorMessage( wc_pagseguro_params.general_error );
					}
				});

			// Display the error messages.
			} else {
				pagSeguroAddErrorMessage( errorHtml );
			}

			return false;
		}

		// Transparent checkout actions.
		if ( true ) {
			// Initialize the transparent checkout.
			//PagSeguroDirectPayment.setSessionId( wc_pagseguro_params.session_id );

			// Display the payment for and init the input masks.
			if ( '1' === wc_checkout_params.is_checkout ) {
				$( 'body' ).on( 'updated_checkout', function() {
					pagSeguroInitPaymentForm();
				});
			} else {
				pagSeguroInitPaymentForm();
			}

			// Update the bank transfer icons classes.
			$( 'body' ).on( 'click', '#asaas-bank-transfer-form input[type=radio]', function() {
				$( '#asaas-bank-transfer-form li' ).removeClass( 'active' );
				$( this ).parent( 'label' ).parent( 'li' ).addClass( 'active' );
			});

			// Switch the payment method form.
			$( 'body' ).on( 'click', '#asaas-payment-methods input[type=radio]', function() {
				pagSeguroShowHideMethodForm( $( this ).val() );
			});

			// Get the credit card brand.
			$( 'body' ).on( 'focusout', '#asaas-card-number', function() {
				var bin = $( this ).val().replace( /[^\d]/g, '' ).substr( 0, 6 ),
					instalmments = $( 'body #asaas-card-installments' );

				if ( 6 === bin.length ) {
					// Reset the installments.
					instalmments.empty();
					instalmments.attr( 'disabled', 'disabled' );

					PagSeguroDirectPayment.getBrand({
						cardBin: bin,
						success: function( data ) {
							$( 'body' ).trigger( 'pagseguro_credit_card_brand', data.brand.name );
							pagSeguroSetCreditCardBrand( data.brand.name );
						},
						error: function() {
							$( 'body' ).trigger( 'pagseguro_credit_card_brand', 'error' );
							pagSeguroSetCreditCardBrand( 'error' );
						}
					});
				}
			});

			// Set the errors.
			$( 'body' ).on( 'focus', '#asaas-card-number, #asaas-card-expiry', function() {
				$( '#asaas-credit-card-form .woocommerce-error' ).remove();
			});

			// Get the installments.
			$( 'body' ).on( 'pagseguro_credit_card_brand', function( event, brand ) {
				if ( 'error' !== brand ) {
					PagSeguroDirectPayment.getInstallments({
						amount: $( 'body #asaas-cart-total' ).val(),
						brand: brand,
						success: function( data ) {
							var instalmments = $( 'body #asaas-card-installments' );

							if ( false === data.error ) {
								instalmments.empty();
								instalmments.removeAttr( 'disabled' );
								instalmments.append( '<option value="0">--</option>' );

								$.each( data.installments[brand], function( index, installment ) {
									instalmments.append( pagSeguroGetInstallmentOption( installment ) );
								});
							} else {
								pagSeguroAddErrorMessage( wc_pagseguro_params.invalid_card );
							}
						},
						error: function() {
							pagSeguroAddErrorMessage( wc_pagseguro_params.invalid_card );
						}
					});
				} else {
					pagSeguroAddErrorMessage( wc_pagseguro_params.invalid_card );
				}
			});

			// Process the credit card data when submit the checkout form.
			$( 'form.checkout' ).on( 'checkout_place_order_pagseguro', function() {
				return pagSeguroformHandler();
			});

			$( 'form#order_review' ).submit( function() {
				return pagSeguroformHandler();
			});

		} else {
			$( 'body' ).on( 'updated_checkout', function() {
				$( '#asaas-payment-form' ).remove();
			});
		}
	});

}( jQuery ));
