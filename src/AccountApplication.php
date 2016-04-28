<?php
namespace keeko\application\account;

use keeko\framework\foundation\AbstractApplication;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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
		$routes = $this->generateRoutes();
		$response = new Response();
		$context = new RequestContext($this->getAppPath());
		$matcher = new UrlMatcher($routes, $context);
		$prefs = $this->service->getPreferenceLoader()->getSystemPreferences();
		
		try {
			$path = str_replace('//', '/', '/' . $this->getDestinationPath());
			$match = $matcher->match($path);
			$route = $match['_route'];
			$user = $this->getServiceContainer()->getModuleManager()->load('keeko/user');
			$auth = $this->getServiceContainer()->getAuthManager();
			$kernel = $this->getServiceContainer()->getKernel();
			$main = '';
			
			switch ($route) {
				case 'index':
					// show dashboard
					if ($auth->isRecognized()) {
						$action = $user->loadAction('dashboard', 'html');
					}
					
					// show register and forget password
					else {
						$action = $user->loadAction('index', 'html');
					}
					
					$response = $kernel->handle($action, $request);
					$main = $response->getContent();
					break;
					
				case 'login':
					$login = '';
					$error = '';
					$redirect = $request->headers->get('referer');
					
					// try login
					if ($request->isMethod('POST')) {
						$login = $request->request->get('login');
						$password = $request->request->get('password');
						$redirect = $request->request->get('redirect');
					
						$auth = $this->getServiceContainer()->getAuthManager();
						if ($auth->login($login, $password)) {
							$token = $auth->getSession()->getToken();
							$foward = $redirect ?: $this->getAppUrl();
							$response = new RedirectResponse($foward);
							$response->headers->setCookie(new Cookie('Bearer', $token));
							return $response;
						}
					
						$error = 'Invalid credentials';
					}

					$form = $user->getWidgetFactory()->createLoginWidget();
					$form = $form->build([
						'error' => $error,
						'destination' => $this->getAppUrl() . '/login',
						'redirect' => $redirect,
						'login' => $login
					]);

					$main = $this->render('/keeko/account-app/templates/login.twig', [
						'form' => $form
					]);
					break;
					
				case 'logout':
					$auth = $this->getServiceContainer()->getAuthManager();
					$auth->logout();
					return new RedirectResponse($this->getAppUrl());
			}
			
			$userWidget = $user->loadAction('user-widget', 'html');
			$response = $kernel->handle($userWidget, $request);
			$userWidget = $response->getContent();
			
			$response->setContent($this->render('/keeko/account-app/templates/main.twig', [
				'user_widget' => $userWidget,
				'main' => $main,
			]));
			
		} catch (ResourceNotFoundException $e) {
			$response->setStatusCode(Response::HTTP_NOT_FOUND);
		}

		return $response;
	}

	private function generateRoutes() {
		$routes = new RouteCollection();
		$routes->add('index', new Route('/'));
		$routes->add('register', new Route('/register'));
		$routes->add('login', new Route('/login'));
		$routes->add('logout', new Route('/logout'));
		$routes->add('forget-password', new Route('/forget-password'));
		return $routes;
	}
}
