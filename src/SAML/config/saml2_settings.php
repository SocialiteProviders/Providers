<?php

return $settings = array(

    /**
     * Array of IDP prefixes to be configured e.g. 'idpNames' => ['test1', 'test2', 'test3'],
     * Separate routes will be automatically registered for each IDP specified with IDP name as prefix
     * Separate config file saml2/<idpName>_idp_settings.php should be added & configured accordingly
     */
    'idpNames' => ['default'],

    /**
     * If 'useRoutes' is set to true, the package defines five new routes for reach entry in idpNames:
     *
     *    Method | URI                                | Name
     *    -------|------------------------------------|------------------
     *    POST   | {routesPrefix}/{idpName}/acs       | saml_acs
     *    GET    | {routesPrefix}/{idpName}/login     | saml_login
     *    GET    | {routesPrefix}/{idpName}/logout    | saml_logout
     *    GET    | {routesPrefix}/{idpName}/metadata  | saml_metadata
     *    GET    | {routesPrefix}/{idpName}/sls       | saml_sls
     */
    'useRoutes' => false,

    /**
     * Optional, leave empty if you want the defined routes to be top level, i.e. "/{idpName}/*"
     */
    'routesPrefix' => '/sso/saml',

    /**
     * which middleware group to use for the saml routes
     * Laravel 5.2 will need a group which includes StartSession
     */
    'routesMiddleware' => [],

    /**
     * Indicates how the parameters will be
     * retrieved from the sls request for signature validation
     */
    'retrieveParametersFromServer' => false,

    /**
     * Where to redirect after logout
     */
    'logoutRoute' => '/',

    /**
     * Where to redirect after login if no other option was provided
     */
    'loginRoute' => '/',

    /**
     * Where to redirect after login if no other option was provided
     */
    'errorRoute' => '/',

    // If 'proxyVars' is True, then the Saml lib will trust proxy headers
    // e.g X-Forwarded-Proto / HTTP_X_FORWARDED_PROTO. This is useful if
    // your application is running behind a load balancer which terminates
    // SSL.
    'proxyVars' => false,

    /**
     * (Optional) Which class implements the route functions.
     * If commented out, defaults to this lib's controller (Aacotroneo\Saml2\Http\Controllers\Saml2Controller).
     * If you need to extend Saml2Controller (e.g. to override the `login()` function to pass
     * a `$returnTo` argument), this value allows you to pass your own controller, and have
     * it used in the routes definition.
     */
     // 'saml2_controller' => '',
);
