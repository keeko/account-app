<?php
namespace keeko\application\account;

use keeko\framework\foundation\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use keeko\framework\exceptions\PermissionDeniedException;

/**
 * Keeko Account-app
 * 
 * @license MIT
 * @author gossi
 */
class AccountApplication extends AbstractApplication {

	/**
	 * @param Request $request
	 * @param string $path
	 */
	public function run(Request $request) {
		$kernel = $this->getServiceContainer()->getKernel();
		$module = $this->getServiceContainer()->getModuleManager()->load('keeko/account');

		$widget = $module->loadAction('account-widget', 'html');
		$widget = $kernel->handle($widget, $request);
		
		try {
			$account = $module->loadAction('account', 'html');
			$account = $kernel->handle($account, $request);
			
			if ($account instanceof RedirectResponse) {
				return $account;
			}
			
			$main = $account->getContent();
		} catch (PermissionDeniedException $e) {
			$main = 'Permission Denied';
		}
		
		$response = new Response();
		$response->setContent($this->render('/keeko/account-app/templates/main.twig', [
			'account_widget' => $widget->getContent(),
			'main' => $main,
		]));

		return $response;
	}
}
