# GersonHernandez DownloadCustomOptionFile

## Main Functionalities
Fix the 404 error in the /sales/download/downloadCustomOption/ path to download images in Magento2.

### Installation

 - Go to the root directory of your magento installation
 - Unzip the zip file in `app/code/GersonHernandez/DownloadCustomOptionFile`
 - Enable the module by running `php bin/magento module:enable GersonHernandez_DownloadCustomOptionFile`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:clean`