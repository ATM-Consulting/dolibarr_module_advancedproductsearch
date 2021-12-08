<?php

/**
 * Class for AdvancedProductSearch
 */
class AdvancedProductSearch extends CommonObject
{

	/**
	 * @var string[]
	 */
	public $supplierElements =  array(
		'supplier_proposal',
		'order_supplier',
		'invoice_supplier'
	);


	/**
	 * @param $fk_soc
	 * @param bool $forceFetch
	 * @return Societe
	 */
	static function getSocieteCache($fk_soc, $forceFetch = false){
		global $db, $advencedProductSearchSocieteCache;

		if(empty($fk_soc) || $fk_soc < 0){
			return false;
		}

		if(!empty($advencedProductSearchSocieteCache[$fk_soc]) && !$forceFetch){
			return $advencedProductSearchSocieteCache[$fk_soc];
		}
		else{
			$societe = new Societe($db);
			$res = $societe->fetch($fk_soc);
			if($res>0){
				$advencedProductSearchSocieteCache[$fk_soc] = $societe;
				return $advencedProductSearchSocieteCache[$fk_soc];
			}
		}

		return false;
	}

	/**
	 * Clear product cache
	 */
	public function clearSocieteCache(){
		global $advencedProductSearchSocieteCache;
		$advencedProductSearchSocieteCache = array();
	}


	/**
	 * @param $fk_product
	 * @param bool $forceFetch
	 * @return Product
	 */
	static function getProductCache($fk_product, $forceFetch = false){
		global $db, $advencedProductSearchProductCache;

		if(!class_exists('Product')){
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		}

		if(empty($fk_product) || $fk_product < 0){
			return false;
		}

		if(!empty($advencedProductSearchProductCache[$fk_product]) && !$forceFetch){
			return $advencedProductSearchProductCache[$fk_product];
		}
		else{
			$product = new Product($db);
			$res = $product->fetch($fk_product);
			if($res>0){
				$advencedProductSearchProductCache[$fk_product] = $product;
				return $advencedProductSearchProductCache[$fk_product];
			}
		}

		return false;
	}


	/**
	 * return an ajax ready search table for product
	 * @param string $pageUrl Page URL (in most cases provided with $_SERVER["PHP_SELF"])
	 * @param bool $isSupplier
	 * @return string
	 */
	public static function advancedProductSearchForm($pageUrl = '', $isSupplier = false) {
		global $langs, $conf, $db, $action;

		$output = '';

		if(empty($pageUrl)){
			$pageUrl = $_SERVER["PHP_SELF"];
		}

		// Load translation files required by the page
		$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'stocks', 'margins'));

		if(!class_exists('Product')){
			include_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		if (!empty($conf->categorie->enabled)){
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		}

		$form = new Form($db);

		$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : 10;
		$sortfield = GETPOST("sortfield", 'alpha');
		$sortorder = GETPOST("sortorder", 'alpha');
		$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
		if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
		$offset = $limit * $page;
		$pageprev = $page - 1;
		$pagenext = $page + 1;
		if (!$sortfield) $sortfield = "p.ref";
		if (!$sortorder) $sortorder = "ASC";


		// LES FILTRES
		$search_type = '';
		$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
		$search_ref = GETPOST("search_ref", 'alpha');
		$search_supplierref = GETPOST("search_supplierref", 'alpha');
		$search_barcode = GETPOST("search_barcode", 'alpha');
		$search_label = GETPOST("search_label", 'alpha');

		$search_type = -1; // TODO $search_type = GETPOST("search_type", 'int');
//	$search_vatrate = GETPOST("search_vatrate", 'alpha');
		$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
		$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
		$search_tosell = 1; // GETPOST("search_tosell", 'int'); // TODO
//	$search_tobuy = GETPOST("search_tobuy", 'int'); // TODO
		$fourn_id = GETPOST("fourn_id", 'int');
		$catid = GETPOST('catid', 'int');
//	$search_tobatch = GETPOST("search_tobatch", 'int');
//	$optioncss = GETPOST('optioncss', 'alpha');
		$type = GETPOST("type", "int");


		$fk_company = GETPOST("fk_company", "int");
		$fk_project = GETPOST("fk_project", "int");

		$element = GETPOST("element", 'aZ09');
		$fk_element = GETPOST("fk_element", "int");

		$object = self::objectAutoLoad($element, $db);
		if($object > 0){
			if($object->fetch($fk_element)){
				$object->fetch_thirdparty();
				if($object->socid>0){
					$fk_company = $object->socid;
				}
				if($object->fk_project>0){
					$fk_project = $object->fk_project;
				}
			}
		}


		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
		if ($sall) $param .= "&sall=".urlencode($sall);
		if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
		}
		if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
		if ($search_supplierref) $param = "&search_supplierref=".urlencode($search_supplierref);
		if ($fk_company) $param.= "&socid=".urlencode($fk_company);
