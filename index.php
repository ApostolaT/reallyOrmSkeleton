<?php

require __DIR__ . '/vendor/autoload.php';

use ReallyOrm\Test\Entity\User;
use ReallyOrm\Test\Hydrator\Hydrator;

////////// hydrater
//$user = new User();
//$user->setName('ciwawa');
//$user->setEmail('email');;
//
//$hydrator = new Hydrator();
//
////var_dump($hydrator->extract($user));
//
//$obj = $hydrator->hydrate("ReallyOrm\Test\Entity\User", ["name" => "ion", "email" => "123"]);
//
//var_dump($obj->getName());
//var_dump($obj->getEmail());

//
//
//$regex = new ReallyOrm\Test\Regex\Regex();
//var_dump($regex->createTableName("User"));