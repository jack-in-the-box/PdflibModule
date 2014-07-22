<?php
/**
 * Base module for integration of Pdflib projects with ZF2 applications
 *
 * @license MIT
 * @link    http://www.jack.fr/
 * @author  Martin Supiot <msupiot@jack.fr>
 */

return array(
    'service_manager' => array(
        'factories' =>  array(
            'PdflibModule\Service\PdflibServiceFactory' => 'PdflibModule\Service\PdflibServiceFactory',
        ),
    ),
);