//	if ($search_ref_supplier) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
		if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");
		if ($search_label) $param .= "&search_label=".urlencode($search_label);
		if ($search_tosell != '') $param .= "&search_tosell=".urlencode($search_tosell);
		if ($fourn_id > 0) $param .= ($fourn_id ? "&fourn_id=".$fourn_id : "");
		//if ($seach_categ) $param.=($search_categ?"&search_categ=".urlencode($search_categ):"");
		if ($type != '') $param .= '&type='.urlencode($type);
		if ($search_type != '') $param .= '&search_type='.urlencode($search_type);

		// REQUETTE SQL

		// List of fields to search into when doing a "search in all"
		$fieldstosearchall = array('p.ref','p.label','p.description',"p.note");

		// multilang
		if (!empty($conf->global->MAIN_MULTILANGS)){
			$fieldstosearchall+= array('pl.label','pl.description','pl.note');
		}

		if (!empty($conf->barcode->enabled)) {
			$fieldstosearchall+=  array('p.barcode','pfp.barcode');
		}

		// Filter on supplier
		if (!empty($conf->fournisseur->enabled)){
			$fieldstosearchall+=  array('pfp.ref_fourn');
		}

		// SELECT PART
		$sqlSelect = ' DISTINCT p.rowid, p.ref, p.label ';
		if (!empty($conf->global->PRODUCT_USE_UNITS))   $sqlSelect .= ' ,cu.label as cu_label';

		// SELECT COUNT PART
		$sqlSelectCount = ' COUNT(DISTINCT p.rowid) as nb_results ';

		$sql = ' FROM '.MAIN_DB_PREFIX.'product as p ';
		if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON (p.rowid = cp.fk_product) "; // We'll need this table joined to the select in order to filter by categ
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (pfp.fk_product = p.rowid) ";
		// multilang
		if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON (pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."' )";
		if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON (cu.rowid = p.fk_unit)";

		$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
		if (isset($search_tosell) && dol_strlen($search_tosell) > 0 && $search_tosell != -1) $sql .= " AND p.tosell = ".((int) $search_tosell);
		if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0 && $search_tobuy != -1)   $sql .= " AND p.tobuy = ".((int) $search_tobuy);

		if ($sall) $sql .= natural_search($fieldstosearchall, $sall);
		// if the type is not 1, we show all products (type = 0,2,3)
