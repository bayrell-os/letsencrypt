<?php

/*!
 * Let’s Encrypt for Cloud OS
 * 
 * MIT License
 * 
 * Copyright (c) 2022 "Ildar Bikmamatov" <support@bayrell.org>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace App;


class Module
{
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		add_chain("init_app", static::class, "init_app");
		add_chain("init_di_defs", static::class, "init_di_defs", CHAIN_LAST);
		add_chain("register_entities", static::class, "register_entities", CHAIN_LAST);
		add_chain("request_before", static::class, "request_before");
		add_chain("method_not_found", static::class, "method_not_found");
		add_chain("routes", static::class, "routes");
		add_chain("base_url", static::class, "base_url");
		add_chain("twig_opt", static::class, "twig_opt");
		add_chain("bus_gateway", static::class, "bus_gateway");
	}
	
	
	
	/**
	 * Init app
	 */
	static function init_app()
	{
	}
	
	
	
	/**
	 * Init defs
	 */
	static function init_di_defs($res)
	{
		$defs = $res->defs;
		
		/* Setup bus key */
		$defs["settings"]["bus_key"] = env("CLOUD_OS_KEY");
		
		$res->defs = $defs;
	}
	
	
	
	/**
	 * Register entities
	 */
	static function register_entities()
	{
		$app = app();
		
		/* Add routes */
		$app->addEntity(\App\Bus\AppBus::class);
		$app->addEntity(\App\Console\SSLGenerateCommand::class);
		$app->addEntity(\App\Console\SSLUpdateCommand::class);
	}
	
	
	
	/**
	 * Request before
	 */
	static function request_before($res)
	{
		$res->container->add_breadcrumb(
			$res->container->base_url . "/",
			"Main"
		);
	}
	
	
	
	/**
	 * Method not found
	 */
	static function method_not_found($res)
	{
		$container = $res->container;
	}
	
	
	
	/**
	 * Routes
	 */
	static function routes($res)
	{
		// var_dump( $res->route_container->routes );
	}
	
	
	
	/**
	 * Base url
	 */
	static function base_url($res)
	{
		$res["base_url"] = $res->request->server->get('HTTP_X_FORWARDED_PREFIX', '');
	}
	
	
	
	/**
	 * Twig opt
	 */
	static function twig_opt($res)
	{
		$twig_opt = $res["twig_opt"];
		$twig_opt["cache"] = "/data/php/cache/twig";
		$res["twig_opt"] = $twig_opt;
	}
	
	
	
	/**
	 * Bus gateway
	 */
	static function bus_gateway($res)
	{
		$gateway = $res["project"];
		if ($gateway == "cloud_os")
		{
			$res["gateway"] = "http://" . env("CLOUD_OS_GATEWAY") . "/api/bus/";
		}
	}
	
	
	
	/**
	 * Create App
	 */
	static function createApp()
	{
		/* Create app */
		$app = create_app_instance();
		
		/* Add modules */
		$app->addModule(static::class);
		
		/* Run app */
		$app->init();
		return $app;
	}
	
}