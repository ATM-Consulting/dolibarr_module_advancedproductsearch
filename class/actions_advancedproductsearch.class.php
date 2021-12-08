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
 */

/**
 * \file    class/actions_advancedproductsearch.class.php
 * \ingroup advancedproductsearch
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsAdvancedProductSearch
 */
class ActionsAdvancedProductSearch {
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}


	/**
	 * @param User $user
	 * @param CommonObject $object
	 * @return bool
	 */
	public static function checkUserUpdateObjectRight($user, $object, $rightToTest = 'creer'){
		$right = false;
		if($object->element == 'propal'){
			$right = $user->rights->propal->{$rightToTest};
		}
		elseif($object->element == 'commande'){
			$right = $user->rights->commande->{$rightToTest};
		}
		elseif($object->element == 'facture'){
			$right = $user->rights->facture->{$rightToTest};
		}

		return $right;
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);

		$langs->loadLangs(array('advancedproductsearch@advancedproductsearch'));
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context)
			|| in_array('supplier_proposalcard', $context) || in_array('ordersuppliercard', $context) || in_array('invoicesuppliercard', $context))
		{
			/** @var CommonObject $object */

			// STATUS DRAFT ONLY AND NOT IN EDIT MODE
		    if(!empty($object->statut) || $action=='editline'){
		        return 0;
		    }


			$TWriteRight = array(
				'commande' => $user->rights->commande->creer,
				'propal' => $user->rights->propal->creer,
				'facture' => $user->rights->facture->creer,
				'invoice_supplier' => $user->rights->fournisseur->facture->creer,
				'order_supplier' => $user->rights->fournisseur->commande->creer,
				'supplier_proposal' => $user->rights->supplier_proposal->creer
			);

			if (($user->socid > 0 || empty($TWriteRight[$object->element]))) {
				return 0;
			}
			// ADD DISCOUNT RULES SEARCH ON DOCUMENT ADD LINE FORM
			print '<!-- MODULE advanced-product-search -->'."\r\n";
			print '<button type="button" id="product-search-dialog-button" class="classfortooltip" data-target-element="'.$object->element.'" data-target-id="'.$object->id.'" title="'.$langs->trans("OpenSearchProductBox").'" ><i class="fa fa-search" ></i></button>';
			?>
			<script type="text/javascript">
				$(document).ready(function(){
					// ADD SEARCH BOX BUTTON
					$( "#idprod,#idprodfournprice" ).parent().append($("#product-search-dialog-button"));
				});
			</script>
			<!-- END MODULE advanced-product-search -->
			<?php
		}
	}
}
