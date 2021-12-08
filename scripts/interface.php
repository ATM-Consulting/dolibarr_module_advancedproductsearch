<?php


//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Include and load Dolibarr environment variables
$res = 0;
if (!$res && file_exists($path . "main.inc.php")) $res = @include($path . "main.inc.php");
if (!$res && file_exists($path . "../main.inc.php")) $res = @include($path . "../main.inc.php");
if (!$res && file_exists($path . "../../main.inc.php")) $res = @include($path . "../../main.inc.php");
if (!$res && file_exists($path . "../../../main.inc.php")) $res = @include($path . "../../../main.inc.php");
if (!$res) die("Include of master fails");
require_once __DIR__ . '/../class/advancedProductSearch.class.php';
require_once __DIR__ . '/../lib/advancedproductsearch.lib.php';

global $langs, $db, $hookmanager, $user, $mysoc;
/**
 * @var DoliDB $db
 */
$hookmanager->initHooks('advancedproductsearchinterface');

// Load traductions files requiredby by page
$langs->loadLangs(array("advancedproductsearch@advancedproductsearch", "other", 'main'));

$action = GETPOST('action');

// Security check
if (empty($conf->advancedproductsearch->enabled)) accessforbidden('Module not enabled');


// AJOUT DE LIGNE DANS LES DOCUMENTS
if ($action === 'add-product') {

	$jsonResponse = new stdClass();
	$jsonResponse->result = false;
	$jsonResponse->msg = '';

	$fk_product = GETPOST('fk_product', 'int');
	$element = GETPOST("element", 'aZ09');
	$fk_element = GETPOST("fk_element", "int");

	$qty = GETPOST("qty", "int");
	$qty = price2num($qty);
	$subprice = GETPOST("subprice", "int");
	$subprice = price2num($subprice);
	$remise_percent = GETPOST("reduction", "int");
	$remise_percent = price2num($remise_percent);
	$fournPrice = GETPOST("fk_fournprice", "alphanohtml"); // int || pmpprice || costprice



	$TWriteRight = array(
		'commande' => $user->rights->commande->creer,
		'propal' => $user->rights->propal->creer,
		'facture' => $user->rights->facture->creer,
		'invoice_supplier' => $user->rights->fournisseur->facture->creer,
		'order_supplier' => $user->rights->fournisseur->commande->creer,
		'supplier_proposal' => $user->rights->supplier_proposal->creer
	);

	if ($user->socid > 0 || empty($TWriteRight[$element])) {
		$jsonResponse->msg = array($langs->transnoentities('NotEnoughRights'));
	}
	else{
		$product = AdvancedProductSearch::getProductCache($fk_product);

		$object = AdvancedProductSearch::objectAutoLoad($element, $db);

		if($product > 0) {
			if($object->fetch($fk_element)) {
				$object->fetch_thirdparty();

				if(is_callable(array($object, 'addline'))) {

					$desc = $product->description;


					$validated = true; // init validation of  data

					// Cost / buy price
					$fk_fournprice = null;
					$pa_ht = $product->pmp;

					if ($conf->fournisseur->enabled) {
						$TFournPriceList = AdvancedProductSearch::getFournPriceList($product->id);
						if (!empty($TFournPriceList) && !empty($fournPrice)) {
							if (is_numeric($fournPrice)) { $fournPrice = intval($fournPrice); }

							$fourPriceKeyExist = false;
							foreach ($TFournPriceList as $fPrice) {
								if (is_numeric($fPrice['id'])){ $fPrice['id'] = intval($fPrice['id']); }

								if ($fPrice['id'] === $fournPrice) { // === to be sure 1 != 'string'
									if (is_numeric($fPrice['id'])){ $fk_fournprice = $fPrice['id']; }
									$pa_ht = $fPrice['price'];
									$ref_supplier = $fPrice['ref'];
									$fourPriceKeyExist = true;
									break;
								}
							}

							if (!$fourPriceKeyExist) {
								$validated = false;
								$jsonResponse->msg = $langs->trans('FournPriceError');
							}
						}
					}



					$TSellPrice = $product->getSellPrice($object->thirdparty, $mysoc);
					$price_base_type = $TSellPrice['price_base_type'];
					$txtva = $TSellPrice['tva_tx'];
					$pu_ht = doubleval($subprice);
					$pu_ttc = $pu_ht * (1+doubleval($TSellPrice['tva_tx'])/100);

					if ($remise_percent>100 || $remise_percent < 0) {
						$validated = false;
						$jsonResponse->msg = $langs->trans('ErrrorRemiseMustBeAValidPercent');
					}

					$txlocaltax1 = 0;
					$txlocaltax2 = 0;
					$fk_product = $product->id;
					$info_bits = 0;
					$fk_remise_except = 0;
					$date_start = '';
					$date_end = '';
					$type = 0;
					$rang = -1;
					$special_code = 0;
					$fk_parent_line = 0;

					$label = '';
					$array_options = 0;
					$fk_unit = $product->fk_unit;
					$origin = '';
					$origin_id = 0;
					$pu_ht_devise = 0;
					$no_trigger = false;


//					var_dump(
//
//						array('pu_ht' => $pu_ht,
//						'qty' => $qty,
//						'txtva' => $txtva,
//						'remise_percent' => $remise_percent,
//						'price_base_type' => $price_base_type,
//						'pu_ttc' => $pu_ttc,
//						'fk_fournprice' => $fk_fournprice,
//						'pa_ht' => $pa_ht
//						));

					if($validated) {
						$resAdd = 0;
						if($element=='commande') {
							/**
							 * @var Commande $object
							 */
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$qty,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$fk_product,
								$remise_percent,
								$info_bits,
								$fk_remise_except,
								$price_base_type,
								$pu_ttc,
								$date_start,
								$date_end,
								$type,
								$rang,
								$special_code,
								$fk_parent_line,
								$fk_fournprice,
								$pa_ht,
								$label,
								$array_options,
								$fk_unit,
								$origin,
								$origin_id,
								$pu_ht_devise
                            );
						}
						elseif($element=='propal') {
							/**
							 * @var Propal $object
							 */
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$qty,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$fk_product,
								$remise_percent,
								$price_base_type,
								$pu_ttc,
								$info_bits,
								$type,
								$rang,
								$special_code,
								$fk_parent_line,
								$fk_fournprice,
								$pa_ht,
								$label,
								$date_start,
								$date_end,
								$array_options,
								$fk_unit,
								$origin,
								$origin_id,
								$pu_ht_devise,
								$fk_remise_except
							);
						}
						elseif($element=='facture') {
							/**
							 * @var Propal $object
							 */
							$ventil = 0;
							$situation_percent = 100;
							$fk_prev_id = '';
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$qty,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$fk_product,
								$remise_percent,
								$date_start,
								$date_end,
								$ventil,
								$info_bits,
								$fk_remise_except,
								$price_base_type,
								$pu_ttc,
								$type,
								$rang,
								$special_code,
								$origin,
								$origin_id,
								$fk_parent_line,
								$fk_fournprice,
								$pa_ht,
								$label,
								$array_options,
								$situation_percent,
								$fk_prev_id,
								$fk_unit,
								$pu_ht_devise
							);
						}
						elseif($element=='invoice_supplier') {
							/**
							 * @var FactureFournisseur $object
							 */
							$ventil = 0;
							$situation_percent = 100;
							$fk_prev_id = '';
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$qty,
								$fk_product,
								$remise_percent,
								$date_start,
								$date_end,
								$ventil,
								$info_bits,
								$price_base_type,
								$type,
								$rang,
								$no_trigger,
								$array_options,
								$fk_unit,
								$origin_id,
								$pu_ht_devise,
								$ref_supplier,
								$special_code,
								$fk_parent_line,
								$fk_remise_except

							);

						}
						elseif($element == 'order_supplier') {
							/**
							 * @var CommandeFournisseur $object
							 */
							$ventil = 0;
							$situation_percent = 100;
							$fk_prev_id = '';
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$qty,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$fk_product,
								$fournPrice,
								$ref_supplier,
								$remise_percent,
								$price_base_type,
								$pu_ttc,
								$type,
								$info_bits,
								$no_trigger,
								$date_start,
								$date_end,
								$array_options,
								$fk_unit,
								$pu_ht_devise,
								$origin,
								$origin_id

							);

						}
						elseif($element == 'supplier_proposal') {
							/**
							 * @var SupplierProposal $object
							 */
							$ventil = 0;
							$situation_percent = 100;
							$fk_prev_id = '';
							$resAdd = $object->addline(
								$desc,
								$pu_ht,
								$qty,
								$txtva,
								$txlocaltax1,
								$txlocaltax2,
								$fk_product,
								$remise_percent,
								$price_base_type,
								$pu_ttc,
								$info_bits,
								$type,
								$rang,
								$special_code,
								$fk_parent_line,
								$fournPrice,
								$pa_ht,
								$label,
								$array_options,
								$ref_supplier,
								$fk_unit,
								$origin,
								$origin_id,
								$pu_ht_devise,
								$date_start,
								$date_end

							);

						}
						else {
							$jsonResponse->msg = $langs->trans('DocumentNotAvailable').': '.$element;
						}

						if($resAdd > 0) {
							$jsonResponse->msg = $langs->trans('LineAdded');
							$jsonResponse->result = true;
						} elseif($resAdd < 0) {
							$jsonResponse->msg = $langs->trans('ErrorOnAddLine');
						}
					}
				}
				else{
					$jsonResponse->msg = $langs->trans('DocumentNotAvailable').': '.$element;
				}
			}
			else{
				$jsonResponse->msg = $langs->trans('ErrorFetchingProduct');
			}
		}
		else{
			$jsonResponse->msg = $langs->trans('ErrorFetchingObject');
		}
	}

	// output
	print json_encode($jsonResponse); // , JSON_PRETTY_PRINT
}
// retourne le formulaire de recherche avancé de produit
elseif ($action === 'product-search-form') {
	$AdvancedProductSearch = new AdvancedProductSearch($db);
	$element = GETPOST("element", 'aZ09');
	$isSupplier = false;
	if(in_array($element, $AdvancedProductSearch->supplierElements)) {
		$isSupplier = true;
	}
	print AdvancedProductSearch::advancedProductSearchForm('', $isSupplier);
}


$db->close();    // Close $db database opened handler
