# CHANGELOG ADVANCEDPRODUCTSEARCH FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)


# NOT RELEASED

- NEW : Display qty of current document  - *12/09/2024* - 1.8.0
- NEW : Setup conf for default search behavior  - *12/09/2024* - 1.7.0

## Release 1.6
- FIX : Permettre de lister les produits hors achat / hors vente - *25/09/2024* - 1.6.2
- FIX : Forgot to remove a js call to getnewtoken during compat - *20/08/2024* - 1.6.1
- FIX : Compat v20
  Changed Dolibarr compatibility range to 16 min - 20 max - *01/08/2024* - 1.6.0

## Release 1.5
- FIX : Rend le code propre du FIX 1.5.4 - *19/06/2024* - 1.5.5
- FIX : DA025087 - L'origine et la nomenclature douanière du produit ne sont pas ajoutés à la description - *17/06/2024* - 1.5.4
- FIX : DA024805 - Mauvaise définition des droits fournisseurs - *16/04/2024* - 1.5.3
- FIX : Search all filter doesn't apply all filters - *04/04/2024* - 1.5.2  
- FIX : "Undefined" dans champs de recherche - *22/01/2024* - 1.5.1  
- NEW : Compat V19 et php 8.2 *04/12/2023* - 1.5.0  
    Changed Dolibarr compatibility range to 15 min - 19 max  
    Change PHP compatibility range to 7.0 min - 8.2 max
- FIX : User experience - *25/11/2023* - 1.4.1
- NEW : Factoring and improve speed - *08/08/2022* - 1.4.0

## Release 1.3

- FIX : PHP 8 - *08/08/2022* - 1.3.4
- FIX : Compatibilité V16 - *13/06/2022* - 1.3.3
- FIX : Minimum quantity apply - *29/04/2022* - 1.3.2
- FIX : Token transfert in js - *24/03/2022* - 1.3.1
- FIX : js file doesn't need to be PHP file - *03/02/2022* - 1.3.0
- FIX : css file doesn't need to be PHP file - *25/01/2022* - 1.2.0

## Release 1.1

- FIX : Wrong parameter for multiprice - *02/02/2021* - 1.1.4
- FIX : Page reload prevent action param in url or post - *19/01/2021* - 1.1.3
- FIX : Missing langs and help infos - *13/12/2021* - 1.1.2
- FIX : By default, no supplier price preselection on supplier quote, and conf ADVANCED_PRODUCT_SEARCH_PRESELECT_IF_ONE_FOURN_PRICE_ON_SUPPLIER_PROPOSAL to restore preselection - *08/12/2021* - 1.1.1
- NEW : handle supplier documents - *08/12/2021* - 1.1.0

## Release 1.0

- FIX : We can't add products or services after pop-in page change - *2022-01-20* - 1.0.2
- FIX : signature for `addline` not consistent across objects (proposal, order, invoice) - *08/10/2021* - 1.0.1
- Initial version
