<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;


final class ErrorPresenter extends BasePresenter implements Nette\Application\IPresenter
{
	use Nette\SmartObject;

	/** @var \Tracy\ILogger */
	private $logger;


	public function __construct(ILogger $logger)
	{
	    parent::__construct();
		$this->logger = $logger;
	}


	public function run(Nette\Application\Request $request): Nette\Application\IResponse
	{
		$exception = $request->getParameter('exception');

		if ($exception instanceof \Nette\Application\BadRequestException) {
			list($module, , $sep) = \Nette\Application\Helpers::splitName($request->getPresenterName());
			return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
		}

		$this->logger->log($exception, ILogger::EXCEPTION);
		return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
			if (!\preg_match('#^text/html(?:;|$)#', (string)$httpResponse->getHeader('Content-Type'))) {
				return;
			}
            require __DIR__ . '/templates/Error/500.phtml';
		});
	}
}