//	if (dol_strlen($search_type) && $search_type != '-1'){
//		if ($search_type == 1) $sql .= " AND p.fk_product_type = 1";
//		else $sql .= " AND p.fk_product_type <> 1";
//	}

		if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
		if ($search_label)   $sql .= natural_search('p.label', $search_label);
		if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
		// Filter on supplier
		if (!empty($conf->fournisseur->enabled) && !empty($search_supplierref)){
			$sql .= natural_search('pfp.ref_fourn', $search_supplierref);
		}

		if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
		if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";

		$searchCategoryProductSqlList = array();
		if ($searchCategoryProductOperator == 1) {
			foreach ($searchCategoryProductList as $searchCategoryProduct) {
				if (intval($searchCategoryProduct) == -2) {
					$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
				} elseif (intval($searchCategoryProduct) > 0) {
					$searchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($searchCategoryProduct);
				}
			}
			if (!empty($searchCategoryProductSqlList)) {
				$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
			}
		} else {
			foreach ($searchCategoryProductList as $searchCategoryProduct) {
				if (intval($searchCategoryProduct) == -2) {
					$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
				} elseif (intval($searchCategoryProduct) > 0) {
					$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
				}
			}
			if (!empty($searchCategoryProductSqlList)) {
				$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
			}
		}
		if ($fourn_id > 0)  $sql .= " AND pfp.fk_soc = ".((int) $fourn_id);

		$output.=  '<form id="product-search-dialog-form" class="--blur-on-loading" >';

		$output.=  '<input type="hidden" name="token" value="'.newToken().'">';
		$output.=  '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		$output.= '<input type="hidden" name="action" value="product-search-form">';
		$output.= '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		$output.= '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		//$output.= '<input type="hidden" name="page" value="'.$page.'">';
		$output.= '<input type="hidden" name="type" value="'.$type.'">';
		$output.= '<input type="hidden" name="fk_company" value="'.$fk_company.'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-element" name="element" value="'.$element.'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-fk-element" name="fk_element" value="'.$fk_element.'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-fk-project" name="fk_project" value="'.$fk_project.'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-default-customer-reduction" name="default_customer_reduction" value="'.floatval($object->thirdparty->remise_percent).'">';

		$querySearchRes = $db->query('SELECT '.$sqlSelectCount.' '.$sql);
		$globalCountResult = 0;
		$curentCountResult = 0;
		if ($querySearchRes) {
			$obj = $db->fetch_object($querySearchRes);
			$globalCountResult = $obj->nb_results;

			$querySearchRes = $db->query('SELECT '.$sqlSelect.' '.$sql.$db->plimit($limit + 1, $offset));
			if ($querySearchRes) {
				$curentCountResult = $db->num_rows($querySearchRes);
			}
		}

		$morehtmlcenter= '<div class="advanced-product-global-search-container" >';
		$morehtmlcenter.= '<input name="sall" value="'.dol_htmlentities($sall).'" id="search-all-form-input" class="advanced-product-global-search-input" placeholder="'.$langs->trans('Search').'" autocomplete="off">';
		$morehtmlcenter.= '</div>';

		$picto = 'product';
		if ($type == 1) $picto = 'service';

		ob_start(); // parceque dolibarr aime tellement le print ...
		print_barre_liste($langs->trans('AdvancedProductSearch'), $page, $pageUrl, $param, $sortfield, $sortorder, $morehtmlcenter, $curentCountResult, $globalCountResult, $picto, 0, '', '', $limit, 0, 0, 0);
		$output.= ob_get_contents();
		ob_end_clean();



		if($globalCountResult > 0){
			$output.= '<div class="advancedproductsearch__results-count">';
			if($globalCountResult>1){
				$output.= $langs->trans('resultsDisplayForNbResultsFounds', min($limit,$globalCountResult), $globalCountResult );
			}
			else{
				$output.= $langs->trans('OneResultDisplayForOneResultFounds', min($limit,$globalCountResult), $globalCountResult );
			}
			$output.= '</div>';
		}

		$moreforfilter = '';
		// Filter on supplier
		if (!empty($conf->fournisseur->enabled))
		{
			$moreforfilter .= '<div class="divsearchfield" >';
			$moreforfilter .= $langs->trans('Supplier').': ';
			$moreforfilter .= $form->select_company($fourn_id, 'fourn_id', '', 1, 'supplier');
			$moreforfilter .= '</div>';

			$moreforfilter .= '<div class="divsearchfield" >';
			$moreforfilter .= $langs->trans('SupplierRef').': ';
			$moreforfilter .= '<input type="text" name="search_supplierref" value="'.dol_htmlentities($search_supplierref).'" />';
			$moreforfilter .= '</div>';
		}


		// Filter on categories
		if (!empty($conf->categorie->enabled))
		{
			$moreforfilter .= '<div class="divsearchfield" >';
			$moreforfilter .= $langs->trans('ProductCategories').': ';
			$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
			$categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
			$moreforfilter .= Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
			$moreforfilter .= ' <input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories');
			$moreforfilter .= '</div>';
		}

