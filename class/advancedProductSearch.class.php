<?php

/**
 * Class for AdvancedProductSearch
 */
class AdvancedProductSearch
{


	public $displayResults = true;

	public $searchSelectArray;
	public $searchSqlSelectCount;
	public $fieldsToSearchAll;
	public $fieldsToSearchAllText;
	public $searchSqlList;

	/**
	 * @var string[]
	 * note : if you change this items, change also advancedproductsearch.js supplierElements var
	 */
	public $supplierElements =  array(
		'supplier_proposal',
		'order_supplier',
		'invoice_supplier'
	);

	public $urlParams = array();

	/**
	 * Search params
	 * @var string[]
	 */
	public $search =  array(
		'newToken' => '',
		'pageUrl' => '',
		'limit' => 10,
		'sortfield' => '',
		'sortorder' => '',
		'page' => 0,
		'offset' => 0,

		// LES FILTRES
		'search_type' => -1,
		'sall' => '',
		'search_ref' => '',
		'search_supplierref' => '',
		'search_barcode' => '',
		'search_label' => '',
		//	'search_vatrate'
		'search_category_product_operator' => 0,
		'search_category_product_list' => '',
		'search_tosell' => 1,
		'search_tobuy' => 1,
		'fourn_id' => '',
		'catid' => '',
		//	'search_tobatch '
		//	'optioncss'
		'type' => '',

		'fk_company' => '',
		'fk_project' => '',

		'element' => '',
		'fk_element' => '',
	);


	/**
	 *
	 */
	public function __construct(){
		$this->setSearchParamDefaultValues();
	}

	/**
	 * @param $fk_soc
	 * @param bool $forceFetch
	 * @return Societe
	 */
	static function getSocieteCache($fk_soc, $forceFetch = false){
		global $db, $advancedProductSearchSocieteCache;

		if(empty($fk_soc) || $fk_soc < 0){
			return false;
		}

		if(!empty($advancedProductSearchSocieteCache[$fk_soc]) && !$forceFetch){
			return $advancedProductSearchSocieteCache[$fk_soc];
		}
		else{
			$societe = new Societe($db);
			$res = $societe->fetch($fk_soc);
			if($res>0){
				$advancedProductSearchSocieteCache[$fk_soc] = $societe;
				return $advancedProductSearchSocieteCache[$fk_soc];
			}
		}

		return false;
	}

	/**
	 * Clear product cache
	 */
	public function clearSocieteCache(){
		global $advancedProductSearchSocieteCache;
		$advancedProductSearchSocieteCache = array();
	}


