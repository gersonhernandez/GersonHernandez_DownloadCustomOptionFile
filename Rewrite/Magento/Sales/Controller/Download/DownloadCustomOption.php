<?php
/**
 * Copyright © Gerson Hernández. Magento Developer from El Salvador. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace GersonHernandez\DownloadCustomOptionFile\Rewrite\Magento\Sales\Controller\Download;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Psr\Log\LoggerInterface;

class DownloadCustomOption extends \Magento\Sales\Controller\Download\DownloadCustomOption
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Sales\Model\Download
     */
    protected $download;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     */
    protected $unserialize;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Sales\Model\Download $download
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param CollectionFactory $itemCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        \Magento\Sales\Model\Download $download,
        \Magento\Framework\Unserialize\Unserialize $unserialize,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        CollectionFactory $itemCollectionFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context, $resultForwardFactory, $download, $unserialize);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->download = $download;
        $this->unserialize = $unserialize;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Custom options download action
     *
     * @return void|\Magento\Framework\Controller\Result\Forward
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $quoteItemOptionId = $this->getRequest()->getParam('id');
        /** @var $option \Magento\Quote\Model\Quote\Item\Option */
        $option = $this->_objectManager->create(
            \Magento\Quote\Model\Quote\Item\Option::class
        )->load($quoteItemOptionId);
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();

        if (!$option->getId()) {

            try {
                $key = $this->getRequest()->getParam('key');
                $itemCollection = $this->itemCollectionFactory->create();
                $itemCollection->addFieldToFilter('product_options', ['like' => '%' . $key . '%']);
        
                $item = $itemCollection->getFirstItem();
                $options = $item->getProductOptions()['options'];
                foreach ($options as $opt) {
                    if ($opt['option_type'] == 'file') {
                        $info = $this->serializer->unserialize($opt['option_value']);
                        if ($info['secret_key'] == $key) {
                            $this->download->downloadFile($info);
                        }
                    }
                }
            } catch (\Throwable $th) {
                $this->logger->debug($th->getMessage());
                return $resultForward->forward('noroute');
            }
        }

        $optionId = null;
        if ($option->getCode() && strpos($option->getCode(), AbstractType::OPTION_PREFIX) === 0) {
            $optionId = str_replace(AbstractType::OPTION_PREFIX, '', $option->getCode());
            if ((int)$optionId != $optionId) {
                $optionId = null;
            }
        }
        $productOption = null;
        if ($optionId) {
            /** @var $productOption \Magento\Catalog\Model\Product\Option */
            $productOption = $this->_objectManager->create(
                \Magento\Catalog\Model\Product\Option::class
            );
            $productOption->load($optionId);
        }

        if ($productOption->getId() && $productOption->getType() != 'file') {
            return $resultForward->forward('noroute');
        }

        try {
            $info = $this->serializer->unserialize($option->getValue());
            if ($this->getRequest()->getParam('key') != $info['secret_key']) {
                return $resultForward->forward('noroute');
            }
            $this->download->downloadFile($info);
        } catch (\Exception $e) {
            return $resultForward->forward('noroute');
        }
        $this->endExecute();
    }

    /**
     * Ends execution process
     *
     * @return void
     */
    protected function endExecute()
    {
        // phpcs:ignore Magento2.Security.LanguageConstruct.ExitUsage
        exit(0);
    }
}
