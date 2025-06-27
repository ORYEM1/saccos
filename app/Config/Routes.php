<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->get('/', 'Home::index');
$routes->match(['get', 'post'],'/users', 'Users::index');
$routes->match(['get', 'post'],'/users/new_user', 'Users::new_user');
$routes->match(['get', 'post'],'/login', 'Login::index');

//loan
$routes->match(['get','post'],'/loans', 'Loans::index');
$routes->match(['get','post'],'/loans/new_loan', 'Loans::new_loan');
$routes->match(['get','post'],'/loans/view_loan/(:any)', 'Loan::view_loan/$1');
$routes->match(['get','post'],'/loans/delete_loan/(:any)', 'Loan::delete_loan/$1');

//savings
$routes->match(['get','post'],'/savings', 'Savings::index');
$routes->match(['get','post'],'/savings/new_saving', 'Savings::new_saving');
$routes->match(['get','post'],'savings/view_saving/(:any)', 'Savings::view_saving/$1');
$routes->match(['get','post'],'/savings/edit_saving/(:any)', 'Savings::edit_saving/$1');