	/**
	 * @param $fk_product
	 * @param bool $forceFetch
	 * @return Product
	 */
	static function getProductCache($fk_product, $forceFetch = false){
		global $db, $advencedProductSearchProductCache, $langs;

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
	 * Corrige les valeurs des champs si besoin
	 * @return array $this->search
	 */
	public function setSearchParamDefaultValues() {

		if(empty($this->search['pageUrl'])){
			$this->search['pageUrl'] = $_SERVER["PHP_SELF"];
		}

		if (!$this->search['sortfield']) $this->search['sortfield'] = "p.ref";
		if (!$this->search['sortorder']) $this->search['sortorder'] = "ASC";

		$this->search['offset'] = $this->search['limit'] * $this->search['page'];
	}

	public function  setUrlParamsFromSearch(){
		global $conf;

		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $this->urlParams['contextpage'] = $contextpage;
		if ($this->search['limit'] > 0 && $this->search['limit'] != $conf->liste_limit) $this->urlParams['limit'] = $this->search['limit'];
		if ($this->search['sall']) $this->urlParams['sall'] = $this->search['sall'];
		if ($this->search['search_category_product_operator'] == 1) $this->urlParams['search_category_product_operator'] = $this->search['search_category_product_operator'];
		foreach ($this->search['search_category_product_list'] as $this->searchearchCategoryProduct) {
			$this->urlParams['search_category_product_list[]'] = $this->search['searchearchCategoryProduct'];
		}
		if ($this->search['search_ref']) $this->urlParams['search_ref'] = $this->search['search_ref'];
		if ($this->search['search_supplierref']) $this->urlParams['search_supplierref'] = $this->search['search_supplierref'];
		if ($this->search['fk_company']) $this->urlParams['socid'] = $this->search['fk_company'];
		//	if ($this->search['search_ref']_supplier) $this->urlParams['search_ref_supplier'] = $this->search['search_ref_supplier'];
		if ($this->search['search_barcode']) $this->urlParams['search_barcode'] = $this->search['search_barcode'];
		if ($this->search['search_label']) $this->urlParams['search_label'] = $this->search['search_label'];
		if ($this->search['search_tosell'] != '') $this->urlParams['search_tosell'] = $this->search['search_tosell'];
		if ($this->search['fourn_id'] > 0) $this->urlParams['fourn_id'] = $this->search['fourn_id'];
		//if ($this->searcheach_categ) $this->urlParams['search_categ'] = $this->search['searchearch_categ'];
		if ($this->search['type'] != '') $this->urlParams['type'] = $this->search['type'];
		if ($this->search['search_type'] != '') $this->urlParams['search_type'] = $this->search['search_type'];
		if ($this->search['element']) $this->urlParams['element'] = $this->search['element'];
		if ($this->search['fk_element']) $this->urlParams['fk_element'] = $this->search['fk_element'];

		return $this->urlParamsToString();
	}

	public function urlParamsToString(){
		$params = array();
		foreach ($this->urlParams as $key => $value){
			$params[] = $key.'='.urlencode($value);
		}

		return implode('&', $params);
	}

	/**
	 * populate $this->search array from post
	 * @return array $this->search
	 */
	public function getSearchParamFromPost() {

		$this->search['newToken'] = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

		$this->search['limit'] = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $this->search['limit'];
		$this->search['sortfield'] = GETPOST("sortfield", 'alpha');
		$this->search['sortorder'] = GETPOST("sortorder", 'alpha');
		$this->search['page'] = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
		if (empty($this->search['page']) || $this->search['page'] < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $this->search['page'] = 0; }     // If $this->search['page'] is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action


		// LES FILTRES
		$this->search['sall'] = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
		$this->search['search_ref'] = GETPOST("search_ref", 'alpha');
		$this->search['search_supplierref'] = GETPOST("search_supplierref", 'alpha');
		$this->search['search_barcode'] = GETPOST("search_barcode", 'alpha');
		$this->search['search_label'] = GETPOST("search_label", 'alpha');

		$this->search['search_type'] = -1; // TODO $this->search['search_type'] = GETPOST("search_type", 'int');
//	$this->search['search_vatrate'] = GETPOST("search_vatrate", 'alpha');
		$this->search['search_category_product_operator'] = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
		$this->search['search_category_product_list'] = GETPOST('search_category_product_list', 'array');
		if (GETPOST("search_tosell", 'int') === "") {
			$this->search['search_tosell'] = "1";
		} else {
			$this->search['search_tosell'] = GETPOST("search_tosell", 'int');
		}
		if (GETPOST("search_tobuy", 'int') === "") {
			$this->search['search_tobuy'] = "1";
		} else {
			$this->search['search_tobuy'] = GETPOST("search_tobuy", 'int');
		}
//		$this->search['search_tobuy'] = GETPOST("search_tobuy", 'int') ;
		$this->search['fourn_id'] = GETPOST("fourn_id", 'int');
		$this->search['catid'] = GETPOST('catid', 'int');
//	$this->search['search_tobatch ']= GETPOST("search_tobatch", 'int');
//	$this->search['optioncss'] = GETPOST('optioncss', 'alpha');
		$this->search['type'] = GETPOST("type", "int");


		$this->search['fk_company'] = GETPOST("fk_company", "int");
		$this->search['fk_project'] = GETPOST("fk_project", "int");

		$this->search['element'] = GETPOST("element", 'aZ09');
		$this->search['fk_element'] = GETPOST("fk_element", "int");


		$this->setSearchParamDefaultValues();

		return $this->search;
	}


	/**
	 * return an ajax ready search table for product
	 * @param string $search params
	 * @param bool $isSupplier
	 * @return string
	 */
	public static function staticAdvancedProductSearchForm($search = array(), $isSupplier = false) {
		$apsf = new self();
		$apsf->search = array_merge($apsf->getSearchParamFromPost(), $search);
		return $apsf->advancedProductSearchForm($isSupplier);
	}

	/**
	 * return an ajax ready search table for product
	 * @param bool $isSupplier
	 * @return string
	 */
	public function advancedProductSearchForm($isSupplier = false) {
		global $langs, $conf, $db, $action;

		$output = '';

		// Load translation files required by the page
		$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'stocks', 'margins'));

