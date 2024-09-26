/* Javascript library of module advancedproductsearch */
jQuery(function ($) {


	/****************************************************************/
	/* recherche de produit rapide sur formulaire d'ajout de ligne  */
	/****************************************************************/
	$(document).on("submit", "#product-search-dialog-form" , function(event) {
		event.preventDefault();
		AdvancedProductSearch.discountLoadSearchProductDialogForm("&"+$( this ).serialize());
	});

	// Pagination
	$(document).on("click", "#product-search-dialog-form .pagination a, #product-search-dialog-form .advanced-product-search-sort-link" , function(event) {
		event.preventDefault();
		let urlParams = $(this).attr('href').split('?')[1];
		AdvancedProductSearch.discountLoadSearchProductDialogForm("&"+urlParams);
	});

	let ctrlPressed = false; //Variable to check if the the first button is pressed at this exact moment
	$(document).keydown(function(e) {
		if (e.ctrlKey) { //If it's ctrl key
			ctrlPressed = true; //Set variable to true
		}
	}).keyup(function(e) { //If user releases ctrl button
		if (e.ctrlKey) {
			ctrlPressed = false; //Set it to false
		}
	}); //This way you know if ctrl key is pressed. You can change e.ctrlKey to any other key code you want

	$(document).keydown(function(e) { //For any other keypress event
		if (e.which == 39) { //Checking if it's right button
			if(ctrlPressed == true){ //If it's space, check if ctrl key is also pressed
				if($('.paginationnext')){
					$('.paginationnext').trigger("click");
				}
				ctrlPressed = false; //Important! Set ctrlPressed variable to false. Otherwise the code will work everytime you press the space button again
			}
		}
	})


	$(document).keydown(function(e) { //For any other keypress event
		if (e.which == 37) { //Checking if it's left button
			if(ctrlPressed == true){ //If it's space, check if ctrl key is also pressed
				if($('.paginationprevious')){
					$('.paginationprevious').trigger("click");
				}
				ctrlPressed = false; //Important! Set ctrlPressed variable to false. Otherwise the code will work everytime you press the space button again
			}
		}
	})


	//_________________________________________________
	// RECHERCHE GLOBALE AUTOMATIQUE SUR FIN DE SAISIE
	// (Uniquement sur la recherche globale)

	//setup before functions
	var typingProductSearchTimer;                //timer identifier
	var doneTypingProductSearchInterval = 2000;  //time in ms (2 seconds)
	var typingProductSearchLastValue = '';

	$(document).on("keyup", "#search-all-form-input" , function(event) {
		clearTimeout(typingProductSearchTimer);
		if ($('#search-all-form-input').val() != typingProductSearchLastValue) {
			typingProductSearchLastValue = $('#search-all-form-input').val();
			typingProductSearchTimer = setTimeout(function(){
				AdvancedProductSearch.discountLoadSearchProductDialogForm("&"+$( "#product-search-dialog-form" ).serialize(), true);
			}, doneTypingProductSearchInterval);
		}
	});



	$(document).on("change", "[name^=prodfourprice]", function() {
		// limité au côté fournisseur
		if(AdvancedProductSearch.isSupplierDocument()){
			let fk_product = $(this).attr("data-product");
			let fourn_qty = $(this).find(':selected').data('fourn_qty');
			let fourn_qty_field = $("#advanced-product-search-list-input-qty-" + fk_product);

			$("#advanced-product-search-list-input-subprice-" + fk_product).val($(this).find(':selected').data('up'));
			if (fourn_qty != undefined) {
				if (fourn_qty > fourn_qty_field.val()) {
					fourn_qty_field.val($(this).find(':selected').data('fourn_qty'));
				}
				fourn_qty_field.attr('min', $(this).find(':selected').data('fourn_qty'));
			} else {
				fourn_qty_field.attr('min', 0);
			}
			$("#advanced-product-search-list-input-subprice-" + fk_product).trigger('change');
		}
	});

	// Update prices display
	$(document).on("change", ".on-update-calc-prices" , function(event) {
		let fk_product = $(this).attr("data-product");
		AdvancedProductSearch.updateLinePricesCalcs(fk_product)
	});

	$(document).on("keyup", ".on-update-calc-prices" , function(event) {
		let fk_product = $(this).attr("data-product");
		AdvancedProductSearch.updateLinePricesCalcs(fk_product);
	});


	// Ajout de produits sur click du bouton
	$(document).on("click", ".advance-prod-search-list-action-btn.--addProductToLine" , function(event) {
		event.preventDefault();
		let fk_product = $(this).attr("data-product");
		AdvancedProductSearch.addProductToCurentDocument(fk_product);
	});


	//_______________
	// LA DIALOG BOX

	$(document).on("click", '#product-search-dialog-button', function(event) {
		event.preventDefault();

		let element = $(this).attr('data-target-element');
		let fk_element = $(this).attr('data-target-id');
		let token = $(this).closest('form').find('input[name=token]').val();
		let search_idprod = $('#search_idprod').val();
		if (search_idprod === undefined) search_idprod = '';
		let productSearchDialogBox = "product-search-dialog-box";
		// crée le calque qui sera convertie en popup
		$('body').append('<div id="'+productSearchDialogBox+'" title="' + AdvancedProductSearch.discountlang.SearchProduct + '"></div>');

		// transforme le calque en popup
		$('#'+productSearchDialogBox).dialog({
			autoOpen: true,
			modal: true,
			width: Math.min($( window ).width() - 50, 1700),
			dialogClass: 'discountrule-product-search-box',
			buttons: [
				{
					text: AdvancedProductSearch.discountlang.CloseDialog,
					"class": 'ui-state-information',
					click: function () {
						$(this).dialog("close");
						$('#'+productSearchDialogBox).remove();
					}
				}
			],
			close: function( event, ui ) {
				if(AdvancedProductSearch.dialogCountAddedProduct>0){
					// si une ligne a été ajoutée, recharge la page actuelle
					//document.location.reload(); // mis en commentaire pour eviter le re-post des data et action
					let url = new URL(window.location.href);
					let urlActionParam = url.searchParams.get("action"); // check actions
					if(urlActionParam != undefined && urlActionParam.length > 0){
						window.location = AdvancedProductSearch.updateURLParameter(window.location.href, "action", ''); // on vide la partie action
					}
					else{
						window.location = window.location.href;
					}
				}
			},
			open: function( event, ui ) {
				//$(this).dialog('option', 'maxHeight', $(window).height()-30);
				AdvancedProductSearch.element = element;
				AdvancedProductSearch.fk_element = fk_element;
				AdvancedProductSearch.newToken = token;

				let displayResult = search_idprod.length > 0 ? 1 : AdvancedProductSearch.config.displayResultsOnOpen;
				AdvancedProductSearch.discountLoadSearchProductDialogForm('&element=' + element + '&fk_element=' + fk_element + '&displayResults=' + displayResult + '&sall=' + search_idprod, true);
				$('#'+productSearchDialogBox).parent().css('z-index', 1002);
				$('.ui-widget-overlay').css('z-index', 1001);
			}
		});
	});


	/****************************************************************/
	/*               Compatibilité avec discount rules              */
	/****************************************************************/

	// Recherche de remise sur modification des quantités
	// Un timer est ajouté pour eviter de spam les requêtes ajax (notamment à cause des boutons + et - des input de type number)
	// setup before functions
	var typingQtySearchDiscountTimer;                //timer identifier
	var doneTypingQtySearchDiscountInterval = 200;  //time in ms (0.2 seconds)
	$(document).on("change", ".advanced-product-search-list-input-qty" , function(event) {
		if(DiscountRule != undefined && !AdvancedProductSearch.isSupplierDocument()){ // il faut bien vérifier que discountrule est présent
			var fk_product = $(this).attr("data-product");
			clearTimeout(typingQtySearchDiscountTimer);
			typingQtySearchDiscountTimer = setTimeout(function(){
				DiscountRule.discountUpdate(
					fk_product,
					$("#product-search-dialog-form").find("input[name=fk_company]").val(),
					$("#advancedproductsearch-form-fk-project").val(),
					"#advanced-product-search-list-input-qty-"+fk_product,
					"#advanced-product-search-list-input-subprice-"+fk_product,
					"#advanced-product-search-list-input-reduction-"+fk_product,
					"#advancedproductsearch-form-default-customer-reduction"
				);
			}, doneTypingQtySearchDiscountInterval);
		}
	});
});



