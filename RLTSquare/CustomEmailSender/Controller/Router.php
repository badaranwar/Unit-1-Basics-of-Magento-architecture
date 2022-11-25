<?php

declare(strict_types=1);

namespace RLTSquare\CustomEmailSender\Controller;

use Exception;
use Magento\Email\Model\BackendTemplate;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use RLTSquare\CustomEmailSender\Logger\Logger;

/**
 * Class Router
 */
class Router implements RouterInterface
{
    /**
     *
     */
    const Custom_Email_Config_Path = 'custom_email/general/enable';
    /**
     *
     */
    const From_Email_Config_Path = 'custom_email/general/from_email';
    /**
     *
     */
    const From_Name_Config_Path = 'custom_email/general/from_name';
    /**
     *
     */
    const To_Email_Config_Path = 'custom_email/general/to_email';
    /**
     * @var PageFactory
     */
    protected PageFactory $pageFactory;
    /**
     * @var Logger
     */
    protected Logger $logger;
    /**
     * @var TransportBuilder
     */
    protected TransportBuilder $transportBuilder;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var StateInterface
     */
    protected StateInterface $inlineTranslation;
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
    /**
     * @var BackendTemplate
     */
    protected BackendTemplate $emailTemplate;
    /**
     * @var ActionFactory
     */
    private ActionFactory $actionFactory;

    /**
     * Router constructor.
     *
     * @param ActionFactory $actionFactory
     * @param PageFactory $pageFactory
     * @param Logger $logger
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $state
     * @param ScopeConfigInterface $scopeConfig
     * @param BackendTemplate $emailTemplate
     */
    public function __construct(
        ActionFactory         $actionFactory,
        PageFactory           $pageFactory,
        Logger                $logger,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface        $state,
        ScopeConfigInterface  $scopeConfig,
        BackendTemplate       $emailTemplate
    ) {
        $this->actionFactory = $actionFactory;
        $this->pageFactory = $pageFactory;
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->scopeConfig = $scopeConfig;
        $this->emailTemplate = $emailTemplate;
    }

    /**
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        $identifier = trim($request->getPathInfo(), '/');

        if (strpos($identifier, 'rltsquare') !== false) {
            $request->setModuleName('custom_email');
            $request->setControllerName('index');
            $request->setActionName('index');
            $request->setParams([
                'first_param' => 'first_value',
                'second_param' => 'second_value'
            ]);

            $isEnabled = $this->scopeConfig->getValue(
                self::Custom_Email_Config_Path,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );

            if ($isEnabled) {
                echo "Test";
                $this->sendEmail();
                $this->logger->info('Page visited');
                exit;
            }
            echo 'Custom Email Module is disabled. Please Enable Module from Admin Configuration.';
            return $this->actionFactory->create(Forward::class);
        }
        return null;
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function sendEmail(): void
    {
        // template id
        $email_template = $this->emailTemplate->load('test_email_template', 'orig_template_code');

        // sender email id
        $fromEmail = $this->scopeConfig->getValue(
            self::From_Email_Config_Path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        // sender name
        $fromName = $this->scopeConfig->getValue(
            self::From_Name_Config_Path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        // receiver email id
        $toEmail = $this->scopeConfig->getValue(
            self::To_Email_Config_Path,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        try {
            $templateVars = [];
            $storeId = $this->storeManager->getStore()->getId();
            $from = ['email' => $fromEmail, 'name' => $fromName];
            $this->inlineTranslation->suspend();
            $templateOptions = [
                'area' => Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($email_template->getId())
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFromByScope($from)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
