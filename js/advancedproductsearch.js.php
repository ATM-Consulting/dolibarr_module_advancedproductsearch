<?php
/* Copyright (C) 2018 John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');


/**
 * \file    js/advancedproductsearch.js.php
 * \ingroup advancedproductsearch
 * \brief   JavaScript file for module advancedproductsearch.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once __DIR__ . '/../class/advancedProductSearch.class.php';

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


// Load traductions files requiredby by page
$langs->loadLangs(array("advancedproductsearch@advancedproductsearch","other"));

$translateList = array('Saved', 'errorAjaxCall');

$translate = array();
foreach ($translateList as $key){
	$translate[$key] = $langs->transnoentities($key);
}

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$AdvancedProductSearch = new AdvancedProductSearch($db);

$confToJs = array(
	'MAIN_MAX_DECIMALS_TOT' => $conf->global->MAIN_MAX_DECIMALS_TOT,
	'MAIN_MAX_DECIMALS_UNIT' => $conf->global->MAIN_MAX_DECIMALS_UNIT,
	'dec' => $dec,
	'thousand' => $thousand,
	'interface_url' => dol_buildpath('advancedproductsearch/scripts/interface.php',1),
	'supplierElements' => $AdvancedProductSearch->supplierElements
);

?>
/* <script > */
// LANGS

/* Javascript library of module advancedproductsearch */
$( document ).ready(function() {


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

	//_________________________________________________
	// RECHERCHE GLOBALE AUTOMATIQUE SUR FIN DE SAISIE
	// (Uniquement sur la recherche globale)

	//setup before functions
	var typingProductSearchTimer;                //timer identifier
	var doneTypingProductSearchInterval = 2000;  //time in ms (2 seconds)

	$(document).on("keyup", "#search-all-form-input" , function(event) {
		clearTimeout(typingProductSearchTimer);
		if ($('#search-all-form-input').val()) {
			typingProductSearchTimer = setTimeout(function(){
				AdvancedProductSearch.discountLoadSearchProductDialogForm("&"+$( "#product-search-dialog-form" ).serialize());
			}, doneTypingProductSearchInterval);
		}
	});



	$(document).on("change", "[name^=prodfourprice]", function() {
		// limité au côté fournisseur
		if(AdvancedProductSearch.isSupplierDocument()){
			let fk_product = $(this).attr("data-product");
			$("#advanced-product-search-list-input-subprice-" + fk_product).val($(this).find(':selected').data('up'));
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
		AdvancedProductSearch.updateLinePricesCalcs(fk_product)
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

		var element = $(this).attr('data-target-element');
		var fk_element = $(this).attr('data-target-id');

		var productSearchDialogBox = "product-search-dialog-box";
		// crée le calque qui sera convertie en popup
		$('body').append('<div id="'+productSearchDialogBox+'" title="<?php print $langs->transnoentities('SearchProduct'); ?>"></div>');

		// transforme le calque en popup
		var popup = $('#'+productSearchDialogBox).dialog({
			autoOpen: true,
			modal: true,
			width: Math.min($( window ).width() - 50, 1700),
			dialogClass: 'discountrule-product-search-box',
			buttons: [
				{
					text: "<?php print $langs->transnoentities('CloseDialog'); ?>",
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
					document.location.reload();
				}
			},
			open: function( event, ui ) {
				//$(this).dialog('option', 'maxHeight', $(window).height()-30);

				AdvancedProductSearch.element = element;
				AdvancedProductSearch.fk_element = fk_element;

				AdvancedProductSearch.discountLoadSearchProductDialogForm("&element="+element+"&fk_element="+fk_element);
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
			var $element = $(this);
			// Do something to each element here.
			$element.removeClass(className).width;
			setTimeout(function(){ $element.addClass(className); }, 0);
		});
	};
}( jQuery ));

// Utilisation d'une sorte de namespace en JS
var AdvancedProductSearch = {};
(function(o) {

	o.lastidprod = 0;
	o.lastqty = 0;

	o.discountlang = <?php print json_encode($translate) ?>;
	o.config = <?php print json_encode($confToJs) ?>;
	o.dialogCountAddedProduct = 0;

	o.element = '';
	o.fk_element = 0;

	/**
	 * Load product search dialog form
	 *
	 * @param $morefilters
	 */
	o.discountLoadSearchProductDialogForm = function (morefilters = ''){
		var productSearchDialogBox = "product-search-dialog-box";

		$('#'+productSearchDialogBox).addClass('--ajax-loading');

		$('#'+productSearchDialogBox).prepend($('<div class="inner-dialog-overlay"><div class="dialog-loading__loading"><div class="dialog-loading__spinner-wrapper"><span class="dialog-loading__spinner-text">LOADING</span><span class="dialog-loading__spinner"></span></div></div></div>'));

		$('#'+productSearchDialogBox).load( o.config.interface_url + '?action=product-search-form' + morefilters, function() {
			o.dialogCountAddedProduct = 0; // init count of product added for reload action
			o.focusAtEndSearchInput($("#search-all-form-input"));

			if($('#'+productSearchDialogBox).outerHeight() >= $( window ).height()-150 ){
				$('#'+productSearchDialogBox).dialog( "option", "position", { my: "top", at: "top", of: window } ); // Hack to position the dialog box after ajax load
			}
			else{
				$('#'+productSearchDialogBox).dialog( "option", "position", { my: "center", at: "center", of: window } ); // Hack to center vertical the dialog box after ajax load
			}

			o.initToolTip($('#'+productSearchDialogBox+' .classfortooltip')); // restore tooltip after ajax call
			$('#'+productSearchDialogBox).removeClass('--ajax-loading');
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
			'element': o.element
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
			success: function (data) {
				if(data.result) {
					// do stuff on success
				}
				else {
					// do stuff on error
				}

				o.dialogCountAddedProduct++; // indique qu'il faudra un rechargement de page à la fermeture de la dialogbox
				o.focusAtEndSearchInput($("#search-all-form-input")); // on replace le focus sur la recherche global pour augmenter la productivité
				o.setEventMessage(data.msg, data.result);
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

})(AdvancedProductSearch);