/**
 * permet de faire un addClass qui reload les animations si la class etait deja la
 */
(function ( $ ) {
	$.fn.addClassReload = function(className) {
		return this.each(function() {
			let $element = $(this);
			// Do something to each element here.
			$element.removeClass(className).width;
			setTimeout(function(){ $element.addClass(className); }, 0);
		});
	};
}( jQuery ));

// Utilisation d'une sorte de namespace en JS
AdvancedProductSearch = {};
(function(o) {

	o.lastidprod = 0;
	o.lastqty = 0;
	o.newToken = '';

	o.productSearchDialogBox = "product-search-dialog-box";

	// lang par défaut, les valeurs son ecrasées lors du chargement de la page en fonction de la langue
	o.discountlang = {
		"Saved":"Sauvegard\u00e9",
		"errorAjaxCall":"Erreur d'appel ajax",
		"SearchProduct":"Recherche de produits\/services",
		"CloseDialog":"Fermer"
	};


	// config par défaut, les valeurs son ecrasées lors du chargement de la page
	o.config = {
		"MAIN_MAX_DECIMALS_TOT":2,
		"MAIN_MAX_DECIMALS_UNIT":5,
		"interface_url":"advancedproductsearch\/scripts\/interface.php",
		"js_url":"advancedproductsearch\/js\/advancedproductsearch.js",
		'displayResultsOnOpen': 0,
		"supplierElements":[
			"supplier_proposal",
			"order_supplier",
			"invoice_supplier"
		]
	};

	o.dialogCountAddedProduct = 0;

	o.element = '';
	o.fk_element = 0;

	/**
	 * Load product search dialog form
	 *
	 * @param $morefilters
	 */
	o.discountLoadSearchProductDialogForm = function (morefilters = '', focus = false){

		$('#'+o.productSearchDialogBox).addClass('--ajax-loading');

		$('#'+o.productSearchDialogBox).prepend($('<div class="inner-dialog-overlay"><div class="dialog-loading__loading"><div class="dialog-loading__spinner-wrapper"><span class="dialog-loading__spinner-text">LOADING</span><span class="dialog-loading__spinner"></span></div></div></div>'));

		$('#'+o.productSearchDialogBox).load( o.config.interface_url + '?action=product-search-form&token=' + o.newToken + morefilters, function() {
			o.dialogCountAddedProduct = 0; // init count of product added for reload action
			if(focus){
				o.focusAtEndSearchInput($("#search-all-form-input"));
			}

			if($('#'+o.productSearchDialogBox).outerHeight() >= $( window ).height()-150 ){
				$('#'+o.productSearchDialogBox).dialog( "option", "position", { my: "top", at: "top", of: window } ); // Hack to position the dialog box after ajax load
			}
			else{
				$('#'+o.productSearchDialogBox).dialog( "option", "position", { my: "center", at: "center", of: window } ); // Hack to center vertical the dialog box after ajax load
			}

			o.initToolTip($('#'+o.productSearchDialogBox+' .classfortooltip')); // restore tooltip after ajax call
			$('#'+o.productSearchDialogBox).removeClass('--ajax-loading');
		});
	}


	/**
	 * affectation du contenu dans l'attribut title
	 *
	 * @param $element
	 * @param text
	 */
	o.setToolTip = function ($element, text){
		$element.attr("title",text);
		o.initToolTip($element);
	}


	/**
	 * initialisation de la tootip
	 * @param element
	 */
	o.initToolTip = function (element){

		if(!element.data("tooltipset")){
			element.data("tooltipset", true);
			element.tooltip({
				show: { collision: "flipfit", effect:"toggle", delay:50 },
				hide: { delay: 50 },
				tooltipClass: "mytooltip",
				content: function () {
					return $(this).prop("title");		/* To force to get title as is */
				}
			});
		}
	}

	/**
	 *
	 * @param msg
	 * @param status
	 */
	o.setEventMessage = function (msg, status = true){

		if(msg.length > 0){
			if(status){
				$.jnotify(msg, 'notice', {timeout: 5},{ remove: function (){} } );
			}
			else{
				$.jnotify(msg, 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
			}
		}
		else{
			$.jnotify('ErrorMessageEmpty', 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
		}
	}


	/**
	 * Permet de désactiver/activer les inputs/bouttons de formulaire d'une ligne de produit et ajoute quelques animations
	 * @param fk_product
	 * @param disable
	 */
	o.disableAddProductFields = function (fk_product, disable = true){

		var timer = 0
		if(!disable){
			timer = 1000; // Add timer on reactivate to avoid doubleclick
		}

		var buttonAddProduct = $(".discount-prod-list-action-btn[data-product="+fk_product+"]");

		setTimeout(function() {
			if(!disable){
				timer = 500; // Add timer on reactivate to avoid doubleclick
				buttonAddProduct.find('.add-btn-icon').removeClass('fa-spinner fa-pulse').addClass('fa-plus');
			}else{
				buttonAddProduct.find('.add-btn-icon').removeClass('fa-plus').addClass('fa-spinner fa-pulse');
			}

			$("#advanced-product-search-list-input-qty-"+fk_product).prop("disabled",disable);
			buttonAddProduct.prop("disabled",disable);
			$("#advanced-product-search-list-input-subprice-"+fk_product).prop("disabled",disable);
			$("#advanced-product-search-list-input-reduction-"+fk_product).prop("disabled",disable);

			// check if fournprice exist becaus it could be not activated
			let fk_fournprice = $("#prodfourprice-" + fk_product);
			if(fk_fournprice.length > 0){
				fk_fournprice.prop("disabled",disable);
			}

		}, timer);
	}


	/**
	 * Met a jour les calcules de prix basé sur les données des input
	 * @param fk_product
	 */
	o.updateLinePricesCalcs = function (fk_product){

		inputQty = $("#advanced-product-search-list-input-qty-"+fk_product);
		inputSubPrice = $("#advanced-product-search-list-input-subprice-"+fk_product);
		inputReduction = $("#advanced-product-search-list-input-reduction-"+fk_product);


		let qty = Number(inputQty.val());
		let subPrice = Number(inputSubPrice.val());
		let reduction = Number(inputReduction.val());
		if(reduction>100){
			reduction = 100;
			inputReduction.val(reduction);
		}

		let finalUnitPrice = subPrice - (subPrice * reduction / 100);
		finalUnitPrice = Number(finalUnitPrice.toFixed(o.config.MAIN_MAX_DECIMALS_UNIT));

		let finalPrice = finalUnitPrice*qty;
		finalPrice = Number(finalPrice.toFixed(o.config.MAIN_MAX_DECIMALS_TOT));

		$("#discount-prod-list-final-subprice-"+fk_product).html(finalUnitPrice.toLocaleString(undefined, { minimumFractionDigits: o.config.MAIN_MAX_DECIMALS_TOT, maximumFractionDigits: o.config.MAIN_MAX_DECIMALS_UNIT }));
		$("#discount-prod-list-final-price-"+fk_product).html(finalPrice.toLocaleString(undefined, { minimumFractionDigits: o.config.MAIN_MAX_DECIMALS_TOT }));
	}

	/**
	 * Positionne le focus et le curseur à la fin de l'input
	 * @param {jQuery} $searchAllInput
	 */
	o.focusAtEndSearchInput = function ($searchAllInput){
		let searchAllInputVal = $searchAllInput.val();
		$searchAllInput.blur().focus().val('').val(searchAllInputVal);
	}


	/**
	 * Will init document info from loaded ajax form
	 */
	o.initCurrentDocumentObjectVarsFromForm = function (){
		o.fk_element = $("#advancedproductsearch-form-fk-element").val();
		o.element = $("#advancedproductsearch-form-element").val();
	}

	/**
	 *
	 * @returns {boolean}
	 */
	o.isSupplierDocument = function (){
		if(o.inArray(o.element, o.config.supplierElements)){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * equivalent de in_array en php
	 * @param needle
	 * @param haystack
	 * @returns {boolean}
	 */
	o.inArray = function inArray(needle, haystack) {
		var length = haystack.length;
		for(var i = 0; i < length; i++) {
			if(haystack[i] == needle) return true;
		}
		return false;
	}

	/**
	 *
	 * @param fk_product
	 */
	o.addProductToCurentDocument = function (fk_product){
		var urlInterface = o.config.interface_url;

		// disable action button during add
		o.disableAddProductFields(fk_product, true);

		o.initCurrentDocumentObjectVarsFromForm();

		var sendData = {
			'action': "add-product",
			'fk_product': fk_product,
			'qty': $("#advanced-product-search-list-input-qty-"+fk_product).val(),
			'subprice': $("#advanced-product-search-list-input-subprice-"+fk_product).val(),
			'reduction': $("#advanced-product-search-list-input-reduction-"+fk_product).val(),
			'fk_element': o.fk_element,
			'element': o.element,
			'token': o.newToken
		};

		// check if supplier price exist because it could be not activated
		let fk_fournprice = $("#prodfourprice-" + fk_product);
		if(fk_fournprice.length > 0){
			sendData.fk_fournprice = fk_fournprice.val();
		}

		$.ajax({
			method: "POST",
			url: urlInterface,
			dataType: 'json',
			data: sendData,
			success: function (response) {
				if(response.result) {
					// update displayed product line of search dialog
					if(typeof response.data.newTotalQtyForProduct != undefined) {
						let qtyTargetBadge = $('.advanced-product-search__badge-qty-doc[data-product=' + fk_product + ']');
						qtyTargetBadge.text(response.data.newTotalQtyForProduct);
					}
				}
				else {
					// do stuff on error
				}
				o.newToken = response.newToken;
				o.dialogCountAddedProduct++; // indique qu'il faudra un rechargement de page à la fermeture de la dialogbox
				o.focusAtEndSearchInput($("#search-all-form-input")); // on replace le focus sur la recherche global pour augmenter la productivité
				o.setEventMessage(response.msg, response.result);
				// re-enable action button
				o.disableAddProductFields(fk_product, false);
			},
			error: function (err) {
				o.setEventMessage(o.discountlang.errorAjaxCall, false);
				// re-enable action button
				o.disableAddProductFields(fk_product, false);
			}
		});
	}

	/**
	 * Remplace la valeur d'un paramètre dans une URL
	 * @param {string} url
	 * @param {string} param the get param
	 * @param {string} paramVal the new value
	 * @returns {string}
	 */
	o.updateURLParameter = function updateURLParameter(url, param, paramVal)
	{
		var TheAnchor = null;
		var newAdditionalURL = "";
		var tempArray = url.split("?");
		var baseURL = tempArray[0];
		var additionalURL = tempArray[1];
		var temp = "";

		if (additionalURL)
		{
			var tmpAnchor = additionalURL.split("#");
			var TheParams = tmpAnchor[0];
			TheAnchor = tmpAnchor[1];
			if(TheAnchor)
				additionalURL = TheParams;

			tempArray = additionalURL.split("&");

			for (var i=0; i<tempArray.length; i++)
			{
				if(tempArray[i].split('=')[0] != param)
				{
					newAdditionalURL += temp + tempArray[i];
					temp = "&";
				}
			}
		}
		else
		{
			var tmpAnchor = baseURL.split("#");
			var TheParams = tmpAnchor[0];
			TheAnchor  = tmpAnchor[1];

			if(TheParams)
				baseURL = TheParams;
		}

		if(TheAnchor)
			paramVal += "#" + TheAnchor;

		var rows_txt = temp + "" + param + "=" + paramVal;
		return baseURL + "?" + newAdditionalURL + rows_txt;
	}

})(AdvancedProductSearch);
