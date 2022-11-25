<?php

declare(strict_types=1);

namespace RLTSquare\CustomEmailSender\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 */
class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/rltsquare.log';
}
