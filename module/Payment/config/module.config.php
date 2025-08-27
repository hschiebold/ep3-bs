<?php

return array(
    'router' => array(
        'routes' => array(
            'payment' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/payment',
                    'defaults' => array(
                        'controller' => 'Payment\Controller\Payment',
                    ),
                ),
                'may_terminate' => false,
                'child_routes'  => array(
                    'booking' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/booking',
                        ),
                        'may_terminate' => false,
                        'child_routes'  => array(
                            'done' => array(
                                'type' => 'segment',
                                'options' => array(
                                    'route'    => '/done[/:payum_token]',
                                    'defaults' => array(
                                        'action' => 'done',
                                    ),
                                ),
                            ),
                            'confirm' => array(
                                'type' => 'segment',
                                'options' => array(
                                    'route'    => '/confirm[/:payum_token]',
                                    'defaults' => array(
                                        'action' => 'confirm',
                                    ),
                                ),
                            ),
                            'webhook' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route'    => '/webhook',
                                    'defaults' => array(
                                        'action' => 'webhook',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Payment\Controller\Payment' => 'Payment\Controller\PaymentController',
        ),
    ),

    'service_manager' => array(
        'factories' => array(
            'Payment\Service\PaymentService' => 'Payment\Service\PaymentServiceFactory',
            'Zend\Session\Config\ConfigInterface' => 'Zend\Session\Service\SessionConfigFactory',
            'Zend\Session\SessionManager' => 'Zend\Session\Service\SessionManagerFactory',
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
