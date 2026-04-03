<?php

declare(strict_types=1);

namespace App\Container;

use Laminas\Filter\FilterPluginManager;
use Laminas\Form\FormElementManager;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ValidatorPluginManager;

class LaminasServiceManagerFactory
{
    public static function create(): ServiceManager
    {
        $sm = new ServiceManager();

        $validatorManager = new ValidatorPluginManager();
        $validatorManager->setServiceLocator($sm);

        $filterManager = new FilterPluginManager();
        $filterManager->setServiceLocator($sm);

        $inputFilterManager = new InputFilterPluginManager();
        $inputFilterManager->setServiceLocator($sm);

        $formElementManager = new FormElementManager();
        $formElementManager->setServiceLocator($sm);

        $sm->setService('ValidatorPluginManager', $validatorManager);
        $sm->setService('ValidatorManager', $validatorManager);
        $sm->setService('FilterPluginManager', $filterManager);
        $sm->setService('FilterManager', $filterManager);
        $sm->setService('InputFilterManager', $inputFilterManager);
        $sm->setService('FormElementManager', $formElementManager);

        return $sm;
    }
}