//	$parameters = array();
//	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
//	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
//	else $moreforfilter = $hookmanager->resPrint;

		if ($moreforfilter)
		{
			$output.= '<div class="liste_titre liste_titre_bydiv centpercent">';
			$output.= $moreforfilter;
			$output.= '</div>';
		}

		$colnumber = 8;

		$output.= '<table class="noborder centpercent advance-search-product-results" >';
		$output.= '<thead>';

		$output.= '<tr class="advanced-product-search-row --search liste_titre">';

		$output.= '	<th class="advanced-product-search-col --ref" >';
		$output.= ' <input type="text" class="flat"  name="search_ref" value="'.dol_htmlentities($search_ref).'" placeholder="'.$langs->trans('SearchRef').'" />';
		$output.= '	</th>';

		$output.= '	<th class="advanced-product-search-col --label" >';
		$output.= ' <input type="text" class="flat"  name="search_label" value="'.dol_htmlentities($search_label).'" placeholder="'.$langs->trans('SearchLabel').'" />';
		$output.= '	</th>';


		if($conf->stock->enabled){
			$output.= '	<th class="advanced-product-search-col --stock-reel center" ></th>';
			$output.= '	<th class="advanced-product-search-col --stock-theorique center" ></th>';
		}

		if ($conf->fournisseur->enabled) {
			$output .= '	<th class="advanced-product-search-col --buy-price" ></th>';
		}

		$output.= '	<th class="advanced-product-search-col --subprice" ></th>';
		$output.= '	<th class="advanced-product-search-col --discount" ></th>';
		$output.= '	<th class="advanced-product-search-col --finalsubprice" ></th>';
		$output.= '	<th class="advanced-product-search-col --qty" ></th>';

		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			$output.= '<th class="advanced-product-search-col --unit" >';
			$output.= '</th>';
		}

		$output.= '	<th class="advanced-product-search-col --finalprice" ></th>';
		$output.= '	<th class="advanced-product-search-col --action" >';

		$output.= '	</th>';
		$output.= '</tr>';


		//---------------------------
		// LES TITTRES DES COLONNES -
		//---------------------------

		$classForSortLink = "advanced-product-search-sort-link";

		$output.= '<tr class="advanced-product-search-row --title liste_titre">';
		$output.= '	<th class="advanced-product-search-col --ref" >'
			. self::getDialogColSortLink($langs->trans('Ref'), $pageUrl, "p.ref", $param, $sortfield, $sortorder, $classForSortLink)
			.'</th>';

		$output.= '	<th class="advanced-product-search-col --label" >'
			. self::getDialogColSortLink($langs->trans('Label'), $pageUrl, "p.label", $param, $sortfield, $sortorder, $classForSortLink)
			.'</th>';

		if($conf->stock->enabled){
			$output.= '	<th class="advanced-product-search-col --stock-reel center" >'.$langs->trans('RealStock').'</th>';
			$output.= '	<th class="advanced-product-search-col --stock-theorique center" >'.$langs->trans('VirtualStock').'</th>';
			$colnumber+=2;
		}

		if ($conf->fournisseur->enabled) {
			$colnumber++;
			$output .= '	<th class="advanced-product-search-col --buy-price" >' . ($isSupplier ? $langs->trans('PredefinedFournPricesForFill').img_help(1, $langs->trans('PredefinedFournPricesForFillHelp')) : $langs->trans('BuyPrice')) . '</th>';
		}
		$output.= '	<th class="advanced-product-search-col --subprice" >'.$langs->trans('Price').'</th>';
		$output.= '	<th class="advanced-product-search-col --discount" >'.$langs->trans('Discount').'</th>';
		$output.= '	<th class="advanced-product-search-col --finalsubprice" >'.$langs->trans('FinalDiscountSubPrice').'</th>';
		$output.= '	<th class="advanced-product-search-col --qty" >'.$langs->trans('Qty').'</th>';

		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			$colnumber++;
			$output.= '<th class="advanced-product-search-col --unit" >';
			$output.= $langs->trans('Unit');
			$output.= '</th>';
		}
		$output.= '	<th class="advanced-product-search-col --finalprice" >'.$langs->trans('FinalDiscountPrice').'</th>';
		$output.= '	<th class="advanced-product-search-col --action" >';
		$output.= '		<div class="nowrap">';
		$output.= '			<button type="submit" class="liste_titre button_search" name="button_search_x" value="x">';
		$output.= '				<span class="fa fa-search"></span>';
		$output.= '			</button>';
		$output.= '			<button type="submit" class="liste_titre button_removefilter" name="button_removefilter_x" value="x">';
		$output.= '				<span class="fa fa-remove"></span>';
		$output.= '			</button>';
		$output.= '		</div>';
		$output.= '	</th>';
		$output.= '</tr>';
		$output.= '</thead>';
		$output.= '<tbody>';

		$sqlList = 'SELECT '.$sqlSelect.' '
			.$sql.$db->order($sortfield, $sortorder)
			.$db->plimit($limit + 1, $offset);

		$querySearchRes = $db->query($sqlList);

		if ($querySearchRes)
		{
			if($curentCountResult > 0){
				while ($obj = $db->fetch_object($querySearchRes)){
					$product = new Product($db);
					$resProd = $product->fetch($obj->rowid);
					if($resProd > 0){
						$product->load_stock();

						// Réduction par défaut du client
						$reduction = doubleval($object->thirdparty->remise_percent);
						if($isSupplier) {
							$reduction = doubleval($object->thirdparty->remise_supplier_percent);
						}

						// Prix unitaire du produit avec prise en compte des niveau de prix et du client
						$subprice = self::getProductSellPrice($product->id, $fk_company);
						if($isSupplier) {
							$subprice = 0;
						}

						// calcule du prix unitaire final apres réduction
						$finalSubprice = $subprice - $subprice*$reduction/100;

						// COMPTATIBILITE MODULE DISCOUNT RULE : RECHERCHE DE REGLE DE TARIFICATION
						if (!empty($conf->discountrules->enabled) && !$isSupplier){
							if(!class_exists('DiscountSearch')){ dol_include_once('/discountrules/class/discountSearch.class.php'); }
							if(class_exists('DiscountSearch')) { // Il est possible que le module soit supprimé mais pas désinstallé
								$discountSearch = new DiscountSearch($db);
								$subprice = DiscountRule::getProductSellPrice($product->id, $fk_company);
								$discountSearchResult = $discountSearch->search(0, $product->id, $fk_company, $fk_project);
								if ($discountSearchResult->result) {
									// Mise en page du résultat
									$discountSearchResult->tpMsg = getDiscountRulesInterfaceMessageTpl($langs, $discountSearchResult, $action);
									$subprice = $discountSearchResult->subprice;
									$finalSubprice = $discountSearchResult->calcFinalSubprice();

									if (!empty($discountSearchResult->reduction)) {
										$reduction = $discountSearchResult->reduction;
									}
								}
							}
							else{
								setEventMessage($langs->trans('ErrorMissingModuleDiscountRule'));
							}
						}



						$output.= '<tr class="advanced-product-search-row --data" data-product="'.$product->id.'"  >';
						$output.= '<td class="advanced-product-search-col --ref" >'. $product->getNomUrl(1).'</td>';
						$output.= '<td class="advanced-product-search-col --label" >'. self::highlightWordsOfSearchQuery($product->label, $search_label.' '.$sall).'</td>';
						if($conf->stock->enabled) {
							$output .= '<td class="advanced-product-search-col --stock-reel" >' . $product->stock_reel . '</td>';
							$output .= '<td class="advanced-product-search-col --stock-theorique" >' . $product->stock_theorique . '</td>';
						}

						if ($conf->fournisseur->enabled) {
							$output .= '<td class="advanced-product-search-col --buy-price" >';
							$TFournPriceList = self::getFournPriceList($product->id, $isSupplier ? $object->socid : 0);
							if (!empty($TFournPriceList)) {
//						$output.= '<div class="default-visible" >'.price($product->pmp).'</div>';
//						$output.= '<div class="default-hidden" >';

								$selectArray = array();
								$idSelected = '';

								foreach ($TFournPriceList as $TpriceInfos) {
									$selectArray[$TpriceInfos['id']] = array(
																				'label'=>$TpriceInfos['label'],
																				'data-up'=>$TpriceInfos['price']
																			);
									if ($TpriceInfos['id'] == 'pmpprice' && !empty($TpriceInfos['price'])) {
										$idSelected = 'pmpprice';
									}
								}

								if($isSupplier) { // Seuls les prix fournisseurs nous intéressent dans le cadre d'un document fournisseur (pas de PMP ou autre dans ce cas)
									unset($selectArray['pmpprice']);
									unset($selectArray['costprice']);
									if(!empty($selectArray)) {
										if(count($selectArray) == 1) {
											$idSelected = key($selectArray);
											$subprice = $selectArray[$idSelected]['data-up'];
											// Recalcul du subprice final
											$finalSubprice = $subprice - $subprice*$reduction/100;
										}
										// On insère une valeur vide, car si plusieurs prix fourn, on laisse le choix à l'utilisateur de sélectionner celui qu'il souhaite
										$selectArray[0] = array('data-up' => 0);
									}
								}


								$key_in_label = 0;
								$value_as_key = 0;
								$moreparam = 'data-product="'.$product->id.'"';
								$translate = 0;
								$maxlen = 0;
								$disabled = 0;
								if($isSupplier) $sort = 'ASC';
								$morecss = 'search-list-select';
								$addjscombo = 0;
								if(!empty($selectArray)) {
									$output .= $form->selectArray('prodfourprice-' . $product->id, $selectArray, $idSelected, 0, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo);
								}
//						$output.= '</div>';
							} else {
								$output .= price($product->pmp);
							}
							$output .= '</td>';
						}


						//
						$output.= '<td class="advanced-product-search-col --subprice right nowraponall" >';
						$output.= '<input id="advanced-product-search-list-input-subprice-'.$product->id.'"  data-product="'.$product->id.'"   class="advanced-product-search-list-input-subprice right on-update-calc-prices" type="number" step="any" min="0" maxlength="8" size="3" value="'.$subprice.'" placeholder="x" name="prodsubprice['.$product->id.']" />';
						$output.= ' '.$langs->trans("HT");
						$output.= '</td>';

						// REDUCTION EN %
						$output.= '<td class="advanced-product-search-col --discount center" >';
						$output.= '<input id="advanced-product-search-list-input-reduction-'.$product->id.'"  data-product="'.$product->id.'"   class="advanced-product-search-list-input-reduction center on-update-calc-prices" type="number" step="any" min="0" max="100" maxlength="3" size="3" value="'.$reduction.'" placeholder="%" name="prodreduction['.$product->id.']" />';
						$output.= '%';
						$output.= '</td>';

						// FINAL SUBPRICE AFTER REDUCTION
						$output.= '<td class="advanced-product-search-col --finalsubprice right" >';
						$output.= '<span id="discount-prod-list-final-subprice-'.$product->id.'"  class="final-subpriceprice" >'.price(round($finalSubprice, $conf->global->MAIN_MAX_DECIMALS_UNIT)).'</span> '.$langs->trans("HT");
						$output.= '</td>';

						// QTY
						$output.= '<td class="advanced-product-search-col --qty" >';
						$qty = 1;
						$output.= '<input id="advanced-product-search-list-input-qty-'.$product->id.'"  data-product="'.$product->id.'"  class="advanced-product-search-list-input-qty center on-update-calc-prices" type="number" step="any" min="0" maxlength="8" size="3" value="'.$qty.'" placeholder="x" name="prodqty['.$product->id.']" />';
						$output.= '</td>';

						// UNITE
						if (!empty($conf->global->PRODUCT_USE_UNITS)) {
							$output.= '<td class="advanced-product-search-col --unit" >';
							$output.= $product->getLabelOfUnit();
							$output.= '</td>';
						}

						$output.= '<td class="advanced-product-search-col --finalprice right" >';
						$finalPrice = $finalSubprice*$qty;
						$output.= '<span id="discount-prod-list-final-price-'.$product->id.'"  class="final-price" >'.price(round($finalPrice, $conf->global->MAIN_MAX_DECIMALS_TOT)).'</span> '.$langs->trans("HT");
						$output.= '</td>';

						$output.= '<td class="advanced-product-search-col --action" >';
//					$output.= '<div class="default-hidden" >';
						$output.= ' <button type="button" title="'.$langs->trans('ClickToAddProductInDocument').'"  data-product="'.$product->id.'" class="advance-prod-search-list-action-btn --addProductToLine" ><span class="fa fa-plus add-btn-icon"></span> '.$langs->trans('Add').'</button>';
//					$output.= '</div>';
						$output.= '</td>';

						$output.= '</tr>';
					}
					else{
						$output.= '<tr class="advanced-product-search-row">';
						$output.= '<td class="advanced-product-search-col-error center" colspan="'.$colnumber.'">'. $product->errorsToString() .'</td>';
						$output.= '</tr>';

					}

				}
			}
			else{
				$output.= '<tr class="advanced-product-search-row">';
				$output.= '<td class="advanced-product-search-col-no-result" colspan="'.$colnumber.'">'. $langs->trans("NoResults") .'</td>';
				$output.= '</tr>';

			}
		}
		else{
			$output.= '<tr class="advanced-product-search-row">';
			$output.= '<td class="advanced-product-search-col-error" colspan="'.$colnumber.'">'. $db->error() .'</td>';
			$output.= '</tr>';
		}

		$output.= '</tbody>';
		$output.= '</table>';
		$output.= '</form>';

		return $output;
	}

	/**
	 * @param $label		Translation key of field
	 * @param $pageUrl		Url used when we click on sort picto
	 * @param $field		Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
	 * @param $param		Add more parameters on sort url links ("" by default)
	 * @param $sortfield	Current field used to sort (Ex: 'd.datep,d.id')
	 * @param $sortorder	Current sort order (Ex: 'asc,desc')
	 * @return string
	 */
	public static function getDialogColSortLink($label, $pageUrl, $field, $param, $sortfield, $sortorder, $moreClass = ""){
		$sortorder = strtoupper($sortorder);
		$tmpsortfield = explode(',', $sortfield);
		$sortfield1 = trim($tmpsortfield[0]); // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
		$tmpfield = explode(',', $field);
		$field1 = trim($tmpfield[0]); // If $field is 'd.datep,d.id', it becomes 'd.datep'


		$sortordertouseinlink = '';
		if ($field1 != $sortfield1) // We are on another field than current sorted field
		{
			if (preg_match('/^DESC/i', $sortorder))
			{
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			}
			else		// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		else                        // We are on field that is the first current sorting criteria
		{
			if (preg_match('/^ASC/i', $sortorder))	// We reverse the var $sortordertouseinlink
			{
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			}
			else
			{
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		$sortordertouseinlink = preg_replace('/,$/', '', $sortordertouseinlink);


		$out = '<a class="reposition '.$moreClass.'" href="'.$pageUrl.'?sortfield='.$field.'&sortorder='.$sortordertouseinlink.$param.'" >';
		$out.= $label;
		$out.= '</a>';

		return $out;
	}

	/**
	 * Return an object
	 *
	 * @param string $objecttype Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
	 * @param $db
	 * @return int object of $objecttype
	 */
	public static function objectAutoLoad($objecttype, &$db)
	{
		global $conf, $langs;

		$ret = -1;
		$regs = array();

		// Parse $objecttype (ex: project_task)
		$module = $myobject = $objecttype;

		// If we ask an resource form external module (instead of default path)
		if (preg_match('/^([^@]+)@([^@]+)$/i', $objecttype, $regs)) {
			$myobject = $regs[1];
			$module = $regs[2];
		}


		if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs))
		{
			$module = $regs[1];
			$myobject = $regs[2];
		}

		// Generic case for $classpath
		$classpath = $module.'/class';

		// Special cases, to work with non standard path
		if ($objecttype == 'facture' || $objecttype == 'invoice') {
			$classpath = 'compta/facture/class';
			$module='facture';
			$myobject='facture';
		}
		elseif ($objecttype == 'commande' || $objecttype == 'order') {
			$classpath = 'commande/class';
			$module='commande';
			$myobject='commande';
		}
		elseif ($objecttype == 'propal')  {
			$classpath = 'comm/propal/class';
		}
		elseif ($objecttype == 'shipping') {
			$classpath = 'expedition/class';
			$myobject = 'expedition';
			$module = 'expedition_bon';
		}
		elseif ($objecttype == 'delivery') {
			$classpath = 'livraison/class';
			$myobject = 'livraison';
			$module = 'livraison_bon';
		}
		elseif ($objecttype == 'contract') {
			$classpath = 'contrat/class';
			$module='contrat';
			$myobject='contrat';
		}
		elseif ($objecttype == 'member') {
			$classpath = 'adherents/class';
			$module='adherent';
			$myobject='adherent';
		}
		elseif ($objecttype == 'cabinetmed_cons') {
			$classpath = 'cabinetmed/class';
			$module='cabinetmed';
			$myobject='cabinetmedcons';
		}
		elseif ($objecttype == 'fichinter') {
			$classpath = 'fichinter/class';
			$module='ficheinter';
			$myobject='fichinter';
		}
		elseif ($objecttype == 'task') {
			$classpath = 'projet/class';
			$module='projet';
			$myobject='task';
		}
		elseif ($objecttype == 'stock') {
			$classpath = 'product/stock/class';
			$module='stock';
			$myobject='stock';
		}
		elseif ($objecttype == 'inventory') {
			$classpath = 'product/inventory/class';
			$module='stock';
			$myobject='inventory';
		}
		elseif ($objecttype == 'mo') {
			$classpath = 'mrp/class';
			$module='mrp';
			$myobject='mo';
		}

		// Generic case for $classfile and $classname
		$classfile = strtolower($myobject); $classname = ucfirst($myobject);
		//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname;

		if ($objecttype == 'invoice_supplier') {
			$classfile = 'fournisseur.facture';
			$classname = 'FactureFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}
		elseif ($objecttype == 'order_supplier') {
			$classfile = 'fournisseur.commande';
			$classname = 'CommandeFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}
		elseif ($objecttype == 'supplier_proposal') {
			$classpath = 'supplier_proposal/class';
			$classfile = 'supplier_proposal';
			$classname = 'SupplierProposal';
			$module = 'supplier_proposal';
		}
		elseif ($objecttype == 'stock') {
			$classpath = 'product/stock/class';
			$classfile = 'entrepot';
			$classname = 'Entrepot';
		}
		elseif ($objecttype == 'dolresource') {
			$classpath = 'resource/class';
			$classfile = 'dolresource';
			$classname = 'Dolresource';
			$module = 'resource';
		}


		if (!empty($conf->$module->enabled))
		{
			$res = dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
			if ($res)
			{
				if (class_exists($classname)) {
					return new $classname($db);
				}
			}
		}
		return $ret;
	}

	/**
	 * Return a list a founr price info for product
	 * @param $idprod
	 * @return array [
	 *            'id' 		=> (int) 	for price id || (string) like pmpprice,costprice
	 *            'price' 	=> (double)
	 *            'label' 	=> (string) a long label
	 *            'title' 	=> (string) a short label
	 *         ]
	 */
	public static function getFournPriceList($idprod, $id_fourn=0){
		global $db, $langs, $conf;
		$prices = array();

		if ($idprod > 0)
		{
			if(!class_exists('ProductFournisseur')){
				include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
			}

			$producttmp = new ProductFournisseur($db);
			$producttmp->fetch($idprod);

			$sorttouse = 's.nom, pfp.quantity, pfp.price';
			if (GETPOST('bestpricefirst')) $sorttouse = 'pfp.unitprice, s.nom, pfp.quantity, pfp.price';

			$productSupplierArray = $producttmp->list_product_fournisseur_price($idprod, $sorttouse, '', 0, 0, $id_fourn); // We list all price per supplier, and then firstly with the lower quantity. So we can choose first one with enough quantity into list.
			if (is_array($productSupplierArray))
			{
				foreach ($productSupplierArray as $productSupplier)
				{
					$price = $productSupplier->fourn_price * (1 - $productSupplier->fourn_remise_percent / 100);
					$unitprice = $productSupplier->fourn_unitprice * (1 - $productSupplier->fourn_remise_percent / 100);

					$title = $productSupplier->fourn_name.' - '.$productSupplier->fourn_ref.' - ';

					if ($productSupplier->fourn_qty == 1)
					{
						$title .= price($price, 0, $langs, 0, 0, -1, $conf->currency)."/";
					}
					$title .= $productSupplier->fourn_qty.' '.($productSupplier->fourn_qty == 1 ? $langs->trans("Unit") : $langs->trans("Units"));

					if ($productSupplier->fourn_qty > 1)
					{
						$title .= " - ";
						$title .= price($unitprice, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
						$price = $unitprice;
					}

					$label = price($price, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
					if ($productSupplier->fourn_ref) $label .= ' ('.$productSupplier->fourn_ref.')';

					$prices[] = array("id" => $productSupplier->product_fourn_price_id, "price" => price2num($price, 0, '', 0), "label" => $label, "title" => $title, 'ref' => $productSupplier->fourn_ref); // For price field, we must use price2num(), for label or title, price()
				}
			}

			// After best supplier prices and before costprice
			if (!empty($conf->stock->enabled))
			{
				// Add price for pmp
				$price = $producttmp->pmp;
				$prices[] = array("id" => 'pmpprice', "price" => price2num($price), "label" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency)); // For price field, we must use price2num(), for label or title, price()
			}

			// Add price for costprice (at end)
			$price = $producttmp->cost_price;
			$prices[] = array("id" => 'costprice', "price" => price2num($price), "label" => $langs->trans("CostPrice").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency)); // For price field, we must use price2num(), for label or title, price()
		}

		return $prices;
	}


	/**
	 * Function copiée du module discount rules
	 * @param $fk_product
	 * @param $fk_company
	 * @return bool|float|mixed
	 */
	public static function getProductSellPrice($fk_product, $fk_company){ // TODO add Cache for result
		global $mysoc, $conf;
		$product = self::getProductCache($fk_product);
		$societe = self::getSocieteCache($fk_company);

		if(!empty($product)) {

			// Dans le cas d'une règle liée à un produit, c'est le prix net qui sert de base de comparaison

			// récupération du prix client
			if ($societe) {
				$TSellPrice = $product->getSellPrice($societe, $mysoc);
				if (!empty($TSellPrice)) {
					$baseSubprice = $TSellPrice['pu_ht'];
				}
			}

			// si pas de prix client alors on force sur le prix de la fiche produit
			if (empty($baseSubprice)) {
				$baseSubprice = $product->price;
			}

			return round($baseSubprice, $conf->global->MAIN_MAX_DECIMALS_UNIT);
		}

		return false;
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @param string $backgroundColor
	 * @param string $color
	 * @return array|string|string[]
	 */
	public static function highlightString( $haystack, $needle, $backgroundColor = '#feff0370', $color = "#000") {
		if(empty($needle)){ return $haystack; }
		$needle = utf8_encode(strtr(utf8_decode($needle),utf8_decode("ÀÁÂàÄÅàáâàäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ"),"aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn"));
		$needle = str_replace('a', '[aàáâãäå]', $needle);
		$needle = str_replace('e', '[eèéêë]', $needle);
		$needle = str_replace('i', '[íîìï]', $needle);
		$needle = str_replace('o', '[óôòøõö]', $needle);
		$needle = str_replace('u', '[úûùü]', $needle);

		return preg_replace("/($needle)/iu", sprintf('<span style="background-color: %s; color:%s;">$1</span>', $backgroundColor, $color), $haystack);
	}

	public function highlightWordsOfSearchQuery( $content, $searchQuery) {

		$words = explode(' ', $searchQuery);
		$words = array_unique($words);
		$words = array_map('trim', $words);
		// loop through words
		foreach( $words as $word ) {
			$content = self::highlightString( $content, $word); // highlight word
		}

		return $content; // return highlighted data
	}

}
