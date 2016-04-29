<?php
namespace keeko\application\account;

use keeko\framework\foundation\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
// 		$prefs = $this->service->getPreferenceLoader()->getSystemPreferences();
		
		$kernel = $this->getServiceContainer()->getKernel();
		$user = $this->getServiceContainer()->getModuleManager()->load('keeko/user');
		$this->getDestinationPath();
		
		$userWidget = $user->loadAction('user-widget', 'html');
		$userWidget = $kernel->handle($userWidget, $request);
		
		$account = $user->loadAction('account', 'html');
		$account = $kernel->handle($account, $request);
		
		if ($account instanceof RedirectResponse) {
			return $account;
		}
		
		$response = new Response();
		$response->setContent($this->render('/keeko/account-app/templates/main.twig', [
			'user_widget' => $userWidget->getContent(),
			'main' => $account->getContent(),
		]));

		return $response;
	}
}