		if(!class_exists('Product')){
			include_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		if (isModEnabled('categorie')){
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		}

		$form = new Form($db);


		$currentQtyByProduct = array();
		$object = self::objectAutoLoad($this->search['element'], $db);
		if($object > 0){
			if($object->fetch($this->search['fk_element'])) {
				$object->fetch_thirdparty();
				if ($object->socid > 0) {
					$this->search['fk_company'] = $object->socid;
				}
				if ($object->fk_project > 0) {
					$this->search['fk_project'] = $object->fk_project;
				}

				if (!empty($object->lines)) {
					foreach ($object->lines as $objLines){
						/** @var OrderLine $objLines */
						if($objLines->fk_product > 0){
							if(!array_key_exists($objLines->fk_product, $currentQtyByProduct)){
								$currentQtyByProduct[$objLines->fk_product] = 0;
							}
							$currentQtyByProduct[$objLines->fk_product]+= $objLines->qty;
						}
					}
				}
			}
		}


		$param = '&'.$this->setUrlParamsFromSearch();

		// REQUETTE SQL

		// List of fields to search into when doing a "search in all"
		$this->fieldsToSearchAll = array('p.ref','p.label','p.description',"p.note");
		$this->fieldsToSearchAllText =array('Ref', 'Label', 'Description', 'Note');

		// multilang
		if (!empty(getDolGlobalString('MAIN_MULTILANGS'))){
			$this->fieldsToSearchAll+= array_merge($this->fieldsToSearchAll, array('pl.label','pl.description','pl.note'));
		}

		if (isModEnabled('barcode')) {
			$this->fieldsToSearchAll =  array_merge($this->fieldsToSearchAll, array('p.barcode','pfp.barcode'));
			$this->fieldsToSearchAllText[]='Barcode';
		}

		// Filter on supplier
		if (isModEnabled('fournisseur')){
			$this->fieldsToSearchAll[] = 'pfp.ref_fourn';
			$this->fieldsToSearchAllText[]='ProductRefFourn';
		}

		// SELECT PART
		$this->searchSqlSelect = ' DISTINCT p.rowid, p.ref, p.label ';
		if (!empty(getDolGlobalString('PRODUCT_USE_UNITS')))   $this->searchSqlSelect .= ' ,cu.label as cu_label';

		// SELECT COUNT PART
		$this->searchSqlSelectCount = ' COUNT(DISTINCT p.rowid) as nb_results ';

		$this->searchSql = ' FROM '.MAIN_DB_PREFIX.'product as p ';
		if (!empty($this->search['search_category_product_list']) || !empty($this->search['catid'])) $this->searchSql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON (p.rowid = cp.fk_product) "; // We'll need this table joined to the select in order to filter by categ
		$this->searchSql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (pfp.fk_product = p.rowid) ";
		// multilang
		if (!empty(getDolGlobalString('MAIN_MULTILANGS'))) $this->searchSql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON (pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."' )";
		if (!empty(getDolGlobalString('PRODUCT_USE_UNITS')))   $this->searchSql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON (cu.rowid = p.fk_unit)";

		$this->searchSql .= ' WHERE p.entity IN ('.getEntity('product').')';
		if (isset($this->search['search_tosell']) && dol_strlen($this->search['search_tosell']) > 0 && $this->search['search_tosell'] != -1) $this->searchSql .= " AND p.tosell = ".((int) $this->search['search_tosell']);
		if (isset($this->search['search_tobuy']) && dol_strlen($this->search['search_tobuy']) > 0 && $this->search['search_tobuy'] != -1)   $this->searchSql .= " AND p.tobuy = ".((int) $this->search['search_tobuy']);

		if ($this->search['sall']) $this->searchSql .= natural_search($this->fieldsToSearchAll, $this->search['sall']);

//		// if the type is not 1, we show all products (type = 0,2,3)
//		if (dol_strlen($this->search['search_type']) && $this->search['search_type'] != '-1'){
//			if ($this->search['search_type'] == 1) $this->searchSql .= " AND p.fk_product_type = 1";
//			else $this->searchSql .= " AND p.fk_product_type <> 1";
//		}

		if ($this->search['search_ref'])     $this->searchSql .= natural_search('p.ref', $this->search['search_ref']);
		if ($this->search['search_label'])   $this->searchSql .= natural_search('p.label', $this->search['search_label']);
		if ($this->search['search_barcode']) $this->searchSql .= natural_search('p.barcode', $this->search['search_barcode']);

		// Filter on supplier
		if (isModEnabled('fournisseur') && !empty($this->search['search_supplierref'])){
			$this->searchSql .= natural_search('pfp.ref_fourn', $this->search['search_supplierref']);
		}

		if ($this->search['catid'] > 0)     $this->searchSql .= " AND cp.fk_categorie = ".$this->search['catid'];
		if ($this->search['catid'] == -2)   $this->searchSql .= " AND cp.fk_categorie IS NULL";

		$this->searchearchCategoryProductSqlList = array();
		if ($this->search['search_category_product_operator'] == 1) {
			foreach ($this->search['search_category_product_list'] as $this->searchearchCategoryProduct) {
				if (intval($this->searchearchCategoryProduct) == -2) {
					$this->searchearchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
				} elseif (intval($this->searchearchCategoryProduct) > 0) {
					$this->searchearchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($this->searchearchCategoryProduct);
				}
			}
			if (!empty($this->searchearchCategoryProductSqlList)) {
				$this->searchSql .= " AND (".implode(' OR ', $this->searchearchCategoryProductSqlList).")";
			}
		} else {
			foreach ($this->search['search_category_product_list'] as $this->searchearchCategoryProduct) {
				if (intval($this->searchearchCategoryProduct) == -2) {
					$this->searchearchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
				} elseif (intval($this->searchearchCategoryProduct) > 0) {
					$this->searchearchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$this->searchearchCategoryProduct.")";
				}
			}
			if (!empty($this->searchearchCategoryProductSqlList)) {
				$this->searchSql .= " AND (".implode(' AND ', $this->searchearchCategoryProductSqlList).")";
			}
		}
		if ($this->search['fourn_id'] > 0)  $this->searchSql .= " AND pfp.fk_soc = ".((int) $this->search['fourn_id']);

		$output.= '<form id="product-search-dialog-form" class="--blur-on-loading" >';

		$output.= '<input type="hidden" name="token" value="'.$this->search['newToken'].'">';
		$output.= '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		$output.= '<input type="hidden" name="action" value="product-search-form">';
		$output.= '<input type="hidden" name="token" value="'.$this->search['newToken'].'">';
		$output.= '<input type="hidden" name="sortfield" value="'.$this->search['sortfield'].'">';
		$output.= '<input type="hidden" name="sortorder" value="'.$this->search['sortorder'].'">';
		//$output.= '<input type="hidden" name="page" value="'.$this->search['page'].'">';
		$output.= '<input type="hidden" name="type" value="'.$this->search['type'].'">';
		$output.= '<input type="hidden" name="fk_company" value="'.$this->search['fk_company'].'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-element" name="element" value="'.$this->search['element'].'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-fk-element" name="fk_element" value="'.$this->search['fk_element'].'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-fk-project" name="fk_project" value="'.$this->search['fk_project'].'">';
		$output.= '<input type="hidden" id="advancedproductsearch-form-default-customer-reduction" name="default_customer_reduction" value="'.floatval($object->thirdparty->remise_percent).'">';

		$globalCountResult = 0;
		$curentCountResult = 0;


		if($this->displayResults) {
			$querySearchRes = $db->query('SELECT ' . $this->searchSqlSelectCount . ' ' . $this->searchSql);
			if ($querySearchRes) {
				$obj = $db->fetch_object($querySearchRes);
				$globalCountResult = $obj->nb_results;

				$querySearchRes = $db->query('SELECT ' . $this->searchSqlSelect . ' ' . $this->searchSql . $db->plimit($this->search['limit'] + 1, $this->search['offset']));
				if ($querySearchRes) {
					$curentCountResult = $db->num_rows($querySearchRes);
				}
			}
		}

		$moreHtmlCenter= '<div class="advanced-product-global-search-container" >';

		foreach ($this->fieldsToSearchAllText as $i => $langKey){
			$this->fieldsToSearchAllText[$i] = $langs->trans($langKey);
		}
		$toolTip = $langs->trans('SearchWillBeOnTheseFields', '<br/>' . implode(', ' , $this->fieldsToSearchAllText));


		$moreHtmlCenter.= '<input name="sall" value="'.dol_htmlentities($this->search['sall']).'" id="search-all-form-input" class="advanced-product-global-search-input" placeholder="'.$langs->trans('Search').'" autocomplete="off">';
		$moreHtmlCenter.= '<i title="'.dol_escape_htmltag($toolTip).'" class="fa fa-question-circle classfortooltip"></i>';
		$moreHtmlCenter.= '</div>';

		$picto = 'product';
		if ($this->search['type'] == 1) $picto = 'service';

		ob_start(); // parceque dolibarr aime tellement le print ...
		print_barre_liste($langs->trans('AdvancedProductSearch'), $this->search['page'], $this->search['pageUrl'], $param, $this->search['sortfield'], $this->search['sortorder'], $moreHtmlCenter, $curentCountResult, $globalCountResult, $picto, 0, '', '', $this->search['limit'], 0, 0, 0);
		$output.= ob_get_contents();
		ob_end_clean();



		if($globalCountResult > 0){
			$output.= '<div class="advancedproductsearch__results-count">';
			if($globalCountResult>1){
				$output.= $langs->trans('resultsDisplayForNbResultsFounds', min($this->search['limit'],$globalCountResult), $globalCountResult );
			}
			else{
				$output.= $langs->trans('OneResultDisplayForOneResultFounds', min($this->search['limit'],$globalCountResult), $globalCountResult );
			}
			$output.= '</div>';
		}

		$moreForFilter = '';
		// Filter on supplier
		if (isModEnabled('fournisseur'))
		{
			$moreForFilter .= '<div class="divsearchfield" >';
			$moreForFilter .= $langs->trans('Supplier').': ';
			$moreForFilter .= $form->select_company($this->search['fourn_id'], 'fourn_id', '', 1, 'supplier');
			$moreForFilter .= '</div>';

			$moreForFilter .= '<div class="divsearchfield" >';
			$moreForFilter .= $langs->trans('SupplierRef').': ';
			$moreForFilter .= '<input type="text" name="search_supplierref" value="'.dol_htmlentities($this->search['search_supplierref']).'" />';
			$moreForFilter .= '</div>';
		}

		$moreForFilter .= '<div class="divsearchfield" >';
		$moreForFilter .= '<input type="hidden" name="search_tosell" value="0"/>';
		$moreForFilter .= '<input type="checkbox" id="search_tosell" class="valignmiddle" name="search_tosell" value="1" ' .($this->search['search_tosell'] == 1 ? ' checked="checked"' : '') .'/>';
		$moreForFilter .= '<label for="search_tosell" class="paddingright">' . $langs->trans('searchToSell') .'</label>';
		$moreForFilter .= '<input type="hidden" name="search_tobuy" value="0"/>';
		$moreForFilter .= '<input type="checkbox" id="search_tobuy" class="valignmiddle" name="search_tobuy"  value="1" ' .($this->search['search_tobuy'] == 1 ? ' checked="checked"' : '') .'/>';
		$moreForFilter .= '<label for="search_tobuy">' . $langs->trans('searchToBuy') .'</label>';
		$moreForFilter .= '</div>';


		// Filter on categories
		if (isModEnabled('categorie'))
		{
			$moreForFilter .= '<div class="divsearchfield" >';
			$moreForFilter .= $langs->trans('ProductCategories').': ';
			$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
			$categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
			$moreForFilter .= Form::multiselectarray('search_category_product_list', $categoriesProductArr, $this->search['search_category_product_list'], 0, 0, 'minwidth300');
			$moreForFilter .= ' <label><input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($this->search['search_category_product_operator'] == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories').'</label>';
			$moreForFilter .= '</div>';
		}

//	$parameters = array();
//	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
//	if (empty($reshook)) $moreForFilter .= $hookmanager->resPrint;
//	else $moreForFilter = $hookmanager->resPrint;

		if ($moreForFilter)
		{
			$output.= '<div class="liste_titre liste_titre_bydiv centpercent">';
			$output.= $moreForFilter;
			$output.= '</div>';
		}

		$colNumber = 8;

		$output.= '<table class="noborder centpercent advance-search-product-results" >';
		$output.= '<thead>';

		$output.= '<tr class="advanced-product-search-row --search liste_titre">';

		$output.= '	<th class="advanced-product-search-col --ref" >';
		$output.= ' <input type="text" class="flat"  name="search_ref" value="'.dol_htmlentities($this->search['search_ref']).'" placeholder="'.$langs->trans('SearchRef').'" />';
		$output.= '	</th>';

		$output.= '	<th class="advanced-product-search-col --label" >';
		$output.= ' <input type="text" class="flat"  name="search_label" value="'.dol_htmlentities($this->search['search_label']).'" placeholder="'.$langs->trans('SearchLabel').'" />';
		$output.= '	</th>';


		if(isModEnabled('stock')){
			$output.= '	<th class="advanced-product-search-col --stock-reel center" ></th>';
			$output.= '	<th class="advanced-product-search-col --stock-theorique center" ></th>';
		}

		if (isModEnabled('fournisseur')) {
			$output .= '	<th class="advanced-product-search-col --buy-price" ></th>';
		}

		$output.= '	<th class="advanced-product-search-col --subprice" ></th>';
		$output.= '	<th class="advanced-product-search-col --discount" ></th>';
		$output.= '	<th class="advanced-product-search-col --finalsubprice" ></th>';
		$output.= '	<th class="advanced-product-search-col --qty" ></th>';

		if (!empty(getDolGlobalString('PRODUCT_USE_UNITS'))) {
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
			. self::getDialogColSortLink($langs->trans('Ref'), $this->search['pageUrl'], "p.ref", $param, $this->search['sortfield'], $this->search['sortorder'], $classForSortLink)
			.'</th>';

		$output.= '	<th class="advanced-product-search-col --label" >'
			. self::getDialogColSortLink($langs->trans('Label'), $this->search['pageUrl'], "p.label", $param, $this->search['sortfield'], $this->search['sortorder'], $classForSortLink)
			.'</th>';

		if(isModEnabled('stock')){
			$output.= '	<th class="advanced-product-search-col --stock-reel center" >'.$langs->trans('RealStock').'</th>';
			$output.= '	<th class="advanced-product-search-col --stock-theorique center" >'.$langs->trans('VirtualStock').'</th>';
			$colNumber+=2;
		}
		if (isModEnabled('fournisseur')) {
			$colNumber++;
			$output .= '	<th class="advanced-product-search-col --buy-price" >' . ($isSupplier ? $langs->trans('PredefinedFournPricesForFill').img_help(1, $langs->trans('PredefinedFournPricesForFillHelp')) : $langs->trans('BuyPrice')) . '</th>';
		}
		$output.= '	<th class="advanced-product-search-col --subprice" >'.$langs->trans('Price').'</th>';
		$output.= '	<th class="advanced-product-search-col --discount" >'.$langs->trans('Discount').'</th>';
		$output.= '	<th class="advanced-product-search-col --finalsubprice" >'.$langs->trans('FinalDiscountSubPrice').'</th>';
		$output.= '	<th class="advanced-product-search-col --qty" >'.$langs->trans('Qty').'</th>';

		if (!empty(getDolGlobalString('PRODUCT_USE_UNITS'))) {
			$colNumber++;
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

		$this->searchSqlList = 'SELECT '.$this->searchSqlSelect.' '
			.$this->searchSql.$db->order($this->search['sortfield'], $this->search['sortorder'])
			.$db->plimit($this->search['limit'] + 1, $this->search['offset']);

		if($this->displayResults) {

			$querySearchRes = $db->query($this->searchSqlList);

			if ($querySearchRes) {
				if ($curentCountResult > 0) {
					while ($obj = $db->fetch_object($querySearchRes)) {
						$product = new Product($db);
						$resProd = $product->fetch($obj->rowid);
						if ($resProd > 0) {
							$product->load_stock();

							// Réduction par défaut du client
							$reduction = doubleval($object->thirdparty->remise_percent);
							if ($isSupplier) {
								$reduction = doubleval($object->thirdparty->remise_supplier_percent);
							}

							// Prix unitaire du produit avec prise en compte des niveau de prix et du client
							$this->searchubprice = self::getProductSellPrice($product->id, $this->search['fk_company']);
							if ($isSupplier) {
								$this->searchubprice = 0;
							}

							// calcule du prix unitaire final apres réduction
							$finalSubprice = $this->searchubprice - $this->searchubprice * $reduction / 100;

							// COMPTATIBILITE MODULE DISCOUNT RULE : RECHERCHE DE REGLE DE TARIFICATION
							if (isModEnabled('discountrules') && !$isSupplier) {
								if (!class_exists('DiscountSearch')) {
									dol_include_once('/discountrules/class/discountSearch.class.php');
								}
								if (class_exists('DiscountSearch')) { // Il est possible que le module soit supprimé mais pas désinstallé
									$discountSearch = new DiscountSearch($db);
									$this->searchubprice = DiscountRule::getProductSellPrice($product->id, $this->search['fk_company']);
									$discountSearchResult = $discountSearch->search(0, $product->id, $this->search['fk_company'], $this->search['fk_project']);
									if ($discountSearchResult->result) {
										// Mise en page du résultat
										$discountSearchResult->tpMsg = getDiscountRulesInterfaceMessageTpl($langs, $discountSearchResult, $action);
										$this->searchubprice = $discountSearchResult->subprice;
										$finalSubprice = $discountSearchResult->calcFinalSubprice();

										if (!empty($discountSearchResult->reduction)) {
											$reduction = $discountSearchResult->reduction;
										}
									}
								} else {
									setEventMessage($langs->trans('ErrorMissingModuleDiscountRule'));
								}
							}


							$output .= '<tr class="advanced-product-search-row --data" data-product="' . $product->id . '"  >';
							$output .= '<td class="advanced-product-search-col --ref" >' . $product->getNomUrl(1) . '</td>';
							$output .= '<td class="advanced-product-search-col --label" >' . self::highlightWordsOfSearchQuery($product->label, $this->search['search_label'] . ' ' . $this->search['sall']) . '</td>';
							if (isModEnabled('stock')) {
								$output .= '<td class="advanced-product-search-col --stock-reel" >' . $product->stock_reel . '</td>';
								$output .= '<td class="advanced-product-search-col --stock-theorique" >' . $product->stock_theorique . '</td>';
							}

							if (isModEnabled('fournisseur')) {
								$output .= '<td class="advanced-product-search-col --buy-price" >';
								$TFournPriceList = self::getFournPriceList($product->id, $isSupplier ? $object->socid : 0);
								if (!empty($TFournPriceList)) {
//						$output.= '<div class="default-visible" >'.price($product->pmp).'</div>';
//						$output.= '<div class="default-hidden" >';

									$this->searchSelectArray = array();
									$idSelected = '';

									foreach ($TFournPriceList as $TpriceInfos) {
										$this->searchSelectArray[$TpriceInfos['id']] = array(
											'label' => $TpriceInfos['label'],
											'data-up' => $TpriceInfos['price'],
											'data-fourn_qty' => $TpriceInfos['fourn_qty']
										);
										if (isModEnabled('margin')) {
											if (getDolGlobalInt('MARGIN_TYPE') == 1 && is_numeric($TpriceInfos['id'])) {
												$idSelected = $TpriceInfos['id'];
											} elseif (getDolGlobalString('MARGIN_TYPE') === 'pmp') {
												$idSelected = 'pmpprice';
											} elseif (getDolGlobalString('MARGIN_TYPE') === 'costprice') {
												$idSelected = 'costprice';
											}
										} else {
											if ($TpriceInfos['id'] == 'pmpprice' && !empty($TpriceInfos['price'])) {
												$idSelected = 'pmpprice';
											}
										}
									}

									if ($isSupplier) { // Seuls les prix fournisseurs nous intéressent dans le cadre d'un document fournisseur (pas de PMP ou autre dans ce cas)
										unset($this->searchSelectArray['pmpprice']);
										unset($this->searchSelectArray['costprice']);
										if (!empty($this->searchSelectArray)) {
											if (count($this->searchSelectArray) == 1 && ($object->element !== 'supplier_proposal' || getDolGlobalString('ADVANCED_PRODUCT_SEARCH_PRESELECT_IF_ONE_FOURN_PRICE_ON_SUPPLIER_PROPOSAL'))) {
												$idSelected = key($this->searchSelectArray);
												$this->searchubprice = $this->searchSelectArray[$idSelected]['data-up'];
												// Recalcul du subprice final
												$finalSubprice = $this->searchubprice - $this->searchubprice * $reduction / 100;
											}
											// On insère une valeur vide, car si plusieurs prix fourn, on laisse le choix à l'utilisateur de sélectionner celui qu'il souhaite
											$this->searchSelectArray[0] = array('data-up' => 0, 'data-fourn_qty' => 0);
										}
									}


									$key_in_label = 0;
									$value_as_key = 0;
									$moreparam = 'data-product="' . $product->id . '"';
									$translate = 0;
									$maxlen = 0;
									$disabled = 0;
									if ($isSupplier) $this->searchSort = 'ASC';
									else $this->searchSort = 'DESC';
									$morecss = 'search-list-select';
									$addjscombo = 0;
									if (!empty($this->searchSelectArray)) {
										$output .= $form->selectArray('prodfourprice-' . $product->id, $this->searchSelectArray, $idSelected, 0, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $this->searchSort, $morecss, $addjscombo);
									}
//						$output.= '</div>';
								} else {
									$output .= price($product->pmp);
								}
								$output .= '</td>';
							}


							//
							$output .= '<td class="advanced-product-search-col --subprice right nowraponall" >';
							$output .= '<input id="advanced-product-search-list-input-subprice-' . $product->id . '"  data-product="' . $product->id . '"   class="advanced-product-search-list-input-subprice right on-update-calc-prices" type="number" step="any" min="0" maxlength="8" size="3" value="' . $this->searchubprice . '" placeholder="x" name="prodsubprice[' . $product->id . ']" />';
							$output .= ' ' . $langs->trans("HT");
							$output .= '</td>';

							// REDUCTION EN %
							$output .= '<td class="advanced-product-search-col --discount center" >';
							$output .= '<input id="advanced-product-search-list-input-reduction-' . $product->id . '"  data-product="' . $product->id . '"   class="advanced-product-search-list-input-reduction center on-update-calc-prices" type="number" step="any" min="0" max="100" maxlength="3" size="3" value="' . $reduction . '" placeholder="%" name="prodreduction[' . $product->id . ']" />';
							$output .= '%';
							$output .= '</td>';

							// FINAL SUBPRICE AFTER REDUCTION
							$output .= '<td class="advanced-product-search-col --finalsubprice right" >';
							$output .= '<span id="discount-prod-list-final-subprice-' . $product->id . '"  class="final-subpriceprice" >' . price(round($finalSubprice, getDolGlobalString('MAIN_MAX_DECIMALS_UNIT'))) . '</span> ' . $langs->trans("HT");
							$output .= '</td>';

							// QTY
							$output .= '<td class="advanced-product-search-col --qty nowrap" >';
							$qty = 1;
							$qtyMin = 0;

							if (!empty($this->searchSelectArray)) {
								$currentlySelectedFournPrice = reset($this->searchSelectArray);
								if (!empty($currentlySelectedFournPrice['data-fourn_qty'])) {
									$qtyMin = doubleval($currentlySelectedFournPrice['data-fourn_qty']);
								}
							}
							// Si le quantity est affecter par un autre élément, plus tard.
							if ($qtyMin > $qty) {
								$qty = $qtyMin;
							}

							$output .= dolGetBadge('', $currentQtyByProduct[$product->id] ?? '','primary', '', '',
								[
									'attr'=>
										[
											'class' => 'advanced-product-search__badge-qty-doc',
											'data-product' => $product->id,
											'title' => $langs->trans('QtyAlreadyInDoc')
										]
								]
							);

							$output .= '<input id="advanced-product-search-list-input-qty-' . $product->id . '"  data-product="' . $product->id . '"  class="advanced-product-search-list-input-qty center on-update-calc-prices" type="number" step="any" min="' . $qtyMin . '" maxlength="8" size="3" value="' . $qty . '" placeholder="x" name="prodqty[' . $product->id . ']" />';
							$output .= '</td>';

							// UNITE
							if (!empty(getDolGlobalString('PRODUCT_USE_UNITS'))) {
								$output .= '<td class="advanced-product-search-col --unit" >';
								$output .= $product->getLabelOfUnit();
								$output .= '</td>';
							}

							$output .= '<td class="advanced-product-search-col --finalprice right" >';
							$finalPrice = $finalSubprice * $qty;
							$output .= '<span id="discount-prod-list-final-price-' . $product->id . '"  class="final-price" >' . price(round($finalPrice,  getDolGlobalString('MAIN_MAX_DECIMALS_TOT'))) . '</span> ' . $langs->trans("HT");
							$output .= '</td>';

							$output .= '<td class="advanced-product-search-col --action" >';
//					$output.= '<div class="default-hidden" >';
							$output .= ' <button type="button" title="' . $langs->trans('ClickToAddProductInDocument') . '"  data-product="' . $product->id . '" class="advance-prod-search-list-action-btn --addProductToLine" ><span class="fa fa-plus add-btn-icon"></span> ' . $langs->trans('Add') . '</button>';
//					$output.= '</div>';
							$output .= '</td>';

							$output .= '</tr>';
						} else {
							$output .= '<tr class="advanced-product-search-row">';
							$output .= '<td class="advanced-product-search-col-error center" colspan="' . $colNumber . '">' . $product->errorsToString() . '</td>';
							$output .= '</tr>';

						}

					}
				} else {
					$output .= '<tr class="advanced-product-search-row">';
					$output .= '<td class="advanced-product-search-col-no-result" colspan="' . $colNumber . '">' . $langs->trans("NoResults") . '</td>';
					$output .= '</tr>';

				}
			} else {
				$output .= '<tr class="advanced-product-search-row">';
				$output .= '<td class="advanced-product-search-col-error" colspan="' . $colNumber . '">' . $db->error() . '</td>';
				$output .= '</tr>';
			}
		} else {
			$output .= '<tr class="advanced-product-search-row">';
			$output .= '<td class="advanced-product-search-col-no-result" colspan="' . $colNumber . '">' . $langs->trans("launchYourFirstSearch") . '</td>';
			$output .= '</tr>';
		}

		$output.= '</tbody>';
		$output.= '</table>';
		$output.= '</form>';

		return $output;
	}


	/**
	 * @param $label	string	Translation key of field
	 * @param $pageUrl	string	Url used when we click on sort picto
	 * @param $field	string	Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
	 * @param $moreParams	string	Add more parameters on sort url links ("" by default)
	 * @param $sortfield	string Current field used to sort (Ex: 'd.datep,d.id')
	 * @param $sortorder	string Current sort order (Ex: 'asc,desc')
	 * @return string
	 */
	public static function getDialogColSortLink($label, $pageUrl, $field, $moreParams, $sortfield, $sortorder, $moreClass = ""){
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


		$out = '<a class="reposition '.$moreClass.'" href="'.$pageUrl.'?sortfield='.$field.'&sortorder='.$sortordertouseinlink.$moreParams.'" >';
		$out.= $label;
		$out.= '</a>';

		return $out;
	}

	/**
	 * Return an object
	 *
	 * @param string $objectType Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
	 * @param $db
	 * @return int object of $objecttype
	 */
	public static function objectAutoLoad($objectType, &$db)
	{
		global $conf;

		$ret = -1;
		$regs = array();

		// Parse $objecttype (ex: project_task)
		$module = $myobject = $objectType;

		// If we ask an resource form external module (instead of default path)
		if (preg_match('/^([^@]+)@([^@]+)$/i', $objectType, $regs)) {
			$myobject = $regs[1];
			$module = $regs[2];
		}


		if (preg_match('/^([^_]+)_([^_]+)/i', $objectType, $regs))
		{
			$module = $regs[1];
			$myobject = $regs[2];
		}

		// Generic case for $classpath
		$classpath = $module.'/class';

		// Special cases, to work with non standard path
		if ($objectType == 'facture' || $objectType == 'invoice') {
			$classpath = 'compta/facture/class';
			$module='facture';
			$myobject='facture';
		}
		elseif ($objectType == 'commande' || $objectType == 'order') {
			$classpath = 'commande/class';
			$module='commande';
			$myobject='commande';
		}
		elseif ($objectType == 'propal')  {
			$classpath = 'comm/propal/class';
		}
		elseif ($objectType == 'shipping') {
			$classpath = 'expedition/class';
			$myobject = 'expedition';
			$module = 'expedition_bon';
		}
		elseif ($objectType == 'delivery') {
			$classpath = 'livraison/class';
			$myobject = 'livraison';
			$module = 'livraison_bon';
		}
		elseif ($objectType == 'contract') {
			$classpath = 'contrat/class';
			$module='contrat';
			$myobject='contrat';
		}
		elseif ($objectType == 'member') {
			$classpath = 'adherents/class';
			$module='adherent';
			$myobject='adherent';
		}
		elseif ($objectType == 'cabinetmed_cons') {
			$classpath = 'cabinetmed/class';
			$module='cabinetmed';
			$myobject='cabinetmedcons';
		}
		elseif ($objectType == 'fichinter') {
			$classpath = 'fichinter/class';
			$module='ficheinter';
			$myobject='fichinter';
		}
		elseif ($objectType == 'task') {
			$classpath = 'projet/class';
			$module='projet';
			$myobject='task';
		}
		elseif ($objectType == 'stock') {
			$classpath = 'product/stock/class';
			$module='stock';
			$myobject='stock';
		}
		elseif ($objectType == 'inventory') {
			$classpath = 'product/inventory/class';
			$module='stock';
			$myobject='inventory';
		}
		elseif ($objectType == 'mo') {
			$classpath = 'mrp/class';
			$module='mrp';
			$myobject='mo';
		}

		// Generic case for $classfile and $classname
		$classfile = strtolower($myobject); $classname = ucfirst($myobject);
		//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname;

		if ($objectType == 'invoice_supplier') {
			$classfile = 'fournisseur.facture';
			$classname = 'FactureFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}
		elseif ($objectType == 'order_supplier') {
			$classfile = 'fournisseur.commande';
			$classname = 'CommandeFournisseur';
			$classpath = 'fourn/class';
			$module = 'fournisseur';
		}
		elseif ($objectType == 'supplier_proposal') {
			$classpath = 'supplier_proposal/class';
			$classfile = 'supplier_proposal';
			$classname = 'SupplierProposal';
			$module = 'supplier_proposal';
		}
		elseif ($objectType == 'stock') {
			$classpath = 'product/stock/class';
			$classfile = 'entrepot';
			$classname = 'Entrepot';
		}
		elseif ($objectType == 'dolresource') {
			$classpath = 'resource/class';
			$classfile = 'dolresource';
			$classname = 'Dolresource';
			$module = 'resource';
		}


		if (isModEnabled($module))
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

					$prices[] = array(
						"id" => $productSupplier->product_fourn_price_id,
						"price" => price2num($price, 0, '', 0),
						"label" => $label,
						"title" => $title,
						'ref' => $productSupplier->fourn_ref,
						'fourn_qty' => $productSupplier->fourn_qty
					); // For price field, we must use price2num(), for label or title, price()
				}
			}

			// After best supplier prices and before costprice
			if (isModEnabled('stock'))
			{
				// Add price for pmp
				$price = $producttmp->pmp;
				$prices[] = array(
					"id" => 'pmpprice',
					"price" => price2num($price),
					"label" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency),
					"title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency),
					'fourn_qty' => 0
				); // For price field, we must use price2num(), for label or title, price()
			}

			// Add price for costprice (at end)
			$price = $producttmp->cost_price;
			$prices[] = array(
				"id" => 'costprice',
				"price" => price2num($price),
				"label" => $langs->trans("CostPrice").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency),
				"title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency),
				'fourn_qty' => 0
			); // For price field, we must use price2num(), for label or title, price()
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
				$TSellPrice = $product->getSellPrice($mysoc, $societe);
				if (!empty($TSellPrice)) {
					$baseSubPrice = $TSellPrice['pu_ht'];
				}
			}

			// si pas de prix client alors on force sur le prix de la fiche produit
			if (empty($baseSubPrice)) {
				$baseSubPrice = $product->price;
			}

			return round(floatval($baseSubPrice), getDolGlobalString('MAIN_MAX_DECIMALS_UNIT'));
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

	public static function highlightWordsOfSearchQuery( $content, $searchQuery) {

		$words = explode(' ', $searchQuery);
		$words = array_unique($words);
		$words = array_map('trim', $words);
		// loop through words
		foreach( $words as $word ) {
			$content = self::highlightString( $content, $word); // highlight word
		}

		return $content; // return highlighted data
	}

	/**
	 * get final product description by adding custom code and country of origin if applicable.
	 *
	 * This function return the product description by appending the custom code and country of origin
	 * if the global configuration allows it and the product has these attributes.
	 *
	 * @global object $langs  The language object for translations.
	 * @global object $db     The database object.
	 * @param object $product The product object which contains the description, custom code, and country code.
	 * @return string The modified product description.
	 */
	public static function getFinalProductDescriptionForLine($product)
	{
		global $langs, $db;

		$desc = $product->description;
		if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE)
			&& (!empty($product->customcode) || !empty($product->country_code))) {

			$langs->load("products");

			$desc_tmp = '(' .
				$langs->transnoentitiesnoconv("CustomCode") . ': ' .
				$product->customcode . ' - ' .
				$langs->transnoentitiesnoconv("CountryOrigin") . ': ' .
				getCountry($product->country_code, 0, $db, $langs, 0) .
				')';

			$desc = dol_concatdesc($desc, $desc_tmp);
		}

		return $desc;
	}

}
