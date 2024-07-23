# Changelog for OMX branch

## [2.2.0 experimental release]
- Updated omniva-api library to dev-omx-api-rework
- Downgraded setasign/fpdf 1.8.2
- Updated setasign/fpdi to v2.6.0
- Updated tecnickcom/tcpdf to 6.7.5
- Added support for multiple packages and consolidation
- Added support for Fragile and Delivery to adult additional services
- Changed how label history display service codes
- Weight class finding should now work with russian language
- Max weight chck is now 25Kg
- Courier calls can be canceled

## [2.1.7]
- Improved module work in Journal 3.0.46
- Allow FI terminals for LV senders
- Added new experimental quickcheckouts support (Opencart 3):
    - Unicheckout (Unicheckout)

## [2.1.6]
- Changed name of Finland delivery methods from Omniva to Matkahuolto
- Updated terminal map js library to 1.1.4
- Changed the Omniva marker to Matkahuolto on the map of Finland parcel terminals

## [2.1.5]
- Created an error message that COD payment is not available when sending to Matkahuolto parcel terminal in Finland

## [2.1.4]
- Fixed an issue where terminal listwas unintentionaly limited to non estonian contrancts only

## [2.1.3]
- Updated omniva-api library to 1.0.15 

## [2.1.2]
- Added codebase to allow FI terminals for EE buiseness clients
- Updated omniva-api library to 1.0.14
- Updated terminal map js library to 1.1.3
- Improved how tranlsations are loaded into templates for Opencart 2.3
- It is now recomended to have PHP 7+ version

## [2.1.1]
- Added new experimental quickcheckouts support (Opencart 3):
    - Custom Onepcheckout (Onepc)
- Fixed secure Omniva ajax URL
- Fixed an issue with barcode strings after switching from JSON array to just string. Should handle both old format and new.

## [2.1.0]
- Removed previous Journal 3 experimental support.
- Added new experimental quickcheckouts support (Opencart 3):
    - Journal 3 (Journal3)
    - Custom Quickcheckout (Cqc)
    - d_quickcheckout ajax version (QcdAjax)
    - Posible conflicts if omniva_m module has been previously customized. 
- Added new module settings to set specific order status upon registering label
- Added new module setting to enable / disable cart weight check for terminals (default: No)
- Added new module setting to enable / disable simple cart fit into terminal check (default: No)
- Updated omniva-api library to 1.0.10
- Removed offload postcode element from PK service request

## [2.0.6] - Improvements
- added simplified support for Journal 3 Theme on OpenCart 3
- fixed a typo in parameter name within front.js
- fixed omniva_m.tpl using incorrect template variables

## [2.0.5] - Dependencies update
- updated omniva-api library to 1.0.6.1

## [2.0.4] - PK Service
- fixed an issue when PK service is used. Since this service requires offloadPostcode, receiver postcode will be used

## [2.0.3] - Public release
- fixed composer autoload. Used Composer 2.4.2 to generate autoloader

## [2.0.2] - Public release
- Updated mijora/omniva-api library to 1.0.4
- Fixed bad variable in order-info-panel.tpl
- Removed unused tab html from omniva_m.tpl and omniva_m.twig

## [2.0.1] - Public release

## [2.0.0] - Initial release
