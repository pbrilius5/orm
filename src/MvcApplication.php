<?php

declare(strict_types=1);

namespace App;

use App\Container\EventServiceProvider;
use App\Container\FlysystemServiceProvider;
use App\Container\MvcServiceProvider;
use App\Command\CommandBusInterface;
use App\Dto\DtoFactory;
use App\Form\GroupForm;
use App\Form\UserForm;
use GuzzleHttp\Psr7\ServerRequest;
use League\Route\Http\Exception\NotFoundException;
use Prototype\Mvc\Http\RequestHandler;
use App\View\ViewRenderer;
use App\View\Helper\FormHelper;
use App\Routing\MvcRoutes;
use DI\ContainerBuilder;
use DI\Container as PhpDiContainer;
use Laminas\ServiceManager\ServiceManager;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Oryx\ORM\EntityManager;
use Psr\Log\LoggerInterface;

use function DI\autowire;

class MvcApplication
{
    private ViewRenderer $view;
    private MvcRoutes $mvcRoutes;
    private Container $leagueContainer;
    private PhpDiContainer $phpDiContainer;
    private ServiceManager $laminasSm;
    private ?LoggerInterface $logger = null;

    public function __construct(EntityManager $em)
    {
        $this->leagueContainer = new Container();
        $this->leagueContainer->delegate(new ReflectionContainer());
        $this->leagueContainer->addServiceProvider(new MvcServiceProvider());
        $this->leagueContainer->addServiceProvider(new FlysystemServiceProvider());
        $this->leagueContainer->addServiceProvider(new EventServiceProvider());

        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAttributes(true);
        $this->phpDiContainer = $builder->build();

        // Register interface -> implementation mapping used by the app.
        // Kernel registers this mapping in a different container; MvcApplication
        // needs it in its own PHP-DI container as it resolves services itself.
        $this->phpDiContainer->set(\App\Service\WorkGroupMapInterface::class, autowire(\App\Service\WorkGroupMap::class));

        $this->phpDiContainer->set(EntityManager::class, $em);
        // Ensure ViewRenderer is available in PHP-DI container for MvcApplication's view rendering
        $this->phpDiContainer->set(ViewRenderer::class, autowire());

        $this->view = $this->phpDiContainer->get(ViewRenderer::class); // Use PHP-DI's ViewRenderer
        $this->laminasSm = $this->leagueContainer->get(ServiceManager::class); // Re-inserting initialization

        $this->mvcRoutes = new MvcRoutes(
            $em,
            $this->leagueContainer->get(CommandBusInterface::class), // CommandBus from League\Container
            $this->leagueContainer->get(DtoFactory::class),         // DtoFactory from League\Container
            $this->leagueContainer->get(LoggerInterface::class),    // Logger from League\Container
            $this->view,                                             // ViewRenderer from PHP-DI container
            $this->laminasSm,                                        // Laminas ServiceManager
            $this->phpDiContainer                                   // PHP-DI Container
        );



    }

    public function run(): void
    {
        $this->logger?->debug('MvcApplication processing request', [
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        ]);

        $request = ServerRequest::fromGlobals();

        try {
            $response = $this->mvcRoutes->getRouter()->dispatch($request);
            RequestHandler::emit($response);
        } catch (NotFoundException $e) {
            http_response_code(404);
            echo $this->view->render('error/404', ['message' => 'Page not found']);
        }
    }
}
